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
</head>
<body>
    <div class="wrapper">
        @include('layouts/sidebar')

        <div class="main-wrapper">
            @include('layouts/header')

            <div class="container">
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>