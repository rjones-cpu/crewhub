<?php
namespace Database\Factories;
use App\Models\{Company, MajorProject, ScheduleForecast};
use Illuminate\Database\Eloquent\Factories\Factory;
class ScheduleForecastFactory extends Factory {
    protected $model=ScheduleForecast::class;
    public function definition(): array { $required=fake()->numberBetween(40,150); return ['company_id'=>Company::factory(),'major_project_id'=>MajorProject::factory(),'forecast_date'=>fake()->dateTimeBetween('now','+2 weeks'),'required_workers'=>$required,'scheduled_workers'=>fake()->numberBetween((int)($required*.7),$required)]; }
}
