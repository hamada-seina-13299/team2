<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Shift; // 💡 実際のモデル名に合わせて適宜変更してください
use Carbon\Carbon;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $startOfMonth = Carbon::create(2026, 6, 1);
        $endOfMonth = Carbon::today(); 

        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            // 土日はシフトなし（スキップ）
            if ($date->isWeekend()) {
                continue;
            }

            Shift::create([
                'user_id' => 1,
                'master_id' => 1, // シフトマスタID
                'target_date' => $date->format('Y-m-d'),
                'status' => '承認',
                'created_at' => now(),
                'updated_at' => now(),
            ]);;
        }
    }
}
