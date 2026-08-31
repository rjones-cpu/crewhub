<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journey_risk_assessments', function (Blueprint $table) {
            $table->foreignId('company_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('code', 40)->after('company_id');

            // Conditions are snapshotted on the assessment so a recalculation later
            // does not silently rewrite the conditions an approval was based on.
            $table->string('weather', 40)->nullable()->after('factors');
            $table->smallInteger('temperature_c')->nullable()->after('weather');
            $table->string('road_conditions', 40)->nullable()->after('temperature_c');
            $table->string('road_condition_quality', 20)->nullable()->after('road_conditions');
            $table->json('recommendations')->nullable()->after('road_condition_quality');

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'outcome']);
        });
    }

    public function down(): void
    {
        Schema::table('journey_risk_assessments', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
            $table->dropIndex(['company_id', 'outcome']);
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn([
                'code',
                'weather',
                'temperature_c',
                'road_conditions',
                'road_condition_quality',
                'recommendations',
            ]);
        });
    }
};
