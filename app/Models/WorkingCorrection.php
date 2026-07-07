<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}