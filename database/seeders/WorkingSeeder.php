<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Working; // 💡 workingsテーブルに対応するモデル名
use Carbon\Carbon;

class WorkingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $startOfMonth = Carbon::create(2026, 6, 1);
        $endOfMonth = Carbon::yesterday(); 

        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            // 土日は勤務実績なし（スキップ）
            if ($date->isWeekend()) {
                continue;
            }

            // 実績データに少しブレを出すためのランダム値
            $randomMinuteIn = rand(-25, -5);   // 8:35 〜 8:55
            $randomMinuteOut = rand(0, 15);  // 17:30 〜 17:45

            Working::create([
                'user_id' => 1,
                'punch_date' => $date->format('Y-m-d'), // シフトの定義に合わせtarget_dateと仮定
                'attendance' => $date->copy()->setTime(9, 0, 0)->addMinutes($randomMinuteIn)->format('H:i:s'),
                'leaving' => $date->copy()->setTime(17, 30, 0)->addMinutes($randomMinuteOut)->format('H:i:s'),
                'break_time' => '12:00:00',
                'break_end_time' => '13:00:00',
                'working_place' => null,  
                'commute' => 350,
                'status' => '未申請',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}