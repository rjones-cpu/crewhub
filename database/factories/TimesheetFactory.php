<?php

namespace Database\Factories;

use App\Enums\TimesheetStatus;
use App\Models\Company;
use App\Models\Timesheet;
use App\Models\Worker;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimesheetFactory extends Factory
{
    protected $model = Timesheet::class;

    public function definition(): array
    {
        $start = now()->startOfWeek()->subWeeks(fake()->numberBetween(0, 4));
        $end = (clone $start)->endOfWeek();
        $entries = $this->buildDayEntries($start);
        $equipment = $this->buildEquipmentEntries($start);
        $totals = $this->sumEntries($entries, $equipment);
        $status = fake()->randomElement([
            TimesheetStatus::Draft,
            TimesheetStatus::Submitted,
            TimesheetStatus::Returned,
            TimesheetStatus::ManagerApproved,
            TimesheetStatus::FullyApproved,
            TimesheetStatus::Rejected,
        ]);

        return [
            'worker_id' => Worker::factory(),
            'company_id' => Company::factory(),
            'period_start' => $start,
            'period_end' => $end,
            'due_date' => (clone $end)->addDays(2),
            'hours' => $totals['total'],
            'regular_hours' => $totals['regular'],
            'overtime_hours' => $totals['overtime'],
            'double_time_hours' => $totals['double_time'],
            'travel_hours' => $totals['travel'],
            'standby_hours' => $totals['standby'],
            'break_hours' => $totals['break'],
            'equipment_hours' => $totals['equipment'],
            'status' => $status,
            'client_approval_required' => fake()->boolean(30),
            'day_entries' => $entries,
            'equipment_entries' => $equipment,
            'compliance' => [
                'safety_meeting' => fake()->boolean(80),
                'toolbox_talk' => fake()->boolean(75),
                'incident_report' => fake()->boolean(10),
                'attachments' => fake()->boolean(40),
                'signature' => in_array($status, [
                    TimesheetStatus::Submitted,
                    TimesheetStatus::ManagerApproved,
                    TimesheetStatus::FullyApproved,
                ], true),
                'worker_declaration' => in_array($status, [
                    TimesheetStatus::Submitted,
                    TimesheetStatus::ManagerApproved,
                    TimesheetStatus::FullyApproved,
                ], true),
            ],
            'status_history' => [
                [
                    'status' => TimesheetStatus::Draft->value,
                    'label' => 'Draft',
                    'at' => $start->copy()->toIso8601String(),
                    'by' => null,
                    'note' => 'Timesheet created',
                    'current' => $status === TimesheetStatus::Draft,
                ],
            ],
            'supervisor_name' => fake()->name(),
            'submitted_at' => in_array($status, [
                TimesheetStatus::Submitted,
                TimesheetStatus::ManagerApproved,
                TimesheetStatus::FullyApproved,
                TimesheetStatus::Rejected,
                TimesheetStatus::Returned,
            ], true) ? $end->copy()->addDay() : null,
        ];
    }

    protected function buildDayEntries(Carbon $start): array
    {
        $entries = [];
        $locations = ['Pad A', 'Pad B', 'Laydown Yard', 'Access Road', 'Camp'];
        $tasks = ['Earthworks', 'Equipment Operation', 'Site Prep', 'Haulage', 'Standby Cover'];

        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);
            $isWeekend = $date->isWeekend();
            $isOff = $isWeekend || fake()->boolean(15);
            $regular = $isOff ? 0 : fake()->randomElement([8, 10, 11]);
            $overtime = (! $isOff && $regular >= 10) ? fake()->randomElement([0, 1, 2]) : 0;
            $travel = (! $isOff && fake()->boolean(20)) ? 1 : 0;
            $standby = (! $isOff && fake()->boolean(10)) ? 4 : 0;
            $break = $isOff ? 0 : 0.5;
            $total = $regular + $overtime + $travel + $standby;

            $entries[] = [
                'date' => $date->toDateString(),
                'day_label' => $date->format('D'),
                'shift' => $isOff ? 'Off' : fake()->randomElement(['Day', 'Night']),
                'start_time' => $isOff ? null : '07:00',
                'end_time' => $isOff ? null : sprintf('%02d:00', 7 + (int) $regular),
                'break_hours' => $break,
                'regular_hours' => $regular,
                'overtime_hours' => $overtime,
                'double_time_hours' => 0,
                'travel_hours' => $travel,
                'standby_hours' => $standby,
                'total_hours' => $total,
                'work_location' => $isOff ? null : fake()->randomElement($locations),
                'task' => $isOff ? null : fake()->randomElement($tasks),
                'notes' => '',
            ];
        }

        return $entries;
    }

    protected function buildEquipmentEntries(Carbon $start): array
    {
        $types = ['Backhoe', 'Excavator', 'Dozer', 'Loader', 'Dump Truck'];
        $count = fake()->numberBetween(1, 4);
        $entries = [];

        for ($i = 0; $i < $count; $i++) {
            $date = $start->copy()->addDays(fake()->numberBetween(0, 4));
            $hours = fake()->randomElement([4, 6, 8, 10]);

            $entries[] = [
                'id' => 'eq-'.($i + 1),
                'day' => $date->format('D'),
                'date' => $date->toDateString(),
                'equipment_type' => fake()->randomElement($types),
                'unit_id' => strtoupper(fake()->bothify('??-###')),
                'start_time' => '07:00',
                'end_time' => sprintf('%02d:00', 7 + $hours),
                'hours' => $hours,
                'cost_code' => 'CC-'.fake()->numberBetween(100, 400),
                'work_activity' => fake()->randomElement(['Excavation', 'Backfill', 'Haulage', 'Site Prep']),
                'fuel_notes' => fake()->boolean(30) ? 'Refueled mid-shift' : '',
                'manager' => fake()->name(),
            ];
        }

        return $entries;
    }

    protected function sumEntries(array $entries, array $equipment): array
    {
        return [
            'regular' => collect($entries)->sum('regular_hours'),
            'overtime' => collect($entries)->sum('overtime_hours'),
            'double_time' => collect($entries)->sum('double_time_hours'),
            'travel' => collect($entries)->sum('travel_hours'),
            'standby' => collect($entries)->sum('standby_hours'),
            'break' => collect($entries)->sum('break_hours'),
            'total' => collect($entries)->sum('total_hours'),
            'equipment' => collect($equipment)->sum('hours'),
        ];
    }
}
