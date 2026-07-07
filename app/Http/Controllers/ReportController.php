<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    // 💡 サイドバーの「集計レポート」リンクの遷移先。
    //    ここから「出勤データ」（今回はダミー）と「シフト提出承認」（管理者のみ）に分岐する。
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return view('reports.index', [
            'isAdmin' => (bool) ($user?->isAdmin()),
        ]);
    }
}