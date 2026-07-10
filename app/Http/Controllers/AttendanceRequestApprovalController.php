<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceRequestApprovalController extends Controller
{
    // 💡 「月次申請」は AttendanceController の月次提出フラグ用の特殊レコードなので、
    //    ここでの承認対象（個別の遅刻・早退・欠勤・有給・半休・残業等）からは除外する。
    private const SUBMISSION_REQUEST_TYPE = '月次申請';

    // 💡 管理者判定。WorkingCorrectionApprovalController と同じ方針（専用ミドルウェアは作らず、各メソッドで簡易チェック）。
    private function ensureAdmin(): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        abort_unless((bool) ($user?->isAdmin()), 403);
    }

    /**
     * 勤怠申請（遅刻・早退・欠勤・有給・半休・残業・有事遅刻・有事早退）承認一覧
     */
    public function index(Request $request)
    {
        $this->ensureAdmin();

        // 💡 自分と同じ部署（dept）のメンバーだけを対象にする。admin=1のユーザーは対象外。
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $myDept = $user?->dept;

        $requests = AttendanceRequest::with('user')
            ->where('status', '申請中')
            ->where('request_type', '!=', self::SUBMISSION_REQUEST_TYPE)
            ->whereHas('user', function ($query) use ($myDept) {
                $query->where('dept', $myDept)->where('admin', false);
            })
            ->orderBy('target_date')
            ->orderBy('created_at')
            ->get();

        return view('reports.attendance-requests', [
            'requests' => $requests,
        ]);
    }

    /**
     * 承認：ステータス更新のみ（workingsテーブルへの反映はこの申請フローでは行っていないため不要）
     */
    public function approve(Request $request, AttendanceRequest $attendanceRequest)
    {
        $this->ensureAdmin();

        $attendanceRequest->update([
            'status'       => '承認',
            'updater_name' => Auth::user()->name,
        ]);

        $message = "{$attendanceRequest->user->name}さんの{$attendanceRequest->target_date}分の勤怠申請を承認しました。";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    /**
     * 却下：ステータスを「却下」に更新し、履歴として残す（working_correctionsと同じ方針）
     * 💡 打刻修正申請と違い、この申請はworkingsテーブルを直接書き換えていないため、
     *    却下時のロールバック処理（rollbackWorkingsDataのようなもの）は不要。
     */
    public function reject(Request $request, AttendanceRequest $attendanceRequest)
    {
        $this->ensureAdmin();

        if ($attendanceRequest->status === '承認') {
            $message = '承認済みの申請は却下できません。';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $attendanceRequest->update([
            'status'       => '却下',
            'updater_name' => Auth::user()->name,
        ]);

        $message = "{$attendanceRequest->user->name}さんの{$attendanceRequest->target_date}分の勤怠申請を却下しました。";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    /**
     * 💡 スワイプUIには確認ダイアログが無いため、誤操作からのリカバリー用に
     *    直前の承認/却下を「申請中」に戻す取り消し操作を用意しておく。
     *    workingsテーブル等への波及がない申請フローなので、単純にstatusを戻すだけでよい。
     */
    public function undo(Request $request, AttendanceRequest $attendanceRequest)
    {
        $this->ensureAdmin();

        $attendanceRequest->update([
            'status'       => '申請中',
            'updater_name' => null,
        ]);

        $message = "{$attendanceRequest->user->name}さんの{$attendanceRequest->target_date}分の勤怠申請の処理を取り消しました。";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }
}