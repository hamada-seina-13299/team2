document.addEventListener('DOMContentLoaded', function () {
    // 💡 ご指摘の通り、最初に関数宣言形式で定義（ホイスティングを可能にし、かつ先頭に配置）
    function openAddModal(date) {
        if (date && modalTargetDate) modalTargetDate.value = date;
        if (addModal) addModal.classList.remove('hidden');
        if (masterSelect && masterSelect.value) {
            const selectedCard = document.querySelector(
                ".master-option-card[data-master-id='" + masterSelect.value + "']"
            );
            if (selectedCard) selectedCard.click();
        }
    }

    function closeAddModal() {
        if (addModal) addModal.classList.add('hidden');
    }

    // HTMLのonclick等から呼べるようにグローバルに紐付け
    window.openAddModal = openAddModal;
    window.closeAddModal = closeAddModal;

    // --- 要素の取得 ---
    const formMode = document.getElementById('formMode');
    const addModal = document.getElementById('addModal');
    const modalTargetDateGroup = document.getElementById('modalTargetDateGroup');
    const modalTargetDate = document.getElementById('modalTargetDate');
    const bulkDateMessageGroup = document.getElementById('bulkDateMessageGroup');
    const modalBulkCount = document.getElementById('modalBulkCount');
    
    const masterSelect = document.getElementById('masterSelect');
    const newMasterFields = document.getElementById('newMasterFields');
    const toggleBtn = document.getElementById('toggleNewMaster');
    const displayTimeGroup = document.getElementById('displayTimeGroup');
    const attendanceDisplay = document.getElementById('attendanceDisplay');
    const leavingDisplay = document.getElementById('leavingDisplay');
    const optionCards = document.querySelectorAll('.master-option-card');
    const masterScrollArea = document.getElementById('masterScrollArea');
    const masterSelectHelpText = document.getElementById('masterSelectHelpText');

    // フッターの入れ替え用ボタン
    const submitShiftBtn = document.getElementById('submitShiftBtn');
    const submitMasterBtn = document.getElementById('submitMasterBtn');

    const checkboxes = document.querySelectorAll('.shift-bulk-checkbox');
    const bulkBtnContainer = document.getElementById('floatingBulkBtnContainer');
    const selectedCountSpan = document.getElementById('selectedCount');
    const openBulkModalBtn = document.getElementById('openBulkModalBtn');
    const shiftAddForm = document.getElementById('shiftAddForm');

    // 通常の単発追加ボタン
    document.querySelectorAll('.open-add-modal-btn').forEach(btn => {
        if (btn.id !== 'openBulkModalBtn') {
            btn.addEventListener('click', function () {
                if (formMode) formMode.value = 'select'; // 初期状態は選択モードに

                document.querySelectorAll('.bulk-date-input').forEach(el => el.remove());

                if (bulkDateMessageGroup) bulkDateMessageGroup.classList.add('hidden');
                if (modalTargetDateGroup) modalTargetDateGroup.classList.remove('hidden');

                if (modalTargetDate) {
                    modalTargetDate.readOnly = false;
                    modalTargetDate.style.pointerEvents = 'auto';
                    modalTargetDate.className = "w-full border border-gray-300 rounded-lg p-2.5 bg-white text-gray-800 shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all outline-none text-sm";
                }

                openAddModal(this.getAttribute('data-date'));
            });
        }
    });

    // 一括追加ボタン
    if (openBulkModalBtn) {
        openBulkModalBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (formMode) formMode.value = 'select'; // 初期状態は選択モードに

            const checkedBoxes = document.querySelectorAll('.shift-bulk-checkbox:checked');
            if (checkedBoxes.length === 0) return;

            const selectedDates = Array.from(checkedBoxes).map(cb => cb.value);
            if (!shiftAddForm) return;

            document.querySelectorAll('.bulk-date-input').forEach(el => el.remove());

            selectedDates.forEach(date => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.className = 'bulk-date-input';
                hiddenInput.name = 'target_dates[]';
                hiddenInput.value = date;
                shiftAddForm.appendChild(hiddenInput);
            });

            if (modalTargetDateGroup) modalTargetDateGroup.classList.add('hidden');
            
            if (modalTargetDate) {
                modalTargetDate.value = selectedDates[0];
                modalTargetDate.readOnly = true;
            }

            if (bulkDateMessageGroup && modalBulkCount) {
                modalBulkCount.textContent = selectedDates.length; 
                bulkDateMessageGroup.classList.remove('hidden');
            }

            openAddModal('');
        });
    }

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

    // マスタカード選択時
    optionCards.forEach(card => {
        card.addEventListener('click', function () {
            if (formMode) formMode.value = 'select';

            if (newMasterFields) newMasterFields.classList.add('hidden');
            if (toggleBtn) toggleBtn.textContent = '＋ 新規追加';
            if (masterScrollArea) masterScrollArea.classList.remove('hidden');
            if (masterSelectHelpText) masterSelectHelpText.classList.remove('invisible');
            if (displayTimeGroup) displayTimeGroup.classList.remove('hidden'); 

            if (submitShiftBtn) submitShiftBtn.classList.remove('hidden');
            if (submitMasterBtn) submitMasterBtn.classList.add('hidden');

            optionCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');

            if (masterSelect) masterSelect.value = this.dataset.masterId;
            updateDisplayTimes(this.dataset.attendance, this.dataset.leaving);
        });
    });

    // 新規登録のトグル切り替え時
    if (toggleBtn && newMasterFields) {
        toggleBtn.addEventListener('click', function () {
            newMasterFields.classList.toggle('hidden');

            if (!newMasterFields.classList.contains('hidden')) {
                if (formMode) formMode.value = 'new_master';

                toggleBtn.textContent = '✕ キャンセル';
                if (masterScrollArea) masterScrollArea.classList.add('hidden');
                if (masterSelectHelpText) masterSelectHelpText.classList.add('invisible');
                if (displayTimeGroup) displayTimeGroup.classList.add('hidden'); // 💡マスタ追加時は出勤退勤表示用を消す
                if (masterSelect) masterSelect.value = '';
                optionCards.forEach(c => c.classList.remove('selected'));
                updateDisplayTimes('', '');

                if (submitShiftBtn) submitShiftBtn.classList.add('hidden');
                if (submitMasterBtn) submitMasterBtn.classList.remove('hidden');
            } else {
                if (formMode) formMode.value = 'select';

                toggleBtn.textContent = '＋ 新規追加';
                if (masterScrollArea) masterScrollArea.classList.remove('hidden');
                if (masterSelectHelpText) masterSelectHelpText.classList.remove('invisible');
                if (displayTimeGroup) displayTimeGroup.classList.remove('hidden'); 

                if (submitShiftBtn) submitShiftBtn.classList.remove('hidden');
                if (submitMasterBtn) submitMasterBtn.classList.add('hidden');
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

    // エラー復元ブロック
    const errorEl = document.getElementById('error-data');
    if (errorEl) {
        const hasErrors = errorEl.getAttribute('data-has-errors') === 'true';
        const hasFieldsError = errorEl.getAttribute('data-has-fields-error') === 'true';
        const savedDate = errorEl.getAttribute('data-saved-date'); 
        const isBulk = errorEl.getAttribute('data-is-bulk') === 'true'; 
        const bulkCount = errorEl.getAttribute('data-bulk-count');       

        if (hasErrors) {
            openAddModal(savedDate || ''); 

            // 一括登録エラー時のUI復元
            if (isBulk) {
                if (modalTargetDateGroup) modalTargetDateGroup.classList.add('hidden');
                if (bulkDateMessageGroup && modalBulkCount) {
                    modalBulkCount.textContent = bulkCount;
                    bulkDateMessageGroup.classList.remove('hidden');
                }
            }

            // 新規作成中のUI復元（厳格化した条件判定により、通常未選択時はスキップされる）
            if (hasFieldsError && newMasterFields) {
                if (formMode) formMode.value = 'new_master';
                newMasterFields.classList.remove('hidden');
                if (toggleBtn) toggleBtn.textContent = '✕ キャンセル';
                if (masterScrollArea) masterScrollArea.classList.add('hidden');
                if (masterSelectHelpText) masterSelectHelpText.classList.add('invisible');
                if (displayTimeGroup) displayTimeGroup.classList.add('hidden'); // エラー復元時も非表示

                if (submitShiftBtn) submitShiftBtn.classList.add('hidden');
                if (submitMasterBtn) submitMasterBtn.classList.remove('hidden');
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
});