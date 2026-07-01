<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>ログイン</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
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
                    <ul style="color:red;">
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