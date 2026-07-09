@extends('layouts.app')

@section('title', '勤怠申請承認 | 勤怠管理システム')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
    <div class="w-full p-6 bg-gray-50 min-h-screen rounded-xl">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex items-center justify-between mb-1">
                <h1 class="text-lg font-bold text-gray-800">勤怠申請承認</h1>
                <a href="{{ route('report.index') }}" class="text-sm text-blue-600 hover:underline">← 集計レポートへ戻る</a>
            </div>
            <p class="text-sm text-gray-500">部下から提出された勤怠申請（遅刻・早退・欠勤・有給・半休・残業など）を確認し、承認・却下してください。</p>
            <div class="text-sm text-gray-500 mt-2">対象件数：<span class="font-bold text-gray-800">{{ $requests->count() }}</span>件</div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg px-4 py-3 mb-6 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 text-red-700 border border-red-200 rounded-lg px-4 py-3 mb-6 text-sm">{{ session('error') }}</div>
        @endif

        @forelse($requests as $req)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4 pb-4 border-b border-gray-100">
                    <div>
                        <span class="font-bold text-gray-800">{{ $req->user->name }}</span>
                        <span class="text-sm text-gray-500 ml-2">{{ $req->user->dept }}</span>
                    </div>
                    <div class="text-sm text-gray-500">
                        対象日：<span class="font-semibold text-gray-800">{{ \Illuminate\Support\Carbon::parse($req->target_date)->format('Y年n月j日') }}</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-sm font-semibold">
                        {{ $req->request_type }}
                    </span>
                    @if($req->request_time)
                        <span class="text-sm text-gray-600">
                            時刻：<span class="font-semibold text-gray-800">{{ \Illuminate\Support\Carbon::parse($req->request_time)->format('H:i') }}</span>
                        </span>
                    @endif
                </div>

                @if($req->memo)
                    <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-600 mb-4 whitespace-pre-line">
                        <span class="font-semibold text-gray-700">申請理由：</span>{{ $req->memo }}
                    </div>
                @endif

                @if($req->attachment)
                    <div class="mb-4">
                        <a href="{{ asset('storage/' . $req->attachment) }}" target="_blank" rel="noopener"
                           class="text-sm text-blue-600 hover:underline">📎 添付ファイルを見る</a>
                    </div>
                @endif

                <div class="flex justify-end gap-3">
                    <form method="POST" action="{{ route('attendance.approvals.reject', $req) }}" onsubmit="return confirm('この申請を却下しますか？');">
                        @csrf
                        <button type="submit" class="px-5 py-2 rounded-lg border border-red-300 text-red-600 text-sm font-semibold hover:bg-red-50">却下</button>
                    </form>
                    <form method="POST" action="{{ route('attendance.approvals.approve', $req) }}" onsubmit="return confirm('この申請を承認しますか？');">
                        @csrf
                        <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">承認</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center text-gray-400">
                現在、承認待ちの勤怠申請はありません。
            </div>
        @endforelse
    </div>
@endsection