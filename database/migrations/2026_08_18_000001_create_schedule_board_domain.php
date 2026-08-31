<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per worker per calendar day per project. The schedule board reads
        // this table directly, so `needs_room` is stored rather than derived: a travel
        // day may or may not consume a bed depending on arrival vs departure.
        Schema::create('worker_schedule_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_project_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('day_type')->default('work')->index();
            $table->boolean('needs_room')->default(true);
            $table->timestamps();
            $table->unique(['worker_id', 'major_project_id', 'date'], 'wsd_worker_project_date_unique');
            $table->index(['major_project_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_schedule_days');
    }
};
