<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WorkingCorrection extends Model
{
    use HasFactory;

    // 💡 一括保存を許可するカラムを指定
    protected $fillable = [
        'user_id',
        'target_date',
        'status',
        'before_attendance',
        'before_leaving',
        'before_break_time',
        'before_break_end_time',
        'before_working_place',
        'after_attendance',
        'after_leaving',
        'after_break_time',
        'after_break_end_time',
        'after_working_place',
        'memo',
        'updater_name',
    ];

    /**
     * 紐づくユーザー（リレーション定義）
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 💡 この申請内容を取り消し、workingsテーブルを「修正前」の状態に戻す。
     *    却下（管理者操作）・取り消し（本人操作）の両方から呼び出す共通ロジック。
     *
     *    WorkingCorrectionController::updateCorrection() は「出勤時刻を削除」した場合、
     *    workingsの行自体を物理削除する仕様になっているため、
     *    このメソッドは「ロールバック時に行が存在しない」ケースを考慮している。
     */
    public function rollbackWorkingsData(): void
    {
        $existingRecord = DB::table('workings')
            ->where('user_id', $this->user_id)
            ->where('punch_date', $this->target_date)
            ->first();

        // 💡 この申請が行われる前、そもそもworkingsにレコードが存在しなかったケース
        //    （新規追加の打刻申請）。この場合は「無かった状態」に戻すだけでよい。
        $wasNewRecord = is_null($this->before_attendance)
            && is_null($this->before_leaving)
            && is_null($this->before_break_time)
            && is_null($this->before_break_end_time);

        if ($wasNewRecord) {
            if ($existingRecord) {
                DB::table('workings')
                    ->where('id', $existingRecord->id)
                    ->delete();
            }

            return;
        }

        // 予定勤務地（シフト）の取得
        $shiftData = DB::table('shifts')
            ->join('shift_masters', 'shifts.master_id', '=', 'shift_masters.id')
            ->where('shifts.user_id', $this->user_id)
            ->where('shifts.target_date', $this->target_date)
            ->select('shift_masters.working_place')
            ->first();

        $plannedPlace = $shiftData ? $shiftData->working_place : '未定';

        // 秒を切り捨てて時分(H:i)で比較し、変化があった項目のみ特定する
        $bAtt = $this->before_attendance ? Carbon::parse($this->before_attendance)->format('H:i') : null;
        $aAtt = $this->after_attendance ? Carbon::parse($this->after_attendance)->format('H:i') : null;
        $attendanceChanged = $bAtt !== $aAtt;

        $bLea = $this->before_leaving ? Carbon::parse($this->before_leaving)->format('H:i') : null;
        $aLea = $this->after_leaving ? Carbon::parse($this->after_leaving)->format('H:i') : null;
        $leavingChanged = $bLea !== $aLea;

        $bBIn = $this->before_break_time ? Carbon::parse($this->before_break_time)->format('H:i') : null;
        $aBIn = $this->after_break_time ? Carbon::parse($this->after_break_time)->format('H:i') : null;
        $breakInChanged = $bBIn !== $aBIn;

        $bBOut = $this->before_break_end_time ? Carbon::parse($this->before_break_end_time)->format('H:i') : null;
        $aBOut = $this->after_break_end_time ? Carbon::parse($this->after_break_end_time)->format('H:i') : null;
        $breakOutChanged = $bBOut !== $aBOut;

        $placeChanged = $this->before_working_place !== $this->after_working_place;

        // 修正前の working_place は「表示用フォールバック値」の場合があるため、
        // 予定勤務地と一致する場合はDB上はnullだったとみなす
        $beforeWorkingPlaceForStorage = ($this->before_working_place === $plannedPlace)
            ? null
            : $this->before_working_place;

        if ($existingRecord) {
            // 💡 行がまだ存在する → 変化した項目だけ「修正前」の値に戻す
            $rollbackData = ['updated_at' => Carbon::now()];

            if ($attendanceChanged) $rollbackData['attendance'] = $this->before_attendance;
            if ($leavingChanged)    $rollbackData['leaving'] = $this->before_leaving;
            if ($breakInChanged)    $rollbackData['break_time'] = $this->before_break_time;
            if ($breakOutChanged)   $rollbackData['break_end_time'] = $this->before_break_end_time;
            if ($placeChanged)      $rollbackData['working_place'] = $beforeWorkingPlaceForStorage;

            DB::table('workings')
                ->where('id', $existingRecord->id)
                ->update($rollbackData);
        } else {
            // 💡 「出勤時刻の削除」などにより、この申請によって行自体が削除されているケース。
            //    before_* の内容で行を再INSERTして、申請前の状態に完全復元する。
            DB::table('workings')->insert([
                'user_id'        => $this->user_id,
                'punch_date'     => $this->target_date,
                'attendance'     => $this->before_attendance,
                'leaving'        => $this->before_leaving,
                'break_time'     => $this->before_break_time,
                'break_end_time' => $this->before_break_end_time,
                'working_place'  => $beforeWorkingPlaceForStorage,
                'commute'        => 0,
                'status'         => '未申請',
                'created_at'     => Carbon::now(),
                'updated_at'     => Carbon::now(),
            ]);
        }
    }
}