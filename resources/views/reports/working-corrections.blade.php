@extends('layouts.app')

@section('title', '打刻修正申請承認 | 勤怠管理システム')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
    <div class="w-full p-6 bg-gray-50 min-h-screen rounded-xl">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex items-center justify-between mb-1">
                <h1 class="text-lg font-bold text-gray-800">打刻修正申請承認</h1>
                <a href="{{ route('report.index') }}" class="text-sm text-blue-600 hover:underline">← 集計レポートへ戻る</a>
            </div>
            <p class="text-sm text-gray-500">部下から提出された打刻修正申請を確認し、承認・却下してください。</p>
            <div class="text-sm text-gray-500 mt-2">対象件数：<span class="font-bold text-gray-800">{{ $corrections->count() }}</span>件</div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg px-4 py-3 mb-6 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 text-red-700 border border-red-200 rounded-lg px-4 py-3 mb-6 text-sm">{{ session('error') }}</div>
        @endif

        @forelse($corrections as $correction)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4 pb-4 border-b border-gray-100">
                    <div>
                        <span class="font-bold text-gray-800">{{ $correction->user->name }}</span>
                        <span class="text-sm text-gray-500 ml-2">{{ $correction->user->dept }}</span>
                    </div>
                    <div class="text-sm text-gray-500">
                        対象日：<span class="font-semibold text-gray-800">{{ \Illuminate\Support\Carbon::parse($correction->target_date)->format('Y年n月j日') }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto mb-4">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left text-gray-500 font-semibold py-2 px-3 border-b border-gray-100">項目</th>
                                <th class="text-left text-gray-500 font-semibold py-2 px-3 border-b border-gray-100">修正前</th>
                                <th class="text-left text-gray-500 font-semibold py-2 px-3 border-b border-gray-100">修正後</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $diffRows = [
                                    ['label' => '出勤時刻', 'before' => $correction->before_attendance, 'after' => $correction->after_attendance, 'time' => true],
                                    ['label' => '退勤時刻', 'before' => $correction->before_leaving, 'after' => $correction->after_leaving, 'time' => true],
                                    ['label' => '休憩開始', 'before' => $correction->before_break_time, 'after' => $correction->after_break_time, 'time' => true],
                                    ['label' => '休憩終了', 'before' => $correction->before_break_end_time, 'after' => $correction->after_break_end_time, 'time' => true],
                                    ['label' => '勤務地', 'before' => $correction->before_working_place, 'after' => $correction->after_working_place, 'time' => false],
                                ];
                            @endphp
                            @foreach($diffRows as $row)
                                @php
                                    $beforeDisplay = $row['before'] ? ($row['time'] ? \Illuminate\Support\Carbon::parse($row['before'])->format('H:i') : $row['before']) : '-';
                                    $afterDisplay = $row['after'] ? ($row['time'] ? \Illuminate\Support\Carbon::parse($row['after'])->format('H:i') : $row['after']) : '-';
                                    $changed = $beforeDisplay !== $afterDisplay;
                                @endphp
                                <tr>
                                    <td class="py-2 px-3 border-b border-gray-100 text-gray-600">{{ $row['label'] }}</td>
                                    <td class="py-2 px-3 border-b border-gray-100 text-gray-500">{{ $beforeDisplay }}</td>
                                    <td class="py-2 px-3 border-b border-gray-100 {{ $changed ? 'text-blue-600 font-bold' : 'text-gray-500' }}">{{ $afterDisplay }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($correction->memo)
                    <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-600 mb-4 whitespace-pre-line">
                        <span class="font-semibold text-gray-700">申請理由：</span>{{ $correction->memo }}
                    </div>
                @endif

                <div class="flex justify-end gap-3">
                    <form method="POST" action="{{ route('working.corrections.reject', $correction) }}" onsubmit="return confirm('この申請を却下しますか？申請前のデータに戻ります。');">
                        @csrf
                        <button type="submit" class="px-5 py-2 rounded-lg border border-red-300 text-red-600 text-sm font-semibold hover:bg-red-50">却下</button>
                    </form>
                    <form method="POST" action="{{ route('working.corrections.approve', $correction) }}" onsubmit="return confirm('この申請を承認しますか？');">
                        @csrf
                        <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">承認</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center text-gray-400">
                現在、承認待ちの打刻修正申請はありません。
            </div>
        @endforelse
    </div>
@endsection