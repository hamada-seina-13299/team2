<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>タイムカード | 勤怠管理</title>
    @vite(['resources/css/dashboard.css', 'resources/js/dashboard.js'])
</head>
<body>

    <div class="wrapper">
        <div class="sidebar">
            <a href="#" class="sidebar-icon active">🕒</a>
            <a href="#" class="sidebar-icon">📅</a>
            <a href="#" class="sidebar-icon">📊</a>
            <a href="#" class="sidebar-icon">📄</a>
            <a href="#" class="sidebar-icon">👤</a>
            <a href="#" class="sidebar-icon">🔍</a>
        </div>

        <div class="main-wrapper">
            <header class="top-header">
                <a href="#" class="notice-link">ⓘ お知らせ</a>
                <div class="user-info">
                    ❓ <span class="user-name">浜崎 康太朗</span>さん
                </div>
            </header>

            <div class="container">
                <div class="main-card">
                    <div class="status-bar">
                        @if($todayAttendance && is_null($todayAttendance->leaving))
                            ただいま勤務中
                        @else
                            未出勤
                        @endif
                    </div>

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
                                <div><strong>30日：</strong> 本社 (出社)</div>
                                <div><strong>勤務時間：</strong> 09:00 〜 17:30</div>
                                <div><strong>休憩時間：</strong> 12:00 〜 13:00</div>
                            </div>
                        </div>

                        <div class="card-right">
                            <div class="info-row">
                                <span class="info-label">勤務地</span>
                                <span class="info-value">
                                    {{ $todayAttendance->working_place ?? '本社' }} 
                                    <a href="#" class="change-link">変更 ⓘ</a>
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
                                <button class="btn-outline">🔄 打刻修正</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="history-section-title">打刻履歴 ⓘ</div>
                
                @php
                    $groupedHistory = $history->groupBy('punch_date');
                @endphp

                @forelse($groupedHistory as $date => $records)
                    <div class="date-line">{{ $date }}</div>
                    <div style="background-color: #fff; padding: 15px 30px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
                        @foreach($records as $record)
                            <div style="display: flex; gap: 40px; margin-bottom: 10px; font-size: 15px;">
                                <div>🟢 <strong>出勤:</strong> {{ \Carbon\Carbon::parse($record->attendance)->format('H:i') }} ({{ $record->working_place }})</div>
                                @if($record->leaving)
                                    <div>🔴 <strong>退勤:</strong> {{ \Carbon\Carbon::parse($record->leaving)->format('H:i') }}</div>
                                @endif
                                <div style="margin-left: auto;"><span style="background:#e5e7eb; padding:2px 8px; border-radius:4px; font-size:12px;">{{ $record->status }}</span></div>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <div class="date-line">履歴なし</div>
                @endforelse

            </div>
        </div>
    </div>

</body>
</html>