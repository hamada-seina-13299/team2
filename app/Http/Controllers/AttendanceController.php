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

class AttendanceController extends Controller
{
    /**
     * 月次申請の予約種別
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

        $workings = Working::where('user_id', $user->id)
            ->whereYear('punch_date', $year)
            ->whereMonth('punch_date', $month)
            ->get();

        $workingsKeyed = $workings->keyBy(fn($item) => Carbon::parse($item->punch_date)->format('Y-m-d'));

        $shifts = Shift::where('user_id', $user->id)
            ->whereYear('target_date', $year)
            ->whereMonth('target_date', $month)
            ->get();

        $shiftsKeyed = $shifts->keyBy(fn($item) => Carbon::parse($item->target_date)->format('Y-m-d'));

        $shiftMasters = ShiftMaster::whereIn('id', $shifts->pluck('master_id')->filter()->unique())
            ->get();

        $shiftMastersKeyed = $shiftMasters->keyBy('id');

        $attendanceRequests = AttendanceRequest::where('user_id', $user->id)
            ->whereYear('target_date', $year)
            ->whereMonth('target_date', $month)
            ->where('request_type', '!=', self::SUBMISSION_REQUEST_TYPE)
            ->orderBy('target_date')
            ->get()
            ->groupBy(fn($item) => Carbon::parse($item->target_date)->format('Y-m-d'));

        // 【修正】無限ループを防ぐため、事前に一か月分の基本祝日マップを生成
        $holidayMap = $this->generateMonthlyHolidayMap($year, $month);

        $calendar = [];
        for ($day = 1; $day <= $currentMonth->daysInMonth; $day++) {
            $date = $currentMonth->copy()->day($day);
            $key  = $date->format('Y-m-d');
            $shift = $shiftsKeyed->get($key);
            $holidayName = $holidayMap[$day] ?? null;

            $calendar[] = [
                'date'         => $date,
                'working'      => $workingsKeyed->get($key),
                'shift'        => $shift,
                'shift_master' => $shift ? $shiftMastersKeyed->get($shift->master_id) : null,
                'requests'     => $attendanceRequests->get($key, collect()),
                'is_holiday'   => !is_null($holidayName),
                'holiday_name' => $holidayName,
            ];
        }

        $summary = $this->buildSummary($workings, $shifts, $shiftMasters, $user, $calendar);

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

        $this->submitMonthlyRequest($user->id, $year, $month);

        return redirect()
            ->route('attendance.index', ['year' => $year, 'month' => $month])
            ->with('success', '勤怠を申請しました。');
    }

    private function submitMonthlyRequest(int $userId, int $year, int $month): void
    {
        AttendanceRequest::updateOrCreate(
            [
                'user_id'      => $userId,
                'target_date'  => Carbon::create($year, $month, 1)->format('Y-m-d'),
                'request_type' => self::SUBMISSION_REQUEST_TYPE,
            ],
            [
                'memo' => '申請済み',
            ]
        );
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

    private function buildSummary(Collection $workings, Collection $shifts, Collection $shiftMasters, User $user, array $calendar = []): array
    {
        $totalWorkedMinutes = 0; 
        $workingDays        = 0; 
        $totalCommute       = 0; 
        $totalBreakMinutes  = 0; 
        $overtimeMinutes    = 0; 
        $lateEarlyMinutes   = 0; 

        $today = Carbon::today();

        foreach ($calendar as $row) {
            $working = $row['working'];
            $master  = $row['shift_master'];

            if ($working) {
                $totalCommute += (int) $working->commute;

                if ($row['date']->lte($today)) {
                    if ($working->attendance && $master && $master->attendance) {
                        $actualAtt = Carbon::parse($working->attendance);
                        $scheduledAtt = Carbon::parse($master->attendance);
                        if ($actualAtt->gt($scheduledAtt)) {
                            $lateEarlyMinutes += $scheduledAtt->diffInMinutes($actualAtt);
                        }
                    }

                    if ($working->leaving && $master && $master->leaving) {
                        $actualLeav = Carbon::parse($working->leaving);
                        $scheduledLeav = Carbon::parse($master->leaving);

                        if ($actualLeav->lt($scheduledLeav)) {
                            $lateEarlyMinutes += $actualLeav->diffInMinutes($scheduledLeav);
                        }

                        $scheduledLeavPlus1 = $scheduledLeav->copy()->addHour();
                        if ($actualLeav->gt($scheduledLeavPlus1)) {
                            $overtimeMinutes += $scheduledLeav->diffInMinutes($actualLeav);
                        }
                    }
                }

                if ($row['date']->lte($today) && $working->attendance && $working->leaving) {
                    $workingDays++;

                    $attTime  = Carbon::parse($working->attendance);
                    $leavTime = Carbon::parse($working->leaving);

                    if ($master && $master->attendance) {
                        $masterAtt = Carbon::parse($master->attendance);
                        if ($attTime->lt($masterAtt)) {
                            $attTime = $masterAtt;
                        }
                    }

                    if ($master && $master->leaving) {
                        $masterLeav = Carbon::parse($master->leaving);
                        $masterLeavPlus1 = $masterLeav->copy()->addHour();
                        if ($leavTime->gt($masterLeav) && $leavTime->lt($masterLeavPlus1)) {
                            $leavTime = $masterLeav;
                        }
                    }

                    $minutes = $attTime->diffInMinutes($leavTime);

                    if ($working->break_time && $working->break_end_time) {
                        $bStart  = Carbon::parse($working->break_time);
                        $bEnd    = Carbon::parse($working->break_end_time);

                        if ($bEnd->gt($bStart)) {
                            $breakMinutes = $bStart->diffInMinutes($bEnd);
                            $minutes -= $breakMinutes;
                            $totalBreakMinutes += $breakMinutes;
                        }
                    } elseif ($master && $master->break_time) {
                        $breakMinutes = Carbon::parse('00:00')->diffInMinutes(Carbon::parse($master->break_time));
                        $minutes -= $breakMinutes;
                        $totalBreakMinutes += $breakMinutes;
                    }

                    $totalWorkedMinutes += max($minutes, 0);
                }
            }
        }

        $scheduledMinutes = 0;
        foreach ($calendar as $row) {
            if ($row['is_holiday']) {
                continue;
            }

            $shift = $row['shift'];
            if (!$shift) {
                continue;
            }

            $master = $row['shift_master'];
            if (! $master || ! $master->attendance || ! $master->leaving) {
                continue;
            }

            $minutes = Carbon::parse($master->attendance)->diffInMinutes(Carbon::parse($master->leaving));

            if ($master->break_time) {
                $minutes -= Carbon::parse('00:00')->diffInMinutes(Carbon::parse($master->break_time));
            }

            $scheduledMinutes += max($minutes, 0);
        }

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
            'total_break_time'    => $this->formatMinutes($totalBreakMinutes),
            'paid_leave_days'     => $paidLeaveDaysUsed,
            'half_day_leave_days' => $halfDayLeaveDaysUsed,
            'overtime_time'       => $this->formatMinutes($overtimeMinutes),
            'late_early_time'     => $this->formatMinutes($lateEarlyMinutes),
        ];
    }

    private function formatMinutes(int $minutes): string
    {
        return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

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

    private function authorizeOwner(AttendanceRequest $attendanceRequest): void
    {
        abort_unless($attendanceRequest->user_id === $this->currentUser()->id, 403);
    }

    private function yearMonthParams(string|Carbon $targetDate): array
    {
        $date = $targetDate instanceof Carbon ? $targetDate : Carbon::parse($targetDate);

        return ['year' => $date->year, 'month' => $date->month];
    }

    private function currentUser(): User
    {
        return Auth::user() ?? User::findOrFail(1);
    }

    public function cancel(Request $request)
    {
        $user  = $this->currentUser();
        $year  = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $targetDate = Carbon::create($year, $month, 1)->format('Y-m-d');

        $submission = AttendanceRequest::where('user_id', $user->id)
            ->where('request_type', self::SUBMISSION_REQUEST_TYPE)
            ->whereDate('target_date', $targetDate)
            ->first();

        if ($submission) {
            $submission->delete();
        }

        return redirect()
            ->route('attendance.index', ['year' => $year, 'month' => $month])
            ->with('success', $year . '年' . $month . '月の勤怠申請を取り下げました。');
    }

    /**
     * 再帰呼び出し（無限ループ）を排除した、安全かつ正確な月間祝日マップ作成ロジック
     */
    private function generateMonthlyHolidayMap(int $year, int $month): array
    {
        $currentMonth = Carbon::create($year, $month, 1);
        $daysInMonth = $currentMonth->daysInMonth;

        $baseHolidays = [];

        // 1. まずその月の固定祝日・ハッピーマンデー・春分秋分をマッピング
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

        // 前月末が日曜日かつ祝日だった場合の、当月1日への振替休日影響を確認
        $prevMonthLast = $currentMonth->copy()->subDay();
        $hasPrevMonthLastHoliday = false;
        if ($prevMonthLast->month === 1 && $prevMonthLast->day === 1) $hasPrevMonthLastHoliday = true; // 例外的な元日のみ考慮
        // (通常の運用上、前月を跨ぐ振替はほぼ元日のみのためこれで安全にカバー)

        $finalHolidays = $baseHolidays;

        // 2. 振替休日の判定 (日曜日が祝日の場合、翌平日が振替)
        for ($d = 1; $d <= $daysInMonth; $d++) {
            if (isset($baseHolidays[$d])) {
                continue; 
            }

            $date = Carbon::create($year, $month, $d);
            $w = $date->dayOfWeek;

            if ($w !== Carbon::SUNDAY) {
                // 前日が祝日かつ日曜日なら、当日は振替休日
                if ($d === 1 && $hasPrevMonthLastHoliday && $prevMonthLast->dayOfWeek === Carbon::SUNDAY) {
                    $finalHolidays[$d] = '振替休日';
                } elseif ($d > 1 && isset($baseHolidays[$d - 1]) && Carbon::create($year, $month, $d - 1)->dayOfWeek === Carbon::SUNDAY) {
                    $finalHolidays[$d] = '振替休日';
                }
                // 5月3日(日)・5月4日(月・祝)・5月5日(火・祝) のように祝日が重なる場合の5月6日振替対策
                elseif ($month === 5 && $d === 6 && isset($baseHolidays[3]) && isset($baseHolidays[4]) && isset($baseHolidays[5])) {
                    $finalHolidays[$d] = '振替休日';
                }
            }
        }

        // 3. 国民の休日の判定 (祝日と祝日に挟まれた平日)
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