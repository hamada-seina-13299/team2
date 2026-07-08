@extends('layouts.app')

@section('title', '社員検索 | 勤怠管理システム')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
    @vite(['resources/css/employee-search.css'])
@endpush

@section('content')
    <div class="w-full p-6 bg-gray-50 min-h-screen rounded-xl">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-lg font-bold text-gray-800">社員検索</h1>
            </div>

            <form method="GET" action="{{ route('employee.search') }}" class="employee-search-filter-form">
                <div>
                    <label class="employee-search-filter-label">社員名/メールアドレス</label>
                    <input type="text" name="keyword" value="{{ $keyword }}" placeholder="社員名、メールで検索"
                        class="employee-search-filter-input">
                </div>

                <div>
                    <label class="employee-search-filter-label">社員ID</label>
                    <input type="text" name="employee_id" value="{{ $employeeId }}" placeholder="カンマ(,)で複数検索可能"
                        class="employee-search-filter-input">
                </div>

                <div>
                    <label class="employee-search-filter-label">部門</label>
                    <select name="dept" class="employee-search-filter-select">
                        <option value="">すべて</option>
                        @foreach($depts as $d)
                            <option value="{{ $d }}" {{ $dept == $d ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="employee-search-filter-actions">
                    <button type="submit" class="employee-search-search-btn">検索</button>
                    <a href="{{ route('employee.search') }}" class="employee-search-clear-btn">クリア</a>
                </div>
            </form>

            {{-- 対象件数の表示 --}}
            <div class="employee-search-summary">
                対象件数：<span class="employee-search-summary-num">{{ $employees->total() }}</span>件
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto w-full">
            <table class="employee-search-table">
                <thead>
                    <tr>
                        <th class="employee-search-th">社員ID</th>
                        <th class="employee-search-th">社員名</th>
                        <th class="employee-search-th">メールアドレス</th>
                        <th class="employee-search-th">部門</th>
                        <th class="employee-search-th">入社日</th>
                        <th class="employee-search-th">権限</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                        <tr class="employee-search-row">
                            <td class="employee-search-td">{{ $employee->id }}</td>
                            <td class="employee-search-td employee-search-td-name">{{ $employee->name }}</td>
                            <td class="employee-search-td employee-search-td-truncate">{{ $employee->email }}</td>
                            <td class="employee-search-td">{{ $employee->dept ?? '-' }}</td>
                            <td class="employee-search-td">
                                {{ $employee->entering_company_date ? \Illuminate\Support\Carbon::parse($employee->entering_company_date)->format('Y-m-d') : '-' }}
                            </td>
                            <td class="employee-search-td">
                                <span class="employee-badge {{ $employee->isAdmin() ? 'employee-badge-admin' : 'employee-badge-general' }}">
                                    {{ $employee->isAdmin() ? '管理者' : '一般' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="employee-search-empty">該当する社員が見つかりません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($employees->hasPages())
                <div class="employee-search-pagination">
                    {{ $employees->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection