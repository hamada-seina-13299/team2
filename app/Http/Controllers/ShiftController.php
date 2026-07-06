<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\ShiftMaster;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yasumi\Yasumi;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $shifts = Shift::with('shiftMaster')
            ->where('user_id', Auth::id())
            ->whereBetween('target_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(fn ($shift) => $shift->target_date->format('Y-m-d'));

        // 💡 日本の祝日を取得し、「Y-m-d => 祝日名」のマップを作成
        //    月またぎ表示はしていないが、年をまたぐケースに備えて前後年分も一応マージしておく
        $holidayMap = [];
        foreach ([$year - 1, $year, $year + 1] as $targetYear) {
            $holidaysProvider = Yasumi::create('Japan', $targetYear, 'ja_JP');
            foreach ($holidaysProvider as $holiday) {
                $holidayMap[$holiday->format('Y-m-d')] = $holiday->getName();
            }
        }

        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

        $days = collect($period)->map(function (Carbon $date) use ($shifts, $holidayMap) {
            $dateKey = $date->format('Y-m-d');

            return [
                'date' => $date,
                'shift' => $shifts->get($dateKey),
                'is_holiday' => array_key_exists($dateKey, $holidayMap),
                'holiday_name' => $holidayMap[$dateKey] ?? null,
            ];
        });

        $shiftMasters = ShiftMaster::where('user_id', Auth::id())
            ->orWhereNull('user_id')
            ->orderBy('name')
            ->get();

        // 💡 前回使用したシフトマスタ（セッション保持）。ワンクリック追加ボタン表示に使用。
        $lastMasterId = session('last_shift_master_id');
        $lastMaster = $lastMasterId
            ? $shiftMasters->firstWhere('id', $lastMasterId)
            : null;

        return view('shift.list', [
            'days' => $days,
            'year' => $year,
            'month' => $month,
            'shiftMasters' => $shiftMasters,
            'lastMaster' => $lastMaster,
        ]);
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'shift_id' => 'required|exists:shifts,id',
        ]);

        $shift = Shift::where('id', $validated['shift_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $formattedDate = Carbon::parse($shift->target_date)->format('m月d日');
        $shift->delete();

        return back()->with('success', "{$formattedDate}のシフトを削除しました。");
    }

    public function store(Request $request)
    {
        $dates = $request->input('target_dates', [$request->input('target_date')]);

        $validated = $request->validate([
            'target_dates' => 'nullable|array',
            'target_dates.*' => 'date',
            'target_date' => 'required_without:target_dates|date',
            'master_id' => 'nullable|exists:shift_masters,id',
            'new_working_place' => 'required_without:master_id|nullable|string|max:255',
            'new_attendance' => 'required_without:master_id|nullable',
            'new_leaving' => 'required_without:master_id|nullable',
            'new_break_time' => 'nullable',
        ], [
            'new_working_place.required_without' => '勤務地名を入力してください。',
            'new_attendance.required_without' => '出勤時刻を入力してください。',
            'new_leaving.required_without' => '退勤時刻を入力してください。',
        ]);

        $masterId = $validated['master_id'] ?? null;

        if (!$masterId) {
            $newMaster = ShiftMaster::create([
                'user_id' => Auth::id(),
                'name' => $validated['new_working_place'],
                'working_place' => $validated['new_working_place'],
                'attendance' => $validated['new_attendance'],
                'leaving' => $validated['new_leaving'],
                'break_time' => $validated['new_break_time'] ?? '00:00',
            ]);

            $masterId = $newMaster->id;
        }

        foreach ($dates as $date) {
            $exists = Shift::where('user_id', Auth::id())->where('target_date', $date)->exists();
            if ($exists) continue;

            Shift::create([
                'user_id' => Auth::id(),
                'master_id' => $masterId,
                'target_date' => $date,
                'status' => '申請中',
                'memo'            => null,
                'attendance_edit' => null,
                'leaving_edit'    => null,
            ]);
        }

        // 💡 今回使用したマスタを記憶しておき、次回「＋シフトを追加」をワンクリックで使えるようにする
        session(['last_shift_master_id' => $masterId]);

        // 💡 Ajax（ワンクリック追加）からの場合は、リロードせず該当行を更新できるようJSONを返す
        if ($request->wantsJson() || $request->ajax()) {
            $createdShifts = Shift::with('shiftMaster')
                ->where('user_id', Auth::id())
                ->whereIn('target_date', $dates)
                ->get();

            return response()->json([
                'success' => true,
                'message' => count($dates) . '件のシフトを追加しました。',
                'shifts' => $createdShifts->map(function ($shift) {
                    return [
                        'date' => $shift->target_date->format('Y-m-d'),
                        'shift_id' => $shift->id,
                        'attendance' => date('H:i', strtotime($shift->shiftMaster->attendance)),
                        'leaving' => date('H:i', strtotime($shift->shiftMaster->leaving)),
                        'master_name' => $shift->shiftMaster->name,
                        'edit_url' => route('shiftcorrection.index', ['shift_id' => $shift->id]),
                    ];
                }),
            ]);
        }

        return back()->with('success', count($dates) . "件のシフトをまとめて追加しました。シフト一覧に反映されました。");
    }

    public function clearLastMaster(Request $request)
    {
        session()->forget('last_shift_master_id');

        return back();
    }

    public function destroyMaster(Request $request)
    {
        $validated = $request->validate([
            'master_id' => 'required|exists:shift_masters,id',
        ]);

        $master = ShiftMaster::where('id', $validated['master_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $masterName = $master->name;

        try {
            $master->delete();
            return back()->with('success', "{$masterName}のマスタを削除しました。");
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->withErrors([
                    'master_id' => "「{$masterName}」は、すでにカレンダーのシフトに登録されているため削除できません。先にカレンダー側のシフトを削除してください。"
                ]);
            }
            throw $e;
        }
    }
}