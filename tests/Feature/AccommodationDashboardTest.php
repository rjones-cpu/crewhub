<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\Company;
use App\Models\MajorProject;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccommodationDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_accommodation_dashboard_is_scoped_to_the_selected_project(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => Role::CompanyAdmin,
        ]);
        $selectedProject = MajorProject::factory()->create([
            'company_id' => $company->id,
            'name' => 'Edgewater Pipeline Project',
            'status' => 'active',
        ]);
        $otherProject = MajorProject::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        $primaryLodge = Accommodation::factory()->create([
            'company_id' => $company->id,
            'major_project_id' => $selectedProject->id,
            'name' => 'Mt. Bracey Lodge',
            'capacity' => 320,
            'occupied' => 252,
        ]);
        Accommodation::factory()->create([
            'company_id' => $company->id,
            'major_project_id' => $otherProject->id,
            'capacity' => 90,
            'occupied' => 40,
        ]);

        $worker = Worker::factory()->create(['company_id' => $company->id]);
        AccommodationAssignment::factory()->create([
            'company_id' => $company->id,
            'accommodation_id' => $primaryLodge->id,
            'worker_id' => $worker->id,
            'check_in' => now()->addDays(3)->toDateString(),
            'check_out' => now()->addMonth()->toDateString(),
            'status' => 'reserved',
        ]);

        $this->actingAs($user)
            ->withSession(['current_project_id' => $selectedProject->id])
            ->get(route('accommodations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Accommodations/Index')
                ->has('accommodations.data', 1)
                ->where('linkedProject.id', $selectedProject->id)
                ->where('linkedProject.name', 'Edgewater Pipeline Project')
                ->where('overview.primary_lodge', 'Mt. Bracey Lodge')
                ->where('overview.facility_count', 1)
                ->where('overview.total_rooms', 320)
                ->where('overview.rooms_used', 252)
                ->where('overview.upcoming_arrivals', 1));
    }

    public function test_accommodation_dashboard_handles_a_project_without_facilities(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => Role::CompanyAdmin,
        ]);
        $project = MajorProject::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->withSession(['current_project_id' => $project->id])
            ->get(route('accommodations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('accommodations.data', 0)
                ->where('linkedProject.id', $project->id)
                ->where('overview.facility_count', 0)
                ->where('overview.total_rooms', 0)
                ->where('overview.rooms_used', 0)
                ->where('overview.upcoming_arrivals', 0));
    }
}
