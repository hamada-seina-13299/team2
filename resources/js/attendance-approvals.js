document.addEventListener('DOMContentLoaded', function () {
    // ── ページ全体のスクロールを防ぐため、実際に残っている縦幅を動的に計測してセットする ──
    // レイアウト側のヘッダー等の高さは環境によって変わるため、100vh固定ではなく
    // 「画面の高さ − このブロックの開始位置」を実測して正確にフィットさせる。
    const appEl = document.querySelector('.aa-app');
    function fitAppHeight() {
        if (!appEl) return;
        appEl.style.height = 'auto';
        const top = appEl.getBoundingClientRect().top;
        const available = Math.max(window.innerHeight - top, 320);
        appEl.style.height = available + 'px';
        appEl.style.maxHeight = available + 'px';
    }
    fitAppHeight();
    window.addEventListener('resize', fitAppHeight);

    const stack = document.getElementById('aaStack');
    const stage = document.getElementById('aaStage');
    if (!stack || !stage) return; // 承認待ちが0件でスタック自体が描画されていない場合

    const emptyEl = document.getElementById('aaEmpty');
    const countEl = document.getElementById('aaCount');
    const historyEl = document.getElementById('aaHistory');
    const zoneReject = document.getElementById('aaZoneReject');
    const zoneApprove = document.getElementById('aaZoneApprove');
    const toastEl = document.getElementById('aaToast');
    const toastMsgEl = document.getElementById('aaToastMsg');
    const toastUndoBtn = document.getElementById('aaToastUndo');

    // 添付ファイル用モーダル
    const attachmentModal = document.getElementById('aaAttachmentModal');
    const attachmentModalBody = document.getElementById('aaModalBody');
    const attachmentModalClose = document.getElementById('aaModalClose');
    const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    const configEl = document.getElementById('aa-config');
    const csrfToken = configEl ? configEl.getAttribute('data-csrf') : '';

    const THRESHOLD = 110;
    let cards = Array.from(stack.querySelectorAll('.aa-card'));
    let drag = { active: false, x: 0, startX: 0 };
    let busy = false; // 同一カードへの多重操作を防ぐための短時間ロック
    let toastTimer = null;

    function isModalOpen() {
        return !!(attachmentModal && attachmentModal.classList.contains('is-open'));
    }

    function openAttachmentModal(url, name) {
        if (!attachmentModal || !attachmentModalBody) return;
        const ext = (name || '').split('.').pop().toLowerCase();
        attachmentModalBody.innerHTML = '';

        if (IMAGE_EXTENSIONS.indexOf(ext) !== -1) {
            const img = document.createElement('img');
            img.src = url;
            img.alt = name || '添付ファイル';
            img.className = 'aa-modal-image';
            attachmentModalBody.appendChild(img);
        } else if (ext === 'pdf') {
            const iframe = document.createElement('iframe');
            iframe.src = url;
            iframe.className = 'aa-modal-iframe';
            attachmentModalBody.appendChild(iframe);
        } else {
            const p = document.createElement('p');
            p.className = 'aa-modal-filename';
            p.textContent = name || '添付ファイル';
            attachmentModalBody.appendChild(p);
        }

        const link = document.createElement('a');
        link.href = url;
        link.target = '_blank';
        link.rel = 'noopener';
        link.className = 'aa-modal-download';
        link.textContent = '⬇ ダウンロード / 別タブで開く';
        attachmentModalBody.appendChild(link);

        attachmentModal.classList.add('is-open');
    }

    function closeAttachmentModal() {
        if (!attachmentModal) return;
        attachmentModal.classList.remove('is-open');
        if (attachmentModalBody) attachmentModalBody.innerHTML = '';
    }

    if (attachmentModalClose) {
        attachmentModalClose.addEventListener('click', closeAttachmentModal);
    }
    if (attachmentModal) {
        attachmentModal.addEventListener('click', function (e) {
            if (e.target === attachmentModal) closeAttachmentModal();
        });
    }

    function layoutStack() {
        cards.forEach((card, i) => {
            card.classList.remove('is-top', 'is-stacked', 'is-flying', 'is-dragging');
            card.style.transform = '';
            card.style.opacity = '';
            card.style.zIndex = '';
            card.style.pointerEvents = ''; // 前回スタック時に無効化されたpointer-eventsをリセット（2枚目以降がドラッグ不能になるバグの修正）
            const overlay = card.querySelector('.aa-card-overlay');
            const stamp = card.querySelector('.aa-card-stamp');
            if (overlay) overlay.style.opacity = '0';
            if (stamp) { stamp.style.opacity = '0'; stamp.classList.remove('approve', 'reject'); }

            if (i === 0) {
                card.classList.add('is-top');
                card.style.zIndex = '50';
                card.style.display = '';
            } else if (i === 1 || i === 2) {
                card.classList.add('is-stacked');
                const depth = i; // 1 or 2
                card.style.transform = `scale(${1 - depth * 0.035}) translateY(${depth * 12}px)`;
                card.style.opacity = String(1 - depth * 0.28);
                card.style.zIndex = String(50 - depth);
                card.style.display = '';
                card.style.pointerEvents = 'none';
            } else {
                card.style.display = 'none';
            }
        });

        if (countEl) countEl.textContent = String(cards.length);

        if (cards.length === 0) {
            stack.style.display = 'none';
            zoneReject && (zoneReject.style.display = 'none');
            zoneApprove && (zoneApprove.style.display = 'none');
            emptyEl && (emptyEl.style.display = 'flex');
        }
    }

    function topCard() {
        return cards[0] || null;
    }

    function applyDragTransform(card, x) {
        const rot = x / 22;
        card.style.transform = `translateX(${x}px) rotate(${rot}deg)`;

        const overlay = card.querySelector('.aa-card-overlay');
        const stamp = card.querySelector('.aa-card-stamp');
        const isApprove = x > 0;
        const opa = Math.min(Math.abs(x) / 140, 0.8);

        if (overlay) {
            overlay.className = 'aa-card-overlay ' + (isApprove ? 'approve' : 'reject');
            overlay.style.opacity = String(opa);
        }
        if (stamp) {
            if (Math.abs(x) > 35) {
                stamp.className = 'aa-card-stamp ' + (isApprove ? 'approve' : 'reject');
                stamp.textContent = isApprove ? '承認' : '却下';
                stamp.style.opacity = String(Math.min(opa * 1.5, 1));
            } else {
                stamp.style.opacity = '0';
            }
        }

        if (zoneReject) zoneReject.style.opacity = x < -20 ? String(Math.min(Math.abs(x) / 90, 1)) : '0.18';
        if (zoneApprove) zoneApprove.style.opacity = x > 20 ? String(Math.min(x / 90, 1)) : '0.18';
    }

    function resetZones() {
        if (zoneReject) zoneReject.style.opacity = '0.18';
        if (zoneApprove) zoneApprove.style.opacity = '0.18';
    }

    function showToast(message, undoUrl) {
        if (!toastEl) return;
        clearTimeout(toastTimer);
        toastMsgEl.textContent = message;
        toastEl.style.display = 'flex';

        if (undoUrl && toastUndoBtn) {
            toastUndoBtn.style.display = '';
            toastUndoBtn.onclick = function () {
                clearTimeout(toastTimer);
                toastEl.style.display = 'none';
                sendAction(undoUrl).then(function (res) {
                    if (res && res.success) {
                        // 状態がサーバー側で戻っただけで、DOM上の順序を正確に復元するのは複雑なため
                        // シンプルにリロードして最新の承認待ち一覧を出し直す
                        location.reload();
                    } else {
                        alert((res && res.message) || '取り消しに失敗しました。');
                    }
                });
            };
        } else if (toastUndoBtn) {
            toastUndoBtn.style.display = 'none';
        }

        toastTimer = setTimeout(function () {
            toastEl.style.display = 'none';
        }, 6000);
    }

    function pushHistory(name, action) {
        if (!historyEl) return;
        const pill = document.createElement('span');
        pill.className = 'aa-history-pill ' + (action === 'approve' ? 'approved' : 'rejected');
        pill.textContent = (action === 'approve' ? '✓ ' : '✕ ') + name;
        historyEl.appendChild(pill);
        // 直近4件だけ表示
        while (historyEl.children.length > 4) {
            historyEl.removeChild(historyEl.firstChild);
        }
    }

    function sendAction(url) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (r) { return Object.assign({ success: r.ok }, r.data); })
            .catch(function () { return { success: false, message: '通信エラーが発生しました。' }; });
    }

    function decide(action) {
        const card = topCard();
        if (!card || busy) return;
        busy = true;

        const url = card.getAttribute(action === 'approve' ? 'data-approve-url' : 'data-reject-url');
        const undoUrl = card.getAttribute('data-undo-url');
        const name = card.getAttribute('data-user-name') || '';

        // ── 楽観的UI ──
        // 見た目を即座に画面外へ飛ばしつつ、通信結果を待たずに配列とレイアウトを即座に更新する。
        // これにより次のカードがラグなく即表示される（体感速度優先）。
        card.classList.remove('is-dragging');
        card.classList.add('is-flying');
        card.style.zIndex = '60'; // 飛んでいくカードが新しいトップカードより手前に見えるように
        const flyX = action === 'approve' ? 'translateX(145%) rotate(20deg)' : 'translateX(-145%) rotate(-20deg)';
        card.style.transform = flyX;
        card.style.opacity = '0.4';
        drag = { active: false, x: 0, startX: 0 };
        resetZones();

        // 配列・DOM上のスタック順序を即座に更新（通信を待たない）
        cards.shift();
        layoutStack();
        pushHistory(name, action);
        busy = false; // 連続スワイプ・連続クリックをすぐ受け付ける

        // アニメーションが終わったら実際にDOMから取り除く
        setTimeout(function () {
            if (card.parentNode) card.remove();
        }, 420);

        // 裏側で非同期にPOST。結果は完了後にトーストで通知するだけで、画面遷移は待たせない。
        sendAction(url).then(function (res) {
            if (res && res.success) {
                showToast(res.message || '処理しました。', undoUrl);
            } else {
                showToast((res && res.message) || '通信に失敗しました。一覧を再読み込みしてご確認ください。', null);
            }
        });
    }

    // ── キーボード操作 ──
    document.addEventListener('keydown', function (e) {
        if (isModalOpen()) {
            if (e.key === 'Escape') closeAttachmentModal();
            return;
        }
        if (!topCard() || busy) return;
        if (e.key === 'ArrowRight') decide('approve');
        if (e.key === 'ArrowLeft') decide('reject');
    });

    // ── ドラッグ操作（トップカードのみ） ──
    stack.addEventListener('pointerdown', function (e) {
        const card = e.target.closest('.aa-card.is-top');
        if (!card || busy) return;
        if (e.target.closest('.aa-btn') || e.target.closest('.aa-attachment')) return; // ボタン類は別ハンドラに任せる
        card.setPointerCapture(e.pointerId);
        card.classList.add('is-dragging');
        drag = { active: true, x: 0, startX: e.clientX, card: card };
    });

    stack.addEventListener('pointermove', function (e) {
        if (!drag.active || !drag.card) return;
        drag.x = e.clientX - drag.startX;
        applyDragTransform(drag.card, drag.x);
    });

    function endDrag() {
        if (!drag.active || !drag.card) return;
        const card = drag.card;
        if (drag.x > THRESHOLD) {
            decide('approve');
        } else if (drag.x < -THRESHOLD) {
            decide('reject');
        } else {
            card.classList.remove('is-dragging');
            card.style.transform = '';
            const overlay = card.querySelector('.aa-card-overlay');
            const stamp = card.querySelector('.aa-card-stamp');
            if (overlay) overlay.style.opacity = '0';
            if (stamp) stamp.style.opacity = '0';
            resetZones();
        }
        drag = { active: false, x: 0, startX: 0 };
    }

    stack.addEventListener('pointerup', endDrag);
    stack.addEventListener('pointercancel', endDrag);

    // ── ボタン操作（✕ 却下 / ✓ 承認 / 📎 添付ファイル） ──
    stack.addEventListener('click', function (e) {
        const attachBtn = e.target.closest('.aa-attachment');
        if (attachBtn) {
            e.stopPropagation();
            openAttachmentModal(
                attachBtn.getAttribute('data-attachment-url'),
                attachBtn.getAttribute('data-attachment-name')
            );
            return;
        }

        const btn = e.target.closest('.aa-btn');
        if (!btn) return;
        const card = btn.closest('.aa-card');
        if (!card || !card.classList.contains('is-top') || busy) return;
        decide(btn.getAttribute('data-action'));
    });

    layoutStack();
});