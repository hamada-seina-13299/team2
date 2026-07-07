@extends('layouts.app')

@section('title', '出勤・打刻データ | 勤怠管理システム')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
    @vite(['resources/css/attendance-report.css'])
@endpush

@section('content')
    <div class="w-full p-6 bg-gray-50 min-h-screen rounded-xl">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-lg font-bold text-gray-800">出勤・打刻データ</h1>
                <a href="{{ route('report.index') }}" class="text-sm text-blue-600 hover:underline">← 集計レポートへ戻る</a>
            </div>

            <form method="GET" action="{{ route('report.attendance') }}" class="report-attendance-filter-form">
                <div>
                    <label class="report-attendance-filter-label">対象日</label>
                    <input type="date" name="date" value="{{ $date->format('Y-m-d') }}"
                        class="report-attendance-filter-input" onchange="this.form.submit()">
                </div>

                <div>
                    <label class="report-attendance-filter-label">部門</label>
                    <select name="dept" class="report-attendance-filter-select" onchange="this.form.submit()">
                        @foreach($depts as $d)
                            <option value="{{ $d }}" {{ $dept == $d ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="report-attendance-filter-label">社員名検索</label>
                    <div class="report-attendance-search-group">
                        <input type="text" name="keyword" value="{{ $keyword }}" placeholder="社員名で検索"
                            class="report-attendance-filter-input">
                        <button type="submit" class="report-attendance-search-btn">検索</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto w-full">
            <table class="report-attendance-table">
                <thead>
                    <tr>
                        <th class="report-attendance-th">社員名</th>
                        <th class="report-attendance-th">部署</th>
                        <th class="report-attendance-th">出勤ステータス</th>
                        <th class="report-attendance-th">予定時刻</th>
                        <th class="report-attendance-th">勤務地</th>
                        <th class="report-attendance-th">出勤日時</th>
                        <th class="report-attendance-th">退勤日時</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr class="report-attendance-row">
                            <td class="report-attendance-td report-attendance-td-name">{{ $row['user']->name }}</td>
                            <td class="report-attendance-td">{{ $row['user']->dept }}</td>
                            <td class="report-attendance-td">
                                <span class="report-status-badge report-status-badge-{{ $row['status'] }}">{{ $row['status'] }}</span>
                            </td>
                            <td class="report-attendance-td">{{ $row['scheduled'] }}</td>
                            <td class="report-attendance-td report-attendance-td-truncate">{{ $row['place'] }}</td>
                            <td class="report-attendance-td">{{ $row['attendance_at'] ?? '-' }}</td>
                            <td class="report-attendance-td">{{ $row['leaving_at'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="report-attendance-empty">該当するデータがありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection