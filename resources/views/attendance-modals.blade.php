{{-- 既存の「編集」モーダル --}}
<div id="attendanceModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <span id="modalTitle">勤怠申請</span>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="attendanceForm" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="method_field" value="">
            <div class="form-group">
                <label for="target_date">対象日</label>
                <input type="date" id="target_date" name="target_date" required>
            </div>
            <div class="form-group">
                <label for="request_type">申請種別</label>
                <select id="request_type" name="request_type" required onchange="updateTimeField()">
                    <option value="">選択してください</option>
                    <option value="遅刻">遅刻</option>
                    <option value="早退">早退</option>
                    <option value="欠勤">欠勤</option>
                    <option value="有給">有給</option>
                    <option value="半休">半休</option>
                    <option value="残業">残業</option>
                    <option value="有事遅刻">有事遅刻</option>
                    <option value="有事早退">有事早退</option>
                </select>
            </div>
            <div class="form-group" id="request_time_wrapper">
                <label for="request_time" id="request_time_label">申請時刻</label>
                <input type="time" id="request_time" name="request_time">
            </div>
            <div class="form-group">
                <label for="memo">メモ</label>
                <input type="text" id="memo" name="memo" maxlength="255" required>
            </div>
            <div class="form-group">
                <label for="attachment">添付ファイル</label>
                <input type="file" id="attachment" name="attachment">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">キャンセル</button>
                <button type="submit" class="btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>

{{-- 打刻修正モーダル --}}
<div id="stampEditModal" class="dashboard-modal-overlay" style="display:none;">
    <div class="dashboard-modal-box">
        <form id="stampEditForm" action="{{ route('clock.correct') }}" method="POST">
            @csrf
            <input type="hidden" id="stamp_target_date" name="date">

            <div class="dashboard-date-nav">
                <button type="button" class="dashboard-nav-btn">&lt; 前日</button>
                <div id="stamp_display_date" class="dashboard-date-title"></div>
                <button type="button" class="dashboard-nav-btn" disabled>翌日 &gt;</button>
            </div>

            <table class="dashboard-stamp-table">
                <thead>
                    <tr>
                        <th>打刻種別</th>
                        <th>打刻時間</th>
                        <th>勤務地</th>
                        <th>申請理由</th>
                        <th class="dashboard-center-text">削除</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="dashboard-type-label">出勤</td>
                        <td><input type="time" id="stamp_attendance" name="attendance" class="dashboard-input-time"></td>
                        <td>-</td>
                        <td><input type="text" name="reason_attendance" class="dashboard-input-text" placeholder="例：打刻忘れのため"></td>
                        <td class="dashboard-center-text"><input type="checkbox" name="delete_attendance"></td>
                    </tr>
                    <tr>
                        <td class="dashboard-type-label">退勤</td>
                        <td><input type="time" id="stamp_leaving" name="leaving" class="dashboard-input-time"></td>
                        <td>-</td>
                        <td><input type="text" name="reason_leaving" class="dashboard-input-text" placeholder="例：残業の申請忘れ"></td>
                        <td class="dashboard-center-text"><input type="checkbox" name="delete_leaving"></td>
                    </tr>
                    <tr>
                        <td class="dashboard-type-label">休憩開始</td>
                        <td><input type="time" id="stamp_break_start" name="break_start" class="dashboard-input-time"></td>
                        <td>-</td>
                        <td><input type="text" name="reason_break_start" class="dashboard-input-text" placeholder="例：休憩打刻の忘れのため"></td>
                        <td class="dashboard-center-text"><input type="checkbox" name="delete_break_start"></td>
                    </tr>
                    <tr>
                        <td class="dashboard-type-label">休憩終了</td>
                        <td><input type="time" id="stamp_break_end" name="break_end" class="dashboard-input-time"></td>
                        <td>-</td>
                        <td><input type="text" name="reason_break_end" class="dashboard-input-text" placeholder="例：休憩終了打刻の忘れのため"></td>
                        <td class="dashboard-center-text"><input type="checkbox" name="delete_break_end"></td>
                    </tr>
                </tbody>
            </table>

            <button type="button" class="dashboard-btn-add">＋ 打刻追加</button>

            <div class="dashboard-history-section">
                <div class="dashboard-history-title">打刻申請履歴</div>
                <table class="dashboard-history-table">
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
                    <tbody>
                        <tr>
                            <td colspan="8" class="dashboard-no-data">申請履歴はありません。</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="dashboard-footer-actions">
                <button type="button" class="dashboard-btn-close" onclick="closeStampModal()">閉じる</button>
                <button type="submit" class="dashboard-btn-submit">申請</button>
            </div>
        </form>
    </div>
</div>

<script>
    // メイン画面の隠し要素から各種通信設定を取得
    const updateUrlBase = document.getElementById('attendance-url-base-meta')?.value || "{{ url('attendance') }}";
    const csrfToken = document.getElementById('csrf-token-meta')?.value;
    const checkLateUrl = document.getElementById('check-late-url-meta')?.value;

    const timeLabels = {
        '遅刻': '遅刻時刻',
        '早退': '早退時刻',
        '欠勤': null,
        '有給': null,
        '半休': '半休開始時刻',
        '残業': '残業終了時刻',
        '有事遅刻': '遅刻時刻',
        '有事早退': '早退時刻',
    };

    function updateTimeField() {
        const type = document.getElementById('request_type').value;
        const label = timeLabels[type];
        const wrapper = document.getElementById('request_time_wrapper');
        const input = document.getElementById('request_time');

        if (type && label === null) {
            wrapper.style.display = 'none';
            input.required = false;
            input.value = '';
        } else {
            wrapper.style.display = '';
            input.required = true;
            document.getElementById('request_time_label').textContent = label || '申請時刻';
        }
    }

    function openEditModal(id, dateStr, type, time, memo) {
        document.getElementById('modalTitle').textContent = '勤慢申請の編集';
        document.getElementById('attendanceForm').action = updateUrlBase + '/' + id;
        document.getElementById('method_field').value = 'PUT';
        document.getElementById('target_date').value = dateStr;
        document.getElementById('request_type').value = type;
        document.getElementById('request_time').value = time;
        document.getElementById('memo').value = memo;
        document.getElementById('attachment').value = '';
        updateTimeField();
        document.getElementById('attendanceModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('attendanceModal').style.display = 'none';
    }

    function openStampModal(date, attendance, leaving, breakTime) {
        const d = new Date(date);
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const formattedDate = `${d.getFullYear()}年${d.getMonth() + 1}月${d.getDate()}日(${dayNames[d.getDay()]}) 📅`;

        document.getElementById('stamp_display_date').innerHTML = formattedDate;
        document.getElementById('stamp_target_date').value = date;
        document.getElementById('stamp_attendance').value = attendance || '';
        document.getElementById('stamp_leaving').value = leaving || '';

        document.getElementById('stamp_break_start').value = attendance ? '12:00' : '';
        document.getElementById('stamp_break_end').value = attendance ? '13:00' : '';

        document.getElementById('stampEditModal').style.display = 'flex';
    }

    function closeStampModal() {
        document.getElementById('stampEditModal').style.display = 'none';
    }

    // イベントバインディング
    document.addEventListener('DOMContentLoaded', function() {

        // 主勤怠 / その他 タブ切り替え処理
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                document.querySelectorAll('.tab-panel').forEach(el => el.style.display = 'none');
                document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));

                const panel = document.getElementById('tab-' + targetTab);
                if (panel) panel.style.display = 'block';
                this.classList.add('active');
            });
        });

        // 編集ボタン
        document.querySelectorAll('.js-edit-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                openEditModal(btn.dataset.id, btn.dataset.date, btn.dataset.type, btn.dataset.time, btn.dataset.memo);
            });
        });

        // 打刻修正ボタン
        document.querySelectorAll('.js-stamp-edit-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                openStampModal(btn.dataset.date, btn.dataset.attendance, btn.dataset.leaving, btn.dataset.break);
            });
        });

        // 削除確認
        document.querySelectorAll('.js-delete-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('この申請を削除しますか？')) {
                    e.preventDefault();
                }
            });
        });

        // 非同期（Fetch API）月次申請・取り下げ制御
        const actionContainer = document.getElementById('monthly-action-container');
        if (actionContainer) {
            actionContainer.addEventListener('submit', function(e) {
                const targetForm = e.target;

                if (targetForm.id === 'monthly-submit-form') {
                    e.preventDefault();
                    const year = targetForm.querySelector('input[name="year"]').value;
                    const month = targetForm.querySelector('input[name="month"]').value;

                    // 1. まず画面上に「遅」または「早」のバッジが存在するかを直接チェック
                    const hasLateBadge = document.querySelector('.late-badge') !== null;
                    const hasEarlyBadge = document.querySelector('.early-badge') !== null;

                    // 遅刻または早退のマークが1つでも残っている場合は、即座に申請を拒否する
                    if (hasLateBadge || hasEarlyBadge) {
                        alert('勤怠修正してください');
                        return; // ここで完全に処理をストップ
                    }

                    // 2. 画面上に遅刻・早退マークがない場合のみ、バックエンドの最終確認を走らせる
                    if (!checkLateUrl) {
                        if (confirm('この月の勤怠を申請します。よろしいですか？')) {
                            executeMonthlyAction(targetForm.action, year, month, 'submit');
                        }
                        return;
                    }

                    fetch(checkLateUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                year: year,
                                month: month
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            // バックエンド側で未修正の遅刻・早退が残っていると判定された場合もブロック
                            if (data.has_uncorrected_late || data.has_uncorrected_early) {
                                alert('勤怠修正してください');
                                return;
                            }

                            if (confirm('この月の勤怠を申請します。よろしいですか？')) {
                                executeMonthlyAction(targetForm.action, year, month, 'submit');
                            }
                        });
                }

                if (targetForm.id === 'monthly-cancel-form') {
                    e.preventDefault();
                    if (confirm('申請を取り下げますか？')) {
                        const year = targetForm.querySelector('input[name="year"]').value;
                        const month = targetForm.querySelector('input[name="month"]').value;
                        executeMonthlyAction(targetForm.action, year, month, 'cancel');
                    }
                }
            });
        }

        function executeMonthlyAction(url, year, month, type) {
            fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        year: year,
                        month: month
                    })
                })
                .then(res => res.json())
                .then(resData => {
                    if (resData.success) {
                        const alertEl = document.getElementById('ajax-alert');
                        alertEl.textContent = resData.message;
                        alertEl.style.display = 'block';

                        const badge = document.getElementById('monthly-status-badge');
                        if (type === 'submit') {
                            badge.textContent = '申請済み';
                            badge.className = 'status-badge status-申請済み';
                            actionContainer.innerHTML = `
                            <form id="monthly-cancel-form" action="${updateUrlBase}/cancel" method="POST" class="inline-form">
                                <input type="hidden" name="_token" value="${csrfToken}">
                                <input type="hidden" name="year" value="${year}">
                                <input type="hidden" name="month" value="${month}">
                                <button type="submit" class="btn-large-action btn-submit-cancel">提出取り下げ</button>
                            </form>
                        `;
                        } else {
                            badge.textContent = '未申請';
                            badge.className = 'status-badge status-未申請';
                            actionContainer.innerHTML = `
                            <form id="monthly-submit-form" action="${updateUrlBase}/submit" method="POST" class="inline-form">
                                <input type="hidden" name="_token" value="${csrfToken}">
                                <input type="hidden" name="year" value="${year}">
                                <input type="hidden" name="month" value="${month}">
                                <button type="submit" class="btn-large-action btn-submit-active">申請する</button>
                            </form>
                        `;
                        }
                    } else {
                        alert(resData.message || '処理に失敗しました。');
                    }
                })
                .catch(() => alert('通信エラーが発生しました。'));
        }
    });
</script>