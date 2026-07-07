@extends('layouts/app')

@section('title', 'タイムカード | 勤怠管理')

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
                <button class="btn-outline">📝 勤怠申請</button>

                {{-- 💡 要望2: 上部ボタンのデータ属性を「最新の打刻履歴データ」に自動追従させます --}}
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


<div class="modal-overlay" id="fix-modal-overlay">
    {{-- 過去全ての打刻日（一週間分に限定しないリスト）を配列としてJSに渡す --}}
    <div id="available-dates-data" data-dates="{{ json_encode($allWorkingDates) }}" style="display:none;"></div>
    <div id="all-history-json-data" data-history="{{ $allHistoryJson }}" style="display:none;"></div>
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
                <tbody id="modal-correction-history-tbody">
                    {{-- 登録された履歴をループ表示（初期状態はJSで出し分けするため全て描画） --}}
                    @forelse($correctionHistory as $correction)
                    <tr class="correction-history-row" data-date="{{ $correction->target_date }}">
                        <td>
                            <button type="button" class="btn-cancel-correction" data-id="{{ $correction->id }}" style="background-color: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 11px;">
                                取消
                            </button>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($correction->created_at)->format('Y/m/d H:i') }}</td>
                        <td>
                            {{-- ステータスごとに色を変えるとおしゃれです --}}
                            @if($correction->status === '承認')
                            <span style="color: #10b981; font-weight: bold;">承認</span>
                            @elseif($correction->status === '却下')
                            <span style="color: #ef4444; font-weight: bold;">却下</span>
                            @else
                            <span style="color: #f59e0b; font-weight: bold;">申請中</span>
                            @endif
                        </td>
                        <td>
                            {{-- 勤務地が変更前と後で違えば「勤務地変更」、時間が変わっていれば「時間修正」など --}}
                            @if($correction->before_working_place !== $correction->after_working_place)
                            勤務地変更
                            @else
                            時間修正
                            @endif
                        </td>
                        <td style="color: #9ca3af; text-align: left; font-family: monospace;">
                            @if($correction->before_attendance) 出:{{ \Carbon\Carbon::parse($correction->before_attendance)->format('H:i') }}<br>@endif
                            @if($correction->before_leaving) 退:{{ \Carbon\Carbon::parse($correction->before_leaving)->format('H:i') }}<br>@endif
                            @if($correction->before_break_time) 憩始:{{ \Carbon\Carbon::parse($correction->before_break_time)->format('H:i') }}<br>@endif
                            @if($correction->before_break_end_time) 憩終:{{ \Carbon\Carbon::parse($correction->before_break_end_time)->format('H:i') }}<br>@endif
                            <span style="color: #6b7280;">📍場所: {{ $correction->before_working_place }}</span>
                        </td>
                        <td style="color: #111827; text-align: left; font-family: monospace; font-weight: bold;">
                            @if($correction->after_attendance) 出:{{ \Carbon\Carbon::parse($correction->after_attendance)->format('H:i') }}<br>@endif
                            @if($correction->after_leaving) 退:{{ \Carbon\Carbon::parse($correction->after_leaving)->format('H:i') }}<br>@endif
                            @if($correction->after_break_time) 憩始:{{ \Carbon\Carbon::parse($correction->after_break_time)->format('H:i') }}<br>@endif
                            @if($correction->after_break_end_time) 憩終:{{ \Carbon\Carbon::parse($correction->after_break_end_time)->format('H:i') }}<br>@endif
                            <span style="color: #1aaba8;">📍場所: {{ $correction->after_working_place }}</span>
                        </td>
                        <td style="text-align: left; max-width: 150px; white-space: pre-line;">{{ $correction->memo }}</td>
                        <td>{{ \Carbon\Carbon::parse($correction->updated_at)->format('Y/m/d H:i') }}</td>
                    </tr>
                    @empty
                    <tr class="correction-empty-row">
                        <td colspan="8" style="color: #9ca3af; padding: 12px;">申請履歴はありません。</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="modal-footer">
                <button type="button" class="btn-close-modal" id="close-fix-modal">閉じる</button>
                <button type="submit" class="btn-submit-modal active">申請</button>
            </div>
        </form>

    </div>
</div>
<form id="hidden-cancel-form" method="POST" style="display: none;">
    @csrf
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 取消ボタンがクリックされた時の処理
    document.body.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('btn-cancel-correction')) {
            const id = e.target.getAttribute('data-id');
            const form = document.getElementById('hidden-cancel-form');
            // ルートURLを動的に生成してセット
            form.action = `/dashboard/correction/${id}/cancel`;
            form.submit();
        }
    });
});
</script>
@endsection