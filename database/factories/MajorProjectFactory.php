<?php
namespace Database\Factories;
use App\Models\{Company, CompanyProjectMembership, MajorProject};
use Illuminate\Database\Eloquent\Factories\Factory;
class MajorProjectFactory extends Factory {
    protected $model = MajorProject::class;
    public function definition(): array { return ['company_id'=>Company::factory(),'name'=>fake()->catchPhrase(),'code'=>fake()->unique()->bothify('PRJ-###'),'description'=>fake()->paragraph(),'location'=>fake()->city(),'project_type'=>fake()->randomElement(['Construction','Operations','Exploration']),'start_date'=>fake()->dateTimeBetween('-1 year','+2 months'),'end_date'=>fake()->dateTimeBetween('+3 months','+2 years'),'status'=>fake()->randomElement(['active','planned'])]; }

    /**
     * Project visibility is driven by membership, not by company_id alone, so the owning
     * company needs the same Owner membership MajorProjectService creates in production.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (MajorProject $project): void {
            CompanyProjectMembership::query()->firstOrCreate(
                [
                    'company_id' => $project->company_id,
                    'major_project_id' => $project->id,
                ],
                [
                    'role' => 'Owner',
                    'status' => 'active',
                    'joined_at' => now(),
                ],
            );
        });
    }
}
