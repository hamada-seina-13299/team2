<?php

namespace Database\Seeders;

use App\Models\ShiftSubmission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ShiftSubmissionSeeder extends Seeder
{
    /**
     * シフト提出状況（未提出／申請中／承認済み／差し戻し）のサンプルデータ。
     *
     * ・管理者以外（admin=false）の全ユーザーに対して、今月・先月分を作成します。
     * ・ステータスはランダムだと偏る可能性があるため、順番に割り振って全パターンが
     *   必ず出るようにしています。
     * ・[user_id, year, month] の組み合わせで updateOrCreate しているので、
     *   何度実行しても重複しません（ReportController の「未承認件数」バッジの
     *   確認にも使えます＝status:'申請中' のデータが必ず含まれます）。
     */
    public function run(): void
    {
        $users = User::where('admin', false)->get();

        if ($users->isEmpty()) {
            $this->command?->warn('対象ユーザー（admin=false）が見つかりません。先にUserSeederを実行してください。');
            return;
        }

        $statusCycle = ['未提出', '申請中', '承認済み', '差し戻し'];

        $targetMonths = [
            Carbon::today()->startOfMonth(),                       // 今月
            Carbon::today()->subMonthNoOverflow()->startOfMonth(), // 先月
        ];

        foreach ($targetMonths as $monthIndex => $monthDate) {
            foreach ($users as $userIndex => $user) {
                if ($monthIndex === 1) {
                    // 先月分は基本「承認済み」、一部だけ「差し戻し」にする
                    $status = ($userIndex % 5 === 0) ? '差し戻し' : '承認済み';
                } else {
                    // 今月分は全ステータスが出るように順番に割り振る
                    $status = $statusCycle[$userIndex % count($statusCycle)];
                }

                ShiftSubmission::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'year' => $monthDate->year,
                        'month' => $monthDate->month,
                    ],
                    [
                        'status' => $status,
                    ]
                );
            }
        }

        $this->command?->info('ShiftSubmissionSeeder: 今月・先月分のシフト提出状況を作成しました。');
    }
}