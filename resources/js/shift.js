document.addEventListener('DOMContentLoaded', function () {
    const addModal = document.getElementById('addModal');
    const modalTargetDateGroup = document.getElementById('modalTargetDateGroup');
    const modalTargetDate = document.getElementById('modalTargetDate');
    const bulkDateMessageGroup = document.getElementById('bulkDateMessageGroup');
    const modalBulkCount = document.getElementById('modalBulkCount');
    
    const masterSelect = document.getElementById('masterSelect');
    const newMasterFields = document.getElementById('newMasterFields');
    const toggleBtn = document.getElementById('toggleNewMaster');
    const attendanceDisplay = document.getElementById('attendanceDisplay');
    const leavingDisplay = document.getElementById('leavingDisplay');
    const optionCards = document.querySelectorAll('.master-option-card');
    const masterScrollArea = document.getElementById('masterScrollArea');
    const masterSelectHelpText = document.getElementById('masterSelectHelpText');

    // 一括用の要素たち
    const checkboxes = document.querySelectorAll('.shift-bulk-checkbox');
    const bulkBtnContainer = document.getElementById('floatingBulkBtnContainer');
    const selectedCountSpan = document.getElementById('selectedCount');
    const openBulkModalBtn = document.getElementById('openBulkModalBtn');
    const shiftAddForm = document.getElementById('shiftAddForm');

    // ==========================================
    // 📅 1. 通常の単発追加ボタン
    // ==========================================
    document.querySelectorAll('.open-add-modal-btn').forEach(btn => {
        if (btn.id !== 'openBulkModalBtn') {
            btn.addEventListener('click', function () {
                // 一括用の古いインプットがあれば掃除
                document.querySelectorAll('.bulk-date-input').forEach(el => el.remove());

                // 💡 通常時はメッセージを隠し、洗練された単発用入力欄を表示する
                if (bulkDateMessageGroup) bulkDateMessageGroup.classList.add('hidden');
                if (modalTargetDateGroup) modalTargetDateGroup.classList.remove('hidden');

                if (modalTargetDate) {
                    modalTargetDate.disabled = false;
                    modalTargetDate.style.pointerEvents = 'auto';
                    // 画像3枚目の太い黒枠線をなくし、モダンでスマートなTailwindスタイルを適用
                    modalTargetDate.className = "w-full border border-gray-300 rounded-lg p-2.5 bg-white text-gray-800 shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all outline-none text-sm";
                }

                openAddModal(this.getAttribute('data-date'));
            });
        }
    });

    // ==========================================
    // 🚀 2. 一括登録ボタン（右下）を押したとき
    // ==========================================
    if (openBulkModalBtn) {
        openBulkModalBtn.addEventListener('click', function(e) {
            e.preventDefault();

            const checkedBoxes = document.querySelectorAll('.shift-bulk-checkbox:checked');
            if (checkedBoxes.length === 0) return;

            const selectedDates = Array.from(checkedBoxes).map(cb => cb.value);
            if (!shiftAddForm) return;

            // 古い一括用隠しデータをクリア
            document.querySelectorAll('.bulk-date-input').forEach(el => el.remove());

            // 選択された日付の数だけ hidden 属性を作って埋め込む
            selectedDates.forEach(date => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.className = 'bulk-date-input';
                hiddenInput.name = 'target_dates[]';
                hiddenInput.value = date;
                shiftAddForm.appendChild(hiddenInput);
            });

            // 💡【ご要望のポイント】一括時は対象日入力欄を非表示にし、下部のメッセージボックスのみにする
            if (modalTargetDateGroup) modalTargetDateGroup.classList.add('hidden');
            
            if (modalTargetDate) {
                modalTargetDate.value = selectedDates[0]; // サブミット時の保険として最初の値をセット
                modalTargetDate.disabled = true;
            }

            if (bulkDateMessageGroup && modalBulkCount) {
                modalBulkCount.textContent = selectedDates.length; 
                bulkDateMessageGroup.classList.remove('hidden'); // メッセージボックスのみ表示
            }

            // モーダルを開く
            openAddModal('');
        });
    }

    // ==========================================
    // ▢ 3. チェックボックスの監視（右下ボタン表示）
    // ==========================================
    function toggleBulkButton() {
        const checkedBoxes = document.querySelectorAll('.shift-bulk-checkbox:checked');
        const count = checkedBoxes.length;

        if (bulkBtnContainer && selectedCountSpan) {
            if (count > 0) {
                selectedCountSpan.textContent = count;
                bulkBtnContainer.classList.remove('hidden');
            } else {
                bulkBtnContainer.classList.add('hidden');
            }
        }
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', toggleBulkButton);
    });

    // ==========================================
    // 🗑️ 4. その他の既存機能
    // ==========================================
    document.querySelectorAll('.delete-shift-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            const confirmDate = this.getAttribute('data-confirm-date');
            if (!confirm(confirmDate + 'のシフトを削除しますか？')) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('.delete-master-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const masterId = this.getAttribute('data-master-id');
            const masterName = this.getAttribute('data-master-name');

            if (confirm(masterName + 'のマスタを削除しますか？')) {
                const form = document.getElementById('masterDeleteForm');
                if (form) {
                    document.getElementById('deleteMasterId').value = masterId;
                    form.submit();
                }
            }
        });
    });

    optionCards.forEach(card => {
        card.addEventListener('click', function () {
            if (newMasterFields) newMasterFields.classList.add('hidden');
            if (toggleBtn) toggleBtn.textContent = '＋ 新規追加';
            if (masterScrollArea) masterScrollArea.classList.remove('hidden');
            if (masterSelectHelpText) masterSelectHelpText.classList.remove('invisible');

            optionCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');

            if (masterSelect) masterSelect.value = this.dataset.masterId;
            updateDisplayTimes(this.dataset.attendance, this.dataset.leaving);
        });
    });

    if (toggleBtn && newMasterFields) {
        toggleBtn.addEventListener('click', function () {
            newMasterFields.classList.toggle('hidden');

            if (!newMasterFields.classList.contains('hidden')) {
                toggleBtn.textContent = '✕ キャンセル';
                if (masterScrollArea) masterScrollArea.classList.add('hidden');
                if (masterSelectHelpText) masterSelectHelpText.classList.add('invisible');
                if (masterSelect) masterSelect.value = '';
                optionCards.forEach(c => c.classList.remove('selected'));
                updateDisplayTimes('', '');
            } else {
                toggleBtn.textContent = '＋ 新規追加';
                if (masterScrollArea) masterScrollArea.classList.remove('hidden');
                if (masterSelectHelpText) masterSelectHelpText.classList.remove('invisible');
            }
        });
    }

    if (masterSelect && masterSelect.value) {
        const selectedCard = document.querySelector(
            ".master-option-card[data-master-id='" + masterSelect.value + "']"
        );
        if (selectedCard) {
            selectedCard.classList.add('selected');
            updateDisplayTimes(selectedCard.dataset.attendance, selectedCard.dataset.leaving);
        }
    }

    const errorEl = document.getElementById('error-data');
    if (errorEl) {
        const hasErrors = errorEl.getAttribute('data-has-errors') === 'true';
        const hasFieldsError = errorEl.getAttribute('data-has-fields-error') === 'true';

        if (hasErrors) {
            openAddModal('');
            if (hasFieldsError && newMasterFields) {
                newMasterFields.classList.remove('hidden');
                if (toggleBtn) toggleBtn.textContent = '✕ キャンセル';
                if (masterScrollArea) masterScrollArea.classList.add('hidden');
                if (masterSelectHelpText) masterSelectHelpText.classList.add('invisible');
            }
        }
    }

    function updateDisplayTimes(attendance, leaving) {
        if (!attendanceDisplay || !leavingDisplay) return;
        attendanceDisplay.value = attendance
            ? (attendance.match(/\d{2}:\d{2}/) ? attendance.match(/\d{2}:\d{2}/)[0] : '')
            : '';
        leavingDisplay.value = leaving
            ? (leaving.match(/\d{2}:\d{2}/) ? leaving.match(/\d{2}:\d{2}/)[0] : '')
            : '';
    }

    window.openAddModal = function (date) {
        if (date && modalTargetDate) modalTargetDate.value = date;
        if (addModal) addModal.classList.remove('hidden');
        if (masterSelect && masterSelect.value) {
            const selectedCard = document.querySelector(
                ".master-option-card[data-master-id='" + masterSelect.value + "']"
            );
            if (selectedCard) selectedCard.click();
        }
    };

    window.closeAddModal = function () {
        if (addModal) addModal.classList.add('hidden');
    };
});