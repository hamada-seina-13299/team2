<?php

namespace App\Models;

<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> 9f57ad9614dd86643c23d207cb75f1d24ace7b15
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    use HasFactory;

    // 一括代入(create)を許可するカラム
<<<<<<< HEAD
=======
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
>>>>>>> main
=======
>>>>>>> 9f57ad9614dd86643c23d207cb75f1d24ace7b15
    protected $fillable = [
        'user_id',
        'master_id',
        'memo',
        'attendance_edit',
        'leaving_edit',
        'target_date',
        'status',
    ];
<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> 9f57ad9614dd86643c23d207cb75f1d24ace7b15

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
<<<<<<< HEAD
=======
>>>>>>> main
=======
>>>>>>> 9f57ad9614dd86643c23d207cb75f1d24ace7b15
}