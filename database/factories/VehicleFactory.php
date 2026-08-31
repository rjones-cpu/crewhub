<?php

namespace Database\Factories;

use App\Enums\VehicleAvailability;
use App\Enums\VehicleType;
use App\Models\Company;
use App\Models\Vehicle;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        $policyStart = fake()->dateTimeBetween('-10 months', '-1 month');

        return [
            'company_id' => Company::factory(),
            'make' => fake()->randomElement(['Toyota', 'Ford', 'Nissan', 'Isuzu', 'Mitsubishi']),
            'model' => fake()->randomElement(['Hilux', 'Ranger', 'Navara', 'D-Max', 'Triton']),
            'year' => fake()->numberBetween(2015, (int) date('Y')),
            'vehicle_type' => fake()->randomElement(VehicleType::cases()),
            'vin' => strtoupper(fake()->unique()->bothify('?#?#?#?#?#?#?#?#?')),
            'license_plate' => strtoupper(fake()->unique()->bothify('???### ??')),
            'assigned_driver_id' => Worker::factory(),

            'has_attachments' => true,
            'insurance_provider' => fake()->company(),
            'policy_number' => strtoupper(fake()->bothify('POL-####-????')),
            'coverage_type' => 'comprehensive',
            'coverage_amount' => fake()->randomElement([1000000, 2000000, 5000000]),
            'policy_start_date' => $policyStart,
            'policy_end_date' => (clone $policyStart)->modify('+1 year'),

            'base_location' => fake()->city(),
            'purpose' => 'personnel_transport',
            'availability' => VehicleAvailability::Available,
            'transmission' => fake()->randomElement(['manual', 'automatic']),
            'odometer_km' => fake()->numberBetween(5000, 240000),
            'equipment' => ['First Aid Kit', 'Fire Extinguisher', 'Spare Tyre'],
            'is_active' => true,
        ];
    }

    public function expiredInsurance(): static
    {
        return $this->state(fn () => [
            'policy_start_date' => now()->subYears(2),
            'policy_end_date' => now()->subMonth(),
        ]);
    }

    public function inMaintenance(): static
    {
        return $this->state(fn () => ['availability' => VehicleAvailability::Maintenance]);
    }
}
