<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>パスワード再設定</title>
</head>
<body>
<!-- メール本文 -->
    <p>いつも勤怠管理システムをご利用いただきありがとうございます。</p>
    <p>以下のURLをクリックして、新しいパスワードの登録を完了させてください。</p>
    
    <p>
        <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
    </p>

    <p>※このURLの有効期限は発行から1時間です。</p>
    <p>※本メールに心当たりがない場合は、破棄してください。</p>
</body>
</html>