<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShiftMaster; 

class ShiftMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ShiftMaster::insert([
            [
                'name' => '本社(出社)',
                'working_place' => '本社',
                'attendance' => '09:00:00',
                'leaving' => '17:30:00',
                'break_start_time' => '12:00:00',
                'break_time' => '01:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '本社(在宅)',
                'working_place' => '自宅',
                'attendance' => '09:00:00',
                'leaving' => '17:30:00',
                'break_start_time' => '12:00:00',
                'break_time' => '01:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '本社(夜勤出社)',
                'working_place' => '本社',
                'attendance' => '17:30:00',
                'leaving' => '09:00:00',
                'break_start_time' => '00:00:00',
                'break_time' => '01:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '研修(研修所)',
                'working_place' => '研修所',
                'attendance' => '9:00:00',
                'leaving' => '17:30:00',
                'break_start_time' => '12:00:00',
                'break_time' => '01:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);;
    }
}