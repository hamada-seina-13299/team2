<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    use HasFactory;

    // 一括代入(create)を許可するカラム
    protected $fillable = [
        'user_id',
        'master_id',
        'memo',
        'attendance_edit',
        'leaving_edit',
        'target_date',
        'status',
    ];

    // 型変換(date型・time型をきれいに扱うため)
    protected $casts = [
        'target_date' => 'date',
        'attendance_edit' => 'datetime:H:i',
        'leaving_edit' => 'datetime:H:i',
    ];

    // このシフトを登録したユーザー
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // このシフトが参照しているシフトマスタ
    public function shiftMaster(): BelongsTo
    {
        return $this->belongsTo(ShiftMaster::class, 'master_id');
    }
}