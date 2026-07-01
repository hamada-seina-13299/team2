<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Working extends Model
{
    protected $fillable = [
        'user_id',
        'punch_date',
        'attendance',
        'leaving',
        'break_time',
        'working_place',
        'commute',
        'status',
    ];
}
