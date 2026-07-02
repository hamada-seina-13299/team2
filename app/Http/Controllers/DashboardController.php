<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{

    /**
     * ダッシュボード画面表示
     */
    public function index()
    {

        // 💡 session('user_id') ではなく、Auth機能から現在ログイン中のユーザーIDを取得します
        $userId = Auth::id();

        // ログインしていない場合はログイン画面へリダイレクト
        if (!$userId) {
            return redirect()->route('login');
        }

        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now()->format('H:i');

        // 7日前までの日付を取得
        $sevenDaysAgo = Carbon::today()->subDays(7)->format('Y-m-d');

        // 本日の勤怠レコードを取得
        $todayAttendance = DB::table('workings')
            ->where('user_id', $userId)
            ->where('punch_date', $today)
            ->first();

        // 履歴一覧を今日から7日前までに限定
        $history = DB::table('workings')
            ->where('user_id', $userId)
            ->where('punch_date', '>=', $sevenDaysAgo)
            ->orderBy('punch_date', 'desc')
            ->get();

        return view('dashboard/dashboard', compact('now', 'todayAttendance', 'history'));
    }

    /**
     * 出勤打刻 (変更なし)
     */
    public function clockIn(Request $request)
    {
        $userId = Auth::id();
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now()->format('H:i:s');

        $exists = DB::table('workings')->where('user_id', $userId)->where('punch_date', $today)->exists();
        if ($exists) {
            return redirect()->back()->with('error', '本日はすでに出勤打刻済みです。');
        }

        DB::table('workings')->insert([
            'user_id' => $userId,
            'punch_date' => $today,
            'attendance' => $now,
            'leaving' => null,
            'break_time' => null,
            'working_place' => '本社',
            'commute' => 0,
            'status' => '未申請',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return redirect()->route('dashboard')->with('success', '出勤しました。');
    }

    /**
     * 退勤打刻 (変更なし)
     */
    public function clockOut(Request $request)
    {
        $userId = Auth::id();
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now()->format('H:i:s');

        $working = DB::table('workings')->where('user_id', $userId)->where('punch_date', $today)->first();
        if (!$working) {
            return redirect()->back()->with('error', '出勤データが見つかりません。');
        }
        if (!is_null($working->leaving)) {
            return redirect()->back()->with('error', 'すでに退勤打刻済みです。');
        }

        DB::table('workings')->where('id', $working->id)->update([
            'leaving' => $now,
            'updated_at' => Carbon::now(),
        ]);

        return redirect()->route('dashboard')->with('success', '退勤しました。');
    }

    /**
     * 打刻修正申請
     */
    public function updateCorrection(Request $request)
    {
        $userId = Auth::id();
        $targetDate = $request->input('target_date');

        $deleteAttendance = $request->has('delete_attendance');
        $deleteLeaving    = $request->has('delete_leaving');

        $attendanceTime = $request->input('attendance_time');
        $attendance = (!empty($attendanceTime) && !$deleteAttendance) ? Carbon::parse($attendanceTime)->format('H:i:s') : null;

        $leavingTime = $request->input('leaving_time');
        $leaving = (!empty($leavingTime) && !$deleteLeaving) ? Carbon::parse($leavingTime)->format('H:i:s') : null;

        $workingPlace = $request->input('working_place', '本社');

        $existingRecord = DB::table('workings')
            ->where('user_id', $userId)
            ->where('punch_date', $targetDate)
            ->first();

        // 両方削除、またはデータが空なら物理削除
        if (($deleteAttendance && $deleteLeaving) || (is_null($attendance) && is_null($leaving))) {
            if ($existingRecord) {
                DB::table('workings')->where('id', $existingRecord->id)->delete();
            }
            return redirect()->route('dashboard')->with('success', '打刻情報を削除しました。');
        }

        if ($existingRecord) {
            DB::table('workings')
                ->where('id', $existingRecord->id)
                ->update([
                    'attendance'    => $attendance,
                    'leaving'       => $leaving,
                    'working_place' => $workingPlace,
                    'status'        => '承認済み',
                    'updated_at'    => Carbon::now(),
                ]);
        } else {
            DB::table('workings')->insert([
                'user_id'       => $userId,
                'punch_date'    => $targetDate,
                'attendance'    => $attendance,
                'leaving'       => $leaving,
                'break_time'    => null,
                'working_place' => $workingPlace,
                'commute'       => 0,
                'status'        => '承認済み',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ]);
        }

        return redirect()->route('dashboard')->with('success', '打刻情報を修正しました。');
    }
}
