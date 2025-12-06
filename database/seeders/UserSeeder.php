<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Andrea Porcarelli',
            'email' => 'poandrea86@gmail.com',
            'password' => Hash::make('StaffTerme1'),
            'role' => 'admin',
        ]);

//        User::create([
//            'name' => 'Supervisor User',
//            'email' => 'supervisor@rapportini.local',
//            'password' => Hash::make('password'),
//            'role' => 'supervisor',
//        ]);
//
//        User::create([
//            'name' => 'Operator User',
//            'email' => 'operator@rapportini.local',
//            'password' => Hash::make('password'),
//            'role' => 'operator',
//        ]);
    }
}
