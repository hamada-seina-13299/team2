<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\Shift_master;
use App\Models\Working;
use Illuminate\Http\Request;

class AttendanceCorrectionController extends Controller
{
    /**
     * 勤怠修正画面を表示
     */
    public function index(Request $request)
    {
        // ▼動作確認用: ログイン機能が整うまでユーザーIDを1に固定
        $userId = 1;

        // 対象日（指定がなければ当日。ただし未来の日は選べない）
        $targetDate = $request->input('target_date', now()->format('Y-m-d'));

        // 明日以降の未来の日付が指定された場合は当日にリダイレクト
        if ($targetDate > now()->format('Y-m-d')) {
            return redirect()
                ->route('attendancecorrection.index', ['target_date' => now()->format('Y-m-d')])
                ->with('error', '勤怠修正申請は今日以前の過去の日付のみ可能です。');
        }

        // 対象日の現在の打刻実績（実際の出勤・退勤時刻など）
        $working = Working::where('user_id', $userId)
            ->whereDate('punch_date', $targetDate)
            ->first();

        // 選択可能なシフトマスタ（修正後のパターンの参考に表示用）
        $shiftMasters = Shift_master::whereNull('user_id')
            ->orWhere('user_id', $userId)
            ->orderBy('name')
            ->get();

        // 自分が提出した「勤怠修正申請」の履歴（Shiftsテーブルを流用、または相方の設計に合わせる）
        // ※今回は一旦Shiftモデルを流用していますが、チームで「勤怠修正用テーブル」を作る場合はここを差し替えてください
        $corrections = Shift::where('user_id', $userId)
            ->where('status', 'like', '%申請%') // 勤怠修正のレコードを識別するための条件など
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return view('attendancecorrection', [
            'targetDate'     => $targetDate,
            'working'        => $working,
            'shiftMasters'   => $shiftMasters,
            'shiftMasterMap' => $shiftMasters->pluck('name', 'id'),
            'corrections'    => $corrections,
        ]);
    }

    /**
     * 勤怠修正申請を登録
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'target_date'     => ['required', 'date', 'before_or_equal:today'],
            'master_id'       => ['required', 'exists:shift_masters,id'],
            'attendance_edit' => ['required'], 
            'leaving_edit'    => ['required'],
            'memo'            => ['required', 'string', 'max:255'],
        ], [
            'target_date.required'     => '対象日を入力してください。',
            'target_date.before_or_equal' => '勤怠修正申請は今日以前の日付のみ可能です。',
            'master_id.required'       => 'シフトパターンを選択してください。',
            'master_id.exists'         => '選択されたシフトパターンが存在しません。',
            'attendance_edit.required' => '修正後の出勤時刻を入力してください。',
            'leaving_edit.required'    => '修正後の退勤時刻を入力してください。',
            'memo.required'            => '修正理由（メモ）を入力してください。',
            'memo.max'                 => 'メモは255文字以内で入力してください。',
        ]);

        // データベースへ保存（ステータスはチームの日本語仕様に合わせて「申請中」）
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
            ->route('attendancecorrection.index', ['target_date' => $validated['target_date']])
            ->with('success', '勤怠修正申請を送信しました。承認をお待ちください。');
    }

    /**
     * 勤怠修正申請の取り消し
     */
    public function destroy(Shift $shift)
    {
        abort_unless($shift->user_id === 1, 403);

        if ($shift->status !== '申請中') {
            return back()->with('error', '承認・却下済みの申請は取り消せません。');
        }

        $shift->delete();

        return back()->with('success', '勤怠修正申請を取り消しました。');
    }
}