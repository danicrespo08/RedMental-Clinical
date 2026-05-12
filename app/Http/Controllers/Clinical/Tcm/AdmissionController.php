<?php

namespace App\Http\Controllers\Clinical\Tcm;

use App\Http\Controllers\Controller;
use App\Models\Hhrr\Employee;
use App\Models\Hhrr\Patient;
use App\Models\Tcm\Admission;
use App\Models\Tcm\Contact as TcmContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdmissionController extends Controller
{
    /** TCM module dashboard — admission + contact metrics + recent activity. */
    public function dashboard(): View
    {
        $admCounts = Admission::selectRaw("
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'admitted'   THEN 1 ELSE 0 END) AS admitted,
            SUM(CASE WHEN status = 'discharged' THEN 1 ELSE 0 END) AS discharged,
            SUM(CASE WHEN status = 'on_hold'    THEN 1 ELSE 0 END) AS hold
        ")->first();

        $contactCounts = TcmContact::whereMonth('contact_at', now()->month)
            ->whereYear('contact_at', now()->year)
            ->selectRaw('COUNT(*) AS total, COALESCE(SUM(units), 0) AS units, COALESCE(SUM(duration_minutes), 0) AS minutes')
            ->first();

        $admissionStats = [
            'total'      => (int) ($admCounts->total ?? 0),
            'admitted'   => (int) ($admCounts->admitted ?? 0),
            'discharged' => (int) ($admCounts->discharged ?? 0),
            'hold'       => (int) ($admCounts->hold ?? 0),
        ];
        $contactStats = [
            'this_month'   => (int) ($contactCounts->total ?? 0),
            'units_month'  => (int) ($contactCounts->units ?? 0),
            'minutes_month'=> (int) ($contactCounts->minutes ?? 0),
            'all_time'     => TcmContact::count(),
        ];

        $recentAdmissions = Admission::with(['patient', 'caseManager'])
            ->latest('created_at')->limit(8)->get();

        $recentContacts = TcmContact::with(['admission.patient', 'caseManager'])
            ->orderByDesc('contact_at')->limit(8)->get();

        // Contact-type mix for this month
        $typeMix = TcmContact::whereMonth('contact_at', now()->month)
            ->whereYear('contact_at', now()->year)
            ->selectRaw('contact_type, COUNT(*) AS count')
            ->groupBy('contact_type')->orderByDesc('count')->get();

        return view('clinical.tcm.dashboard', [
            'client'           => auth()->user()->client,
            'admissionStats'   => $admissionStats,
            'contactStats'     => $contactStats,
            'recentAdmissions' => $recentAdmissions,
            'recentContacts'   => $recentContacts,
            'typeMix'          => $typeMix,
            'types'            => TcmContact::CONTACT_TYPES,
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
            ->with(['patient', 'caseManager'])
            ->orderByDesc('admission_date')
            ->paginate(20)
            ->withQueryString();

        return view('clinical.tcm.admissions.index', [
            'admissions' => $admissions,
            'statuses'   => Admission::STATUSES,
            'status'     => $status,
            'q'          => $q,
        ]);
    }

    public function create(): View
    {
        return view('clinical.tcm.admissions.form', [
            'admission'    => new Admission(['status' => 'admitted', 'admission_date' => now()->toDateString()]),
            'patients'     => Patient::where('active', true)->orderBy('last_name')->get(),
            'caseManagers' => Employee::where('active', true)->orderBy('last_name')->get(),
            'statuses'     => Admission::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Admission::create($this->validated($request));
        return redirect()->route('clinical.tcm.admissions.index')->with('status', 'TCM admission created.');
    }

    public function show(Admission $admission): View
    {
        $admission->load(['patient', 'caseManager', 'contacts' => fn ($q) => $q->orderByDesc('contact_at')]);
        return view('clinical.tcm.admissions.show', compact('admission'));
    }

    public function edit(Admission $admission): View
    {
        return view('clinical.tcm.admissions.form', [
            'admission'    => $admission,
            'patients'     => Patient::where('active', true)->orderBy('last_name')->get(),
            'caseManagers' => Employee::where('active', true)->orderBy('last_name')->get(),
            'statuses'     => Admission::STATUSES,
        ]);
    }

    public function update(Request $request, Admission $admission): RedirectResponse
    {
        $admission->update($this->validated($request));
        return redirect()->route('clinical.tcm.admissions.show', $admission)->with('status', 'TCM admission updated.');
    }

    public function destroy(Admission $admission): RedirectResponse
    {
        $admission->delete();
        return redirect()->route('clinical.tcm.admissions.index')->with('status', 'TCM admission deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'patient_id'            => ['required', 'exists:patients,id'],
            'case_manager_id'       => ['nullable', 'exists:employees,id'],
            'admission_date'        => ['required', 'date'],
            'discharge_date'        => ['nullable', 'date', 'after_or_equal:admission_date'],
            'status'                => ['required', Rule::in(array_keys(Admission::STATUSES))],
            'diagnosis_code'        => ['nullable', 'string', 'max:20'],
            'diagnosis_description' => ['nullable', 'string', 'max:200'],
            'authorization_number'  => ['nullable', 'string', 'max:50'],
            'service_plan'          => ['nullable', 'string'],
            'notes'                 => ['nullable', 'string'],
        ]);
    }
}
