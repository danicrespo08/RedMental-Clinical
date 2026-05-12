<?php

namespace App\Http\Controllers\Clinical\It;

use App\Http\Controllers\Controller;
use App\Models\Hhrr\Employee;
use App\Models\It\Admission;
use App\Models\It\Authorization;
use App\Models\It\ServiceLog;
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
            ->with(['admission.patient', 'therapist'])
            ->when($status, fn ($q) => $q->where('billing_status', $status))
            ->when($month,  fn ($q) => $q->whereYear('service_date', substr($month, 0, 4))->whereMonth('service_date', substr($month, 5, 2)))
            ->orderByDesc('service_date')
            ->paginate(20)
            ->withQueryString();

        return view('clinical.it.service-log.index', [
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

        return view('clinical.it.service-log.form', [
            'log'        => new ServiceLog([
                'it_admission_id' => $admission?->id,
                'patient_id'      => $admission?->patient_id,
                'therapist_id'    => $admission?->therapist_id,
                'service_date'    => now()->toDateString(),
                'cpt_code'        => '90834',
                'place_of_service'=> '11',
                'units'           => 1,
                'billing_status'  => 'unbilled',
                'diagnosis_code'  => $admission?->diagnosis_code,
                'diagnosis_description' => $admission?->diagnosis_description,
            ]),
            'admissions' => Admission::with('patient')->orderByDesc('admission_date')->limit(200)->get(),
            'therapists' => Employee::where('active', true)->where('is_provider', true)->orderBy('last_name')->get(),
            'statuses'   => ServiceLog::BILLING_STATUSES,
            'auths'      => $admission ? Authorization::where('it_admission_id', $admission->id)->orderByDesc('approved_end_date')->get() : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = auth()->id();
        ServiceLog::create($data);
        return redirect()->route('clinical.it.service_log.index')->with('status', 'Service-log entry saved.');
    }

    public function show(ServiceLog $serviceLog): View
    {
        $serviceLog->load(['admission.patient', 'therapist', 'authorization', 'session']);
        return view('clinical.it.service-log.show', ['log' => $serviceLog]);
    }

    public function edit(ServiceLog $serviceLog): View
    {
        return view('clinical.it.service-log.form', [
            'log'        => $serviceLog,
            'admissions' => Admission::with('patient')->orderByDesc('admission_date')->limit(200)->get(),
            'therapists' => Employee::where('active', true)->where('is_provider', true)->orderBy('last_name')->get(),
            'statuses'   => ServiceLog::BILLING_STATUSES,
            'auths'      => Authorization::where('it_admission_id', $serviceLog->it_admission_id)->orderByDesc('approved_end_date')->get(),
        ]);
    }

    public function update(Request $request, ServiceLog $serviceLog): RedirectResponse
    {
        $serviceLog->update($this->validated($request));
        return redirect()->route('clinical.it.service_log.show', $serviceLog)->with('status', 'Service-log entry updated.');
    }

    public function destroy(ServiceLog $serviceLog): RedirectResponse
    {
        $serviceLog->delete();
        return redirect()->route('clinical.it.service_log.index')->with('status', 'Entry deleted.');
    }

    private function validated(Request $request): array
    {
        foreach (['it_session_id', 'it_authorization_id', 'auth_number', 'paid_amount', 'paid_date', 'billed_date', 'claim_number', 'denial_reason'] as $f) {
            if ($request->input($f) === '') $request->merge([$f => null]);
        }
        return $request->validate([
            'it_admission_id'    => ['required', 'exists:it_admissions,id'],
            'patient_id'         => ['required', 'exists:patients,id'],
            'it_session_id'      => ['nullable', 'exists:it_sessions,id'],
            'it_authorization_id'=> ['nullable', 'exists:it_authorizations,id'],
            'auth_number'        => ['nullable', 'string', 'max:50'],
            'service_date'       => ['required', 'date'],
            'start_time'         => ['nullable', 'date_format:H:i'],
            'end_time'           => ['nullable', 'date_format:H:i', 'after:start_time'],
            'units'              => ['required', 'integer', 'min:0'],
            'cpt_code'           => ['required', 'string', 'max:20'],
            'modifier'           => ['nullable', 'string', 'max:20'],
            'place_of_service'   => ['nullable', 'string', 'max:10'],
            'diagnosis_code'     => ['nullable', 'string', 'max:20'],
            'diagnosis_description' => ['nullable', 'string', 'max:255'],
            'therapist_id'       => ['required', 'exists:employees,id'],
            'billing_status'     => ['required', Rule::in(array_keys(ServiceLog::BILLING_STATUSES))],
            'claim_number'       => ['nullable', 'string', 'max:50'],
            'billed_date'        => ['nullable', 'date'],
            'paid_date'          => ['nullable', 'date'],
            'paid_amount'        => ['nullable', 'numeric', 'min:0'],
            'denial_reason'      => ['nullable', 'string'],
            'has_progress_note'  => ['sometimes', 'boolean'],
            'notes'              => ['nullable', 'string'],
        ]);
    }
}
