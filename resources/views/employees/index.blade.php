@extends('layouts.app')

@section('title', '社員検索 | 勤怠管理システム')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
    @vite(['resources/css/employee-search.css'])
    <style>
        /* 美しいミントグリーンのベース */
        .th-card-container {
            padding: 0.5rem !important;
            background-color: #e6fbf7 !important;
            border: 1px solid #c2f1e7;
            position: relative;
        }

        /* 持ち運びできる各項目の独立カード */
        .th-card {
            background-color: #a7ebd9;
            color: #2c4a43;
            font-weight: 600;
            border-radius: 0.375rem;
            padding: 0.5rem 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            border: 1px solid #82dec5;
            cursor: grab;
            user-select: none;
            transition: background-color 0.2s, box-shadow 0.2s;
        }

        /* 社員名（一番左固定） */
        .th-card.fixed-col {
            background-color: #bbf3e6;
            border: 1px solid #9cecd9;
            cursor: pointer;
        }

        /* ドラッグ中：上下は完全に固定され、左右だけに「ぬるぬる」追従するスタイル */
        .th-card.is-dragging {
            position: fixed !important;
            z-index: 9999;
            pointer-events: none; /* 下の要素のイベントを遮らない */
            transform: scale(1.02);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
            background-color: #82dec5;
            border-color: #4ed2b1;
        }

        /* 列が入れ替わる時のスライドアニメーション */
        .employee-search-table th,
        .employee-search-table td {
            transition: transform 0.25s cubic-bezier(0.2, 0.8, 0.2, 1), background-color 0.2s;
        }

        /* ドラッグ中に元の場所を半透明にする影 */
        .th-card.drag-placeholder {
            opacity: 0.15;
            background-color: #a7ebd9;
            border: 1px dashed #2c4a43;
        }
        
        .sort-arrow {
            font-size: 0.9rem;
            display: inline-block;
            line-height: 1;
            font-weight: bold;
        }
    </style>
@endpush

@section('content')
    <div class="w-full p-6 bg-gray-50 min-h-screen rounded-xl">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-lg font-bold text-gray-800">社員検索</h1>
            </div>

            <form method="GET" action="{{ route('employee.search') }}" class="employee-search-filter-form" id="searchForm">
                {{-- ソート状態の隠しフィールド --}}
                <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                <input type="hidden" name="order" value="{{ $order }}">

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
                    <a href="{{ route('employee.search') }}" class="employee-search-clear-btn" onclick="localStorage.removeItem('employee_column_order');">クリア</a>
                </div>
            </form>

            <div class="employee-search-summary">
                対象件数：<span class="employee-search-summary-num">{{ $employees->total() }}</span>件
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto w-full">
            <table class="employee-search-table" id="employeeTable">
                <thead>
                    <tr id="headerRow">
                        {{-- 社員名：一番左に固定 --}}
                        <th class="employee-search-th th-card-container" data-col="name">
                            <div class="th-card fixed-col" onclick="toggleSort('name')">
                                社員名/氏名
                                @if($sortBy === 'name')
                                    <span class="sort-arrow">{{ $order === 'desc' ? '▼' : '▲' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="employee-search-th th-card-container" data-col="id">
                            <div class="th-card" onclick="toggleSort('id')">
                                社員ID
                                @if($sortBy === 'id')
                                    <span class="sort-arrow">{{ $order === 'desc' ? '▼' : '▲' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="employee-search-th th-card-container" data-col="email">
                            <div class="th-card" onclick="toggleSort('email')">
                                メールアドレス
                                @if($sortBy === 'email')
                                    <span class="sort-arrow">{{ $order === 'desc' ? '▼' : '▲' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="employee-search-th th-card-container" data-col="dept">
                            <div class="th-card" onclick="toggleSort('dept')">
                                部門
                                @if($sortBy === 'dept')
                                    <span class="sort-arrow">{{ $order === 'desc' ? '▼' : '▲' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="employee-search-th th-card-container" data-col="date">
                            <div class="th-card" onclick="toggleSort('entering_company_date')">
                                入社日
                                @if($sortBy === 'entering_company_date')
                                    <span class="sort-arrow">{{ $order === 'desc' ? '▼' : '▲' }}</span>
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
                            <td class="employee-search-td" data-cell="date">
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
            const currentOrder = form.querySelector('input[name="order"]').value;
            
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

                    // 💡 感度向上：マウス位置ではなく、動かしているカード自体の中心X座標で判定する
                    const cardRect = activeCard.getBoundingClientRect();
                    const cardCenterX = cardRect.left + (cardRect.width / 2);

                    // 現在の並びを配列化
                    const thsArray = Array.from(headerRow.children);
                    const currentIndex = thsArray.indexOf(activeContainer);

                    // 隣のコンテナとの境界をまたいだか高速判定
                    let targetIndex = currentIndex;

                    // 左に動かしている時
                    if (deltaX < 0 && currentIndex > 1) {
                        const leftNeighbor = thsArray[currentIndex - 1];
                        const neighborRect = leftNeighbor.getBoundingClientRect();
                        // 隣のカードの右端を少しでも超えたら入れ替える
                        if (cardRect.left < neighborRect.left + (neighborRect.width * 0.7)) {
                            targetIndex = currentIndex - 1;
                        }
                    } 
                    // 右に動かしている時
                    else if (deltaX > 0 && currentIndex < thsArray.length - 1) {
                        const rightNeighbor = thsArray[currentIndex + 1];
                        const neighborRect = rightNeighbor.getBoundingClientRect();
                        // 隣のカードの左端を少しでも超えたら入れ替える
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