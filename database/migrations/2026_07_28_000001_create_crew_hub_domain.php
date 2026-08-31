<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('logo')->nullable();
            $table->string('industry')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('role')->default('read_only')->index();
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->softDeletes();
        });

        Schema::create('major_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('project_type')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('planned')->index();
            $table->string('icon')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('employee_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('position')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->default('active')->index();
            $table->string('avatar')->nullable();
            $table->boolean('on_site')->default(false);
            $table->foreignId('primary_project_id')->nullable()->constrained('major_projects')->nullOnDelete();
            $table->boolean('module_access')->default(true);
            $table->boolean('schedule_access')->default(true);
            $table->boolean('timesheet_access')->default(true);
            $table->boolean('lms_access')->default(true);
            $table->boolean('journey_access')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'employee_id']);
            $table->index(['company_id', 'primary_project_id', 'status']);
        });

        Schema::create('worker_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status')->default('active')->index();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['worker_id', 'major_project_id', 'start_date'], 'worker_project_start_unique');
        });

        Schema::create('worker_readiness', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('overall_status')->default('pending_review')->index();
            foreach (['medical', 'certification', 'training', 'journey', 'accommodation', 'site_access'] as $status) {
                $table->string("{$status}_status")->default('pending');
            }
            $table->timestamp('last_checked_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('certificate_number')->nullable();
            $table->string('issuer')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->string('status')->default('valid')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('exam_type');
            $table->string('status')->index();
            $table->date('examined_at')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->string('provider')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('training_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('course_name');
            $table->string('provider')->nullable();
            $table->string('status')->default('not_started')->index();
            $table->date('completed_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('journeys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('origin');
            $table->string('destination');
            $table->dateTime('departure_at');
            $table->dateTime('arrival_at')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'departure_at']);
        });

        Schema::create('accommodations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('location');
            $table->unsignedInteger('capacity');
            $table->unsignedInteger('occupied')->default(0);
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('accommodation_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('room_number')->nullable();
            $table->date('check_in');
            $table->date('check_out')->nullable();
            $table->string('status')->default('reserved')->index();
            $table->timestamps();
        });

        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_project_id')->nullable()->constrained()->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('hours', 7, 2)->default(0);
            $table->decimal('overtime_hours', 7, 2)->default(0);
            $table->string('status')->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['worker_id', 'period_start', 'period_end']);
        });

        Schema::create('priority_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('issue')->nullable();
            $table->unsignedInteger('affected_count')->default(0);
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('owner_name')->nullable();
            $table->date('due_date')->nullable()->index();
            $table->string('status')->default('open')->index();
            $table->string('severity')->default('medium')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('worker_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index();
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('schedule_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_project_id')->constrained()->cascadeOnDelete();
            $table->date('forecast_date');
            $table->unsignedInteger('required_workers');
            $table->unsignedInteger('scheduled_workers');
            $table->timestamps();
            $table->unique(['company_id', 'major_project_id', 'forecast_date'], 'schedule_forecast_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_forecasts');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('worker_activities');
        Schema::dropIfExists('priority_actions');
        Schema::dropIfExists('timesheets');
        Schema::dropIfExists('accommodation_assignments');
        Schema::dropIfExists('accommodations');
        Schema::dropIfExists('journeys');
        Schema::dropIfExists('training_records');
        Schema::dropIfExists('medical_records');
        Schema::dropIfExists('certifications');
        Schema::dropIfExists('worker_readiness');
        Schema::dropIfExists('worker_assignments');
        Schema::dropIfExists('workers');
        Schema::dropIfExists('major_projects');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['role', 'phone', 'avatar', 'is_active', 'deleted_at']);
        });
        Schema::dropIfExists('companies');
    }
};
