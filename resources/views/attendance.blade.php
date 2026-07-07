@extends('layouts.app')

@section('content')
{{-- ここでViteのCSSを強制的に読み込ませて、元のデザインを最優先で復活させます --}}
@vite(['resources/css/attendance.css'])

<div class="attendance-page">

    {{-- JavaScript用の設定データを配置 --}}
    <input type="hidden" id="csrf-token-meta" value="{{ csrf_token() }}">
    <input type="hidden" id="check-late-url-meta" value="{{ route('attendance.check-late') }}">
    <input type="hidden" id="attendance-url-base-meta" value="{{ url('attendance') }}">

    {{-- ユーザー情報 --}}
    <div class="user-info">
        <span class="user-name">{{ $user->name }}</span>
        @if ($user->dept)
            <span class="user-dept">{{ $user->dept }}</span>
        @endif
    </div>

    {{-- 月切替ヘッダー --}}
    <div class="attendance-header">
        <a href="{{ route('attendance.index', ['year' => $prevMonth->year, 'month' => $prevMonth->month]) }}"
           class="month-nav-btn" aria-label="前月">&lt;</a>

        <h1 class="month-title">{{ $currentMonth->format('Y年n月') }}</h1>

        <a href="{{ route('attendance.index', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}"
           class="month-nav-btn" aria-label="翌月">&gt;</a>
    </div>

    {{-- 申請ステータス表示 --}}
    <div class="attendance-status-wrapper" style="text-align: center; margin-bottom: 16px; display:flex; justify-content:center; align-items:center; gap:12px;">
        <span id="monthly-status-badge" class="status-badge status-{{ $summary['monthly_status'] ?? '未申請' }}">{{ $summary['monthly_status'] ?? '未申請' }}</span>
    </div>

    {{-- 非同期メッセージ用アラートエリア --}}
    <div id="ajax-alert" class="alert-success" style="display: none;"></div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    {{-- 勤怠集計 --}}
    <div class="summary-card">
        <div class="summary-title">勤怠集計</div>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">合計勤務時間</div>
                <div class="summary-value">{{ $summary['total_worked_time'] ?? '0:00' }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">所定労働時間</div>
                <div class="summary-value">{{ $summary['scheduled_time'] ?? '0:00' }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">出勤日数</div>
                <div class="summary-value">{{ $summary['working_days'] ?? 0 }}日</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">交通費合計</div>
                <div class="summary-value">{{ number_format($summary['total_commute'] ?? 0) }}円</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">合計休憩時間</div>
                <div class="summary-value">{{ $summary['total_break_time'] ?? '0:00' }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">有給休暇使用日数</div>
                <div class="summary-value">{{ $summary['paid_leave_days'] ?? 0 }}日</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">半休使用日数</div>
                <div class="summary-value">{{ $summary['half_day_leave_days'] ?? 0 }}日</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">残業時間</div>
                <div class="summary-value">{{ $summary['overtime_time'] ?? '0:00' }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">遅刻早退・控除</div>
                <div class="summary-value">{{ $summary['late_early_time'] ?? '0:00' }}</div>
            </div>
        </div>
    </div>

    {{-- 勤務表カード --}}
    <div class="attendance-card">
        <div class="card-header">
            <span class="card-icon">📋</span>
            <span class="card-title">勤務表</span>
        </div>

        {{-- タブボタン --}}
        <div class="tabs">
            <button type="button" class="tab-btn active" data-tab="main">主勤怠情報</button>
            <button type="button" class="tab-btn" data-tab="other">その他</button>
        </div>

        {{-- 主勤怠情報タブ --}}
        <div id="tab-main" class="tab-panel">
            <div class="table-wrapper">
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th class="col-date">日付</th>
                            <th>操作</th>
                            <th>出勤</th>
                            <th>退勤</th>
                            <th>休憩</th>
                            <th>勤務地</th>
                            <th>交通費</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($calendar as $row)
                            @php
                                $date = $row['date'];
                                $w = $row['working'];
                                $master = $row['shift_master'];
                                $isHoliday = $row['is_holiday'];
                                $isSaturday = $date->dayOfWeek === \Carbon\Carbon::SATURDAY;
                                $isSunday   = $date->dayOfWeek === \Carbon\Carbon::SUNDAY;
                                
                                $rowClass = ($isSunday || $isHoliday) ? 'row-sunday' : ($isSaturday ? 'row-saturday' : '');

                                $displayAttendance = '';
                                $displayLeaving = '';
                                $isLate = false;
                                $isEarly = false;

                                // 遅刻判定
                                if ($w && $w->attendance && $master && $master->attendance) {
                                    $actualAtt = \Carbon\Carbon::parse($w->attendance);
                                    $scheduledAtt = \Carbon\Carbon::parse($master->attendance);
                                    if ($actualAtt->gt($scheduledAtt)) { $isLate = true; }
                                }
                                // 早退判定
                                if ($w && $w->leaving && $master && $master->leaving) {
                                    $actualLeav = \Carbon\Carbon::parse($w->leaving);
                                    $scheduledLeav = \Carbon\Carbon::parse($master->leaving);
                                    if ($actualLeav->lt($scheduledLeav)) { $isEarly = true; }
                                }

                                if ($w && $w->attendance) {
                                    $attTime = \Carbon\Carbon::parse($w->attendance);
                                    if ($master && $master->attendance) {
                                        $masterAtt = \Carbon\Carbon::parse($master->attendance);
                                        if ($attTime->lt($masterAtt)) { $attTime = $masterAtt; }
                                    }
                                    $displayAttendance = $attTime->format('H:i');
                                }
                                if ($w && $w->leaving) {
                                    $leavTime = \Carbon\Carbon::parse($w->leaving);
                                    if ($master && $master->leaving) {
                                        $masterLeav = \Carbon\Carbon::parse($master->leaving);
                                        $masterLeavPlus1 = $masterLeav->copy()->addHour();
                                        if ($leavTime->gt($masterLeav) && $leavTime->lt($masterLeavPlus1)) { $leavTime = $masterLeav; }
                                    }
                                    $displayLeaving = $leavTime->format('H:i');
                                }
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td class="col-date">
                                    {{ $date->format('n月j日') }}（{{ ['日','月','火','水','木','金','土'][$date->dayOfWeek] }}）
                                    @if($isHoliday)
                                        <span class="holiday-name" style="font-size: 10px; display: block; color: #d64b5f;">{{ $row['holiday_name'] }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-buttons-cell" style="display: flex; gap: 6px; align-items: center;">
                                        <button type="button" class="custom-stamp-edit-btn js-stamp-edit-btn" 
                                                data-date="{{ $date->format('Y-m-d') }}"
                                                data-attendance="{{ $w && $w->attendance ? \Carbon\Carbon::parse($w->attendance)->format('H:i') : '' }}"
                                                data-leaving="{{ $w && $w->leaving ? \Carbon\Carbon::parse($w->leaving)->format('H:i') : '' }}"
                                                data-break="{{ $w && $w->break_time ? \Carbon\Carbon::parse($w->break_time)->format('H:i') : '' }}">
                                            🕒
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    @if($isLate)
                                        <span class="late-badge" style="background:#ffecec; color:#d64b5f; padding:2px 4px; border-radius:3px; font-size:11px; margin-right:2px; font-weight:bold;">遅</span>
                                    @endif
                                    {{ $displayAttendance }}
                                </td>
                                <td>
                                    @if($isEarly)
                                        <span class="early-badge" style="background:#fff4ec; color:#e67e22; padding:2px 4px; border-radius:3px; font-size:11px; margin-right:2px; font-weight:bold;">早</span>
                                    @endif
                                    {{ $displayLeaving }}
                                </td>
                                <td>{{ $w && $w->break_time ? \Carbon\Carbon::parse($w->break_time)->format('H:i') : '' }}</td>
                                <td>{{ $master->working_place ?? '' }}</td>
                                <td>{{ $w && $w->commute ? number_format($w->commute) . '円' : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- その他タブ --}}
        <div id="tab-other" class="tab-panel" style="display:none;">
            <div class="table-wrapper">
                <table class="attendance-table other-detail-table">
                    <thead>
                        <tr>
                            <th class="col-date">日付</th>
                            <th>勤務日種別</th>
                            <th>操作</th>
                            <th>シフト名</th>
                            <th>出勤</th>
                            <th>退勤</th>
                            <th>休憩開始</th>
                            <th>休憩終了</th>
                            <th>休憩合計</th>
                            <th>遅刻</th>
                            <th>早退</th>
                            <th>他控除</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($calendar as $row)
                            @php
                                $date = $row['date'];
                                $w = $row['working'];
                                $master = $row['shift_master'];
                                $requests = $row['requests'];
                                $isHoliday = $row['is_holiday'];
                                $isSaturday = $date->dayOfWeek === \Carbon\Carbon::SATURDAY;
                                $isSunday   = $date->dayOfWeek === \Carbon\Carbon::SUNDAY;
                                $rowClass = ($isSunday || $isHoliday) ? 'row-sunday' : ($isSaturday ? 'row-saturday' : '');

                                $dayType = $isHoliday ? '祝日' : ($isSunday ? '法定休日' : ($isSaturday ? '法定外休日' : '平日'));

                                $lateTime = '';
                                $earlyLeaveTime = '';
                                $otherDeduction = '';

                                if ($w && $w->attendance && $master && $master->attendance) {
                                    $actualAtt = \Carbon\Carbon::parse($w->attendance);
                                    $scheduledAtt = \Carbon\Carbon::parse($master->attendance);
                                    if ($actualAtt->gt($scheduledAtt)) {
                                        $lateMinutes = $scheduledAtt->diffInMinutes($actualAtt);
                                        $lateTime = sprintf('%d:%02d', intdiv($lateMinutes, 60), $lateMinutes % 60);
                                    }
                                }

                                if ($w && $w->leaving && $master && $master->leaving) {
                                    $actualLeav = \Carbon\Carbon::parse($w->leaving);
                                    $scheduledLeav = \Carbon\Carbon::parse($master->leaving);
                                    if ($actualLeav->lt($scheduledLeav)) {
                                        $earlyMinutes = $actualLeav->diffInMinutes($scheduledLeav);
                                        $earlyLeaveTime = sprintf('%d:%02d', intdiv($earlyMinutes, 60), $earlyMinutes % 60);
                                    }
                                }

                                foreach ($requests as $req) {
                                    if (in_array($req->request_type, ['欠勤', '有給', '半休'])) { $otherDeduction = $req->request_type; }
                                }

                                $breakStart = null; $breakEnd = null; $breakDurationMinutes = null;
                                if ($w && $w->leaving && $master && $master->break_start_time && $master->break_time) {
                                    $breakStart = \Carbon\Carbon::parse($master->break_start_time);
                                    $breakDurationMinutes = \Carbon\Carbon::parse('00:00')->diffInMinutes(\Carbon\Carbon::parse($master->break_time));
                                    $breakEnd = $breakStart->copy()->addMinutes($breakDurationMinutes);
                                }
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td class="col-date">{{ $date->format('n月j日') }}</td>
                                <td>{{ $dayType }}</td>
                                <td>
                                    <div class="action-buttons-cell" style="display: flex; gap: 6px; align-items: center;">
                                        <button type="button" class="custom-stamp-edit-btn js-stamp-edit-btn" 
                                                data-date="{{ $date->format('Y-m-d') }}"
                                                data-attendance="{{ $w && $w->attendance ? \Carbon\Carbon::parse($w->attendance)->format('H:i') : '' }}"
                                                data-leaving="{{ $w && $w->leaving ? \Carbon\Carbon::parse($w->leaving)->format('H:i') : '' }}"
                                                data-break="{{ $w && $w->break_time ? \Carbon\Carbon::parse($w->break_time)->format('H:i') : '' }}">
                                            🕒
                                        </button>

                                        @if($requests->count() > 0)
                                            @foreach($requests as $req)
                                                <button type="button" class="icon-btn js-edit-btn"
                                                    data-id="{{ $req->id }}"
                                                    data-date="{{ $date->format('Y-m-d') }}"
                                                    data-type="{{ $req->request_type }}"
                                                    data-time="{{ $req->request_time ? \Carbon\Carbon::parse($req->request_time)->format('H:i') : '' }}"
                                                    data-memo="{{ $req->memo }}">✏️</button>
                                                <form action="{{ route('attendance.destroy', $req->id) }}" method="POST"
                                                      class="inline-form js-delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="icon-btn" title="削除">🗑️</button>
                                                </form>
                                            @endforeach
                                        @endif
                                    </div>
                                </td>
                                <td>@if($master){{ $master->name ?? '' }}@endif</td>
                                <td>
                                    @if($w && $w->attendance)
                                        @if($lateTime) <span class="late-badge" style="background:#ffecec; color:#d64b5f; padding:2px 4px; border-radius:3px; font-size:11px; margin-right:2px;">遅</span>@endif{{ \Carbon\Carbon::parse($w->attendance)->format('H:i') }}
                                    @endif
                                </td>
                                <td>
                                    @if($w && $w->leaving)
                                        @if($earlyLeaveTime) <span class="early-badge" style="background:#fff4ec; color:#e67e22; padding:2px 4px; border-radius:3px; font-size:11px; margin-right:2px;">早</span>@endif{{ \Carbon\Carbon::parse($w->leaving)->format('H:i') }}
                                    @endif
                                </td>
                                <td>{{ $breakStart ? $breakStart->format('H:i') : '' }}</td>
                                <td>{{ $breakEnd ? $breakEnd->format('H:i') : '' }}</td>
                                <td>{{ $breakDurationMinutes !== null ? sprintf('%d:%02d', intdiv($breakDurationMinutes, 60), $breakDurationMinutes % 60) : '' }}</td>
                                <td>{{ $lateTime }}</td>
                                <td>{{ $earlyLeaveTime }}</td>
                                <td>{{ $otherDeduction }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 申請ボタンエリア --}}
        <div class="attendance-card-footer-action" id="monthly-action-container">
            @if (($summary['monthly_status'] ?? '未申請') === '未申請')
                @if ($summary['can_submit'] ?? false)
                    <form id="monthly-submit-form" action="{{ route('attendance.submit') }}" method="POST" class="inline-form">
                        @csrf
                        <input type="hidden" name="year" value="{{ $currentMonth->year }}">
                        <input type="hidden" name="month" value="{{ $currentMonth->month }}">
                        <button type="submit" class="btn-large-action btn-submit-active">申請する</button>
                    </form>
                @endif
            @elseif (($summary['monthly_status'] ?? '') === '申請済み')
                <form id="monthly-cancel-form" action="{{ route('attendance.cancel') }}" method="POST" class="inline-form">
                    @csrf
                    <input type="hidden" name="year" value="{{ $currentMonth->year }}">
                    <input type="hidden" name="month" value="{{ $currentMonth->month }}">
                    <button type="submit" class="btn-large-action btn-submit-cancel">提出取り下げ</button>
                </form>
            @elseif (($summary['monthly_status'] ?? '') === '承認')
                <button type="button" class="btn-large-action btn-submit-disabled" disabled>承認済みの勤務表</button>
            @endif
        </div>
    </div>
</div>

{{-- モーダルファイルのインクルード --}}
@include('attendance-modals')

@endsection