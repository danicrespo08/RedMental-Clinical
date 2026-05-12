<?php

namespace App\Http\Controllers\Clinical\Tcm;

use App\Http\Controllers\Controller;
use App\Models\Hhrr\Employee;
use App\Models\Tcm\Admission;
use App\Models\Tcm\ServiceLog;
use App\Models\Tcm\SuperbillWeekLock;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Weekly Mon-Sat TCM superbill grid: rows = active admissions,
 * columns = days, cells show billable case-management entries.
 */
class SuperbillController extends Controller
{
    public function index(Request $request): View
    {
        $weekInput = $request->query('week');
        $monday = $weekInput
            ? Carbon::parse($weekInput)->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);
        $saturday = $monday->copy()->addDays(5);

        $weekDates = [];
        for ($i = 0; $i < 6; $i++) {
            $d = $monday->copy()->addDays($i);
            $weekDates[] = ['date' => $d->toDateString(), 'label' => $d->format('D'), 'short' => $d->format('m/d'), 'today' => $d->isToday()];
        }

        $caseManagerId = $request->query('case_manager_id');
        $statusOnly    = $request->query('status', 'admitted');

        $admissions = Admission::query()
            ->with(['patient', 'caseManager'])
            ->when($caseManagerId, fn ($q) => $q->where('case_manager_id', $caseManagerId))
            ->when($statusOnly !== 'all', fn ($q) => $q->where('status', $statusOnly))
            ->orderBy('patient_id')
            ->get();

        $logs = ServiceLog::query()
            ->whereBetween('service_date', [$monday->toDateString(), $saturday->toDateString()])
            ->whereIn('tcm_admission_id', $admissions->pluck('id'))
            ->get();

        $grid = [];
        foreach ($logs as $log) {
            $grid[$log->tcm_admission_id][$log->service_date->toDateString()] = $log;
        }

        $dayTotals = [];
        foreach ($weekDates as $wd) {
            $dayTotals[$wd['date']] = $logs->where('service_date', Carbon::parse($wd['date']))->sum('units');
        }

        $lock = SuperbillWeekLock::where('week_start_date', $monday->toDateString())->first();

        $stats = [
            'admissions'    => $admissions->count(),
            'rows'          => $logs->count(),
            'units'         => $logs->sum('units'),
            'unbilled'      => $logs->where('billing_status', 'unbilled')->count(),
            'submitted'     => $logs->where('billing_status', 'submitted')->count(),
            'paid'          => $logs->where('billing_status', 'paid')->count(),
            'paid_total'    => (float) $logs->sum('paid_amount'),
            'missing_note'  => $logs->where('has_contact_note', false)->count(),
        ];

        return view('clinical.tcm.superbill.index', [
            'monday'        => $monday,
            'saturday'      => $saturday,
            'weekDates'     => $weekDates,
            'admissions'    => $admissions,
            'grid'          => $grid,
            'dayTotals'     => $dayTotals,
            'stats'         => $stats,
            'lock'          => $lock,
            'caseManagers'  => Employee::where('active', true)->orderBy('last_name')->get(),
            'caseManagerId' => $caseManagerId,
            'statusOnly'    => $statusOnly,
        ]);
    }

    public function lock(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'week_start_date'  => ['required', 'date'],
            'supervisor_name'  => ['nullable', 'string', 'max:200'],
            'notes'            => ['nullable', 'string'],
        ]);
        $data['locked_by'] = auth()->id();
        $data['locked_at'] = now();

        SuperbillWeekLock::updateOrCreate(
            ['client_id' => auth()->user()->client_id, 'week_start_date' => $data['week_start_date']],
            $data,
        );
        return back()->with('status', 'TCM superbill week locked.');
    }

    public function unlock(SuperbillWeekLock $lock): RedirectResponse
    {
        $lock->delete();
        return back()->with('status', 'TCM superbill week unlocked.');
    }
}
