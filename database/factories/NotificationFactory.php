<?php
namespace Database\Factories;
use App\Models\{Company, Notification, User};
use Illuminate\Database\Eloquent\Factories\Factory;
class NotificationFactory extends Factory {
    protected $model=Notification::class;
    public function definition(): array { return ['company_id'=>Company::factory(),'user_id'=>User::factory(),'type'=>'operational','title'=>fake()->sentence(4),'message'=>fake()->sentence(),'data'=>[],'read_at'=>fake()->optional()->dateTimeThisMonth()]; }
}
