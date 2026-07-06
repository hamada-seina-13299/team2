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
        'attachment'
    ];
}
