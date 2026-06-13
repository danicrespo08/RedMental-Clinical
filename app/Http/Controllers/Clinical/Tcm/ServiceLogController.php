<?php

namespace App\Http\Controllers\Clinical\Tcm;

use App\Http\Controllers\Controller;
use App\Models\Hhrr\Employee;
use App\Models\Tcm\Admission;
use App\Models\Tcm\Authorization;
use App\Models\Tcm\ServiceLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceLogController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $month  = $request->query('month');
        $logs = ServiceLog::query()
            ->with(['admission.patient', 'caseManager'])
            ->when($status, fn ($q) => $q->where('billing_status', $status))
            ->when($month,  fn ($q) => $q->whereYear('service_date', substr($month, 0, 4))->whereMonth('service_date', substr($month, 5, 2)))
            ->orderByDesc('service_date')
            ->paginate(20)
            ->withQueryString();

        return view('clinical.tcm.service-log.index', [
            'logs'     => $logs,
            'statuses' => ServiceLog::BILLING_STATUSES,
            'status'   => $status,
            'month'    => $month,
        ]);
    }

    public function create(Request $request): View
    {
        $admission = $request->filled('admission_id')
            ? Admission::with('patient')->find($request->query('admission_id'))
            : null;

        return view('clinical.tcm.service-log.form', [
            'log'        => new ServiceLog([
                'tcm_admission_id' => $admission?->id,
                'patient_id'       => $admission?->patient_id,
                'case_manager_id'  => $admission?->case_manager_id,
                'service_date'     => now()->toDateString(),
                'cpt_code'         => 'T1017',
                'place_of_service' => '12',
                'units'            => 1,
                'billing_status'   => 'unbilled',
                'diagnosis_code'   => $admission?->diagnosis_code,
                'diagnosis_description' => $admission?->diagnosis_description,
            ]),
            'admissions'   => Admission::with('patient')->orderByDesc('admission_date')->limit(200)->get(),
            'caseManagers' => Employee::where('active', true)->orderBy('last_name')->get(),
            'statuses'     => ServiceLog::BILLING_STATUSES,
            'auths'        => $admission ? Authorization::where('tcm_admission_id', $admission->id)->orderByDesc('approved_end_date')->get() : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = auth()->id();
        $log = ServiceLog::create($data);
        $log->authorization?->recalcUnitsUsed();
        return redirect()->route('clinical.tcm.service_log.index')->with('status', 'Service-log entry saved.');
    }

    public function show(ServiceLog $serviceLog): View
    {
        $serviceLog->load(['admission.patient', 'caseManager', 'authorization', 'contact']);
        return view('clinical.tcm.service-log.show', ['log' => $serviceLog]);
    }

    public function edit(ServiceLog $serviceLog): View
    {
        return view('clinical.tcm.service-log.form', [
            'log'          => $serviceLog,
            'admissions'   => Admission::with('patient')->orderByDesc('admission_date')->limit(200)->get(),
            'caseManagers' => Employee::where('active', true)->orderBy('last_name')->get(),
            'statuses'     => ServiceLog::BILLING_STATUSES,
            'auths'        => Authorization::where('tcm_admission_id', $serviceLog->tcm_admission_id)->orderByDesc('approved_end_date')->get(),
        ]);
    }

    public function update(Request $request, ServiceLog $serviceLog): RedirectResponse
    {
        $previousAuthId = $serviceLog->tcm_authorization_id;
        $serviceLog->update($this->validated($request));
        foreach (array_unique(array_filter([$previousAuthId, $serviceLog->tcm_authorization_id])) as $authId) {
            Authorization::find($authId)?->recalcUnitsUsed();
        }
        return redirect()->route('clinical.tcm.service_log.show', $serviceLog)->with('status', 'Service-log entry updated.');
    }

    public function destroy(ServiceLog $serviceLog): RedirectResponse
    {
        $authId = $serviceLog->tcm_authorization_id;
        $serviceLog->delete();
        Authorization::find($authId)?->recalcUnitsUsed();
        return redirect()->route('clinical.tcm.service_log.index')->with('status', 'Entry deleted.');
    }

    private function validated(Request $request): array
    {
        foreach (['tcm_contact_id', 'tcm_authorization_id', 'auth_number', 'paid_amount', 'paid_date', 'billed_date', 'claim_number', 'denial_reason'] as $f) {
            if ($request->input($f) === '') $request->merge([$f => null]);
        }
        return $request->validate([
            'tcm_admission_id'    => ['required', 'exists:tcm_admissions,id'],
            'patient_id'          => ['required', 'exists:patients,id'],
            'tcm_contact_id'      => ['nullable', 'exists:tcm_contacts,id'],
            'tcm_authorization_id'=> ['nullable', 'exists:tcm_authorizations,id'],
            'auth_number'         => ['nullable', 'string', 'max:50'],
            'service_date'        => ['required', 'date'],
            'start_time'          => ['nullable', 'date_format:H:i'],
            'end_time'            => ['nullable', 'date_format:H:i', 'after:start_time'],
            'units'               => ['required', 'integer', 'min:0'],
            'cpt_code'            => ['required', 'string', 'max:20'],
            'modifier'            => ['nullable', 'string', 'max:20'],
            'place_of_service'    => ['nullable', 'string', 'max:10'],
            'diagnosis_code'      => ['nullable', 'string', 'max:20'],
            'diagnosis_description' => ['nullable', 'string', 'max:255'],
            'case_manager_id'     => ['required', 'exists:employees,id'],
            'billing_status'      => ['required', Rule::in(array_keys(ServiceLog::BILLING_STATUSES))],
            'claim_number'        => ['nullable', 'string', 'max:50'],
            'billed_date'         => ['nullable', 'date'],
            'paid_date'           => ['nullable', 'date'],
            'paid_amount'         => ['nullable', 'numeric', 'min:0'],
            'denial_reason'       => ['nullable', 'string'],
            'has_contact_note'    => ['sometimes', 'boolean'],
            'notes'               => ['nullable', 'string'],
        ]);
    }
}
