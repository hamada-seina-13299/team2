<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>パスワード再設定</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1a2a4a; line-height: 1.6;">
    <!-- メール本文 -->
    <p>いつも勤怠管理システムをご利用いただきありがとうございます。</p>
    <p>以下のURLをクリックして、新しいパスワードの登録を完了させてください。</p>
    
    <div style="margin: 30px 0;">
        <a href="{{ $resetUrl }}" class="reset-button" style="background-color: #0066cc; border-radius: 6px; color: #ffffff; display: inline-block; font-size: 16px; font-weight: bold; line-height: 50px; text-align: center; text-decoration: none; width: 240px; -webkit-text-size-adjust: none; mso-hide: all; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            パスワード再設定
        </a>
    </div>

    <p>※このURLの有効期限は発行から1時間です。</p>
    <p>※本メールに心当たりがない場合は、破棄してください。</p>
</body>
</html>