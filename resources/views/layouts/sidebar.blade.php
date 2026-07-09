<div class="sidebar" id="sidebar">
    <div class="sidebar-logo" id="sidebarToggle" style="cursor: pointer;">
        <img src="{{ asset('favicon.ico') }}" alt="Logo">
        <span class="brand-name">Sky Duty</span>
    </div>

    <a href="{{ route('dashboard') }}" 
       class="sidebar-icon {{ request()->routeIs('dashboard') ? 'active' : '' }}" 
       data-tooltip="打刻">
        <span class="icon-emoji">🕒</span>
        <span class="sidebar-text">打刻</span>
    </a>

    <a href="{{ route('shift.list') }}" 
       class="sidebar-icon {{ request()->routeIs('shift.list') ? 'active' : '' }}" 
       data-tooltip="シフト">
        <span class="icon-emoji">📅</span>
        <span class="sidebar-text">シフト</span>
    </a>

    <a href="{{ route('attendance.index') }}" class="sidebar-icon" data-tooltip="勤務表">
        <span class="icon-emoji">📊</span>
        <span class="sidebar-text">勤務表</span>
    </a>

    <a href="#" class="sidebar-icon" data-tooltip="各種申請">
        <span class="icon-emoji">📄</span>
        <span class="sidebar-text">各種申請</span>
    </a>

    <a href="#" class="sidebar-icon" data-tooltip="マイデータ">
        <span class="icon-emoji">👤</span>
        <span class="sidebar-text">マイデータ</span>
    </a>
    
    <a href="{{ route('employee.search') }}"
       class="sidebar-icon {{ request()->routeIs('employee.*') ? 'active' : '' }}"
       data-tooltip="社員検索">
        <span class="icon-emoji">🔍</span>
        <span class="sidebar-text">社員検索</span>
    </a>
    
    <a href="{{ route('report.index') }}"
       class="sidebar-icon {{ request()->routeIs('report.*') || request()->routeIs('shift.approvals.*') ? 'active' : '' }}"
       data-tooltip="集計レポート">
        <span class="icon-emoji">📈</span>
        <span class="sidebar-text">集計レポート</span>
    </a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');

        // ロゴをクリックしたら .is-open クラスを付け外しする
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('is-open');
        });
    });
</script>