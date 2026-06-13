<?php

namespace App\Http\Controllers\Clinical\Tcm;

use App\Http\Controllers\Controller;
use App\Models\Hhrr\Payer;
use App\Models\Tcm\Admission;
use App\Models\Tcm\Authorization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthorizationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $auths = Authorization::query()
            ->with(['admission.patient', 'payer'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('approved_end_date')
            ->paginate(20)
            ->withQueryString();
        return view('clinical.tcm.authorizations.index', [
            'auths'    => $auths,
            'statuses' => Authorization::STATUSES,
            'status'   => $status,
        ]);
    }

    public function create(Request $request): View
    {
        $admission = $request->filled('admission_id')
            ? Admission::with('patient')->find($request->query('admission_id'))
            : null;

        return view('clinical.tcm.authorizations.form', [
            'auth'       => new Authorization([
                'tcm_admission_id' => $admission?->id,
                'patient_id'       => $admission?->patient_id,
                'auth_type'        => 'initial',
                'status'           => 'pending',
                'cpt_codes'        => ['T1017'],
            ]),
            'admissions' => Admission::with('patient')->orderByDesc('admission_date')->limit(200)->get(),
            'payers'     => Payer::orderBy('name')->get(),
            'types'      => Authorization::TYPES,
            'statuses'   => Authorization::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = auth()->id();
        $auth = Authorization::create($data);
        return redirect()->route('clinical.tcm.authorizations.show', $auth)
            ->with('status', 'Authorization saved.');
    }

    public function show(Authorization $authorization): View
    {
        $authorization->load(['admission.patient', 'payer']);
        return view('clinical.tcm.authorizations.show', ['auth' => $authorization]);
    }

    public function edit(Authorization $authorization): View
    {
        return view('clinical.tcm.authorizations.form', [
            'auth'       => $authorization,
            'admissions' => Admission::with('patient')->orderByDesc('admission_date')->limit(200)->get(),
            'payers'     => Payer::orderBy('name')->get(),
            'types'      => Authorization::TYPES,
            'statuses'   => Authorization::STATUSES,
        ]);
    }

    public function update(Request $request, Authorization $authorization): RedirectResponse
    {
        $authorization->update($this->validated($request));
        return redirect()->route('clinical.tcm.authorizations.show', $authorization)
            ->with('status', 'Authorization updated.');
    }

    public function destroy(Authorization $authorization): RedirectResponse
    {
        $authorization->delete();
        return redirect()->route('clinical.tcm.authorizations.index')
            ->with('status', 'Authorization deleted.');
    }

    private function validated(Request $request): array
    {
        $cptInput = $request->input('cpt_codes', '');
        if (is_string($cptInput)) {
            $request->merge(['cpt_codes' => array_values(array_filter(array_map('trim', explode(',', $cptInput))))]);
        }

        return $request->validate([
            'tcm_admission_id'     => ['required', 'exists:tcm_admissions,id'],
            'patient_id'           => ['required', 'exists:patients,id'],
            'payer_id'             => ['nullable', 'exists:payers,id'],
            'auth_number'          => ['required', 'string', 'max:50'],
            'auth_type'            => ['required', Rule::in(array_keys(Authorization::TYPES))],
            'status'               => ['required', Rule::in(array_keys(Authorization::STATUSES))],
            'requested_start_date' => ['nullable', 'date'],
            'requested_end_date'   => ['nullable', 'date', 'after_or_equal:requested_start_date'],
            'approved_start_date'  => ['nullable', 'date'],
            'approved_end_date'    => ['nullable', 'date', 'after_or_equal:approved_start_date'],
            'approved_units'       => ['required', 'integer', 'min:0'],
            'cpt_codes'            => ['nullable', 'array'],
            'cpt_codes.*'          => ['string', 'max:20'],
            'denial_reason'        => ['nullable', 'string'],
            'notes'                => ['nullable', 'string'],
        ]);
    }
}
