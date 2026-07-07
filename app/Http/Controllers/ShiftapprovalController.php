<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\ShiftSubmission;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShiftApprovalController extends Controller
{
    // 💡 管理者判定。専用ミドルウェアは作らず、各メソッドで簡易チェックする方針。
    private function ensureAdmin(): void
    {
        abort_unless((bool) (Auth::user()?->isAdmin()), 403);
    }

    public function index(Request $request)
    {
        $this->ensureAdmin();

        // 💡 自分と同じ部署（dept）のメンバーだけを対象にする。admin=1のユーザーは対象外。
        $myDept = Auth::user()?->dept;

        $submissions = ShiftSubmission::with('user')
            ->where('status', '申請中')
            ->whereHas('user', function ($query) use ($myDept) {
                $query->where('dept', $myDept)->where('admin', false);
            })
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // 💡 各提出について、その対象月のシフト内容をポップアップ表示用にまとめておく
        $shiftsBySubmission = [];

        foreach ($submissions as $submission) {
            $startOfMonth = Carbon::create($submission->year, $submission->month, 1)->startOfMonth();
            $endOfMonth = $startOfMonth->copy()->endOfMonth();

            $shifts = Shift::with('shiftMaster')
                ->where('user_id', $submission->user_id)
                ->whereBetween('target_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                ->get()
                ->keyBy(fn ($shift) => $shift->target_date->format('Y-m-d'));

            $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

            $days = collect($period)->map(function (Carbon $date) use ($shifts) {
                $shift = $shifts->get($date->format('Y-m-d'));

                // 💡 その日のシフトが無い場合は「休み」として表示する
                if (!$shift) {
                    return [
                        'date' => $date->format('n月j日'),
                        'attendance' => '休み',
                        'leaving' => '',
                        'place' => '',
                    ];
                }

                // 💡 修正後時刻(attendance_edit/leaving_edit)があればそちらを優先表示
                $attendance = $shift->attendance_edit
                    ? $shift->attendance_edit->format('H:i')
                    : ($shift->shiftMaster ? date('H:i', strtotime($shift->shiftMaster->attendance)) : '--:--');

                $leaving = $shift->leaving_edit
                    ? $shift->leaving_edit->format('H:i')
                    : ($shift->shiftMaster ? date('H:i', strtotime($shift->shiftMaster->leaving)) : '--:--');

                return [
                    'date' => $date->format('n月j日'),
                    'attendance' => $attendance,
                    'leaving' => $leaving,
                    'place' => $shift->shiftMaster->name ?? '',
                ];
            })->values();

            $shiftsBySubmission[$submission->id] = $days;
        }

        return view('shift.approvals', [
            'submissions' => $submissions,
            'shiftsBySubmission' => $shiftsBySubmission,
        ]);
    }

    public function approve(Request $request, ShiftSubmission $shiftSubmission)
    {
        $this->ensureAdmin();

        $shiftSubmission->update(['status' => '承認済み']);

        return back()->with('success', "{$shiftSubmission->user->name}さんの{$shiftSubmission->year}年{$shiftSubmission->month}月分を承認しました。");
    }

    public function withdraw(Request $request, ShiftSubmission $shiftSubmission)
    {
        $this->ensureAdmin();

        // 💡 本人が行う「提出取り下げ」と同じ処理（申請中 → 未提出）
        $shiftSubmission->update(['status' => '未提出']);

        return back()->with('success', "{$shiftSubmission->user->name}さんの{$shiftSubmission->year}年{$shiftSubmission->month}月分を未提出に戻しました。");
    }
}