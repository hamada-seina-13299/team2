@extends('layouts.app')

@section('title', '社員検索 | 勤怠管理システム')

@push('styles')
    @vite(['resources/css/employee-search.css'])
@endpush

@section('content')
    <div class="employee-search-container">
        <div class="employee-search-card">
            <div class="employee-search-header">
                <h1 class="employee-search-title">社員検索</h1>
            </div>

            <form method="GET" action="{{ route('employee.search') }}" class="employee-search-filter-form" id="searchForm">
                {{-- ソート状態の隠しフィールド --}}
                <input type="hidden" name="sort_by" value="{{ $sortBy ?? 'name' }}">
                <input type="hidden" name="order" value="{{ $order ?? 'asc' }}">

                <div>
                    <label class="employee-search-filter-label">社員名/メールアドレス</label>
                    <input type="text" name="keyword" value="{{ $keyword ?? '' }}" placeholder="社員名、メールで検索"
                        class="employee-search-filter-input">
                </div>

                <div>
                    <label class="employee-search-filter-label">社員ID</label>
                    <input type="text" name="employee_id" value="{{ $employeeId ?? '' }}" placeholder="カンマ(,)で複数検索可能"
                        class="employee-search-filter-input">
                </div>

                <div>
                    <label class="employee-search-filter-label">部門</label>
                    <select name="dept" class="employee-search-filter-select">
                        <option value="">すべて</option>
                        @foreach($depts as $d)
                            <option value="{{ $d }}" {{ ($dept ?? '') == $d ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="employee-search-filter-actions">
                    <button type="submit" class="employee-search-search-btn">検索</button>
                    <a href="{{ route('employee.search') }}" class="employee-search-clear-btn" onclick="localStorage.removeItem('employee_column_order');">クリア</a>
                </div>
            </form>

            <div class="employee-search-summary">
                対象件数：<span class="employee-search-summary-num">{{ $employees->total() }}</span>件
            </div>
        </div>

        <div class="employee-search-table-wrapper">
            <table class="employee-search-table" id="employeeTable">
                <thead>
                    <tr id="headerRow">
                        {{-- 社員名：一番左に固定 --}}
                        <th class="employee-search-th th-card-container" data-col="name">
                            <div class="th-card fixed-col" onclick="toggleSort('name')">
                                社員名/氏名
                                @if(($sortBy ?? 'name') === 'name')
                                    <span class="sort-arrow">{{ ($order ?? 'asc') === 'desc' ? '▼' : '▲' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="employee-search-th th-card-container" data-col="id">
                            <div class="th-card" onclick="toggleSort('id')">
                                社員ID
                                @if(($sortBy ?? '') === 'id')
                                    <span class="sort-arrow">{{ (strtolower($order ?? 'asc')) === 'desc' ? '▼' : '▲' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="employee-search-th th-card-container" data-col="email">
                            <div class="th-card" onclick="toggleSort('email')">
                                メールアドレス
                                @if(($sortBy ?? '') === 'email')
                                    <span class="sort-arrow">{{ ($order ?? '') === 'desc' ? '▼' : '▲' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="employee-search-th th-card-container" data-col="dept">
                            <div class="th-card" onclick="toggleSort('dept')">
                                部門
                                @if(($sortBy ?? '') === 'dept')
                                    <span class="sort-arrow">{{ ($order ?? '') === 'desc' ? '▼' : '▲' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="employee-search-th th-card-container" data-col="entering_company_date">
                            <div class="th-card" onclick="toggleSort('entering_company_date')">
                                入社日
                                @if(($sortBy ?? '') === 'entering_company_date')
                                    <span class="sort-arrow">{{ ($order ?? '') === 'desc' ? '▼' : '▲' }}</span>
                                @endif
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                        <tr class="employee-search-row">
                            <td class="employee-search-td employee-search-td-name" data-cell="name">{{ $employee->name }}</td>
                            <td class="employee-search-td" data-cell="id">{{ $employee->id }}</td>
                            <td class="employee-search-td employee-search-td-truncate" data-cell="email">{{ $employee->email }}</td>
                            <td class="employee-search-td" data-cell="dept">{{ $employee->dept ?? '-' }}</td>
                            <td class="employee-search-td" data-cell="entering_company_date">
                                {{ $employee->entering_company_date ? \Illuminate\Support\Carbon::parse($employee->entering_company_date)->format('Y-m-d') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="employee-search-empty">該当する社員が見つかりません。</td>
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

    <script>
        // 💡 ▼ → ▲ → ▼ → ▲ と綺麗にループするソート切り替え関数
        function toggleSort(column) {
            const form = document.getElementById('searchForm');
            const currentSort = form.querySelector('input[name="sort_by"]').value;
            const currentOrder = (form.querySelector('input[name="order"]').value || 'asc').toLowerCase();;
            
            let newOrder = 'desc'; // 別の項目を押したときはまず ▼(desc) から始まる
            if (currentSort === column) {
                // 同じ項目の場合は ▼ と ▲ を交互にループ
                newOrder = (currentOrder === 'desc') ? 'asc' : 'desc';
            }
            
            form.querySelector('input[name="sort_by"]').value = column;
            form.querySelector('input[name="order"]').value = newOrder;
            form.submit();
        }

        document.addEventListener('DOMContentLoaded', () => {
            const table = document.getElementById('employeeTable');
            const headerRow = document.getElementById('headerRow');
            const containers = table.querySelectorAll('thead th');
            
            // 1. 並び順をLocalStorageから復元
            const savedOrder = localStorage.getItem('employee_column_order');
            if (savedOrder) {
                const orderArray = JSON.parse(savedOrder);
                orderArray.forEach(colKey => {
                    const th = headerRow.querySelector(`[data-col="${colKey}"]`);
                    if (th) headerRow.appendChild(th);
                });
                table.querySelectorAll('tbody tr').forEach(row => {
                    if(row.querySelector('.employee-search-empty')) return;
                    orderArray.forEach(colKey => {
                        const td = row.querySelector(`[data-cell="${colKey}"]`);
                        if (td) row.appendChild(td);
                    });
                });
            }

            // 2. ドラッグ＆スライド（高感度版）
            let activeCard = null;
            let placeholderCard = null;
            let activeContainer = null;
            let startX = 0;
            let originalLeft = 0;
            let fixedTop = 0;
            let isMoving = false;

            containers.forEach((container) => {
                const card = container.querySelector('.th-card');
                if (!card || card.classList.contains('fixed-col')) return;

                card.addEventListener('mousedown', (e) => {
                    if (e.button !== 0) return; // 左クリックのみ
                    
                    activeCard = card;
                    activeContainer = container;
                    isMoving = false;

                    const rect = card.getBoundingClientRect();
                    originalLeft = rect.left;
                    fixedTop = rect.top + window.scrollY; 
                    startX = e.pageX;

                    document.addEventListener('mousemove', onMouseMove);
                    document.addEventListener('mouseup', onMouseUp);
                    
                    e.preventDefault();
                });
            });

            function onMouseMove(e) {
                if (!activeCard) return;

                const deltaX = e.pageX - startX;

                // 3px以上動いたらドラッグ開始
                if (!isMoving && Math.abs(deltaX) > 3) {
                    isMoving = true;
                    const rect = activeCard.getBoundingClientRect();

                    placeholderCard = activeCard.cloneNode(true);
                    placeholderCard.classList.add('drag-placeholder');
                    activeCard.parentNode.insertBefore(placeholderCard, activeCard);

                    activeCard.style.width = rect.width + 'px';
                    activeCard.style.height = rect.height + 'px';
                    activeCard.style.top = (fixedTop - window.scrollY) + 'px'; 
                    activeCard.classList.add('is-dragging');
                }

                if (isMoving) {
                    // 左右だけ追従
                    const currentLeft = originalLeft + deltaX;
                    activeCard.style.left = currentLeft + 'px';

                    // 💡 cardRect をここで正しく定義
                    const cardRect = activeCard.getBoundingClientRect();

                    // 現在の並びを配列化
                    const thsArray = Array.from(headerRow.children);
                    const currentIndex = thsArray.indexOf(activeContainer);

                    // 隣のコンテナとの境界をまたいだか高速判定
                    let targetIndex = currentIndex;

                    // 左に動かしている時
                    if (deltaX < 0 && currentIndex > 1) {
                        const leftNeighbor = thsArray[currentIndex - 1];
                        const neighborRect = leftNeighbor.getBoundingClientRect();
                        if (cardRect.left < neighborRect.left + (neighborRect.width * 0.7)) {
                            targetIndex = currentIndex - 1;
                        }
                    } 
                    // 右に動かしている時
                    else if (deltaX > 0 && currentIndex < thsArray.length - 1) {
                        const rightNeighbor = thsArray[currentIndex + 1];
                        const neighborRect = rightNeighbor.getBoundingClientRect();
                        if (cardRect.right > neighborRect.left + (neighborRect.width * 0.3)) {
                            targetIndex = currentIndex + 1;
                        }
                    }

                    // 💡 判定に引っかかったらDOMをその場でスライド入れ替え
                    if (targetIndex !== currentIndex) {
                        const targetContainer = thsArray[targetIndex];
                        if (currentIndex < targetIndex) {
                            headerRow.insertBefore(activeContainer, targetContainer.nextSibling);
                        } else {
                            headerRow.insertBefore(activeContainer, targetContainer);
                        }

                        // 下のデータ行（tbody）も完全同期
                        table.querySelectorAll('tbody tr').forEach((row) => {
                            const cells = Array.from(row.children);
                            if (cells.length > 1) {
                                const srcTd = cells[currentIndex];
                                const targetTd = cells[targetIndex];
                                if (currentIndex < targetIndex) {
                                    targetTd.parentNode.insertBefore(srcTd, targetTd.nextSibling);
                                } else {
                                    targetTd.parentNode.insertBefore(srcTd, targetTd);
                                }
                            }
                        });

                        // 順番を保存
                        saveCurrentOrder();
                    }
                }
            }

            function onMouseUp(e) {
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);

                if (!activeCard) return;

                // ほとんど動いていなかったらソート実行
                if (!isMoving) {
                    const colName = activeContainer.getAttribute('data-col');
                    toggleSort(colName);
                }

                // スタイルリセット
                activeCard.classList.remove('is-dragging');
                activeCard.style.position = '';
                activeCard.style.width = '';
                activeCard.style.height = '';
                activeCard.style.left = '';
                activeCard.style.top = '';

                if (placeholderCard) {
                    placeholderCard.remove();
                    placeholderCard = null;
                }
                
                activeCard = null;
                activeContainer = null;
            }

            function saveCurrentOrder() {
                const currentCols = Array.from(headerRow.children).map(th => th.getAttribute('data-col'));
                localStorage.setItem('employee_column_order', JSON.stringify(currentCols));
            }
        });
    </script>
@endsection