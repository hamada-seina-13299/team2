// ==========================================================================
// ダッシュボード専用背景：機内窓からの一人称視点
// ・時刻連動の空（朝焼け/昼/夕焼け/夜）
// ・地上(state-ground) ⇔ 離陸(state-takeoff) ⇔ 巡航(state-flying) ⇔ 着陸(state-landing) ⇔ 地上
// ==========================================================================

document.addEventListener('DOMContentLoaded', () => {
    const sky = document.getElementById('dash-sky-bg');
    if (!sky) return; // このページで背景CSSが読み込まれていない場合は何もしない

    const inner = sky.querySelector('.dash-sky-inner');

    // ---------------------------------------------------------------
    // 時刻連動の空：現在時刻から朝焼け/昼/夕焼け/夜のクラスを付け替える
    // ---------------------------------------------------------------
    function applySkyTimeClass() {
        const hour = new Date().getHours();
        let timeClass = 'sky-day';

        if (hour >= 5 && hour < 8) {
            timeClass = 'sky-dawn';
        } else if (hour >= 8 && hour < 16) {
            timeClass = 'sky-day';
        } else if (hour >= 16 && hour < 19) {
            timeClass = 'sky-dusk';
        } else {
            timeClass = 'sky-night';
        }

        sky.classList.remove('sky-dawn', 'sky-day', 'sky-dusk', 'sky-night');
        sky.classList.add(timeClass);
    }

    applySkyTimeClass();
    // 1分ごとに再判定し、開きっぱなしのダッシュボードでも時間帯の切り替わりに追従させる
    setInterval(applySkyTimeClass, 60 * 1000);

    // 雲・星を初期生成（1回だけ、.dash-sky-inner の中に追加して一緒に傾くようにする）
    if (inner && !inner.querySelector('.dash-cloud')) {
        ['dash-cloud-1', 'dash-cloud-2'].forEach((cloudClass) => {
            const cloud = document.createElement('div');
            cloud.className = `dash-cloud ${cloudClass}`;
            inner.appendChild(cloud);
        });
    }
    if (inner && !inner.querySelector('.dash-star')) {
        const STAR_COUNT = 40;
        for (let i = 0; i < STAR_COUNT; i++) {
            const star = document.createElement('div');
            star.className = 'dash-star';
            star.style.top = `${Math.random() * 65}%`;
            star.style.left = `${Math.random() * 100}%`;
            star.style.animationDelay = `${Math.random() * 3}s`;
            inner.appendChild(star);
        }
    }

    // ---------------------------------------------------------------
    // 状態管理：地上 ⇔ 巡航（出勤中かどうか）
    // ---------------------------------------------------------------
    // 現在の「落ち着いた」状態（アニメーション中の一時的な状態ではない）に切り替える
    function setRestingState(state) {
        sky.classList.remove('state-ground', 'state-flying', 'state-takeoff', 'state-landing');
        sky.classList.add(state);
    }

    // ページ読み込み直後点の状態（Blade側で出勤中かどうかに応じて付与済み）
    const initialState = sky.classList.contains('state-flying') ? 'state-flying' : 'state-ground';

    // 出勤/退勤の直後だけ、対応するトランジションを1回再生するためのトリガー
    const trigger = document.getElementById('dash-anim-trigger');
    const animType = trigger ? trigger.getAttribute('data-anim') : null;

    if (animType === 'takeoff' && inner) {
        // 地上の見た目から開始し、次のフレームで離陸トランジションへ切り替える
        setRestingState('state-ground');
        requestAnimationFrame(() => {
            sky.classList.remove('state-ground');
            sky.classList.add('state-takeoff');
        });
        inner.addEventListener('animationend', () => setRestingState('state-flying'), { once: true });
    } else if (animType === 'landing' && inner) {
        // 巡航中の見た目から開始し、次のフレームで着陸トランジションへ切り替える
        setRestingState('state-flying');
        requestAnimationFrame(() => {
            sky.classList.remove('state-flying');
            sky.classList.add('state-landing');
        });
        inner.addEventListener('animationend', () => setRestingState('state-ground'), { once: true });
    } else {
        // 通常のページ読み込み（リロード等）：アニメーション無しでそのままの状態を維持
        setRestingState(initialState);
    }
});