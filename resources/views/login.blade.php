<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>
    @vite(['resources/css/login.css'])
</head>

<body>
    <div class="container">
        <main>
            <h1>ログイン</h1>
            <form action="{{ route('login') }}" method="post">
                @csrf

                <table>
                    <tr>
                        <th>メールアドレス</th>
                        <td><input type="text" name="email" placeholder="メールアドレス" value="{{ $email ?? '' }}"></td>
                    </tr>
                    <tr>
                        <th>パスワード</th>
                        <td><input type="password" name="password" placeholder="パスワード"></td>
                    </tr>
                </table>

                <div class="button">
                    <button type="submit" class="btn">ログイン</button>
                </div>

                @if (!empty($errorList))
                    <ul style="color:red; list-style: none; padding-left: 0;">
                        @foreach ($errorList as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </form>

        </main>  
    </div>
</body>
</html>