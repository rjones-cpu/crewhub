<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Journey;
use App\Models\JourneyHub;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JourneyHubTest extends TestCase
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

    private function hub(User $user, array $overrides = []): JourneyHub
    {
        return JourneyHub::query()->create(array_merge([
            'company_id' => $user->company_id,
            'name' => 'Rustenburg Hub',
            'code' => 'RBG',
            'location' => 'Rustenburg',
            'radius_km' => 50,
            'is_active' => true,
        ], $overrides));
    }

    private function journey(User $user, array $overrides = []): Journey
    {
        $worker = Worker::factory()->create(['company_id' => $user->company_id]);

        return Journey::factory()->create(array_merge([
            'company_id' => $user->company_id,
            'worker_id' => $worker->id,
            'journey_hub_id' => null,
        ], $overrides));
    }

    public function test_hub_page_lists_hubs_and_undesignated_journeys(): void
    {
        $user = $this->companyAdmin();
        $this->hub($user);
        $this->journey($user);

        $this->actingAs($user)
            ->get(route('journeys.hubs'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Journeys/Hubs/Index')
                ->has('hubs.data', 1)
                ->has('undesignated', 1)
                ->where('stats.total', 1)
                ->where('stats.undesignated', 1)
                ->where('canManage', true));
    }

    public function test_company_admin_can_create_a_hub(): void
    {
        $user = $this->companyAdmin();

        $this->actingAs($user)
            ->post(route('journeys.hubs.store'), [
                'name' => 'Main Plant Hub',
                'code' => 'MPH',
                'location' => 'Main Plant',
                'radius_km' => 80,
                'contact_name' => 'Ralph Jones',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('journey_hubs', [
            'company_id' => $user->company_id,
            'code' => 'MPH',
            'radius_km' => 80,
        ]);
    }

    public function test_hub_code_must_be_unique_within_the_company(): void
    {
        $user = $this->companyAdmin();
        $this->hub($user);

        $this->actingAs($user)
            ->post(route('journeys.hubs.store'), ['name' => 'Duplicate', 'code' => 'RBG'])
            ->assertSessionHasErrors('code');
    }

    public function test_designating_journeys_sets_the_hub_and_keeps_the_display_name_in_step(): void
    {
        $user = $this->companyAdmin();
        $hub = $this->hub($user);
        $first = $this->journey($user, ['hub' => 'Unknown']);
        $second = $this->journey($user, ['hub' => null]);

        $this->actingAs($user)
            ->post(route('journeys.hubs.designate', $hub), [
                'journey_ids' => [$first->id, $second->id],
            ])
            ->assertRedirect();

        foreach ([$first, $second] as $journey) {
            $journey->refresh();
            $this->assertSame($hub->id, $journey->journey_hub_id);
            $this->assertSame('Rustenburg Hub', $journey->hub);
        }
    }

    public function test_cannot_designate_a_journey_from_another_company(): void
    {
        $user = $this->companyAdmin();
        $outsider = $this->companyAdmin(Company::factory()->create(['name' => 'Other Co']));
        $hub = $this->hub($user);
        $foreign = $this->journey($outsider);

        $this->actingAs($user)
            ->post(route('journeys.hubs.designate', $hub), ['journey_ids' => [$foreign->id]])
            ->assertSessionHasErrors('journey_ids.0');
    }

    public function test_hubs_are_scoped_to_the_owning_company(): void
    {
        $owner = $this->companyAdmin();
        $outsider = $this->companyAdmin(Company::factory()->create(['name' => 'Other Co']));
        $this->hub($owner);

        $this->actingAs($outsider)
            ->get(route('journeys.hubs'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('hubs.data', 0));
    }
}
