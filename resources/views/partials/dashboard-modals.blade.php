{{--
    🔁 共有パーツ：打刻修正モーダル ＋ 勤怠申請モーダル
    ダッシュボード画面・勤務表画面など、複数の画面から @include('partials.dashboard-modals') で呼び出す想定。

    このパーツが正しく動くために、呼び出し元のController側で以下の変数を渡してください
    （渡されていない場合は空扱いでフォールバックするので画面は壊れませんが、機能が一部動きません）。
      - $allWorkingDates   : ユーザーの全打刻日の配列（前日/翌日ナビゲーションで使用）
      - $allHistoryJson    : $allWorkingDates に対応する打刻データのJSON（DashboardControllerの実装を参照）
      - $workingPlaces     : 勤務地の選択肢一覧
      - $correctionHistory : 打刻修正申請の履歴一覧

    また、打刻修正のトリガーには class="btn-edit-trigger" と
    data-date / data-date-label / data-attendance / data-leaving / data-break / data-break-out / data-place
    を、勤怠申請のトリガーには openAttendanceRequestModal(date) を呼ぶJSをそれぞれ用意してください
    （dashboard.js が自動でイベントを拾います）。
--}}

<div class="modal-overlay" id="fix-modal-overlay">
    {{-- 過去全ての打刻日（一週間分に限定しないリスト）を配列としてJSに渡す --}}
    <div id="available-dates-data" data-dates="{{ json_encode($allWorkingDates ?? []) }}" style="display:none;"></div>
    <div id="all-history-json-data" data-history="{{ $allHistoryJson ?? '{}' }}" style="display:none;"></div>
    <div id="available-places-data" data-places="{{ json_encode($workingPlaces ?? []) }}" style="display:none;"></div>

    <div class="modal-container">

        <div class="modal-header">
            <button type="button" class="btn-break" id="btn-modal-prev" style="width: auto; padding: 4px 12px;">&lt; 前日</button>
            <div class="modal-title">
                <span id="modal-target-date-label"></span> 📅
            </div>
            <button type="button" class="btn-break" id="btn-modal-next" style="width: auto; padding: 4px 12px;">翌日 &gt;</button>
        </div>

        <form action="{{ route('clock.correct') }}" method="POST" id="modal-fix-form">
            @csrf
            <input type="hidden" name="target_date" id="modal-target-date-input">

            <table class="modal-table">
                <thead>
                    <tr>
                        <th style="width: 14%;">打刻種別</th>
                        <th style="width: 20%;">打刻時間</th>
                        <th style="width: 15%;">勤務地</th>
                        <th>申請理由</th>
                        <th style="width: 8%;">削除</th>
                    </tr>
                </thead>
                <tbody id="modal-table-tbody">
                    <tr class="form-row-group static-row" id="row-attendance">
                        <td style="font-weight: bold;">出勤</td>
                        <td>
                            <input type="time" name="attendance_time" id="modal-attendance-time" class="modal-input watch-change">
                        </td>
                        <td>-</td>
                        <td style="text-align: left;">
                            <input type="text" name="attendance_reason" id="modal-attendance-reason" class="modal-input reason-input" placeholder="例: 打刻忘れのため">
                            <div class="error-msg" id="error-attendance" style="color: #ef4444; font-size: 11px; margin-top: 4px; display: none;">※申請理由を入力してください</div>
                        </td>
                        <td><input type="checkbox" name="delete_attendance" value="1" class="modal-checkbox watch-change"></td>
                    </tr>

                    <tr class="form-row-group static-row" id="row-leaving">
                        <td style="font-weight: bold;">退勤</td>
                        <td>
                            <input type="time" name="leaving_time" id="modal-leaving-time" class="modal-input watch-change">
                        </td>
                        <td>-</td>
                        <td style="text-align: left;">
                            <input type="text" name="leaving_reason" id="modal-leaving-reason" class="modal-input reason-input" placeholder="例: 残業の申請忘れ">
                            <div class="error-msg" id="error-leaving" style="color: #ef4444; font-size: 11px; margin-top: 4px; display: none;">※申請理由を入力してください</div>
                        </td>
                        <td><input type="checkbox" name="delete_leaving" value="1" class="modal-checkbox watch-change"></td>
                    </tr>
                </tbody>
            </table>

            <button type="button" class="btn-break" id="btn-add-punch-row" style="width: auto; margin-bottom: 20px;">＋ 打刻追加</button>

            <div style="font-size: 14px; font-weight: bold; color: #1aaba8; margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px;">
                打刻申請履歴
            </div>
            <table class="modal-table" style="font-size: 12px;">
                <thead>
                    <tr>
                        <th>操作</th>
                        <th>申請日時</th>
                        <th>ステータス</th>
                        <th>追加種別</th>
                        <th>修正前</th>
                        <th>修正後</th>
                        <th>打刻補足情報</th>
                        <th>更新日時</th>
                    </tr>
                </thead>
                <tbody id="modal-correction-history-tbody">
                    @forelse (($correctionHistory ?? collect()) as $correction)
                    <tr class="correction-history-row" data-date="{{ $correction->target_date }}">
                        <td>
                            <button type="button" class="btn-cancel-correction" data-id="{{ $correction->id }}" style="background-color: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 11px;">
                                取消
                            </button>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($correction->created_at)->format('Y/m/d H:i') }}</td>
                        <td>
                            {{--DBから取得したステータスをそのまま表示 --}}
                            <span>{{ $correction->status }}</span>
                            {{--DBに記録された「更新者名」を表示 --}}
                            @if(!empty($correction->updater_name))
                            <br><span style="font-size: 10px; color: #6b7280;">({{ $correction->updater_name }})</span>
                            @endif
                        </td>
                        <td>
                            @if($correction->before_working_place !== $correction->after_working_place)
                            勤務地変更
                            @else
                            時間修正
                            @endif
                        </td>

                        <td style="text-align: left; font-family: monospace; vertical-align: top;">
                            @if($correction->before_attendance !== $correction->after_attendance)
                            <div>出: {{ $correction->before_attendance ? \Carbon\Carbon::parse($correction->before_attendance)->format('H:i') : '未打刻' }}</div>
                            @endif
                            @if($correction->before_leaving !== $correction->after_leaving)
                            <div>退: {{ $correction->before_leaving ? \Carbon\Carbon::parse($correction->before_leaving)->format('H:i') : '未打刻' }}</div>
                            @endif
                            @if($correction->before_break_time !== $correction->after_break_time)
                            <div>憩始: {{ $correction->before_break_time ? \Carbon\Carbon::parse($correction->before_break_time)->format('H:i') : '未打刻' }}</div>
                            @endif
                            @if($correction->before_break_end_time !== $correction->after_break_end_time)
                            <div>憩終: {{ $correction->before_break_end_time ? \Carbon\Carbon::parse($correction->before_break_end_time)->format('H:i') : '未打刻' }}</div>
                            @endif
                            @if($correction->before_working_place !== $correction->after_working_place)
                            <div>場所: {{ $correction->before_working_place }}</div>
                            @endif
                        </td>

                        {{-- 変更があった項目のみを表示（太字） --}}
                        <td style="text-align: left; font-family: monospace; font-weight: bold; vertical-align: top;">
                            @if($correction->before_attendance !== $correction->after_attendance)
                            <div>出: {{ $correction->after_attendance ? \Carbon\Carbon::parse($correction->after_attendance)->format('H:i') : '削除' }}</div>
                            @endif
                            @if($correction->before_leaving !== $correction->after_leaving)
                            <div>退: {{ $correction->after_leaving ? \Carbon\Carbon::parse($correction->after_leaving)->format('H:i') : '削除' }}</div>
                            @endif
                            @if($correction->before_break_time !== $correction->after_break_time)
                            <div>憩始: {{ $correction->after_break_time ? \Carbon\Carbon::parse($correction->after_break_time)->format('H:i') : '削除' }}</div>
                            @endif
                            @if($correction->before_break_end_time !== $correction->after_break_end_time)
                            <div>憩終: {{ $correction->after_break_end_time ? \Carbon\Carbon::parse($correction->after_break_end_time)->format('H:i') : '削除' }}</div>
                            @endif
                            @if($correction->before_working_place !== $correction->after_working_place)
                            <div>場所: {{ $correction->after_working_place }}</div>
                            @endif
                        </td>

                        <td style="text-align: left; max-width: 150px; white-space: pre-line;">{{ $correction->memo }}</td>
                        <td>{{ \Carbon\Carbon::parse($correction->updated_at)->format('Y/m/d H:i') }}</td>
                    </tr>
                    @empty
                    <tr class="correction-empty-row">
                        <td colspan="8" style="color: #9ca3af; padding: 12px;">申請履歴はありません。</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="modal-footer">
                <button type="button" class="btn-close-modal" id="close-fix-modal">閉じる</button>
                <button type="submit" class="btn-submit-modal active">申請</button>
            </div>
        </form>

    </div>
</div>

{{-- 勤怠申請モーダル --}}
@if ($errors->any() && old('_form') === 'attendance_request')
<div id="dash-request-reopen-flag" data-date="{{ old('target_date') }}" style="display:none;"></div>
@endif
<div id="attendance-request-modal-overlay" class="dashboard-modal-overlay">
    <div class="dashboard-modal-box" id="dashboard-modal-box" style="max-width: 480px;">
        <div class="dashboard-date-nav" style="padding: 0 5px 10px 5px; border-bottom: 1px solid #ddd; margin-bottom: 20px;">
            <div class="dashboard-date-title" style="font-size: 18px; font-weight: bold;">⏰ 勤怠申請</div>
        </div>

        <p style="color:red; font-size:12px; margin: -10px 0 15px 0; text-align:left;">*は必須項目です</p>

        <form id="dashboard-attendance-form" action="{{ route('attendance.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="dash_method_field" value="POST">
            {{-- このモーダルからの送信であることを判別するための目印（他フォームのエラーと混同しないため） --}}
            <input type="hidden" name="_form" value="attendance_request">

            <div style="margin-bottom: 15px; text-align: left;">
                <label for="dash_target_date" style="display:block; font-weight:bold; margin-bottom: 5px; font-size:14px;">対象日 <span style="color:red;">*</span></label>
                <input type="date" id="dash_target_date" name="target_date" value="{{ old('target_date') }}" required class="dashboard-input-text" style="width:100%; height:38px;">
                @error('target_date')
                <span style="color:#dc2626; font-size:12px; display:block; margin-top:4px;">{{ $message }}</span>
                @enderror
            </div>

            <div style="margin-bottom: 15px; text-align: left;">
                <label for="dash_request_type" style="display:block; font-weight:bold; margin-bottom: 5px; font-size:14px;">申請種別 <span style="color:red;">*</span></label>
                <select id="dash_request_type" name="request_type" required class="dashboard-input-text" style="width:100%; height:38px; background-color:#fff;">
                    <option value="">選択してください</option>
                    <option value="遅刻" @selected(old('request_type') === '遅刻')>遅刻</option>
                    <option value="早退" @selected(old('request_type') === '早退')>早退</option>
                    <option value="欠勤" @selected(old('request_type') === '欠勤')>欠勤</option>
                    <option value="有給" @selected(old('request_type') === '有給')>有給</option>
                    <option value="半休" @selected(old('request_type') === '半休')>半休</option>
                    <option value="残業" @selected(old('request_type') === '残業')>残業</option>
                    <option value="有事遅刻" @selected(old('request_type') === '有事遅刻')>有事遅刻</option>
                    <option value="有事早退" @selected(old('request_type') === '有事早退')>有事早退</option>
                </select>
                @error('request_type')
                <span style="color:#dc2626; font-size:12px; display:block; margin-top:4px;">{{ $message }}</span>
                @enderror
            </div>

            <div id="dash_time_wrapper" style="margin-bottom: 15px; text-align: left;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:5px;">
                    <label for="dash_request_time" id="dash_time_label" style="font-weight:bold; font-size:14px;">申請時刻</label>
                    <div id="dash_sync_toggle_wrapper" style="display:none; align-items:center; gap:8px;">
                        <span style="font-size:12px; color:#7f8c8d;">打刻に合わせる</span>
                        <label class="switch">
                            <input type="checkbox" id="dash_sync_punch_toggle">
                            <span class="slider">
                                <span class="slider-ball"></span>
                                <span class="toggle-text">OFF</span>
                            </span>
                        </label>
                    </div>
                </div>
                <input type="time" id="dash_request_time" name="request_time" value="{{ old('request_time') }}" class="dashboard-input-time" style="width:100%; height:38px;">
                {{-- ONの間、サーバー側で「打刻に合わせた」申請だと判別するためのフラグ --}}
                <input type="hidden" name="sync_with_punch" id="dash_sync_punch_field" value="{{ old('sync_with_punch', '0') }}">
                @error('request_time')
                <span id="dash_error_request_time" style="color:#dc2626; font-size:12px; display:block; margin-top:4px;">{{ $message }}</span>
                @enderror
            </div>

            <div id="dash_halfday_wrapper" style="margin-bottom: 15px; text-align: left; display:none;">
                <label for="dash_halfday_type" style="display:block; font-weight:bold; margin-bottom: 5px; font-size:14px;">半休区分 <span style="color:red;">*</span></label>
                <select id="dash_halfday_type" name="halfday_type" class="dashboard-input-text" style="width:100%; height:38px; background-color:#fff;">
                    <option value="前半休" @selected(old('halfday_type') === '前半休')>前半休</option>
                    <option value="後半休" @selected(old('halfday_type') === '後半休')>後半休</option>
                </select>
                @error('halfday_type')
                <span style="color:#dc2626; font-size:12px; display:block; margin-top:4px;">{{ $message }}</span>
                @enderror
            </div>

            <div style="margin-bottom: 15px; text-align: left;">
                <label for="dash_memo" style="display:block; font-weight:bold; margin-bottom: 5px; font-size:14px;">申請理由・補足事項をご記入ください <span style="color:red;">*</span></label>
                <input type="text" id="dash_memo" name="memo" maxlength="255" required value="{{ old('memo') }}" placeholder="例: 電車遅延のため、私用のため、体調不良のため" class="dashboard-input-text" style="width:100%; height:38px;">
                @error('memo')
                <span style="color:#dc2626; font-size:12px; display:block; margin-top:4px;">{{ $message }}</span>
                @enderror
            </div>

            <div style="margin-bottom: 25px; text-align: left;">
                <label for="dash_attachment" style="display:block; font-weight:bold; margin-bottom: 5px; font-size:14px;">添付ファイル</label>
                <div id="dash_dropzone" class="dashboard-dropzone">
                    <span id="dash_dropzone_label">ここにファイルをドロップ<br>または</span>
                    <div>
                        <button type="button" class="dashboard-btn-close" id="dash_dropzone_btn" style="margin-top:10px; padding: 6px 16px; font-size:13px;">📎 ファイル選択</button>
                    </div>
                    <img id="dash_dropzone_preview" class="dashboard-dropzone-preview" style="display:none;" alt="添付ファイルのプレビュー">
                    <span class="dashboard-dropzone-filename" id="dash_dropzone_filename"></span>
                </div>
                @error('attachment')
                <span style="color:#dc2626; font-size:12px; display:block; margin-top:4px;">{{ $message }}</span>
                @enderror
                <input type="file" id="dash_attachment" name="attachment" style="display:none;">
            </div>

            <div class="dashboard-footer-actions" style="border-top: 1px solid #ddd; padding-top: 15px; margin-top: 10px;">
                <button type="button" class="dashboard-btn-close" id="dash_close_btn">閉じる</button>
                <button type="submit" class="dashboard-btn-submit">申請</button>
            </div>
        </form>
    </div>
</div>