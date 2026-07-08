<?php

namespace App\Http\Middleware;

use App\Models\Working;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 「打刻に合わせる」がONで勤怠申請が送られてきた時、
 * クライアントから届いた request_time は信用せず、
 * workings テーブルの実打刻（出勤/退勤）で必ず上書きしてから
 * AttendanceController@store / update に渡すためのMiddleware。
 *
 * AttendanceController 自体には手を加えず、ここで完結させる。
 */
class SyncAttendanceRequestTime
{
    /** 申請種別ごとに、どちらの打刻（出勤/退勤）と同期するか */
    private const SYNC_FIELD_MAP = [
        '遅刻'     => 'attendance',
        '有事遅刻' => 'attendance',
        '早退'     => 'leaving',
        '残業'     => 'leaving',
        '有事早退' => 'leaving',
    ];

    public function handle(Request $request, Closure $next)
    {
        if ($request->input('sync_with_punch') !== '1') {
            return $next($request);
        }

        $requestType = $request->input('request_type');
        $syncField = self::SYNC_FIELD_MAP[$requestType] ?? null;

        // 出勤/退勤と同期する種別ではない場合は何もしない（欠勤・有給・半休など）
        if (!$syncField) {
            return $next($request);
        }

        $working = Working::where('user_id', Auth::id())
            ->where('punch_date', $request->input('target_date'))
            ->first();

        $punchTime = $working->{$syncField} ?? null;

        if (!$punchTime) {
            return back()
                ->withErrors(['request_time' => '必要な打刻情報がありません。'])
                ->withInput();
        }

        // クライアント側の値（改ざんされている可能性がある）を、実打刻の値で必ず上書きする
        $request->merge([
            'request_time' => Carbon::parse($punchTime)->format('H:i'),
        ]);

        return $next($request);
    }
}