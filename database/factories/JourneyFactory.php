<?php

namespace Database\Factories;

use App\Enums\JourneyRisk;
use App\Enums\JourneyStatus;
use App\Models\Company;
use App\Models\Journey;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

class JourneyFactory extends Factory
{
    protected $model = Journey::class;

    public function definition(): array
    {
        $departure = fake()->dateTimeBetween('-1 week', '+1 month');
        $origin = fake()->city();
        $destination = fake()->city();
        $status = fake()->randomElement(JourneyStatus::cases());

        return [
            'company_id' => Company::factory(),
            'worker_id' => Worker::factory(),
            'code' => sprintf('JRN-%s-%04d', now()->year, fake()->unique()->numberBetween(1, 9999)),
            'type' => fake()->randomElement(['mobilization', 'demobilization', 'transfer']),
            'origin' => $origin,
            'destination' => $destination,
            'vehicle_plate' => strtoupper(fake()->bothify('??# ### ??')),
            'vehicle_model' => fake()->randomElement(['Toyota Hilux', 'Ford Ranger', 'Isuzu D-Max', 'Nissan Navara']),
            'hub' => fake()->city().' Hub',
            'risk_level' => fake()->randomElement(JourneyRisk::cases()),
            'distance_km' => fake()->randomFloat(1, 12, 280),
            'departure_at' => $departure,
            'arrival_at' => (clone $departure)->modify('+6 hours'),
            'status' => $status,
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->phoneNumber(),
            'checkpoints' => [
                ['name' => $origin, 'status' => 'pending', 'occurred_at' => null],
                ['name' => 'En route', 'status' => 'pending', 'occurred_at' => null],
                ['name' => $destination, 'status' => 'pending', 'occurred_at' => null],
            ],
        ];
    }
}
