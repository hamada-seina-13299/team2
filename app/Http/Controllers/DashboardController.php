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

        // 「出勤していて退勤していない最新のレコード」を取得（夜勤対応用）
        $latestOpenAttendance = DB::table('workings')
            ->where('user_id', $userId)
            ->whereNotNull('attendance')
            ->whereNull('leaving')
            ->orderBy('punch_date', 'desc')
            ->orderBy('attendance', 'desc')
            ->first();

        // 本日の勤怠レコードを取得（打刻ボタン以外の既存ロジック互換用）
        $todayAttendance = DB::table('workings')
            ->where('user_id', $userId)
            ->where('punch_date', $today)
            ->first();

        // 本日のシフト情報を取得
        $todayShift = DB::table('shifts')
            ->join('shift_masters', 'shifts.master_id', '=', 'shift_masters.id')
            ->where('shifts.user_id', $userId)
            ->where('shifts.target_date', $today)
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
            ->filter()
            ->values()
            ->all();

        // 勤務地決定ロジック
        $displayWorkingPlace = '未定';
        if ($todayAttendance && !empty($todayAttendance->working_place)) {
            $displayWorkingPlace = $todayAttendance->working_place;
        } elseif ($todayShift && !empty($todayShift->working_place)) {
            $displayWorkingPlace = $todayShift->working_place;
        }

        // 休憩終了時刻を計算してプロパティとして追加
        if ($todayAttendance) {
            $todayAttendance->break_out = '';
            if (!empty($todayAttendance->break_time) && $todayShift && !empty($todayShift->break_time)) {
                $bStart = Carbon::parse($todayAttendance->break_time);
                $bLength = Carbon::parse($todayShift->break_time);
                $todayAttendance->break_out = $bStart->copy()->addHours($bLength->hour)->addMinutes($bLength->minute)->format('H:i');
            }
        }

        // 履歴一覧を今日から7日前までに限定
        $historyData = DB::table('workings')
            ->where('user_id', $userId)
            ->where('punch_date', '>=', $sevenDaysAgo)
            ->orderBy('punch_date', 'desc')
            ->get();

        //コレクションの各レコードに break_out プロパティを動的に追加
        $history = $historyData->map(function ($record) use ($todayShift) {
            $record->break_out = ''; // 初期化

            if (!empty($record->break_time) && $todayShift && !empty($todayShift->break_time)) {
                $bStart = Carbon::parse($record->break_time);
                $bLength = Carbon::parse($todayShift->break_time);
                // 休憩開始にシフトの休憩時間を足してセット
                $record->break_out = $bStart->copy()->addHours($bLength->hour)->addMinutes($bLength->minute)->format('H:i');
            }

            return $record;
        });

        // 期間を絞らず、このユーザーが持っている全ての「打刻日」の重複なきリストを取得
        $allWorkingDates = DB::table('workings')
            ->where('user_id', $userId)
            ->orderBy('punch_date', 'desc')
            ->pluck('punch_date')
            ->unique()
            ->values()
            ->all();

        //  過去すべての打刻データをJSに渡すために取得（軽量化のため必要なカラムのみ）
        $allHistoryRaw = DB::table('workings')
            ->where('user_id', $userId)
            ->orderBy('punch_date', 'desc')
            ->get();

        $allHistoryData = $allHistoryRaw->map(function ($record) use ($todayShift) {

            // 1. まずはDBに直接保存されている『確定済みの休憩終了時刻』を最優先で取得
            $breakEndTime = !empty($record->break_end_time) ? Carbon::parse($record->break_end_time)->format('H:i') : '';

            // 2. もしDBが空で、休憩開始時刻とシフトデータがある場合のみ、動的に計算（バックアップロジック）
            if (empty($breakEndTime) && !empty($record->break_time) && $todayShift && !empty($todayShift->break_time)) {
                $bStart = Carbon::parse($record->break_time);
                $bLength = Carbon::parse($todayShift->break_time);
                $breakEndTime = $bStart->copy()->addHours($bLength->hour)->addMinutes($bLength->minute)->format('H:i');
            }

            return [
                'punch_date' => $record->punch_date,
                'attendance' => $record->attendance ? Carbon::parse($record->attendance)->format('H:i') : '',
                'leaving' => $record->leaving ? Carbon::parse($record->leaving)->format('H:i') : '',
                'break_time' => $record->break_time ? Carbon::parse($record->break_time)->format('H:i') : '',
                'break_end_time' => $breakEndTime,
                'working_place' => $record->working_place ?? '',
            ];
        })->keyBy('punch_date'); // 日付をキーにする

        $allHistoryJson = json_encode($allHistoryData);

        $correctionHistory = DB::table('working_corrections')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        // ビューに渡す
        return view('dashboard/dashboard', compact(
            'now',
            'todayAttendance',
            'latestOpenAttendance',
            'todayShift',
            'history',
            'displayWorkingPlace',
            'workingPlaces',
            'displayBreakRange',
            'allWorkingDates',
            'allHistoryJson',
            'correctionHistory'
        ));
    }

    /**
     * 既定の休憩を追加：状態変更非同期処理
     */
    public function toggleAutoBreak(Request $request)
    {
        $userId = Auth::id();

        // 夜勤考慮のため、最新の未退勤レコードを基準に判定
        $attendance = DB::table('workings')
            ->where('user_id', $userId)
            ->whereNotNull('attendance')
            ->whereNull('leaving')
            ->orderBy('punch_date', 'desc')
            ->first();

        // 出勤中かどうかの判定（未退勤レコードがない場合は422エラー）
        if (!$attendance) {
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

        // 未退勤のデータが残っている場合は出勤させない（重複防止）
        $hasOpenAttendance = DB::table('workings')
            ->where('user_id', $userId)
            ->whereNotNull('attendance')
            ->whereNull('leaving')
            ->exists();

        if ($hasOpenAttendance) {
            return redirect()->back()->with('error', '未退勤の勤務データがあります。先に退勤を行ってください。');
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
        $now = Carbon::now()->format('H:i:s');

        // 「本日」ではなく「最新の未退勤レコード」を取得（夜勤対応）
        $working = DB::table('workings')
            ->where('user_id', $userId)
            ->whereNotNull('attendance')
            ->whereNull('leaving')
            ->orderBy('punch_date', 'desc')
            ->orderBy('attendance', 'desc')
            ->first();

        if (!$working) {
            return redirect()->back()->with('error', '出勤データが見つかりません。');
        }

        //  ユーザーの「既定の休憩を追加」設定(can_auto_break)を取得
        $user = User::find($userId);
        $canAutoBreak = $user ? $user->can_auto_break : false;

        // 更新データのベース（退勤時刻）
        $updateData = [
            'leaving' => $now,
            'updated_at' => Carbon::now(),
        ];

        // 休憩開始時刻(break_time)が入っていない（Null）場合の判定
        $hasNoBreakNotice = false; // 休憩なしでお知らせを出すかどうかのフラグ

        if (is_null($working->break_time)) {
            if ($canAutoBreak) {
                $shift = DB::table('shifts')
                    ->join('shift_masters', 'shifts.master_id', '=', 'shift_masters.id')
                    ->where('shifts.user_id', $userId)
                    ->where('shifts.target_date', $working->punch_date)
                    ->select('shift_masters.break_start_time', 'shift_masters.break_time as break_length')
                    ->first();

                if ($shift && !empty($shift->break_start_time)) {
                    $updateData['break_time'] = $shift->break_start_time;

                    // 休憩終了時刻を物理計算して保存
                    if (!empty($shift->break_length)) {
                        $bStart = Carbon::parse($shift->break_start_time);
                        $bLength = Carbon::parse($shift->break_length);
                        $updateData['break_end_time'] = $bStart->copy()->addHours($bLength->hour)->addMinutes($bLength->minute)->format('H:i:s');
                    }
                }
            } else {
                //エラーで弾くのではなく、退勤を許可しつつ、お知らせを出すフラグを立てる
                $hasNoBreakNotice = true;
            }
        }

        // workingsテーブルを更新して退勤完了
        DB::table('workings')->where('id', $working->id)->update($updateData);

        // 休憩なしフラグが立っている場合は、うす黄色アラート用のwarningメッセージを渡してリダイレクト
        if ($hasNoBreakNotice) {
            return redirect()->route('dashboard')->with('warning', '休憩なしの退勤となりました。');
        }

        return redirect()->route('dashboard')->with('success', '退勤しました。');
    }

    /**
     *  休憩開始打刻
     */
    public function breakIn(Request $request)
    {
        $userId = Auth::id();
        $now = Carbon::now()->format('H:i:s');

        //  夜勤考慮のため、最新の未退勤レコードを対象にする
        $working = DB::table('workings')
            ->where('user_id', $userId)
            ->whereNotNull('attendance')
            ->whereNull('leaving')
            ->orderBy('punch_date', 'desc')
            ->first();

        if (!$working) {
            return redirect()->back()->with('error', '出勤データが見つからないため、休憩を開始できません。');
        }
        if (!is_null($working->break_time)) {
            return redirect()->back()->with('error', 'すでに休憩開始打刻済みです。');
        }

        DB::table('workings')->where('id', $working->id)->update([
            'break_time' => $now,
            'updated_at' => Carbon::now(),
        ]);

        return redirect()->route('dashboard')->with('success', '休憩を開始しました。');
    }
    
    /**
     * ⚠️ 未使用: 勤怠申請の実際の保存処理は AttendanceController@store（route: attendance.store）が行っています。
     * このメソッドはルーティングされていないため実行されません。
     */
    public function store(Request $request)
    {
        return redirect()->back()->with('success', '勤怠申請を送信しました。');
    }
}