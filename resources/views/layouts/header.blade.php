<header class="top-header">
    <a href="#" class="notice-link">ⓘ お知らせ</a>

    <div class="user-info">
        {{-- ❓ヘルプメニュー --}}
        <div class="header-dropdown-wrapper" id="header-help-wrapper">
            <button type="button" class="header-icon-link header-dropdown-toggle" aria-haspopup="true" aria-expanded="false">❓</button>

            <div class="header-dropdown-menu">
                <a href="#" class="header-dropdown-menu-item">ヘルプ</a>
                <a href="#" class="header-dropdown-menu-item">よくあるご質問</a>
                <a href="#" class="header-dropdown-menu-item">ご意見・ご要望</a>
                <a href="#" class="header-dropdown-menu-item modal-trigger" data-target="terms-modal">利用規約</a>
                <a href="#" class="header-dropdown-menu-item modal-trigger" data-target="privacy-modal">プライバシーポリシー</a>
                <a href="#" class="header-dropdown-menu-item">外部送信規律に関する公表</a>
            </div>
        </div>

        {{-- ユーザー名メニュー --}}
        <div class="header-dropdown-wrapper user-name" id="header-user-wrapper">
            <button type="button" class="header-user-link header-dropdown-toggle" aria-haspopup="true" aria-expanded="false">
                {{ Auth::user() ? Auth::user()->name : 'ゲスト' }}
            </button>

            <div class="header-dropdown-menu">
                <a href="#" class="header-dropdown-menu-item">オプション</a>
                <a href="#" class="header-dropdown-menu-item">メール通知設定</a>
                <a href="#" class="header-dropdown-menu-item">パスワード変更</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="header-dropdown-menu-item header-dropdown-menu-item--button">ログアウト</button>
                </form>
            </div>
        </div>
        さん
    </div>
</header>

<div id="terms-modal" class="modal-overlay">
    <div class="modal-window">
        <div class="modal-header">
            <h3>利用規約（Sky Duty 開発演習版）</h3>
            <button class="modal-close-btn">&times;</button>
        </div>
        <div class="modal-body">
            <p><strong>第1条（目的）</strong><br>本規約は、Sky Duty（以下「本システム」）の開発演習における開発メンバーのモチベーション維持および円滑な研修運用を目的とします。</p>
            <p><strong>第2条（規約の適用）</strong><br>開発メンバーは、本システムに触れた時点で、本規約のすべての項目に血判を押す勢いで同意したものとみなされます。</p>
            <p><strong>第3条（居眠りの権利）</strong><br>開発メンバーは、エラーが解決せず脳がオーバーヒートした場合、1日最大20分間の「パワーナップ（積極的仮眠）」を取得できます。ただし、イビキの音量は30デシベル以下（図書館の静けさ）に制御しなければなりません。</p>
            <p><strong>第4条（禁止事項）</strong><br>開発メンバーは、本システムのコード内に「絶対動く」「神頼み」などのコメントを残してはなりません。</p>
            <p><strong>第5条（利用制限）</strong><br>サーバー負荷を避けるため、故意に `while(true)` を走らせてPCを暖房器具代わりに使う行為を禁止します。</p>
            <p><strong>第6条（配給と義務）</strong><br>午後15時を過ぎた場合、糖分補給としてラムネまたはチョコレートの摂取が強く推奨されます。これに違反して集中力が切れバグを出した場合、すべて自己責任となります。</p>
            <p><strong>第7条（知的財産権）</strong><br>演習中に生み出された奇跡的なバグおよび芸術的なインデントの崩れは、開発メンバー全員の共有財産として語り継がれます。</p>
            <p><strong>第8条（エラーへの態度）</strong><br>赤いエラー画面が表示された際、「なんでやねん」と画面を叩いて物理攻撃を試みる行為を禁止します。エラーは悪くありません。</p>
            <p><strong>第9条（Gitコミットのルール）</strong><br>コミットメッセージに「直した」「修正」「あああ」などの雑な文言を使用することを禁じます。未来の自分が泣きます。</p>
            <p><strong>第10条（独り言の許可）</strong><br>バグ探索中、「あぁ分かった！」「いや待てよ…？」などの独り言を呟くことは、周囲の邪魔にならない範囲で完全に合法とします。</p>
            <p><strong>第11条（パスワード管理）</strong><br>テストユーザーのパスワードを `password` に設定する怠慢は、演習環境に限り大目に目をつむるものとします。</p>
            <p><strong>第12条（デザインへの深い執着）</strong><br>CSSの調整にこだわりすぎて、肝心のPHP（ロジック）の実装が1行も進まない事態を「デザイナーズ・ハイ」と呼び、警戒します。そして反省します。</p>
            <p><strong>第13条（コーヒー依存の免責）</strong><br>開発中のカフェイン摂取量の増大について、当システムおよびは一切の責任を負わないものとします。</p>
            <p><strong>第14条（騒音の規定）</strong><br>Enterキーを叩く際、周囲を威嚇するような「ッターン！」という強打音を出してはなりません。キーボードを優しく愛してください。</p>
            <p><strong>第15条（環境構築の呪い）</strong><br>「自分のPCでは動くんですけど」というセリフは、本番環境において何ら効力を持たない不吉な呪文として定義されます。</p>
            <p><strong>第16条（コピペの節度）</strong><br>AIのコードをコピペする際は、少なくとも1回は処理内容を確認し、理解する義務があります。我々はもちろんやってますよ。</p>
            <p><strong>第17条（神コードの禁止）</strong><br>1ファイルの行数が1万行を超えるような、誰も解読できない「スパゲッティコード」を錬成してはなりません。肉体の一部が奪われてしまう可能性があります。</p>
            <p><strong>第18条（同期へのリスペクト）</strong><br>隣の同期が自分より進んでいても焦ってはなりません。進捗は「気流」と同じで、いつか追い風が吹く仕様となっております。</p>
            <p><strong>第19条（規約の変更）</strong><br>本システムは、ユーザーに通知することなく、いつでも規約に項目を誰でも匿名追加できるものとします。</p>
            <p><strong>第20条（準拠法と裁判管轄）</strong><br>本規約に関する紛争が生じた場合は、本社の一番日当たりの良い会議室を第一審の専属的合意管轄裁判所とします。</p>
        </div>
        <div class="modal-footer">
            <button class="modal-action-btn">全条項を承諾して閉じる</button>
        </div>
    </div>
</div>

<div id="privacy-modal" class="modal-overlay">
    <div class="modal-window">
        <div class="modal-header">
            <h3>プライバシーポリシー（Sky Duty 開発演習版）</h3>
            <button class="modal-close-btn">&times;</button>
        </div>
        <div class="modal-body">
            <p><strong>1. 個人情報の定義</strong><br>本ポリシーにおいて個人情報とは、演習用のデータベース（DB）内に存在するダミーの氏名、架空の社員番号、存在しない生年月日を指します。</p>
            <p><strong>2. 情報の管理方法</strong><br>当システムは、ダミーデータをローカル環境に厳重に保管し、本物の勤怠クラウドアプリなどに流出しないよう祈祷を捧げます。</p>
            <p><strong>3. 秘密保持の例外（コーヒーの好み）</strong><br>当システムは、ユーザー様の「コーヒーの好み（微糖、ブラック、またはカフェラテ）」を自動学習し、疲労が極限に達した際に周囲のメンバーへ差し入れを促す目的で勝手に開示する場合があります。</p>
            <p><strong>4. 利用目的の限定</strong><br>収集したバグのログは、本システム開発チームの技術向上および「あ、ここ前も間違えたな」という尊い自己反省の材料としてのみ利用されます。</p>
            <p><strong>5. 第三者提供の制限</strong><br>当システムが取得した「進捗遅れデータ」を、ユーザー様の同意なしに実家の保護者へ送信することは決してありません。</p>
            <p><strong>6. リーダーへの感謝</strong><br>メンバーが開発中、リーダーに『ありがとう』と心の中で呟いた回数は、暗号化されてメンバーの「徳（トークン）」として裏でカウントされます。</p>
            <p><strong>7. クッキー（Cookie）の利用</strong><br>当システムは、ログイン状態を維持するためにCookieを使用します。なお、焼き菓子のクッキーは各自でご用意ください。</p>
            <p><strong>8. アクセス解析</strong><br>当システムは、ユーザー様が「どの画面で一番フリーズ（困惑）していたか」を解析し、今後のカリキュラム改善の闇データとして蓄積します。</p>
            <p><strong>9. 安全管理措置</strong><br>PCの画面をロックせずに離席（プロテクト違反）した場合、同期にデスクトップの壁紙をおもしろ画像に変えられるかもしれないリスクは自己責任となります。</p>
            <p><strong>10. 休憩時間のトラッキング</strong><br>ユーザー様が「ちょっと休憩」と言ってからスマホを弄っている時間は、タイムディラシオン（時間の歪み）により通常の3倍速で消費されることをログに記録します。</p>
            <p><strong>11. 情報の開示請求</strong><br>ユーザー様は、自分の書いたコードの「美しさレベル」の開示を請求できますが、システムの判定は常に「伸び代しかない」となります。</p>
            <p><strong>12. データの訂正</strong><br>過去のコミット履歴を揉み消したいという衝動は、個人のプライバシーとして最大限尊重されますが、やりすぎるとこの世の全てが消え去ります。</p>
            <p><strong>13. 免責事項</strong><br>開発の夢を見て夜中に「セミコロン…！」と叫んだとしても、当システムはその精神的疲労について一切の補償を行いません。</p>
            <p><strong>14. お気に入り機能の追跡</strong><br>サイドバーの絵文字アイコンを無駄に連打したログは、「ただの動作確認」として温かくスルーされます。</p>
            <p><strong>15. 視力保護に関する警告</strong><br>ダークモードが未実装の間、白い画面を凝視し続けたユーザーの目がショボショボする事象について、システムは目薬の配給義務を負いません。</p>
            <p><strong>16. ダミーデータの尊厳</strong><br>テストデータに「テスト太郎」や「あいうえお」を乱造する行為は、データのプライバシー保護の観点及び見栄えの観点から、極力「山田太郎」にするよう推奨します。</p>
            <p><strong>17. 匿名加工情報</strong><br>ユーザーのソースコードから一切の天才的センスを排除し、普通のコードに加工した情報を、未来の後輩 of 後輩の教材として利用することがあります。</p>
            <p><strong>18. お祝いログの記録</strong><br>行き詰っていた処理が完全に動いた瞬間のユーザーのガッツポーズは、周囲の目撃情報として記憶域に永続保存されます。</p>
            <p><strong>19. ポリシーの改定</strong><br>本プライバシーポリシーは、新しいバグや自販機下の100円玉が発見されるたびに、事前の予告なくバージョンアップされます。</p>
            <p><strong>20. お問い合わせ窓口</strong><br>本ポリシーに関する苦情・ご意見、または「お腹が減った」などの泣き言は、浜﨑康太朗を除く本システム開発チーム、またはセブンイレブン日本橋本町1丁目店で受け付けます。</p>
        </div>
        <div class="modal-footer">
            <button class="modal-action-btn">確認して閉じる</button>
        </div>
    </div>
</div>