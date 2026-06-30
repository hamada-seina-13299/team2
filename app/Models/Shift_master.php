<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift_master extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'attendance',
        'leaving',
        'break_time',
        'working_place',
    ];
}
