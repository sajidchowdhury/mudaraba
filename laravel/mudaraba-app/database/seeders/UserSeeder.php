<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create an employee record for the superadmin
        $employee = Employee::firstOrCreate(
            ['name' => 'Mohammad'],
            [
                'designation' => 'Senior IT Executive',
                'phone_number' => '8801911599014',
                'status' => 'Active',
                'created_date' => now(),
            ],
        );

        // Create the superadmin user
        User::firstOrCreate(
            ['username' => 'E0001'],
            [
                'employee_id' => $employee->id,
                'email' => 'admin@mudaraba.test',
                'password_hash' => Hash::make('Mudaraba@2026'),
                'role' => 'superadmin',
                'status' => 'Active',
                'login_start' => '09:00:00',
                'login_end' => '20:00:00',
            ],
        );
    }
}
