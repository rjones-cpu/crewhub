<?php
namespace Database\Factories;
use App\Models\{Company, Worker, WorkerActivity};
use Illuminate\Database\Eloquent\Factories\Factory;
class WorkerActivityFactory extends Factory {
    protected $model=WorkerActivity::class;
    public function definition(): array { return ['worker_id'=>Worker::factory(),'company_id'=>Company::factory(),'type'=>fake()->randomElement(['profile_updated','readiness_checked','assignment_changed']),'description'=>fake()->sentence(),'metadata'=>['source'=>'seed']]; }
}
