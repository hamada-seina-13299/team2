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

{{-- 参考ダッシュボード画面の見た目・構成を完全再現した「打刻修正」モーダル --}}
<div id="stampEditModal" class="dashboard-modal-overlay" style="display:none;">
    <div class="dashboard-modal-box">
        <form id="stampEditForm" action="{{ route('clock.correct') }}" method="POST">
            @csrf
            <input type="hidden" id="stamp_target_date" name="date">

            {{-- 上部ナビゲーションバー --}}
            <div class="dashboard-date-nav">
                <button type="button" class="dashboard-nav-btn">&lt; 前日</button>
                <div id="stamp_display_date" class="dashboard-date-title"></div>
                <button type="button" class="dashboard-nav-btn" disabled>翌日 &gt;</button>
            </div>

            {{-- 打刻行テーブル --}}
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

            {{-- 打刻追加ボタン --}}
            <button type="button" class="dashboard-btn-add">＋ 打刻追加</button>

            {{-- 打刻申請履歴セクション --}}
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

            {{-- フッターアクションボタン（閉じる・申請） --}}
            <div class="dashboard-footer-actions">
                <button type="button" class="dashboard-btn-close" onclick="closeStampModal()">閉じる</button>
                <button type="submit" class="dashboard-btn-submit">申請</button>
            </div>
        </form>
    </div>
</div>

<script>
    const updateUrlBase = "{{ url('attendance') }}";

    const timeLabels = {
        '遅刻':   '遅刻時刻',
        '早退':   '早退時刻',
        '欠勤':   null,
        '有給':   null,
        '半休':   '半休開始時刻',
        '残業':   '残業終了時刻',
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

    /* 打刻修正モーダル開閉処理 */
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

    // イベントリスナー登録
    document.querySelectorAll('.js-edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openEditModal(btn.dataset.id, btn.dataset.date, btn.dataset.type, btn.dataset.time, btn.dataset.memo);
        });
    });

    document.querySelectorAll('.js-stamp-edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openStampModal(btn.dataset.date, btn.dataset.attendance, btn.dataset.leaving, btn.dataset.break);
        });
    });
</script>