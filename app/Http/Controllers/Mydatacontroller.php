<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MyDataController extends Controller
{
    /**
     * shift_submissions の旧ステータス文字列を、他の申請テーブルと統一された文字列に変換するマップ。
     * 未提出 / 申請中 / 承認済み / 差し戻し → 未申請 / 申請中 / 承認 / 却下
     */
    private const SHIFT_SUBMISSION_STATUS_MAP = [
        '未提出'   => '未申請',
        '申請中'   => '申請中',
        '承認済み' => '承認',
        '差し戻し' => '却下',
    ];

    /**
     * ログインユーザー自身の申請データを、種別を問わず横断して一覧表示する。
     * 対象テーブル：attendance_requests（勤怠申請）/ shift_submissions（シフト提出）/ working_corrections（打刻修正）
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $attendanceRequests = DB::table('attendance_requests')
            ->where('user_id', $userId)
            ->get()
            ->map(function ($row) {
                $summary = $row->request_type;
                if (!empty($row->request_time)) {
                    $summary .= '（' . substr($row->request_time, 0, 5) . '）';
                }

                return [
                    'type'         => '勤怠申請',
                    'type_key'     => 'attendance_request',
                    'target'       => Carbon::parse($row->target_date)->format('Y/m/d'),
                    'target_sort'  => $row->target_date,
                    'summary'      => $summary,
                    'memo'         => $row->memo,
                    'status'       => $row->status,
                    'updater_name' => $row->updater_name,
                    'attachment'   => $row->attachment, // nullable：無ければ「ー」、あれば確認ボタンを出す
                    'created_at'   => $row->created_at,
                ];
            });

        $shiftSubmissions = DB::table('shift_submissions')
            ->where('user_id', $userId)
            ->get()
            ->map(function ($row) {
                return [
                    'type'         => 'シフト提出',
                    'type_key'     => 'shift_submission',
                    'target'       => sprintf('%d年%d月', $row->year, $row->month),
                    'target_sort'  => sprintf('%04d-%02d-01', $row->year, $row->month),
                    'summary'      => sprintf('%d年%d月分のシフト提出', $row->year, $row->month),
                    'memo'         => null,
                    'status'       => self::SHIFT_SUBMISSION_STATUS_MAP[$row->status] ?? $row->status,
                    'updater_name' => null,
                    'attachment'   => null,
                    'created_at'   => $row->created_at,
                ];
            });

        $workingCorrections = DB::table('working_corrections')
            ->where('user_id', $userId)
            ->get()
            ->map(function ($row) {
                $summaryParts = [];

                if (!empty($row->after_attendance)) {
                    $summaryParts[] = '出勤 ' . substr($row->after_attendance, 0, 5);
                }
                if (!empty($row->after_leaving)) {
                    $summaryParts[] = '退勤 ' . substr($row->after_leaving, 0, 5);
                }
                if (!empty($row->after_working_place)) {
                    $summaryParts[] = $row->after_working_place;
                }

                return [
                    'type'         => '打刻修正',
                    'type_key'     => 'working_correction',
                    'target'       => Carbon::parse($row->target_date)->format('Y/m/d'),
                    'target_sort'  => $row->target_date,
                    'summary'      => !empty($summaryParts) ? implode(' / ', $summaryParts) : '打刻修正申請',
                    'memo'         => $row->memo,
                    'status'       => $row->status,
                    'updater_name' => $row->updater_name,
                    'attachment'   => null,
                    'created_at'   => $row->created_at,
                ];
            });

        // 3種類の申請を1本のリストにまとめ、申請日時（新しい順）で並べる
        $myData = $attendanceRequests
            ->concat($shiftSubmissions)
            ->concat($workingCorrections)
            ->sortByDesc('created_at')
            ->values();

        return view('mydata.index', [
            'myData' => $myData,
        ]);
    }
}