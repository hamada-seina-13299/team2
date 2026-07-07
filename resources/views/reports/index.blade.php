@extends('layouts.app')

@section('title', '集計レポート | 勤怠管理システム')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
    <div class="w-full p-6 bg-gray-50 min-h-screen rounded-xl">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <h1 class="text-lg font-bold text-gray-800 mb-1">集計レポート</h1>
            <p class="text-sm text-gray-500">確認したいレポートを選択してください。</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- 💡 出勤データは今回ダミー実装(リンク先は#) --}}
            <a href="#" class="block bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-gray-200 transition-all">
                <div class="text-2xl mb-2">📊</div>
                <div class="font-bold text-gray-800 mb-1">出勤データ</div>
                <div class="text-sm text-gray-500">勤怠の集計・出勤状況を確認します。</div>
            </a>

            @if($isAdmin)
                <a href="{{ route('shift.approvals.index') }}" class="block bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-gray-200 transition-all">
                    <div class="text-2xl mb-2">✅</div>
                    <div class="font-bold text-gray-800 mb-1">シフト提出承認</div>
                    <div class="text-sm text-gray-500">従業員から提出されたシフトを確認・承認します。</div>
                </a>
            @endif
        </div>
    </div>
@endsection