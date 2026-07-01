<x-app-layout>
    <div class="w-full p-6 bg-gray-50 min-h-screen">
        
        <h1 class="text-lg font-bold mb-2 text-gray-800">シフト一覧</h1>

        <div class="flex items-center gap-4 mb-6">
            <div class="flex items-center gap-1">
                <a href="{{ route('shift.list', ['year' => $month == 1 ? $year - 1 : $year, 'month' => $month == 1 ? 12 : $month - 1]) }}" 
                   class="w-10 h-10 border border-gray-200 rounded-lg bg-white flex items-center justify-center shadow-sm hover:bg-gray-50 text-gray-500 transition-colors">
                    &lt;
                </a>
                <span class="text-2xl font-bold px-3 text-gray-800">{{ $month }}月</span>
                <a href="{{ route('shift.list', ['year' => $month == 12 ? $year + 1 : $year, 'month' => $month == 12 ? 1 : $month + 1]) }}" 
                   class="w-10 h-10 border border-gray-200 rounded-lg bg-white flex items-center justify-center shadow-sm hover:bg-gray-50 text-gray-500 transition-colors">
                    &gt;
                </a>
            </div>
            <span class="text-gray-400 text-lg font-medium self-end mb-1">{{ $year }}年</span>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto w-full">
            <table class="w-full border-collapse table-fixed min-w-[600px]">
                <thead>
                    <tr class="bg-gray-50/75 border-b border-gray-100">
                        <th class="p-4 text-sm font-semibold text-gray-600 text-center w-[15%]">日付</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 text-center w-[15%]">出勤時刻</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 text-center w-[15%]">退勤時刻</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 text-center w-[25%]">勤務地</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 text-center w-[15%]">修正</th>
                        <th class="p-4 text-sm font-semibold text-gray-600 text-center w-[15%]">削除</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($days as $day)
                        @php
                            $isSunday = $day['date']->isSunday();
                            $isSaturday = $day['date']->isSaturday();

                            $dateColor = 'text-gray-800';
                            if ($isSunday) $dateColor = 'text-red-600';
                            if ($isSaturday) $dateColor = 'text-blue-600';

                            $rowBg = 'shift-row-default';
                            if ($isSunday) $rowBg = 'shift-row-sunday';
                            elseif ($isSaturday) $rowBg = 'shift-row-saturday';

                            $weeks = ['日', '月', '火', '水', '木', '金', '土'];
                            $japaneseWeek = $weeks[$day['date']->dayOfWeek];
                        @endphp

                        <tr class="border-b border-gray-100 {{ $rowBg }} h-14 transition-colors">
                            <td class="p-3 text-sm {{ $dateColor }} font-semibold text-center align-middle">
                                {{ $day['date']->format('d') }} ({{ $japaneseWeek }})
                            </td>
                            <td class="p-3 text-sm text-gray-600 text-center align-middle">
                                @if($day['shift'])
                                    {{ date('H:i', strtotime($day['shift']->shiftMaster->attendance)) }}
                                @else
                                    --:--
                                @endif
                            </td>
                            <td class="p-3 text-sm text-gray-600 text-center align-middle">
                                @if($day['shift'])
                                    {{ date('H:i', strtotime($day['shift']->shiftMaster->leaving)) }}
                                @else
                                    --:--
                                @endif
                            </td>
                            <td class="p-3 text-sm text-gray-600 text-center align-middle truncate">
                                @if($day['shift'])
                                    <span class="inline-flex items-center justify-center gap-1 w-full max-w-full truncate">
                                        📍<span class="truncate font-medium text-gray-700">{{ $day['shift']->shiftMaster->working_place }}</span>
                                    </span>
                                @else
                                    <span class="text-gray-300">--</span>
                                @endif
                            </td>
                            <td class="p-3 text-sm text-center align-middle">
                                @if($day['shift'])
                                    <button type="button" class="btn-edit">修正</button>
                                @else
                                    <button type="button"
                                        data-date="{{ $day['date']->format('Y-m-d') }}"
                                        class="open-add-modal-btn add-shift-btn {{ $dateColor }}">
                                        + シフトを追加
                                    </button>
                                @endif
                            </td>
                            <td class="p-3 text-sm text-center align-middle">
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
    <div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden">

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

                    <div class="mb-4">
                        <label class="block mb-1 text-sm font-medium text-gray-700">対象日 <span class="text-red-500">*</span></label>
                        <input type="date" id="modalTargetDate" name="target_date" value="{{ old('target_date') }}"
                            class="w-full border rounded-lg p-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        @error('target_date')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
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
                                    <span class="font-bold text-gray-800 text-sm block mb-0.5">{{ $master->working_place }}</span>
                                    <span class="text-xs text-gray-500">
                                        ({{ date('H:i', strtotime($master->attendance)) }} 〜 {{ date('H:i', strtotime($master->leaving)) }})
                                    </span>
                                    @if($master->user_id === Auth::id())
                                        <button type="button"
                                                data-master-id="{{ $master->id }}"
                                                data-master-name="{{ $master->working_place }}"
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
                            <p class="text-xs font-bold text-emerald-600 mb-3">✨ 新しい勤務地マスタを登録します</p>

                            <div class="mb-3">
                                <label class="block mb-1 text-xs font-medium text-gray-700">勤務地名 <span class="text-red-500">*</span></label>
                                <input type="text" name="new_working_place" value="{{ old('new_working_place') }}" class="w-full border rounded-lg p-2 bg-white text-sm">
                                @error('new_working_place') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex gap-4 mb-4">
                                <div class="flex-1">
                                    <label class="block mb-1 text-xs font-medium text-gray-700">出勤時刻 <span class="text-red-500">*</span></label>
                                    <input type="time" name="new_attendance" value="{{ old('new_attendance') }}" class="w-full border rounded-lg p-2 bg-white text-sm">
                                    @error('new_attendance') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="flex-1">
                                    <label class="block mb-1 text-xs font-medium text-gray-700">退勤時刻 <span class="text-red-500">*</span></label>
                                    <input type="time" name="new_leaving" value="{{ old('new_leaving') }}" class="w-full border rounded-lg p-2 bg-white text-sm">
                                    @error('new_leaving') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block mb-1 text-xs font-medium text-gray-700">休憩時間</label>
                                <input type="time" name="new_break_time" value="{{ old('new_break_time') }}" class="w-full border rounded-lg p-2 bg-white text-sm">
                            </div>

                            <div class="text-right">
                                <button type="submit" name="action" value="create_master"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 py-2 rounded-lg shadow transition-colors">
                                    上記の入力内容でマスタを登録する
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 mb-2">
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
                <button type="submit" form="shiftAddForm"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-5 rounded-xl text-sm shadow-md hover:shadow-lg transition-all text-center block">
                    ＋ シフトを追加する
                </button>
            </div>
        </div>
    </div>

    {{-- マスタ削除用フォーム --}}
    <form id="masterDeleteForm" action="{{ route('shift.master.delete') }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
        <input type="hidden" id="deleteMasterId" name="master_id">
    </form>

    {{-- エラー情報をJSに渡すための隠し要素 --}}
    <span id="error-data"
        data-has-errors="{{ $errors->any() ? 'true' : 'false' }}"
        data-has-fields-error="{{ ($errors->has('new_working_place') || $errors->has('new_attendance') || $errors->has('new_leaving')) ? 'true' : 'false' }}"
        class="hidden">
    </span>

    @vite(['resources/css/shift.css', 'resources/js/shift.js'])
</x-app-layout>