<?php
namespace Database\Factories;
use App\Models\{Company, PriorityAction};
use Illuminate\Database\Eloquent\Factories\Factory;
class PriorityActionFactory extends Factory {
    protected $model=PriorityAction::class;
    public function definition(): array { return ['company_id'=>Company::factory(),'title'=>fake()->sentence(5),'issue'=>fake()->sentence(),'affected_count'=>fake()->numberBetween(1,30),'owner_name'=>fake()->name(),'due_date'=>fake()->dateTimeBetween('now','+1 month'),'status'=>fake()->randomElement(['open','in_progress','overdue']),'severity'=>fake()->randomElement(['critical','high','medium','low'])]; }
}
