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
// 🕹️ モーダル制御：前日翌日移動・動的行追加・重複防止バリデーション
// ==========================================================================
document.addEventListener('DOMContentLoaded', () => {
    const modalOverlay = document.getElementById('fix-modal-overlay');
    const closeBtn = document.getElementById('close-fix-modal');
    const editTriggers = document.querySelectorAll('.btn-edit-trigger');
    const fixForm = document.getElementById('modal-fix-form');
    const tbody = document.getElementById('modal-table-tbody');
    const btnAddRow = document.getElementById('btn-add-punch-row');

    const btnPrev = document.getElementById('btn-modal-prev');
    const btnNext = document.getElementById('btn-modal-next');

    if (!modalOverlay || !fixForm || !tbody || !btnAddRow) return;

    const availableDatesEl = document.getElementById('available-dates-data');
    const availableDates = availableDatesEl ? JSON.parse(availableDatesEl.getAttribute('data-dates')) : [];
    let currentDataIndex = -1;

    // 固定要素の参照（ 既定休憩追加に関する要素 modal-break-auto を削除して5列構造に適合）
    const elements = {
        attTime: document.getElementById('modal-attendance-time'),
        attReason: document.getElementById('modal-attendance-reason'),
        deleteAtt: document.querySelector('input[name="delete_attendance"]'),
        errAttendance: document.getElementById('error-attendance'),
        leavingTime: document.getElementById('modal-leaving-time'),
        leavingReason: document.getElementById('modal-leaving-reason'),
        deleteLeaving: document.querySelector('input[name="delete_leaving"]'),
        errLeaving: document.getElementById('error-leaving')
    };

    // 現在画面上に存在する「打刻種別」を正確に集計する関数
    function getExistingTypes(excludeSelectElement = null) {
        const types = [];
        if (tbody.querySelector('#row-static-break-in')) types.push('休憩開始');
        if (tbody.querySelector('#row-static-break-out')) types.push('休憩終了');
        
        // 追加された他の動的行をすべてチェック
        tbody.querySelectorAll('.dynamic-row .row-type-select').forEach(select => {
            if (select !== excludeSelectElement && select.value) {
                types.push(select.value);
            }
        });
        return types;
    }

    // 画面上のすべてのセレクトボックスの選択肢をリアルタイムにクレンジングする関数（重複防止）
    function updateSelectOptions() {
        const dynamicRows = tbody.querySelectorAll('.dynamic-row');
        
        dynamicRows.forEach(row => {
            const select = row.querySelector('.row-type-select');
            const currentValue = select.value;
            
            // 自分以外の行で選択済みの種別を回収
            const usedTypes = getExistingTypes(select);
            
            let optionsHtml = '';
            
            // 他の行で使われていなければ「休憩開始」を選択肢に出す
            if (!usedTypes.includes('休憩開始')) {
                optionsHtml += `<option value="休憩開始" ${currentValue === '休憩開始' ? 'selected' : ''}>休憩開始</option>`;
            }
            // 他の行で使われていなければ「休憩終了」を選択肢に出す
            if (!usedTypes.includes('休憩終了')) {
                optionsHtml += `<option value="休憩終了" ${currentValue === '休憩終了' ? 'selected' : ''}>休憩終了</option>`;
            }
            // 勤務地変更は重複して何個でも追加可能
            optionsHtml += `<option value="勤務地変更" ${currentValue === '勤務地変更' ? 'selected' : ''}>勤務地変更</option>`;
            
            select.innerHTML = optionsHtml;
        });
    }

    // リアルタイム申請理由・未入力チェック
    function checkFormValidation() {
        let isAllValid = true;

        if (!elements.attTime) return true;

        // 1. 固定の出勤行チェック
        const isAttChanged = elements.attTime.value !== elements.attTime.getAttribute('data-init') ||
            (elements.deleteAtt && elements.deleteAtt.checked);
        if (isAttChanged && elements.attReason.value.trim() === '') {
            if (elements.errAttendance) elements.errAttendance.style.display = 'block';
            elements.attReason.style.borderColor = '#ef4444';
            isAllValid = false;
        } else {
            if (elements.errAttendance) elements.errAttendance.style.display = 'none';
            elements.attReason.style.borderColor = '';
        }

        // 2. 固定の退勤行チェック（ elements.breakAuto に依存していた判定ロジックを綺麗に削除）
        const isLeaveChanged = elements.leavingTime.value !== elements.leavingTime.getAttribute('data-init') ||
            (elements.deleteLeaving && elements.deleteLeaving.checked);
        if (isLeaveChanged && elements.leavingReason.value.trim() === '') {
            if (elements.errLeaving) elements.errLeaving.style.display = 'block';
            elements.leavingReason.style.borderColor = '#ef4444';
            isAllValid = false;
        } else {
            if (elements.errLeaving) elements.errLeaving.style.display = 'none';
            elements.leavingReason.style.borderColor = '';
        }

        // 3. 固定の休憩開始行チェック
        const staticBreakInRow = tbody.querySelector('#row-static-break-in');
        if (staticBreakInRow) {
            const breakInTime = staticBreakInRow.querySelector('#modal-break-time');
            const breakInReason = staticBreakInRow.querySelector('#modal-break-reason');
            const breakInDelete = staticBreakInRow.querySelector('input[name="delete_break"]');
            const errBreakIn = staticBreakInRow.querySelector('#error-break-in');

            const isBreakInChanged = breakInTime.value !== breakInTime.getAttribute('data-init') || (breakInDelete && breakInDelete.checked);
            if (isBreakInChanged && breakInReason.value.trim() === '') {
                if (errBreakIn) errBreakIn.style.display = 'block';
                breakInReason.style.borderColor = '#ef4444';
                isAllValid = false;
            } else {
                if (errBreakIn) errBreakIn.style.display = 'none';
                breakInReason.style.borderColor = '';
            }
        }

        // 4. 固定の休憩終了行チェック
        const staticBreakOutRow = tbody.querySelector('#row-static-break-out');
        if (staticBreakOutRow) {
            const breakOutTime = staticBreakOutRow.querySelector('#modal-break-out-time');
            const breakOutReason = staticBreakOutRow.querySelector('#modal-break-out-reason');
            const breakOutDelete = staticBreakOutRow.querySelector('input[name="delete_break_out"]');
            const errBreakOut = staticBreakOutRow.querySelector('#error-break-out');

            const isBreakOutChanged = breakOutTime.value !== breakOutTime.getAttribute('data-init') || (breakOutDelete && breakOutDelete.checked);
            if (isBreakOutChanged && breakOutReason.value.trim() === '') {
                if (errBreakOut) errBreakOut.style.display = 'block';
                breakOutReason.style.borderColor = '#ef4444';
                isAllValid = false;
            } else {
                if (errBreakOut) errBreakOut.style.display = 'none';
                breakOutReason.style.borderColor = '';
            }
        }

        // 5. ➕ 手動追加された動的行のチェック
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

    function updatePrevNextButtons() {
        if (!btnPrev || !btnNext) return;
        btnPrev.disabled = currentDataIndex >= availableDates.length - 1;
        btnNext.disabled = currentDataIndex <= 0;
    }

    // 固定の休憩行を生成（完全に不要な列を削り、綺麗な5列に変更）
    function injectStaticBreakRow(type, timeValue) {
        const tr = document.createElement('tr');
        tr.className = 'form-row-group';
        
        if (type === 'in') {
            tr.id = 'row-static-break-in';
            tr.innerHTML = `
                <td style="font-weight: bold;">休憩開始</td>
                <td><input type="time" name="break_time" id="modal-break-time" class="modal-input watch-change" value="${timeValue}" data-init="${timeValue}"></td>
                <td>-</td>
                <td style="text-align: left;">
                    <input type="text" name="break_reason" id="modal-break-reason" class="modal-input reason-input" placeholder="例: 休憩打刻の忘れのため">
                    <div class="error-msg" id="error-break-in" style="color: #ef4444; font-size: 11px; margin-top: 4px; display: none;">※申請理由を入力してください</div>
                </td>
                <td><input type="checkbox" name="delete_break" value="1" class="modal-checkbox watch-change"></td>
            `;
        } else {
            tr.id = 'row-static-break-out';
            tr.innerHTML = `
                <td style="font-weight: bold;">休憩終了</td>
                <td><input type="time" name="break_out_time" id="modal-break-out-time" class="modal-input watch-change" value="${timeValue}" data-init="${timeValue}"></td>
                <td>-</td>
                <td style="text-align: left;">
                    <input type="text" name="break_out_reason" id="modal-break-out-reason" class="modal-input reason-input" placeholder="例: 休憩終了打刻の忘れのため">
                    <div class="error-msg" id="error-break-out" style="color: #ef4444; font-size: 11px; margin-top: 4px; display: none;">※申請理由を入力してください</div>
                </td>
                <td><input type="checkbox" name="delete_break_out" value="1" class="modal-checkbox watch-change"></td>
            `;
        }

        tr.querySelectorAll('.watch-change, .reason-input').forEach(input => {
            input.addEventListener('input', checkFormValidation);
            input.addEventListener('change', checkFormValidation);
        });

        const leavingRow = tbody.querySelector('#row-leaving');
        const staticBreakIn = tbody.querySelector('#row-static-break-in');
        
        if (type === 'in' && leavingRow) {
            leavingRow.parentNode.insertBefore(tr, leavingRow.nextSibling);
        } else if (type === 'out' && staticBreakIn) {
            staticBreakIn.parentNode.insertBefore(tr, staticBreakIn.nextSibling);
        } else if (type === 'out' && leavingRow) {
            leavingRow.parentNode.insertBefore(tr, leavingRow.nextSibling);
        } else {
            tbody.appendChild(tr);
        }
    }

    // 手動追加される動的行（5列構造に完全適応 ＆ 重複種別を即座に間引く改良版）
    function createDynamicRow() {
        const tr = document.createElement('tr');
        tr.className = 'form-row-group dynamic-row';

        const existingTypes = getExistingTypes();

        // 画面上の状況に合わせ、最初期に自動選択されるべきデフォルト種別をインテリジェントに決定
        let defaultType = '勤務地変更';
        if (!existingTypes.includes('休憩開始')) {
            defaultType = '休憩開始';
        } else if (!existingTypes.includes('休憩終了')) {
            defaultType = '休憩終了';
        }

        // 不要な「-」列を完全排除した5列のHTML
        tr.innerHTML = `
            <td>
                <select name="dynamic_type[]" class="modal-select row-type-select watch-change">
                    <!-- updateSelectOptions() によって重複のない選択肢が瞬時に生成されます -->
                </select>
            </td>
            <td>
                <input type="time" name="dynamic_time[]" class="modal-input row-time-input watch-change">
                <div class="dynamic-time-error-msg" style="color: #ef4444; font-size: 11px; margin-top: 4px; display: none;">※打刻時間を入力してください</div>
            </td>
            <td class="row-working-place-cell">-</td>
            <td style="text-align: left;">
                <input type="text" name="dynamic_reason[]" class="modal-input row-reason-input reason-input" placeholder="例: 申請理由を入力してください">
                <div class="dynamic-error-msg" style="color: #ef4444; font-size: 11px; margin-top: 4px; display: none;">※申請理由を入力してください</div>
            </td>
            <td><button type="button" class="btn-row-delete" style="background:none; border:none; cursor:pointer; font-size:16px;">❌</button></td>
        `;

        const typeSelect = tr.querySelector('.row-type-select');
        typeSelect.value = defaultType;

        const placeCell = tr.querySelector('.row-working-place-cell');

        typeSelect.addEventListener('change', () => {
            if (typeSelect.value === '勤務地変更') {
                const placesDataEl = document.getElementById('available-places-data');
                const availablePlaces = placesDataEl ? JSON.parse(placesDataEl.getAttribute('data-places')) : ['本社', '在宅'];
                let selectHtml = `<select name="dynamic_working_place[]" class="modal-select watch-change">`;
                availablePlaces.forEach(p => selectHtml += `<option value="${p}">${p}</option>`);
                selectHtml += `</select>`;
                placeCell.innerHTML = selectHtml;
                placeCell.querySelector('select').addEventListener('change', checkFormValidation);
            } else {
                placeCell.innerHTML = '-';
            }
            updateSelectOptions(); //どこかの行が切り替わったら全動的行の選択肢を再最適化（重複を追放）
            checkFormValidation();
        });

        tr.querySelector('.btn-row-delete').addEventListener('click', () => {
            tr.remove();
            updateSelectOptions(); //行が削除されたら選択肢を他の行に即座に復活させる
            checkFormValidation();
        });

        tr.querySelectorAll('.watch-change, .reason-input').forEach(input => {
            input.addEventListener('input', checkFormValidation);
            input.addEventListener('change', checkFormValidation);
        });

        tbody.appendChild(tr);

        // 選択肢のバインドとチェンジイベントの着火
        updateSelectOptions();
        typeSelect.dispatchEvent(new Event('change'));
        checkFormValidation();
    }

    // コントローラーから渡された全履歴JSONからデータを読み込む
    function loadDateDataByIndex(index) {
        if (index < 0 || index >= availableDates.length) return;
        currentDataIndex = index;
        const targetDate = availableDates[currentDataIndex];

        //全履歴データが入ったJSONを読み込む
        const historyJsonEl = document.getElementById('all-history-json-data');
        const allHistory = historyJsonEl ? JSON.parse(historyJsonEl.getAttribute('data-history')) : {};
        const dayData = allHistory[targetDate];
        const daysShort = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        // 日付ラベルの生成用（1週間前より過去はボタンがないので自前でフォーマット）
        document.getElementById('modal-target-date-input').value = targetDate;
        
        //ボタン（DOM）が存在すればその data-date-label を使い、無ければ自前で「YYYY年M月D日(ShortDay)」を生成
        const trigger = document.querySelector(`.btn-edit-trigger[data-date="${targetDate}"]`);
        if (trigger) {
            document.getElementById('modal-target-date-label').textContent = trigger.getAttribute('data-date-label');
        } else {
            // ハイフン区切りをパースして日付オブジェクト化 (タイムゾーンのズレを防ぐためスプリット)
            const parts = targetDate.split('-');
            const dateObj = new Date(parts[0], parts[1] - 1, parts[2]);
            
            const year = dateObj.getFullYear();
            const month = dateObj.getMonth() + 1;
            const day = dateObj.getDate();
            const dayName = daysShort[dateObj.getDay()];
            
            // 画像のフォーマット「2026年6月29日(Mon)」を完全再現
            document.getElementById('modal-target-date-label').textContent = `${year}年${month}月${day}日(${dayName})`;
        }

        // dayData（JSON）が存在すればそこから、無ければ空欄をセット
        if (elements.attTime) elements.attTime.value = (dayData && dayData.attendance) ? dayData.attendance : '';
        if (elements.leavingTime) elements.leavingTime.value = (dayData && dayData.leaving) ? dayData.leaving : '';

        if (elements.deleteAtt) elements.deleteAtt.checked = false;
        if (elements.deleteLeaving) elements.deleteLeaving.checked = false;
        if (elements.attReason) elements.attReason.value = '';
        if (elements.leavingReason) elements.leavingReason.value = '';

        if (elements.attTime) elements.attTime.setAttribute('data-init', elements.attTime.value);
        if (elements.leavingTime) elements.leavingTime.setAttribute('data-init', elements.leavingTime.value);

        // 古い動的・固定休憩行をリセット
        tbody.querySelectorAll('.dynamic-row').forEach(row => row.remove());
        const oldIn = tbody.querySelector('#row-static-break-in');
        if (oldIn) oldIn.remove();
        const oldOut = tbody.querySelector('#row-static-break-out');
        if (oldOut) oldOut.remove();

        // JSONデータから休憩開始・終了の時間をマッピング
        const breakInTime = (dayData && dayData.break_time) ? dayData.break_time : '';
        const breakOutTime = (dayData && dayData.break_out) ? dayData.break_out : '';

        if (breakInTime !== '') injectStaticBreakRow('in', breakInTime);
        if (breakOutTime !== '') injectStaticBreakRow('out', breakOutTime);

        updateSelectOptions(); 
        checkFormValidation();
        const correctionRows = tbody.parentNode.querySelectorAll('.correction-history-row');
        const emptyRow = tbody.parentNode.querySelector('.correction-empty-row');
        let visibleCount = 0;

        correctionRows.forEach(row => {
            if (row.getAttribute('data-date') === targetDate) {
                row.style.display = ''; // 表示
                visibleCount++;
            } else {
                row.style.display = 'none'; // 非表示
            }
        });

        // 該当する日の履歴が1件もない場合は「履歴はありません」の行を出す
        if (emptyRow) {
            if (visibleCount === 0) {
                emptyRow.style.display = '';
                // colspanを全列分に設定
                emptyRow.querySelector('td').setAttribute('colspan', '8');
            } else {
                emptyRow.style.display = 'none';
            }
        }
        updatePrevNextButtons();
    }

    editTriggers.forEach(t => {
        t.addEventListener('click', (e) => {
            e.preventDefault();
            const idx = availableDates.indexOf(t.getAttribute('data-date'));
            if (idx !== -1) loadDateDataByIndex(idx);
            modalOverlay.classList.add('is-open');
        });
    });

    if (btnPrev) btnPrev.addEventListener('click', () => currentDataIndex < availableDates.length - 1 && loadDateDataByIndex(currentDataIndex + 1));
    if (btnNext) btnNext.addEventListener('click', () => currentDataIndex > 0 && loadDateDataByIndex(currentDataIndex - 1));
    
    btnAddRow.addEventListener('click', () => createDynamicRow());

    modalOverlay.querySelectorAll('.watch-change, .reason-input').forEach(field => {
        field.addEventListener('input', checkFormValidation);
        field.addEventListener('change', checkFormValidation);
    });

    fixForm.addEventListener('submit', (e) => {
        if (!checkFormValidation()) {
            e.preventDefault();
            alert('未入力の必須項目、または申請理由が不足している項目があります。');
        }
    });

    if (closeBtn) closeBtn.addEventListener('click', () => modalOverlay.classList.remove('is-open'));
    modalOverlay.addEventListener('click', (e) => e.target === modalOverlay && modalOverlay.classList.remove('is-open'));
});

// ==========================================================================
// 🔗 メイン画面の「変更」リンクとの連動処理
// ==========================================================================
document.addEventListener('DOMContentLoaded', function () {
    const locationChangeBtn = document.getElementById('trigger-location-change');
    if (!locationChangeBtn) return;
    locationChangeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        const modal = document.getElementById('fix-modal-overlay');
        if (!modal) return;
        modal.classList.add('is-open');
        const mainTrigger = document.querySelector('.bottom-actions .btn-edit-trigger');
        if (mainTrigger) mainTrigger.click();

        setTimeout(() => {
            const addRowBtn = document.getElementById('btn-add-punch-row');
            if (addRowBtn) addRowBtn.click();
            const typeSelects = document.querySelectorAll('.row-type-select');
            if (typeSelects.length > 0) {
                const lastSelect = typeSelects[typeSelects.length - 1];
                lastSelect.value = '勤務地変更';
                lastSelect.dispatchEvent(new Event('change'));
            }
        }, 100);
    });
});

// ==========================================================================
// 🎛️ 既定の休憩を追加：トグルスイッチ非同期制御
// ==========================================================================
document.addEventListener('DOMContentLoaded', () => {
    const toggleInput = document.getElementById('toggle-auto-break');
    const slider = toggleInput ? toggleInput.nextElementSibling : null;
    const toggleText = slider ? slider.querySelector('.toggle-text') : null;
    const errorContainer = document.getElementById('dynamic-flash-error');
    const errorMessage = document.getElementById('dynamic-flash-message');
    const closeErrorBtn = document.getElementById('close-dynamic-flash');

    if (!toggleInput || !slider) return;

    const updateToggleText = (isLeft) => { if (toggleText) toggleText.textContent = isLeft ? 'ON' : 'OFF'; };
    updateToggleText(toggleInput.checked);

    toggleInput.addEventListener('change', function () {
        const isChecked = this.checked;
        updateToggleText(isChecked);
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value;

        fetch('/dashboard/toggle-auto-break', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ can_auto_break: isChecked })
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || '設定の変更に失敗しました。');
            if (errorContainer) errorContainer.style.display = 'none';
        })
        .catch(error => {
            toggleInput.checked = !isChecked;
            updateToggleText(!isChecked);
            if (errorContainer && errorMessage) {
                errorMessage.textContent = error.message;
                errorContainer.style.display = 'flex';
            }
        });
    });

    if (closeErrorBtn && errorContainer) closeErrorBtn.addEventListener('click', () => errorContainer.style.display = 'none');
});

// ==========================================================================
// ⏱️ アラート自動消去システム
// ==========================================================================
document.addEventListener('DOMContentLoaded', () => {
    const sessionAlerts = document.querySelectorAll('.main-card ~ div, .main-card + div, div[style*="background-color"]');
    sessionAlerts.forEach(alert => {
        if (alert.id === 'dynamic-flash-error') return;
        const bg = alert.style.backgroundColor;
        if (bg.includes('rgb(209, 250, 229)') || bg.includes('rgb(254, 226, 226)') || bg.includes('#d1fae5') || bg.includes('#fee2e2')) {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 600);
            }, 5000);
        }
    });

    const errorContainer = document.getElementById('dynamic-flash-error');
    if (errorContainer) {
        const observer = new MutationObserver(() => {
            if (errorContainer.style.display === 'flex') {
                setTimeout(() => {
                    errorContainer.style.transition = 'opacity 0.6s ease';
                    errorContainer.style.opacity = '0';
                    setTimeout(() => { errorContainer.style.display = 'none'; errorContainer.style.opacity = '1'; }, 600);
                }, 5000);
            }
        });
        observer.observe(errorContainer, { attributes: true, attributeFilter: ['style'] });
    }
});