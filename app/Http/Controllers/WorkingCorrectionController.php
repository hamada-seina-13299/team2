<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\WorkingCorrection;

class WorkingCorrectionController extends Controller
{
    public function updateCorrection(Request $request)
    {
        $userId = Auth::id();
        $targetDate = $request->input('target_date');

        // 1. 既存レコード（修正前の状態）とシフト（予定勤務地）の取得
        $existingRecord = DB::table('workings')->where('user_id', $userId)->where('punch_date', $targetDate)->first();
        $shiftData = DB::table('shifts')
            ->join('shift_masters', 'shifts.master_id', '=', 'shift_masters.id')
            ->where('shifts.user_id', $userId)
            ->where('shifts.target_date', $targetDate)
            ->select('shift_masters.working_place', 'shift_masters.break_time as shift_break_time')
            ->first();

        $plannedPlace = $shiftData ? $shiftData->working_place : '未定';

        // 修正前の見かけ上の勤務地（実勤務地があれば最優先、なければ予定勤務地）
        $beforeWorkingPlace = ($existingRecord && !is_null($existingRecord->working_place))
            ? $existingRecord->working_place
            : $plannedPlace;

        $beforeAttendance   = $existingRecord ? $existingRecord->attendance : null;
        $beforeLeaving      = $existingRecord ? $existingRecord->leaving : null;
        $beforeBreakTime    = $existingRecord ? $existingRecord->break_time : null;
        $beforeBreakEndTime = $existingRecord ? $existingRecord->break_end_time : null;

        // 2. 固定行（すでに存在するデータ）の入力値取得
        $attendance   = $request->filled('attendance_time')   ? Carbon::parse($request->input('attendance_time'))->format('H:i:s')   : $beforeAttendance;
        $leaving      = $request->filled('leaving_time')      ? Carbon::parse($request->input('leaving_time'))->format('H:i:s')      : $beforeLeaving;
        $breakTime    = $request->filled('break_time')        ? Carbon::parse($request->input('break_time'))->format('H:i:s')        : $beforeBreakTime;
        $breakEndTime = $request->filled('break_end_time')    ? Carbon::parse($request->input('break_end_time'))->format('H:i:s')    : $beforeBreakEndTime;

        // 3. 削除フラグの処理（チェックが入っていれば Null にする）
        if ($request->has('delete_attendance')) $attendance = null;
        if ($request->has('delete_leaving'))    $leaving = null;
        if ($request->has('delete_break_in'))   $breakTime = null;
        if ($request->has('delete_break_out'))  $breakEndTime = null;

        // 4. 動的行（追加打刻）のループ処理と「実勤務地」の判定
        $dynamicTypes  = $request->input('dynamic_type', []);
        $dynamicTimes  = $request->input('dynamic_time', []);
        $dynamicPlaces = $request->input('dynamic_working_place', []);

        $newWorkingPlace = null;
        $changeTime = null;

        // ループ内で動的行のデータをパース
        foreach ($dynamicTypes as $index => $type) {
            if ($type === '休憩開始' && !empty($dynamicTimes[$index])) {
                $breakTime = Carbon::parse($dynamicTimes[$index])->format('H:i:s');
            } elseif ($type === '休憩終了' && !empty($dynamicTimes[$index])) {
                $breakEndTime = Carbon::parse($dynamicTimes[$index])->format('H:i:s');
            } elseif ($type === '勤務地変更') {
                if (!empty($dynamicPlaces[$index])) {
                    $newWorkingPlace = $dynamicPlaces[$index];
                    $changeTime = !empty($dynamicTimes[$index]) ? Carbon::parse($dynamicTimes[$index])->format('H:i') : null;
                }
            }
        }

        // ==========================================================================
        // 動的追加で「休憩開始」が申請された場合、休憩終了もここで自動計算して確定させる
        // ==========================================================================
        if (in_array('休憩開始', $dynamicTypes) && !empty($breakTime)) {
            // シフトデータから休憩時間の長さを取得して加算
            if ($shiftData && !empty($shiftData->shift_break_time)) {
                $bStart = Carbon::parse($breakTime);
                $bLength = Carbon::parse($shiftData->shift_break_time);
                $breakEndTime = $bStart->copy()->addHours($bLength->hour)->addMinutes($bLength->minute)->format('H:i:s');
            }
        }

        // 今回動的行で勤務地の変更が指定されなかった場合は、既存の実勤務地状態を維持
        if (is_null($newWorkingPlace) && $existingRecord) {
            $newWorkingPlace = $existingRecord->working_place;
        }

        // 表示用に「修正後」の見かけの勤務地を組み立てる
        $afterWorkingPlaceDisplay = !is_null($newWorkingPlace) ? $newWorkingPlace : $plannedPlace;
        if ($changeTime && !is_null($newWorkingPlace)) {
            $afterWorkingPlaceDisplay = "{$newWorkingPlace}:{$changeTime}より";
        }

        // 5. 理由（メモ）の構築（固定行・動的行すべての理由を【】なしで集約）
        $reasons = [];
        if ($request->input('attendance_reason'))  $reasons[] = $request->input('attendance_reason');
        if ($request->input('leaving_reason'))     $reasons[] = $request->input('leaving_reason');
        if ($request->input('break_reason'))       $reasons[] = $request->input('break_reason');
        if ($request->input('break_out_reason'))   $reasons[] = $request->input('break_out_reason');

        $dynamicReasons = $request->input('dynamic_reason', []);
        foreach ($dynamicReasons as $idx => $dr) {
            if (!empty($dr)) {
                $reasons[] = $dr;
            }
        }
        $memo = implode("\n", $reasons);

        // 6. workingsテーブルへの即時反映（更新・追加、または出勤削除時の物理削除）

        if (is_null($attendance)) {
            if ($existingRecord) {
                DB::table('workings')->where('id', $existingRecord->id)->delete();
            }
        } else {

            $updateData = [
                'attendance'     => $attendance,
                'leaving'        => $leaving,
                'break_time'     => $breakTime,
                'break_end_time' => $breakEndTime, // 💡 自動計算された値がここに入ります
                'working_place'  => $newWorkingPlace,
                'updated_at'     => Carbon::now()
            ];

            if ($existingRecord) {
                DB::table('workings')->where('id', $existingRecord->id)->update($updateData);
            } else {
                $updateData['user_id'] = $userId;
                $updateData['punch_date'] = $targetDate;
                $updateData['created_at'] = Carbon::now();
                DB::table('workings')->insert($updateData);
            }
        }

        // ==========================================================================
        //秒単位の差異による誤判定を防ぐため、時分（H:i）に直して比較を行う
        // ==========================================================================
        $bAttendanceComp = $beforeAttendance ? Carbon::parse($beforeAttendance)->format('H:i') : null;
        $bLeavingComp    = $beforeLeaving    ? Carbon::parse($beforeLeaving)->format('H:i')    : null;
        $bBreakTimeComp  = $beforeBreakTime  ? Carbon::parse($beforeBreakTime)->format('H:i')  : null;
        $bBreakEndComp   = $beforeBreakEndTime ? Carbon::parse($beforeBreakEndTime)->format('H:i') : null;

        $aAttendanceComp = $attendance ? Carbon::parse($attendance)->format('H:i') : null;
        $aLeavingComp    = $leaving    ? Carbon::parse($leaving)->format('H:i')    : null;
        $aBreakTimeComp  = $breakTime  ? Carbon::parse($breakTime)->format('H:i')  : null;
        $aBreakEndComp   = $breakEndTime ? Carbon::parse($breakEndTime)->format('H:i') : null; // 💡 自動計算された値を含めて時分にパース

        // 7. 履歴保存用のモデル生成
        WorkingCorrection::create([
            'user_id'               => $userId,
            'target_date'           => $targetDate,
            'status'                => '申請中',
            'updater_name'          => Auth::user()->name,

            'before_attendance'     => $beforeAttendance,
            'after_attendance'      => ($bAttendanceComp !== $aAttendanceComp) ? $attendance : $beforeAttendance,

            'before_leaving'        => $beforeLeaving,
            'after_leaving'         => ($bLeavingComp !== $aLeavingComp) ? $leaving : $beforeLeaving,

            'before_break_time'     => $beforeBreakTime,
            'after_break_time'      => ($bBreakTimeComp !== $aBreakTimeComp) ? $breakTime : $beforeBreakTime,

            'before_break_end_time' => $beforeBreakEndTime,
            //  時分比較により、自動計算された休憩終了時刻がしっかりと履歴（アフター）に保存されます
            'after_break_end_time'  => ($bBreakEndComp !== $aBreakEndComp) ? $breakEndTime : $beforeBreakEndTime,

            'before_working_place'  => $beforeWorkingPlace,
            'after_working_place'   => $afterWorkingPlaceDisplay,
            'memo'                  => $memo,
        ]);

        return redirect()->back()->with('success', '打刻修正申請が完了しました。');
    }

    public function cancelCorrection($id)
    {
        $correction = WorkingCorrection::findOrFail($id);

        if ($correction->status === '承認') {
            return redirect()->back()->with('error', '承認済みの申請は取消できません。');
        }

        $existingRecord = DB::table('workings')
            ->where('user_id', $correction->user_id)
            ->where('punch_date', $correction->target_date)
            ->first();

        if (
            is_null($correction->before_attendance) &&
            is_null($correction->before_leaving) &&
            is_null($correction->before_break_time) &&
            is_null($correction->before_break_end_time)
        ) {

            DB::table('workings')
                ->where('user_id', $correction->user_id)
                ->where('punch_date', $correction->target_date)
                ->delete();
        } else {
            // シフトデータ(予定勤務地)の取得
            $shiftData = DB::table('shifts')
                ->join('shift_masters', 'shifts.master_id', '=', 'shift_masters.id')
                ->where('shifts.user_id', $correction->user_id)
                ->where('shifts.target_date', $correction->target_date)
                ->select('shift_masters.working_place')
                ->first();

            $plannedPlace = $shiftData ? $shiftData->working_place : '未定';

            // 動的ロールバック用配列の初期化
            $rollbackData = ['updated_at' => Carbon::now()];

            // 修正仕様：秒を切り捨てて時分(H:i)で変化があった（その申請で修正された）項目のみを特定して戻す
            $bAtt = $correction->before_attendance ? Carbon::parse($correction->before_attendance)->format('H:i') : null;
            $aAtt = $correction->after_attendance  ? Carbon::parse($correction->after_attendance)->format('H:i')  : null;
            if ($bAtt !== $aAtt) {
                $rollbackData['attendance'] = $correction->before_attendance;
            }

            $bLea = $correction->before_leaving ? Carbon::parse($correction->before_leaving)->format('H:i') : null;
            $aLea = $correction->after_leaving  ? Carbon::parse($correction->after_leaving)->format('H:i')  : null;
            if ($bLea !== $aLea) {
                $rollbackData['leaving'] = $correction->before_leaving;
            }

            $bBIn = $correction->before_break_time ? Carbon::parse($correction->before_break_time)->format('H:i') : null;
            $aBIn = $correction->after_break_time  ? Carbon::parse($correction->after_break_time)->format('H:i')  : null;
            if ($bBIn !== $aBIn) {
                $rollbackData['break_time'] = $correction->before_break_time;
            }

            $bBOut = $correction->before_break_end_time ? Carbon::parse($correction->before_break_end_time)->format('H:i') : null;
            $aBOut = $correction->after_break_end_time  ? Carbon::parse($correction->after_break_end_time)->format('H:i')  : null;
            if ($bBOut !== $aBOut) {
                $rollbackData['break_end_time'] = $correction->before_break_end_time;
            }

            // 勤務地のロールバック判定
            if ($correction->before_working_place !== $correction->after_working_place) {
                $rollbackData['working_place'] = ($correction->before_working_place === $plannedPlace)
                    ? null
                    : $correction->before_working_place;
            }

            //  変化のあった項目のみが $rollbackData にセットされているため、安全にupdateを実行
            DB::table('workings')
                ->where('user_id', $correction->user_id)
                ->where('punch_date', $correction->target_date)
                ->update($rollbackData);
        }

        // 申請履歴自体の削除
        $correction->delete();
        return redirect()->back()->with('success', '打刻修正申請を取り消しました。');
    }
}
