<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>
    @vite(['resources/css/background.css', 'resources/css/login.css'])
</head>

<body class="weather-night">

    <!-- 星空 -->
    <div class="starry_sky universe"></div>

    <div class="stage-objects">
        <div class="cloud cloud-back"></div>
    </div>

    <!-- 雨 -->
    <div class="rain">
        <div></div><div></div><div></div><div></div><div></div>
        <div></div><div></div><div></div><div></div><div></div>
        <div></div><div></div><div></div><div></div><div></div>
        <div></div><div></div><div></div><div></div><div></div>
        <div></div><div></div><div></div><div></div><div></div>
    </div>
        
    <div class="stage-objects" id="airplane-stage">
        <div class="cloud cloud-back"></div>
        <div class="cloud cloud-middle"></div>
        <div class="cloud cloud-front"></div>

        <div class="airplane-container fly-left-to-right">
            <svg class="airplane" viewBox="0 0 24 24">
                <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L14 19v-5.5l8 2.5z" fill="currentColor"/>
            </svg>
        </div>

        <div class="airplane-container fly-right-to-left">
            <svg class="airplane" viewBox="0 0 24 24">
                <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L14 19v-5.5l8 2.5z" fill="currentColor"/>
            </svg>
        </div>

        <div class="airplane-container fly-bottom-left-to-top-right">
            <svg class="airplane" viewBox="0 0 24 24">
                <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L14 19v-5.5l8 2.5z" fill="currentColor"/>
            </svg>
        </div>
    </div>
        
    <div class="container">
        <main>
            <h1 class="skyDuty" style="color: #1a2a4a; font-size: 3rem; margin-top: 0px;">SkyDuty</h1>
            @if (!empty($errorList))
                <ul style="color: #dc2626; list-style: none; padding-left: 0; margin-top: 15px; font-size: 0.9rem;">
                    @foreach ($errorList as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
            
            
            <form action="{{ route('login') }}" method="post">
                @csrf

                <table>
                    <tr>
                        <th><label for="email">メールアドレス</label></th>
                        <td><input type="text" name="email" placeholder="example@gmail.com" value="{{ $email ?? '' }}"></td>
                    </tr>
                    <tr>
                        <th><label for="password">パスワード</label></th>
                        <td><input type="password" name="password" placeholder="password"></td>
                    </tr>
                </table>

                <div class="button">
                    <button type="submit" class="btn">ログイン</button>
                </div>

                <div style="margin: 20px 0 10px 0;">
                    <a href="{{ route('password.passwordRequest') }}" style="color: #3b5998; text-decoration: none; font-size: 0.9rem;">パスワードをお忘れの方はこちら</a>
                </div>

                
            </form>

        </main>  
    </div>

    @if (session('password_changed'))
        <script>
            // 画面が読み込まれたら「変更されました」のポップアップを表示
            alert('パスワードが変更されました');
        </script>
    @endif

    <!-- 星空 -->
    <script>
        function init() {
            const universe = document.querySelector('.universe');
            if (!universe) return;

            // 1. 固定の星を150個自動生成
            for (let i = 0; i < 150; i++) {
                const star = document.createElement('div');
                star.className = 'star';
                star.style.left = Math.random() * 100 + 'vw';
                star.style.top = Math.random() * 100 + 'vh';
                star.style.animationDelay = Math.random() * 2 + 's';
                universe.appendChild(star);
            }

            // 2. 流れ星をランダムな方向・位置で発生させる関数
            function createShootingStar() {
                // 夜間飛行（weather-night）の時だけ生成する
                if (!document.body.classList.contains('weather-night')) return;

                const sStar = document.createElement('div');
                sStar.className = 'shooting-star';

                // 画面のサイズを取得
                const w = window.innerWidth;
                const h = window.innerHeight;

                // 【方向のランダム化】 4パターンの方向からランダムに1つ選ぶ
                const directions = ['top-to-bottom', 'left-to-right', 'right-to-left', 'bottom-to-top'];
                const dir = directions[Math.floor(Math.random() * directions.length)];

                let startX, startY, endX, endY;

                if (dir === 'top-to-bottom') {
                    // 上から右下または左下へ流れる
                    startX = Math.random() * w; startY = -100;
                    endX = startX + (Math.random() * 600 - 300); endY = h + 100;
                } else if (dir === 'left-to-right') {
                    // 左から右斜め下へ流れる
                    startX = -200; startY = Math.random() * (h * 0.6);
                    endX = w + 200; endY = startY + (Math.random() * 400 + 100);
                } else if (dir === 'right-to-left') {
                    // 右から左斜め下へ流れる
                    startX = w + 200; startY = Math.random() * (h * 0.6);
                    endX = -200; endY = startY + (Math.random() * 400 + 100);
                } else {
                    // 【おまけ】下から上へ昇る逆方向の流れ星（稀に発生）
                    startX = Math.random() * w; startY = h + 100;
                    endX = startX + (Math.random() * 400 - 200); endY = -100;
                }

                // 動的にoffset-path（SVGの軌跡パス）をセット
                sStar.style.offsetPath = `path('M ${startX},${startY} L ${endX},${endY}')`;

                universe.appendChild(sStar);

                // アニメーション終了（7秒）したらHTMLから削除してメモリを解放
                setTimeout(() => {
                    sStar.remove();
                }, 7000);
            }

            // 最初にある程度ばらつかせて流れ星を始動
            setTimeout(createShootingStar, 1000);
            setTimeout(createShootingStar, 4000);

            // 以降、3.5秒〜6秒のランダムな周期で新しい流れ星を永遠に降らせる（時間差の自動調整）
            function InterimLoop() {
                const randomInterval = Math.floor(Math.random() * 2500) + 3500; // 3500ms〜6000ms
                setTimeout(() => {
                    createShootingStar();
                    InterimLoop();
                }, randomInterval);
            }
            InterimLoop();
        }
        window.addEventListener('load', init);
    </script>
    

</body>
</html>