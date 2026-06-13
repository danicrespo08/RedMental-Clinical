<?php

namespace App\Http\Controllers\Clinical\Psr;

use App\Http\Controllers\Controller;
use App\Models\Hhrr\Clinic;
use App\Models\Hhrr\Employee;
use App\Models\Hhrr\Patient;
use App\Models\Hhrr\Payer;
use App\Models\Psr\Admission;
use App\Models\Psr\Authorization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * PSR Authorization controller.
 *
 * Tracks insurance authorizations per admission with full Florida-Medicaid
 * style fields: service codes, modifiers, units, NPIs, diagnoses, dates,
 * decision tracking, and DocuSign envelope state. Auths can be filtered
 * by status (pending/submitted/approved/denied/expired) and by units-used %.
 */
class PsrAuthorizationController extends Controller
{
    public function index(Request $request): View
    {
        $search       = trim((string) $request->query('search', ''));
        $status       = $request->query('status');
        $admissionId  = $request->query('admission_id');
        $payerFilter  = $request->query('payer_id');
        $dateFrom     = $request->query('date_from');
        $dateTo       = $request->query('date_to');

        $auths = Authorization::query()
            ->with(['admission.patient', 'payer', 'clinic', 'renderingProvider'])
            ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                $w->where('auth_number', 'like', "%{$search}%")
                  ->orWhere('service_code', 'like', "%{$search}%")
                  ->orWhereHas('admission.patient', fn ($p) => $p
                      ->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('mrn', 'like', "%{$search}%"));
            }))
            ->when($status,      fn ($q) => $q->where('status', $status))
            ->when($admissionId, fn ($q) => $q->where('psr_admission_id', $admissionId))
            ->when($payerFilter, fn ($q) => $q->where('payer_id', $payerFilter))
            ->when($dateFrom,    fn ($q) => $q->whereDate('approved_start_date', '>=', $dateFrom))
            ->when($dateTo,      fn ($q) => $q->whereDate('approved_end_date',   '<=', $dateTo))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'all'      => Authorization::count(),
            'pending'  => Authorization::whereIn('status', ['pending', 'submitted', 'pending_review'])->count(),
            'approved' => Authorization::where('status', 'approved')
                ->where(fn ($q) => $q->whereNull('approved_end_date')->orWhereDate('approved_end_date', '>=', now()))
                ->count(),
            'denied'   => Authorization::where('status', 'denied')->count(),
            'expired'  => Authorization::where(fn ($q) => $q->where('status', 'expired')
                ->orWhere(fn ($w) => $w->whereNotNull('approved_end_date')->whereDate('approved_end_date', '<', now())))
                ->count(),
        ];

        $alerts = [
            'expired' => Authorization::with(['admission.patient', 'clinic', 'payer'])
                ->where('status', 'approved')
                ->whereNotNull('approved_end_date')
                ->whereDate('approved_end_date', '<', now())
                ->orderByDesc('approved_end_date')->limit(10)->get(),

            'expiring' => Authorization::with(['admission.patient', 'clinic', 'payer'])
                ->where('status', 'approved')
                ->whereNotNull('approved_end_date')
                ->whereDate('approved_end_date', '>=', now())
                ->whereDate('approved_end_date', '<=', now()->addDays(30))
                ->orderBy('approved_end_date')->limit(10)->get(),

            'unitsLow' => Authorization::with(['admission.patient', 'clinic', 'payer'])
                ->where('status', 'approved')
                ->whereNotNull('units_approved')
                ->where('units_approved', '>', 0)
                ->whereRaw('(CAST(units_used AS REAL) / CAST(units_approved AS REAL)) >= 0.8')
                ->orderByDesc('units_used')->limit(10)->get(),

            'denied' => Authorization::with(['admission.patient', 'clinic', 'payer'])
                ->where('status', 'denied')
                ->latest('decision_date')->limit(10)->get(),

            'noAuth' => \App\Models\Psr\Admission::with(['patient', 'clinic'])
                ->where('status', 'admitted')
                ->whereDoesntHave('authorizations', fn ($q) => $q->where('status', 'approved')
                    ->where(fn ($w) => $w->whereNull('approved_end_date')->orWhereDate('approved_end_date', '>=', now())))
                ->orderByDesc('admission_date')->limit(10)->get(),
        ];

        return view('clinical.psr.authorizations.index', [
            'auths'         => $auths,
            'statuses'      => Authorization::STATUSES,
            'status'        => $status,
            'counts'        => $counts,
            'alerts'        => $alerts,
            'search'        => $search,
            'payerFilter'   => $payerFilter,
            'dateFrom'      => $dateFrom,
            'dateTo'        => $dateTo,
            'filterPayers'  => \App\Models\Hhrr\Payer::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $admission = $request->query('admission_id')
            ? Admission::with(['patient', 'clinic'])->find($request->query('admission_id'))
            : null;

        $auth = new Authorization([
            'psr_admission_id' => $admission?->id,
            'patient_id'       => $admission?->patient_id,
            'clinic_id'        => $admission?->clinic_id,
            'auth_type'        => 'initial',
            'status'           => 'pending',
            'unit_type'        => 'units',
            'service_code'     => 'H2017',
            'units_alert_threshold' => 80,
            'expiry_alert_days'     => 14,
        ]);

        return view('clinical.psr.authorizations.form', [
            'auth'       => $auth,
            'admission'  => $admission,
            'admissions' => Admission::with('patient')->orderByDesc('admission_date')->limit(200)->get(),
            'patients'   => Patient::where('active', true)->orderBy('last_name')->get(),
            'payers'     => Payer::where('active', true)->orderBy('name')->get(),
            'clinics'    => Clinic::where('active', true)->orderBy('name')->get(),
            'providers'  => Employee::where('active', true)->where('is_provider', true)->orderBy('last_name')->get(),
            'statuses'   => Authorization::STATUSES,
            'authTypes'  => Authorization::AUTH_TYPES,
            'unitTypes'  => Authorization::UNIT_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = auth()->id();
        $auth = Authorization::create($data);

        return redirect()
            ->route('clinical.psr.authorizations.show', $auth)
            ->with('status', 'Authorization created.');
    }

    public function show(Authorization $authorization): View
    {
        $authorization->load([
            'admission.patient', 'payer', 'clinic',
            'renderingProvider', 'supervisingProvider',
            'createdBy', 'updatedBy',
            'serviceLogs' => fn ($q) => $q->orderByDesc('service_date')->limit(50),
        ]);
        return view('clinical.psr.authorizations.show', ['auth' => $authorization]);
    }

    public function edit(Authorization $authorization): View|RedirectResponse
    {
        if ($authorization->is_locked) {
            return redirect()
                ->route('clinical.psr.authorizations.show', $authorization)
                ->with('error', 'Approved authorizations are locked and can only be viewed.');
        }

        return view('clinical.psr.authorizations.form', [
            'auth'       => $authorization,
            'admission'  => $authorization->admission()->with(['patient', 'clinic'])->first(),
            'admissions' => Admission::with('patient')->orderByDesc('admission_date')->limit(200)->get(),
            'patients'   => Patient::where('active', true)->orderBy('last_name')->get(),
            'payers'     => Payer::where('active', true)->orderBy('name')->get(),
            'clinics'    => Clinic::where('active', true)->orderBy('name')->get(),
            'providers'  => Employee::where('active', true)->where('is_provider', true)->orderBy('last_name')->get(),
            'statuses'   => Authorization::STATUSES,
            'authTypes'  => Authorization::AUTH_TYPES,
            'unitTypes'  => Authorization::UNIT_TYPES,
        ]);
    }

    public function update(Request $request, Authorization $authorization): RedirectResponse
    {
        abort_if($authorization->is_locked, 403, 'Approved authorizations are locked and cannot be edited.');

        $data = $this->validated($request);
        $data['updated_by'] = auth()->id();
        $authorization->update($data);

        return redirect()
            ->route('clinical.psr.authorizations.show', $authorization)
            ->with('status', 'Authorization updated.');
    }

    public function destroy(Authorization $authorization): RedirectResponse
    {
        abort_if($authorization->is_locked, 403, 'Approved authorizations are locked and cannot be deleted.');

        $authorization->delete();
        return redirect()
            ->route('clinical.psr.authorizations.index')
            ->with('status', 'Authorization deleted.');
    }

    private function validated(Request $request): array
    {
        $nullableStrings = [
            'auth_number','service_code','service_description',
            'modifier_1','modifier_2','modifier_3','modifier_4',
            'place_of_service','revenue_code','frequency',
            'group_npi','rendering_npi','taxonomy_code',
            'member_id','medicaid_id','payer_external_id','plan_type',
            'primary_dx_code','primary_dx_description',
            'secondary_dx_code','secondary_dx_description',
            'clinical_justification','medical_necessity_statement',
            'denial_reason','appeal_notes','contact_name','contact_phone',
            'reference_number','docusign_envelope_id','docusign_status','notes',
        ];
        $nullableDates = [
            'requested_start_date','requested_end_date',
            'approved_start_date','approved_end_date',
            'submission_date','decision_date',
        ];
        $nullableInts = [
            'units_requested','units_approved',
            'units_alert_threshold','expiry_alert_days',
            'rendering_provider_employee_id','supervising_provider_id',
            'payer_id','clinic_id',
        ];
        foreach (array_merge($nullableStrings, $nullableDates, $nullableInts) as $f) {
            if ($request->input($f) === '') $request->merge([$f => null]);
        }

        return $request->validate([
            'psr_admission_id'    => ['required', 'exists:psr_admissions,id'],
            'patient_id'          => ['required', 'exists:patients,id'],
            'payer_id'            => ['nullable', 'exists:payers,id'],
            'clinic_id'           => ['nullable', 'exists:clinics,id'],

            'auth_number'         => ['nullable', 'string', 'max:100'],
            'auth_type'           => ['required', Rule::in(array_keys(Authorization::AUTH_TYPES))],
            'status'              => ['required', Rule::in(array_keys(Authorization::STATUSES))],

            'service_code'        => ['nullable', 'string', 'max:20'],
            'service_description' => ['nullable', 'string', 'max:255'],
            'modifier_1'          => ['nullable', 'string', 'max:10'],
            'modifier_2'          => ['nullable', 'string', 'max:10'],
            'modifier_3'          => ['nullable', 'string', 'max:10'],
            'modifier_4'          => ['nullable', 'string', 'max:10'],
            'place_of_service'    => ['nullable', 'string', 'max:10'],
            'revenue_code'        => ['nullable', 'string', 'max:10'],

            'units_requested'     => ['nullable', 'integer', 'min:0'],
            'units_approved'      => ['nullable', 'integer', 'min:0'],
            'unit_type'           => ['required', Rule::in(array_keys(Authorization::UNIT_TYPES))],
            'frequency'           => ['nullable', 'string', 'max:100'],

            'requested_start_date' => ['nullable', 'date'],
            'requested_end_date'   => ['nullable', 'date'],
            'approved_start_date'  => ['nullable', 'date'],
            'approved_end_date'    => ['nullable', 'date'],

            'rendering_provider_employee_id' => ['nullable', 'exists:employees,id'],
            'supervising_provider_id'        => ['nullable', 'exists:employees,id'],
            'group_npi'           => ['nullable', 'string', 'max:20'],
            'rendering_npi'       => ['nullable', 'string', 'max:20'],
            'taxonomy_code'       => ['nullable', 'string', 'max:20'],

            'member_id'           => ['nullable', 'string'],
            'medicaid_id'         => ['nullable', 'string'],
            'payer_external_id'   => ['nullable', 'string', 'max:50'],
            'plan_type'           => ['nullable', 'string', 'max:50'],

            'primary_dx_code'        => ['nullable', 'string', 'max:20'],
            'primary_dx_description' => ['nullable', 'string', 'max:255'],
            'secondary_dx_code'      => ['nullable', 'string', 'max:20'],
            'secondary_dx_description' => ['nullable', 'string', 'max:255'],

            'clinical_justification'        => ['nullable', 'string'],
            'medical_necessity_statement'   => ['nullable', 'string'],

            'submission_date'     => ['nullable', 'date'],
            'decision_date'       => ['nullable', 'date'],
            'denial_reason'       => ['nullable', 'string'],
            'appeal_notes'        => ['nullable', 'string'],
            'contact_name'        => ['nullable', 'string', 'max:150'],
            'contact_phone'       => ['nullable', 'string', 'max:30'],
            'reference_number'    => ['nullable', 'string', 'max:100'],

            'units_alert_threshold' => ['nullable', 'integer', 'min:1', 'max:100'],
            'expiry_alert_days'     => ['nullable', 'integer', 'min:1', 'max:365'],

            'docusign_envelope_id'  => ['nullable', 'string', 'max:100'],
            'docusign_status'       => ['nullable', 'string', 'max:30'],
            'notes'                 => ['nullable', 'string'],
        ]);
    }
}
