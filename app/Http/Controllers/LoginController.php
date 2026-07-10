<?php

namespace App\Http\Controllers;

use App\Models\User;    // DB関係のファイル
use Illuminate\Http\Request;    // ユーザからの入力を扱うためのツール
use Illuminate\Support\Facades\Auth;    // ユーザの状態を管理・チェックする
use Illuminate\Support\Facades\Hash;    // パスワードのハッシュ化
use Illuminate\Support\Str;    // 一時的なURL用トークンを生成するため
use Illuminate\Support\Facades\Mail; // メール送信ファサード
use App\Mail\ResetPasswordMail;

class LoginController extends Controller
{
    // ================================================
    // ログイン機能
    // ================================================

    // ログイン画面を表示する
    public function index()
    {
        // 入力値は空でビューを表示
        return view('login', [
            'email' => '',
            'password' => ''
        ]);
    }

    // ログイン処理
    public function login(Request $request)
    {

        // エラーメッセージ配列
        $errorList = [];

        // 入力値を取得
        $email = $request->input('email');
        $password = $request->input('password');

        // Adminユーザーかどうか
        if ($email === "admin" and $password === "adminpassword") {
            return redirect('/admin');
        }

        // 入力チェック
        if (empty($email)) {
            // 未入力チェック
            $errorList[] = 'メールアドレスを入力してください';
        }
        if (empty($password)) {
            // 未入力チェック
            $errorList[] = 'パスワードを入力してください';
        }

        // メールアドレスとパスワードが入力されている場合のみ、パスワードの照合を行う
        if (empty($errorList)) {
            // パスワードの照合
            $credentails = ['email' => $email, 'password' => $password];

            //Auth::attemptは成否を true / false で返す
            if (!Auth::attempt($credentails)) {
                // パスワードが間違っていた場合
                $errorList[] = 'メールアドレスまたはパスワードが正しくありません';
            }
        }

        // エラーが発生していた場合
        if (!empty($errorList)) {
            //エラーメッセージと、入力された名前を保持してログイン画面に戻る
            return view('login', [
                'errorList' => $errorList,
                'email' => $email
            ]);
        }


        // ログイン成功時：セッションの再作成
        $request->session()->regenerate();

        // ログイン後、ダッシュボード画面に遷移
        return redirect('/dashboard');
    }


    // ================================================
    // パスワード変更（ログイン中の本人による変更）
    // ================================================
    // メールでの本人確認は不要（既にセッションで認証済みのため）。
    // 「現在のパスワード」を入力させることで本人確認とする。
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => '現在のパスワードを入力してください。',
            'current_password.current_password' => '現在のパスワードが正しくありません。',
            'new_password.required' => '新しいパスワードを入力してください。',
            'new_password.min' => '新しいパスワードは8文字以上で入力してください。',
            'new_password.confirmed' => '確認用パスワードが一致しません。',
        ]);

        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'パスワードを変更しました。',
        ]);
    }


    // ================================================
    // ログアウト処理
    // ================================================
    public function logout(Request $request)
    {
        // ログアウトを実行
        Auth::logout();

        //現在のセッションを無効化
        $request->session()->invalidate();

        // CSRFトークンの再作成
        $request->session()->regenerateToken();

        // ログアウト後、ログイン画面に遷移
        return redirect()->route('login');
    }


    // ================================================
    // パスワード再設定
    // メールアドレス入力・送信
    // ================================================
    // 「パスワードをお忘れの方はこちら」から遷移する画面を表示
    public function showRequestForm()
    {
        return view('password.passwordRequest', ['email' => '']);
    }

    // 送信ボタンが押された時の処理（メール送信）
    public function sendResetLink(Request $request)
    {
        $errorList = [];
        $email = $request->input('email');

        // 未入力チェック
        if (empty($email)) {
            $errorList[] = 'メールアドレスを入力してください';
        }

        // エラーがあった場合
        if (!empty($errorList)) {
            return view('password.passwordRequest', [
                'errorList' => $errorList,
                'email' => $email
            ]);
        }

        // パスワード変更URLに添付する一時的なランダム文字列（トークン）を生成
        $token = Str::random(60);
        
        // パスワード変更画面のURLを生成
        $resetUrl = url("/password/passwordReset/{$token}?email=" . urlencode($email));

        // 生成した $resetUrl をメールに添付して、 $email 宛てに送信
        Mail::to($email)->send(new ResetPasswordMail($resetUrl));
    

        // メール送信完了画面（または完了メッセージ付きで同じ画面）を表示
        return view('password.passwordRequest', [
            'successMessage' => 'パスワード変更用URLをメールに送信しました',
            'email' => $email,
            'developerUrl' => $resetUrl // 開発中はメールの代わりに画面にURLを出して動作確認できるようにしています
        ]);
    }


    // ================================================
    // パスワード再設定
    // URLクリック後の新パスワード入力・変更確定
    // ================================================

    // メールに添付されたURLをクリックした際の、新しいパスワード入力画面を表示
    public function showResetForm(Request $request, $token)
    {
        return view('password.passwordReset', [
            'token' => $token,
            'email' => $request->query('email', '')
        ]);
    }

    // 確定ボタンが押された時のパスワード更新処理
    public function resetPassword(Request $request)
    {
        $errorList = [];
        $email = $request->input('email');
        $token = $request->input('token');
        $password = $request->input('password');

        if (empty($password)) {
            $errorList[] = '新しいパスワードを入力してください';
        }

        if (!empty($errorList)) {
            return view('password.passwordReset', [
                'errorList' => $errorList,
                'token' => $token,
                'email' => $email
            ]);
        }

        // 対象のユーザーを検索してパスワードを更新
        $user = User::where('email', $email)->first();
        if ($user) {
            $user->password = Hash::make($password);
            $user->save();

            // 「変更されました」のポップアップを出すトリガーとして、セッション（フラッシュデータ）に値を格納
            // ログイン画面（index）にリダイレクトします
            return view('password.passwordReset', [
                'password_changed' => true, // フロント側でポップアップを表示させるフラグ
                'token' => $token,
                'email' => $email
            ]);        }

        $errorList[] = 'ユーザーの特定に失敗しました。もう一度最初からやり直してください。';
        return view('password.passwordReset', [
            'errorList' => $errorList,
            'token' => $token,
            'email' => $email
        ]);
    }
   
}