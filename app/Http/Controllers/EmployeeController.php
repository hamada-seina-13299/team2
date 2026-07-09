<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * 社員検索画面表示
     */
    public function index(Request $request)
    {
        $keyword = $request->input('keyword');
        $employeeId = $request->input('employee_id');
        $dept = $request->input('dept');
        
        // 💡 画面からのソート要求を正しく受け取る（初期値はIDの▼(desc)）
        $sortBy = $request->input('sort_by', 'id');
        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        // プルダウン用：重複を除いた部署名一覧
        $depts = User::whereNotNull('dept')
            ->where('dept', '!=', '')
            ->distinct()
            ->orderBy('dept')
            ->pluck('dept');

        $query = User::query();

        // 社員名 or メールアドレスで検索
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        // 社員IDで検索（カンマ区切りで複数指定可）
        if ($employeeId) {
            $ids = collect(explode(',', $employeeId))
                ->map(fn ($id) => trim($id))
                ->filter(fn ($id) => $id !== '' && is_numeric($id))
                ->values();

            if ($ids->isNotEmpty()) {
                $query->whereIn('id', $ids);
            }
        }

        // 部門で絞り込み
        if ($dept) {
            $query->where('dept', $dept);
        }

        // 💡 決定したソート条件でデータを取得
        $employees = $query->orderBy($sortBy, $order)->paginate(50)->withQueryString();

        return view('employees.index', [
            'employees'  => $employees,
            'depts'      => $depts,
            'keyword'    => $keyword,
            'employeeId' => $employeeId,
            'dept'       => $dept,
            'sortBy'     => $sortBy,
            'order'      => $order,
        ]);
    }
}