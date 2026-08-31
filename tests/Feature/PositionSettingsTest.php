<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Position;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PositionSettingsTest extends TestCase
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

    public function test_settings_page_is_available_to_company_admin(): void
    {
        $this->actingAs($this->companyAdmin())
            ->get(route('settings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Profile/Edit'));
    }

    public function test_company_admin_can_view_positions(): void
    {
        $user = $this->companyAdmin();
        Position::factory()->create(['name' => 'Site Supervisor']);

        $this->actingAs($user)
            ->get(route('settings.positions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Positions')
                ->has('positions.data', 1)
                ->where('positions.data.0.name', 'Site Supervisor'));
    }

    public function test_read_only_user_cannot_manage_positions(): void
    {
        $user = User::factory()->create(['role' => Role::ReadOnly]);

        $this->actingAs($user)
            ->get(route('settings.positions.index'))
            ->assertForbidden();
    }

    public function test_admin_can_add_a_position(): void
    {
        $user = $this->companyAdmin();

        $this->actingAs($user)
            ->post(route('settings.positions.store'), [
                'name' => 'Field Engineer',
                'code' => 'FE',
                'description' => 'On-site engineering',
                'is_active' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('positions', [
            'name' => 'Field Engineer',
            'code' => 'FE',
        ]);
    }

    public function test_duplicate_position_name_is_rejected(): void
    {
        $user = $this->companyAdmin();
        Position::factory()->create(['name' => 'Electrician']);

        $this->actingAs($user)
            ->from(route('settings.positions.index'))
            ->post(route('settings.positions.store'), ['name' => 'Electrician'])
            ->assertRedirect(route('settings.positions.index'))
            ->assertSessionHasErrors('name');
    }

    public function test_renaming_a_position_updates_matching_workers(): void
    {
        $user = $this->companyAdmin();
        $position = Position::factory()->create(['name' => 'Operator']);
        $worker = Worker::factory()->create([
            'company_id' => $user->company_id,
            'position' => 'Operator',
        ]);

        $this->actingAs($user)
            ->put(route('settings.positions.update', $position), [
                'name' => 'Heavy Equipment Operator',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertSame('Heavy Equipment Operator', $worker->fresh()->position);
    }

    public function test_csv_import_creates_positions(): void
    {
        $user = $this->companyAdmin();
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'positions-import.csv';
        file_put_contents($path, "name,code,description\nWelder,WLD,Shop welder\nMedic,MED,\n");

        $file = new UploadedFile($path, 'positions.csv', 'text/csv', null, true);

        $this->actingAs($user)
            ->post(route('settings.positions.import'), ['file' => $file])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('positions', [
            'name' => 'Welder',
            'code' => 'WLD',
        ]);
        $this->assertDatabaseHas('positions', [
            'name' => 'Medic',
        ]);
    }

    public function test_positions_are_shared_across_companies(): void
    {
        $user = $this->companyAdmin();
        Position::factory()->create(['name' => 'Shared Role']);

        $this->actingAs($user)
            ->get(route('settings.positions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('positions.data', 1)
                ->where('positions.data.0.name', 'Shared Role'));
    }

    public function test_template_download_is_csv(): void
    {
        $response = $this->actingAs($this->companyAdmin())
            ->get(route('settings.positions.template'));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('name', $response->streamedContent());
    }

    public function test_positions_list_is_paginated(): void
    {
        $user = $this->companyAdmin();

        foreach (range(1, 12) as $index) {
            Position::factory()->create(['name' => 'Role '.$index]);
        }

        $this->actingAs($user)
            ->get(route('settings.positions.index', ['per_page' => 10]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('positions.data', 10)
                ->where('positions.total', 12)
                ->where('positions.last_page', 2)
                ->where('positions.per_page', 10));
    }

    public function test_workers_index_includes_catalog_positions(): void
    {
        $user = $this->companyAdmin();
        Position::factory()->create(['name' => 'HSE Advisor']);

        $this->actingAs($user)
            ->get(route('workers.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('positions.0', 'HSE Advisor'));
    }
}
