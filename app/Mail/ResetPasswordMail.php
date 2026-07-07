<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// メールの設定を管理するクラス

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    // View（メール本文）に渡すためのプロパティを定義
    public $resetUrl;

    /**
     * コンストラクタでコントローラーからURLを受け取る
     */
    public function __construct($resetUrl)
    {
        $this->resetUrl = $resetUrl;
    }

    /**
     * メールの件名（タイトル）を設定
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【SkyDuty】パスワード再設定のお知らせ',
        );
    }

    /**
     * メールの本文に使うBladeファイルを指定
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.resetMail',
        );
    }

    /**
     * 添付ファイルの設定（今回は使わないので空でOK）
     */
    public function attachments(): array
    {
        return [];
    }
}