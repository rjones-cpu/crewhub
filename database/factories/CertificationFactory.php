<?php
namespace Database\Factories;
use App\Models\{Certification, Company, Worker};
use Illuminate\Database\Eloquent\Factories\Factory;
class CertificationFactory extends Factory {
    protected $model=Certification::class;
    public function definition(): array { return ['worker_id'=>Worker::factory(),'company_id'=>Company::factory(),'name'=>fake()->randomElement(['First Aid','Working at Heights','Heavy Equipment']),'certificate_number'=>fake()->bothify('CERT-######'),'issuer'=>fake()->company(),'issued_at'=>fake()->dateTimeBetween('-2 years','-2 months'),'expires_at'=>fake()->dateTimeBetween('+1 month','+2 years'),'status'=>'valid']; }
}
