document.addEventListener('DOMContentLoaded', () => {
    // 🕒 リアルタイム時計
    const hoursMinutesElement = document.getElementById('clock-hm');
    const secondsElement = document.getElementById('clock-s');
    if (hoursMinutesElement && secondsElement) {
        setInterval(() => {
            const now = new Date();
            hoursMinutesElement.textContent = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
            secondsElement.textContent = String(now.getSeconds()).padStart(2, '0');
        }, 1000);
    }
});

// ==========================================================================
// 🕹️ モーダル制御：前日翌日移動・動的行追加・バリデーション
// ==========================================================================
document.addEventListener('DOMContentLoaded', () => {
    const modalOverlay = document.getElementById('fix-modal-overlay');
    const closeBtn = document.getElementById('close-fix-modal');
    const editTriggers = document.querySelectorAll('.btn-edit-trigger');
    const fixForm = document.getElementById('modal-fix-form');
    const tbody = document.getElementById('modal-table-tbody');
    const btnAddRow = document.getElementById('btn-add-punch-row');
    
    // 💡 ID取得の記述不備を修正しました
    const btnPrev = document.getElementById('btn-modal-prev');
    const btnNext = document.getElementById('btn-modal-next');

    if (!modalOverlay || !fixForm || !tbody || !btnAddRow) return;

    // 履歴に存在する日付リストを配列として取得
    const availableDatesEl = document.getElementById('available-dates-data');
    const availableDates = availableDatesEl ? JSON.parse(availableDatesEl.getAttribute('data-dates')) : [];
    let currentDataIndex = -1;

    // 固定（初期）要素
    const elements = {
        attTime: document.getElementById('modal-attendance-time'),
        workingPlace: document.getElementById('modal-working-place'),
        attReason: document.getElementById('modal-attendance-reason'),
        deleteAtt: document.querySelector('input[name="delete_attendance"]'),
        errAttendance: document.getElementById('error-attendance'),
        leavingTime: document.getElementById('modal-leaving-time'),
        leavingReason: document.getElementById('modal-leaving-reason'),
        breakAuto: document.getElementById('modal-break-auto'),
        deleteLeaving: document.querySelector('input[name="delete_leaving"]'),
        errLeaving: document.getElementById('error-leaving')
    };

    // 💡 リアルタイム申請理由・未入力チェック
    function checkFormValidation() {
        let isAllValid = true;

        if (!elements.attTime) return true;

        // 1. 固定の出勤行チェック
        const isAttChanged = elements.attTime.value !== elements.attTime.getAttribute('data-init') ||
                             elements.workingPlace.value !== elements.workingPlace.getAttribute('data-init') ||
                             (elements.deleteAtt && elements.deleteAtt.checked);
        if (isAttChanged && elements.attReason.value.trim() === '') {
            if (elements.errAttendance) elements.errAttendance.style.display = 'block';
            elements.attReason.style.borderColor = '#ef4444';
            isAllValid = false;
        } else {
            if (elements.errAttendance) elements.errAttendance.style.display = 'none';
            elements.attReason.style.borderColor = '';
        }

        // 2. 固定の退勤行チェック
        const isLeaveChanged = elements.leavingTime.value !== elements.leavingTime.getAttribute('data-init') ||
                               elements.breakAuto.value !== elements.breakAuto.getAttribute('data-init') ||
                               (elements.deleteLeaving && elements.deleteLeaving.checked);
        if (isLeaveChanged && elements.leavingReason.value.trim() === '') {
            if (elements.errLeaving) elements.errLeaving.style.display = 'block';
            elements.leavingReason.style.borderColor = '#ef4444';
            isAllValid = false;
        } else {
            if (elements.errLeaving) elements.errLeaving.style.display = 'none';
            elements.leavingReason.style.borderColor = '';
        }

        // 3. ➕ 動的に追加された行のチェック
        const dynamicRows = tbody.querySelectorAll('.dynamic-row');
        dynamicRows.forEach(row => {
            const timeInput = row.querySelector('.row-time-input');
            const reasonInput = row.querySelector('.row-reason-input');
            const errMsg = row.querySelector('.dynamic-error-msg');
            const timeErrMsg = row.querySelector('.dynamic-time-error-msg');

            if (timeInput.value === '') {
                if (timeErrMsg) timeErrMsg.style.display = 'block';
                timeInput.style.borderColor = '#ef4444';
                isAllValid = false;
            } else {
                if (timeErrMsg) timeErrMsg.style.display = 'none';
                timeInput.style.borderColor = '';
            }

            if (reasonInput.value.trim() === '') {
                if (errMsg) errMsg.style.display = 'block';
                reasonInput.style.borderColor = '#ef4444';
                isAllValid = false;
            } else {
                if (errMsg) errMsg.style.display = 'none';
                reasonInput.style.borderColor = '';
            }
        });

        return isAllValid;
    }

    // 💡 前日・翌日ボタンの活性・非活性（白黒化）制御
    function updatePrevNextButtons() {
        if (!btnPrev || !btnNext) return;
        
        // 前のデータ（より古い日付。配列の後方）があるか
        if (currentDataIndex >= availableDates.length - 1) {
            btnPrev.disabled = true;
        } else {
            btnPrev.disabled = false;
        }

        // 次のデータ（より新しい日付。配列の前方）があるか
        if (currentDataIndex <= 0) {
            btnNext.disabled = true;
        } else {
            btnNext.disabled = false;
        }
    }

    // 💡 指定インデックスの勤怠データをモーダルにマッピングする関数
    function loadDateDataByIndex(index) {
        if (index < 0 || index >= availableDates.length) return;
        currentDataIndex = index;
        const targetDate = availableDates[currentDataIndex];

        // 対応する履歴のトリガー要素を画面上から探索してデータを抽出
        const trigger = document.querySelector(`.btn-edit-trigger[data-date="${targetDate}"]`);
        if (!trigger) return;

        const targetDateLabel = trigger.getAttribute('data-date-label');
        const attendanceTime = trigger.getAttribute('data-attendance');
        const leavingTime = trigger.getAttribute('data-leaving');
        const workingPlace = trigger.getAttribute('data-place');

        // モーダル各部に流し込み
        document.getElementById('modal-target-date-input').value = targetDate;
        document.getElementById('modal-target-date-label').textContent = targetDateLabel;
        
        elements.attTime.value = attendanceTime;
        elements.leavingTime.value = leavingTime;
        elements.workingPlace.value = workingPlace || '本社';
        elements.breakAuto.value = 'OFF';

        if (elements.deleteAtt) elements.deleteAtt.checked = false;
        if (elements.deleteLeaving) elements.deleteLeaving.checked = false;
        elements.attReason.value = '';
        elements.leavingReason.value = '';

        // 初期状態として記憶
        elements.attTime.setAttribute('data-init', attendanceTime);
        elements.workingPlace.setAttribute('data-init', workingPlace || '本社');
        elements.leavingTime.setAttribute('data-init', leavingTime);
        elements.breakAuto.setAttribute('data-init', 'OFF');

        // 動的に追加されていた行はリセットしてクリア
        tbody.querySelectorAll('.dynamic-row').forEach(row => row.remove());

        checkFormValidation();
        updatePrevNextButtons();
    }

    // 各履歴の「修正」ボタンクリックイベント
    editTriggers.forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const targetDate = trigger.getAttribute('data-date');
            const dateIdx = availableDates.indexOf(targetDate);
            if (dateIdx !== -1) {
                loadDateDataByIndex(dateIdx);
            }
            modalOverlay.classList.add('is-open');
        });
    });

    // 📅 前日ボタンクリック時
    if (btnPrev) {
        btnPrev.addEventListener('click', () => {
            if (currentDataIndex < availableDates.length - 1) {
                loadDateDataByIndex(currentDataIndex + 1);
            }
        });
    }

    // 📅 翌日ボタンクリック時
    if (btnNext) {
        btnNext.addEventListener('click', () => {
            if (currentDataIndex > 0) {
                loadDateDataByIndex(currentDataIndex - 1);
            }
        });
    }

    // 💡 ➕ 「打刻追加」行追加処理
    btnAddRow.addEventListener('click', () => {
        const rowId = 'dynamic-row-' + Date.now();
        const tr = document.createElement('tr');
        tr.className = 'form-row-group dynamic-row';
        tr.id = rowId;

        tr.innerHTML = `
            <td>
                <select name="dynamic_type[]" class="modal-select row-type-select watch-change">
                    <option value="出勤">出勤</option>
                    <option value="退勤" selected>退勤</option>
                    <option value="休憩開始">休憩開始</option>
                    <option value="休憩終了">休憩終了</option>
                    <option value="勤務地変更">勤務地変更</option>
                </select>
            </td>
            <td>
                <input type="time" name="dynamic_time[]" class="modal-input row-time-input watch-change">
                <div class="dynamic-time-error-msg" style="color: #ef4444; font-size: 11px; margin-top: 4px; display: none;">※打刻時間を入力してください</div>
            </td>
            <td>-</td>
            <td style="text-align: left;">
                <input type="text" name="dynamic_reason[]" class="modal-input row-reason-input reason-input" placeholder="例: 申請理由を入力してください">
                <div class="dynamic-error-msg" style="color: #ef4444; font-size: 11px; margin-top: 4px; display: none;">※申請理由を入力してください</div>
            </td>
            <td>-</td>
            <td>
                <button type="button" class="btn-row-delete">❌</button>
            </td>
        `;

        // ❌行削除ボタンの動作紐付け
        tr.querySelector('.btn-row-delete').addEventListener('click', () => {
            tr.remove();
            checkFormValidation();
        });

        // 変更イベントの再バインド
        tr.querySelectorAll('.watch-change, .reason-input').forEach(input => {
            input.addEventListener('input', checkFormValidation);
            input.addEventListener('change', checkFormValidation);
        });

        tbody.appendChild(tr);
        checkFormValidation();
    });

    // 既存フィールドの監視設定
    const watchFields = modalOverlay.querySelectorAll('.watch-change, .reason-input');
    watchFields.forEach(field => {
        field.addEventListener('input', checkFormValidation);
        field.addEventListener('change', checkFormValidation);
    });

    // 申請ボタン最終バリデーション
    fixForm.addEventListener('submit', (e) => {
        if (!checkFormValidation()) {
            e.preventDefault();
            alert('未入力の必須項目、または申請理由が不足している項目があります。');
        }
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', () => modalOverlay.classList.remove('is-open'));
    }
    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) modalOverlay.classList.remove('is-open');
    });
});