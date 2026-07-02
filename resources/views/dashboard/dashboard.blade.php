@extends('layouts/app')

@section('title', 'タイムカード | 勤怠管理')

@section('content')

@if(session('success'))
<div style="background-color: #d1fae5; color: #065f46; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold;">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background-color: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold;">
    {{ session('error') }}
</div>
@endif

<div class="main-card">
    @if(!$todayAttendance)
    <div class="status-bar">出勤準備中</div>
    @elseif($todayAttendance && is_null($todayAttendance->leaving))
    <div class="status-bar">ただいま勤務中</div>
    @else
    <div class="status-bar status-finished">本日の勤務お疲れ様でした</div>
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
                <!-- 休憩時間フォーマット例。秒を省いて表示したい場合は変換します -->
                <strong>休憩時間：</strong> {{ \Carbon\Carbon::parse($todayShift->break_time)->format('H:i') }}
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
                    <!-- 勤務地名を表示 -->
                    <span id="current-working-place">{{ $displayWorkingPlace }}</span>
                    <a href="#" id="trigger-location-change" class="change-link">変更 ⓘ</a>
                </span>
            </div>

            <div class="punch-buttons">
                @if(!$todayAttendance)
                <form action="{{ route('clock.in') }}" method="POST" style="flex: 1;">
                    @csrf
                    <button type="submit" class="btn-punch btn-in">出勤</button>
                </form>
                @else
                <button class="btn-punch" disabled>出勤</button>
                @endif

                @if($todayAttendance && is_null($todayAttendance->leaving))
                <form action="{{ route('clock.out') }}" method="POST" style="flex: 1;">
                    @csrf
                    <button type="submit" class="btn-punch btn-out">退勤</button>
                </form>
                @else
                <button class="btn-punch" disabled>退勤</button>
                @endif
            </div>

            <div class="sub-actions">
                <button class="btn-break" {{ ($todayAttendance && is_null($todayAttendance->leaving)) ? '' : 'disabled' }}>
                    休憩開始
                </button>
            </div>

            <div class="bottom-actions">
                <button class="btn-outline">📝 勤怠申請</button>
                <button class="btn-outline btn-edit-trigger"
                    data-date="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
                    data-date-label="{{ \Carbon\Carbon::today()->isoFormat('YYYY年M月D日(ddd)') }}"
                    data-attendance="{{ $todayAttendance ? \Carbon\Carbon::parse($todayAttendance->attendance)->format('H:i') : '' }}"
                    data-leaving="{{ ($todayAttendance && $todayAttendance->leaving) ? \Carbon\Carbon::parse($todayAttendance->leaving)->format('H:i') : '' }}"
                    data-place="{{ $displayWorkingPlace }}">
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
<div style="background-color: #fff; padding: 15px 30px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
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
        <div>🟢 <strong>出勤:</strong> {{ $attTime }} ({{ $record->working_place }})</div>
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


<div class="modal-overlay" id="fix-modal-overlay">
    <div id="available-dates-data" data-dates="{{ json_encode($history->pluck('punch_date')->unique()->values()->all()) }}" style="display:none;"></div>
    <div id="available-places-data" data-places="{{ json_encode($workingPlaces) }}" style="display:none;"></div>

    <div class="modal-container">

        <div class="modal-header">
            <button type="button" class="btn-break" id="btn-modal-prev" style="width: auto; padding: 4px 12px;">&lt; 前日</button>
            <div class="modal-title">
                <span id="modal-target-date-label"></span> 📅
            </div>
            <button type="button" class="btn-break" id="btn-modal-next" style="width: auto; padding: 4px 12px;">翌日 &gt;</button>
        </div>

        <form action="{{ route('clock.correct') }}" method="POST" id="modal-fix-form">
            @csrf
            <input type="hidden" name="target_date" id="modal-target-date-input">

            <table class="modal-table">
                <thead>
                    <tr>
                        <th style="width: 14%;">打刻種別</th>
                        <th style="width: 20%;">打刻時間</th>
                        <th style="width: 15%;">勤務地</th>
                        <th>申請理由</th>
                        <th style="width: 18%;">既定休憩追加</th>
                        <th style="width: 8%;">削除</th>
                    </tr>
                </thead>
                <tbody id="modal-table-tbody">
                    <tr class="form-row-group static-row" id="row-attendance">
                        <td style="font-weight: bold;">出勤</td>
                        <td>
                            <input type="time" name="attendance_time" id="modal-attendance-time" class="modal-input watch-change">
                        </td>
                        <td>-</td>
                        <td style="text-align: left;">
                            <input type="text" name="attendance_reason" id="modal-attendance-reason" class="modal-input reason-input" placeholder="例: 打刻忘れのため">
                            <div class="error-msg" id="error-attendance" style="color: #ef4444; font-size: 11px; margin-top: 4px; display: none;">※申請理由を入力してください</div>
                        </td>
                        <td style="color: #ccc; font-size: 12px;">-</td>
                        <td><input type="checkbox" name="delete_attendance" value="1" class="modal-checkbox watch-change"></td>
                    </tr>

                    <tr class="form-row-group static-row" id="row-leaving">
                        <td style="font-weight: bold;">退勤</td>
                        <td>
                            <input type="time" name="leaving_time" id="modal-leaving-time" class="modal-input watch-change">
                        </td>
                        <td>-</td>
                        <td style="text-align: left;">
                            <input type="text" name="leaving_reason" id="modal-leaving-reason" class="modal-input reason-input" placeholder="例: 残業の申請忘れ">
                            <div class="error-msg" id="error-leaving" style="color: #ef4444; font-size: 11px; margin-top: 4px; display: none;">※申請理由を入力してください</div>
                        </td>
                        <td>
                            <select id="modal-break-auto" class="modal-select watch-change">
                                <option value="OFF">OFF</option>
                                <option value="自動追加">休憩を自動追加</option>
                            </select>
                        </td>
                        <td><input type="checkbox" name="delete_leaving" value="1" class="modal-checkbox watch-change"></td>
                    </tr>
                </tbody>
            </table>

            <button type="button" class="btn-break" id="btn-add-punch-row" style="width: auto; margin-bottom: 20px;">＋ 打刻追加</button>

            <div style="font-size: 14px; font-weight: bold; color: #1aaba8; margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px;">
                打刻申請履歴
            </div>
            <table class="modal-table" style="font-size: 12px;">
                <thead>
                    <tr>
                        <th>操作</th>
                        <th>申請日時</th>
                        <th>ステータス</th>
                        <th>追加種別</th>
                        <th>修正前</th>
                        <th>修正後</th>
                        <th>打刻補足情報</th>
                        <th>更新日時</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="8" style="color: #9ca3af; padding: 12px;">申請履歴はありません。</td>
                    </tr>
                </tbody>
            </table>

            <div class="modal-footer">
                <button type="button" class="btn-close-modal" id="close-fix-modal">閉じる</button>
                <button type="submit" class="btn-submit-modal active">申請</button>
            </div>
        </form>

    </div>
</div>
@endsection