<?php
namespace Database\Factories;
use App\Models\{Company, MedicalRecord, Worker};
use Illuminate\Database\Eloquent\Factories\Factory;
class MedicalRecordFactory extends Factory {
    protected $model=MedicalRecord::class;
    public function definition(): array { return ['worker_id'=>Worker::factory(),'company_id'=>Company::factory(),'exam_type'=>'Fit for Work','status'=>'cleared','examined_at'=>fake()->dateTimeBetween('-1 year','-1 month'),'expires_at'=>fake()->dateTimeBetween('+1 month','+1 year'),'provider'=>fake()->company()]; }
}
