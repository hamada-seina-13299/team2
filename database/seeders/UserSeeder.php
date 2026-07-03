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
        User::insert([
        [
            'name' => 'TestUser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'dept' => '開発部',
            'entering_company_date' => '2005-01-02',
            'can_auto_break' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'ながまやちいき',
            'email' => 'fgroup.shoping@gmail.com',
            'password' => Hash::make('2026fgroup.shoping'),
            'dept' => '開発部',
            'entering_company_date' => '2009-03-08',
            'can_auto_break' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        ]);;
    }
}
