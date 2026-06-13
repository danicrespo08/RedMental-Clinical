<?php

namespace App\Http\Controllers\Clinical\Psr;

use App\Http\Controllers\Controller;
use App\Models\Hhrr\Clinic;
use App\Models\Hhrr\Employee;
use App\Models\Hhrr\Patient;
use App\Models\Psr\Admission;
use App\Models\Psr\Authorization;
use App\Models\Psr\ServiceLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * PSR Service Log controller.
 *
 * One row per billable encounter. Most rows are auto-created from group
 * session attendances; manual rows can be added (retroactive) when an
 * encounter happened outside a group session. Used by the Superbill module
 * to compute claims; tracks billing_status (unbilled / submitted / paid /
 * denied / void).
 */
class PsrServiceLogController extends Controller
{
    public function index(Request $request): View
    {
        $billing  = $request->query('billing_status');
        $from     = $request->query('from');
        $to       = $request->query('to');
        $patient  = $request->query('patient_id');

        $logs = ServiceLog::query()
            ->with(['patient', 'admission', 'therapist', 'authorization'])
            ->when($billing, fn ($q) => $q->where('billing_status', $billing))
            ->when($from, fn ($q) => $q->whereDate('service_date', '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate('service_date', '<=', $to))
            ->when($patient, fn ($q) => $q->where('patient_id', $patient))
            ->orderByDesc('service_date')
            ->paginate(25)
            ->withQueryString();

        return view('clinical.psr.service-log.index', [
            'logs'             => $logs,
            'billingStatuses'  => ServiceLog::BILLING_STATUSES,
            'sourceTypes'      => ServiceLog::SOURCE_TYPES,
            'billing'          => $billing,
            'from'             => $from,
            'to'               => $to,
        ]);
    }

    public function create(Request $request): View
    {
        $admission = $request->query('admission_id')
            ? Admission::with(['patient', 'clinic'])->find($request->query('admission_id'))
            : null;

        return view('clinical.psr.service-log.form', [
            'log'        => new ServiceLog([
                'psr_admission_id' => $admission?->id,
                'patient_id'       => $admission?->patient_id,
                'clinic_id'        => $admission?->clinic_id,
                'service_date'     => now()->toDateString(),
                'units'            => 4,
                'service_code'     => 'H2017',
                'place_of_service' => $admission?->default_shift_pos ?: '11',
                'source_type'      => 'individual',
                'billing_status'   => 'unbilled',
                'is_retroactive'   => false,
            ]),
            'admissions'      => Admission::with('patient')->orderByDesc('admission_date')->limit(200)->get(),
            'patients'        => Patient::where('active', true)->orderBy('last_name')->get(),
            'clinics'         => Clinic::where('active', true)->orderBy('name')->get(),
            'therapists'      => Employee::where('active', true)->where('is_provider', true)->orderBy('last_name')->get(),
            'authorizations'  => Authorization::with('admission.patient')->where('status', 'approved')->orderByDesc('approved_start_date')->limit(200)->get(),
            'sourceTypes'     => ServiceLog::SOURCE_TYPES,
            'billingStatuses' => ServiceLog::BILLING_STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = auth()->id();
        $log = ServiceLog::create($data);
        $log->authorization?->recalcUnitsUsed();
        return redirect()
            ->route('clinical.psr.service_log.index')
            ->with('status', 'Service log entry recorded.');
    }

    public function show(ServiceLog $serviceLog): View
    {
        $serviceLog->load(['patient', 'admission', 'therapist', 'authorization', 'progressNote', 'groupSession']);
        return view('clinical.psr.service-log.show', ['log' => $serviceLog]);
    }

    public function edit(ServiceLog $serviceLog): View
    {
        return view('clinical.psr.service-log.form', [
            'log'             => $serviceLog,
            'admissions'      => Admission::with('patient')->orderByDesc('admission_date')->limit(200)->get(),
            'patients'        => Patient::where('active', true)->orderBy('last_name')->get(),
            'clinics'         => Clinic::where('active', true)->orderBy('name')->get(),
            'therapists'      => Employee::where('active', true)->where('is_provider', true)->orderBy('last_name')->get(),
            'authorizations'  => Authorization::with('admission.patient')->where('status', 'approved')->orderByDesc('approved_start_date')->limit(200)->get(),
            'sourceTypes'     => ServiceLog::SOURCE_TYPES,
            'billingStatuses' => ServiceLog::BILLING_STATUSES,
        ]);
    }

    public function update(Request $request, ServiceLog $serviceLog): RedirectResponse
    {
        $previousAuthId = $serviceLog->psr_authorization_id;
        $serviceLog->update($this->validated($request));
        foreach (array_unique(array_filter([$previousAuthId, $serviceLog->psr_authorization_id])) as $authId) {
            Authorization::find($authId)?->recalcUnitsUsed();
        }
        return redirect()
            ->route('clinical.psr.service_log.show', $serviceLog)
            ->with('status', 'Service log entry updated.');
    }

    public function destroy(ServiceLog $serviceLog): RedirectResponse
    {
        $authId = $serviceLog->psr_authorization_id;
        $serviceLog->delete();
        Authorization::find($authId)?->recalcUnitsUsed();
        return redirect()
            ->route('clinical.psr.service_log.index')
            ->with('status', 'Service log entry removed.');
    }

    private function validated(Request $request): array
    {
        foreach (['psr_authorization_id', 'psr_progress_note_id', 'psr_group_session_id', 'auth_number',
                  'claim_number', 'billed_date', 'paid_date', 'paid_amount', 'denial_reason',
                  'note_status', 'start_time', 'end_time', 'modifier', 'diagnosis_code',
                  'diagnosis_description'] as $f) {
            if ($request->input($f) === '') $request->merge([$f => null]);
        }
        $data = $request->validate([
            'psr_admission_id'      => ['required', 'exists:psr_admissions,id'],
            'patient_id'            => ['required', 'exists:patients,id'],
            'clinic_id'             => ['required', 'exists:clinics,id'],
            'service_date'          => ['required', 'date'],
            'start_time'            => ['nullable', 'date_format:H:i'],
            'end_time'              => ['nullable', 'date_format:H:i', 'after_or_equal:start_time'],
            'units'                 => ['required', 'integer', 'min:0'],
            'service_code'          => ['required', 'string', 'max:20'],
            'modifier'              => ['nullable', 'string', 'max:20'],
            'place_of_service'      => ['nullable', 'string', 'max:10'],
            'diagnosis_code'        => ['nullable', 'string', 'max:20'],
            'diagnosis_description' => ['nullable', 'string', 'max:255'],
            'therapist_id'          => ['required', 'exists:employees,id'],
            'source_type'           => ['required', Rule::in(array_keys(ServiceLog::SOURCE_TYPES))],
            'psr_group_session_id'  => ['nullable', 'exists:psr_group_sessions,id'],
            'psr_progress_note_id'  => ['nullable', 'exists:psr_progress_notes,id'],
            'psr_authorization_id'  => ['nullable', 'exists:psr_authorizations,id'],
            'auth_number'           => ['nullable', 'string', 'max:50'],
            'billing_status'        => ['required', Rule::in(array_keys(ServiceLog::BILLING_STATUSES))],
            'claim_number'          => ['nullable', 'string', 'max:50'],
            'billed_date'           => ['nullable', 'date'],
            'paid_date'             => ['nullable', 'date'],
            'paid_amount'           => ['nullable', 'numeric', 'min:0'],
            'denial_reason'         => ['nullable', 'string'],
            'has_progress_note'     => ['sometimes', 'boolean'],
            'note_status'           => ['nullable', 'string', 'max:20'],
            'is_retroactive'        => ['sometimes', 'boolean'],
            'notes'                 => ['nullable', 'string'],
        ]);
        $data['has_progress_note'] = $request->boolean('has_progress_note');
        $data['is_retroactive']    = $request->boolean('is_retroactive');
        return $data;
    }
}
