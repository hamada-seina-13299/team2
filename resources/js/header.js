// ==========================================================================
// ヘッダー共通処理（❓メニュー / ユーザー名メニュー など）
// ==========================================================================
//
// 「.header-dropdown-wrapper」の中に
//   ・開閉トリガーとなる「.header-dropdown-toggle」
//   ・中身の「.header-dropdown-menu」
// を1組入れておくだけで、自動的に開閉・外側クリックで閉じる・Escで閉じる
// が有効になる汎用実装です。今後ヘッダーにメニューを追加する時も
// このクラスを使えば、JSを書き足す必要はありません。

document.addEventListener('DOMContentLoaded', () => {
    const wrappers = document.querySelectorAll('.header-dropdown-wrapper');
    if (wrappers.length === 0) return;

    const closeWrapper = (wrapper) => {
        const menu = wrapper.querySelector('.header-dropdown-menu');
        const toggle = wrapper.querySelector('.header-dropdown-toggle');
        if (menu) menu.classList.remove('is-open');
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
    };

    const closeAll = (except = null) => {
        wrappers.forEach((wrapper) => {
            if (wrapper !== except) closeWrapper(wrapper);
        });
    };

    wrappers.forEach((wrapper) => {
        const toggle = wrapper.querySelector('.header-dropdown-toggle');
        const menu = wrapper.querySelector('.header-dropdown-menu');
        if (!toggle || !menu) return;

        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const isOpen = menu.classList.contains('is-open');

            // 同時に複数のメニューが開かないよう、他のメニューは閉じておく
            closeAll(wrapper);

            if (isOpen) {
                closeWrapper(wrapper);
            } else {
                menu.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
            }
        });
    });

    // メニューの外側をクリックしたら、開いているものを全部閉じる
    document.addEventListener('click', () => closeAll());

    // Escキーでも全部閉じる
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeAll();
    });
});

// ==========================================================================
// ✈️ モーダル完全開閉制御（ボタン、×、Esc、背景クリック、スクロール対応）
// ==========================================================================
document.addEventListener('DOMContentLoaded', () => {
    const modalTriggers = document.querySelectorAll('.modal-trigger');
    const overlays = document.querySelectorAll('.modal-overlay');

    if (overlays.length === 0) return;

    // 全てのモーダルを閉じる関数
    const closeAllModals = () => {
        overlays.forEach(overlay => {
            overlay.classList.remove('is-open');
        });
    };

    // メニューから「利用規約」や「プライバシー」を押した時
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation(); // ヘッダーの外側クリック判定とぶつからないようにする

            const targetId = trigger.getAttribute('data-target');
            const targetModal = document.getElementById(targetId);
            
            if (targetModal) {
                closeAllModals(); // 他が開いていたら一回リセット
                targetModal.classList.add('is-open');
            }
        });
    });

    // モーダル内の各閉じるボタンの設定
    overlays.forEach(overlay => {
        const windowEl = overlay.querySelector('.modal-window');
        const closeBtn = overlay.querySelector('.modal-close-btn');
        const actionBtn = overlay.querySelector('.modal-action-btn');

        // × ボタンで閉じる
        if (closeBtn) {
            closeBtn.addEventListener('click', () => closeAllModals());
        }

        // 下部の閉じるボタン
        if (actionBtn) {
            actionBtn.addEventListener('click', () => closeAllModals());
        }

        // 枠外（黒い背景）クリックで閉じる
        overlay.addEventListener('click', (e) => {
            if (windowEl && !windowEl.contains(e.target)) {
                closeAllModals();
            }
        });
    });

    // Escキーで閉じる
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' || e.key === 'Esc') {
            closeAllModals();
        }
    });
});