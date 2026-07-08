<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\ShiftSubmission;
use App\Models\User;
use App\Models\Working;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    // 💡 サイドバーの「集計レポート」リンクの遷移先
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $isAdmin = (bool) ($user?->isAdmin());

        // 💡 未承認（申請中）件数：シフト承認画面と同じ「同じ部署・admin=false」の条件で数える
        $pendingApprovalsCount = 0;

        if ($isAdmin) {
            $pendingApprovalsCount = ShiftSubmission::where('status', '申請中')
                ->whereHas('user', function ($query) use ($user) {
                    $query->where('dept', $user->dept)->where('admin', false);
                })
                ->count();
        }

        return view('reports.index', [
            'isAdmin' => $isAdmin,
            'pendingApprovalsCount' => $pendingApprovalsCount,
        ]);
    }

    // 💡 「出勤・打刻データ」レポート。対象日×部門で全従業員の出勤状況を一覧表示する。
    public function attendance(Request $request)
    {
        /** @var \App\Models\User|null $viewer */
        $viewer = Auth::user();

        $date = $request->filled('date')
            ? Carbon::parse($request->input('date'))
            : now();

        // 💡 部門未指定時は自分の部署をデフォルトにする
        $dept = $request->input('dept', $viewer?->dept);

        // 💡 プルダウン用：重複を除いた部署名一覧
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

        // その日のシフト予定
        $shifts = Shift::with('shiftMaster')
            ->whereIn('user_id', $userIds)
            ->where('target_date', $date->toDateString())
            ->get()
            ->keyBy('user_id');

        // その日の実績打刻
        $workings = Working::whereIn('user_id', $userIds)
            ->where('punch_date', $date->toDateString())
            ->get()
            ->keyBy('user_id');

        $rows = $users->map(function ($user) use ($shifts, $workings, $date) {
            $shift = $shifts->get($user->id);
            $working = $workings->get($user->id);

            // 予定時刻・勤務地（シフトから）
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

        // 💡 出勤者数：ステータスが「出勤」または「遅刻」の人数（＝実際に打刻して出勤している人数）
        $attendingCount = $rows->whereIn('status', ['出勤', '遅刻'])->count();

        // 💡 対象人数：シフトが入っている（＝「休み」ではない）人数のみをカウントする。
        //    修正前は $rows->count() で部署の全員をそのまま対象人数にしていたため、
        //    その日シフトが無い（＝休み）人まで対象に含まれてしまっていた。
        $totalCount = $rows->where('status', '!=', '休み')->count();

        return view('reports.attendance', [
            'date' => $date,
            'dept' => $dept,
            'depts' => $depts,
            'keyword' => $keyword,
            'rows' => $rows,
            'attendingCount' => $attendingCount,
            'totalCount' => $totalCount,
        ]);
    }

    // 💡 出勤ステータスを「出勤日時・退勤日時の有無」から判定する（working.statusカラムには依存しない）
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