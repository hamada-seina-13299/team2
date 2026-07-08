<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>パスワード再設定 - メール送信</title>
    @vite(['resources/css/background.css', 'resources/css/passwordRequest.css'])

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/MotionPathPlugin.min.js"></script>

    <style>
        /* 初期状態：背景透明・青枠線 */
        #btnBase {
            fill: transparent;
            stroke: #3b5998;
            stroke-width: 6; /* SVGのサイズに合わせて視認しやすい太さに設定 */
            cursor: pointer;
            transition: all 0.2s;
        }
        /* 初期状態：テキストと紙飛行機は青色 */
        #txtSend, #paperPlanePath {
            fill: #3b5998;
            transition: all 0.2s;
        }
        
        /* ホバー時：通常のCSS同様に色を反転 */
        .button-container:hover #btnBase {
            fill: #3b5998;
        }
        .button-container:hover #txtSend,
        .button-container:hover #paperPlanePath {
            fill: white;
        }

        /* クリック（アニメーション開始）以降のスタイル破綻を防ぐ補正 */
        #base {
            cursor: pointer;
        }
    </style>
</head>

<body class="weather-sunny">

    <div class="stage-objects" id="airplane-stage">
        <div class="cloud cloud-back"></div>
        <div class="cloud cloud-middle"></div>
        <div class="cloud cloud-front"></div>

        <div class="airplane-container fly-left-to-right">
            <svg class="airplane" viewBox="0 0 24 24">
                <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L14 19v-5.5l8 2.5z" fill="currentColor"/>
            </svg>
        </div>

        <div class="airplane-container fly-right-to-left">
            <svg class="airplane" viewBox="0 0 24 24">
                <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L14 19v-5.5l8 2.5z" fill="currentColor"/>
            </svg>
        </div>

        <div class="airplane-container fly-bottom-left-to-top-right">
            <svg class="airplane" viewBox="0 0 24 24">
                <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L14 19v-5.5l8 2.5z" fill="currentColor"/>
            </svg>
        </div>
    </div>

    <div class="container">
        <main>
            <h1 class="skyDuty" style="color: #1a2a4a; font-size: 3rem; margin-top: 0px; margin-bottom: 15px;">SkyDuty</h1>
            <h3 style="margin-top: 0px;">パスワード再設定</h3>

            <form id="resetForm" action="{{ url('/password/passwordRequest') }}" method="POST">
                @csrf

                @if (!empty($errorList))
                    <div>
                        <ul style="color: #dc2626; list-style: none; padding-left: 0; margin-top: 15px; font-size: 1rem;">
                            @foreach ($errorList as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (!empty($successMessage))
                    <div style="color: green;">
                        <p>{{ $successMessage }}</p>
                    </div>
                @endif

                <table>
                    <tr>
                        <th><label for="email">メールアドレス</label></th>
                        <td><input type="text" id="emailInput" name="email" placeholder="example@gmail.com" value="{{ $email ?? '' }}"></td>
                    </tr>
                </table>

                <div class="button-container">
                    <svg class="animated-btn-svg" viewBox="400 440 600 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path id="paperPlaneRoute" d="M563.558,526.618 C638.854,410.19 787.84,243.065 916.53,334.949 1041.712,424.328 858.791,877.927 743.926,856.655 642.241,838.669 699.637,688.664 700,540" stroke="white" stroke-width="3" style="stroke-dashoffset: 0.001px; stroke-dasharray: 0px, 999999px;"/>
                        
                        <g id="rectSent" clip-path="url(#clipPath)">
                            <g id="rectSentItems">
                                <rect id="sentBase" x="550" y="468.5" width="300" height="110" rx="16" fill="#3b5998"/>
                                <text id="txtSent" fill="white" xml:space="preserve" style="white-space: pre" font-family="-apple-system, sans-serif" font-size="45" font-weight="bold" letter-spacing="0.025em"><tspan x="590" y="543">送信完了！</tspan></text>
                            </g>
                        </g>

                        <circle id="cBottom" cx="700" cy="540" r="97.516" fill="#3b5998" class="hidden"/>
                        <circle id="cTop" cx="700" cy="502.365" r="107.898" fill="#3b5998" class="hidden"/>
                        <circle id="cCenter" cx="700" cy="540" r="123" fill="#3b5998" class="hidden" />
                        <circle id="cEnd" cx="495" cy="540" r="98" fill="#3b5998" class="hidden"/>
                        <path id="tickMark" fill-rule="evenodd" clip-rule="evenodd" d="M597.3 489.026C595.179 487.257 592.026 487.541 590.257 489.662L550.954 536.768L534.647 522.965C532.539 521.181 529.384 521.444 527.6 523.551L519.096 533.598C517.312 535.706 517.575 538.861 519.682 540.645L538.606 556.662C538.893 557.162 539.272 557.621 539.74 558.012L549.847 566.445C551.967 568.214 555.12 567.929 556.889 565.809L608.042 504.501C609.811 502.38 609.527 499.227 607.406 497.458L597.3 489.026Z" fill="white" class="hidden"/>
                        
                        <g id="base">
                            <g filter="url(#flShadow)">
                                <rect id="btnBase" x="550" y="468.5" width="300" height="110" rx="16" />
                            </g>
                            <text id="txtSend" xml:space="preserve" style="white-space: pre" font-family="-apple-system, sans-serif" font-size="45" font-weight="bold" letter-spacing="0.025em"><tspan x="685" y="543">送信</tspan></text>
                            
                            <g id="paperPlane" style="transform-origin: 0px 0px 0px;" data-svg-origin="563.55859375 527.734375" transform="matrix(0.8396,0.5432,-0.5432,0.8396,377.09924,-222.6639)">
                                <g transform="scale(0.45) translate(820, 540)">
                                    <path id="paperPlanePath" d="M560.611 481.384C562.003 479.263 565.113 479.263 566.505 481.384L607.063 543.177C615.657 556.272 607.507 573.375 592.766 575.676L566.422 557.462V510.018C566.422 508.436 565.14 507.154 563.558 507.154C561.976 507.154 560.693 508.436 560.693 510.018V557.462L534.349 575.676C519.609 573.375 511.459 556.272 520.053 543.177L560.611 481.384Z"/>
                                </g>
                            </g>
                        </g>
                        
                        <defs>
                            <clipPath id="clipPath">
                                <rect id="mask1" x="700" y="450" width="520" height="180" fill="white"/>
                            </clipPath>
                            <filter id="flShadow" x="0" y="0" width="1400" height="1080" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                                <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/>
                                <feOffset dx="4" dy="4"/>
                                <feGaussianBlur stdDeviation="5"/>
                                <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.15 0"/>
                                <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow"/>
                                <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow" result="shape"/>
                            </filter>
                        </defs>
                    </svg>
                </div>

                <button type="submit" id="realSubmitBtn" class="hidden-submit"></button>
            </form>

            <br>
            <a href="{{ route('login') }}" style="color: #3b5998; text-decoration: none; font-size: 0.9rem;">ログイン画面に戻る</a>

        </main>  
    </div>

    <script>
        gsap.registerPlugin(MotionPathPlugin);

        gsap.set("#rectSentItems", { x: "-=300" });
        
        const tl = gsap.timeline({ paused: true });
        let ranOnce = false;

        tl.to("#base", { duration: 0.15, scale: 0.95, transformOrigin: "50% 50%" })
          .to("#base", { duration: 0.1, scale: 1 })
          .to("#txtSend", { duration: 0.2, opacity: 0, scale: 0, transformOrigin: "50% 50%" }, "start")
          // ★ 演出補正：飛行機が飛び立つ瞬間、枠線（青）から白に切り替える処理を追加してアニメーションをより自然に
          .to("#paperPlanePath", { duration: 0.1, fill: "white" }, "start")
          .to("#paperPlane", {
                duration: 1.2,
                ease: "power1.inOut",
                motionPath: {
                    path: "#paperPlaneRoute",
                    align: "#paperPlaneRoute",
                    alignOrigin: [0.5, 0.5],
                    autoRotate: 90
                }
          }, "start")
          .to("#base", { duration: 0.4, opacity: 0, ease: "power1.inOut" }, "start+=0.6")
          .to("#rectSentItems", { x: "0", duration: 0.5, ease: "power2.out" }, "start+=0.6")
          .to("#mask1", { x: "-=300", duration: 0.5, ease: "power2.out" }, "start+=0.6")
          .call(() => {
                document.getElementById("resetForm").submit();
          });

        const btn = document.getElementById("base");

        btn.addEventListener("click", (e) => {
            e.preventDefault();

            const emailValue = document.getElementById("emailInput").value.trim();
            if (emailValue === "") {
                document.getElementById("resetForm").submit();
                return;
            }

            if (!ranOnce) {
                ranOnce = true;
                tl.play();
            }
        });
    </script>
</body>
</html>