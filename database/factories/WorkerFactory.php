<?php
namespace Database\Factories;
use App\Models\{Company, Worker};
use Illuminate\Database\Eloquent\Factories\Factory;
class WorkerFactory extends Factory {
    protected $model = Worker::class;
    public function definition(): array { return ['company_id'=>Company::factory(),'employee_id'=>fake()->unique()->bothify('EMP-#####'),'first_name'=>fake()->firstName(),'last_name'=>fake()->lastName(),'email'=>fake()->unique()->safeEmail(),'phone'=>fake()->phoneNumber(),'position'=>fake()->randomElement(['Operator','Electrician','Engineer','Supervisor','Medic']),'location'=>fake()->city(),'status'=>fake()->randomElement(['active','active','on_leave','mobilizing']),'on_site'=>fake()->boolean(65),'module_access'=>true,'schedule_access'=>true,'timesheet_access'=>true,'lms_access'=>true,'journey_access'=>true]; }
}
