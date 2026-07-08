<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WorkingCorrection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class WorkingCorrectionSeeder extends Seeder
{
    /**
     * 勤怠修正申請（working_corrections）のサンプルデータ。
     *
     * ・admin=false のユーザーから最大5名分、過去日の打刻修正申請を作成します。
     * ・ステータスは「申請中」「承認」「却下」を混在させます。
     *   「承認」「却下」には updater_name（承認/却下した人の名前）を入れます。
     * ・[user_id, target_date] で既存チェックしてから作成するので、
     *   再実行しても重複して増え続けることはありません。
     */
    public function run(): void
    {
        $users = User::where('admin', false)->take(5)->get();
        $approver = User::where('admin', true)->first();

        if ($users->isEmpty()) {
            $this->command?->warn('対象ユーザー（admin=false）が見つかりません。先にUserSeederを実行してください。');
            return;
        }

        // [days_ago, status]
        $patterns = [
            [1, '申請中'],
            [2, '承認'],
            [3, '却下'],
            [5, '承認'],
            [7, '申請中'],
        ];

        foreach ($users as $index => $user) {
            [$daysAgo, $status] = $patterns[$index % count($patterns)];
            $targetDate = Carbon::today()->subDays($daysAgo);

            $exists = WorkingCorrection::where('user_id', $user->id)
                ->where('target_date', $targetDate->toDateString())
                ->exists();

            if ($exists) {
                continue;
            }

            WorkingCorrection::create([
                'user_id' => $user->id,
                'target_date' => $targetDate->toDateString(),
                'status' => $status,
                'before_attendance' => '09:00:00',
                'before_leaving' => '18:00:00',
                'before_break_time' => '12:00:00',
                'before_break_end_time' => '13:00:00',
                'before_working_place' => '本社',
                'after_attendance' => '09:15:00',
                'after_leaving' => '18:00:00',
                'after_break_time' => '12:00:00',
                'after_break_end_time' => '13:00:00',
                'after_working_place' => '本社',
                'memo' => '電車遅延のため出勤時刻の修正をお願いします。',
                'updater_name' => $status === '申請中' ? null : ($approver->name ?? '管理者'),
            ]);
        }

        $this->command?->info('WorkingCorrectionSeeder: 勤怠修正申請のサンプルを作成しました。');
    }
}