<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('shift_masters')->insert([
            [
                'user_id' => 1,
                'name' => '本社勤務',
                'working_place' => '本社勤務',
                'attendance' => '09:00:00',
                'leaving' => '17:30:00',
                'break_time' => '01:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'name' => '在宅勤務',
                'working_place' => '在宅勤務',
                'attendance' => '09:00:00',
                'leaving' => '17:30:00',
                'break_time' => '01:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'name' => '夜勤',
                'working_place' => '夜勤',
                'attendance' => '17:30:00',
                'leaving' => '09:00:00',
                'break_time' => '01:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'name' => '午前休勤務',
                'working_place' => '午前休勤務',
                'attendance' => '13:45:00',
                'leaving' => '17:30:00',
                'break_time' => '00:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}