<?php
namespace Database\Factories;
use App\Models\{Accommodation, Company};
use Illuminate\Database\Eloquent\Factories\Factory;
class AccommodationFactory extends Factory {
    protected $model=Accommodation::class;
    public function definition(): array { $capacity=fake()->numberBetween(30,150); return ['company_id'=>Company::factory(),'name'=>fake()->company().' Lodge','location'=>fake()->city(),'capacity'=>$capacity,'occupied'=>fake()->numberBetween(0,$capacity),'status'=>'active']; }
}
