<header class="top-header">
    <a href="#" class="notice-link">ⓘ お知らせ</a>
    <div class="user-info">
        <a href="#" class="header-icon-link">❓</a> 
        <span class="user-name">
            <a href="#" class="header-user-link">{{ Auth::user() ? Auth::user()->name : 'ゲスト' }}</a>
        </span>さん
    </div>
</header>