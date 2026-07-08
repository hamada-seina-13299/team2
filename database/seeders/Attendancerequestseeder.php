<?php

namespace Database\Seeders;

use App\Models\AttendanceRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AttendanceRequestSeeder extends Seeder
{
    /**
     * 打刻修正以外の勤怠申請（attendance_requests）のサンプルデータ。
     *
     * ・request_type に実際どんな値を使っているかがこちらでは分からないため、
     *   一般的な想定で「有給休暇」「半休」「遅刻届」「早退届」を使っています。
     *   実際に使っている値と違う場合は $patterns の request_type を修正してください。
     * ・attendance_requests テーブルには status カラムが無いため、承認/却下のような
     *   ステータスは持たせていません（申請一覧としての表示確認用データです）。
     * ・admin=false のユーザーから最大5名分、過去〜未来にかけて作成します。
     * ・[user_id, target_date, request_type] で既存チェックしてから作成するので、
     *   再実行しても重複して増え続けることはありません。
     */
    public function run(): void
    {
        $users = User::where('admin', false)->take(5)->get();

        if ($users->isEmpty()) {
            $this->command?->warn('対象ユーザー（admin=false）が見つかりません。先にUserSeederを実行してください。');
            return;
        }

        // [days_offset(今日からの日数、マイナスは過去), request_type, memo]
        $patterns = [
            [-3, '有給休暇', '私用のため'],
            [-1, '遅刻届', '電車遅延のため'],
            [2, '半休', '通院のため'],
            [5, '有給休暇', '家族の用事のため'],
            [10, '早退届', '通院のため'],
        ];

        foreach ($users as $index => $user) {
            [$dayOffset, $type, $memo] = $patterns[$index % count($patterns)];
            $targetDate = Carbon::today()->addDays($dayOffset);

            $exists = AttendanceRequest::where('user_id', $user->id)
                ->where('target_date', $targetDate->toDateString())
                ->where('request_type', $type)
                ->exists();

            if ($exists) {
                continue;
            }

            AttendanceRequest::create([
                'user_id' => $user->id,
                'target_date' => $targetDate->toDateString(),
                'request_type' => $type,
                'memo' => $memo,
                'request_time' => in_array($type, ['遅刻届', '早退届'], true) ? '09:30:00' : null,
                'attachment' => null,
            ]);
        }

        $this->command?->info('AttendanceRequestSeeder: 勤怠申請のサンプルを作成しました。');
    }
}