<?php

namespace Database\Seeders;

use App\Models\Loan;
use App\Models\Payment;
use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role = (new Role())->where('name', '=', 'Admin')->first();

        $user = User::create([
            'role_id' => $role->id,
            'username' => 'admin',
            'email' => 'admin@bnpfinance.com',
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'), // 12345678
            'remember_token' => Str::random(10),
        ]);

        $user->profile()->create([
            'identity_number' => '1234567890',
            'nick_name' => 'Admin',
            'full_name' => 'Administrator'
        ]);

        User::factory()->count(19)
            ->hasProfile(1)
            ->create();

    }
}
