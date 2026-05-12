<?php

namespace App\Http\Controllers\Clinical\Psr;

use App\Http\Controllers\Controller;
use App\Models\Hhrr\Clinic;
use App\Models\Hhrr\Employee;
use App\Models\Psr\Admission;
use App\Models\Psr\ServiceLog;
use App\Models\Psr\SuperbillWeekLock;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PSR Superbill controller.
 *
 * Renders a weekly Mon–Sat attendance grid: rows = active admissions, columns
 * = days of the selected week, cells show service-log presence + units.
 * Supervisor-level lock freezes the week.
 */
class PsrSuperbillController extends Controller
{
    public function index(Request $request): View
    {
        // Week resolution: ISO `YYYY-Www` or any parseable date; default to this week's Monday.
        $weekInput = $request->query('week');
        if ($weekInput && preg_match('/^(\d{4})-W(\d{2})$/', $weekInput, $m)) {
            $monday = Carbon::now()->setISODate((int) $m[1], (int) $m[2])->startOfWeek(Carbon::MONDAY);
        } elseif ($weekInput) {
            $monday = Carbon::parse($weekInput)->startOfWeek(Carbon::MONDAY);
        } else {
            $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);
        }
        $saturday = $monday->copy()->addDays(5);

        // Mon-Sat day cells used by the view.
        $weekDates = [];
        for ($i = 0; $i < 6; $i++) {
            $d = $monday->copy()->addDays($i);
            $weekDates[] = [
                'date'  => $d->toDateString(),
                'label' => $d->format('D'),     // Mon, Tue, …
                'short' => $d->format('m/d'),
                'today' => $d->isToday(),
            ];
        }

        $clinicId    = $request->query('clinic_id');
        $therapistId = $request->query('therapist_id');
        $statusOnly  = $request->query('status', 'admitted');

        $admissionsQuery = Admission::query()
            ->with(['patient', 'clinic', 'assignedTherapist'])
            ->when($clinicId,    fn ($q) => $q->where('clinic_id', $clinicId))
            ->when($therapistId, fn ($q) => $q->where('assigned_therapist_id', $therapistId))
            ->when($statusOnly !== 'all', fn ($q) => $q->where('status', $statusOnly))
            ->orderBy('clinic_id')
            ->orderBy('patient_id');

        $admissions = $admissionsQuery->get();

        // Build a [admission_id][YYYY-MM-DD] => ServiceLog grid.
        $logs = ServiceLog::query()
            ->whereBetween('service_date', [$monday->toDateString(), $saturday->toDateString()])
            ->whereIn('psr_admission_id', $admissions->pluck('id'))
            ->get();

        $grid = [];
        foreach ($logs as $log) {
            $grid[$log->psr_admission_id][$log->service_date->toDateString()] = $log;
        }

        // Per-day column totals (units).
        $dayTotals = [];
        foreach ($weekDates as $wd) {
            $dayTotals[$wd['date']] = $logs->where('service_date', Carbon::parse($wd['date']))->sum('units');
        }

        // Lock: client-wide week lock (clinic_id null) supersedes everything for the demo.
        $lock = SuperbillWeekLock::query()
            ->where('week_start_date', $monday->toDateString())
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId), fn ($q) => $q->whereNull('clinic_id'))
            ->first();

        $stats = [
            'admissions'   => $admissions->count(),
            'rows'         => $logs->count(),
            'units'        => $logs->sum('units'),
            'unbilled'     => $logs->where('billing_status', 'unbilled')->count(),
            'submitted'    => $logs->where('billing_status', 'submitted')->count(),
            'paid'         => $logs->where('billing_status', 'paid')->count(),
            'paid_total'   => (float) $logs->sum('paid_amount'),
            'missing_note' => $logs->where('has_progress_note', false)->count(),
        ];

        return view('clinical.psr.superbill.index', [
            'monday'      => $monday,
            'saturday'    => $saturday,
            'weekDates'   => $weekDates,
            'admissions'  => $admissions,
            'grid'        => $grid,
            'dayTotals'   => $dayTotals,
            'stats'       => $stats,
            'lock'        => $lock,
            'clinics'     => Clinic::where('active', true)->orderBy('name')->get(),
            'therapists'  => Employee::where('active', true)->where('is_provider', true)->orderBy('last_name')->get(),
            'clinicId'    => $clinicId,
            'therapistId' => $therapistId,
            'statusOnly'  => $statusOnly,
        ]);
    }

    public function lock(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'clinic_id'        => ['nullable', 'exists:clinics,id'],
            'week_start_date'  => ['required', 'date'],
            'supervisor_name'  => ['nullable', 'string', 'max:200'],
            'notes'            => ['nullable', 'string'],
        ]);
        $data['locked_by'] = auth()->id();
        $data['locked_at'] = now();

        SuperbillWeekLock::updateOrCreate(
            [
                'client_id'       => auth()->user()->client_id,
                'clinic_id'       => $data['clinic_id'] ?? null,
                'week_start_date' => $data['week_start_date'],
            ],
            $data
        );

        return back()->with('status', 'Superbill week locked. Service-log entries for this week are frozen.');
    }

    public function unlock(SuperbillWeekLock $lock): RedirectResponse
    {
        $lock->delete();
        return back()->with('status', 'Superbill week unlocked.');
    }
}
