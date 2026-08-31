<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camp_company_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('camp_company_id')->unique();
            $table->unsignedBigInteger('camp_id')->nullable()->index();
            $table->string('camp_company_name');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('company_id');
        });

        Schema::create('camp_project_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('major_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('camp_company_link_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('camp_project_id')->unique();
            $table->string('camp_project_name');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique('major_project_id');
        });

        Schema::create('camp_booking_worker_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('camp_company_link_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('camp_booking_id')->unique();
            $table->string('booking_code')->nullable()->index();
            $table->string('source_email')->nullable()->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('camp_timesheet_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timesheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('camp_booking_worker_link_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('camp_schedule_id');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('schedule_fingerprint', 64);
            $table->timestamp('last_synced_at');
            $table->timestamps();

            $table->unique(
                ['camp_booking_worker_link_id', 'camp_schedule_id', 'period_start'],
                'camp_source_booking_schedule_period_unique'
            );
            $table->index(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camp_timesheet_sources');
        Schema::dropIfExists('camp_booking_worker_links');
        Schema::dropIfExists('camp_project_links');
        Schema::dropIfExists('camp_company_links');
    }
};
