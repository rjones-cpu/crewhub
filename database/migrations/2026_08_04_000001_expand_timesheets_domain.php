<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('major_projects', function (Blueprint $table) {
            $table->boolean('client_approval_required')->default(false)->after('icon');
        });

        Schema::table('timesheets', function (Blueprint $table) {
            $table->decimal('regular_hours', 7, 2)->default(0)->after('hours');
            $table->decimal('double_time_hours', 7, 2)->default(0)->after('overtime_hours');
            $table->decimal('travel_hours', 7, 2)->default(0)->after('double_time_hours');
            $table->decimal('standby_hours', 7, 2)->default(0)->after('travel_hours');
            $table->decimal('break_hours', 7, 2)->default(0)->after('standby_hours');
            $table->decimal('equipment_hours', 7, 2)->default(0)->after('break_hours');
            $table->boolean('client_approval_required')->default(false)->after('status');
            $table->json('day_entries')->nullable()->after('client_approval_required');
            $table->json('equipment_entries')->nullable()->after('day_entries');
            $table->json('compliance')->nullable()->after('equipment_entries');
            $table->json('status_history')->nullable()->after('compliance');
            $table->string('supervisor_name')->nullable()->after('status_history');
            $table->text('worker_comment')->nullable()->after('supervisor_name');
            $table->text('manager_comment')->nullable()->after('worker_comment');
            $table->text('client_comment')->nullable()->after('manager_comment');
            $table->string('worker_signature')->nullable()->after('client_comment');
            $table->timestamp('worker_signed_at')->nullable()->after('worker_signature');
            $table->foreignId('manager_approved_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            $table->timestamp('manager_approved_at')->nullable()->after('approved_at');
            $table->foreignId('client_approved_by')->nullable()->after('manager_approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('client_approved_at')->nullable()->after('client_approved_by');
            $table->foreignId('returned_by')->nullable()->after('client_approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('returned_at')->nullable()->after('returned_by');
            $table->text('return_reason')->nullable()->after('returned_at');
            $table->date('due_date')->nullable()->after('return_reason');
        });

        // Map legacy statuses onto the new approval workflow values.
        DB::table('timesheets')->where('status', 'pending')->update(['status' => 'submitted']);
        DB::table('timesheets')->where('status', 'approved')->update(['status' => 'fully_approved']);
    }

    public function down(): void
    {
        DB::table('timesheets')->where('status', 'submitted')->update(['status' => 'pending']);
        DB::table('timesheets')->where('status', 'fully_approved')->update(['status' => 'approved']);
        DB::table('timesheets')->whereIn('status', ['manager_approved', 'returned'])->update(['status' => 'pending']);

        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_approved_by');
            $table->dropConstrainedForeignId('client_approved_by');
            $table->dropConstrainedForeignId('returned_by');
            $table->dropColumn([
                'regular_hours',
                'double_time_hours',
                'travel_hours',
                'standby_hours',
                'break_hours',
                'equipment_hours',
                'client_approval_required',
                'day_entries',
                'equipment_entries',
                'compliance',
                'status_history',
                'supervisor_name',
                'worker_comment',
                'manager_comment',
                'client_comment',
                'worker_signature',
                'worker_signed_at',
                'manager_approved_at',
                'client_approved_at',
                'returned_at',
                'return_reason',
                'due_date',
            ]);
        });

        Schema::table('major_projects', function (Blueprint $table) {
            $table->dropColumn('client_approval_required');
        });
    }
};
