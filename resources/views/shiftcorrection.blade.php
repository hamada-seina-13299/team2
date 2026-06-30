@extends('layouts.app')

@section('title', 'シフト修正')

@section('content')
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
        {{-- 対象日選択 --}}
        <div class="col-12 mb-4">
            <form method="GET" action="{{ route('shiftcorrection.index') }}" class="d-flex align-items-end gap-2">
                <div>
                    <label for="target_date_select" class="form-label">対象日</label>
                    <input type="date" id="target_date_select" name="target_date" class="form-control"
                           value="{{ $targetDate }}">
                </div>
                <button type="submit" class="btn btn-outline-secondary">表示</button>
            </form>
        </div>

        {{-- 打刻実績（実際の出勤・退勤） --}}
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">打刻実績 ({{ $targetDate }})</div>
                <div class="card-body">
                    @if ($working)
                        <table class="table table-borderless mb-0">
                            <tr>
                                <th class="w-50">出勤時刻</th>
                                <td>{{ \Carbon\Carbon::parse($working->attendance)->format('H:i') }}</td>
                            </tr>
                            <tr>
                                <th>退勤時刻</th>
                                <td>{{ $working->leaving ? \Carbon\Carbon::parse($working->leaving)->format('H:i') : '-' }}</td>
                            </tr>
                            <tr>
                                <th>休憩時間</th>
                                <td>{{ $working->break_time ? \Carbon\Carbon::parse($working->break_time)->format('H:i') : '-' }}</td>
                            </tr>
                            <tr>
                                <th>勤務場所</th>
                                <td>{{ $working->working_place }}</td>
                            </tr>
                            <tr>
                                <th>ステータス</th>
                                <td>{{ $working->status }}</td>
                            </tr>
                        </table>
                    @else
                        <p class="text-muted mb-0">この日の打刻実績はありません。</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- 修正申請フォーム --}}
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">シフト修正申請</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('shiftcorrection.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="target_date" class="form-label">対象日 <span class="text-danger">*</span></label>
                            <input type="date" id="target_date" name="target_date" class="form-control"
                                   value="{{ old('target_date', $targetDate) }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="master_id" class="form-label">シフトパターン <span class="text-danger">*</span></label>
                            <select id="master_id" name="master_id" class="form-select" required>
                                <option value="">選択してください</option>
                                @foreach ($shiftMasters as $master)
                                    <option value="{{ $master->id }}"
                                        data-attendance="{{ \Carbon\Carbon::parse($master->attendance)->format('H:i') }}"
                                        data-leaving="{{ \Carbon\Carbon::parse($master->leaving)->format('H:i') }}"
                                        {{ old('master_id') == $master->id ? 'selected' : '' }}>
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
                                <input type="time" id="attendance_edit" name="attendance_edit" class="form-control"
                                       value="{{ old('attendance_edit') }}" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label for="leaving_edit" class="form-label">修正後 退勤時刻 <span class="text-danger">*</span></label>
                                <input type="time" id="leaving_edit" name="leaving_edit" class="form-control"
                                       value="{{ old('leaving_edit') }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="memo" class="form-label">修正理由（メモ） <span class="text-danger">*</span></label>
                            <textarea id="memo" name="memo" class="form-control" rows="3"
                                      maxlength="255" required>{{ old('memo') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">修正を申請する</button>
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
                            <td>{{ $shift->target_date->format('Y-m-d') }}</td>
                            <td>{{ $shift->master_id }}</td>
                            <td>{{ \Carbon\Carbon::parse($shift->attendance_edit)->format('H:i') }}</td>
                            <td>{{ \Carbon\Carbon::parse($shift->leaving_edit)->format('H:i') }}</td>
                            <td>{{ $shift->memo }}</td>
                            <td>
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
                            <td>{{ $shift->created_at->format('Y-m-d H:i') }}</td>
                            <td>
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

<script>
    // シフトパターン選択時に修正時刻欄へ初期値を自動入力
    document.getElementById('master_id').addEventListener('change', function (e) {
        const option = e.target.selectedOptions[0];
        if (!option || !option.value) return;
        document.getElementById('attendance_edit').value = option.dataset.attendance || '';
        document.getElementById('leaving_edit').value = option.dataset.leaving || '';
    });
</script>
@endsection
