<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>シフト修正</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Zen+Kaku+Gothic+New:wght@700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');

        :root {
            --sc-bg: #F0F2F5;
            --sc-surface: #FFFFFF;
            --sc-border: #D9DDE4;
            --sc-primary: #2B4C7E;
            --sc-primary-dark: #1E3A63;
            --sc-primary-soft: #E8EEF6;
            --sc-text: #1F2933;
            --sc-muted: #6B7280;
        }

        body::before {
            content: "";
            display: block;
            height: 4px;
            background: linear-gradient(90deg, #2B4C7E 0%, #4A7EC0 100%);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
        }

        body {
            background: #F0F2F5 !important;
            font-family: "Noto Sans JP", sans-serif;
        }

        h1.h4 {
            font-family: "Zen Kaku Gothic New", sans-serif;
            font-weight: 900 !important;
            font-size: 1.45rem !important;
            color: #1E3A63 !important;
            display: flex !important;
            align-items: center;
            gap: 0.6rem;
            padding-bottom: 0.9rem;
            border-bottom: 2px solid #D9DDE4;
            margin-bottom: 1.5rem !important;
        }

        h1.h4::before {
            content: "";
            display: inline-block;
            width: 6px;
            height: 24px;
            background: #2B4C7E;
            border-radius: 3px;
            flex-shrink: 0;
        }

        .card>.card-header {
            background-color: #2B4C7E !important;
            color: #fff !important;
            border-bottom: none !important;
            font-family: "Zen Kaku Gothic New", sans-serif;
            font-weight: 700 !important;
            font-size: 0.93rem;
            letter-spacing: 0.04em;
            padding: 0.9rem 1.25rem !important;
            position: relative;
        }

        .card>.card-header::after {
            content: "";
            position: absolute;
            left: 1.25rem;
            right: 1.25rem;
            bottom: 0;
            height: 3px;
            background-image: radial-gradient(circle, rgba(255, 255, 255, .2) 2px, transparent 2.1px);
            background-size: 12px 3px;
            background-repeat: repeat-x;
        }

        .card {
            border: 1px solid #D9DDE4 !important;
            border-radius: 10px !important;
            box-shadow: 0 1px 3px rgba(31, 41, 51, .06), 0 4px 16px rgba(31, 41, 51, .07) !important;
            overflow: hidden;
        }

        .card-body {
            background: #fff !important;
        }

        .sc-date-row {
            background: #fff;
            border: 1px solid #D9DDE4;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            box-shadow: 0 1px 3px rgba(31, 41, 51, .06);
            display: flex !important;
            align-items: flex-end;
            gap: 0.75rem;
        }

        .form-control,
        .form-select {
            border: 1px solid #D9DDE4 !important;
            border-radius: 7px !important;
            background-color: #FAFBFC !important;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #2B4C7E !important;
            box-shadow: 0 0 0 3px #E8EEF6 !important;
        }

        .sc-submit-btn {
            display: block !important;
            width: 100% !important;
            padding: 0.75rem !important;
            font-size: 1rem !important;
            font-weight: 700 !important;
            background-color: #2B4C7E !important;
            border-color: #2B4C7E !important;
            border-radius: 8px !important;
            letter-spacing: 0.05em;
            margin-top: 0.4rem;
        }

        .sc-submit-btn:hover {
            background-color: #1E3A63 !important;
            border-color: #1E3A63 !important;
        }

        .btn-outline-secondary {
            white-space: nowrap;
            font-weight: 700 !important;
        }

        .table thead th {
            background: #F7F8FA !important;
            color: #6B7280 !important;
            font-size: 0.74rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            border-top: none !important;
            padding: 0.7rem 1rem !important;
            white-space: nowrap;
        }

        .table tbody td {
            vertical-align: middle;
            border-top: none !important;
        }

        .table tbody tr:hover td {
            background: #E8EEF6;
        }

        .badge {
            border-radius: 999px !important;
            font-size: 0.72rem !important;
            font-weight: 700 !important;
        }

        .badge.bg-warning {
            background: #FBF1DF !important;
            color: #7A4D0A !important;
        }

        .badge.bg-success {
            background: #E4F2EE !important;
            color: #1A5C45 !important;
        }

        .badge.bg-danger {
            background: #FAE7E5 !important;
            color: #7A2820 !important;
        }

        .table-borderless td {
            font-weight: 700 !important;
            font-size: 1.05rem !important;
            color: #1E3A63 !important;
        }

        .btn-outline-danger {
            border-color: #C75146 !important;
            color: #C75146 !important;
            font-weight: 700 !important;
        }

        .btn-outline-danger:hover {
            background: #C75146 !important;
            color: #fff !important;
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <h1 class="h4 mb-4">シフト修正</h1>

        @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="row">
            {{-- ⭕ 改善点1: 対象日選択フォーム（ここで一度閉じます。入れ子にしない） --}}
            <div class="col-12 mb-4">
                <form method="GET" action="{{ route('shiftcorrection.index') }}" class="sc-date-row">
                    <div>
                        <label for="target_date_select" class="form-label">対象日</label>
                        <input type="date" id="target_date_select" name="target_date" class="form-control"
                            value="{{ $targetDate }}"
                            min="{{ now()->addDay()->format('Y-m-d') }}">
                    </div>
                    <button type="submit" class="btn btn-outline-secondary">表示</button>
                </form>
            </div>

            {{-- 変更前の現在のシフト予定 --}}
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">現在の登録シフト ({{ $targetDate }})</div>
                    <div class="card-body">
                        @if ($currentShift)
                        <p class="text-muted mb-2">現在、この日に以下のシフトが登録されています。</p>
                        <table class="table table-borderless mb-0">
                            <tr>
                                <th class="w-50">現在のシフトパターン</th>
                                {{-- マップからパターン名を取得し、なければ「不明」と表示 --}}
                                <td>{{ $shiftMasterMap[$currentShift->master_id] ?? '未設定' }}</td>
                            </tr>
                            <tr>
                                <th>出勤予定時刻</th>
                                <td>{{ \Carbon\Carbon::parse($currentShift->attendance_edit)->format('H:i') }}</td>
                            </tr>
                            <tr>
                                <th>退勤予定時刻</th>
                                <td>{{ \Carbon\Carbon::parse($currentShift->leaving_edit)->format('H:i') }}</td>
                            </tr>
                        </table>
                        @else
                        <p class="text-muted mb-0">この日にはまだ基本シフトの登録がありません。（新規で申請を行ってください）</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ⭕ 改善点2: 完全に独立した修正申請フォーム --}}
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">シフト修正申請</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('shiftcorrection.store') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="target_date" class="form-label">対象日 <span class="text-danger">*</span></label>
                                <input type="date" id="target_date" name="target_date" class="form-control"
                                    value="{{ old('target_date', $targetDate) }}"
                                    min="{{ now()->addDay()->format('Y-m-d') }}" required>
                            </div>

                            <div class="mb-3">
                                <label for="master_id" class="form-label">シフトパターン <span class="text-danger">*</span></label>
                                <select id="master_id" name="master_id" class="form-select" required>
                                    <option value="">選択してください</option>
                                    @foreach ($shiftMasters as $master)
                                    {{-- ⭕ 前回追加内容（$currentShift）があれば初期選択されるように制御 --}}
                                    <option value="{{ $master->id }}"
                                        data-attendance="{{ \Carbon\Carbon::parse($master->attendance)->format('H:i') }}"
                                        data-leaving="{{ \Carbon\Carbon::parse($master->leaving)->format('H:i') }}"
                                        {{ old('master_id', $currentShift->master_id ?? '') == $master->id ? 'selected' : '' }}>
                                        {{ $master->name }}
                                        （{{ \Carbon\Carbon::parse($master->attendance)->format('H:i') }}
                                        〜{{ \Carbon\Carbon::parse($master->leaving)->format('H:i') }} /
                                        {{ $master->working_place }}）
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label for="attendance_edit" class="form-label">修正後 出勤時刻 <span class="text-danger">*</span></label>
                                    {{-- ⭕ 前回追加内容があれば初期値にセット --}}
                                    <input type="time" id="attendance_edit" name="attendance_edit" class="form-control"
                                        value="{{ old('attendance_edit', isset($currentShift->attendance_edit) ? \Carbon\Carbon::parse($currentShift->attendance_edit)->format('H:i') : '') }}" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label for="leaving_edit" class="form-label">修正後 退勤時刻 <span class="text-danger">*</span></label>
                                    {{-- ⭕ 前回追加内容があれば初期値にセット --}}
                                    <input type="time" id="leaving_edit" name="leaving_edit" class="form-control"
                                        value="{{ old('leaving_edit', isset($currentShift->leaving_edit) ? \Carbon\Carbon::parse($currentShift->leaving_edit)->format('H:i') : '') }}" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="memo" class="form-label">修正理由（メモ） <span class="text-danger">*</span></label>
                                <textarea id="memo" name="memo" class="form-control" rows="3"
                                    maxlength="255" required>{{ old('memo', $currentShift->memo ?? '') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-primary sc-submit-btn">修正を申請する</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- 修正申請履歴 --}}
        <div class="card">
            <div class="card-header">シフト修正申請履歴</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>対象日</th>
                            <th>シフトパターン</th>
                            <th>出勤</th>
                            <th>退勤</th>
                            <th>メモ</th>
                            <th>状態</th>
                            <th>申請日時</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($shifts as $shift)
                        <tr>
                            <td data-label="対象日">{{ \Carbon\Carbon::parse($shift->target_date)->format('Y-m-d') }}</td>
                            <td data-label="シフトパターン">{{ $shiftMasterMap[$shift->master_id] ?? '不明' }}</td>
                            <td data-label="出勤">{{ \Carbon\Carbon::parse($shift->attendance_edit)->format('H:i') }}</td>
                            <td data-label="退勤">{{ \Carbon\Carbon::parse($shift->leaving_edit)->format('H:i') }}</td>
                            <td data-label="メモ">{{ $shift->memo }}</td>
                            <td data-label="状態">
                                @switch($shift->status)
                                @case('pending')
                                <span class="badge bg-warning text-dark">承認待ち</span>
                                @break
                                @case('approved')
                                <span class="badge bg-success">承認済み</span>
                                @break
                                @case('rejected')
                                <span class="badge bg-danger">却下</span>
                                @break
                                @default
                                <span class="badge bg-secondary">{{ $shift->status }}</span>
                                @endswitch
                            </td>
                            <td data-label="申請日時">{{ $shift->created_at->format('Y-m-d H:i') }}</td>
                            <td data-label="">
                                @if ($shift->status === 'pending')
                                <form method="POST" action="{{ route('shiftcorrection.destroy', $shift) }}"
                                    onsubmit="return confirm('この修正申請を取り消しますか？');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">取消</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">修正申請の履歴はありません。</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // シフトパターン選択時に修正時刻欄へ初期値を自動入力
        document.getElementById('master_id').addEventListener('change', function(e) {
            const option = e.target.selectedOptions[0];
            if (!option || !option.value) return;
            document.getElementById('attendance_edit').value = option.dataset.attendance || '';
            document.getElementById('leaving_edit').value = option.dataset.leaving || '';
        });
    </script>
</body>

</html>