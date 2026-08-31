<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('make', 60);
            $table->string('model', 60);
            $table->unsignedSmallInteger('year');
            $table->string('vehicle_type', 30);
            $table->string('vin', 40);
            $table->string('license_plate', 20);
            $table->foreignId('assigned_driver_id')->nullable()->constrained('workers')->nullOnDelete();

            $table->boolean('has_attachments')->default(false);
            $table->string('insurance_document_path')->nullable();
            $table->string('insurance_provider', 120)->nullable();
            $table->string('policy_number', 60)->nullable();
            $table->string('coverage_type', 40)->nullable();
            $table->decimal('coverage_amount', 12, 2)->nullable();
            $table->date('policy_start_date')->nullable();
            $table->date('policy_end_date')->nullable();

            $table->string('base_location', 120)->nullable();
            $table->string('purpose', 60)->nullable();
            $table->text('additional_notes')->nullable();
            $table->text('additional_details')->nullable();

            $table->string('availability', 30)->default('available');
            $table->string('transmission', 20)->nullable();
            $table->unsignedInteger('odometer_km')->nullable();
            $table->text('known_issues')->nullable();

            $table->json('equipment')->nullable();
            $table->text('maintenance_notes')->nullable();
            $table->date('last_service_at')->nullable();
            $table->date('next_service_due_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'vin']);
            $table->unique(['company_id', 'license_plate']);
            $table->index(['company_id', 'availability']);
        });

        // Journeys keep their denormalised plate/model strings for display, but a real
        // vehicle link is what lets the risk engine read type and insurance validity.
        Schema::table('journeys', function (Blueprint $table) {
            $table->foreignId('vehicle_id')
                ->nullable()
                ->after('vehicle_model')
                ->constrained('vehicles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('journeys', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vehicle_id');
        });

        Schema::dropIfExists('vehicles');
    }
};
