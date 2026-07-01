<?php

namespace App\Models;

use App\Models\User;
use App\Models\ShiftMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'master_id',
        'memo',
        'attendance_edit',
        'leaving_edit',
        'target_date',
        'status',
    ];

    protected $casts = [
        'target_date' => 'date',
        'attendance_edit' => 'datetime:H:i',
        'leaving_edit' => 'datetime:H:i',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shiftMaster(): BelongsTo
    {
        return $this->belongsTo(ShiftMaster::class, 'master_id');
    }
}