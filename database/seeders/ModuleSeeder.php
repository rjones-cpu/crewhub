<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

/**
 * Seeds the platform module catalog from the Crew Hub sidebar order.
 * Major Projects and LMS are paid add-ons; all other modules stay free so
 * existing behavior is preserved until a Super Admin marks them paid.
 */
class ModuleSeeder extends Seeder
{
    public const CATALOG = [
        [
            'key' => 'company_command',
            'name' => 'Company Command',
            'description' => 'Company-wide command center and performance overview.',
            'is_paid' => false,
            'sort_order' => 10,
        ],
        [
            'key' => 'workers',
            'name' => 'Workers',
            'description' => 'Workforce roster and worker profiles.',
            'is_paid' => false,
            'sort_order' => 20,
        ],
        [
            'key' => 'hierarchy',
            'name' => 'Hierarchy',
            'description' => 'Manager connections and responsibility delegations.',
            'is_paid' => false,
            'sort_order' => 30,
        ],
        [
            'key' => Module::KEY_MAJOR_PROJECTS,
            'name' => 'Major Projects',
            'description' => 'Create, own, and participate in major projects.',
            'is_paid' => true,
            'sort_order' => 40,
        ],
        [
            'key' => 'schedule',
            'name' => 'Schedule',
            'description' => 'Crew scheduling and workforce outlook.',
            'is_paid' => false,
            'sort_order' => 50,
        ],
        [
            'key' => 'timesheets',
            'name' => 'Timesheets',
            'description' => 'Timesheet entry, approval, and reporting.',
            'is_paid' => false,
            'sort_order' => 60,
        ],
        [
            'key' => 'readiness',
            'name' => 'Readiness',
            'description' => 'Worker readiness and mobilization checks.',
            'is_paid' => false,
            'sort_order' => 70,
        ],
        [
            'key' => 'journey_management',
            'name' => 'Journey Management',
            'description' => 'Travel and journey planning for workers.',
            'is_paid' => false,
            'sort_order' => 80,
        ],
        [
            'key' => 'accommodation',
            'name' => 'Accommodation',
            'description' => 'Lodging reservations and accommodation status.',
            'is_paid' => false,
            'sort_order' => 90,
        ],
        [
            'key' => Module::KEY_LMS,
            'name' => 'LMS',
            'description' => 'Learning management and training records.',
            'is_paid' => true,
            'sort_order' => 100,
        ],
        [
            'key' => 'communications',
            'name' => 'Communications',
            'description' => 'Company messaging and announcements.',
            'is_paid' => false,
            'sort_order' => 110,
        ],
        [
            'key' => 'equipment',
            'name' => 'Equipment',
            'description' => 'Equipment inventory and assignments.',
            'is_paid' => false,
            'sort_order' => 120,
        ],
        [
            'key' => 'documents',
            'name' => 'Documents',
            'description' => 'Document library and compliance files.',
            'is_paid' => false,
            'sort_order' => 130,
        ],
        [
            'key' => 'settings',
            'name' => 'Settings',
            'description' => 'Company and account configuration.',
            'is_paid' => false,
            'sort_order' => 140,
        ],
    ];

    public function run(): void
    {
        foreach (self::CATALOG as $module) {
            Module::query()->updateOrCreate(
                ['key' => $module['key']],
                [
                    'name' => $module['name'],
                    'description' => $module['description'],
                    'is_paid' => $module['is_paid'],
                    'is_active' => true,
                    'sort_order' => $module['sort_order'],
                ],
            );
        }
    }
}
