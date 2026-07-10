<!DOCTYPE html>
<html lang="ja">
<head>
    {{-- 🎨 ダーク/ライトモードの判定。CSSより前、head内で一番最初に実行することで
         「一瞬ライトモードで表示されてから切り替わる」ちらつき(FOUC)を防ぐ --}}
    <script>
        (function () {
            var mode = localStorage.getItem('themeMode') || 'light';
            var isDark;

            if (mode === 'dark') {
                isDark = true;
            } else if (mode === 'auto') {
                var hour = new Date().getHours();
                isDark = (hour < 6 || hour >= 19); // 自動：19時〜翌6時をダーク扱いにする
            } else {
                isDark = false;
            }

            document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '勤怠管理システム')</title>
    @vite([
        'resources/css/dashboard.css', 
        'resources/css/sidebar.css', 
        'resources/css/header.css', 
        'resources/css/dark-mode.css',
        'resources/js/dashboard.js',
        'resources/js/header.js'
    ])
    
    {{-- 子画面（list.blade.php）からプッシュされた Tailwind CSS や shift.css がここに挿入されます --}}
    @stack('styles')
    
    {{-- 💡 横並びレイアウトのバグを防ぐための補正用簡易スタイル --}}
    <style>
        /* Tailwind のリセットCSSによる wrapper や main-wrapper の影響を抑える記述 */
        .wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }
        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0; /* Tailwind環境下でのテーブル等のはみ出し防止 */
        }
        .container {
            width: 100%;
            flex: 1;
        }

        /* 🌀 画面遷移中のローディングオーバーレイ */
        #dash-loading-overlay {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            opacity: 1;
            transition: opacity 0.25s ease;
        }
        #dash-loading-overlay.is-hidden {
            opacity: 0;
            pointer-events: none;
        }
        #dash-loading-overlay img {
            width: 140px;
            height: 140px;
            animation: dash-loading-spin 2s linear infinite;
        }
        @keyframes dash-loading-spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="@yield('body-class')">
    {{-- 🌅 時刻連動の空＋出退勤の離着陸演出用レイヤー
         ※ body直下（.wrapperの外）に置くのが重要：ネストして置くと、
            position指定の無い通常のカード要素より前面に出てしまい、
            コンテンツが見えなくなってしまうため。
            スタイル自体はダッシュボード画面でのみ読み込まれるので、
            他画面では中身のない透明なdivとして存在するだけで実害はありません。 --}}
    <div id="dash-sky-bg" class="{{ (isset($latestOpenAttendance) && $latestOpenAttendance) ? 'state-flying' : 'state-ground' }}">
        <div class="dash-window-glass">
            <div class="dash-sky-inner">
                <div class="dash-sun"></div>
                <div class="dash-ground-scene">
                    <div class="dash-ground"></div>
                    <div class="dash-runway"></div>
                    <div class="dash-terminal"></div>
                    <div class="dash-tower"></div>
                </div>
                <div class="dash-cloud-floor"></div>
                <div class="dash-wing"></div>
            </div>
        </div>
    </div>

    {{-- 出勤/退勤の直後だけ、対応する離着陸アニメーションを1回再生するためのトリガー --}}
    @if (session('success') === '出勤しました。')
    <div id="dash-anim-trigger" data-anim="takeoff" style="display:none;"></div>
    @elseif (session('success') === '退勤しました。' || session('warning') === '休憩なしの退勤となりました。')
    <div id="dash-anim-trigger" data-anim="landing" style="display:none;"></div>
    @endif

    {{-- 🌀 画面遷移中のローディングオーバーレイ（public/load.png を使用） --}}
    <div id="dash-loading-overlay">
        <img src="{{ asset('load.png') }}" alt="読み込み中">
    </div>

    <div class="wrapper">
        {{-- サイドバーの読み込み --}}
        @include('layouts/sidebar')

        <div class="main-wrapper">
            {{-- ヘッダーの読み込み --}}
            @include('layouts/header')

            {{-- 💡 ここに list.blade.php の @section('content') の中身がはめ込まれます --}}
            <div class="container">
                @yield('content')
            </div>
        </div>
    </div>

    {{-- 子画面（list.blade.php）からプッシュされた shift.js がここに挿入されます --}}
    @stack('scripts')

    <script>
        (function () {
            var overlay = document.getElementById('dash-loading-overlay');
            if (!overlay) return;

            var hideTimer = null;

            function hideOverlay() {
                overlay.classList.add('is-hidden');
                if (hideTimer) {
                    clearTimeout(hideTimer);
                    hideTimer = null;
                }
            }
            function showOverlay() {
                overlay.classList.remove('is-hidden');

                // 🛡️ 安全策：Ajaxなどで実際にはページ遷移しないケースもあるため、
                // 一定時間たっても load イベントが来なければ自動で消す
                // （遷移する場合は hideOverlay() が load 側で先に呼ばれるので実害なし）
                if (hideTimer) clearTimeout(hideTimer);
                hideTimer = setTimeout(hideOverlay, 6000);
            }

            // ページの読み込みが完了したら消す
            window.addEventListener('load', hideOverlay);

            // ブラウザの「戻る/進む」でキャッシュから復元された場合も確実に消す
            window.addEventListener('pageshow', function (e) {
                if (e.persisted) hideOverlay();
            });

            // フォーム送信で別ページへ遷移する瞬間に再表示する
            // ⚠️ ここは「バブリングフェーズ」で拾うのが重要。
            // キャプチャフェーズ（第3引数 true）だと、送信対象自身の submit ハンドラ
            // （confirm()や、Ajax化のための e.preventDefault()）より“先に”実行されてしまい、
            // e.defaultPrevented が正しく判定できず、Ajax送信や確認ダイアログでキャンセルした
            // 場合でもオーバーレイが表示されっぱなしになるバグの原因になっていた。
            document.addEventListener('submit', function (e) {
                if (!e.defaultPrevented) showOverlay();
            });

            // 通常のリンククリックで別ページへ遷移する瞬間に再表示する
            document.addEventListener('click', function (e) {
                var link = e.target.closest('a[href]');
                if (!link) return;

                var href = link.getAttribute('href');
                // 新規タブ・アンカーリンク・javascript:リンクなどは対象外
                if (link.target === '_blank' || !href || href.startsWith('#') || href.startsWith('javascript:')) {
                    return;
                }
                // 他のスクリプトが Ajax化のために既に e.preventDefault() している場合は
                // 実際にはページ遷移しないので、オーバーレイを表示しない
                if (e.defaultPrevented) return;

                showOverlay();
            });
        })();
    </script>
</body>
</html>