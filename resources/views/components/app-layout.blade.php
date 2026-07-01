<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>勤怠管理システム</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        {{-- サイドメニュー --}}
        <aside class="w-60 bg-white border-r">
            <div class="p-4 flex items-center gap-2 border-b">
                <span class="font-bold">勤怠管理システム</span>
            </div>

            <nav class="p-3 space-y-1">
                <a href="#"
                    class="flex items-center gap-2 px-3 py-2 rounded text-gray-700 hover:bg-gray-100">
                    ホーム
                </a>

                <a href="{{ route('shift.list') }}"
                    class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('shift.list') ? 'bg-blue-600 text-white font-semibold' : 'text-gray-700 hover:bg-gray-100' }}">
                    シフト一覧
                </a>

                <a href="#"
                    class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('shift.correction') ? 'bg-blue-600 text-white font-semibold' : 'text-gray-700 hover:bg-gray-100' }}">
                    シフト修正
                </a>

                <form action="#" method="POST" class="pt-2 border-t mt-2">
                    @csrf
                    <button type="submit" class="flex items-center gap-2 px-3 py-2 rounded text-gray-700 hover:bg-gray-100 w-full text-left">
                    ログアウト
                    </button>
                </form>
            </nav>
        </aside>

        {{-- メインコンテンツ --}}
        <div class="flex-1">
            <header class="bg-white border-b p-4 flex justify-end items-center gap-2">
                @auth
                    <span>{{ auth()->user()->name }}</span>
                    <span class="text-gray-400">|</span>
                    <span class="text-gray-500 text-sm">{{ auth()->user()->dept }}</span>
                @endauth
            </header>

            <main>
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>