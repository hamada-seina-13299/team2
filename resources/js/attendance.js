document.addEventListener('DOMContentLoaded', function () {
    const updateUrlBase = document.getElementById('attendance-url-base-meta')?.value || "{{ url('attendance') }}";
    const csrfToken = document.getElementById('csrf-token-meta')?.value || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const checkLateUrl = document.getElementById('check-late-url-meta')?.value || "/attendance/check-late";

    const timeLabels = {
        '遅刻': '遅刻時刻', '早退': '早退時刻', '欠勤': null, '有給': null,
        '半休': '半休開始時刻', '残業': '残業終了時刻', '有事遅刻': '遅刻時刻', '有事早退': '早退時刻'
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

    window.openEditModal = function (id, dateStr, type, time, memo) {
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

    window.closeModal = function () {
        document.getElementById('attendanceModal').style.display = 'none';
    }

    window.openStampModal = function (date, attendance, leaving, breakTime) {
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

    window.closeStampModal = function () {
        document.getElementById('stampEditModal').style.display = 'none';
    }

    function showToast(message, type = 'error') {
        let toast = document.getElementById('custom-toast-container');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'custom-toast-container';
            toast.style = 'position: fixed; top: 24px; right: 24px; z-index: 10005; color: #fff; padding: 14px 28px; border-radius: 10px; font-weight: 600; box-shadow: 0 10px 30px rgba(0,0,0,0.15); transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); transform: translateY(-20px); opacity: 0; pointer-events: none;';
            document.body.appendChild(toast);
        }
        // 要素が非表示設定になっていたら解除する
        toast.style.display = 'block';
        toast.style.background = type === 'success' ? '#2ecc71' : '#e74c3c';
        toast.textContent = message;
        setTimeout(() => { toast.style.opacity = '1'; toast.style.transform = 'translateY(0)'; }, 50);
    }

    function showCustomConfirm(message) {
        return new Promise((resolve) => {
            const overlay = document.createElement('div');
            overlay.style = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.4); z-index: 10000; display: flex; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.2s ease;';
            overlay.innerHTML = `
                <div style="background: #fff; padding: 28px; border-radius: 14px; width: 90%; max-width: 400px; box-shadow: 0 15px 35px rgba(0,0,0,0.15); text-align: center; transform: scale(0.9); transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
                    <div style="font-size: 16px; font-weight: 600; color: #2c3e50; margin-bottom: 24px; line-height: 1.6; white-space: pre-wrap;">${message}</div>
                    <div style="display: flex; justify-content: center; gap: 12px;">
                        <button id="confirm-cancel" style="background: #f1f2f6; color: #57606f; border: none; padding: 11px 22px; border-radius: 8px; font-weight: 600; cursor: pointer;">キャンセル</button>
                        <button id="confirm-ok" style="background: #3498db; color: #fff; border: none; padding: 11px 22px; border-radius: 8px; font-weight: 600; cursor: pointer;">確定</button>
                    </div>
                </div>
            `;
            document.body.appendChild(overlay);
            setTimeout(() => { overlay.style.opacity = '1'; overlay.querySelector('div').style.transform = 'scale(1)'; }, 10);

            overlay.querySelector('#confirm-ok').addEventListener('click', () => { overlay.remove(); resolve(true); });
            overlay.querySelector('#confirm-cancel').addEventListener('click', () => { overlay.remove(); resolve(false); });
        });
    }

    function executeMonthlyAction(url, year, month) {
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ year: year, month: month })
        })
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    showToast(resData.message, 'success');
                    setTimeout(() => { location.reload(); }, 1000);
                } else {
                    showToast(resData.message || '処理に失敗しました。', 'error');
                }
            })
            .catch(() => showToast('通信エラーが発生しました。', 'error'));
    }

    // 全体のクリックイベント監視（バブリングフェーズに変更して干渉を回避）
    document.addEventListener('click', async function (e) {
        // 1. 申請するボタン
        const submitBtn = e.target.closest('#js-trigger-submit');
        if (submitBtn) {
            e.preventDefault();
            const form = document.getElementById('monthly-submit-form');
            const year = form.querySelector('input[name="year"]').value;
            const month = form.querySelector('input[name="month"]').value;

            if (document.querySelector('.late-badge') || document.querySelector('.early-badge')) {
                showToast('勤怠修正してください。未修正の遅刻・早退があります。', 'error');
                return;
            }

            fetch(checkLateUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ year: year, month: month })
            })
                .then(res => res.json())
                .then(async data => {
                    if (data.has_uncorrected_late || data.has_uncorrected_early) {
                        showToast('勤怠修正してください。未修正の遅刻・早退があります。', 'error');
                        return;
                    }
                    if (await showCustomConfirm(`${year}年${month}月の勤怠を申請します。よろしいですか？`)) {
                        executeMonthlyAction(form.action, year, month);
                    }
                })
                .catch(async () => {
                    if (await showCustomConfirm(`${year}年${month}月の勤怠を申請します。よろしいですか？`)) {
                        executeMonthlyAction(form.action, year, month);
                    }
                });
            return;
        }

        // 2. 提出取り下げボタン
        const cancelBtn = e.target.closest('#js-trigger-cancel');
        if (cancelBtn) {
            e.preventDefault();
            const form = document.getElementById('monthly-cancel-form');
            if (await showCustomConfirm('申請を取り下げますか？')) {
                const year = form.querySelector('input[name="year"]').value;
                const month = form.querySelector('input[name="month"]').value;
                executeMonthlyAction(form.action, year, month);
            }
            return;
        }

        // 3. 編集ボタン
        const editBtn = e.target.closest('.js-edit-btn');
        if (editBtn) {
            openEditModal(editBtn.dataset.id, editBtn.dataset.date, editBtn.dataset.type, editBtn.dataset.time, editBtn.dataset.memo);
            return;
        }

        // 4. 打刻修正ボタン
        const stampBtn = e.target.closest('.js-stamp-edit-btn');
        if (stampBtn) {
            openStampModal(stampBtn.dataset.date, stampBtn.dataset.attendance, stampBtn.dataset.leaving, stampBtn.dataset.break);
            return;
        }

        // 5. タブ切り替え
        const tabBtn = e.target.closest('.tab-btn');
        if (tabBtn) {
            const targetTab = tabBtn.getAttribute('data-tab');
            document.querySelectorAll('.tab-panel').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            const panel = document.getElementById('tab-' + targetTab);
            if (panel) panel.style.display = 'block';
            tabBtn.classList.add('active');
            return;
        }
    });

    // 削除確認
    document.addEventListener('submit', async function (e) {
        const targetForm = e.target;
        if (targetForm.classList.contains('js-delete-form')) {
            e.preventDefault();
            if (await showCustomConfirm('この申請を削除しますか？')) {
                targetForm.submit();
            }
        }
    });
});