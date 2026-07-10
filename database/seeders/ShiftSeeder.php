<?php

namespace Database\Seeders;

use App\Models\Shift;
use App\Models\ShiftMaster;
use App\Models\User;
use App\Models\Working;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Yasumi\Yasumi;

class ShiftSeeder extends Seeder
{
    /**
     * 月に0〜1回だけ発生させる「遅刻/欠勤」の対象日をユーザーごとに保持する。
     * [$userId => [$dateString => '遅刻'|'欠勤']]
     */
    private array $specialPatternDates = [];

    /**
     * ユーザーごとに固定した通勤費（適当な範囲でユーザーごとに変化させる）
     * [$userId => int]
     */
    private array $userCommuteFares = [];

    public function run(): void
    {
        $masters = ShiftMaster::all();

        if ($masters->isEmpty()) {
            $this->command?->warn('shift_masters にレコードがありません。先にShiftMasterのシーダーを実行してください。');
            return;
        }

        $allUsers = User::all();

        if ($allUsers->isEmpty()) {
            $this->command?->warn('users にレコードがありません。先にUserのシーダーを実行してください。');
            return;
        }

        $this->seedRandomRange(
            $allUsers,
            Carbon::today()->subDays(40),
            Carbon::today()->addDays(22),
            $masters
        );
    }

    private function seedRandomRange($users, Carbon $start, Carbon $end, $masters): void
    {
        $today = Carbon::today();

        // 遅刻/欠勤の対象日を先に決めておく（月0〜1回のペースにするため）
        $this->specialPatternDates = $this->buildSpecialPatternDates($users, $start, $end, $today);

        // user5用の状態管理
        $user5LastMaster = null;
        $user5SkipDays = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {

            foreach ($users as $user) {

                // user5以外は土日祝をスキップ
                if (
                    $user->id !== 5 &&
                    ($date->isWeekend() || $this->isHoliday($date))
                ) {
                    continue;
                }

                // user5の休み判定
                if ($user->id === 5 && $user5SkipDays > 0) {
                    $user5SkipDays--;
                    continue;
                }

                $alreadyExists = Shift::where('user_id', $user->id)
                    ->where('target_date', $date->toDateString())
                    ->exists();

                if ($alreadyExists) {
                    continue;
                }

                $isFuture = $date->gt($today);

                // 未来日は必ず出勤予定。過去/当日は、あらかじめ決めておいた
                // 「遅刻/欠勤の対象日」に該当する場合だけ遅刻/欠勤、それ以外は通常出勤。
                $pattern = $isFuture
                    ? '出勤'
                    : ($this->specialPatternDates[$user->id][$date->toDateString()] ?? '出勤');

                switch ($user->id) {

                    case 1:
                        $master = $date->isWednesday()
                            ? $masters->firstWhere('id', 2)
                            : $masters->firstWhere('id', 1);
                        break;

                    case 2:
                    case 3:
                    case 4:
                        $master = $masters->firstWhere('id', 4);
                        break;

                    case 6:
                        $master = $masters->firstWhere('id', 1);
                        break;

                    case 5:

                        $masterId = fake()->randomElement([1, 3]);

                        if ($user5LastMaster !== null) {

                            // 1→3 または 3→1
                            if ($user5LastMaster != $masterId) {
                                $user5SkipDays = 2;
                            }
                            // 3→3
                            elseif ($masterId == 3) {
                                $user5SkipDays = 1;
                            }
                        }

                        $user5LastMaster = $masterId;
                        $master = $masters->firstWhere('id', $masterId);
                        break;

                    default:
                        $master = $masters->random();
                        break;
                }

                $this->createShiftAndWorking(
                    $user,
                    $date,
                    $pattern,
                    $master,
                    $isFuture
                );
            }
        }
    }

    /**
     * ユーザーごとに、月0〜1回のペースで「遅刻」または「欠勤」になる日をあらかじめ決めておく。
     * ・対象は過去〜当日まで（未来日は常に出勤なので対象外）
     * ・その人が実際に働く日（土日祝を除く）の中からランダムに1日だけ選ぶ
     * ・user5は打刻修正モーダルのSKIPロジックと絡めると複雑になるため、いったん対象外にしている
     *   （必要であれば同じ考え方で対応可能）
     */
    private function buildSpecialPatternDates($users, Carbon $start, Carbon $end, Carbon $today): array
    {
        $result = [];

        // 過去/当日の範囲だけが対象（未来日は常に出勤なので特別扱い不要）
        $rangeEnd = $end->greaterThan($today) ? $today->copy() : $end->copy();

        foreach ($users as $user) {
            if ($user->id === 5) {
                continue;
            }

            $result[$user->id] = [];

            $monthCursor = $start->copy()->startOfMonth();

            while ($monthCursor->lte($rangeEnd)) {

                $monthStart = $monthCursor->copy();
                if ($monthStart->lt($start)) {
                    $monthStart = $start->copy();
                }

                $monthEnd = $monthCursor->copy()->endOfMonth();
                if ($monthEnd->gt($rangeEnd)) {
                    $monthEnd = $rangeEnd->copy();
                }

                if ($monthStart->lte($monthEnd)) {
                    // その月に遅刻/欠勤を発生させるかどうか（0〜1回/月のイメージで50%の確率にしている）
                    if (fake()->boolean(50)) {
                        $candidateDates = [];

                        for ($d = $monthStart->copy(); $d->lte($monthEnd); $d->addDay()) {
                            if (!$d->isWeekend() && !$this->isHoliday($d)) {
                                $candidateDates[] = $d->toDateString();
                            }
                        }

                        if (!empty($candidateDates)) {
                            $targetDate = fake()->randomElement($candidateDates);
                            $result[$user->id][$targetDate] = fake()->randomElement(['遅刻', '欠勤']);
                        }
                    }
                }

                $monthCursor->addMonth();
            }
        }

        return $result;
    }

    /**
     * 1人・1日分のシフト／打刻データを作成する共通処理。
     */
    private function createShiftAndWorking(User $user, Carbon $date, string $pattern, ShiftMaster $master, bool $isFuture): void
    {
        $shift = Shift::create([
            'user_id' => $user->id,
            'master_id' => $master->id,
            'target_date' => $date->toDateString(),
            'status' => '承認',
        ]);

        if ($isFuture) {
            return;
        }

        if ($pattern === '欠勤') {
            return;
        }

        $scheduledAttendance = $shift->attendance_edit ?? $master->attendance;
        $scheduledLeaving = $shift->leaving_edit ?? $master->leaving;

        $attendanceTime = Carbon::parse($scheduledAttendance);
        $leavingTime = Carbon::parse($scheduledLeaving);

        if ($pattern === '遅刻') {
            // 遅刻：予定より15〜45分遅く打刻
            $attendanceTime->addMinutes(fake()->numberBetween(15, 45));
        } else {
            // 実績データに少しブレを出すためのランダム値
            $randomMinuteIn = fake()->numberBetween(-25, -5); // 9:00出勤だとしたら8:35〜8:55
            $attendanceTime->addMinutes($randomMinuteIn);
        }

        // 退勤側のブレは、通常/遅刻どちらの日にも一律で付与する
        $randomMinuteOut = fake()->numberBetween(0, 15); // 17:30退勤だとしたら17:30〜17:45
        $leavingTime->addMinutes($randomMinuteOut);

        Working::create([
            'user_id' => $user->id,
            'punch_date' => $date->toDateString(),
            'attendance' => $attendanceTime->format('H:i:s'),
            'leaving' => $leavingTime->format('H:i:s'),
            'commute' => $this->commuteFor($user),
            'status' => '未申請',
        ]);
    }

    /**
     * ユーザーごとに適当な通勤費を割り当てる（初回だけ決めて、以降は同じ人には同じ額を使い回す）。
     */
    private function commuteFor(User $user): int
    {
        if (!isset($this->userCommuteFares[$user->id])) {
            $this->userCommuteFares[$user->id] = fake()->randomElement(
                [150, 180, 210, 260, 320, 400, 480, 550, 620, 780, 900]
            );
        }

        return $this->userCommuteFares[$user->id];
    }

    private function isHoliday(Carbon $date): bool
    {
        $holidays = Yasumi::create('Japan', $date->year);

        return $holidays->isHoliday($date);
    }
}