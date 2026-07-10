@extends('layouts.app')

@section('title', '勤怠申請承認 | 勤怠管理システム')

@php
    // 種別ごとの固定CSSクラス（インラインstyleでのBlade展開を廃止し、Linterエラーを解消するため）
    $typeClasses = [
        '遅刻'     => 'type-late',
        '早退'     => 'type-early-leave',
        '欠勤'     => 'type-absent',
        '有給'     => 'type-paid-leave',
        '半休'     => 'type-half-day',
        '残業'     => 'type-overtime',
        '有事遅刻' => 'type-emergency-late',
        '有事早退' => 'type-emergency-early-leave',
    ];
@endphp

@section('content')
    <div class="aa-app">
        <div class="aa-header">
            <div>
                <p class="aa-eyebrow">承認管理</p>
                <h1 class="aa-title">承認待ち&nbsp;<span class="aa-count" id="aaCount">{{ $requests->count() }}</span>&nbsp;件</h1>
                <a href="{{ route('report.index') }}" class="aa-back-link">← 集計レポートへ戻る</a>
            </div>
            <div class="aa-history" id="aaHistory"></div>
        </div>

        @if(session('success'))
            <div class="aa-flash aa-flash-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="aa-flash aa-flash-error">{{ session('error') }}</div>
        @endif

        <div class="aa-stage" id="aaStage">
            <div class="aa-empty {{ $requests->count() === 0 ? 'is-visible' : '' }}" id="aaEmpty">
                <div class="aa-empty-icon">✓</div>
                <p class="aa-empty-title">全件処理完了</p>
                <p class="aa-empty-sub">承認待ちの勤怠申請はありません</p>
            </div>

            @if($requests->count() > 0)
                <div class="aa-zone aa-zone-reject" id="aaZoneReject">
                    <div class="aa-zone-icon aa-zone-icon-reject">✕</div>
                    <p class="aa-zone-label aa-zone-label-reject">却下</p>
                    <kbd class="aa-kbd aa-kbd-reject">←</kbd>
                </div>
                <div class="aa-zone aa-zone-approve" id="aaZoneApprove">
                    <div class="aa-zone-icon aa-zone-icon-approve">✓</div>
                    <p class="aa-zone-label aa-zone-label-approve">承認</p>
                    <kbd class="aa-kbd aa-kbd-approve">→</kbd>
                </div>

                <div class="aa-stack" id="aaStack">
                    @foreach($requests as $req)
                        @php
                            $typeClass = $typeClasses[$req->request_type] ?? 'type-other';
                            $initials = mb_substr($req->user->name ?? '？', 0, 1);
                            $hasAttachment = !empty($req->attachment);
                        @endphp
                        <div class="aa-card {{ $typeClass }}"
                             data-user-name="{{ $req->user->name }}"
                             data-approve-url="{{ route('attendance.approvals.approve', $req) }}"
                             data-reject-url="{{ route('attendance.approvals.reject', $req) }}"
                             data-undo-url="{{ route('attendance.approvals.undo', $req) }}">
                            <div class="aa-card-accent"></div>
                            <div class="aa-card-body">
                                <div class="aa-card-head">
                                    <div class="aa-avatar">{{ $initials }}</div>
                                    <div class="aa-card-headtext">
                                        <h3>{{ $req->user->name }}</h3>
                                        <p>{{ $req->user->dept }}</p>
                                    </div>
                                    <span class="aa-type-badge">
                                        {{ $req->request_type }}
                                    </span>
                                </div>

                                <div class="aa-detail-grid">
                                    <div class="aa-detail">
                                        <p>対象日</p>
                                        <p>{{ \Illuminate\Support\Carbon::parse($req->target_date)->format('n月j日（' . ['日','月','火','水','木','金','土'][\Illuminate\Support\Carbon::parse($req->target_date)->dayOfWeek] . '）') }}</p>
                                    </div>
                                    <div class="aa-detail">
                                        <p>時刻</p>
                                        <p>{{ $req->request_time ? \Illuminate\Support\Carbon::parse($req->request_time)->format('H:i') : '－' }}</p>
                                    </div>
                                    <div class="aa-detail">
                                        <p>申請日時</p>
                                        <p>{{ $req->created_at?->format('n/j H:i') }}</p>
                                    </div>
                                    <div class="aa-detail">
                                        <p>申請種別</p>
                                        <p>{{ $req->request_type }}</p>
                                    </div>
                                </div>

                                <div class="aa-reason">
                                    <p>申請理由</p>
                                    <p>{{ $req->memo ?: '（記載なし）' }}</p>
                                </div>

                                @if($hasAttachment)
                                    <button type="button"
                                            class="aa-attachment"
                                            data-attachment-url="{{ asset('storage/' . $req->attachment) }}"
                                            data-attachment-name="{{ basename($req->attachment) }}">
                                        📎 添付ファイルを見る
                                    </button>
                                @endif

                                
                            </div>

                            <div class="aa-card-overlay"></div>
                            <div class="aa-card-stamp"></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if($requests->count() > 0)
            <div class="aa-hint">
                <span><kbd>←</kbd> 却下</span>
                <span>カードを左右にスワイプ、またはボタンで操作できます</span>
                <span>承認 <kbd>→</kbd></span>
            </div>
        @endif
    </div>

    <div class="aa-toast" id="aaToast" style="display:none;">
        <span id="aaToastMsg"></span>
        <button type="button" id="aaToastUndo" class="aa-toast-undo">元に戻す</button>
    </div>

    {{-- 添付ファイル用ポップアップ（モーダル） --}}
    <div class="aa-modal-overlay" id="aaAttachmentModal">
        <div class="aa-modal">
            <button type="button" class="aa-modal-close" id="aaModalClose" aria-label="閉じる">✕</button>
            <div class="aa-modal-body" id="aaModalBody"></div>
        </div>
    </div>

    <span id="aa-config" data-csrf="{{ csrf_token() }}" class="hidden"></span>

    @vite(['resources/css/attendance-approvals.css', 'resources/js/attendance-approvals.js'])
@endsection@extends('layouts.app')

@section('title', '勤怠申請承認 | 勤怠管理システム')

@php
    // 種別ごとの固定CSSクラス（インラインstyleでのBlade展開を廃止し、Linterエラーを解消するため）
    $typeClasses = [
        '遅刻'     => 'type-late',
        '早退'     => 'type-early-leave',
        '欠勤'     => 'type-absent',
        '有給'     => 'type-paid-leave',
        '半休'     => 'type-half-day',
        '残業'     => 'type-overtime',
        '有事遅刻' => 'type-emergency-late',
        '有事早退' => 'type-emergency-early-leave',
    ];
@endphp

@section('content')
    <div class="aa-app">
        <div class="aa-header">
            <div>
                <p class="aa-eyebrow">承認管理</p>
                <h1 class="aa-title">承認待ち&nbsp;<span class="aa-count" id="aaCount">{{ $requests->count() }}</span>&nbsp;件</h1>
                <a href="{{ route('report.index') }}" class="aa-back-link">← 集計レポートへ戻る</a>
            </div>
            <div class="aa-history" id="aaHistory"></div>
        </div>

        @if(session('success'))
            <div class="aa-flash aa-flash-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="aa-flash aa-flash-error">{{ session('error') }}</div>
        @endif

        <div class="aa-stage" id="aaStage">
            <div class="aa-empty {{ $requests->count() === 0 ? 'is-visible' : '' }}" id="aaEmpty">
                <div class="aa-empty-icon">✓</div>
                <p class="aa-empty-title">全件処理完了</p>
                <p class="aa-empty-sub">承認待ちの勤怠申請はありません</p>
            </div>

            @if($requests->count() > 0)
                <div class="aa-zone aa-zone-reject" id="aaZoneReject">
                    <div class="aa-zone-icon aa-zone-icon-reject">✕</div>
                    <p class="aa-zone-label aa-zone-label-reject">却下</p>
                    <kbd class="aa-kbd aa-kbd-reject">←</kbd>
                </div>
                <div class="aa-zone aa-zone-approve" id="aaZoneApprove">
                    <div class="aa-zone-icon aa-zone-icon-approve">✓</div>
                    <p class="aa-zone-label aa-zone-label-approve">承認</p>
                    <kbd class="aa-kbd aa-kbd-approve">→</kbd>
                </div>

                <div class="aa-stack" id="aaStack">
                    @foreach($requests as $req)
                        @php
                            $typeClass = $typeClasses[$req->request_type] ?? 'type-other';
                            $initials = mb_substr($req->user->name ?? '？', 0, 1);
                            $hasAttachment = !empty($req->attachment);
                        @endphp
                        <div class="aa-card {{ $typeClass }}"
                             data-user-name="{{ $req->user->name }}"
                             data-approve-url="{{ route('attendance.approvals.approve', $req) }}"
                             data-reject-url="{{ route('attendance.approvals.reject', $req) }}"
                             data-undo-url="{{ route('attendance.approvals.undo', $req) }}">
                            <div class="aa-card-accent"></div>
                            <div class="aa-card-body">
                                <div class="aa-card-scroll">
                                    <div class="aa-card-head">
                                        <div class="aa-avatar">{{ $initials }}</div>
                                        <div class="aa-card-headtext">
                                            <h3>{{ $req->user->name }}</h3>
                                            <p>{{ $req->user->dept }}</p>
                                        </div>
                                        <span class="aa-type-badge">
                                            {{ $req->request_type }}
                                        </span>
                                    </div>

                                    <div class="aa-detail-grid">
                                        <div class="aa-detail">
                                            <p>対象日</p>
                                            <p>{{ \Illuminate\Support\Carbon::parse($req->target_date)->format('n月j日（' . ['日','月','火','水','木','金','土'][\Illuminate\Support\Carbon::parse($req->target_date)->dayOfWeek] . '）') }}</p>
                                        </div>
                                        <div class="aa-detail">
                                            <p>時刻</p>
                                            <p>{{ $req->request_time ? \Illuminate\Support\Carbon::parse($req->request_time)->format('H:i') : '－' }}</p>
                                        </div>
                                        <div class="aa-detail">
                                            <p>申請日時</p>
                                            <p>{{ $req->created_at?->format('n/j H:i') }}</p>
                                        </div>
                                        <div class="aa-detail">
                                            <p>申請種別</p>
                                            <p>{{ $req->request_type }}</p>
                                        </div>
                                    </div>

                                    <div class="aa-reason">
                                        <p>申請理由</p>
                                        <p>{{ $req->memo ?: '（記載なし）' }}</p>
                                    </div>

                                    @if($hasAttachment)
                                        <button type="button"
                                                class="aa-attachment"
                                                data-attachment-url="{{ asset('storage/' . $req->attachment) }}"
                                                data-attachment-name="{{ basename($req->attachment) }}">
                                            📎 添付ファイルを見る
                                        </button>
                                    @endif
                                </div>

                                <div class="aa-actions">
                                    <button type="button" class="aa-btn aa-btn-reject" data-action="reject">✕ 却下</button>
                                    <button type="button" class="aa-btn aa-btn-approve" data-action="approve">✓ 承認</button>
                                </div>
                            </div>

                            <div class="aa-card-overlay"></div>
                            <div class="aa-card-stamp"></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if($requests->count() > 0)
            <div class="aa-hint">
                <span><kbd>←</kbd> 却下</span>
                <span>カードを左右にスワイプ、またはボタンで操作できます</span>
                <span>承認 <kbd>→</kbd></span>
            </div>
        @endif
    </div>

    <div class="aa-toast" id="aaToast" style="display:none;">
        <span id="aaToastMsg"></span>
        <button type="button" id="aaToastUndo" class="aa-toast-undo">元に戻す</button>
    </div>

    {{-- 添付ファイル用ポップアップ（モーダル） --}}
    <div class="aa-modal-overlay" id="aaAttachmentModal">
        <div class="aa-modal">
            <button type="button" class="aa-modal-close" id="aaModalClose" aria-label="閉じる">✕</button>
            <div class="aa-modal-body" id="aaModalBody"></div>
        </div>
    </div>

    <span id="aa-config" data-csrf="{{ csrf_token() }}" class="hidden"></span>

    @vite(['resources/css/attendance-approvals.css', 'resources/js/attendance-approvals.js'])
@endsection