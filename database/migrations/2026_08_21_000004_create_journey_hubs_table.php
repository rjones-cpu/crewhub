<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journey_hubs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('name', 120);
            $table->string('code', 20);
            $table->string('location', 180)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Radius the hub is responsible for; used later to match journeys by proximity.
            $table->unsignedSmallInteger('radius_km')->default(50);

            $table->string('contact_name', 120)->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
        });

        // The free-text `hub` column stays as the display fallback for journeys that
        // were captured before hubs became real records.
        Schema::table('journeys', function (Blueprint $table) {
            $table->foreignId('journey_hub_id')
                ->nullable()
                ->after('hub')
                ->constrained('journey_hubs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('journeys', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journey_hub_id');
        });

        Schema::dropIfExists('journey_hubs');
    }
};
