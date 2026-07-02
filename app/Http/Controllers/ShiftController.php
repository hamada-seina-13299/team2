<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\ShiftMaster;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

        $days = collect($period)->map(function (Carbon $date) use ($shifts) {
            return [
                'date' => $date,
                'shift' => $shifts->get($date->format('Y-m-d')),
            ];
        });

        $shiftMasters = ShiftMaster::where('user_id', Auth::id())
            ->orWhereNull('user_id')
            ->orderBy('name')
            ->get();

        return view('shift.list', [
            'days' => $days,
            'year' => $year,
            'month' => $month,
            'shiftMasters' => $shiftMasters,
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
            ]);
        }

        return back()->with('success', count($dates) . "件のシフトをまとめて追加しました。シフト一覧に反映されました。");
    }

    public function destroyMaster(Request $request)
    {
        $validated = $request->validate([
            'master_id' => 'required|exists:shift_masters,id',
        ]);

        $master = ShiftMaster::where('id', $validated['master_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $workingPlace = $master->working_place;

        try {
            $master->delete();
            return back()->with('success', "{$workingPlace}のマスタを削除しました。");
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->withErrors([
                    'master_id' => "「{$workingPlace}」は、すでにカレンダーのシフトに登録されているため削除できません。先にカレンダー側のシフトを削除してください。"
                ]);
            }
            throw $e;
        }
    }
}