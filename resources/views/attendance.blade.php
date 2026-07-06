@extends('layouts.app')

@section('content')
<div class="attendance-page">

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
        <span class="status-badge status-{{ $summary['monthly_status'] }}">{{ $summary['monthly_status'] }}</span>

        @if ($summary['can_submit'])
            <form action="{{ route('attendance.submit') }}" method="POST" class="inline-form"
                  onsubmit="return confirm('この月の勤怠を申請します。よろしいですか？');">
                @csrf
                <input type="hidden" name="year" value="{{ $currentMonth->year }}">
                <input type="hidden" name="month" value="{{ $currentMonth->month }}">
                <button type="submit" class="btn-primary">申請する</button>
            </form>
        @endif
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    {{-- 勤怠集計 --}}
    <div class="summary-card">
        <div class="summary-title">勤怠集計</div>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">合計勤務時間</div>
                <div class="summary-value">{{ $summary['total_worked_time'] }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">所定労働時間</div>
                <div class="summary-value">{{ $summary['scheduled_time'] }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">出勤日数</div>
                <div class="summary-value">{{ $summary['working_days'] }}日</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">交通費合計</div>
                <div class="summary-value">{{ number_format($summary['total_commute']) }}円</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">有給休暇使用日数</div>
                <div class="summary-value">{{ $summary['paid_leave_days'] }}日</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">半休使用日数</div>
                <div class="summary-value">{{ $summary['half_day_leave_days'] }}日</div>
            </div>
        </div>
    </div>

    {{-- 勤務表 --}}
    <div class="attendance-card">
        <div class="card-header">
            <span class="card-icon">📋</span>
            <span class="card-title">勤務表</span>
        </div>

        {{-- タブ --}}
        <div class="tabs">
            <button type="button" class="tab-btn active" onclick="switchTab('main', this)">主勤怠情報</button>
            <button type="button" class="tab-btn" onclick="switchTab('other', this)">その他</button>
        </div>

        {{-- 主勤怠情報タブ：workings（実績）を表示 --}}
        <div id="tab-main" class="tab-panel">
            <div class="table-wrapper">
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th class="col-date">日付</th>
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
                                $isSaturday = $date->dayOfWeek === \Carbon\Carbon::SATURDAY;
                                $isSunday   = $date->dayOfWeek === \Carbon\Carbon::SUNDAY;
                                $rowClass = $isSunday ? 'row-sunday' : ($isSaturday ? 'row-saturday' : '');

                                $displayAttendance = '';
                                $displayLeaving = '';
                                if ($w && $w->attendance) {
                                    $attTime = \Carbon\Carbon::parse($w->attendance);
                                    if ($master && $master->attendance) {
                                        $masterAtt = \Carbon\Carbon::parse($master->attendance);
                                        if ($attTime->lt($masterAtt)) {
                                            $attTime = $masterAtt;
                                        }
                                    }
                                    $displayAttendance = $attTime->format('H:i');
                                }
                                if ($w && $w->leaving) {
                                    $leavTime = \Carbon\Carbon::parse($w->leaving);
                                    if ($master && $master->leaving) {
                                        $masterLeav = \Carbon\Carbon::parse($master->leaving);
                                        $masterLeavPlus1 = $masterLeav->copy()->addHour();
                                        if ($leavTime->gt($masterLeav) && $leavTime->lt($masterLeavPlus1)) {
                                            $leavTime = $masterLeav;
                                        }
                                    }
                                    $displayLeaving = $leavTime->format('H:i');
                                }
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td class="col-date">
                                    {{ $date->format('n月j日') }}（{{ ['日','月','火','水','木','金','土'][$date->dayOfWeek] }}）
                                </td>
                                <td>{{ $displayAttendance }}</td>
                                <td>{{ $displayLeaving }}</td>
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
                                $isSaturday = $date->dayOfWeek === \Carbon\Carbon::SATURDAY;
                                $isSunday   = $date->dayOfWeek === \Carbon\Carbon::SUNDAY;
                                $rowClass = $isSunday ? 'row-sunday' : ($isSaturday ? 'row-saturday' : '');

                                $dayType = '平日';
                                if ($isSunday) {
                                    $dayType = '法定休日';
                                } elseif ($isSaturday) {
                                    $dayType = '法定外休日';
                                }

                                $lateTime = '';
                                $earlyLeaveTime = '';
                                $otherDeduction = '';

                                foreach ($requests as $req) {
                                    if (in_array($req->request_type, ['遅刻', '有事遅刻'])) {
                                        $lateTime = $req->request_time ? \Carbon\Carbon::parse($req->request_time)->format('H:i') : '';
                                    } elseif (in_array($req->request_type, ['早退', '有事早退'])) {
                                        $earlyLeaveTime = $req->request_time ? \Carbon\Carbon::parse($req->request_time)->format('H:i') : '';
                                    } elseif (in_array($req->request_type, ['欠勤', '有給', '半休'])) {
                                        $otherDeduction = $req->request_type;
                                    }
                                }

                                // 休憩開始・終了・合計は workings に該当カラムが無いため、シフトマスタ（予定）から算出。
                                // その日の退勤打刻が済むまでは空欄のままにする（未確定の日には表示しない）。
                                $breakStart = null;
                                $breakEnd = null;
                                $breakDurationMinutes = null;

                                if ($w && $w->leaving && $master && $master->break_start_time && $master->break_time) {
                                    $breakStart = \Carbon\Carbon::parse($master->break_start_time);
                                    $breakDurationMinutes = \Carbon\Carbon::parse('00:00')->diffInMinutes(\Carbon\Carbon::parse($master->break_time));
                                    $breakEnd = $breakStart->copy()->addMinutes($breakDurationMinutes);
                                }
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td class="col-date">
                                    {{ $date->format('n月j日') }}（{{ ['日','月','火','水','木','金','土'][$date->dayOfWeek] }}）
                                </td>
                                <td>{{ $dayType }}</td>
                                <td>
                                    <div class="action-buttons-cell" style="display: flex; gap: 4px; align-items: center;">
                                        @if($requests->count() > 0)
                                            @foreach($requests as $req)
                                                <button type="button" class="icon-btn js-edit-btn"
                                                    data-id="{{ $req->id }}"
                                                    data-date="{{ $date->format('Y-m-d') }}"
                                                    data-type="{{ $req->request_type }}"
                                                    data-time="{{ $req->request_time ? \Carbon\Carbon::parse($req->request_time)->format('H:i') : '' }}"
                                                    data-memo="{{ $req->memo }}"
                                                    title="編集 ({{ $req->request_type }})">✏️</button>
                                                <form action="{{ route('attendance.destroy', $req->id) }}" method="POST"
                                                      class="inline-form" onsubmit="return confirm('この申請を削除しますか？');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="icon-btn" title="削除">🗑️</button>
                                                </form>
                                            @endforeach
                                        @else
                                            <button type="button" class="icon-btn js-create-btn"
                                                data-date="{{ $date->format('Y-m-d') }}"
                                                title="新規登録">➕</button>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($master)
                                        {{ $master->name ?? '' }}
                                    @endif
                                </td>
                                <td>
                                    @if($w && $w->attendance)
                                        @if($lateTime) <span class="late-badge" style="background:#ffecec; color:#d64b5f; padding:2px 4px; border-radius:3px; font-size:11px; margin-right:2px;">遅</span>@endif{{ \Carbon\Carbon::parse($w->attendance)->format('H:i') }}
                                    @endif
                                </td>
                                <td>{{ $w && $w->leaving ? \Carbon\Carbon::parse($w->leaving)->format('H:i') : '' }}</td>
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
    </div>
</div>

{{-- 新規登録／編集モーダル --}}
<div id="attendanceModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <span id="modalTitle">勤怠申請</span>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>

        <form id="attendanceForm" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="method_field" value="">

            <div class="form-group">
                <label for="target_date">対象日</label>
                <input type="date" id="target_date" name="target_date" required>
            </div>

            <div class="form-group">
                <label for="request_type">申請種別</label>
                <select id="request_type" name="request_type" required onchange="updateTimeField()">
                    <option value="">選択してください</option>
                    <option value="遅刻">遅刻</option>
                    <option value="早退">早退</option>
                    <option value="欠勤">欠勤</option>
                    <option value="有給">有給</option>
                    <option value="半休">半休</option>
                    <option value="残業">残業</option>
                    <option value="有事遅刻">有事遅刻</option>
                    <option value="有事早退">有事早退</option>
                </select>
            </div>

            <div class="form-group" id="request_time_wrapper">
                <label for="request_time" id="request_time_label">申請時刻</label>
                <input type="time" id="request_time" name="request_time">
            </div>

            <div class="form-group">
                <label for="memo">メモ</label>
                <input type="text" id="memo" name="memo" maxlength="255" required>
            </div>

            <div class="form-group">
                <label for="attachment">添付ファイル</label>
                <input type="file" id="attachment" name="attachment">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">キャンセル</button>
                <button type="submit" class="btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>

<script>
    const storeUrl = "{{ route('attendance.store') }}";
    const updateUrlBase = "{{ url('attendance') }}";

    const timeLabels = {
        '遅刻':   '遅刻時刻',
        '早退':   '早退時刻',
        '欠勤':   null,
        '有給':   null,
        '半休':   '半休開始時刻',
        '残業':   '残業終了時刻',
        '有事遅刻': '遅刻時刻',
        '有事早退': '早退時刻',
    };

    function updateTimeField() {
        const type = document.getElementById('request_type').value;
        const label = timeLabels[type];
        const wrapper = document.getElementById('request_time_wrapper');
        const input = document.getElementById('request_time');

        if (type && label === null) {
            wrapper.style.display = 'none';
            input.required = false;
            input.value = '';
        } else {
            wrapper.style.display = '';
            input.required = true;
            document.getElementById('request_time_label').textContent = label || '申請時刻';
        }
    }

    function switchTab(tab, btn) {
        document.querySelectorAll('.tab-panel').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        document.getElementById('tab-' + tab).style.display = 'block';
        btn.classList.add('active');
    }

    function openCreateModal(dateStr) {
        document.getElementById('modalTitle').textContent = '勤怠申請の新規登録';
        document.getElementById('attendanceForm').action = storeUrl;
        document.getElementById('method_field').value = '';
        document.getElementById('target_date').value = dateStr || '';
        document.getElementById('request_type').value = '';
        document.getElementById('request_time').value = '';
        document.getElementById('memo').value = '';
        document.getElementById('attachment').value = '';
        updateTimeField();
        document.getElementById('attendanceModal').style.display = 'flex';
    }

    function openEditModal(id, dateStr, type, time, memo) {
        document.getElementById('modalTitle').textContent = '勤怠申請の編集';
        document.getElementById('attendanceForm').action = updateUrlBase + '/' + id;
        document.getElementById('method_field').value = 'PUT';
        document.getElementById('target_date').value = dateStr;
        document.getElementById('request_type').value = type;
        document.getElementById('request_time').value = time;
        document.getElementById('memo').value = memo;
        document.getElementById('attachment').value = '';
        updateTimeField();
        document.getElementById('attendanceModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('attendanceModal').style.display = 'none';
    }

    document.querySelectorAll('.js-edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openEditModal(
                btn.dataset.id,
                btn.dataset.date,
                btn.dataset.type,
                btn.dataset.time,
                btn.dataset.memo
            );
        });
    });

    document.querySelectorAll('.js-create-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openCreateModal(btn.dataset.date);
        });
    });
</script>

<style>
    .attendance-page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 16px;
        font-family: "Hiragino Kaku Gothic ProN", "Meiryo", sans-serif;
        color: #333;
    }

    .user-info {
        display: flex;
        align-items: baseline;
        gap: 10px;
        margin-bottom: 8px;
    }

    .user-name {
        font-weight: 700;
        font-size: 16px;
    }

    .user-dept {
        font-size: 13px;
        color: #777;
    }

    .attendance-header {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        margin-bottom: 8px;
    }

    .month-title {
        font-size: 20px;
        font-weight: 700;
        margin: 0;
    }

    .month-nav-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #f1f1f1;
        color: #333;
        text-decoration: none;
        font-size: 14px;
    }

    .month-nav-btn:hover {
        background: #e0e0e0;
    }

    .alert-success {
        background: #e6f7ee;
        color: #1a7f4f;
        border: 1px solid #b7e4c9;
        padding: 10px 14px;
        border-radius: 6px;
        margin-bottom: 16px;
    }

    .summary-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        padding: 16px 18px;
        margin-bottom: 16px;
    }

    .summary-title {
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 12px;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 12px;
    }

    .summary-item {
        background: #f7fbfb;
        border-radius: 8px;
        padding: 10px;
        text-align: center;
    }

    .summary-label {
        font-size: 11px;
        color: #777;
        margin-bottom: 4px;
    }

    .summary-value {
        font-size: 16px;
        font-weight: 700;
        color: #12b3ab;
    }

    .attendance-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .card-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 14px 18px;
        border-bottom: 1px solid #eee;
    }

    .card-title {
        font-weight: 700;
        font-size: 15px;
        flex: 1;
    }

    .tabs {
        display: flex;
        border-bottom: 1px solid #eee;
        padding: 0 12px;
    }

    .tab-btn {
        background: none;
        border: none;
        padding: 10px 16px;
        font-size: 13px;
        color: #777;
        cursor: pointer;
        border-bottom: 2px solid transparent;
    }

    .tab-btn.active {
        color: #12b3ab;
        border-bottom-color: #12b3ab;
        font-weight: 700;
    }

    .table-wrapper {
        overflow-x: auto;
        padding: 12px 18px 18px;
    }

    .attendance-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .attendance-table th {
        background: #fafafa;
        color: #666;
        font-weight: 600;
        text-align: left;
        padding: 10px 12px;
        border-bottom: 1px solid #eee;
        white-space: nowrap;
    }

    .attendance-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #f2f2f2;
        vertical-align: middle;
        white-space: nowrap;
    }

    .other-detail-table th {
        background: #eef8f8;
        color: #444;
    }

    .row-saturday {
        background: #eaf6fb;
    }

    .row-saturday .col-date {
        color: #1c7fb0;
    }

    .row-sunday {
        background: #fdeef0;
    }

    .row-sunday .col-date {
        color: #d64b5f;
    }

    .col-date {
        white-space: nowrap;
        width: 140px;
    }

    .status-badge {
        display: inline-block;
        border-radius: 20px;
        padding: 4px 16px;
        font-size: 13px;
        font-weight: 700;
    }

    .status-未申請 {
        background: #f1f1f1;
        color: #777;
    }

    .status-申請済み {
        background: #eef7f6;
        color: #12b3ab;
    }

    .status-承認 {
        background: #e6f7ee;
        color: #1a7f4f;
    }

    .status-却下 {
        background: #fdeef0;
        color: #d64b5f;
    }

    .btn-primary {
        background: #12b3ab;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 8px 14px;
        font-size: 13px;
        cursor: pointer;
    }

    .btn-primary:hover {
        background: #0f9a92;
    }

    .btn-secondary {
        background: #f1f1f1;
        color: #333;
        border: none;
        border-radius: 6px;
        padding: 8px 14px;
        font-size: 13px;
        cursor: pointer;
    }

    .icon-btn {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 14px;
        padding: 2px;
    }

    .inline-form {
        display: inline;
    }

    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.4);
        align-items: center;
        justify-content: center;
        z-index: 50;
    }

    .modal-box {
        background: #fff;
        border-radius: 10px;
        width: 90%;
        max-width: 420px;
        padding: 20px;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 700;
        margin-bottom: 16px;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 18px;
        cursor: pointer;
    }

    .form-group {
        margin-bottom: 14px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .form-group label {
        font-size: 12px;
        color: #666;
    }

    .form-group input,
    .form-group select {
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 8px 10px;
        font-size: 13px;
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-top: 8px;
    }
</style>
@endsection