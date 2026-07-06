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

        // 1. 修正前の状態（workings）とシフトを取得
        $existingRecord = DB::table('workings')->where('user_id', $userId)->where('punch_date', $targetDate)->first();

        $shiftData = DB::table('shifts')
            ->join('shift_masters', 'shifts.master_id', '=', 'shift_masters.id')
            ->where('shifts.user_id', $userId)
            ->where('shifts.target_date', $targetDate)
            ->select('shift_masters.working_place')
            ->first();

        $beforeAttendance   = $existingRecord ? $existingRecord->attendance : null;
        $beforeLeaving      = $existingRecord ? $existingRecord->leaving : null;
        $beforeBreakTime    = $existingRecord ? $existingRecord->break_time : null;
        $beforeBreakEndTime = $existingRecord ? $existingRecord->break_end_time : null;
        $beforeWorkingPlace = $shiftData ? $shiftData->working_place : ($existingRecord ? $existingRecord->working_place : '未定');

        // 2. 削除チェックボックスの判定
        $deleteAttendance = $request->has('delete_attendance');
        $deleteLeaving    = $request->has('delete_leaving');

        $attendance = (!empty($request->input('attendance_time')) && !$deleteAttendance) ? Carbon::parse($request->input('attendance_time'))->format('H:i:s') : null;
        $leaving    = (!empty($request->input('leaving_time')) && !$deleteLeaving) ? Carbon::parse($request->input('leaving_time'))->format('H:i:s') : null;

        // 3. 💡 動的行（休憩開始・休憩終了・勤務地変更）のパース処理
        $breakTime    = $beforeBreakTime;
        $breakEndTime = $beforeBreakEndTime;
        $workingPlace = $existingRecord ? $existingRecord->working_place : $beforeWorkingPlace;

        $dynamicTypes  = $request->input('dynamic_type', []);
        $dynamicTimes  = $request->input('dynamic_time', []);
        $dynamicPlaces = $request->input('dynamic_working_place', []); // 勤務地変更用

        foreach ($dynamicTypes as $index => $type) {
            if (empty($dynamicTimes[$index]) && $type !== '勤務地変更') continue;

            if ($type === '休憩開始') {
                $breakTime = Carbon::parse($dynamicTimes[$index])->format('H:i:s');
            } elseif ($type === '休憩終了') {
                // 💡 要望3：これでDBに確実に入るようになります
                $breakEndTime = Carbon::parse($dynamicTimes[$index])->format('H:i:s');
            } elseif ($type === '勤務地変更') {
                // 💡 要望2：「勤務地変更」が明示的に選択されている時だけ上書き更新
                if (!empty($dynamicPlaces[$index])) {
                    $workingPlace = $dynamicPlaces[$index];
                }
            }
        }

        // 固定休憩行の削除チェック対応
        if ($request->has('delete_break')) {
            $breakTime = null;
            $breakEndTime = null;
        }

        // 4. memoの構築
        $reasons = [];
        if ($request->input('attendance_reason')) $reasons[] = '【出勤修正】' . $request->input('attendance_reason');
        if ($request->input('leaving_reason')) $reasons[] = '【退勤修正】' . $request->input('leaving_reason');
        if ($request->has('dynamic_reason')) {
            foreach ($request->input('dynamic_reason') as $idx => $dr) {
                if (!empty($dr)) {
                    $type = $dynamicTypes[$idx] ?? '追加';
                    $reasons[] = "【{$type}】" . $dr;
                }
            }
        }
        $memo = implode("\n", $reasons);

        // 5. workings テーブルの即時更新
        if (($deleteAttendance && $deleteLeaving) || (is_null($attendance) && is_null($leaving))) {
            if ($existingRecord) {
                DB::table('workings')->where('id', $existingRecord->id)->delete();
            }
            return redirect()->route('dashboard')->with('success', '打刻情報を削除しました。');
        }

        $updateData = [
            'attendance'    => $attendance,
            'leaving'       => $leaving,
            'break_time'    => $breakTime,
            'break_end_time'=> $breakEndTime,
            'working_place' => $workingPlace, 
            'status'        => '承認',
            'updated_at'    => Carbon::now(),
        ];

        if ($existingRecord) {
            DB::table('workings')->where('id', $existingRecord->id)->update($updateData);
        } else {
            $updateData['user_id'] = $userId;
            $updateData['punch_date'] = $targetDate;
            $updateData['commute'] = 0;
            $updateData['created_at'] = Carbon::now();
            DB::table('workings')->insert($updateData);
        }

        // 6. ログ保存
        WorkingCorrection::create([
            'user_id'               => $userId,
            'target_date'           => $targetDate,
            'status'                => '承認',
            'before_attendance'     => $beforeAttendance,
            'before_leaving'        => $beforeLeaving,
            'before_break_time'     => $beforeBreakTime,
            'before_break_end_time' => $beforeBreakEndTime,
            'before_working_place'  => $beforeWorkingPlace,
            'after_attendance'      => $attendance,
            'after_leaving'         => $leaving,
            'after_break_time'      => $breakTime,
            'after_break_end_time'  => $breakEndTime,
            'after_working_place'   => $workingPlace,
            'memo'                  => $memo,
        ]);

        return redirect()->route('dashboard')->with('success', '打刻情報を修正しました。');
    }

    public function cancelCorrection($id)
    {
        // これで親と干渉せず綺麗に削除
        WorkingCorrection::where('user_id', Auth::id())->findOrFail($id)->delete();
        return redirect()->back()->with('success', '打刻修正を取り消しました。');
    }
}