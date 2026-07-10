<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRequest extends Model
{
    protected $fillable = [
        'user_id',
        'target_date',
        'request_type',
        'memo',
        'request_time',
        'attachment',
        'status',
        'updater_name',
    ];

    // 💡 承認画面で $attendanceRequest->user->name / dept を使うために追加
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}