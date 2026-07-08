document.addEventListener('DOMContentLoaded', function () {
    // 💡 ご指摘の通り、最初に関数宣言形式で定義（ホイスティングを可能にし、かつ先頭に配置）
    function openAddModal(date) {
        if (date && modalTargetDate) modalTargetDate.value = date;
        // 💡 ダッシュボードのモーダルと同じ「is-open」方式で開閉（フェード＋スライド演出のため）
        if (addModal) {
            addModal.classList.remove('hidden'); // 念のため旧方式のクラスが残っていても解除
            addModal.classList.add('is-open');
        }
        if (masterSelect && masterSelect.value) {
            const selectedCard = document.querySelector(
                ".master-option-card[data-master-id='" + masterSelect.value + "']"
            );
            if (selectedCard) selectedCard.click();
        }
    }

    function closeAddModal() {
        if (addModal) addModal.classList.remove('is-open');
    }

    // HTMLのonclick等から呼べるようにグローバルに紐付け
    window.openAddModal = openAddModal;
    window.closeAddModal = closeAddModal;

    // --- 要素の取得 ---
    const formMode = document.getElementById('formMode');
    const addModal = document.getElementById('addModal');

    // 💡 ダッシュボードと同様、オーバーレイの背景クリックでも閉じられるようにする
    if (addModal) {
        addModal.addEventListener('click', (e) => {
            if (e.target === addModal) closeAddModal();
        });
    }
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
    const selectWeekdaysBtn = document.getElementById('selectWeekdaysBtn');
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

    // 💡 チェック有無で「土日を除く全選択」⇔「◯件まとめて追加」をヌルっと切り替える
    function toggleBulkButton() {
        const checkedBoxes = document.querySelectorAll('.shift-bulk-checkbox:checked');
        const count = checkedBoxes.length;

        if (selectedCountSpan) selectedCountSpan.textContent = count;

        if (count > 0) {
            if (selectWeekdaysBtn) selectWeekdaysBtn.classList.add('btn-slot-hidden');
            if (openBulkModalBtn) openBulkModalBtn.classList.remove('btn-slot-hidden');
        } else {
            if (selectWeekdaysBtn) selectWeekdaysBtn.classList.remove('btn-slot-hidden');
            if (openBulkModalBtn) openBulkModalBtn.classList.add('btn-slot-hidden');
        }
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', toggleBulkButton);
    });

    // 土日祝を除くすべてにチェックをつける
    if (selectWeekdaysBtn) {
        selectWeekdaysBtn.addEventListener('click', function () {
            checkboxes.forEach(cb => {
                const dow = parseInt(cb.getAttribute('data-day-of-week'), 10);
                const isHoliday = cb.getAttribute('data-is-holiday') === '1';
                // 0:日曜, 6:土曜, 祝日 は対象外
                cb.checked = (dow !== 0 && dow !== 6 && !isHoliday);
            });
            toggleBulkButton();
        });
    }

    // 初期表示時の状態を合わせておく
    toggleBulkButton();

    // 💡 削除フォームの確認ダイアログ(Ajax追加後に生成されたフォームにも再利用できるよう関数化)
    function bindDeleteForm(form) {
        if (!form) return;
        form.addEventListener('submit', function (e) {
            const confirmDate = this.getAttribute('data-confirm-date');
            if (!confirm(confirmDate + 'のシフトを削除しますか？')) {
                e.preventDefault();
            }
        });
    }

    // 💡 ワンクリック追加(前回マスタ使用分)はAjaxで送信し、画面リロード・スクロール位置リセットを防ぐ
    const csrfTokenInput = document.querySelector('input[name="_token"]');
    const csrfToken = csrfTokenInput ? csrfTokenInput.value : '';
    const shiftDeleteUrl = (function () {
        const el = document.getElementById('error-data');
        return el ? el.getAttribute('data-shift-delete-url') : '';
    })();
    const shiftUpdateTimeUrl = (function () {
        const el = document.getElementById('error-data');
        return el ? el.getAttribute('data-shift-update-time-url') : '';
    })();

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function updateRowAfterQuickAdd(shift) {
        const row = document.getElementById('shift-row-' + shift.date);
        if (!row) return;

        const checkboxCell = row.querySelector('.cell-checkbox');
        if (checkboxCell) checkboxCell.innerHTML = '<span class="text-xs text-gray-300 select-none">-</span>';

        const attendanceCell = row.querySelector('.cell-attendance');
        if (attendanceCell) {
            attendanceCell.innerHTML =
                '<span class="attendance-text">' + escapeHtml(shift.attendance) + '</span>' +
                '<input type="time" class="attendance-input hidden w-full border rounded-lg p-1 text-sm text-center" value="' + escapeHtml(shift.attendance) + '">';
        }

        const leavingCell = row.querySelector('.cell-leaving');
        if (leavingCell) {
            leavingCell.innerHTML =
                '<span class="leaving-text">' + escapeHtml(shift.leaving) + '</span>' +
                '<input type="time" class="leaving-input hidden w-full border rounded-lg p-1 text-sm text-center" value="' + escapeHtml(shift.leaving) + '">';
        }

        const placeCell = row.querySelector('.cell-place');
        if (placeCell) {
            placeCell.innerHTML =
                '<span class="inline-flex items-center justify-center gap-1 w-full max-w-full truncate">' +
                '📍<span class="truncate font-medium text-gray-700">' + escapeHtml(shift.master_name) + '</span></span>';
        }

        const editCell = row.querySelector('.cell-edit');
        if (editCell) {
            editCell.innerHTML =
                '<button type="button" class="btn-edit edit-shift-btn inline-block text-center" data-shift-id="' + shift.shift_id + '" data-editing="0">修正</button>';
            bindEditButton(editCell.querySelector('.edit-shift-btn'));
        }

        const deleteCell = row.querySelector('.cell-delete');
        if (deleteCell && shiftDeleteUrl) {
            const dateLabel = row.querySelector('td:nth-child(2)');
            const confirmDate = dateLabel ? dateLabel.textContent.trim() : '';

            deleteCell.innerHTML =
                '<form action="' + shiftDeleteUrl + '" method="POST" data-confirm-date="' + escapeHtml(confirmDate) + '" class="delete-shift-form inline-block m-0">' +
                '<input type="hidden" name="_token" value="' + escapeHtml(csrfToken) + '">' +
                '<input type="hidden" name="_method" value="DELETE">' +
                '<input type="hidden" name="shift_id" value="' + shift.shift_id + '">' +
                '<button type="submit" class="btn-delete">削除</button>' +
                '</form>';

            bindDeleteForm(deleteCell.querySelector('.delete-shift-form'));
        }

        // チェックボックスが消えたので選択件数を再計算
        toggleBulkButton();
    }

    document.querySelectorAll('.quick-add-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const btn = form.querySelector('button[type="submit"]');
            const originalLabel = btn ? btn.textContent : '';
            if (btn) {
                btn.disabled = true;
                btn.textContent = '追加中...';
            }

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: new FormData(form),
            })
                .then(res => res.json().then(data => ({ ok: res.ok, data })))
                .then(({ ok, data }) => {
                    if (!ok || !data || !data.success) {
                        alert((data && data.message) || 'シフトの追加に失敗しました。');
                        if (btn) {
                            btn.disabled = false;
                            btn.textContent = originalLabel;
                        }
                        return;
                    }
                    (data.shifts || []).forEach(updateRowAfterQuickAdd);
                })
                .catch(() => {
                    alert('通信エラーが発生しました。お手数ですが再度お試しください。');
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = originalLabel;
                    }
                });
        });
    });

    document.querySelectorAll('.delete-shift-form').forEach(bindDeleteForm);

    // 💡 勤務地セレクト変更時に、修正後の出勤・退勤時刻をそのマスタの標準時刻へ自動入力
    document.querySelectorAll('.place-select').forEach(select => {
        select.addEventListener('change', function () {
            const row = this.closest('tr');
            if (!row) return;
            const option = this.selectedOptions[0];
            if (!option) return;

            const attendanceInput = row.querySelector('.attendance-input');
            const leavingInput = row.querySelector('.leaving-input');
            if (attendanceInput) attendanceInput.value = option.getAttribute('data-attendance') || '';
            if (leavingInput) leavingInput.value = option.getAttribute('data-leaving') || '';
        });
    });

    // 💡 「修正」→ テキストがinputに変わり「保存」表示に。「保存」を押すとAjaxでその場保存し「修正」に戻す
    function bindEditButton(btn) {
        if (!btn) return;
        btn.addEventListener('click', function () {
            const row = this.closest('tr');
            if (!row) return;

            const attendanceText = row.querySelector('.attendance-text');
            const attendanceInput = row.querySelector('.attendance-input');
            const leavingText = row.querySelector('.leaving-text');
            const leavingInput = row.querySelector('.leaving-input');
            const placeText = row.querySelector('.place-text');
            const placeSelect = row.querySelector('.place-select');
            if (!attendanceText || !attendanceInput || !leavingText || !leavingInput) return;

            const isEditing = this.getAttribute('data-editing') === '1';

            if (!isEditing) {
                // 編集モードへ切り替え
                attendanceText.classList.add('hidden');
                attendanceInput.classList.remove('hidden');
                leavingText.classList.add('hidden');
                leavingInput.classList.remove('hidden');
                if (placeText && placeSelect) {
                    placeText.classList.add('hidden');
                    placeSelect.classList.remove('hidden');
                }

                this.textContent = '保存';
                this.setAttribute('data-editing', '1');
                return;
            }

            // 保存処理
            if (!shiftUpdateTimeUrl) return;

            const shiftId = this.getAttribute('data-shift-id');
            const originalLabel = this.textContent;
            this.disabled = true;
            this.textContent = '保存中...';

            const payload = {
                _token: csrfToken,
                shift_id: shiftId,
                attendance_edit: attendanceInput.value,
                leaving_edit: leavingInput.value,
            };
            if (placeSelect) payload.master_id = placeSelect.value;

            fetch(shiftUpdateTimeUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: new URLSearchParams(payload),
            })
                .then(res => res.json().then(data => ({ ok: res.ok, data })))
                .then(({ ok, data }) => {
                    if (!ok || !data || !data.success) {
                        alert((data && data.message) || '保存に失敗しました。');
                        this.disabled = false;
                        this.textContent = originalLabel;
                        return;
                    }

                    attendanceText.textContent = data.attendance;
                    leavingText.textContent = data.leaving;

                    attendanceText.classList.remove('hidden');
                    attendanceInput.classList.add('hidden');
                    leavingText.classList.remove('hidden');
                    leavingInput.classList.add('hidden');

                    if (placeText && placeSelect && data.master_name) {
                        placeText.innerHTML = '📍<span class="truncate font-medium text-gray-700">' + escapeHtml(data.master_name) + '</span>';
                        placeText.classList.remove('hidden');
                        placeSelect.classList.add('hidden');
                    }

                    this.disabled = false;
                    this.textContent = '修正';
                    this.setAttribute('data-editing', '0');
                })
                .catch(() => {
                    alert('通信エラーが発生しました。お手数ですが再度お試しください。');
                    this.disabled = false;
                    this.textContent = originalLabel;
                });
        });
    }

    document.querySelectorAll('.edit-shift-btn').forEach(bindEditButton);

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