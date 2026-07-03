<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class DashboardController extends Controller
{

    /**
     * ダッシュボード画面表示
     */
    public function index()
    {
        $userId = Auth::id();

        if (!$userId) {
            return redirect()->route('login');
        }

        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now()->format('H:i');
        $sevenDaysAgo = Carbon::today()->subDays(7)->format('Y-m-d');

        // 本日の勤怠レコードを取得（打刻ボタンの制御用）
        $todayAttendance = DB::table('workings')
            ->where('user_id', $userId)
            ->where('punch_date', $today)
            ->first();

        // 本日のシフト情報を取得
        $todayShift = DB::table('shifts')
            ->join('shift_masters', 'shifts.master_id', '=', 'shift_masters.id')
            ->where('shifts.user_id', $userId)
            ->where('shifts.target_date', $today)
            //->where('shifts.status', '承認')
            ->select(
                'shift_masters.name as master_name',
                'shift_masters.attendance',
                'shift_masters.leaving',
                'shift_masters.break_time',
                'shift_masters.break_start_time', 
                'shift_masters.working_place'
            )
            ->first();

        // 休憩の開始〜終了時間の表示用文字列を計算
        $displayBreakRange = '--:-- ～ --:--';
        if ($todayShift && !empty($todayShift->break_start_time) && !empty($todayShift->break_time)) {
            $breakStart = Carbon::parse($todayShift->break_start_time);
            $breakTime = Carbon::parse($todayShift->break_time);

            $breakEnd = $breakStart->copy()
                ->addHours($breakTime->hour)
                ->addMinutes($breakTime->minute)
                ->addSeconds($breakTime->second);

            $displayBreakRange = $breakStart->format('H:i') . ' ～ ' . $breakEnd->format('H:i');
        }

        // ユーザーが選択可能な勤務地リストをシフトマスタから取得
        $workingPlaces = DB::table('shift_masters')
            ->where('user_id', $userId)
            ->orWhereNull('user_id')
            ->pluck('working_place')
            ->unique()
            ->filter() // 空値を除外
            ->values()
            ->all();

        // 勤務地決定ロジック
        $displayWorkingPlace = '未定';
        if ($todayAttendance && !empty($todayAttendance->working_place)) {
            // 1. 実勤務地があれば最優先
            $displayWorkingPlace = $todayAttendance->working_place;
        } elseif ($todayShift && !empty($todayShift->working_place)) {
            // 2. 実勤務地がNullならシフトの予定勤務地（本社(在宅)など）
            $displayWorkingPlace = $todayShift->working_place;
        }

        // 履歴一覧を今日から7日前までに限定
        $history = DB::table('workings')
            ->where('user_id', $userId)
            ->where('punch_date', '>=', $sevenDaysAgo)
            ->orderBy('punch_date', 'desc')
            ->get();

        // ビューに渡す
        return view('dashboard/dashboard', compact(
            'now',
            'todayAttendance',
            'todayShift',
            'history',
            'displayWorkingPlace',
            'workingPlaces',
            'displayBreakRange'
        ));
    }

    /**
     * 既定の休憩を追加：状態変更非同期処理（追加箇所）
     */
    public function toggleAutoBreak(Request $request)
    {
        $userId = Auth::id();
        $today = Carbon::today()->format('Y-m-d');

        // 本日の勤怠レコードを取得
        $attendance = DB::table('workings')
            ->where('user_id', $userId)
            ->where('punch_date', $today)
            ->first();

        // 出勤中かどうかの判定（レコードがない、またはすでに退勤時刻がある場合は422エラー）
        if (!$attendance || !is_null($attendance->leaving)) {
            return response()->json([
                'success' => false,
                'message' => '「既定の休憩を追加」の設定は出勤中に限り変更可能です。'
            ], 422);
        }

        // usersテーブルの can_auto_break カラムを更新
        $user = User::find($userId);
        $user->can_auto_break = $request->input('can_auto_break') ? true : false;
        $user->save();

        return response()->json([
            'success' => true,
            'can_auto_break' => $user->can_auto_break
        ]);
    }

    /**
     * 出勤打刻
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
            'working_place' => null,
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
