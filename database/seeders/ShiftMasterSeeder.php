<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShiftMaster;

class ShiftMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * ⚠️ ID1〜4は ShiftSeeder.php が firstWhere('id', 1) のように直接ID指定で参照しているため、
     *    「勤務時間帯・位置づけ」はそのまま保ち、名称と勤務地だけ航空系にリネームしている。
     *    ID1: 通常の日勤（運航管理部・オフィス出社）
     *    ID2: ID1と同じ時間帯の在宅バリエーション（user1の水曜日用）
     *    ID3: 夜勤（user5の夜勤ローテーション用）
     *    ID4: users 2〜4 が固定で使う勤務（訓練センター）
     *    ID5以降は追加のバリエーション（ランダム割り当て・シフト追加画面での選択肢用）
     */
    public function run(): void
    {
        ShiftMaster::insert([
            // --- ID1〜4：既存の位置づけを保ったまま航空系にリネーム ---
            [
                'name' => '運航管理部(出社)',
                'working_place' => '羽田空港オペレーションセンター',
                'attendance' => '09:00:00',
                'leaving' => '17:30:00',
                'break_start_time' => '12:00:00',
                'break_time' => '01:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '運航管理部(在宅)',
                'working_place' => '自宅',
                'attendance' => '09:00:00',
                'leaving' => '17:30:00',
                'break_start_time' => '12:00:00',
                'break_time' => '01:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'グランドハンドリング(夜勤)',
                'working_place' => '羽田空港 貨物・グランドエリア',
                'attendance' => '17:30:00',
                'leaving' => '09:00:00',
                'break_start_time' => '00:00:00',
                'break_time' => '01:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '客室訓練センター(研修)',
                'working_place' => '訓練センター',
                'attendance' => '9:00:00',
                'leaving' => '17:30:00',
                'break_start_time' => '12:00:00',
                'break_time' => '01:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // --- ID5以降：バリエーション追加（ランダム割り当て・手動選択用） ---
            [
                'name' => '客室乗務員(国内線乗務)',
                'working_place' => '羽田空港 搭乗ゲート',
                'attendance' => '07:30:00',
                'leaving' => '16:30:00',
                'break_start_time' => '12:30:00',
                'break_time' => '01:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '客室乗務員(国際線乗務)',
                'working_place' => '成田空港 搭乗ゲート',
                'attendance' => '14:00:00',
                'leaving' => '23:00:00',
                'break_start_time' => '18:00:00',
                'break_time' => '01:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '空港カウンター(早番)',
                'working_place' => '羽田空港 出発カウンター',
                'attendance' => '06:00:00',
                'leaving' => '15:00:00',
                'break_start_time' => '11:00:00',
                'break_time' => '01:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '空港カウンター(遅番)',
                'working_place' => '羽田空港 出発カウンター',
                'attendance' => '13:00:00',
                'leaving' => '22:00:00',
                'break_start_time' => '17:30:00',
                'break_time' => '01:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '整備士(早番)',
                'working_place' => '格納庫',
                'attendance' => '06:00:00',
                'leaving' => '15:00:00',
                'break_start_time' => '11:30:00',
                'break_time' => '01:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '整備士(夜勤)',
                'working_place' => '格納庫',
                'attendance' => '21:00:00',
                'leaving' => '06:00:00',
                'break_start_time' => '01:00:00',
                'break_time' => '01:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '貨物ハンドリング',
                'working_place' => '貨物ターミナル',
                'attendance' => '08:00:00',
                'leaving' => '17:00:00',
                'break_start_time' => '12:00:00',
                'break_time' => '01:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '予約・コールセンター',
                'working_place' => '自宅',
                'attendance' => '09:00:00',
                'leaving' => '18:00:00',
                'break_start_time' => '12:00:00',
                'break_time' => '01:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}