<?php
namespace Database\Factories;
use App\Models\{Company, MajorProject, Worker, WorkerAssignment};
use Illuminate\Database\Eloquent\Factories\Factory;
class WorkerAssignmentFactory extends Factory {
    protected $model=WorkerAssignment::class;
    public function definition(): array { return ['worker_id'=>Worker::factory(),'major_project_id'=>MajorProject::factory(),'company_id'=>Company::factory(),'role'=>fake()->jobTitle(),'start_date'=>fake()->dateTimeBetween('-1 year','now'),'end_date'=>null,'status'=>'active','is_primary'=>true]; }
}
