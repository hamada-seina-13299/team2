<?php

namespace App\Http\Controllers;

use App\Models\User;    // DB関係のファイル
use Illuminate\Http\Request;    // ユーザからの入力を扱うためのツール
use Illuminate\Support\Facades\Auth;    // ユーザの状態を管理・チェックする
use Illuminate\Support\Facades\Hash;    // パスワードのハッシュ化

class AccountController extends Controller
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

        // ログイン後、商品一覧画面に遷移
        return redirect('/products');
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
}