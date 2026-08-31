<?php

use App\Enums\JourneyRisk;
use App\Enums\JourneyStatus;
use App\Models\Journey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journeys', function (Blueprint $table) {
            $table->string('code', 40)->nullable()->after('id');
            $table->string('vehicle_plate', 40)->nullable()->after('destination');
            $table->string('vehicle_model', 80)->nullable()->after('vehicle_plate');
            $table->string('hub', 120)->nullable()->after('vehicle_model');
            $table->string('risk_level', 20)->nullable()->after('hub');
            $table->decimal('distance_km', 8, 1)->nullable()->after('risk_level');
            $table->string('emergency_contact_name')->nullable()->after('approved_by');
            $table->string('emergency_contact_phone', 40)->nullable()->after('emergency_contact_name');
            $table->json('checkpoints')->nullable()->after('emergency_contact_phone');
        });

        Journey::query()->withTrashed()->orderBy('id')->each(function (Journey $journey): void {
            $year = $journey->created_at?->year ?? now()->year;
            $journey->forceFill([
                'code' => sprintf('JRN-%d-%04d', $year, $journey->id),
                'risk_level' => $journey->risk_level ?: JourneyRisk::Low,
                'checkpoints' => $this->checkpointsFor($journey),
            ])->saveQuietly();
        });

        Schema::table('journeys', function (Blueprint $table) {
            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('journeys', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
            $table->dropColumn([
                'code',
                'vehicle_plate',
                'vehicle_model',
                'hub',
                'risk_level',
                'distance_km',
                'emergency_contact_name',
                'emergency_contact_phone',
                'checkpoints',
            ]);
        });
    }

    /**
     * @return list<array{name: string, status: string, occurred_at: string|null}>
     */
    private function checkpointsFor(Journey $journey): array
    {
        $status = $journey->status instanceof JourneyStatus
            ? $journey->status
            : JourneyStatus::tryFrom((string) $journey->status);

        return match ($status) {
            JourneyStatus::Completed => [
                ['name' => $journey->origin, 'status' => 'completed', 'occurred_at' => $journey->departure_at?->toIso8601String()],
                ['name' => 'En route', 'status' => 'completed', 'occurred_at' => $journey->departure_at?->toIso8601String()],
                ['name' => $journey->destination, 'status' => 'completed', 'occurred_at' => $journey->arrival_at?->toIso8601String()],
            ],
            JourneyStatus::InTransit => [
                ['name' => $journey->origin, 'status' => 'completed', 'occurred_at' => $journey->departure_at?->toIso8601String()],
                ['name' => 'En route', 'status' => 'in_progress', 'occurred_at' => null],
                ['name' => $journey->destination, 'status' => 'pending', 'occurred_at' => null],
            ],
            default => [
                ['name' => $journey->origin, 'status' => 'pending', 'occurred_at' => null],
                ['name' => 'En route', 'status' => 'pending', 'occurred_at' => null],
                ['name' => $journey->destination, 'status' => 'pending', 'occurred_at' => null],
            ],
        };
    }
};
