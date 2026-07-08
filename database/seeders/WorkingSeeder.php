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

        // 2026年6月と7月の祝日マップを事前に生成（跨いで生成できるようにするため）
        $holidayMap6 = $this->generateMonthlyHolidayMap(2026, 6);
        $holidayMap7 = $this->generateMonthlyHolidayMap(2026, 7);

        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            
            // 1. 土日は勤務実績なし（スキップ）
            if ($date->isWeekend()) {
                continue;
            }

            // 2. 祝日も勤務実績なし（スキップ）
            $d = $date->day;
            if ($date->month === 6 && isset($holidayMap6[$d])) {
                continue; // 6月の祝日ならスキップ
            }
            if ($date->month === 7 && isset($holidayMap7[$d])) {
                continue; // 7月の祝日ならスキップ
            }

            // 実績データに少しブレを出すためのランダム値
            $randomMinuteIn = rand(-25, -5);   // 8:35 〜 8:55
            $randomMinuteOut = rand(0, 15);  // 17:30 〜 17:45

            Working::create([
                'user_id' => 1,
                'punch_date' => $date->format('Y-m-d'),
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

    /**
     * 安全かつ正確な月間祝日マップ作成ロジック（コントローラーと共通）
     */
    private function generateMonthlyHolidayMap(int $year, int $month): array
    {
        $currentMonth = Carbon::create($year, $month, 1);
        $daysInMonth = $currentMonth->daysInMonth;

        $baseHolidays = [];

        // 1. 固定祝日・ハッピーマンデー・春分秋分のマッピング
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::create($year, $month, $d);
            $w = $date->dayOfWeek;
            $name = null;

            if ($month === 1 && $d === 1)   $name = '元日';
            if ($month === 2 && $d === 11)  $name = '建国記念の日';
            if ($month === 2 && $d === 23)  $name = '天皇誕生日';
            if ($month === 4 && $d === 29)  $name = '昭和の日';
            if ($month === 5 && $d === 3)   $name = '憲法記念日';
            if ($month === 5 && $d === 4)   $name = 'みどりの日';
            if ($month === 5 && $d === 5)   $name = 'こどもの日';
            if ($month === 8 && $d === 11)  $name = '山の日';
            if ($month === 11 && $d === 3)  $name = '文化の日';
            if ($month === 11 && $d === 23) $name = '勤労感謝の日';

            $nthMonday = intdiv($d - 1, 7) + 1;
            if ($w === Carbon::MONDAY) {
                if ($month === 1 && $nthMonday === 2)  $name = '成人の日';
                if ($month === 7 && $nthMonday === 3)  $name = '海の日';
                if ($month === 9 && $nthMonday === 3)  $name = '敬老の日';
                if ($month === 10 && $nthMonday === 2) $name = 'スポーツの日';
            }

            if ($month === 3) {
                $shunbun = intval(20.8431 + 0.242194 * ($year - 1980) - intval(($year - 1980) / 4));
                if ($d === $shunbun) $name = '春分の日';
            }
            if ($month === 9) {
                $shubun = intval(23.2488 + 0.242194 * ($year - 1980) - intval(($year - 1980) / 4));
                if ($d === $shubun) $name = '秋分の日';
            }

            if ($name) {
                $baseHolidays[$d] = $name;
            }
        }

        $prevMonthLast = $currentMonth->copy()->subDay();
        $hasPrevMonthLastHoliday = false;
        if ($prevMonthLast->month === 1 && $prevMonthLast->day === 1) $hasPrevMonthLastHoliday = true;

        $finalHolidays = $baseHolidays;

        // 2. 振替休日の判定
        for ($d = 1; $d <= $daysInMonth; $d++) {
            if (isset($baseHolidays[$d])) {
                continue; 
            }

            $date = Carbon::create($year, $month, $d);
            $w = $date->dayOfWeek;

            if ($w !== Carbon::SUNDAY) {
                if ($d === 1 && $hasPrevMonthLastHoliday && $prevMonthLast->dayOfWeek === Carbon::SUNDAY) {
                    $finalHolidays[$d] = '振替休日';
                } elseif ($d > 1 && isset($baseHolidays[$d - 1]) && Carbon::create($year, $month, $d - 1)->dayOfWeek === Carbon::SUNDAY) {
                    $finalHolidays[$d] = '振替休日';
                }
                elseif ($month === 5 && $d === 6 && isset($baseHolidays[3]) && isset($baseHolidays[4]) && isset($baseHolidays[5])) {
                    $finalHolidays[$d] = '振替休日';
                }
            }
        }

        // 3. 国民の休日の判定
        for ($d = 2; $d < $daysInMonth; $d++) {
            if (isset($finalHolidays[$d])) {
                continue;
            }
            $date = Carbon::create($year, $month, $d);
            $w = $date->dayOfWeek;

            if ($w !== Carbon::SUNDAY && $w !== Carbon::SATURDAY) {
                if (isset($finalHolidays[$d - 1]) && isset($finalHolidays[$d + 1])) {
                    if ($finalHolidays[$d - 1] !== '振替休日' && $finalHolidays[$d + 1] !== '振替休日') {
                        $finalHolidays[$d] = '国民の休日';
                    }
                }
            }
        }

        return $finalHolidays;
    }
}