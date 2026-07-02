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
            'name' => 'TestUser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'dept' => '開発部',
            'entering_company_date' => '2005-01-02',
            'can_auto_break' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
