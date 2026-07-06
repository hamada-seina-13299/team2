<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>新しいパスワードの登録</title>
</head>
<body>
    <h2>新しいパスワードの登録</h2>

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
        
        {{-- コントローラーがユーザーを特定するために必要な情報をこっそり送信する（hidden） --}}
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <div>
            <label for="password">新しいパスワード：</label>
            <input type="password" id="password" name="password">
        </div>

        <br>

        <div>
            <label for="password_confirmation">新しいパスワード（確認用）：</label>
            <input type="password" id="password_confirmation" name="password_confirmation">
        </div>

        <br>
        <button type="submit">確定</button>
    </form>
</body>
</html>