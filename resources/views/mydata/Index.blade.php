@extends('layouts.app')

@section('title', 'マイデータ | 勤怠管理システム')

@section('content')
<div class="mydata-page">
    <div class="mydata-card">
        <div class="mydata-card-header">
            <h1 class="mydata-title">👤 マイデータ</h1>
            <span class="mydata-subtitle">{{ Auth::user()->name }} さんのこれまでの申請一覧</span>
        </div>

        {{-- 種別ごとのタブ切り替え（サーバー側は常に全件返し、表示/非表示だけJSで切り替える） --}}
        <div class="mydata-tabs" id="mydata-tabs">
            <button type="button" class="mydata-tab-btn active" data-filter="all">すべて</button>
            <button type="button" class="mydata-tab-btn" data-filter="attendance_request">勤怠申請</button>
            <button type="button" class="mydata-tab-btn" data-filter="shift_submission">シフト提出</button>
            <button type="button" class="mydata-tab-btn" data-filter="working_correction">打刻修正</button>
        </div>

        <div class="mydata-table-wrapper">
            <table class="mydata-table" id="mydata-table">
                <thead>
                    <tr>
                        <th>種別</th>
                        <th>対象</th>
                        <th>内容</th>
                        <th>添付ファイル</th>
                        <th>ステータス</th>
                        <th>申請日時</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($myData as $item)
                        <tr data-type="{{ $item['type_key'] }}">
                            <td>
                                <span class="mydata-type-badge mydata-type-{{ $item['type_key'] }}">{{ $item['type'] }}</span>
                            </td>
                            <td class="mydata-nowrap">{{ $item['target'] }}</td>
                            <td>
                                {{ $item['summary'] }}
                                @if(!empty($item['memo']))
                                    <br><span class="mydata-memo">{{ $item['memo'] }}</span>
                                @endif
                            </td>
                            <td class="mydata-nowrap">
                                @if(!empty($item['attachment']))
                                    <button type="button"
                                        class="mydata-attachment-btn"
                                        data-attachment-url="{{ asset('storage/' . $item['attachment']) }}"
                                        data-attachment-name="{{ basename($item['attachment']) }}">
                                        📎 確認する
                                    </button>
                                @else
                                    <span class="mydata-dash">ー</span>
                                @endif
                            </td>
                            <td>
                                <span class="mydata-status-badge mydata-status-{{ $item['status'] }}">{{ $item['status'] }}</span>
                                @if(!empty($item['updater_name']) && !in_array($item['status'], ['申請中', '未申請'], true))
                                    <div class="mydata-updater">対応: {{ $item['updater_name'] }}</div>
                                @endif
                            </td>
                            <td class="mydata-nowrap">{{ \Carbon\Carbon::parse($item['created_at'])->format('Y/m/d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="mydata-empty">申請データがありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- 添付ファイルのプレビュー用モーダル --}}
<div id="mydata-attachment-modal" class="mydata-modal-overlay">
    <div class="mydata-modal-box">
        <div class="mydata-modal-header">
            <span>📎 添付ファイルの確認</span>
            <button type="button" id="mydata-attachment-close" class="mydata-modal-close">&times;</button>
        </div>
        <div class="mydata-modal-body" id="mydata-attachment-body">
            {{-- クリックされたボタンの内容に応じてJSで差し込む --}}
        </div>
    </div>
</div>

<style>
    /* ==========================================================================
       マイデータ画面専用スタイル（外部CSSに依存せず、このページだけで完結させる）
       既存ダッシュボードのテイスト（ティール系アクセントカラー #26d0ce）に合わせています
       ========================================================================== */
    .mydata-page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 10px 4px 40px;
    }

    .mydata-card {
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .mydata-card-header {
        padding: 22px 24px 16px;
        border-bottom: 1px solid #eef0f2;
    }

    .mydata-title {
        margin: 0 0 4px;
        font-size: 20px;
        font-weight: bold;
        color: #2d3748;
    }

    .mydata-subtitle {
        font-size: 13px;
        color: #7f8c8d;
    }

    /* タブ */
    .mydata-tabs {
        display: flex;
        gap: 4px;
        padding: 0 20px;
        border-bottom: 1px solid #eef0f2;
        overflow-x: auto;
    }

    .mydata-tab-btn {
        background: none;
        border: none;
        padding: 12px 16px;
        font-size: 13px;
        font-weight: bold;
        color: #7f8c8d;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        white-space: nowrap;
        transition: color 0.15s ease, border-color 0.15s ease;
    }
    .mydata-tab-btn:hover {
        color: #1aaba8;
    }
    .mydata-tab-btn.active {
        color: #12b3ab;
        border-bottom-color: #26d0ce;
    }

    /* テーブル */
    .mydata-table-wrapper {
        overflow-x: auto;
        padding: 8px 20px 22px;
    }

    .mydata-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        min-width: 760px;
    }

    .mydata-table th {
        background-color: #f4fbfb;
        color: #12b3ab;
        font-weight: bold;
        text-align: left;
        padding: 10px 12px;
        border-bottom: 2px solid #d9f0ef;
        white-space: nowrap;
    }

    .mydata-table td {
        padding: 12px;
        border-bottom: 1px solid #f0f2f4;
        vertical-align: middle;
        color: #333333;
    }

    .mydata-table tbody tr:hover {
        background-color: #fafdfd;
    }

    .mydata-nowrap {
        white-space: nowrap;
    }

    .mydata-memo {
        display: block;
        margin-top: 3px;
        font-size: 12px;
        color: #9ca3af;
    }

    .mydata-empty {
        text-align: center;
        color: #a0aec0;
        padding: 32px !important;
    }

    /* 種別バッジ */
    .mydata-type-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        white-space: nowrap;
    }
    .mydata-type-attendance_request { background-color: #eef2ff; color: #4f46e5; }
    .mydata-type-shift_submission   { background-color: #fef3e2; color: #b8760a; }
    .mydata-type-working_correction { background-color: #e6f7f7; color: #12b3ab; }

    /* ステータスバッジ（既存ダッシュボードの日本語ステータス文字列と合わせています） */
    .mydata-status-badge {
        display: inline-block;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        white-space: nowrap;
    }
    .mydata-status-未申請 { background-color: #f1f1f1; color: #777777; }
    .mydata-status-申請中 { background-color: #eef7f6; color: #12b3ab; }
    .mydata-status-承認   { background-color: #e6f7ee; color: #1a7f4f; }
    .mydata-status-却下   { background-color: #fdeef0; color: #d64b5f; }

    .mydata-updater {
        font-size: 11px;
        color: #9ca3af;
        margin-top: 4px;
    }

    .mydata-dash {
        color: #c0c6cc;
    }

    /* 添付ファイル確認ボタン */
    .mydata-attachment-btn {
        background-color: #ffffff;
        border: 1px solid #26d0ce;
        color: #26d0ce;
        border-radius: 6px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: bold;
        cursor: pointer;
        white-space: nowrap;
        transition: background-color 0.15s ease, color 0.15s ease;
    }
    .mydata-attachment-btn:hover {
        background-color: #26d0ce;
        color: #ffffff;
    }

    /* 添付ファイルプレビュー用モーダル */
    .mydata-modal-overlay {
        position: fixed;
        inset: 0;
        background-color: rgba(0, 0, 0, 0.55);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 5000;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }
    .mydata-modal-overlay.is-open {
        opacity: 1;
        pointer-events: auto;
    }
    .mydata-modal-box {
        background-color: #ffffff;
        width: 90%;
        max-width: 560px;
        max-height: 85vh;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transform: translateY(-16px);
        transition: transform 0.2s ease;
    }
    .mydata-modal-overlay.is-open .mydata-modal-box {
        transform: translateY(0);
    }
    .mydata-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px;
        border-bottom: 1px solid #eef0f2;
        font-weight: bold;
        color: #2d3748;
        font-size: 14px;
        flex-shrink: 0;
    }
    .mydata-modal-close {
        background: none;
        border: none;
        font-size: 22px;
        line-height: 1;
        color: #9ca3af;
        cursor: pointer;
    }
    .mydata-modal-close:hover {
        color: #374151;
    }
    .mydata-modal-body {
        padding: 20px;
        overflow-y: auto;
        text-align: center;
    }
    .mydata-modal-body img {
        max-width: 100%;
        max-height: 65vh;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    .mydata-modal-body a {
        color: #12b3ab;
        font-weight: bold;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- タブ切り替え ---
        const tabs = document.querySelectorAll('#mydata-tabs .mydata-tab-btn');
        const rows = document.querySelectorAll('#mydata-table tbody tr[data-type]');

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                tabs.forEach((t) => t.classList.remove('active'));
                tab.classList.add('active');

                const filter = tab.getAttribute('data-filter');
                rows.forEach((row) => {
                    row.style.display = (filter === 'all' || row.getAttribute('data-type') === filter) ? '' : 'none';
                });
            });
        });

        // --- 添付ファイルのプレビューモーダル ---
        const modal = document.getElementById('mydata-attachment-modal');
        const body = document.getElementById('mydata-attachment-body');
        const closeBtn = document.getElementById('mydata-attachment-close');

        function closeAttachmentModal() {
            modal.classList.remove('is-open');
            body.innerHTML = '';
        }

        document.querySelectorAll('.mydata-attachment-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const url = btn.getAttribute('data-attachment-url');
                const name = btn.getAttribute('data-attachment-name') || '';
                const isImage = /\.(png|jpe?g|gif|webp)$/i.test(name);

                if (isImage) {
                    body.innerHTML = `<img src="${url}" alt="添付ファイル">`;
                } else {
                    // 画像以外（PDFなど）はブラウザプレビューに頼らず、新しいタブで開くリンクにする
                    body.innerHTML = `<p>このファイル形式はプレビューできません。<br><br><a href="${url}" target="_blank" rel="noopener">新しいタブで開く（${name}）</a></p>`;
                }

                modal.classList.add('is-open');
            });
        });

        closeBtn.addEventListener('click', closeAttachmentModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeAttachmentModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeAttachmentModal();
        });
    });
</script>
@endsection