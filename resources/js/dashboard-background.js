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
    // ただし、ヘッダーの「オプション」でユーザーが固定の時間帯を選んでいる場合はそれを優先する
    // ---------------------------------------------------------------
    const SKY_OVERRIDE_KEY = 'dashSkyOverride';
    const VALID_SKY_CLASSES = ['sky-dawn', 'sky-day', 'sky-dusk', 'sky-night', 'sky-rainy', 'sky-storm'];
    const ALL_SKY_CLASSES = VALID_SKY_CLASSES; // クラスの付け外し用（意味は同じだが名前で使い分け）

    // 「自動」モード時にのみ使う、現在の実際の天気（'rain' | 'storm' | null）
    // 取得に失敗した場合は null のままにし、通常通り時刻ベースの空にフォールバックする
    let currentWeatherCondition = null;

    function applySkyTimeClass() {
        const override = localStorage.getItem(SKY_OVERRIDE_KEY);
        let timeClass;

        if (override && VALID_SKY_CLASSES.includes(override)) {
            // ユーザーが固定した空（モーニング/アフタヌーン/イブニング/ナイト/レイン/ストームフライト）
            timeClass = override;
        } else if (currentWeatherCondition === 'storm') {
            // 「自動」時、実際の天気が荒れている場合は時刻より天気を優先する
            timeClass = 'sky-storm';
        } else if (currentWeatherCondition === 'rain') {
            timeClass = 'sky-rainy';
        } else {
            // 「自動」：現在時刻から判定
            const hour = new Date().getHours();
            if (hour >= 5 && hour < 8) {
                timeClass = 'sky-dawn';
            } else if (hour >= 8 && hour < 16) {
                timeClass = 'sky-day';
            } else if (hour >= 16 && hour < 19) {
                timeClass = 'sky-dusk';
            } else {
                timeClass = 'sky-night';
            }
        }

        sky.classList.remove(...ALL_SKY_CLASSES);
        sky.classList.add(timeClass);

        // レイン/ストームフライトは、固定選択でも実際の現在時刻に応じて日中/夜間の明るさを自動で変える
        // （手動でレイン/ストームを選んでいても、時間帯モーダルを別途追加する必要はない）
        const hourNow = new Date().getHours();
        const isDaytime = hourNow >= 5 && hourNow < 19;
        sky.classList.toggle('is-daytime', isDaytime);
        sky.classList.toggle('is-nighttime', !isDaytime);
    }

    applySkyTimeClass();
    // 1分ごとに再判定し、開きっぱなしのダッシュボードでも時間帯の切り替わりに追従させる
    setInterval(applySkyTimeClass, 60 * 1000);

    // ヘッダーの「オプション」モーダルで設定を保存した瞬間、ページ遷移無しで即座に反映する
    window.addEventListener('dash-sky-preference-changed', applySkyTimeClass);

    // ---------------------------------------------------------------
    // 🌦️ 実際の天気を取得する（「自動」モード時に、雨/嵐なら空に反映するため）
    // Open-Meteo（APIキー不要・無料）を使用。位置情報が使えない/拒否された場合は
    // 東京の座標にフォールバックし、取得自体に失敗した場合は天気判定を諦めて
    // 通常通り時刻ベースの空にフォールバックする（＝失敗してもアプリは壊れない）。
    // ---------------------------------------------------------------
    const TOKYO_FALLBACK = { lat: 35.6895, lon: 139.6917 };

    function getCoordinates() {
        return new Promise((resolve) => {
            if (!('geolocation' in navigator)) {
                resolve(TOKYO_FALLBACK);
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => resolve({ lat: pos.coords.latitude, lon: pos.coords.longitude }),
                () => resolve(TOKYO_FALLBACK), // 拒否・失敗時は東京にフォールバック
                { timeout: 5000 }
            );
        });
    }

    // WMO Weather interpretation codes → 'rain' | 'storm' | null(晴れ/曇り等)
    function mapWeatherCodeToCondition(code) {
        if ([95, 96, 99].includes(code)) return 'storm';
        if ([51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82].includes(code)) return 'rain';
        return null;
    }

    async function fetchWeatherCondition() {
        try {
            const { lat, lon } = await getCoordinates();
            const url = `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current=weather_code`;
            const res = await fetch(url);
            if (!res.ok) throw new Error('weather fetch failed');
            const data = await res.json();
            const code = data && data.current ? data.current.weather_code : null;
            currentWeatherCondition = mapWeatherCodeToCondition(code);
        } catch (e) {
            // 取得失敗時は静かに諦める（コンソールにだけ残す。ユーザー体験は時刻ベースの空を維持）
            console.warn('[dashboard-background] 天気の取得に失敗しました。時刻ベースの空を使用します。', e);
            currentWeatherCondition = null;
        }
        applySkyTimeClass(); // 取得結果を反映（「自動」以外が選ばれていれば何も変わらない）
    }

    fetchWeatherCondition();
    // 天気は1分ごとに変わるものではないので、15分おきに再取得すれば十分
    setInterval(fetchWeatherCondition, 15 * 60 * 1000);

    // 雲・星を初期生成（1回だけ、.dash-sky-inner の中に追加して一緒に傾くようにする）
    if (inner && !inner.querySelector('.dash-cloud')) {
        ['dash-cloud-1', 'dash-cloud-2'].forEach((cloudClass) => {
            const cloud = document.createElement('div');
            cloud.className = `dash-cloud ${cloudClass}`;
            inner.appendChild(cloud);
        });
    }
    if (inner && !inner.querySelector('.dash-star')) {
        const STAR_COUNT = 45;
        for (let i = 0; i < STAR_COUNT; i++) {
            const star = document.createElement('div');
            const isWarm = Math.random() < 0.3; // 約3割を暖色（黄みがかった星）にする
            const size = 2 + Math.random() * 3; // 2px〜5pxでばらつかせる

            star.className = isWarm ? 'dash-star dash-star--warm' : 'dash-star';
            star.style.width = `${size}px`;
            star.style.height = `${size}px`;
            star.style.top = `${Math.random() * 65}%`;
            star.style.left = `${Math.random() * 100}%`;
            star.style.animationDelay = `${Math.random() * 3}s`;
            inner.appendChild(star);
        }
    }
    if (inner && !inner.querySelector('.dash-rain-drop')) {
        const RAIN_COUNT = 70;
        for (let i = 0; i < RAIN_COUNT; i++) {
            const drop = document.createElement('div');
            drop.className = 'dash-rain-drop';
            drop.style.left = `${Math.random() * 130 - 15}%`; // 斜めに流れても画面端が透けないよう左右に広めに配置
            drop.style.animationDuration = `${350 + Math.random() * 250}ms`;
            drop.style.animationDelay = `${Math.random() * 2}s`;
            inner.appendChild(drop);
        }
        // ストーム限定の追加レイヤー（雨量を大幅に増やす）
        const STORM_EXTRA_RAIN_COUNT = 55;
        for (let i = 0; i < STORM_EXTRA_RAIN_COUNT; i++) {
            const drop = document.createElement('div');
            drop.className = 'dash-rain-drop dash-rain-drop--extra';
            drop.style.left = `${Math.random() * 130 - 15}%`;
            drop.style.animationDuration = `${300 + Math.random() * 200}ms`;
            drop.style.animationDelay = `${Math.random() * 2}s`;
            inner.appendChild(drop);
        }
        // 雷用の全画面フラッシュレイヤー（ストームフライトの時だけCSS側でアニメーションする）
        const lightning = document.createElement('div');
        lightning.className = 'dash-lightning';
        inner.appendChild(lightning);
    }

    // 🪟 窓ガラスに当たる水滴（.dash-window-glass の上に直接重ねる。overflow:hiddenで窓の形にクリップされる）
    // ストームフライトのみで使用（レインフライトでは非表示：CSS側で制御）
    // 参考画像のような密度感を出すため、「張り付いたまま揺れる水滴」を多め、「流れ落ちる水滴」を少なめに混ぜる
    const glass = sky.querySelector('.dash-window-glass');
    if (glass && !glass.querySelector('.dash-window-droplet')) {
        const CLING_DROPLET_COUNT = 55;  // ガラスに張り付いたままの水滴（メイン）
        const SLIDE_DROPLET_COUNT = 12;  // 時々流れ落ちる水滴（アクセント）

        for (let i = 0; i < CLING_DROPLET_COUNT; i++) {
            const droplet = document.createElement('div');
            const isBig = Math.random() < 0.3;
            const size = isBig ? 10 + Math.random() * 6 : 3 + Math.random() * 5;

            droplet.className = 'dash-window-droplet dash-window-droplet--static';
            droplet.style.width = `${size}px`;
            droplet.style.height = `${size}px`;
            droplet.style.top = `${Math.random() * 90}%`;
            droplet.style.left = `${Math.random() * 100}%`;
            droplet.style.animationDuration = `${2500 + Math.random() * 2500}ms`;
            droplet.style.animationDelay = `${Math.random() * 3}s`;
            glass.appendChild(droplet);
        }

        for (let i = 0; i < SLIDE_DROPLET_COUNT; i++) {
            const droplet = document.createElement('div');
            const isBig = Math.random() < 0.4;

            droplet.className = isBig ? 'dash-window-droplet dash-window-droplet--big' : 'dash-window-droplet';
            droplet.style.top = `${Math.random() * 70}%`;
            droplet.style.left = `${Math.random() * 100}%`;
            droplet.style.animationDuration = `${1800 + Math.random() * 1400}ms`;
            droplet.style.animationDelay = `${Math.random() * 2.5}s`;
            glass.appendChild(droplet);
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