<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '勤怠管理システム')</title>
    @vite([
        'resources/css/dashboard.css', 
        'resources/css/sidebar.css', 
        'resources/css/header.css', 
        'resources/js/dashboard.js'
    ])
    
    {{-- 子画面（list.blade.php）からプッシュされた Tailwind CSS や shift.css がここに挿入されます --}}
    @stack('styles')
    
    {{-- 💡 横並びレイアウトのバグを防ぐための補正用簡易スタイル --}}
    <style>
        /* Tailwind のリセットCSSによる wrapper や main-wrapper の影響を抑える記述 */
        .wrapper {
            display: flex;
            min-h: 100vh;
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
    </style>
</head>
<body>
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
</body>
</html>