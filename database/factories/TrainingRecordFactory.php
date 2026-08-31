<?php
namespace Database\Factories;
use App\Models\{Company, TrainingRecord, Worker};
use Illuminate\Database\Eloquent\Factories\Factory;
class TrainingRecordFactory extends Factory {
    protected $model=TrainingRecord::class;
    public function definition(): array { return ['worker_id'=>Worker::factory(),'company_id'=>Company::factory(),'course_name'=>fake()->randomElement(['Site Induction','Safety Leadership','Emergency Response']),'provider'=>fake()->company(),'status'=>'completed','completed_at'=>fake()->dateTimeBetween('-1 year','now'),'expires_at'=>fake()->dateTimeBetween('+2 months','+2 years'),'score'=>fake()->randomFloat(2,70,100)]; }
}
