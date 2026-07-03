<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>パスワード再設定 - メール送信</title>
</head>
<body>
    <h2>パスワード再設定</h2>

    @if (!empty($errorList))
        <div style="color: red;">
            <ul>
                @foreach ($errorList as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (!empty($successMessage))
        <div style="color: green;">
            <p>{{ $successMessage }}</p>
        </div>
    @endif

    <form action="{{ url('/password/passwordRequest') }}" method="POST">
        @csrf {{-- Laravelのセキュリティ対策用トークン（必須） --}}
        
        <label for="email">メールアドレス：</label>
        <input type="email" id="email" name="email" value="{{ $email }}">
        
        <button type="submit">送信</button>
    </form>

    <br>
    <a href="{{ route('login') }}">ログイン画面に戻る</a>
</body>
</html>