<?php

namespace App\Http\Controllers\Clinical\It;

use App\Http\Controllers\Controller;
use App\Models\Hhrr\Employee;
use App\Models\It\Admission;
use App\Models\It\Session as ItSession;
use App\Models\Hhrr\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdmissionController extends Controller
{
    /** IT module dashboard — admission + session metrics + recent activity. */
    public function dashboard(): View
    {
        $admCounts = Admission::selectRaw("
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'admitted'   THEN 1 ELSE 0 END) AS admitted,
            SUM(CASE WHEN status = 'discharged' THEN 1 ELSE 0 END) AS discharged,
            SUM(CASE WHEN status = 'on_hold'    THEN 1 ELSE 0 END) AS hold
        ")->first();

        $sessionCounts = ItSession::whereMonth('session_date', now()->month)
            ->whereYear('session_date', now()->year)
            ->selectRaw('COUNT(*) AS total, COALESCE(SUM(units), 0) AS units')
            ->first();

        $admissionStats = [
            'total'      => (int) ($admCounts->total ?? 0),
            'admitted'   => (int) ($admCounts->admitted ?? 0),
            'discharged' => (int) ($admCounts->discharged ?? 0),
            'hold'       => (int) ($admCounts->hold ?? 0),
        ];
        $sessionStats = [
            'this_month'  => (int) ($sessionCounts->total ?? 0),
            'units_month' => (int) ($sessionCounts->units ?? 0),
            'all_time'    => ItSession::count(),
        ];

        $recentAdmissions = Admission::with(['patient', 'therapist'])
            ->latest('created_at')->limit(8)->get();

        $recentSessions = ItSession::with(['admission.patient', 'therapist'])
            ->orderByDesc('session_date')->limit(8)->get();

        // CPT mix for this month
        $cptMix = ItSession::whereMonth('session_date', now()->month)
            ->whereYear('session_date', now()->year)
            ->selectRaw('cpt_code, COUNT(*) AS count, SUM(units) AS units')
            ->groupBy('cpt_code')->orderByDesc('count')->get();

        return view('clinical.it.dashboard', [
            'client'           => auth()->user()->client,
            'admissionStats'   => $admissionStats,
            'sessionStats'     => $sessionStats,
            'recentAdmissions' => $recentAdmissions,
            'recentSessions'   => $recentSessions,
            'cptMix'           => $cptMix,
        ]);
    }

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q', ''));

        $admissions = Admission::query()
            ->when($status, fn ($qb) => $qb->where('status', $status))
            ->when($q !== '', fn ($qb) => $qb->whereHas('patient', function ($p) use ($q) {
                $p->where('first_name', 'like', "%{$q}%")->orWhere('last_name', 'like', "%{$q}%")->orWhere('mrn', 'like', "%{$q}%");
            }))
            ->with(['patient', 'therapist'])
            ->orderByDesc('admission_date')
            ->paginate(20)
            ->withQueryString();

        return view('clinical.it.admissions.index', [
            'admissions' => $admissions,
            'statuses'   => Admission::STATUSES,
            'status'     => $status,
            'q'          => $q,
        ]);
    }

    public function create(): View
    {
        return view('clinical.it.admissions.form', [
            'admission'  => new Admission(['status' => 'admitted', 'admission_date' => now()->toDateString()]),
            'patients'   => Patient::where('active', true)->orderBy('last_name')->get(),
            'therapists' => Employee::where('active', true)->orderBy('last_name')->get(),
            'statuses'   => Admission::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Admission::create($this->validated($request));
        return redirect()->route('clinical.it.admissions.index')->with('status', 'IT admission created.');
    }

    public function show(Admission $admission): View
    {
        $admission->load(['patient', 'therapist', 'treatmentPlans.goals', 'dischargeSummary', 'sessions' => fn ($q) => $q->orderByDesc('session_date')]);
        return view('clinical.it.admissions.show', compact('admission'));
    }

    public function edit(Admission $admission): View
    {
        return view('clinical.it.admissions.form', [
            'admission'  => $admission,
            'patients'   => Patient::where('active', true)->orderBy('last_name')->get(),
            'therapists' => Employee::where('active', true)->orderBy('last_name')->get(),
            'statuses'   => Admission::STATUSES,
        ]);
    }

    public function update(Request $request, Admission $admission): RedirectResponse
    {
        $admission->update($this->validated($request));
        return redirect()->route('clinical.it.admissions.show', $admission)->with('status', 'IT admission updated.');
    }

    public function destroy(Admission $admission): RedirectResponse
    {
        $admission->delete();
        return redirect()->route('clinical.it.admissions.index')->with('status', 'IT admission deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'patient_id'            => ['required', 'exists:patients,id'],
            'therapist_id'          => ['nullable', 'exists:employees,id'],
            'admission_date'        => ['required', 'date'],
            'discharge_date'        => ['nullable', 'date', 'after_or_equal:admission_date'],
            'status'                => ['required', Rule::in(array_keys(Admission::STATUSES))],
            'diagnosis_code'        => ['nullable', 'string', 'max:20'],
            'diagnosis_description' => ['nullable', 'string', 'max:200'],
            'authorization_number'  => ['nullable', 'string', 'max:50'],
            'notes'                 => ['nullable', 'string'],
        ]);
    }
}
