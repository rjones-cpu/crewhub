<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_schedule_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending')->index();
            $table->timestamps();
            $table->unique(['worker_id', 'major_project_id'], 'wsd_worker_project_draft_unique');
        });

        Schema::create('worker_schedule_draft_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_schedule_draft_id')->constrained('worker_schedule_drafts')->cascadeOnDelete();
            $table->date('date');
            $table->string('from_type');
            $table->string('to_type');
            $table->boolean('needs_room')->default(true);
            $table->timestamps();
            $table->unique(['worker_schedule_draft_id', 'date'], 'wsdd_draft_date_unique');
        });

        Schema::create('schedule_modification_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->date('check_in')->nullable();
            $table->date('check_out')->nullable();
            $table->date('previous_check_in')->nullable();
            $table->date('previous_check_out')->nullable();
            $table->unsignedInteger('change_count')->default(0);
            $table->string('status')->default('pending')->index();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_modification_requests');
        Schema::dropIfExists('worker_schedule_draft_days');
        Schema::dropIfExists('worker_schedule_drafts');
    }
};
