<?php

namespace App\Http\Controllers;

use App\Models\WorkingCorrection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkingCorrectionApprovalController extends Controller
{
    // 💡 管理者判定。専用ミドルウェアは作らず、各メソッドで簡易チェックする方針（ShiftApprovalControllerと同じ方針）。
    private function ensureAdmin(): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        abort_unless((bool) ($user?->isAdmin()), 403);
    }

    /**
     * 勤怠申請（打刻修正）承認一覧
     */
    public function index(Request $request)
    {
        $this->ensureAdmin();

        // 💡 自分と同じ部署（dept）のメンバーだけを対象にする。admin=1のユーザーは対象外。
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $myDept = $user?->dept;

        $corrections = WorkingCorrection::with('user')
            ->where('status', '申請中')
            ->whereHas('user', function ($query) use ($myDept) {
                $query->where('dept', $myDept)->where('admin', false);
            })
            ->orderBy('target_date')
            ->orderBy('created_at')
            ->get();

        return view('reports.working-corrections', [
            'corrections' => $corrections,
        ]);
    }

    /**
     * 承認：打刻データはすでに申請時点でworkingsテーブルに反映済みのため、ステータス更新のみでよい
     */
    public function approve(Request $request, WorkingCorrection $workingCorrection)
    {
        $this->ensureAdmin();

        $workingCorrection->update([
            'status'       => '承認',
            'updater_name' => Auth::user()->name,
        ]);

        return back()->with(
            'success',
            "{$workingCorrection->user->name}さんの{$workingCorrection->target_date}分の打刻修正申請を承認しました。"
        );
    }

    /**
     * 却下：workingsテーブルを申請前の状態に戻した上で、ステータスのみ更新（履歴として残す）
     *
     * 💡 「出勤削除」等でworkingsの行自体がすでに物理削除されているケースでは、
     *    WorkingCorrection::rollbackWorkingsData() 内で before_* の内容から行を再INSERTして復元する。
     */
    public function reject(Request $request, WorkingCorrection $workingCorrection)
    {
        $this->ensureAdmin();

        if ($workingCorrection->status === '承認') {
            return back()->with('error', '承認済みの申請は却下できません。');
        }

        $workingCorrection->rollbackWorkingsData();

        $workingCorrection->update([
            'status'       => '却下',
            'updater_name' => Auth::user()->name,
        ]);

        return back()->with(
            'success',
            "{$workingCorrection->user->name}さんの{$workingCorrection->target_date}分の打刻修正申請を却下しました。"
        );
    }
}