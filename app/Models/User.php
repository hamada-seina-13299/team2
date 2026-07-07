<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'dept',
        'entering_company_date',
        'paid_leave_days',
        'half_day_leave_days'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // このユーザーが持つシフト一覧
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    // このユーザーが作成したシフトマスタ一覧
    public function shiftMasters(): HasMany
    {
        return $this->hasMany(ShiftMaster::class, 'user_id');
    }
}