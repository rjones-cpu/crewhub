<?php

namespace Database\Seeders;

use App\Enums\ScheduleDayType;
use App\Enums\TimesheetStatus;
use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\Certification;
use App\Models\Company;
use App\Models\CompanyProjectMembership;
use App\Models\MajorProject;
use App\Models\MedicalRecord;
use App\Models\Timesheet;
use App\Models\TrainingRecord;
use App\Models\Worker;
use App\Models\WorkerActivity;
use App\Models\WorkerAssignment;
use App\Models\WorkerReadiness;
use App\Models\WorkerScheduleDay;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BakerHughesDemoSeeder extends Seeder
{
    private const PROJECT_CODE = 'BH-DEMO-01';

    private const WORKERS = [
        ['BH-DEMO-001', 'Amina', 'Yusuf', 'Field Engineer', 'Instrumentation Technician', 'amina.yusuf@example.test'],
        ['BH-DEMO-002', 'Daniel', 'Brooks', 'Site Supervisor', 'Site Supervisor', 'daniel.brooks@example.test'],
        ['BH-DEMO-003', 'Priya', 'Nair', 'HSE Advisor', 'Health and Safety', 'priya.nair@example.test'],
        ['BH-DEMO-004', 'Marcus', 'Lee', 'Mechanical Technician', 'Millwright', 'marcus.lee@example.test'],
        ['BH-DEMO-005', 'Sofia', 'Martinez', 'Electrical Technician', 'Industrial Electrician', 'sofia.martinez@example.test'],
        ['BH-DEMO-006', 'Noah', 'Williams', 'Equipment Operator', 'Heavy Equipment Operator', 'noah.williams@example.test'],
        ['BH-DEMO-007', 'Fatima', 'Khan', 'Quality Inspector', 'Quality Control', 'fatima.khan@example.test'],
        ['BH-DEMO-008', 'Ethan', 'Clark', 'Logistics Coordinator', 'Logistics', 'ethan.clark@example.test'],
    ];

    public function run(): void
    {
        $company = Company::query()->where('code', 'BKRH')->first();

        if (! $company) {
            throw new RuntimeException('Baker Hughes (BKRH) is missing. Run CompanySeeder first.');
        }

        DB::transaction(function () use ($company): void {
            $project = $this->project($company->id);
            $accommodation = $this->accommodation($company->id, $project);

            foreach (self::WORKERS as $index => $workerData) {
                $worker = $this->worker($company->id, $project->id, $workerData, $index);

                $this->assignment($worker, $project);
                $this->readiness($worker, $index);
                $this->complianceRecords($worker, $index);
                $this->accommodationAssignment($worker, $accommodation, $index);
                $this->timesheets($worker, $project, $index);
                $this->activity($worker, $project);
            }

            $this->schedule($company->id, $project->id);
        });
    }

    private function project(int $companyId): MajorProject
    {
        $project = MajorProject::withTrashed()->firstOrNew([
            'company_id' => $companyId,
            'code' => self::PROJECT_CODE,
        ]);

        $project->fill([
            'name' => 'Baker Hughes Demo Operations',
            'description' => 'Demonstration project populated with complete worker operational data.',
            'location' => 'North Field Site',
            'address' => 'North Field Operations Camp',
            'project_type' => 'Field Services',
            'project_number' => 'BH-OPS-2026',
            'po_number' => 'PO-DEMO-2026',
            'start_date' => Carbon::today()->subMonths(3),
            'end_date' => Carbon::today()->addYear(),
            'status' => 'active',
            'client_approval_required' => false,
            'modules' => MajorProject::defaultModules(),
        ]);
        $project->deleted_at = null;
        $project->save();

        CompanyProjectMembership::query()->updateOrCreate(
            ['company_id' => $companyId, 'major_project_id' => $project->id],
            ['role' => 'Owner', 'status' => 'active', 'joined_at' => now()],
        );

        return $project;
    }

    private function accommodation(int $companyId, MajorProject $project): Accommodation
    {
        return Accommodation::query()->updateOrCreate(
            [
                'company_id' => $companyId,
                'major_project_id' => $project->id,
                'name' => 'North Field Lodge',
            ],
            [
                'location' => 'North Field Operations Camp',
                'capacity' => 120,
                'occupied' => count(self::WORKERS),
                'status' => 'active',
            ],
        );
    }

    private function worker(
        int $companyId,
        int $projectId,
        array $workerData,
        int $index,
    ): Worker {
        [$employeeId, $firstName, $lastName, $position, $trade, $email] = $workerData;

        $worker = Worker::withTrashed()->firstOrNew([
            'company_id' => $companyId,
            'employee_id' => $employeeId,
        ]);

        $worker->fill([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => sprintf('+1 780 555 %04d', 1100 + $index),
            'date_of_birth' => Carbon::create(1982 + $index, ($index % 12) + 1, 10),
            'gender' => $index % 2 === 0 ? 'Female' : 'Male',
            'position' => $position,
            'trade' => $trade,
            'location' => 'North Field Site',
            'employer_name' => 'Baker Hughes',
            'status' => $index === 7 ? 'mobilizing' : 'active',
            'on_site' => $index < 6,
            'primary_project_id' => $projectId,
            'start_date' => Carbon::today()->subMonths(2)->subDays($index * 3),
            'end_date' => Carbon::today()->addMonths(10),
            'notes' => 'Dummy worker created for Baker Hughes system demonstrations.',
            'documents' => [
                ['name' => 'Site orientation acknowledgement', 'status' => 'verified'],
                ['name' => 'Emergency contact form', 'status' => 'verified'],
            ],
            'module_access' => true,
            'schedule_access' => true,
            'timesheet_access' => true,
            'lms_access' => true,
            'journey_access' => true,
        ]);
        $worker->deleted_at = null;
        $worker->save();

        return $worker;
    }

    private function assignment(Worker $worker, MajorProject $project): void
    {
        WorkerAssignment::query()->updateOrCreate(
            [
                'worker_id' => $worker->id,
                'major_project_id' => $project->id,
                'start_date' => $worker->start_date,
            ],
            [
                'company_id' => $worker->company_id,
                'role' => $worker->position,
                'end_date' => $worker->end_date,
                'status' => 'active',
                'is_primary' => true,
            ],
        );
    }

    private function readiness(Worker $worker, int $index): void
    {
        $atRisk = $index === 6;

        WorkerReadiness::query()->updateOrCreate(
            ['worker_id' => $worker->id],
            [
                'company_id' => $worker->company_id,
                'overall_status' => $atRisk ? 'at_risk' : 'ready',
                'medical_status' => 'valid',
                'certification_status' => $atRisk ? 'expiring' : 'valid',
                'training_status' => 'complete',
                'journey_status' => 'approved',
                'accommodation_status' => 'confirmed',
                'site_access_status' => 'approved',
                'last_checked_at' => now(),
                'notes' => $atRisk ? 'One certification expires within 30 days.' : 'All requirements verified.',
            ],
        );
    }

    private function complianceRecords(Worker $worker, int $index): void
    {
        $certification = Certification::withTrashed()->updateOrCreate(
            ['worker_id' => $worker->id, 'name' => 'H2S Alive'],
            [
                'company_id' => $worker->company_id,
                'certificate_number' => "H2S-{$worker->employee_id}",
                'issuer' => 'Energy Safety Canada',
                'issued_at' => Carbon::today()->subMonths(8),
                'expires_at' => $index === 6
                    ? Carbon::today()->addDays(24)
                    : Carbon::today()->addMonths(16),
                'status' => 'valid',
                'deleted_at' => null,
            ],
        );

        TrainingRecord::query()->updateOrCreate(
            ['worker_id' => $worker->id, 'course_name' => 'Site Safety Orientation'],
            [
                'company_id' => $worker->company_id,
                'certification_id' => $certification->id,
                'provider' => 'Baker Hughes Learning',
                'category' => 'Safety',
                'is_required' => true,
                'status' => 'completed',
                'completed_at' => Carbon::today()->subMonths(2),
                'expires_at' => Carbon::today()->addMonths(10),
                'score' => 94 - $index,
            ],
        );

        MedicalRecord::withTrashed()->updateOrCreate(
            ['worker_id' => $worker->id, 'exam_type' => 'Pre-employment medical'],
            [
                'company_id' => $worker->company_id,
                'status' => 'fit',
                'examined_at' => Carbon::today()->subMonths(4),
                'expires_at' => Carbon::today()->addMonths(8),
                'provider' => 'North Field Occupational Health',
                'notes' => 'Cleared for assigned duties.',
                'deleted_at' => null,
            ],
        );
    }

    private function accommodationAssignment(
        Worker $worker,
        Accommodation $accommodation,
        int $index,
    ): void {
        AccommodationAssignment::query()->updateOrCreate(
            ['accommodation_id' => $accommodation->id, 'worker_id' => $worker->id],
            [
                'company_id' => $worker->company_id,
                'room_number' => (string) (201 + $index),
                'check_in' => Carbon::today()->subDays(10),
                'check_out' => Carbon::today()->addDays(18),
                'status' => $worker->on_site ? 'checked_in' : 'reserved',
            ],
        );
    }

    private function timesheets(Worker $worker, MajorProject $project, int $workerIndex): void
    {
        for ($weekOffset = 3; $weekOffset >= 0; $weekOffset--) {
            $periodStart = Carbon::today()->startOfWeek()->subWeeks($weekOffset);
            $periodEnd = $periodStart->copy()->endOfWeek();
            $status = $this->timesheetStatus($weekOffset, $workerIndex);
            $entries = $this->dayEntries($periodStart, $workerIndex);
            $regularHours = collect($entries)->sum('regular_hours');
            $overtimeHours = collect($entries)->sum('overtime_hours');
            $travelHours = collect($entries)->sum('travel_hours');

            Timesheet::query()->updateOrCreate(
                [
                    'worker_id' => $worker->id,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                ],
                [
                    'company_id' => $worker->company_id,
                    'major_project_id' => $project->id,
                    'due_date' => $periodEnd->copy()->addDays(2),
                    'hours' => $regularHours + $overtimeHours + $travelHours,
                    'regular_hours' => $regularHours,
                    'overtime_hours' => $overtimeHours,
                    'double_time_hours' => 0,
                    'travel_hours' => $travelHours,
                    'standby_hours' => 0,
                    'break_hours' => collect($entries)->sum('break_hours'),
                    'equipment_hours' => 0,
                    'status' => $status,
                    'client_approval_required' => false,
                    'day_entries' => $entries,
                    'equipment_entries' => [],
                    'compliance' => [
                        'safety_meeting' => true,
                        'toolbox_talk' => true,
                        'incident_report' => false,
                        'attachments' => false,
                        'signature' => $status !== TimesheetStatus::Draft,
                        'worker_declaration' => $status !== TimesheetStatus::Draft,
                    ],
                    'status_history' => $this->statusHistory($status, $periodEnd),
                    'supervisor_name' => 'Daniel Brooks',
                    'worker_comment' => 'Demo weekly time entry.',
                    'worker_signature' => $status === TimesheetStatus::Draft ? null : $worker->full_name,
                    'worker_signed_at' => $status === TimesheetStatus::Draft ? null : $periodEnd->copy()->setTime(17, 30),
                    'submitted_at' => $status === TimesheetStatus::Draft ? null : $periodEnd->copy()->addDay(),
                    'approved_at' => $status === TimesheetStatus::FullyApproved
                        ? $periodEnd->copy()->addDays(2)
                        : null,
                ],
            );
        }
    }

    private function timesheetStatus(int $weekOffset, int $workerIndex): TimesheetStatus
    {
        if ($weekOffset >= 2) {
            return TimesheetStatus::FullyApproved;
        }

        if ($weekOffset === 1) {
            return $workerIndex % 3 === 0
                ? TimesheetStatus::Returned
                : TimesheetStatus::Submitted;
        }

        return TimesheetStatus::Draft;
    }

    private function dayEntries(Carbon $periodStart, int $workerIndex): array
    {
        $entries = [];

        for ($day = 0; $day < 7; $day++) {
            $date = $periodStart->copy()->addDays($day);
            $isWorkDay = ! $date->isWeekend();
            $regularHours = $isWorkDay ? 10 : 0;
            $overtimeHours = $isWorkDay && ($day + $workerIndex) % 3 === 0 ? 1 : 0;
            $travelHours = $isWorkDay && $day === 0 ? 1 : 0;

            $entries[] = [
                'date' => $date->toDateString(),
                'day_label' => $date->format('D'),
                'shift' => $isWorkDay ? ($workerIndex % 4 === 0 ? 'Night' : 'Day') : 'Off',
                'start_time' => $isWorkDay ? '07:00' : null,
                'end_time' => $isWorkDay ? '17:00' : null,
                'break_hours' => $isWorkDay ? 0.5 : 0,
                'regular_hours' => $regularHours,
                'overtime_hours' => $overtimeHours,
                'double_time_hours' => 0,
                'travel_hours' => $travelHours,
                'standby_hours' => 0,
                'total_hours' => $regularHours + $overtimeHours + $travelHours,
                'work_location' => $isWorkDay ? 'North Field Site' : null,
                'task' => $isWorkDay ? 'Field operations and maintenance' : null,
                'notes' => '',
            ];
        }

        return $entries;
    }

    private function statusHistory(TimesheetStatus $status, Carbon $periodEnd): array
    {
        $history = [[
            'status' => TimesheetStatus::Draft->value,
            'label' => TimesheetStatus::Draft->label(),
            'at' => $periodEnd->copy()->subDays(6)->toIso8601String(),
            'by' => null,
            'note' => 'Timesheet created',
            'current' => $status === TimesheetStatus::Draft,
        ]];

        if ($status !== TimesheetStatus::Draft) {
            $history[] = [
                'status' => TimesheetStatus::Submitted->value,
                'label' => TimesheetStatus::Submitted->label(),
                'at' => $periodEnd->copy()->addDay()->toIso8601String(),
                'by' => null,
                'note' => 'Submitted by worker',
                'current' => $status === TimesheetStatus::Submitted,
            ];
        }

        if ($status === TimesheetStatus::Returned) {
            $history[] = [
                'status' => TimesheetStatus::Returned->value,
                'label' => TimesheetStatus::Returned->label(),
                'at' => $periodEnd->copy()->addDays(2)->toIso8601String(),
                'by' => null,
                'note' => 'Please confirm the Monday travel hour.',
                'current' => true,
            ];
        }

        if ($status === TimesheetStatus::FullyApproved) {
            $history[] = [
                'status' => TimesheetStatus::FullyApproved->value,
                'label' => TimesheetStatus::FullyApproved->label(),
                'at' => $periodEnd->copy()->addDays(2)->toIso8601String(),
                'by' => null,
                'note' => 'Approved for demo purposes',
                'current' => true,
            ];
        }

        return $history;
    }

    private function activity(Worker $worker, MajorProject $project): void
    {
        WorkerActivity::query()->updateOrCreate(
            [
                'worker_id' => $worker->id,
                'type' => 'project_assignment',
                'description' => 'Assigned to Baker Hughes Demo Operations.',
            ],
            [
                'company_id' => $worker->company_id,
                'metadata' => ['major_project_id' => $project->id, 'demo' => true],
            ],
        );
    }

    private function schedule(int $companyId, int $projectId): void
    {
        $workers = Worker::query()
            ->where('company_id', $companyId)
            ->whereIn('employee_id', array_column(self::WORKERS, 0))
            ->orderBy('employee_id')
            ->get();

        WorkerScheduleDay::query()->whereIn('worker_id', $workers->pluck('id'))->delete();

        $start = Carbon::today()->subDays(30);
        $end = Carbon::today()->addDays(80);
        $rotations = [[21, 7], [14, 14], [20, 8], [14, 7]];
        $rows = [];

        foreach ($workers as $index => $worker) {
            [$workDays, $offDays] = $rotations[$index % count($rotations)];
            $cycle = $workDays + $offDays;
            $offset = ($index * 5) % $cycle;

            // Counted rather than derived from diffInDays(), which is signed in
            // Carbon 3 and silently pushed every position negative.
            $dayNumber = 0;

            for ($date = $start->copy(); $date->lte($end); $date->addDay(), $dayNumber++) {
                $position = ($dayNumber + $offset) % $cycle;

                // Off days carry no row at all, so the board leaves them white.
                if ($position >= $workDays) {
                    continue;
                }

                $isTravelDay = $position === 0 || $position === $workDays - 1;
                $rows[] = [
                    'company_id' => $companyId,
                    'worker_id' => $worker->id,
                    'major_project_id' => $projectId,
                    'date' => $date->toDateString(),
                    'day_type' => $isTravelDay ? ScheduleDayType::Travel->value : ScheduleDayType::Work->value,
                    'needs_room' => $position !== $workDays - 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('worker_schedule_days')->insert($chunk);
        }
    }
}
