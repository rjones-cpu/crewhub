<?php
namespace Database\Factories;
use App\Models\{Company, Worker, WorkerReadiness};
use Illuminate\Database\Eloquent\Factories\Factory;
class WorkerReadinessFactory extends Factory {
    protected $model=WorkerReadiness::class;
    public function definition(): array { $s=fake()->randomElement(['ready','at_risk','not_ready','pending_review']); return ['worker_id'=>Worker::factory(),'company_id'=>Company::factory(),'overall_status'=>$s,'medical_status'=>$s,'certification_status'=>$s,'training_status'=>$s,'journey_status'=>'ready','accommodation_status'=>'ready','site_access_status'=>$s,'last_checked_at'=>now()]; }
}
