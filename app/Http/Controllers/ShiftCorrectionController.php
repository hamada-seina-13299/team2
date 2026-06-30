<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\Shift_master;
use App\Models\Working;
use Illuminate\Http\Request;

class ShiftCorrectionController extends Controller
{
    /**
     * シフト修正画面を表示
     */
    public function index(Request $request)
    {
        // ▼動作確認用: ログイン機能が整うまでユーザーIDを1に固定
        $userId = 1;

        // 対象日（指定がなければ今日）
        $targetDate = $request->input('target_date', now()->format('Y-m-d'));

        // 対象日の打刻実績（実際の出勤・退勤時刻）
        $working = Working::where('user_id', $userId)
            ->whereDate('punch_date', $targetDate)
            ->first();

        // 選択可能なシフトマスタ（全社共通 + 自分専用）
        $shiftMasters = Shift_master::whereNull('user_id')
            ->orWhere('user_id', $userId)
            ->orderBy('name')
            ->get();

        // 自分が提出したシフト修正申請の履歴
        $shifts = Shift::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return view('shiftcorrection', [
            'working'      => $working,
            'targetDate'   => $targetDate,
            'shiftMasters' => $shiftMasters,
            'shifts'       => $shifts,
        ]);
    }

    /**
     * シフト修正申請を登録
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'target_date'     => ['required', 'date'],
            'master_id'       => ['required', 'exists:shift_masters,id'],
            'attendance_edit' => ['required', 'date_format:H:i'],
            'leaving_edit'    => ['required', 'date_format:H:i', 'after:attendance_edit'],
            'memo'            => ['required', 'string', 'max:255'],
        ], [
            'target_date.required'     => '対象日を入力してください。',
            'master_id.required'       => 'シフトパターンを選択してください。',
            'master_id.exists'         => '選択されたシフトパターンが存在しません。',
            'attendance_edit.required' => '修正後の出勤時刻を入力してください。',
            'leaving_edit.required'    => '修正後の退勤時刻を入力してください。',
            'leaving_edit.after'       => '退勤時刻は出勤時刻より後の時間を指定してください。',
            'memo.required'            => '修正理由（メモ）を入力してください。',
            'memo.max'                 => 'メモは255文字以内で入力してください。',
        ]);

        Shift::create([
            'user_id'         => 1, // ▼動作確認用: ユーザーIDを1に固定
            'master_id'       => $validated['master_id'],
            'memo'            => $validated['memo'],
            'attendance_edit' => $validated['attendance_edit'],
            'leaving_edit'    => $validated['leaving_edit'],
            'target_date'     => $validated['target_date'],
            'status'          => 'pending',
        ]);

        return redirect()
            ->route('shiftcorrection.index', ['target_date' => $validated['target_date']])
            ->with('success', 'シフト修正申請を送信しました。承認をお待ちください。');
    }

    /**
     * シフト修正申請の取り消し（pending状態のみ可能）
     */
    public function destroy(Shift $shift)
    {
        abort_unless($shift->user_id === 1, 403); // ▼動作確認用: ユーザーIDを1に固定

        if ($shift->status !== 'pending') {
            return back()->with('error', '承認・却下済みの申請は取り消せません。');
        }

        $shift->delete();

        return back()->with('success', '修正申請を取り消しました。');
    }
}
