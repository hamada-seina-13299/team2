<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\Shift_master;
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

        // 対象日（指定がなければ明日）
        $targetDate = $request->input('target_date', now()->addDay()->format('Y-m-d'));

        // 今日以前の日付が指定された場合は明日にリダイレクト（未来の予定修正に特化）
        if ($targetDate <= now()->format('Y-m-d')) {
            return redirect()
                ->route('shiftcorrection.index', ['target_date' => now()->addDay()->format('Y-m-d')])
                ->with('error', 'シフト修正申請は明日以降の日付のみ可能です。');
        }

        // 対象日にすでに登録されている最新のシフト（変更前の予定）を取得
        $currentShift = Shift::where('user_id', $userId)
            ->whereDate('target_date', $targetDate)
            ->orderByDesc('created_at')
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
            'targetDate'     => $targetDate,
            'shiftMasters'   => $shiftMasters,
            'shiftMasterMap' => $shiftMasters->pluck('name', 'id'), // id => name の連想配列
            'shifts'         => $shifts,
            'currentShift'   => $currentShift, // 変更前の予定表示用
        ]);
    }

    /**
     * シフト修正申請を登録
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'target_date'     => ['required', 'date', 'after:today'],
            'master_id'       => ['required', 'exists:shift_masters,id'],
            // 秒数が混ざってもエラーにならないよう、規則を緩めるのとフォーマットを外す
            'attendance_edit' => ['required'], 
            'leaving_edit'    => ['required'],
            'memo'            => ['required', 'string', 'max:255'],
        ], [
            'target_date.required'     => '対象日を入力してください。',
            'target_date.after'        => '修正申請は明日以降の日付のみ可能です。',
            'master_id.required'       => 'シフトパターンを選択してください。',
            'master_id.exists'         => '選択されたシフトパターンが存在しません。',
            'attendance_edit.required' => '修正後の出勤時刻を入力してください。',
            'leaving_edit.required'    => '修正後の退勤時刻を入力してください。',
            'memo.required'            => '修正理由（メモ）を入力してください。',
            'memo.max'                 => 'メモは255文字以内で入力してください。',
        ]);

        Shift::create([
            'user_id'         => 1,
            'master_id'       => $validated['master_id'],
            'memo'            => $validated['memo'],
            'attendance_edit' => $validated['attendance_edit'],
            'leaving_edit'    => $validated['leaving_edit'],
            'target_date'     => $validated['target_date'],
            'status'          => '申請中',
        ]);

        return redirect()
            ->route('shiftcorrection.index', ['target_date' => $validated['target_date']])
            ->with('success', 'シフト修正申請を送信しました。承認をお待ちください。');
    }

    /**
     * シフト修正申請の取り消し
     */
    public function destroy(Shift $shift)
    {
        abort_unless($shift->user_id === 1, 403);

        // ここも「申請中」のときだけ消せるように揃えます
        if ($shift->status !== '申請中' && $shift->status !== 'pending') {
            return back()->with('error', '承認・却下済みの申請は取り消せません。');
        }

        $shift->delete();

        return back()->with('success', '修正申請を取り消しました。');
    }
}