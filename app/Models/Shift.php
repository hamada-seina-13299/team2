<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'user_id',
        'master_id',
        'memo',
        'attendance_edit',
        'leaving_edit',
        'target_date',
        'status',
    ];
}