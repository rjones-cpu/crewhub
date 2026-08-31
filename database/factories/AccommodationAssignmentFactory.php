<?php
namespace Database\Factories;
use App\Models\{Accommodation, AccommodationAssignment, Company, Worker};
use Illuminate\Database\Eloquent\Factories\Factory;
class AccommodationAssignmentFactory extends Factory {
    protected $model=AccommodationAssignment::class;
    public function definition(): array { return ['accommodation_id'=>Accommodation::factory(),'worker_id'=>Worker::factory(),'company_id'=>Company::factory(),'room_number'=>fake()->bothify('R-###'),'check_in'=>fake()->dateTimeBetween('-1 month','now'),'check_out'=>fake()->dateTimeBetween('+1 week','+3 months'),'status'=>'checked_in']; }
}
