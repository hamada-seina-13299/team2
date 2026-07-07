<div class="sidebar">
    <a href="{{ route('dashboard') }}" 
   class="sidebar-icon {{ request()->routeIs('dashboard') ? 'active' : '' }}" 
   data-tooltip="打刻">🕒</a>

    <a href="{{ route('shift.list') }}" 
   class="sidebar-icon {{ request()->routeIs('shift.list') ? 'active' : '' }}" 
   data-tooltip="シフト">📅</a>
    <a href="#" class="sidebar-icon" data-tooltip="勤務表">📊</a>
    <a href="#" class="sidebar-icon" data-tooltip="各種申請">📄</a>
    <a href="#" class="sidebar-icon" data-tooltip="マイデータ">👤</a>
    <a href="#" class="sidebar-icon" data-tooltip="社員検索">🔍</a>
    
    <a href="{{ route('report.index') }}"
   class="sidebar-icon {{ request()->routeIs('report.*') || request()->routeIs('shift.approvals.*') ? 'active' : '' }}"
   data-tooltip="集計レポート">📈</a>
</div>