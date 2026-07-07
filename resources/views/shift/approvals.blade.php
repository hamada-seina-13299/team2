@extends('layouts.app')

@section('title', 'シフト提出承認 | 勤怠管理システム')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
    @vite(['resources/css/shift.css'])
@endpush

@section('content')
    <div class="w-full p-6 bg-gray-50 min-h-screen rounded-xl">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex items-center justify-between mb-1">
                <h1 class="text-lg font-bold text-gray-800">シフト提出承認</h1>
                <a href="{{ route('report.index') }}" class="text-sm text-blue-600 hover:underline">← 集計レポートへ戻る</a>
            </div>
            <p class="text-sm text-gray-500">現在「申請中」になっているシフトの一覧です。</p>
        </div>

        @if(session('success'))
            <div class="bulk-message-box">
                <p class="bulk-message-text">{{ session('success') }}</p>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-500 text-xs">
                        <th class="p-3 text-center font-medium">対象年月</th>
                        <th class="p-3 text-center font-medium">名前</th>
                        <th class="p-3 text-center font-medium">部署</th>
                        <th class="p-3 text-center font-medium">シフト内容</th>
                        <th class="p-3 text-center font-medium">承認</th>
                        <th class="p-3 text-center font-medium">取り下げ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($submissions as $submission)
                        <tr class="border-b border-gray-100 h-14">
                            <td class="p-3 text-center align-middle text-gray-700 font-semibold">
                                {{ $submission->year }}年{{ $submission->month }}月
                            </td>
                            <td class="p-3 text-center align-middle text-gray-700">
                                {{ $submission->user->name ?? '(不明なユーザー)' }}
                            </td>
                            <td class="p-3 text-center align-middle text-gray-500">
                                {{ $submission->user->dept ?? '-' }}
                            </td>
                            <td class="p-3 text-center align-middle">
                                <button type="button"
                                    class="btn-edit shift-detail-btn"
                                    data-title="{{ ($submission->user->name ?? '') }}さん　{{ $submission->year }}年{{ $submission->month }}月分"
                                    data-shifts='@json($shiftsBySubmission[$submission->id] ?? [])'>
                                    シフト内容を見る
                                </button>
                            </td>
                            <td class="p-3 text-center align-middle">
                                <form action="{{ route('shift.approvals.approve', $submission) }}" method="POST"
                                    onsubmit="return confirm('{{ $submission->user->name ?? '' }}さんの{{ $submission->year }}年{{ $submission->month }}月分を承認しますか？');" class="inline-block m-0">
                                    @csrf
                                    <button type="submit" class="btn-approve">承認</button>
                                </form>
                            </td>
                            <td class="p-3 text-center align-middle">
                                <form action="{{ route('shift.approvals.withdraw', $submission) }}" method="POST"
                                    onsubmit="return confirm('{{ $submission->user->name ?? '' }}さんの{{ $submission->year }}年{{ $submission->month }}月分の提出を取り下げますか？（本人側は未提出に戻ります）');" class="inline-block m-0">
                                    @csrf
                                    <button type="submit" class="btn-delete">取り下げ</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-gray-400">
                                現在、申請中のシフトはありません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- シフト内容ポップアップ --}}
    <div id="shiftDetailModal" class="modal-overlay">
        <div class="modal-container">
            <div class="flex justify-between items-center mb-4 flex-shrink-0">
                <h2 id="shiftDetailTitle" class="font-bold text-lg text-gray-800"></h2>
                <button type="button" id="closeShiftDetailModal" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <div id="shiftDetailBody" class="overflow-y-auto flex-1">
                {{-- JSで日付・出勤・退勤・勤務地の一覧を挿入 --}}
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('shiftDetailModal');
            const titleEl = document.getElementById('shiftDetailTitle');
            const bodyEl = document.getElementById('shiftDetailBody');
            const closeBtn = document.getElementById('closeShiftDetailModal');

            function escapeHtml(str) {
                const div = document.createElement('div');
                div.textContent = str == null ? '' : String(str);
                return div.innerHTML;
            }

            function openModal(title, shifts) {
                titleEl.textContent = title;

                if (!shifts || shifts.length === 0) {
                    bodyEl.innerHTML = '<p class="text-sm text-gray-400 text-center py-6">シフトが登録されていません。</p>';
                } else {
                    let html = '<table class="w-full text-sm"><thead><tr class="text-gray-500 text-xs border-b border-gray-100">' +
                        '<th class="p-2 text-center font-medium">日付</th>' +
                        '<th class="p-2 text-center font-medium">出勤</th>' +
                        '<th class="p-2 text-center font-medium">退勤</th>' +
                        '<th class="p-2 text-center font-medium">勤務地</th>' +
                        '</tr></thead><tbody>';

                    shifts.forEach(s => {
                        html += '<tr class="border-b border-gray-50">' +
                            '<td class="p-2 text-center">' + escapeHtml(s.date) + '</td>' +
                            '<td class="p-2 text-center">' + escapeHtml(s.attendance) + '</td>' +
                            '<td class="p-2 text-center">' + escapeHtml(s.leaving) + '</td>' +
                            '<td class="p-2 text-center">' + escapeHtml(s.place) + '</td>' +
                            '</tr>';
                    });

                    html += '</tbody></table>';
                    bodyEl.innerHTML = html;
                }

                modal.classList.add('is-open');
            }

            function closeModal() {
                modal.classList.remove('is-open');
            }

            document.querySelectorAll('.shift-detail-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    let shifts = [];
                    try {
                        shifts = JSON.parse(this.getAttribute('data-shifts') || '[]');
                    } catch (e) {
                        shifts = [];
                    }
                    openModal(this.getAttribute('data-title') || 'シフト内容', shifts);
                });
            });

            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            if (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) closeModal();
                });
            }
        });
    </script>
@endsection