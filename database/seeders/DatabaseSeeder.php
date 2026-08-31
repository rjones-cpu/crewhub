<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a clean, login-ready system: the platform super admin plus every company
     * with its single administrator. Operational data (projects, workers,
     * timesheets) comes from the Camp sync, not from seeders.
     */
    public function run(): void
    {
        $this->call([
            SuperAdminSeeder::class,
            CompanySeeder::class,
            ModuleSeeder::class,
            // Standard journey assessment questions, seeded per company as configuration.
            JourneyQuestionSeeder::class,
            // No-op until major projects exist; backfills the reporting hierarchy
            // when re-run after a Camp sync.
            HierarchySeeder::class,
            // No-op until workers exist; builds rotation coverage for the schedule board.
            ScheduleSeeder::class,
        ]);
    }
}
