<?php

namespace App\Console\Commands;

use App\Models\MajorProject;
use App\Services\ServiceRating\ServiceRatingSnapshotService;
use Illuminate\Console\Command;

class RecalculateServiceRatings extends Command
{
    protected $signature = 'service-rating:recalculate {--project=}';

    protected $description = 'Recalculate and publish CH-11 service rating snapshots';

    public function handle(ServiceRatingSnapshotService $snapshots): int
    {
        $projects = MajorProject::query()
            ->when($this->option('project'), fn ($query, $id) => $query->where('id', $id))
            ->where('status', 'active')
            ->get(['id', 'company_id', 'name']);

        if ($projects->isEmpty()) {
            $this->warn('No active major projects found.');

            return self::SUCCESS;
        }

        foreach ($projects as $project) {
            $snapshot = $snapshots->recalculateAndPublish(
                (int) $project->company_id,
                (int) $project->id,
            );

            $this->line(sprintf(
                '%s → %s (snapshot #%s)',
                $project->name,
                $snapshot->overall_grade?->value ?? '—',
                $snapshot->sequence_no,
            ));
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
