@extends('layouts/app')

@section('title', 'タイムカード | 勤怠管理')

@section('body-class', 'has-sky-bg')

@section('content')

<!-- 上部固定の動的エラーアラート用コンテナ -->
<div id="dynamic-flash-error" style="display: none; background-color: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; align-items: center; justify-content: space-between;">
    <div style="display: flex; align-items: center; gap: 8px;">
        <span>⚠️</span>
        <span id="dynamic-flash-message"></span>
    </div>
    <button type="button" id="close-dynamic-flash" style="background: none; border: none; color: #991b1b; font-size: 20px; cursor: pointer; font-weight: bold; line-height: 1;">&times;</button>
</div>

@if(session('success'))
<div style="background-color: #d1fae5; color: #065f46; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold;">
    {{ session('success') }}
</div>
@endif
@if(session('warning'))
<div style="background-color: #fef08a; color: #854d0e; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; border: 1px solid #fef08a;">
    ⚠️ {{ session('warning') }}
</div>
@endif
@if(session('error'))
<div style="background-color: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold;">
    {{ session('error') }}
</div>
@endif

<div class="main-card">
    @if($latestOpenAttendance)
    <div class="status-bar">ただいま勤務中</div>
    @elseif($todayAttendance && !is_null($todayAttendance->leaving))
    <div class="status-bar" style="background-color: #6b7280;">本日の勤務お疲れ様でした。</div>
    @else
    <div class="status-bar">出勤準備中</div>
    @endif

    <div class="card-body">
        <div class="card-left">
            <div class="current-date">
                {{ \Carbon\Carbon::today()->isoFormat('YYYY年M月D日(ddd)') }}
            </div>
            <div class="clock-display">
                <span id="clock-hm">{{ \Carbon\Carbon::now()->format('H:i') }}</span>
                <span class="clock-seconds" id="clock-s">{{ \Carbon\Carbon::now()->format('s') }}</span>
            </div>

            <div class="shift-info-box">
                @if($todayShift)
                <strong>予定勤務地：</strong> {{ $todayShift->master_name }}<br>
                <strong>勤務時間：</strong> {{ \Carbon\Carbon::parse($todayShift->attendance)->format('H:i') }} ～ {{ \Carbon\Carbon::parse($todayShift->leaving)->format('H:i') }}<br>
                <strong>休憩時間：</strong> {{ $displayBreakRange }}
                @else
                <strong>予定勤務地：</strong> シフト未登録<br>
                <strong>勤務時間：</strong> --:-- ～ --:--<br>
                <strong>休憩時間：</strong> --:--
                @endif
            </div>
        </div>

        <div class="card-right">
            <div class="info-row">
                <span class="info-label">勤務地</span>
                <span class="info-value">
                    <span id="current-working-place">{{ $displayWorkingPlace }}</span>
                    <a href="#" id="trigger-location-change" class="change-link">変更 ⓘ</a>
                </span>
            </div>

            <div class="punch-buttons">
                {{-- 出勤ボタンの有効化条件：
                     1. 未退勤のオープンなデータがないこと
                     2. かつ、本日のデータがまだ存在しない（または本日のデータがあっても出勤・退勤どちらも空）こと --}}
                @if(!$latestOpenAttendance && (!$todayAttendance || (is_null($todayAttendance->attendance) && is_null($todayAttendance->leaving))))
                <form action="{{ route('clock.in') }}" method="POST" style="flex: 1;">
                    @csrf
                    <button type="submit" class="btn-punch btn-in">出勤</button>
                </form>
                @else
                <button class="btn-punch" disabled>出勤</button>
                @endif

                {{-- 退勤ボタンの有効化条件：
                    未退勤のオープンなデータが存在すること --}}
                @if($latestOpenAttendance)
                <form action="{{ route('clock.out') }}" method="POST" style="flex: 1;">
                    @csrf
                    <input type="hidden" name="attendance_id" value="{{ $latestOpenAttendance->id }}">
                    <button type="submit" class="btn-punch btn-out">退勤</button>
                </form>
                @else
                <button class="btn-punch" disabled>退勤</button>
                @endif
            </div>

            <!-- 既定の休憩を追加-->
            <div class="auto-break-toggle-wrapper" style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 15px; margin-bottom: 8px; font-size: 14px; color: #4b5563;">
                <span>既定の休憩を追加 ⓘ</span>
                <label class="switch">
                    <input type="checkbox" id="toggle-auto-break" {{ Auth::user()->can_auto_break ? 'checked' : '' }}>
                    <span class="slider">
                        <span class="slider-ball"></span>
                        <span class="toggle-text">OFF</span>
                    </span>
                </label>
            </div>

            <div class="sub-actions">
                <form action="{{ route('dashboard.breakIn') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-break"
                        {{ (
                            $latestOpenAttendance && 
                            is_null($latestOpenAttendance->break_time)
                        ) ? '' : 'disabled' }}>
                        休憩開始
                    </button>
                </form>
            </div>

            <div class="bottom-actions">
                <button type="button" class="btn-outline btn-attendance-request"
                    data-date="{{ \Carbon\Carbon::today()->format('Y-m-d') }}">
                    📝 勤怠申請
                </button>

                {{-- 上部ボタンのデータ属性を「最新の打刻履歴データ」に自動追従させます --}}
                @php
                $latestRecord = $history->first(); // 履歴の1件目（最新）
                @endphp
                <button class="btn-outline btn-edit-trigger"
                    data-date="{{ $latestRecord ? $latestRecord->punch_date : \Carbon\Carbon::today()->format('Y-m-d') }}"
                    data-date-label="{{ $latestRecord ? \Carbon\Carbon::parse($latestRecord->punch_date)->isoFormat('YYYY年M月D日(ddd)') : \Carbon\Carbon::today()->isoFormat('YYYY年M月D日(ddd)') }}"
                    data-attendance="{{ ($latestRecord && $latestRecord->attendance) ? \Carbon\Carbon::parse($latestRecord->attendance)->format('H:i') : '' }}"
                    data-leaving="{{ ($latestRecord && $latestRecord->leaving) ? \Carbon\Carbon::parse($latestRecord->leaving)->format('H:i') : '' }}"
                    data-break="{{ ($latestRecord && $latestRecord->break_time) ? \Carbon\Carbon::parse($latestRecord->break_time)->format('H:i') : '' }}"
                    data-break-out="{{ $latestRecord ? $latestRecord->break_out : '' }}"
                    data-place="{{ $latestRecord ? $latestRecord->working_place : $displayWorkingPlace }}">
                    🔄 打刻修正
                </button>
            </div>
        </div>
    </div>
</div>

<div class="history-section-title">打刻履歴 ⓘ</div>

@php
$groupedHistory = $history->groupBy('punch_date');
@endphp

@forelse($groupedHistory as $date => $records)
<div class="date-line">{{ \Carbon\Carbon::parse($date)->isoFormat('YYYY年M月D日(ddd)') }}</div>
<div class="history-card" style="background-color: #fff; padding: 15px 30px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
    @foreach($records as $record)
    @php
    $dateLabel = \Carbon\Carbon::parse($record->punch_date)->isoFormat('YYYY年M月D日(ddd)');
    $attTime = $record->attendance ? \Carbon\Carbon::parse($record->attendance)->format('H:i') : '未打刻';
    $leaveTime = $record->leaving ? \Carbon\Carbon::parse($record->leaving)->format('H:i') : '未打刻';

    $isNextDay = false;
    if($record->attendance && $record->leaving) {
    if(strtotime($record->attendance) > strtotime($record->leaving)) {
    $isNextDay = true;
    }
    }
    @endphp
    <div style="display: flex; gap: 40px; margin-bottom: 10px; font-size: 15px; align-items: center;">
        <div>🟢 <strong>出勤:</strong> {{ $attTime }} &nbsp; {{ $record->working_place }}</div>
        <div>
            🔴 <strong>退勤:</strong> {{ $leaveTime }}
            @if($isNextDay) <span style="color: #ef4444; font-weight: bold;">(翌日)</span> @endif
        </div>

        <div style="margin-left: auto;">
            <button type="button" class="btn-history-edit btn-edit-trigger"
                data-date="{{ $record->punch_date }}"
                data-date-label="{{ $dateLabel }}"
                data-attendance="{{ $record->attendance ? \Carbon\Carbon::parse($record->attendance)->format('H:i') : '' }}"
                data-leaving="{{ $record->leaving ? \Carbon\Carbon::parse($record->leaving)->format('H:i') : '' }}"
                data-break="{{ $record->break_time ? \Carbon\Carbon::parse($record->break_time)->format('H:i') : '' }}"
                data-break-out="{{ $record->break_out }}"
                data-place="{{ $record->working_place }}">
                ✏️ 修正
            </button>
        </div>
    </div>
    @endforeach
</div>
@empty
<div class="date-line">履歴なし</div>
@endforelse

@include('partials.dashboard-modals')


@endsection

@push('styles')
    @vite(['resources/css/dashboard-background.css'])
@endpush

@push('scripts')
    @vite(['resources/js/dashboard-background.js'])
@endpush