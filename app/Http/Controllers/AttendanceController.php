<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRequest;
use App\Models\Shift;
use App\Models\ShiftMaster;
use App\Models\User;
use App\Models\Working;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    /**
     * 月次の申請ステータスを表すために attendance_requests に登録する予約済みの申請種別。
     * target_date にその月の1日、memo に「未申請・申請済み・承認・却下」のいずれかを保存する。
     * 通常のモーダルの選択肢（validated()の in: ルール）には含めていないので、
     * ユーザーが誤ってこの種別の申請を作成することはない。
     */
    private const SUBMISSION_REQUEST_TYPE = '月次申請';

    private const REQUEST_TYPES_WITHOUT_TIME = ['欠勤', '有給'];

    public function index(Request $request)
    {
        $user  = $this->currentUser();
        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $currentMonth = Carbon::create($year, $month, 1);
        $prevMonth    = $currentMonth->copy()->subMonth();
        $nextMonth    = $currentMonth->copy()->addMonth();

        // 実績（打刻）データ：日付をキーに取得
        $workings = Working::where('user_id', $user->id)
            ->whereYear('punch_date', $year)
            ->whereMonth('punch_date', $month)
            ->get();

        $workingsKeyed = $workings->keyBy(fn($item) => Carbon::parse($item->punch_date)->format('Y-m-d'));

        // シフト予定データ：日付をキーに取得
        $shifts = Shift::where('user_id', $user->id)
            ->whereYear('target_date', $year)
            ->whereMonth('target_date', $month)
            ->get();

        $shiftsKeyed = $shifts->keyBy(fn($item) => Carbon::parse($item->target_date)->format('Y-m-d'));

        $shiftMasters = ShiftMaster::whereIn('id', $shifts->pluck('master_id')->filter()->unique())
            ->get();

        $shiftMastersKeyed = $shiftMasters->keyBy('id');

        // 勤怠申請データ（月次申請ステータス用の予約レコードは一覧には出さない）
        $attendanceRequests = AttendanceRequest::where('user_id', $user->id)
            ->whereYear('target_date', $year)
            ->whereMonth('target_date', $month)
            ->where('request_type', '!=', self::SUBMISSION_REQUEST_TYPE)
            ->orderBy('target_date')
            ->get()
            ->groupBy(fn($item) => Carbon::parse($item->target_date)->format('Y-m-d'));

        // 1日〜月末までのカレンダー配列を作成し、各テーブルのデータを紐付ける
        $calendar = [];
        for ($day = 1; $day <= $currentMonth->daysInMonth; $day++) {
            $date = $currentMonth->copy()->day($day);
            $key  = $date->format('Y-m-d');
            $shift = $shiftsKeyed->get($key);

            $calendar[] = [
                'date'         => $date,
                'working'      => $workingsKeyed->get($key),
                'shift'        => $shift,
                'shift_master' => $shift ? $shiftMastersKeyed->get($shift->master_id) : null,
                'requests'     => $attendanceRequests->get($key, collect()),
            ];
        }

        $summary = $this->buildSummary($workings, $shifts, $shiftMasters, $user, $calendar);

        // 月次申請ステータスの判定（自動判定ではなく、保存された値を読む）
        $lastWorkingDate = $this->resolveLastWorkingDate($user, $year, $month);
        $lastWorking = $lastWorkingDate
            ? $workingsKeyed->get($lastWorkingDate->format('Y-m-d'))
            : null;

        $submission = AttendanceRequest::where('user_id', $user->id)
            ->where('request_type', self::SUBMISSION_REQUEST_TYPE)
            ->whereDate('target_date', $currentMonth->format('Y-m-d'))
            ->first();

        $summary['monthly_status'] = $submission->memo ?? '未申請';
        $summary['can_submit'] = $summary['monthly_status'] === '未申請' && $lastWorking && $lastWorking->leaving;

        return view('attendance', [
            'user'         => $user,
            'calendar'     => $calendar,
            'currentMonth' => $currentMonth,
            'prevMonth'    => $prevMonth,
            'nextMonth'    => $nextMonth,
            'summary'      => $summary,
        ]);
    }

    /**
     * その月の勤怠を「申請済み」にする
     * （その月最後の出勤日＝シフトが登録されている最後の日の退勤打刻が完了していないと申請できない）
     */
    public function submit(Request $request)
    {
        $user  = $this->currentUser();
        $year  = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $lastWorkingDate = $this->resolveLastWorkingDate($user, $year, $month);

        abort_if(! $lastWorkingDate, 422, '対象月にシフトが登録されていません。');

        $lastWorking = Working::where('user_id', $user->id)
            ->whereDate('punch_date', $lastWorkingDate->format('Y-m-d'))
            ->first();

        abort_if(! $lastWorking || ! $lastWorking->leaving, 422, 'まだ最終出勤日の退勤打刻が完了していません。');

        AttendanceRequest::updateOrCreate(
            [
                'user_id'      => $user->id,
                'target_date'  => Carbon::create($year, $month, 1)->format('Y-m-d'),
                'request_type' => self::SUBMISSION_REQUEST_TYPE,
            ],
            [
                'memo' => '申請済み',
            ]
        );

        return redirect()
            ->route('attendance.index', ['year' => $year, 'month' => $month])
            ->with('success', '勤怠を申請しました。');
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('attendance_attachments', 'public');
        }

        $validated['user_id'] = $this->currentUser()->id;

        AttendanceRequest::create($validated);

        return redirect()
            ->route('attendance.index', $this->yearMonthParams($validated['target_date']))
            ->with('success', '勤怠申請を登録しました。');
    }

    /**
     * 勤怠申請を更新
     */
    public function update(Request $request, AttendanceRequest $attendanceRequest)
    {
        $this->authorizeOwner($attendanceRequest);

        $validated = $this->validated($request);

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('attendance_attachments', 'public');
        }

        $attendanceRequest->update($validated);

        return redirect()
            ->route('attendance.index', $this->yearMonthParams($validated['target_date']))
            ->with('success', '勤怠申請を更新しました。');
    }

    public function destroy(AttendanceRequest $attendanceRequest)
    {
        $this->authorizeOwner($attendanceRequest);

        $date = $attendanceRequest->target_date;
        $attendanceRequest->delete();

        return redirect()
            ->route('attendance.index', $this->yearMonthParams($date))
            ->with('success', '勤怠申請を削除しました。');
    }

    /**
     * 勤怠集計（合計勤務時間・所定労働時間・出勤日数・交通費合計など）を算出
     */
    private function buildSummary(Collection $workings, Collection $shifts, Collection $shiftMasters, User $user, array $calendar = []): array
    {
        $totalWorkedMinutes = 0; // 実際の勤務時間合計（実績ベース）
        $workingDays        = 0; // 出勤日数（両方打刻あり）
        $totalCommute       = 0; // 交通費合計

        $shiftMastersKeyed = $shiftMasters->keyBy('id');

        foreach ($calendar as $row) {
            $working = $row['working'];
            $master  = $row['shift_master'];

            Log::debug('Working row', [
                'date' => $row['date']->format('Y-m-d'),
                'working_exists' => !is_null($working),
                'attendance' => $working?->attendance,
                'leaving' => $working?->leaving,
                'break_time' => $working?->break_time,
            ]);

            if ($working) {
                $totalCommute += (int) $working->commute;

                // 【合計勤務時間】: 出勤打刻と退勤打刻が両方されている日数を対象
                if ($working->attendance && $working->leaving) {
                    Log::debug('Attendance calculation start', [
                        'attendance' => $working->attendance,
                        'leaving' => $working->leaving,
                    ]);
                    $workingDays++;

                    $attTime  = Carbon::parse($working->attendance);
                    $leavTime = Carbon::parse($working->leaving);

                    // 補正①: シフトマスタの出勤時刻より前の時刻だったらシフトマスタの出勤時刻にする
                    if ($master && $master->attendance) {
                        $masterAtt = Carbon::parse($master->attendance);
                        if ($attTime->lt($masterAtt)) {
                            $attTime = $masterAtt;
                        }
                    }

                    // 補正②: シフトマスタの退勤時刻より後の時刻かつシフトマスタの退勤時刻＋1時間未満だったらシフトマスタの退勤時刻にする
                    if ($master && $master->leaving) {
                        $masterLeav = Carbon::parse($master->leaving);
                        $masterLeavPlus1 = $masterLeav->copy()->addHour();
                        if ($leavTime->gt($masterLeav) && $leavTime->lt($masterLeavPlus1)) {
                            $leavTime = $masterLeav;
                        }
                    }

                    // 補正後の出退勤差分（分）
                    $minutes = $attTime->diffInMinutes($leavTime);
                    Log::debug('Before break', [
                        'minutes' => $minutes,
                    ]);

                    // 実績休憩時間を引く
                    if ($working->break_time) {
                        $minutes -= Carbon::parse('00:00')->diffInMinutes(Carbon::parse($working->break_time));
                    }

                    Log::debug('After break', [
                        'minutes' => $minutes,
                    ]);

                    $totalWorkedMinutes += max($minutes, 0);

                    Log::debug('Total', [
                        'totalWorkedMinutes' => $totalWorkedMinutes,
                    ]);
                }
            }
        }

        Log::debug('Summary Result', [
            'totalWorkedMinutes' => $totalWorkedMinutes,
            'formatted' => $this->formatMinutes($totalWorkedMinutes),
        ]);

        // 【所定労働時間】: あらかじめシフトで設定した分の出社時刻から退勤時刻（休憩除く）× 働く日数（シフト追加日数）
        $scheduledMinutes = 0;
        foreach ($shifts as $shift) {
            $master = $shiftMastersKeyed->get($shift->master_id);

            if (! $master || ! $master->attendance || ! $master->leaving) {
                continue;
            }

            $minutes = Carbon::parse($master->attendance)->diffInMinutes(Carbon::parse($master->leaving));

            if ($master->break_time) {
                $minutes -= Carbon::parse('00:00')->diffInMinutes(Carbon::parse($master->break_time));
            }

            $scheduledMinutes += max($minutes, 0);
        }

        // 当月の有給・半休の使用日数をカウント
        $paidLeaveDaysUsed    = 0;
        $halfDayLeaveDaysUsed = 0;
        foreach ($calendar as $row) {
            foreach ($row['requests'] as $req) {
                if ($req->request_type === '有給') {
                    $paidLeaveDaysUsed++;
                } elseif ($req->request_type === '半休') {
                    $halfDayLeaveDaysUsed++;
                }
            }
        }

        return [
            'total_worked_time'   => $this->formatMinutes($totalWorkedMinutes),
            'scheduled_time'      => $this->formatMinutes($scheduledMinutes),
            'working_days'        => $workingDays,
            'total_commute'       => $totalCommute,
            'paid_leave_days'     => $paidLeaveDaysUsed,
            'half_day_leave_days' => $halfDayLeaveDaysUsed,
        ];
    }

    private function formatMinutes(int $minutes): string
    {
        return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * 対象月内で、シフトが登録されている最後の日付を返す（月次申請の対象日を決めるため）
     */
    private function resolveLastWorkingDate($user, int $year, int $month): ?Carbon
    {
        $currentMonth = Carbon::create($year, $month, 1);

        for ($day = $currentMonth->daysInMonth; $day >= 1; $day--) {
            $date = $currentMonth->copy()->day($day);

            $hasShift = Shift::where('user_id', $user->id)
                ->whereDate('target_date', $date->format('Y-m-d'))
                ->exists();

            if ($hasShift) {
                return $date;
            }
        }

        return null;
    }

    /**
     * バリデーション（attendance_requests のカラムのみ対象）
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'target_date'   => ['required', 'date'],
            'request_type'  => ['required', 'string', 'in:遅刻,早退,欠勤,有給,半休,残業,有事遅刻,有事早退'],
            'memo'          => ['required', 'string', 'max:255'],
            'request_time'  => [
                'nullable',
                'date_format:H:i',
                'required_unless:request_type,' . implode(',', self::REQUEST_TYPES_WITHOUT_TIME),
            ],
            'attachment'    => ['nullable', 'file', 'max:10240'],
        ]);
    }

    /**
     * 自分の申請のみ操作できるようにする簡易ガード
     */
    private function authorizeOwner(AttendanceRequest $attendanceRequest): void
    {
        abort_unless($attendanceRequest->user_id === $this->currentUser()->id, 403);
    }

    /**
     * 対象日から一覧画面へ戻すための year / month パラメータを生成
     */
    private function yearMonthParams(string|Carbon $targetDate): array
    {
        $date = $targetDate instanceof Carbon ? $targetDate : Carbon::parse($targetDate);

        return ['year' => $date->year, 'month' => $date->month];
    }

    /**
     * ログイン中のユーザーを返す。
     *
     * TODO: ログイン機能が実装されたら、この一時的なフォールバック（user_id=1固定）は削除し、
     * 単純に Auth::user() のみを使うように戻してください。
     */
    private function currentUser(): User
    {
        return Auth::user() ?? User::findOrFail(1);
    }
}
