<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\User;
use App\Models\Working;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return view('reports.index', [
            'isAdmin' => (bool) ($user?->isAdmin()),
        ]);
    }

    public function attendance(Request $request)
    {
        /** @var \App\Models\User|null $viewer */
        $viewer = Auth::user();

        $date = $request->filled('date')
            ? Carbon::parse($request->input('date'))
            : now();

        $dept = $request->input('dept', $viewer?->dept);

        $depts = User::whereNotNull('dept')
            ->where('dept', '!=', '')
            ->distinct()
            ->orderBy('dept')
            ->pluck('dept');

        $keyword = $request->input('keyword');

        $usersQuery = User::where('dept', $dept);

        if ($keyword) {
            $usersQuery->where('name', 'like', "%{$keyword}%");
        }

        $users = $usersQuery->orderBy('name')->get();
        $userIds = $users->pluck('id');

        $shifts = Shift::with('shiftMaster')
            ->whereIn('user_id', $userIds)
            ->where('target_date', $date->toDateString())
            ->get()
            ->keyBy('user_id');

        $workings = Working::whereIn('user_id', $userIds)
            ->where('punch_date', $date->toDateString())
            ->get()
            ->keyBy('user_id');

        $rows = $users->map(function ($user) use ($shifts, $workings, $date) {
            $shift = $shifts->get($user->id);
            $working = $workings->get($user->id);

            if ($shift) {
                $scheduledAttendance = $shift->attendance_edit ?? $shift->shiftMaster->attendance;
                $scheduledLeaving = $shift->leaving_edit ?? $shift->shiftMaster->leaving;
                $scheduled = date('H:i', strtotime($scheduledAttendance)) . ' 〜 ' . date('H:i', strtotime($scheduledLeaving));
                $place = $shift->shiftMaster->name ?? '-';
            } else {
                $scheduledAttendance = null;
                $scheduled = '-';
                $place = $working->working_place ?? '-';
            }

            return [
                'user' => $user,
                'status' => $this->resolveAttendanceStatus($shift, $working, $date, $scheduledAttendance),
                'scheduled' => $scheduled,
                'place' => $place,
                'attendance_at' => $working?->attendance
                    ? $date->format('Y-m-d') . ' ' . date('H:i', strtotime($working->attendance))
                    : null,
                'leaving_at' => $working?->leaving
                    ? $date->format('Y-m-d') . ' ' . date('H:i', strtotime($working->leaving))
                    : null,
            ];
        });

        return view('reports.attendance', [
            'date' => $date,
            'dept' => $dept,
            'depts' => $depts,
            'keyword' => $keyword,
            'rows' => $rows,
        ]);
    }

    // 💡 出勤ステータスを「出勤日時・退勤日時の有無」から判定する。
    //    working.status カラムの値には依存しない。
    private function resolveAttendanceStatus($shift, ?Working $working, Carbon $date, ?string $scheduledAttendance): string
    {
        if (!$shift) {
            return '休み';
        }

        if (!$working || !$working->attendance) {
            return ($date->isPast() && !$date->isToday()) ? '欠勤' : '-';
        }

        $isLate = $scheduledAttendance
            && strtotime($working->attendance) > strtotime($scheduledAttendance);

        return $isLate ? '遅刻' : '出勤';
    }
}