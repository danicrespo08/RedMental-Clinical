<?php

namespace App\Http\Controllers\Clinical\Psr;

use App\Http\Controllers\Controller;
use App\Models\Hhrr\Clinic;
use App\Models\Hhrr\Employee;
use App\Models\Hhrr\Patient;
use App\Models\Psr\Admission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * PSR (Psychosocial Rehabilitation) — Admissions controller.
 *
 * Mirrors albamed/PsrAdmissionController. Handles the master record that
 * links a Patient to the PSR program at a specific Clinic, plus quick
 * status transitions (admit, hold, discharge).
 *
 * Sub-records (intake, assessment, treatment plan, authorizations, progress
 * notes, service logs, discharge summary) live in their own controllers and
 * reference this admission via psr_admission_id.
 */
class PsrAdmissionController extends Controller
{
    public function index(Request $request): View
    {
        $search       = trim((string) $request->query('search', ''));
        $status       = $request->query('status');
        $clinicFilter = $request->query('clinic_id');
        $dateFrom     = $request->query('date_from');
        $dateTo       = $request->query('date_to');

        $admissions = Admission::query()
            ->with([
                'patient', 'clinic', 'assignedTherapist',
                'intake', 'bioAssessment',
                'treatmentPlans' => fn ($q) => $q->select('id', 'psr_admission_id', 'is_signed', 'start_date', 'end_date'),
                'farsAssessments' => fn ($q) => $q->select('id', 'psr_admission_id', 'evaluation_type', 'evaluation_date'),
                'authorizations' => fn ($q) => $q->select('id', 'psr_admission_id', 'status', 'approved_end_date'),
            ])
            ->when($search !== '', fn ($qb) => $qb->where(function ($w) use ($search) {
                $w->whereHas('patient', fn ($p) => $p
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name',  'like', "%{$search}%")
                    ->orWhere('mrn',         'like', "%{$search}%"))
                  ->orWhereHas('clinic', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            }))
            ->when($status,       fn ($qb) => $qb->where('status', $status))
            ->when($clinicFilter, fn ($qb) => $qb->where('clinic_id', $clinicFilter))
            ->when($dateFrom,     fn ($qb) => $qb->whereDate('admission_date', '>=', $dateFrom))
            ->when($dateTo,       fn ($qb) => $qb->whereDate('admission_date', '<=', $dateTo))
            ->orderByDesc('admission_date')
            ->paginate(20)
            ->withQueryString();

        // Aggregate counts in one query ( dashboard pattern)
        $counts = Admission::selectRaw("
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'admitted'   THEN 1 ELSE 0 END) AS admitted,
            SUM(CASE WHEN status = 'on_hold'    THEN 1 ELSE 0 END) AS hold,
            SUM(CASE WHEN status = 'discharged' THEN 1 ELSE 0 END) AS discharged
        ")->first();
        $stats = [
            'total'      => (int) ($counts->total ?? 0),
            'admitted'   => (int) ($counts->admitted ?? 0),
            'hold'       => (int) ($counts->hold ?? 0),
            'discharged' => (int) ($counts->discharged ?? 0),
        ];

        return view('clinical.psr.admissions.index', [
            'admissions'    => $admissions,
            'stats'         => $stats,
            'filterClinics' => Clinic::where('active', true)->orderBy('name')->get(),
            'search'        => $search,
            'status'        => $status,
            'clinicFilter'  => $clinicFilter,
            'dateFrom'      => $dateFrom,
            'dateTo'        => $dateTo,
        ]);
    }

    public function create(): View
    {
        return view('clinical.psr.admissions.form', [
            'admission'  => new Admission([
                'admission_date' => now()->toDateString(),
                'status'         => 'pending_intake',
            ]),
            'patients'   => Patient::where('active', true)->orderBy('last_name')->get(),
            'clinics'    => Clinic::where('active', true)->orderBy('name')->get(),
            'therapists' => Employee::where('active', true)->where('is_provider', true)->orderBy('last_name')->get(),
            'statuses'   => Admission::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = auth()->id();

        $admission = Admission::create($data);

        return redirect()
            ->route('clinical.psr.admissions.show', $admission)
            ->with('status', 'Admission created.');
    }

    public function show(Admission $admission): View
    {
        $admission->load([
            'patient', 'clinic', 'assignedTherapist', 'referringProvider',
            'intake',
            'bioAssessment.signedByEmployee',
            'farsAssessments' => fn ($q) => $q->orderByDesc('evaluation_date'),
            'treatmentPlans.goals.objectives',
            'authorizations' => fn ($q) => $q->orderByDesc('created_at'),
            'progressNotes' => fn ($q) => $q->orderByDesc('note_date')->limit(10),
            'serviceLogs' => fn ($q) => $q->orderByDesc('service_date')->limit(10),
            'dischargeSummary',
            'documents',
        ]);

        return view('clinical.psr.admissions.show', compact('admission'));
    }

    public function edit(Admission $admission): View
    {
        return view('clinical.psr.admissions.form', [
            'admission'  => $admission,
            'patients'   => Patient::where('active', true)->orderBy('last_name')->get(),
            'clinics'    => Clinic::where('active', true)->orderBy('name')->get(),
            'therapists' => Employee::where('active', true)->where('is_provider', true)->orderBy('last_name')->get(),
            'statuses'   => Admission::STATUSES,
        ]);
    }

    public function update(Request $request, Admission $admission): RedirectResponse
    {
        $admission->update($this->validated($request));
        return redirect()
            ->route('clinical.psr.admissions.show', $admission)
            ->with('status', 'Admission updated.');
    }

    public function destroy(Admission $admission): RedirectResponse
    {
        $admission->delete();
        return redirect()
            ->route('clinical.psr.admissions.index')
            ->with('status', 'Admission deleted.');
    }

    /** Status transition shortcut (button on the show page). */
    public function transitionStatus(Request $request, Admission $admission): RedirectResponse
    {
        $request->validate(['status' => ['required', Rule::in(array_keys(Admission::STATUSES))]]);
        $admission->status = $request->input('status');
        if ($request->input('status') === 'discharged' && ! $admission->discharge_date) {
            $admission->discharge_date = now()->toDateString();
        }
        $admission->save();
        return back()->with('status', 'Admission status updated to ' . Admission::STATUSES[$admission->status] . '.');
    }

    private function validated(Request $request): array
    {
        foreach (['discharge_date', 'referral_date', 'assigned_therapist_id', 'referring_provider_id', 'risk_score'] as $f) {
            if ($request->input($f) === '') $request->merge([$f => null]);
        }

        return $request->validate([
            'patient_id'              => ['required', 'exists:patients,id'],
            'clinic_id'               => ['required', 'exists:clinics,id'],
            'admission_date'          => ['required', 'date'],
            'discharge_date'          => ['nullable', 'date', 'after_or_equal:admission_date'],
            'status'                  => ['required', Rule::in(array_keys(Admission::STATUSES))],
            'assigned_therapist_id'   => ['nullable', 'exists:employees,id'],
            'referring_provider_id'   => ['nullable', 'exists:employees,id'],
            'referral_date'           => ['nullable', 'date'],
            'primary_dx_code'         => ['nullable', 'string', 'max:20'],
            'primary_dx_description'  => ['nullable', 'string', 'max:255'],
            'secondary_dx_code'       => ['nullable', 'string', 'max:20'],
            'secondary_dx_description'=> ['nullable', 'string', 'max:255'],
            'default_shift_pos'       => ['nullable', 'string', 'max:10'],
            'risk_score'              => ['nullable', 'integer', 'min:0', 'max:200'],
        ]);
    }
}
