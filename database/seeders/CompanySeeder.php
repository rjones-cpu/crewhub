<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Company;
use Database\Seeders\Concerns\SeedsAdministrators;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds every Crew Hub tenant together with the one administrator that owns it.
 *
 * Safe to re-run: companies are matched on name (falling back to code) and
 * administrators on email, so an existing tenant is repaired rather than
 * duplicated.
 */
class CompanySeeder extends Seeder
{
    use SeedsAdministrators;

    /**
     * Plain-text password every administrator receives on first creation.
     */
    public const ADMIN_PASSWORD = 'password';

    /**
     * One row per tenant, each with exactly one administrator.
     *
     * `code` must stay unique across companies and `admin_email` unique across
     * users. `industry` and `location` are display-only defaults.
     *
     * @var list<array<string, string>>
     */
    public const COMPANIES = [
        [
            'name' => 'Baker Hughes',
            'code' => 'BKRH',
            'industry' => 'Energy Services',
            'location' => 'Calgary, AB',
            'admin_name' => 'Baker Hughes Administrator',
            'admin_email' => 'admin@bakerhughes.test',
        ],
        [
            'name' => 'Belair',
            'code' => 'BLAI',
            'industry' => 'Industrial Services',
            'location' => 'Kitimat, BC',
            'admin_name' => 'Belair Administrator',
            'admin_email' => 'admin@belair.test',
        ],
        [
            'name' => 'IRISNDT',
            'code' => 'IRIS',
            'industry' => 'Inspection & NDT',
            'location' => 'Edmonton, AB',
            'admin_name' => 'IRISNDT Administrator',
            'admin_email' => 'admin@irisndt.test',
        ],
        [
            'name' => 'KDL',
            'code' => 'KDL',
            'industry' => 'Industrial Services',
            'location' => 'Kitimat, BC',
            'admin_name' => 'KDL Administrator',
            'admin_email' => 'admin@kdl.test',
        ],
        [
            'name' => 'LNG Canada',
            'code' => 'LNGC',
            'industry' => 'Energy',
            'location' => 'Kitimat, BC',
            'admin_name' => 'LNG Canada Administrator',
            'admin_email' => 'admin@lngcanada.test',
        ],
        [
            'name' => 'LodgeX',
            'code' => 'LDGX',
            'industry' => 'Hospitality',
            'location' => 'Calgary, AB',
            'admin_name' => 'LodgeX Administrator',
            'admin_email' => 'admin@lodgex.test',
        ],
    ];

    public function run(): void
    {
        foreach (self::COMPANIES as $definition) {
            $company = $this->seedCompany($definition);

            $this->seedAdministrator($definition['admin_email'], [
                'company_id' => $company->id,
                'name' => $definition['admin_name'],
                'role' => Role::CompanyAdmin,
                'is_active' => true,
            ], self::ADMIN_PASSWORD);
        }

        $this->reportCredentials();
    }

    /**
     * @param  array<string, string>  $definition
     */
    protected function seedCompany(array $definition): Company
    {
        // CampTimesheetSyncService resolves tenants by normalised name, so match the
        // same way to avoid duplicating a company the Camp sync already created.
        // Trashed rows still hold the unique `code`, so they are revived too.
        $company = Company::withTrashed()
            ->whereRaw('LOWER(TRIM(name)) = ?', [Str::lower(trim($definition['name']))])
            ->orWhere('code', $definition['code'])
            ->first() ?? new Company;

        $company->fill([
            'name' => $definition['name'],
            'code' => $definition['code'],
            'industry' => $definition['industry'],
            'location' => $definition['location'],
            'status' => 'active',
            'deleted_at' => null,
        ]);

        $company->save();

        return $company;
    }

    protected function reportCredentials(): void
    {
        if (! $this->command) {
            return;
        }

        $this->command->newLine();
        $this->command->info('Company administrators (password: '.self::ADMIN_PASSWORD.')');
        $this->command->table(
            ['Company', 'Code', 'Administrator email', 'Role'],
            array_map(fn (array $definition) => [
                $definition['name'],
                $definition['code'],
                $definition['admin_email'],
                Role::CompanyAdmin->value,
            ], self::COMPANIES),
        );
    }
}
