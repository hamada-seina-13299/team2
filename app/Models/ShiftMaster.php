<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftMaster extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'attendance',
        'leaving',
        'break_time',
        'working_place',
    ];

    protected $casts = [
        'attendance' => 'datetime:H:i',
        'leaving' => 'datetime:H:i',
        'break_time' => 'datetime:H:i',
    ];

    // このマスタを作成したユーザー(共通マスタの場合はnull)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // このマスタを参照しているシフト一覧
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'master_id');
    }
}