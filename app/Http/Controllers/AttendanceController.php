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

        $shiftMasters = ShiftMaster::whereIn('id', $shifts->pluck('master_id')->filter()->unique())->get();
        $shiftMastersKeyed = $shiftMasters->keyBy('id');

        $attendanceRequests = AttendanceRequest::where('user_id', $user->id)
            ->whereYear('target_date', $year)
            ->whereMonth('target_date', $month)
            ->where('request_type', '!=', self::SUBMISSION_REQUEST_TYPE)
            ->orderBy('target_date')
            ->get()
            ->groupBy(fn($item) => Carbon::parse($item->target_date)->format('Y-m-d'));

        // 【エラーの原因】下部にメソッドを新設して解決
        $holidayMap = $this->generateMonthlyHolidayMap($year, $month);

        $calendar = [];
        for ($day = 1; $day <= $currentMonth->daysInMonth; $day++) {
            $date = $currentMonth->copy()->day($day);
            $key  = $date->format('Y-m-d');
            $shift = $shiftsKeyed->get($key);
            $holidayName = $holidayMap[$key] ?? null; // キーを日付フォーマット（Y-m-d）に対応

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
        $lastWorking = $lastWorkingDate ? $workingsKeyed->get($lastWorkingDate->format('Y-m-d')) : null;

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

    public function checkLateStatus(Request $request)
    {
        $user  = $this->currentUser();
        $year  = (int) $request->input('year');
        $month = (int) $request->input('month');

        $workings = Working::where('user_id', $user->id)
            ->whereYear('punch_date', $year)
            ->whereMonth('punch_date', $month)
            ->get()
            ->keyBy(fn($item) => Carbon::parse($item->punch_date)->format('Y-m-d'));

        $shifts = Shift::where('user_id', $user->id)
            ->whereYear('target_date', $year)
            ->whereMonth('target_date', $month)
            ->get();

        $shiftMasters = ShiftMaster::whereIn('id', $shifts->pluck('master_id')->filter()->unique())->get()->keyBy('id');
        $attendanceRequests = AttendanceRequest::where('user_id', $user->id)
            ->whereYear('target_date', $year)
            ->whereMonth('target_date', $month)
            ->where('request_type', '!=', self::SUBMISSION_REQUEST_TYPE)
            ->get()
            ->groupBy(fn($item) => Carbon::parse($item->target_date)->format('Y-m-d'));

        $hasUncorrectedLate = false;
        $hasCorrectedLate = false;

        foreach ($shifts as $shift) {
            $dateKey = Carbon::parse($shift->target_date)->format('Y-m-d');
            $working = $workings->get($dateKey);
            $master = $shiftMasters->get($shift->master_id);

            if ($working && $working->attendance && $master && $master->attendance) {
                $actualAtt = Carbon::parse($working->attendance);
                $scheduledAtt = Carbon::parse($master->attendance);

                if ($actualAtt->gt($scheduledAtt)) {
                    if (!$attendanceRequests->has($dateKey)) {
                        $hasUncorrectedLate = true;
                    } else {
                        $hasCorrectedLate = true;
                    }
                }
            }
        }

        return response()->json([
            'has_uncorrected_late' => $hasUncorrectedLate,
            'has_corrected_late'   => $hasCorrectedLate,
        ]);
    }

    public function submit(Request $request)
    {
        $user  = $this->currentUser();
        $year  = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $lastWorkingDate = $this->resolveLastWorkingDate($user, $year, $month);
        if (!$lastWorkingDate) {
            return response()->json(['success' => false, 'message' => '対象月にシフトが登録されていません。'], 422);
        }

        $lastWorking = Working::where('user_id', $user->id)
            ->whereDate('punch_date', $lastWorkingDate->format('Y-m-d'))
            ->first();

        if (!$lastWorking || !$lastWorking->leaving) {
            return response()->json(['success' => false, 'message' => 'まだ最終出勤日の退勤打刻が完了していません。'], 422);
        }

        // 下部の新設メソッドで実処理を走らせる
        $this->submitMonthlyRequest($user->id, $year, $month);

        return response()->json([
            'success' => true,
            'message' => '勤怠を申請しました。'
        ]);
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

        return response()->json([
            'success' => true,
            'message' => $year . '年' . $month . '月の勤怠申請を取り下げました。'
        ]);
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

    /**
     * 【追加】指定された年月の祝日マップを生成する（現状は空配列、必要に応じてDBやライブラリと連動）
     */
    private function generateMonthlyHolidayMap(int $year, int $month): array
    {
        // 独自の祝日テーブル(Holidaysなど)がある場合はここでクエリを取得してください
        // 例: return \App\Models\Holiday::whereYear('date', $year)->whereMonth('date', $month)->pluck('name', 'date')->toArray();
        return [];
    }

    /**
     * 【追加】月次申請データを保存・更新する実処理
     */
    private function submitMonthlyRequest(int $userId, int $year, int $month): void
    {
        $targetDate = Carbon::create($year, $month, 1)->format('Y-m-d');

        AttendanceRequest::updateOrCreate(
            [
                'user_id' => $userId,
                'request_type' => self::SUBMISSION_REQUEST_TYPE,
                'target_date' => $targetDate,
            ],
            [
                'memo' => '申請済み',
            ]
        );
    }
}