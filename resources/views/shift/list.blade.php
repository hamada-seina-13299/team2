@extends('layouts.app')

@section('title', 'シフト一覧 | 勤怠管理システム')

{{-- 💡 必要なスタイルシートを親の @stack('styles') に送り込む --}}
@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
    @vite(['resources/css/shift.css'])
@endpush

@section('content')
    <div class="w-full p-6 bg-gray-50 min-h-screen rounded-xl">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <h1 class="text-lg font-bold mb-2 text-gray-800">シフト一覧</h1>

            <div class="flex items-center justify-center gap-1">
                <a href="{{ route('shift.list', ['year' => $month == 1 ? $year - 1 : $year, 'month' => $month == 1 ? 12 : $month - 1]) }}" 
                   class="w-10 h-10 border border-gray-200 rounded-lg bg-white flex items-center justify-center shadow-sm hover:bg-gray-50 text-gray-500 transition-colors">
                    &lt;
                </a>
                <span class="text-2xl font-bold px-4 text-gray-800">{{ $year }}年{{ $month }}月</span>
                <a href="{{ route('shift.list', ['year' => $month == 12 ? $year + 1 : $year, 'month' => $month == 12 ? 1 : $month + 1]) }}" 
                   class="w-10 h-10 border border-gray-200 rounded-lg bg-white flex items-center justify-center shadow-sm hover:bg-gray-50 text-gray-500 transition-colors">
                    &gt;
                </a>
            </div>

            @if($lastMaster)
                <div class="mt-4 flex items-center justify-center">
                    <div class="inline-flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-full pl-4 pr-2 py-1.5">
                        <span class="text-sm font-semibold text-blue-800">📌 選択中のマスタ：{{ $lastMaster->name }}</span>
                        <form action="{{ route('shift.master.clear') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit"
                                class="w-5 h-5 flex items-center justify-center rounded-full text-blue-400 hover:text-red-500 hover:bg-red-50 text-xs font-bold transition-colors"
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
            <div class="mb-4 p-4 bg-emerald-100 text-emerald-700 rounded-xl font-semibold shadow-sm text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto w-full">
            <table class="w-full border-collapse table-fixed min-w-[650px]">
                <thead>
                    <tr class="bg-gray-50/75 border-b border-gray-100">
                        <th class="p-4 text-sm font-semibold text-gray-600 text-center w-[10%]">選択</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 text-center w-[12%]">日付</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 text-center w-[15%]">出勤時刻</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 text-center w-[15%]">退勤時刻</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 text-center w-[24%]">勤務地</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 text-center w-[12%]">修正</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 text-center w-[12%]">削除</th>
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

                        <tr class="border-b border-gray-100 {{ $rowBg }} h-14 transition-colors" id="shift-row-{{ $day['date']->format('Y-m-d') }}" data-date-color="{{ $dateColor }}">
                            <td class="p-3 text-center align-middle cell-checkbox">
                                @if(!$day['shift'])
                                    <input type="checkbox" class="shift-bulk-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer" value="{{ $day['date']->format('Y-m-d') }}" data-day-of-week="{{ $day['date']->dayOfWeek }}" data-is-holiday="{{ $isHoliday ? '1' : '0' }}">
                                @else
                                    <span class="text-xs text-gray-300 select-none">-</span>
                                @endif
                            </td>

                            <td class="p-3 text-sm {{ $dateColor }} font-semibold text-center align-middle" @if($isHoliday) title="{{ $day['holiday_name'] }}" @endif>
                                {{ $day['date']->format('d') }} ({{ $japaneseWeek }})
                                @if($isHoliday)
                                    <span class="block text-[10px] font-normal text-red-400 leading-tight truncate">{{ $day['holiday_name'] }}</span>
                                @endif
                            </td>
                            
                            <td class="p-3 text-sm text-gray-600 text-center align-middle cell-attendance">
                                @if($day['shift'])
                                    {{ date('H:i', strtotime($day['shift']->shiftMaster->attendance)) }}
                                @else
                                    --:--
                                @endif
                            </td>
                            <td class="p-3 text-sm text-gray-600 text-center align-middle cell-leaving">
                                @if($day['shift'])
                                    {{ date('H:i', strtotime($day['shift']->shiftMaster->leaving)) }}
                                @else
                                    --:--
                                @endif
                            </td>
                            <td class="p-3 text-sm text-gray-600 text-center align-middle truncate cell-place">
                                @if($day['shift'])
                                    <span class="inline-flex items-center justify-center gap-1 w-full max-w-full truncate">
                                        📍<span class="truncate font-medium text-gray-700">{{ $day['shift']->shiftMaster->name }}</span>
                                    </span>
                                @else
                                    <span class="text-gray-300">--</span>
                                @endif
                            </td>
                            <td class="p-3 text-sm text-center align-middle cell-edit">
                                @if($day['shift'])
                                    <a href="{{ route('shiftcorrection.index', ['shift_id' => $day['shift']->id]) }}" class="btn-edit inline-block text-center">
                                        修正
                                    </a>
                                @elseif($lastMaster)
                                    <form action="{{ route('shift.store') }}" method="POST" class="quick-add-form inline m-0">
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
                            <td class="p-3 text-sm text-center align-middle cell-delete">
                                @if($day['shift'])
                                    <form action="{{ route('shift.delete') }}" method="POST"
                                        data-confirm-date="{{ $day['date']->format('m月d日') }}"
                                        class="delete-shift-form inline-block m-0">
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

            <div class="flex justify-between items-center mb-4 flex-shrink-0">
                <h2 class="font-bold text-lg text-gray-800">シフトを追加する</h2>
                <button type="button" onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600 text-xl transition-colors">✕</button>
            </div>

            <div class="flex-1 overflow-y-auto pr-1 mb-4">
                @if ($errors->any())
                    <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-sm">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="shiftAddForm" action="{{ route('shift.store') }}" method="POST">
                    @csrf
                    <input type="hidden" id="formMode" name="form_mode" value="{{ old('form_mode', 'select') }}">

                    <div id="modalTargetDateGroup" class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">対象日 <span class="text-red-500">*</span></label>
                        <input type="date" id="modalTargetDate" name="target_date" value="{{ old('target_date') }}"
                               class="w-full border border-gray-300 rounded-lg p-2.5 bg-white text-gray-800 shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all outline-none text-sm">
                    </div>

                    <div id="bulkDateMessageGroup" class="hidden mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-center gap-2 animate-fade-in">
                        <span class="text-blue-600 text-base">📅</span>
                        <p class="text-sm font-semibold text-blue-800">
                            <span id="modalBulkCount" class="font-extrabold text-blue-600 underline text-base">0</span> 件のシフトをまとめて追加します。
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-1 text-sm font-medium text-gray-700">勤務地名 <span class="text-red-500">*</span></label>

                        <div class="flex gap-2 mb-2 items-center justify-between">
                            <span class="text-xs text-gray-400 transition-all" id="masterSelectHelpText">登録済みのマスタから選択してください</span>
                            <button type="button" id="toggleNewMaster"
                                class="border border-gray-300 rounded-lg px-3 py-1.5 bg-white whitespace-nowrap hover:bg-gray-50 text-xs shadow-sm transition-colors font-semibold text-gray-700">
                                ＋ 新規追加
                            </button>
                        </div>

                        <div id="masterScrollArea" class="border border-gray-200 rounded-xl p-2 bg-gray-50 max-h-[220px] overflow-y-auto space-y-2 shadow-inner">
                            @foreach ($shiftMasters as $master)
                                <div class="master-option-card"
                                     data-master-id="{{ $master->id }}"
                                     data-attendance="{{ $master->attendance }}"
                                     data-leaving="{{ $master->leaving }}">
                                    <span class="font-bold text-gray-800 text-sm block mb-0.5">{{ $master->name }}</span>
                                    <span class="text-xs text-gray-500">
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
                                <p class="text-gray-400 text-xs text-center py-4">登録されているマスタがありません。「新規追加」から登録してください。</p>
                            @endif
                        </div>

                        <input type="hidden" id="masterSelect" name="master_id" value="{{ old('master_id') }}">

                        <div id="newMasterFields" class="hidden border rounded-xl p-4 bg-gray-50 mt-3 shadow-inner">
                            <p class="text-xs font-bold text-emerald-600 mb-3">新しい勤務地マスタを登録します</p>

                            <div class="mb-3">
                                <label class="block mb-1 text-xs font-medium text-gray-700">勤務地名 <span class="text-red-500">*</span></label>
                                <input type="text" name="new_working_place" value="{{ old('new_working_place') }}" class="w-full border rounded-lg p-2 bg-white text-sm">
                            </div>

                            <div class="flex gap-4 mb-3">
                                <div class="flex-1">
                                    <label class="block mb-1 text-xs font-medium text-gray-700">出勤時刻 <span class="text-red-500">*</span></label>
                                    <input type="time" name="new_attendance" value="{{ old('new_attendance') }}" class="w-full border rounded-lg p-2 bg-white text-sm">
                                </div>
                                <div class="flex-1">
                                    <label class="block mb-1 text-xs font-medium text-gray-700">退勤時刻 <span class="text-red-500">*</span></label>
                                    <input type="time" name="new_leaving" value="{{ old('new_leaving') }}" class="w-full border rounded-lg p-2 bg-white text-sm">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block mb-1 text-xs font-medium text-gray-700">休憩時間</label>
                                <select name="new_break_time" class="w-full border border-gray-300 rounded-lg p-2 bg-white text-sm outline-none shadow-sm focus:border-blue-500 transition-all">
                                    <option value="01:00" {{ old('new_break_time', '01:00') == '01:00' ? 'selected' : '' }}>1時間休憩をつける</option>
                                    <option value="00:00" {{ old('new_break_time') == '00:00' ? 'selected' : '' }}>つけない</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="displayTimeGroup" class="flex gap-4 mb-2">
                        <div class="flex-1">
                            <label class="block mb-1 text-xs text-gray-400">出勤時刻(表示用)</label>
                            <input type="time" id="attendanceDisplay" disabled class="w-full border rounded-lg p-2 bg-gray-100 text-gray-500 text-sm">
                        </div>
                        <div class="flex-1">
                            <label class="block mb-1 text-xs text-gray-400">退勤時刻(表示用)</label>
                            <input type="time" id="leavingDisplay" disabled class="w-full border rounded-lg p-2 bg-gray-100 text-gray-500 text-sm">
                        </div>
                    </div>
                </form>
            </div>

            <div class="border-t pt-4 bg-white flex-shrink-0">
                <button type="submit" id="submitShiftBtn" form="shiftAddForm"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-5 rounded-xl text-sm shadow-md hover:shadow-lg transition-all text-center block">
                    ＋ シフトを追加する
                </button>

                <button type="submit" id="submitMasterBtn" form="shiftAddForm"
                    class="w-full hidden bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-5 rounded-xl text-sm shadow-md hover:shadow-lg transition-all text-center block">
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
        class="hidden">
    </span>

    <div id="floatingBulkBtnContainer" class="fixed bottom-6 right-6 z-40">
        <div class="floating-btn-swap">
            <button type="button" id="selectWeekdaysBtn" class="floating-bulk-btn floating-btn-slot">
                📅 土日祝を除くすべてにシフトを入れる
            </button>
            <button type="button" id="openBulkModalBtn" class="floating-bulk-btn floating-btn-slot btn-slot-hidden">
                選択した <span id="selectedCount" class="underline font-extrabold mx-0.5">0</span> 日分をまとめて追加
            </button>
        </div>
    </div>
@endsection

{{-- 💡 JavaScriptを親の @stack('scripts') に送り込む --}}
@push('scripts')
    @vite(['resources/js/shift.js'])
@endpush