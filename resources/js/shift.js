document.addEventListener('DOMContentLoaded', function () {
    const addModal = document.getElementById('addModal');
    const modalTargetDate = document.getElementById('modalTargetDate');
    const masterSelect = document.getElementById('masterSelect');
    const newMasterFields = document.getElementById('newMasterFields');
    const toggleBtn = document.getElementById('toggleNewMaster');
    const attendanceDisplay = document.getElementById('attendanceDisplay');
    const leavingDisplay = document.getElementById('leavingDisplay');
    const optionCards = document.querySelectorAll('.master-option-card');
    const masterScrollArea = document.getElementById('masterScrollArea');
    const masterSelectHelpText = document.getElementById('masterSelectHelpText');

    // 各行の「+ シフトを追加」ボタン
    document.querySelectorAll('.open-add-modal-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            openAddModal(this.getAttribute('data-date'));
        });
    });

    // 削除確認
    document.querySelectorAll('.delete-shift-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            const confirmDate = this.getAttribute('data-confirm-date');
            if (!confirm(confirmDate + 'のシフトを削除しますか？')) {
                e.preventDefault();
            }
        });
    });

    // マスタ削除
    document.querySelectorAll('.delete-master-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const masterId = this.getAttribute('data-master-id');
            const masterName = this.getAttribute('data-master-name');

            if (confirm(masterName + 'のマスタを削除しますか？')) {
                const form = document.getElementById('masterDeleteForm');
                document.getElementById('deleteMasterId').value = masterId;
                form.submit();
            }
        });
    });

    // マスタカード選択
    optionCards.forEach(card => {
        card.addEventListener('click', function () {
            newMasterFields.classList.add('hidden');
            toggleBtn.textContent = '＋ 新規追加';
            masterScrollArea.classList.remove('hidden');
            masterSelectHelpText.classList.remove('invisible');

            optionCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');

            masterSelect.value = this.dataset.masterId;
            updateDisplayTimes(this.dataset.attendance, this.dataset.leaving);
        });
    });

    // 新規マスタ追加トグル
    toggleBtn.addEventListener('click', function () {
        newMasterFields.classList.toggle('hidden');

        if (!newMasterFields.classList.contains('hidden')) {
            toggleBtn.textContent = '✕ キャンセル';
            masterScrollArea.classList.add('hidden');
            masterSelectHelpText.classList.add('invisible');
            masterSelect.value = '';
            optionCards.forEach(c => c.classList.remove('selected'));
            updateDisplayTimes('', '');
        } else {
            toggleBtn.textContent = '＋ 新規追加';
            masterScrollArea.classList.remove('hidden');
            masterSelectHelpText.classList.remove('invisible');
        }
    });

    // 既存選択の復元
    if (masterSelect.value) {
        const selectedCard = document.querySelector(
            ".master-option-card[data-master-id='" + masterSelect.value + "']"
        );
        if (selectedCard) {
            selectedCard.classList.add('selected');
            updateDisplayTimes(selectedCard.dataset.attendance, selectedCard.dataset.leaving);
        }
    }

    // バリデーションエラー時のモーダル自動オープン
    const errorEl = document.getElementById('error-data');
    if (errorEl) {
        const hasErrors = errorEl.getAttribute('data-has-errors') === 'true';
        const hasFieldsError = errorEl.getAttribute('data-has-fields-error') === 'true';

        if (hasErrors) {
            openAddModal('');
            if (hasFieldsError) {
                newMasterFields.classList.remove('hidden');
                toggleBtn.textContent = '✕ キャンセル';
                masterScrollArea.classList.add('hidden');
                masterSelectHelpText.classList.add('invisible');
            }
        }
    }

    function updateDisplayTimes(attendance, leaving) {
        attendanceDisplay.value = attendance
            ? (attendance.match(/\d{2}:\d{2}/) ? attendance.match(/\d{2}:\d{2}/)[0] : '')
            : '';
        leavingDisplay.value = leaving
            ? (leaving.match(/\d{2}:\d{2}/) ? leaving.match(/\d{2}:\d{2}/)[0] : '')
            : '';
    }

    window.openAddModal = function (date) {
        if (date) modalTargetDate.value = date;
        if (addModal) addModal.classList.remove('hidden');
        if (masterSelect.value) {
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