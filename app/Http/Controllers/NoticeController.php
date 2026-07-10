<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NoticeController extends Controller
{
    /**
     * shift_submissions の旧ステータス文字列を、他の申請テーブルと統一された文字列に変換するマップ。
     * MyDataController と同じ変換ルール。
     */
    private const SHIFT_SUBMISSION_STATUS_MAP = [
        '未提出'   => '未申請',
        '申請中'   => '申請中',
        '承認済み' => '承認',
        '差し戻し' => '却下',
    ];

    /**
     * ログインユーザー向けのお知らせ（アラート／システム通知／給与明細）を
     * 新しい順にまとめてJSONで返す。ヘッダーの「お知らせ」モーダルを開いた時にAjaxで呼ばれる。
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $notices = collect()
            ->concat($this->buildLatenessNotices($userId))
            ->concat($this->buildShiftSubmissionNotices($userId))
            ->concat($this->buildRejectionNotices($userId))
            ->concat($this->buildPayslipNotices())
            ->sortByDesc(fn ($n) => $n['datetime'])
            ->values();

        return response()->json([
            'notices' => $notices,
        ]);
    }

    /**
     * 「遅刻」「早退」発生通知：
     * 実際の打刻(workings)が、その日のシフト予定時刻(shifts→shift_masters、
     * shift側にattendance_edit/leaving_editがあればそちらを優先)より
     * 遅い出勤／早い退勤だった場合に通知を作る。
     */
    private function buildLatenessNotices(int $userId)
    {
        $rows = DB::table('workings as w')
            ->join('shifts as s', function ($join) {
                $join->on('s.target_date', '=', 'w.punch_date')
                     ->on('s.user_id', '=', 'w.user_id');
            })
            ->join('shift_masters as m', 's.master_id', '=', 'm.id')
            ->where('w.user_id', $userId)
            ->whereNotNull('w.attendance')
            ->select(
                'w.punch_date',
                'w.attendance',
                'w.leaving',
                's.attendance_edit',
                's.leaving_edit',
                'm.attendance as master_attendance',
                'm.leaving as master_leaving'
            )
            ->orderByDesc('w.punch_date')
            ->limit(300)
            ->get();

        $notices = collect();

        foreach ($rows as $row) {
            $scheduledAttendance = $row->attendance_edit ?? $row->master_attendance;
            $scheduledLeaving = $row->leaving_edit ?? $row->master_leaving;
            $dateLabel = Carbon::parse($row->punch_date)->format('n月j日');

            if ($scheduledAttendance && $row->attendance > $scheduledAttendance) {
                $dt = Carbon::parse($row->punch_date . ' ' . $row->attendance);
                $notices->push([
                    'category'     => 'システム通知',
                    'category_key' => 'system',
                    'title'        => "「遅刻」発生通知（{$dateLabel}）",
                    'datetime'     => $dt->format('Y-m-d H:i'),
                    'action'       => null,
                ]);
            }

            if ($row->leaving && $scheduledLeaving && $row->leaving < $scheduledLeaving) {
                $dt = Carbon::parse($row->punch_date . ' ' . $row->leaving);
                $notices->push([
                    'category'     => 'システム通知',
                    'category_key' => 'system',
                    'title'        => "「早退」発生通知（{$dateLabel}）",
                    'datetime'     => $dt->format('Y-m-d H:i'),
                    'action'       => null,
                ]);
            }
        }

        return $notices;
    }

    /**
     * シフト未提出アラート／却下アラート。
     */
    private function buildShiftSubmissionNotices(int $userId)
    {
        $rows = DB::table('shift_submissions')->where('user_id', $userId)->get();
        $notices = collect();

        foreach ($rows as $row) {
            $status = self::SHIFT_SUBMISSION_STATUS_MAP[$row->status] ?? $row->status;
            $label = "{$row->year}年{$row->month}月";

            if ($status === '未申請') {
                // 月初(5:01)に自動チェックされた、というイメージの日時にしている
                $dt = Carbon::createFromDate((int) $row->year, (int) $row->month, 1)->setTime(5, 1);
                $notices->push([
                    'category'     => 'アラート',
                    'category_key' => 'alert',
                    'title'        => "「シフト」未提出アラート（{$label}）",
                    'datetime'     => $dt->format('Y-m-d H:i'),
                    'action'       => null,
                ]);
            } elseif ($status === '却下') {
                $dt = Carbon::parse($row->updated_at);
                $notices->push([
                    'category'     => 'アラート',
                    'category_key' => 'alert',
                    'title'        => "「シフト」（{$label}分）が「却下」されました",
                    'datetime'     => $dt->format('Y-m-d H:i'),
                    'action'       => null,
                ]);
            }
        }

        return $notices;
    }

    /**
     * 勤怠申請／打刻修正の却下アラート。
     */
    private function buildRejectionNotices(int $userId)
    {
        $notices = collect();

        $attendanceRequests = DB::table('attendance_requests')
            ->where('user_id', $userId)
            ->where('status', '却下')
            ->get();

        foreach ($attendanceRequests as $row) {
            $dt = Carbon::parse($row->updated_at);
            $notices->push([
                'category'     => 'アラート',
                'category_key' => 'alert',
                'title'        => "「{$row->request_type}」の申請が「却下」されました",
                'datetime'     => $dt->format('Y-m-d H:i'),
                'action'       => null,
            ]);
        }

        $workingCorrections = DB::table('working_corrections')
            ->where('user_id', $userId)
            ->where('status', '却下')
            ->get();

        foreach ($workingCorrections as $row) {
            $dateLabel = Carbon::parse($row->target_date)->format('n月j日');
            $dt = Carbon::parse($row->updated_at);
            $notices->push([
                'category'     => 'アラート',
                'category_key' => 'alert',
                'title'        => "「打刻修正」（{$dateLabel}）の申請が「却下」されました",
                'datetime'     => $dt->format('Y-m-d H:i'),
                'action'       => null,
            ]);
        }

        return $notices;
    }

    /**
     * 給与明細通知（開発演習用のダミーデータ）。
     * 直近3ヶ月分（当月は未発行として除外）を、翌月25日発行という想定で生成する。
     * 画像は実データが無いため favicon.ico をプレビュー用に使う。
     */
    private function buildPayslipNotices()
    {
        $notices = collect();
        $today = Carbon::today();

        for ($i = 1; $i <= 3; $i++) {
            $target = $today->copy()->subMonthsNoOverflow($i)->startOfMonth();
            $issuedAt = $target->copy()->addMonthNoOverflow()->setDay(25)->setTime(10, 0);

            $notices->push([
                'category'     => '給与明細',
                'category_key' => 'payslip',
                'title'        => '給与明細（' . $target->format('Y年n月') . '分）',
                'datetime'     => $issuedAt->format('Y-m-d H:i'),
                'action'       => 'payslip',
                'action_label' => '確認する',
                'preview_url'  => asset('favicon.ico'),
            ]);
        }

        return $notices;
    }
}