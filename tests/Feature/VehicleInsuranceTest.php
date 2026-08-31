<?php

namespace Tests\Feature;

use App\Enums\InsuranceStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleInsuranceTest extends TestCase
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

    public function test_confirmation_queue_reports_cover_and_confirmation_counts(): void
    {
        $user = $this->companyAdmin();
        Vehicle::factory()->create(['company_id' => $user->company_id]);
        Vehicle::factory()->expiredInsurance()->create(['company_id' => $user->company_id]);
        Vehicle::factory()->create([
            'company_id' => $user->company_id,
            'policy_end_date' => now()->addDays(10),
        ]);

        $this->actingAs($user)
            ->get(route('journeys.insurance'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Journeys/Insurance/Index')
                ->has('vehicles.data', 3)
                ->where('stats.total', 3)
                ->where('stats.awaiting', 3)
                ->where('stats.expired', 1)
                ->where('stats.expiring', 1)
                ->where('canManage', true));
    }

    public function test_vehicles_start_awaiting_confirmation(): void
    {
        $user = $this->companyAdmin();
        $vehicle = Vehicle::factory()->create(['company_id' => $user->company_id]);

        $this->assertSame(InsuranceStatus::Unverified, $vehicle->insurance_status);
    }

    public function test_company_admin_can_confirm_cover(): void
    {
        $user = $this->companyAdmin();
        $vehicle = Vehicle::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->post(route('journeys.insurance.confirm', $vehicle), [
                'status' => InsuranceStatus::Confirmed->value,
                'notes' => 'Policy schedule checked against provider portal.',
            ])
            ->assertRedirect();

        $vehicle->refresh();

        $this->assertSame(InsuranceStatus::Confirmed, $vehicle->insurance_status);
        $this->assertSame($user->id, $vehicle->insurance_verified_by);
        $this->assertNotNull($vehicle->insurance_verified_at);
    }

    public function test_cover_can_be_flagged_for_follow_up(): void
    {
        $user = $this->companyAdmin();
        $vehicle = Vehicle::factory()->expiredInsurance()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->post(route('journeys.insurance.confirm', $vehicle), [
                'status' => InsuranceStatus::Flagged->value,
                'notes' => 'Policy lapsed last month.',
            ])
            ->assertRedirect();

        $vehicle->refresh();

        $this->assertSame(InsuranceStatus::Flagged, $vehicle->insurance_status);
        $this->assertFalse($vehicle->insurance_status->clearsForJourneys());
    }

    public function test_expired_cover_filter_narrows_the_queue(): void
    {
        $user = $this->companyAdmin();
        Vehicle::factory()->create(['company_id' => $user->company_id]);
        Vehicle::factory()->expiredInsurance()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->get(route('journeys.insurance', ['cover' => 'expired']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('vehicles.data', 1));
    }

    public function test_queue_is_scoped_to_the_owning_company(): void
    {
        $owner = $this->companyAdmin();
        $outsider = $this->companyAdmin(Company::factory()->create(['name' => 'Other Co']));
        Vehicle::factory()->create(['company_id' => $owner->company_id]);

        $this->actingAs($outsider)
            ->get(route('journeys.insurance'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('vehicles.data', 0));
    }
}
