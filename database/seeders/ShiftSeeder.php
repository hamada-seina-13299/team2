<?php

namespace Database\Seeders;

use App\Models\Shift;
use App\Models\ShiftMaster;
use App\Models\User;
use App\Models\Working;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ShiftSeeder extends Seeder
{
    /**
     * 「出勤・打刻データ」レポート画面の動作確認用シーダー。
     *
     * 【前提】
     * ・shift_masters に最低1件あること（name / attendance / leaving / working_place など必須カラムあり）
     * ・A・B・Cさんは users.name が 'A' / 'B' / 'C' で登録されている想定です。
     *   実際の名前が違う場合は $abcNames を書き換えてください。
     *
     * 【今回の変更点】
     * ・以前は「今日」の分もランダムだったため、たまたま3人とも同じステータス（欠勤）に
     *   偏ってしまっていました。今回は「今日」の分だけは A・B・C で必ず別々のステータス
     *   （出勤／遅刻／欠勤）になるよう固定し、出勤・退勤時刻もきちんと入るようにしています。
     * ・それ以外の過去日はランダムに「出勤／遅刻／欠勤／休み」の4パターン全てが出るようにしています。
     * ・未来日（今月の残り＋来月分）はシフト（予定）だけを作成します。まだ出勤していないので
     *   打刻データは作りません。
     * ・全ユーザー向けのランダムデータも、直近14日間の過去分に加えて今後14日分の未来シフトを
     *   作成するようにしました。
     * ・勤務地（shift_masters）もランダムに選ぶようにし、同じ人でも日によって勤務地が変わるように
     *   しています。
     * ・再実行しても正しく上書きされるよう、A・B・Cさんの今月〜来月分は一旦削除してから作り直します。
     */
    public function run(): void
    {
        $masters = ShiftMaster::all();

        if ($masters->isEmpty()) {
            $this->command?->warn('shift_masters にレコードがありません。先にShiftMasterのシーダーを実行してください。');
            return;
        }

        // 1) 既存ユーザー全員：直近14日間のランダムデータ（重複はスキップ）
        $allUsers = User::all();

        if ($allUsers->isEmpty()) {
            $this->command?->warn('users にレコードがありません。先にUserのシーダーを実行してください。');
            return;
        }

        $this->seedRandomRange(
            $allUsers,
            Carbon::today()->subDays(13),
            Carbon::today()->addDays(13),
            $masters
        );

        // 2) A・B・Cさん：今月分を作り直す（今日は必ずステータスが分かれるようにする）
        $abcNames = ['A', 'B', 'C'];
        $abcUsers = User::whereIn('name', $abcNames)->get();

        if ($abcUsers->isEmpty()) {
            $this->command?->warn('A・B・Cという名前のユーザーが見つかりませんでした。users.name の値を確認し、$abcNames を実際の名前に合わせて修正してください。');
            return;
        }

        $this->reseedAbcMonth($abcUsers, $masters);

        $this->command?->info('ShiftSeeder: A・B・Cさんの今月〜来月分シフトを作り直しました（今日は出勤／遅刻／欠勤に分かれています）。');
    }

    /**
     * 既存ユーザー全員向け：指定期間（平日のみ）にランダムでシフト／打刻を作成する。
     * ・過去日／当日：出勤・遅刻・欠勤・休みをランダムに割り振る。
     * ・未来日：シフト（予定）のみ作成し、打刻は作らない。
     * 既に同じユーザー・同じ日にシフトがある場合はスキップ（重複防止）。
     */
    private function seedRandomRange($users, Carbon $start, Carbon $end, $masters): void
    {
        $today = Carbon::today();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($date->isWeekend()) {
                continue;
            }

            foreach ($users as $user) {
                $alreadyExists = Shift::where('user_id', $user->id)
                    ->where('target_date', $date->toDateString())
                    ->exists();

                if ($alreadyExists) {
                    continue;
                }

                $isFuture = $date->gt($today);
                $pattern = $isFuture
                    ? '出勤'
                    : fake()->randomElement(['出勤', '出勤', '遅刻', '欠勤', '休み']);

                $this->createShiftAndWorking($user, $date, $pattern, $masters->random(), $isFuture);
            }
        }
    }

    /**
     * A・B・Cさん向け：今月〜来月分（平日のみ）を一旦削除してから作り直す。
     * ・今日は必ずユーザーごとに異なるステータス（出勤／遅刻／欠勤）になるように固定する。
     * ・今日より前の過去日はランダムに「出勤／遅刻／欠勤／休み」の全パターンが出るようにする。
     * ・今日より後の未来日（来月分含む）はシフト（予定）のみ作成し、打刻は作らない。
     */
    private function reseedAbcMonth($users, $masters): void
    {
        $today = Carbon::today();
        $monthStart = Carbon::today()->startOfMonth();
        // 今月末だけでなく来月末まで、未来のシフト（予定）も作成する
        $monthEnd = Carbon::today()->addMonthNoOverflow()->endOfMonth();
        $userIds = $users->pluck('id');

        // 再実行しても内容が上書きされるよう、今月分は一旦削除する
        Working::whereIn('user_id', $userIds)
            ->whereBetween('punch_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->delete();

        Shift::whereIn('user_id', $userIds)
            ->whereBetween('target_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->delete();

        // 「今日」は必ず全員別々のステータスになるようにする
        $todayPatternCycle = ['出勤', '遅刻', '欠勤'];
        $randomPatternCycle = ['出勤', '遅刻', '欠勤', '休み'];

        foreach ($users as $userIndex => $user) {
            for ($date = $monthStart->copy(); $date->lte($monthEnd); $date->addDay()) {
                if ($date->isWeekend()) {
                    continue;
                }

                $isFuture = $date->gt($today);
                $isToday = $date->equalTo($today);

                if ($isFuture) {
                    $pattern = '出勤';
                } elseif ($isToday) {
                    $pattern = $todayPatternCycle[$userIndex % count($todayPatternCycle)];
                } else {
                    $pattern = fake()->randomElement($randomPatternCycle);
                }

                $this->createShiftAndWorking($user, $date, $pattern, $masters->random(), $isFuture);
            }
        }
    }

    /**
     * 1人・1日分のシフト／打刻データを作成する共通処理。
     */
    private function createShiftAndWorking(User $user, Carbon $date, string $pattern, ShiftMaster $master, bool $isFuture): void
    {
        if ($pattern === '休み') {
            // シフト自体を作らない → resolveAttendanceStatus() が「休み」と判定する
            return;
        }

        $shift = Shift::create([
            'user_id' => $user->id,
            'master_id' => $master->id,
            'target_date' => $date->toDateString(),
            'status' => '承認済み',
        ]);

        // 未来日は打刻データを作らない（まだ出勤していないため）
        if ($isFuture) {
            return;
        }

        if ($pattern === '欠勤') {
            // シフトはあるが打刻なし（過去日／当日なので「欠勤」判定になる）
            return;
        }

        $scheduledAttendance = $shift->attendance_edit ?? $master->attendance;
        $scheduledLeaving = $shift->leaving_edit ?? $master->leaving;

        $attendanceTime = Carbon::parse($scheduledAttendance);
        $leavingTime = Carbon::parse($scheduledLeaving);

        if ($pattern === '遅刻') {
            // 予定より15〜45分遅く打刻
            $attendanceTime->addMinutes(fake()->numberBetween(15, 45));
        }

        Working::create([
            'user_id' => $user->id,
            'punch_date' => $date->toDateString(),
            'attendance' => $attendanceTime->format('H:i:s'),
            'leaving' => $leavingTime->format('H:i:s'),
            'working_place' => $master->working_place,
            'status' => $pattern,
        ]);
    }
}