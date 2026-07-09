@extends('layouts.app')

@section('title', 'シフト一覧 | 勤怠管理システム')

{{-- 💡 必要なスタイルシートを親の @stack('styles') に送り込む --}}
@push('styles')
    @vite(['resources/css/shift.css', 'resources/css/shiftAdd.css'])
@endpush

@section('content')
    <div class="view-container">
        <div class="header-card">
            <div class="header-title-flex">
                <h1>シフト一覧</h1>
                
                <div id="floatingSubmitContainer" class="floating-submit-container">
                    @if($submissionStatus === '承認済み')
                        <div class="floating-bulk-btn floating-bulk-btn-green floating-bulk-btn-static">
                            ✅ 承認済みです
                        </div>
                    @elseif($submissionStatus === '申請中')
                        <form action="{{ route('shift.withdraw') }}" method="POST"
                            onsubmit="return confirm('{{ $year }}年{{ $month }}月分の提出を取り下げますか？');">
                            @csrf
                            <input type="hidden" name="year" value="{{ $year }}">
                            <input type="hidden" name="month" value="{{ $month }}">
                            <button type="submit" class="floating-bulk-btn floating-bulk-btn-green">
                                📮 申請中（取り下げる）
                            </button>
                        </form>
                    @else
                        <form action="{{ route('shift.submit') }}" method="POST"
                            onsubmit="return confirm('{{ $year }}年{{ $month }}月分のシフトを提出しますか？');">
                            @csrf
                            <input type="hidden" name="year" value="{{ $year }}">
                            <input type="hidden" name="month" value="{{ $month }}">
                            <button type="submit" class="floating-bulk-btn floating-bulk-btn-green">
                                @if($submissionStatus === '差し戻し')
                                    🔁 差し戻されました（再提出する）
                                @else
                                    📤 シフトを提出する
                                @endif
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="month-pager">
                <a href="{{ route('shift.list', ['year' => $month == 1 ? $year - 1 : $year, 'month' => $month == 1 ? 12 : $month - 1]) }}" 
                class="pager-btn">
                    &lt;
                </a>
                <span class="pager-current">{{ $year }}年{{ $month }}月</span>
                <a href="{{ route('shift.list', ['year' => $month == 12 ? $year + 1 : $year, 'month' => $month == 12 ? 1 : $month + 1]) }}" 
                class="pager-btn">
                    &gt;
                </a>
            </div>

            @if($lastMaster)
                <div class="selected-master-container">
                    <div class="selected-master-badge">
                        <span class="selected-master-label">📌 選択中のマスタ：{{ $lastMaster->name }}</span>
                        <form action="{{ route('shift.master.clear') }}" method="POST" class="m-0-inline">
                            @csrf
                            <button type="submit"
                                class="btn-clear-master"
                                title="選択を解除する">
                                ✕
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        {{-- 成功フラッシュメッセージ表示 --}}
        @if (session('success'))
            <div class="alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="table-wrapper">
            <table class="shift-list-table">
                <thead>
                    <tr class="table-head-row">
                        <th class="th-select">選択</th>
                        <th class="th-date">日付</th>
                        <th class="th-time">出勤時刻</th>
                        <th class="th-time">退勤時刻</th>
                        <th class="th-place">勤務地</th>
                        <th class="th-action">修正</th>
                        <th class="th-action">削除</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($days as $day)
                        @php
                            $isSunday = $day['date']->isSunday();
                            $isSaturday = $day['date']->isSaturday();
                            $isHoliday = $day['is_holiday'] ?? false;

                            // 💡 祝日は日曜と同じ扱い（赤色）にする
                            $dateColor = 'text-gray-800';
                            if ($isSunday || $isHoliday) $dateColor = 'text-red-600';
                            elseif ($isSaturday) $dateColor = 'text-blue-600';

                            $rowBg = 'shift-row-default';
                            if ($isSunday || $isHoliday) $rowBg = 'shift-row-sunday';
                            elseif ($isSaturday) $rowBg = 'shift-row-saturday';

                            $weeks = ['日', '月', '火', '水', '木', '金', '土'];
                            $japaneseWeek = $weeks[$day['date']->dayOfWeek];
                        @endphp

                        <tr class="{{ $rowBg }} table-body-row" id="shift-row-{{ $day['date']->format('Y-m-d') }}" data-date-color="{{ $dateColor }}">
                            <td class="cell-checkbox">
                                @if(!$day['shift'])
                                    <input type="checkbox" class="shift-bulk-checkbox form-checkbox" value="{{ $day['date']->format('Y-m-d') }}" data-day-of-week="{{ $day['date']->dayOfWeek }}" data-is-holiday="{{ $isHoliday ? '1' : '0' }}">
                                @else
                                    <span class="cell-hyphen">-</span>
                                @endif
                            </td>

                            <td class="cell-date-text {{ $dateColor }}" @if($isHoliday) title="{{ $day['holiday_name'] }}" @endif>
                                {{ $day['date']->format('d') }} ({{ $japaneseWeek }})
                                @if($isHoliday)
                                    <span class="holiday-badge-text">{{ $day['holiday_name'] }}</span>
                                @endif
                            </td>
                            
                            @php
                                $displayAttendance = $day['shift']
                                    ? ($day['shift']->attendance_edit ?? $day['shift']->shiftMaster->attendance)
                                    : null;
                                $displayLeaving = $day['shift']
                                    ? ($day['shift']->leaving_edit ?? $day['shift']->shiftMaster->leaving)
                                    : null;
                            @endphp
                            <td class="cell-attendance">
                                @if($day['shift'])
                                    <span class="attendance-text">{{ date('H:i', strtotime($displayAttendance)) }}</span>
                                    <input type="time" class="attendance-input hidden form-input-time-inline" value="{{ date('H:i', strtotime($displayAttendance)) }}">
                                @else
                                    --:--
                                @endif
                            </td>
                            <td class="cell-leaving">
                                @if($day['shift'])
                                    <span class="leaving-text">{{ date('H:i', strtotime($displayLeaving)) }}</span>
                                    <input type="time" class="leaving-input hidden form-input-time-inline" value="{{ date('H:i', strtotime($displayLeaving)) }}">
                                @else
                                    --:--
                                @endif
                            </td>
                            <td class="cell-place">
                                @if($day['shift'])
                                    <span class="place-text">
                                        📍<span class="place-name-inner">{{ $day['shift']->shiftMaster->name }}</span>
                                    </span>
                                    <select class="place-select hidden form-select-inline">
                                        @foreach ($shiftMasters as $master)
                                            <option value="{{ $master->id }}"
                                                data-attendance="{{ date('H:i', strtotime($master->attendance)) }}"
                                                data-leaving="{{ date('H:i', strtotime($master->leaving)) }}"
                                                {{ $day['shift']->master_id == $master->id ? 'selected' : '' }}>
                                                {{ $master->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <span class="cell-hyphen-light">--</span>
                                @endif
                            </td>
                            <td class="cell-edit">
                                @if($day['shift'])
                                    <button type="button"
                                        class="btn-edit edit-shift-btn"
                                        data-shift-id="{{ $day['shift']->id }}"
                                        data-editing="0">
                                        修正
                                    </button>
                                @elseif($lastMaster)
                                    <form action="{{ route('shift.store') }}" method="POST" class="quick-add-form m-0-inline">
                                        @csrf
                                        <input type="hidden" name="target_date" value="{{ $day['date']->format('Y-m-d') }}">
                                        <input type="hidden" name="master_id" value="{{ $lastMaster->id }}">
                                        <button type="submit" class="add-shift-btn {{ $dateColor }}" title="「{{ $lastMaster->name }}」で追加します">
                                            + シフトを追加
                                        </button>
                                    </form>
                                @else
                                    <button type="button"
                                        data-date="{{ $day['date']->format('Y-m-d') }}"
                                        class="open-add-modal-btn add-shift-btn {{ $dateColor }}">
                                        + シフトを追加
                                    </button>
                                @endif
                            </td>
                            <td class="cell-delete">
                                @if($day['shift'])
                                    <form action="{{ route('shift.delete') }}" method="POST"
                                        data-confirm-date="{{ $day['date']->format('m月d日') }}"
                                        class="delete-shift-form m-0-inline">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="shift_id" value="{{ $day['shift']->id }}">
                                        <button type="submit" class="btn-delete">削除</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- シフト追加モーダル --}}
    <div id="addModal" class="modal-overlay">
        <div class="modal-container">

            <div class="modal-title-bar">
                <h2>シフトを追加する</h2>
                <button type="button" onclick="closeAddModal()" class="btn-modal-close-icon">✕</button>
            </div>

            <div class="modal-scroll-content">
                @if ($errors->any())
                    <div class="alert-danger-box">
                        <ul class="error-list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="shiftAddForm" action="{{ route('shift.store') }}" method="POST">
                    @csrf
                    <input type="hidden" id="formMode" name="form_mode" value="{{ old('form_mode', 'select') }}">

                    <div id="modalTargetDateGroup" class="form-control-group">
                        <label class="form-field-label">対象日 <span class="required-star">*</span></label>
                        <div class="date-input-wrapper">
                            <input type="date" id="modalTargetDate" name="target_date" value="{{ old('target_date') }}" class="form-input-date">
                        </div>
                    </div>

                    <div id="bulkDateMessageGroup" class="hidden bulk-msg-banner">
                        <span class="bulk-msg-icon">📅</span>
                        <p class="bulk-msg-text">
                            <span id="modalBulkCount" class="bulk-msg-count-num">0</span> 件のシフトをまとめて追加します。
                        </p>
                    </div>

                    <div class="form-control-group">
                        <label class="form-field-label">勤務地名 <span class="required-star">*</span></label>

                        <div class="master-select-header">
                            <span id="masterSelectHelpText" class="form-field-help">登録済みのマスタから選択してください</span>
                            <button type="button" id="toggleNewMaster" class="btn-add-new-master">
                                ＋ 新規追加
                            </button>
                        </div>

                        <div id="masterScrollArea" class="master-scroll-area-box">
                            @foreach ($shiftMasters as $master)
                                <div class="master-option-card"
                                     data-master-id="{{ $master->id }}"
                                     data-attendance="{{ $master->attendance }}"
                                     data-leaving="{{ $master->leaving }}">
                                    <span class="master-option-name">{{ $master->name }}</span>
                                    <span class="master-option-time">
                                        ({{ date('H:i', strtotime($master->attendance)) }} 〜 {{ date('H:i', strtotime($master->leaving)) }})
                                    </span>
                                    @if($master->user_id === Auth::id())
                                        <button type="button"
                                                data-master-id="{{ $master->id }}"
                                                data-master-name="{{ $master->name }}"
                                                class="delete-master-btn">
                                            ✕
                                        </button>
                                    @endif
                                </div>
                            @endforeach

                            @if($shiftMasters->isEmpty())
                                <p class="master-empty-text">登録されているマスタがありません。「新規追加」から登録してください。</p>
                            @endif
                        </div>

                        <input type="hidden" id="masterSelect" name="master_id" value="{{ old('master_id') }}">

                        <div id="newMasterFields" class="hidden new-master-form-block">
                            <p class="new-master-form-title">新しい勤務地マスタを登録します</p>

                            <div class="form-control-sub-group">
                                <label class="form-field-label-sub">勤務地名 <span class="required-star">*</span></label>
                                <input type="text" name="new_working_place" value="{{ old('new_working_place') }}" class="form-input-text">
                            </div>

                            <div class="form-row-flex">
                                <div class="flex-1">
                                    <label class="form-field-label-sub">出勤時刻 <span class="required-star">*</span></label>
                                    <input type="time" name="new_attendance" value="{{ old('new_attendance') }}" class="form-input-time">
                                </div>
                                <div class="flex-1">
                                    <label class="form-field-label-sub">退勤時刻 <span class="required-star">*</span></label>
                                    <input type="time" name="new_leaving" value="{{ old('new_leaving') }}" class="form-input-time">
                                </div>
                            </div>

                            <div class="form-control-sub-group">
                                <label class="form-field-label-sub">休憩時間</label>
                                <select name="new_break_time" class="form-select-custom">
                                    <option value="01:00" {{ old('new_break_time', '01:00') == '01:00' ? 'selected' : '' }}>1時間休憩をつける</option>
                                    <option value="00:00" {{ old('new_break_time') == '00:00' ? 'selected' : '' }}>つけない</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="displayTimeGroup" class="form-row-flex row-display-time">
                        <div class="flex-1">
                            <label class="form-field-label-disabled">出勤時刻(表示用)</label>
                            <input type="time" id="attendanceDisplay" disabled class="form-input-disabled">
                        </div>
                        <div class="flex-1">
                            <label class="form-field-label-disabled">退勤時刻(表示用)</label>
                            <input type="time" id="leavingDisplay" disabled class="form-input-disabled">
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer-bar">
                <button type="submit" id="submitShiftBtn" form="shiftAddForm" class="btn-submit-primary">
                    ＋ シフトを追加する
                </button>

                <button type="submit" id="submitMasterBtn" form="shiftAddForm" class="hidden btn-submit-secondary">
                    上記の入力内容でマスタを登録し、シフトに追加
                </button>
            </div>
        </div>
    </div>

    <form id="masterDeleteForm" action="{{ route('shift.master.delete') }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
        <input type="hidden" id="deleteMasterId" name="master_id">
    </form>

    <span id="error-data"
        data-has-errors="{{ $errors->any() ? 'true' : 'false' }}"
        data-has-fields-error="{{ (old('form_mode') === 'new_master' && ($errors->has('new_working_place') || $errors->has('new_attendance') || $errors->has('new_leaving'))) ? 'true' : 'false' }}"
        data-saved-date="{{ old('target_date') }}"
        data-is-bulk="{{ old('target_dates') ? 'true' : 'false' }}"
        data-bulk-count="{{ old('target_dates') ? count(old('target_dates')) : 0 }}"
        data-shift-delete-url="{{ route('shift.delete') }}"
        data-shift-update-time-url="{{ route('shift.updateTime') }}"
        class="hidden">
    </span>

    <div id="floatingBulkBtnContainer" class="floating-bulk-btn-container">
        <div class="floating-btn-swap">
            <button type="button" id="selectWeekdaysBtn" class="floating-bulk-btn floating-btn-slot">
                📅 土日祝を除くすべてにチェックを入れる
            </button>
            <button type="button" id="openBulkModalBtn" class="floating-bulk-btn floating-btn-slot btn-slot-hidden">
                選択した <span id="selectedCount" class="selected-badge-count">0</span> 日分をまとめて追加
            </button>
        </div>
    </div>
@endsection

{{-- 💡 JavaScriptを親の @stack('scripts') に送り込む --}}
@push('scripts')
    @vite(['resources/js/shift.js'])
@endpush