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

// ==========================================================================
// 🌤️ ダッシュボード背景「時間帯」設定 ＋ 🌓 表示モード（ライト/ダーク/自動）
// ==========================================================================
// 選択内容は localStorage に保存し、dashboard-background.js 側がそれを読んで
// 実際の空の色（sky-dawn / sky-day / sky-dusk / sky-night）に反映します。
// 値が無い（=空文字）場合は「自動（現在時刻に合わせる）」として扱われます。
document.addEventListener('DOMContentLoaded', () => {
    const STORAGE_KEY = 'dashSkyOverride';
    const THEME_STORAGE_KEY = 'themeMode';

    const openBtn = document.getElementById('sky-option-open-btn');
    const modal = document.getElementById('sky-option-modal');
    const saveBtn = document.getElementById('sky-option-save-btn');

    if (!modal || !saveBtn) return;

    // 表示モードを実際に画面へ反映する（<html data-theme="..."> を切り替えるだけ。
    // CSS側は dark-mode.css が html[data-theme="dark"] を見て切り替わる）
    function applyThemeMode(mode) {
        let isDark;
        if (mode === 'dark') {
            isDark = true;
        } else if (mode === 'auto') {
            const hour = new Date().getHours();
            isDark = (hour < 6 || hour >= 19);
        } else {
            isDark = false;
        }
        document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
    }

    // モーダルを開いた瞬間、現在保存されている設定にラジオボタンを合わせる
    if (openBtn) {
        openBtn.addEventListener('click', () => {
            const current = localStorage.getItem(STORAGE_KEY) || '';
            modal.querySelectorAll('input[name="sky_option"]').forEach((radio) => {
                radio.checked = (radio.value === current);
            });

            const currentTheme = localStorage.getItem(THEME_STORAGE_KEY) || 'light';
            modal.querySelectorAll('input[name="theme_option"]').forEach((radio) => {
                radio.checked = (radio.value === currentTheme);
            });
        });
    }

    // 保存ボタン：選択値を保存し、開いていればダッシュボードの空／画面配色にも即座に反映する
    // ※モーダルを閉じる処理自体は、上の汎用モーダルシステム（.modal-action-btn）が担当
    saveBtn.addEventListener('click', () => {
        const selected = modal.querySelector('input[name="sky_option"]:checked');
        const value = selected ? selected.value : '';

        if (value) {
            localStorage.setItem(STORAGE_KEY, value);
        } else {
            localStorage.removeItem(STORAGE_KEY); // 「自動」が選ばれた場合は保存値を削除
        }

        const selectedTheme = modal.querySelector('input[name="theme_option"]:checked');
        const themeValue = selectedTheme ? selectedTheme.value : 'light';
        localStorage.setItem(THEME_STORAGE_KEY, themeValue);
        applyThemeMode(themeValue);

        // 同じページ内にダッシュボードの空(dashboard-background.js)がいれば、即座に切り替える
        window.dispatchEvent(new CustomEvent('dash-sky-preference-changed'));
    });

    // 表示モードが「自動」の場合、開きっぱなしのページでも19時/6時の切り替わりに追従させる
    setInterval(() => {
        const mode = localStorage.getItem(THEME_STORAGE_KEY) || 'light';
        if (mode === 'auto') applyThemeMode('auto');
    }, 60 * 1000);
});

// ==========================================================================
// 🔒 パスワード変更（ログイン中の本人による変更・Ajax送信）
// ==========================================================================
// 送信ボタンには意図的に .modal-action-btn を付けていないため、
// クリックしても汎用モーダルシステムによる自動クローズは発生しない。
// バリデーションに失敗した場合はモーダルを開いたままエラーを表示し、
// 成功した場合だけ、メッセージを少し見せてから自分でモーダルを閉じる。
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('change-password-modal');
    const form = document.getElementById('change-password-form');
    const submitBtn = document.getElementById('change-password-submit-btn');
    const errorBox = document.getElementById('change-password-error');
    const successBox = document.getElementById('change-password-success');

    if (!modal || !form || !submitBtn || !errorBox || !successBox) return;

    function resetMessages() {
        errorBox.style.display = 'none';
        errorBox.textContent = '';
        successBox.style.display = 'none';
        successBox.textContent = '';
    }

    function showError(message) {
        successBox.style.display = 'none';
        errorBox.textContent = message;
        errorBox.style.display = 'block';
    }

    // モーダルを開くたびに、前回の入力内容・メッセージをリセットする
    document.querySelectorAll('[data-target="change-password-modal"]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            form.reset();
            resetMessages();
        });
    });

    submitBtn.addEventListener('click', async () => {
        resetMessages();

        const formData = new FormData(form);
        const newPassword = formData.get('new_password');
        const confirmPassword = formData.get('new_password_confirmation');

        if (newPassword !== confirmPassword) {
            showError('確認用パスワードが一致しません。');
            return;
        }

        const tokenInput = form.querySelector('input[name="_token"]');
        const token = tokenInput ? tokenInput.value : '';

        submitBtn.disabled = true;
        submitBtn.textContent = '変更中...';

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await res.json().catch(() => ({}));

            if (res.ok && data.success) {
                successBox.textContent = data.message || 'パスワードを変更しました。';
                successBox.style.display = 'block';
                form.reset();

                // メッセージを少し見せてから自動で閉じる
                setTimeout(() => {
                    modal.classList.remove('is-open');
                    resetMessages();
                }, 1500);
            } else if (data.errors) {
                // Laravelの標準バリデーションエラー形式 { errors: { field: ["メッセージ"] } }
                const firstError = Object.values(data.errors)[0];
                showError(Array.isArray(firstError) ? firstError[0] : String(firstError));
            } else {
                showError(data.message || '変更に失敗しました。もう一度お試しください。');
            }
        } catch (err) {
            showError('通信エラーが発生しました。もう一度お試しください。');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = '変更する';
        }
    });
});

// ==========================================================================
// 🔔 お知らせ（アラート／システム通知／給与明細）
// ==========================================================================
// モーダルを開いた瞬間にAjaxで取得する（ヘッダーは全ページ共通で読み込まれるため、
// ページ読み込みのたびに毎回計算させないようにするため）。
// 一度取得できたら使い回し、モーダルを開くたびに再取得はしない。
document.addEventListener('DOMContentLoaded', () => {
    const trigger = document.querySelector('[data-target="notice-modal"]');
    const loadingEl = document.getElementById('notice-loading');
    const emptyEl = document.getElementById('notice-empty');
    const tableEl = document.getElementById('notice-table');
    const tbody = document.getElementById('notice-table-body');
    const previewModal = document.getElementById('notice-preview-modal');
    const previewImg = document.getElementById('notice-preview-img');

    if (!trigger || !tbody) return;

    const categoryBadgeClass = {
        alert: 'notice-badge-alert',
        system: 'notice-badge-system',
        payslip: 'notice-badge-payslip',
    };

    let loaded = false;

    function renderNotices(notices) {
        tbody.innerHTML = '';

        if (!notices || notices.length === 0) {
            tableEl.style.display = 'none';
            emptyEl.style.display = 'block';
            return;
        }

        notices.forEach((notice) => {
            const badgeClass = categoryBadgeClass[notice.category_key] || 'notice-badge-system';

            let actionHtml = '';
            if (notice.action === 'payslip') {
                actionHtml = `<button type="button" class="notice-action-btn" data-preview-url="${notice.preview_url}">${notice.action_label || '確認する'}</button>`;
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><span class="notice-category-badge ${badgeClass}">${notice.category}</span></td>
                <td>${notice.title}</td>
                <td class="notice-nowrap">${notice.datetime}</td>
                <td class="notice-nowrap">${actionHtml}</td>
            `;
            tbody.appendChild(tr);
        });

        tableEl.style.display = 'table';
        emptyEl.style.display = 'none';
    }

    trigger.addEventListener('click', () => {
        if (loaded) return;

        loadingEl.style.display = 'block';
        tableEl.style.display = 'none';
        emptyEl.style.display = 'none';

        fetch(trigger.getAttribute('data-notice-url'), {
            headers: { 'Accept': 'application/json' },
        })
            .then((res) => res.json())
            .then((data) => {
                loaded = true;
                loadingEl.style.display = 'none';
                renderNotices(data.notices || []);
            })
            .catch(() => {
                loadingEl.textContent = '読み込みに失敗しました。時間をおいて再度お試しください。';
            });
    });

    // 給与明細の「確認する」ボタン（動的に生成される要素なのでイベント委譲で拾う）
    tbody.addEventListener('click', (e) => {
        const btn = e.target.closest('.notice-action-btn');
        if (!btn || !previewModal || !previewImg) return;

        previewImg.src = btn.getAttribute('data-preview-url');

        // お知らせ一覧モーダルを閉じてから、プレビューモーダルを開く
        document.querySelectorAll('.modal-overlay').forEach((overlay) => overlay.classList.remove('is-open'));
        previewModal.classList.add('is-open');
    });
});