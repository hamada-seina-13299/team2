<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新しいパスワードの登録</title>
    @vite(['resources/css/background.css', 'resources/css/passwordReset.css'])
</head>
<body class="weather-rainy">

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
            <h4 style="margin-top: 0px;">新しいパスワードの登録</h4>

            @if (!empty($errorList))
                <div style="color: red;">
                    <ul>
                        @foreach ($errorList as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('password.update') }}" method="POST">
                @csrf
                
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <table>
                    <tr>
                        <th><label for="password">新しいパスワード</label></th>
                        <td><input type="password" id="password" name="password" placeholder="password"></td>
                    </tr>

                    <tr>
                        <th><label for="password_confirmation">新しいパスワード<br>（確認用）</label></th>
                        <td><input type="password" id="password_confirmation" name="password_confirmation" placeholder="password"></td>
                    </tr>
                </table>

                <div class="button">
                    <button type="submit" class="btn">確定</button>
                </div>
            </form>
        </main>  
    </div>

@if(!empty($password_changed))
    <div id="successModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); display: flex; justify-content: center; align-items: center; z-index: 9999;">
        
        <div style="background: #ffffff; padding: 30px; border-radius: 12px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.2); max-width: 380px; width: 85%;">
            
            <div style="font-size: 3rem; color: #1a2a4a; margin-bottom: 10px;">SkyDuty</div>
            
            <h3 style="color: #4caf50; margin-top: 0; font-size: 1.3rem;">パスワード変更完了</h3>
            
            <p style="color: #555555; font-size: 0.9rem; line-height: 1.6; margin-bottom: 25px;">
                パスワードが正常に変更されました。<br>新しいパスワードでログインしてください。
            </p>
            
            <button id="modalOkBtn" style="background: #3b5998; color: #ffffff; border: none; padding: 12px 0; font-size: 1rem; font-weight: bold; border-radius: 6px; cursor: pointer; width: 100%; transition: background 0.2s;">
                OK
            </button>
        </div>
    </div>

    <script>
        // OKボタンが押下された時の処理
        document.getElementById('modalOkBtn').addEventListener('click', function() {
            // Laravelのルート名 'login' に対応するURLへ遷移
            window.location.href = "{{ route('login') }}";
        });
    </script>
@endif
    
</body>
</html>