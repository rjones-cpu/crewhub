<?php

namespace Tests\Feature;

use App\Enums\JourneyRisk;
use App\Enums\JourneyStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\Journey;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JourneyManagementTest extends TestCase
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

    public function test_company_admin_can_view_journey_list(): void
    {
        $user = $this->companyAdmin();
        $worker = Worker::factory()->create(['company_id' => $user->company_id]);
        Journey::factory()->create([
            'company_id' => $user->company_id,
            'worker_id' => $worker->id,
            'status' => JourneyStatus::InTransit,
            'risk_level' => JourneyRisk::High,
        ]);

        $this->actingAs($user)
            ->get(route('journeys.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Journeys/Index')
                ->has('journeys.data', 1)
                ->where('stats.total', 1)
                ->where('stats.en_route', 1)
                ->where('stats.high_risk', 1)
                ->where('canCreate', true));
    }

    public function test_status_filter_limits_the_list(): void
    {
        $user = $this->companyAdmin();
        $worker = Worker::factory()->create(['company_id' => $user->company_id]);
        Journey::factory()->create([
            'company_id' => $user->company_id,
            'worker_id' => $worker->id,
            'status' => JourneyStatus::Approved,
        ]);
        Journey::factory()->create([
            'company_id' => $user->company_id,
            'worker_id' => $worker->id,
            'status' => JourneyStatus::InTransit,
        ]);

        $this->actingAs($user)
            ->get(route('journeys.index', ['status' => JourneyStatus::Approved->value]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('journeys.data', 1)
                ->where('journeys.data.0.status', JourneyStatus::Approved->value));
    }

    public function test_unrelated_company_cannot_see_another_company_journey(): void
    {
        $owner = $this->companyAdmin();
        $outsider = $this->companyAdmin(Company::factory()->create(['name' => 'Other Co']));
        $worker = Worker::factory()->create(['company_id' => $owner->company_id]);
        Journey::factory()->create([
            'company_id' => $owner->company_id,
            'worker_id' => $worker->id,
        ]);

        $this->actingAs($outsider)
            ->get(route('journeys.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('journeys.data', 0));
    }

    public function test_company_admin_can_create_a_journey(): void
    {
        $user = $this->companyAdmin();
        $worker = Worker::factory()->create([
            'company_id' => $user->company_id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('journeys.store'), [
                'worker_id' => $worker->id,
                'origin' => 'Rustenburg Mine',
                'destination' => 'Main Plant',
                'departure_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'arrival_at' => now()->addDay()->addHours(4)->format('Y-m-d H:i:s'),
                'vehicle_plate' => 'KZV 829 GP',
                'vehicle_model' => 'Toyota Hilux',
                'hub' => 'Rustenburg Hub',
            ])
            ->assertRedirect(route('journeys.index'));

        // Without answers the engine assumes the worst on every unknown factor, so a
        // bare journey never clears the low-risk bar and waits for approval.
        $this->assertDatabaseHas('journeys', [
            'company_id' => $user->company_id,
            'worker_id' => $worker->id,
            'origin' => 'Rustenburg Mine',
            'destination' => 'Main Plant',
            'hub' => 'Rustenburg Hub',
            'status' => JourneyStatus::Pending->value,
        ]);

        $this->assertNotNull(Journey::query()->firstOrFail()->risk_score);
    }

    public function test_company_admin_can_update_journey_status(): void
    {
        $user = $this->companyAdmin();
        $worker = Worker::factory()->create(['company_id' => $user->company_id]);
        $journey = Journey::factory()->create([
            'company_id' => $user->company_id,
            'worker_id' => $worker->id,
            'status' => JourneyStatus::Approved,
        ]);

        $this->actingAs($user)
            ->patch(route('journeys.status', $journey), [
                'status' => JourneyStatus::InTransit->value,
            ])
            ->assertRedirect();

        $this->assertSame(JourneyStatus::InTransit, $journey->fresh()->status);
    }

    public function test_export_downloads_csv(): void
    {
        $user = $this->companyAdmin();
        $worker = Worker::factory()->create([
            'company_id' => $user->company_id,
            'first_name' => 'James',
            'last_name' => 'Anderson',
        ]);
        Journey::factory()->create([
            'company_id' => $user->company_id,
            'worker_id' => $worker->id,
            'code' => 'JRN-2026-1248',
        ]);

        $response = $this->actingAs($user)
            ->get(route('journeys.export'));

        $response->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringContainsString('JRN-2026-1248', $csv);
        $this->assertStringContainsString('James Anderson', $csv);
    }
}
