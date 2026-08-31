<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\VehicleAvailability;
use App\Enums\VehicleType;
use App\Enums\WorkerStatus;
use App\Models\Company;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function companyAdmin(?Company $company = null): User
    {
        $company ??= Company::factory()->create();

        return User::factory()->create([
            'company_id' => $company->id,
            'role' => Role::CompanyAdmin,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Worker $driver, array $overrides = []): array
    {
        return array_merge([
            'make' => 'Toyota',
            'model' => 'Hilux',
            'year' => 2024,
            'vehicle_type' => VehicleType::Suv->value,
            'vin' => 'AHTFR22G8L0123456',
            'license_plate' => 'K2V E29 GP',
            'assigned_driver_id' => $driver->id,
            'insurance_provider' => 'Santam',
            'policy_number' => 'POL-4821-XZ',
            'coverage_type' => 'comprehensive',
            'coverage_amount' => 2000000,
            'policy_start_date' => now()->subMonth()->toDateString(),
            'policy_end_date' => now()->addYear()->toDateString(),
            'base_location' => 'Rustenburg Hub',
            'purpose' => 'personnel_transport',
        ], $overrides);
    }

    public function test_company_admin_can_view_the_registered_vehicles_list(): void
    {
        $user = $this->companyAdmin();
        Vehicle::factory()->create(['company_id' => $user->company_id]);
        Vehicle::factory()->inMaintenance()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->get(route('journeys.vehicles'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Journeys/Vehicles/Index')
                ->has('vehicles.data', 2)
                ->where('stats.total', 2)
                ->where('stats.maintenance', 1)
                ->where('canManage', true));
    }

    public function test_register_form_only_offers_active_drivers(): void
    {
        $user = $this->companyAdmin();
        Worker::factory()->create([
            'company_id' => $user->company_id,
            'status' => WorkerStatus::Active,
        ]);
        Worker::factory()->create([
            'company_id' => $user->company_id,
            'status' => WorkerStatus::Inactive,
        ]);

        $this->actingAs($user)
            ->get(route('journeys.vehicles.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Journeys/Vehicles/Create')
                ->has('drivers', 1)
                ->has('vehicleTypes', count(VehicleType::cases())));
    }

    public function test_company_admin_can_register_a_vehicle_with_an_insurance_document(): void
    {
        Storage::fake('public');

        $user = $this->companyAdmin();
        $driver = Worker::factory()->create([
            'company_id' => $user->company_id,
            'status' => WorkerStatus::Active,
        ]);

        $this->actingAs($user)
            ->post(route('journeys.vehicles.store'), $this->payload($driver, [
                'insurance_document' => UploadedFile::fake()->create('policy.pdf', 120, 'application/pdf'),
            ]))
            ->assertRedirect(route('journeys.vehicles'));

        $vehicle = Vehicle::query()->firstOrFail();

        $this->assertSame('K2V E29 GP', $vehicle->license_plate);
        $this->assertSame(VehicleType::Suv, $vehicle->vehicle_type);
        $this->assertSame(VehicleAvailability::Available, $vehicle->availability);
        $this->assertTrue($vehicle->is_active);
        $this->assertTrue($vehicle->insurance_valid);
        Storage::disk('public')->assertExists($vehicle->insurance_document_path);
    }

    public function test_saving_a_draft_skips_the_insurance_requirements(): void
    {
        $user = $this->companyAdmin();
        $driver = Worker::factory()->create([
            'company_id' => $user->company_id,
            'status' => WorkerStatus::Active,
        ]);

        $this->actingAs($user)
            ->post(route('journeys.vehicles.store'), [
                'is_draft' => true,
                'make' => 'Ford',
                'model' => 'Ranger',
                'year' => 2023,
                'vehicle_type' => VehicleType::Truck->value,
                'vin' => 'AHTFR22G8L0999999',
                'license_plate' => 'BVL 417 GP',
                'assigned_driver_id' => $driver->id,
            ])
            ->assertRedirect(route('journeys.vehicles'));

        $this->assertFalse(Vehicle::query()->firstOrFail()->is_active);
    }

    public function test_license_plate_must_be_unique_within_the_company(): void
    {
        $user = $this->companyAdmin();
        $driver = Worker::factory()->create([
            'company_id' => $user->company_id,
            'status' => WorkerStatus::Active,
        ]);
        Vehicle::factory()->create([
            'company_id' => $user->company_id,
            'license_plate' => 'K2V E29 GP',
        ]);

        $this->actingAs($user)
            ->post(route('journeys.vehicles.store'), $this->payload($driver))
            ->assertSessionHasErrors('license_plate');
    }

    public function test_vehicles_are_scoped_to_the_owning_company(): void
    {
        $owner = $this->companyAdmin();
        $outsider = $this->companyAdmin(Company::factory()->create(['name' => 'Other Co']));
        Vehicle::factory()->create(['company_id' => $owner->company_id]);

        $this->actingAs($outsider)
            ->get(route('journeys.vehicles'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('vehicles.data', 0));
    }
}
