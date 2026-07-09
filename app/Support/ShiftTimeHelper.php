<?php

namespace App\Support;

/**
 * シフトの時刻表示・ロック判定に関する共通ロジック。
 *
 * 「退勤 <= 出勤」＝日をまたぐ勤務として扱い、表示側では
 * バリデーションエラーにせず「（翌日）」を自動付記する。
 * 一覧画面のインライン編集・承認画面のポップアップなど、
 * 退勤時刻を表示する箇所は必ずこのクラス経由で表示すること。
 */
class ShiftTimeHelper
{
    /**
     * 退勤時刻を表示用に整形する。
     * 出勤時刻以下（＝翌日にまたがる）の場合は「（翌日）」を付ける。
     *
     * @param string|null $attendance 出勤時刻（"H:i" or "H:i:s"）
     * @param string|null $leaving    退勤時刻（"H:i" or "H:i:s"）
     */
    public static function formatLeaving(?string $attendance, ?string $leaving): ?string
    {
        if (!$leaving) {
            return null;
        }

        $leavingTime = date('H:i', strtotime($leaving));

        if (self::isOvernight($attendance, $leaving)) {
            return $leavingTime . '（翌日）';
        }

        return $leavingTime;
    }

    /**
     * 退勤が出勤以下（＝日をまたぐ勤務）かどうかを判定する。
     */
    public static function isOvernight(?string $attendance, ?string $leaving): bool
    {
        if (!$attendance || !$leaving) {
            return false;
        }

        return date('H:i', strtotime($leaving)) <= date('H:i', strtotime($attendance));
    }

    /**
     * 指定した年月のシフトが「提出済み（申請中）」または「承認済み」で
     * ロックされているかどうかを判定する。
     */
    public static function isSubmissionLocked(?string $submissionStatus): bool
    {
        return in_array($submissionStatus, ['申請中', '承認済み'], true);
    }

    /**
     * 対象日が「先月以前（当月より過去）」かどうかを判定する。
     */
    public static function isPastMonth(\Carbon\Carbon $targetDate): bool
    {
        return $targetDate->copy()->startOfMonth()->lt(now()->startOfMonth());
    }
}