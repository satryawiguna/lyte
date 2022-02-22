<?php

namespace Tests\Feature;

use App\Models\Calendar;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use WithFaker;

    public function setUp():void {
        parent::setUp();
        Artisan::call('migrate:fresh');
        Artisan::call('passport:install');
    }

    /**
     * @test
     */
    public function an_admin_can_view_all_calendars()
    {
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);

        $userAdmin = (new User())->whereHas('role', function($query) {
            $query->where('name', 'Admin');
        })->first();

        $response = $this->actingAs($userAdmin, 'api')->withHeaders([
            "Content-Type" => "application/json",
            "Accept" => "application/json"
        ])->get(route('api.calendars'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        $userCustomer = (new User())->where('id', '<>', $userAdmin->id)->get()
            ->random(1)
            ->first();

        $response = $this->actingAs($userCustomer, 'api')->withHeaders([
            "Content-Type" => "application/json",
            "Accept" => "application/json"
        ])->get(route('api.calendars'));

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function an_admin_can_view_each_customer_calendar()
    {
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);

        $userAdmin = (new User())->whereHas('role', function($query) {
            $query->where('name', 'Admin');
        })->first();

        $userCustomer = (new User())->whereHas('role', function($query) {
            $query->where('name', '!=', 'Admin');
        })->first();

        $response = $this->actingAs($userAdmin, 'api')->withHeaders([
            "Content-Type" => "application/json",
            "Accept" => "application/json"
        ])->get(route('api.user_calendars', ['id' => $userCustomer->id]));

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    /**
     * @test
     */
    public function an_customer_can_view_own_calendar()
    {
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);

        $userCustomer = (new User())->whereHas('role', function($query) {
            $query->where('name', '!=', 'Admin');
        })->first();

        $response = $this->actingAs($userCustomer, 'api')->withHeaders([
            "Content-Type" => "application/json",
            "Accept" => "application/json"
        ])->get(route('api.user_calendars', ['id' => $userCustomer->id]));

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        $userOtherCustomer = (new User())->whereHas('role', function($query) {
            $query->where('name', '!=', 'Admin');
        })->limit(1, 1)->first();

        $response = $this->actingAs($userCustomer, 'api')->withHeaders([
            "Content-Type" => "application/json",
            "Accept" => "application/json"
        ])->get(route('api.user_calendars', ['id' => $userOtherCustomer->id]));

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function an_admin_and_customer_can_create_a_calendar()
    {
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);

        $userAdmin = (new User())->whereHas('role', function($query) {
            $query->where('name', 'Admin');
        })->first();

        $response = $this->actingAs($userAdmin, 'api')->post(route('api.calendar_store'), [
            'user_id' => $userAdmin->id,
            'appointment_date' => $this->faker->dateTime(),
            'description' => $this->faker->text(),
            'status' => 'pending'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        $userCustomer= (new User())->whereHas('role', function($query) {
            $query->where('name', '!=', 'Admin');
        })->first();

        $response = $this->actingAs($userCustomer, 'api')->post(route('api.calendar_store'), [
            'user_id' => $userCustomer->id,
            'appointment_date' => $this->faker->date(),
            'description' => $this->faker->text(),
            'status' => 'pending'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    /**
     * @test
     */
    public function an_admin_can_update_a_calendar()
    {
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);

        $userAdmin = (new User())->whereHas('role', function($query) {
            $query->where('name', 'Admin');
        })->first();

        $this->actingAs($userAdmin, 'api')->post(route('api.calendar_store'), [
            'user_id' => $userAdmin->id,
            'appointment_date' => $this->faker->date(),
            'description' => $this->faker->text(),
            'status' => 'pending'
        ]);

        $calendar = Calendar::all()->first();

        $response = $this->actingAs($userAdmin, 'api')->put(route('api.calendar_update', ['id' => $calendar->id]), [
            'appointment_date' => $this->faker->dateTime(),
            'description' => $this->faker->text(),
            'status' => 'approved'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    /**
     * @test
     */
    public function a_customer_cannot_update_a_calendar()
    {
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);

        $userCustomer = (new User())->whereHas('role', function($query) {
            $query->where('name', '!=', 'Admin');
        })->first();

        $this->actingAs($userCustomer, 'api')->post(route('api.calendar_store'), [
            'user_id' => $userCustomer->id,
            'appointment_date' => $this->faker->date(),
            'description' => $this->faker->text(),
            'status' => 'pending'
        ]);

        $calendar = Calendar::all()->first();

        $response = $this->actingAs($userCustomer, 'api')->put(route('api.calendar_update', ['id' => $calendar->id]), [
            'appointment_date' => $this->faker->date(),
            'description' => $this->faker->text(),
            'status' => 'approved'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    /**
     * @test
     */
    public function an_admin_can_delete_a_calendar()
    {
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);

        $userAdmin = (new User())->whereHas('role', function($query) {
            $query->where('name', 'Admin');
        })->first();

        $this->actingAs($userAdmin, 'api')->post(route('api.calendar_store'), [
            'user_id' => $userAdmin->id,
            'appointment_date' => $this->faker->date(),
            'description' => $this->faker->text(),
            'status' => 'pending'
        ]);

        $calendar = Calendar::all()->first();

        $response = $this->actingAs($userAdmin, 'api')->delete(route('api.calendar_delete', ['id' => $calendar->id]));

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function a_customer_cannot_delete_a_calendar()
    {
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);

        $userCustomer = (new User())->whereHas('role', function($query) {
            $query->where('name', '!=', 'Admin');
        })->first();

        $this->actingAs($userCustomer, 'api')->post(route('api.calendar_store'), [
            'user_id' => $userCustomer->id,
            'appointment_date' => $this->faker->date(),
            'description' => $this->faker->text(),
            'status' => 'pending'
        ]);

        $calendar = Calendar::all()->first();

        $response = $this->actingAs($userCustomer, 'api')->delete(route('api.calendar_delete', ['id' => $calendar->id]));

        $response->assertStatus(401);
    }
}
