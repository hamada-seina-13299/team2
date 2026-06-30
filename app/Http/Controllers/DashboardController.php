<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // あるいは該当するModel（例: Attendance）
use Carbon\Carbon;

class DashboardController extends Controller
{
    // 開発中の暫定ユーザーID
    private $mockUserId = 1;

    /**
     * ダッシュボード画面表示
     */
    public function index()
    {
        $userId = $this->mockUserId; // 本番時は Auth::id() に差し替え
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now()->format('H:i');

        // 1. 本日の勤怠レコードを取得（ボタンの活性・非活性判定用）
        $todayAttendance = DB::table('workings') // テーブル名は実際の名称に合わせてください
            ->where('user_id', $userId)
            ->where('punch_date', $today)
            ->first();

        // 2. 履歴一覧を取得
        $history = DB::table('workings')
            ->where('user_id', $userId)
            ->orderBy('punch_date', 'desc')
            ->get();

        // 3. ビューにデータを渡す
        return view('dashboard.dashboard', compact('now', 'todayAttendance', 'history'));
    }

    /**
     * 出勤打刻
     */
    public function clockIn(Request $request)
    {
        $userId = $this->mockUserId;
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now()->format('H:i:s');

        // 二重打刻防止のバックエンド側バリデーション
        $exists = DB::table('workings')
            ->where('user_id', $userId)
            ->where('punch_date', $today)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', '本日はすでに出勤打刻済みです。');
        }

        // データの新規作成
        DB::table('workings')->insert([
            'user_id' => $userId,
            'punch_date' => $today,
            'attendance' => $now,
            'leaving' => null,
            'break_time' => null,
            'working_place' => '本社', // シフト連動ができるまでは固定値などで対応
            'commute' => 0,
            'status' => '未申請',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return redirect()->route('dashboard')->with('success', '出勤しました。');
    }

    /**
     * 退勤打刻
     */
    public function clockOut(Request $request)
    {
        $userId = $this->mockUserId;
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now()->format('H:i:s');

        // 本日の出勤レコードがあるか確認
        $working = DB::table('workings')
            ->where('user_id', $userId)
            ->where('punch_date', $today)
            ->first();

        // 出勤レコードがない、またはすでに退勤済みの場合はエラー
        if (!$working) {
            return redirect()->back()->with('error', '出勤データが見つかりません。');
        }
        if (!is_null($working->leaving)) {
            return redirect()->back()->with('error', 'すでに退勤打刻済みです。');
        }

        // 退勤時刻を更新
        DB::table('workings')
            ->where('id', $working->id)
            ->update([
                'leaving' => $now,
                'updated_at' => Carbon::now(),
            ]);

        return redirect()->route('dashboard')->with('success', '退勤しました。');
    }
}