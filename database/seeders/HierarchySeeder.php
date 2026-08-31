<?php

namespace Database\Seeders;

use App\Enums\DelegationStatus;
use App\Enums\ManagerRelationship;
use App\Enums\Role;
use App\Models\AssignmentActivity;
use App\Models\Company;
use App\Models\MajorProject;
use App\Models\ProjectManagerLink;
use App\Models\ResponsibilityDelegation;
use App\Models\User;
use App\Services\Hierarchy\HierarchyService;
use Illuminate\Database\Seeder;

/**
 * Backfills the reporting hierarchy for companies that don't have one yet.
 * Safe to run on its own against an already-seeded database.
 */
class HierarchySeeder extends Seeder
{
    public function run(): void
    {
        foreach (Company::all() as $company) {
            $project = MajorProject::query()
                ->where('company_id', $company->id)
                ->orderBy('name')
                ->first();

            if (! $project) {
                continue;
            }

            $alreadyLinked = ProjectManagerLink::query()
                ->where('major_project_id', $project->id)
                ->exists();

            if ($alreadyLinked) {
                continue;
            }

            $candidates = User::query()
                ->where('company_id', $company->id)
                ->orderByRaw("CASE role WHEN ? THEN 1 WHEN ? THEN 2 ELSE 3 END", [
                    Role::WorkforceManager->value,
                    Role::CompanyAdmin->value,
                ])
                ->limit(2)
                ->get();

            if ($candidates->isEmpty()) {
                continue;
            }

            $primary = ProjectManagerLink::create([
                'company_id' => $company->id,
                'major_project_id' => $project->id,
                'user_id' => $candidates->first()->id,
                'title' => 'Major Project Manager',
                'relationship' => ManagerRelationship::Primary,
            ]);

            $connected = $candidates->count() > 1
                ? ProjectManagerLink::create([
                    'company_id' => $company->id,
                    'major_project_id' => $project->id,
                    'user_id' => $candidates->last()->id,
                    'title' => 'Deputy Project Manager',
                    'relationship' => ManagerRelationship::Connected,
                ])
                : $primary;

            foreach (HierarchyService::AREAS as $index => $area) {
                $delegated = $area !== 'Journey Management';
                $link = $index % 2 === 0 ? $primary : $connected;

                ResponsibilityDelegation::create([
                    'company_id' => $company->id,
                    'major_project_id' => $project->id,
                    'project_manager_link_id' => $delegated ? $link->id : null,
                    'area' => $area,
                    'status' => $delegated ? DelegationStatus::Accepted : DelegationStatus::NotDelegated,
                    'is_delegable' => $delegated,
                ]);
            }

            $entries = [
                [$primary->user_id, 'Manager connected (Primary)', "Added as Primary manager for {$project->name}"],
                [$connected->user_id, 'Manager connected', "Added as Connected manager for {$project->name}"],
                [null, 'Crew Hub connected', "Linked to {$project->name}"],
            ];

            foreach ($entries as $offset => [$userId, $action, $details]) {
                AssignmentActivity::create([
                    'company_id' => $company->id,
                    'major_project_id' => $project->id,
                    'user_id' => $userId,
                    'actor_name' => $userId ? null : 'Crew Hub',
                    'action' => $action,
                    'details' => $details,
                    'created_at' => now()->subHours($offset + 1),
                    'updated_at' => now()->subHours($offset + 1),
                ]);
            }
        }
    }
}
