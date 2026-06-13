<?php

namespace App\Http\Controllers\Clinical\Tcm;

use App\Http\Controllers\Controller;
use App\Models\Hhrr\Employee;
use App\Models\Tcm\Admission;
use App\Models\Tcm\DischargeSummary;
use App\Models\Tcm\ServiceLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DischargeController extends Controller
{
    public function index(): View
    {
        $discharges = DischargeSummary::query()
            ->with(['patient', 'admission', 'caseManager'])
            ->orderByDesc('discharge_date')
            ->paginate(20);

        $pending = Admission::query()
            ->where('status', 'discharged')
            ->whereDoesntHave('dischargeSummary')
            ->with('patient')
            ->orderByDesc('discharge_date')
            ->get();

        return view('clinical.tcm.discharges.index', compact('discharges', 'pending'));
    }

    public function create(Request $request): View
    {
        $admission = Admission::with('patient', 'contacts', 'treatmentPlans.goals')->findOrFail($request->query('admission_id'));

        $totalContacts = $admission->contacts->count();
        $unitsBilled = ServiceLog::where('tcm_admission_id', $admission->id)
            ->whereIn('billing_status', ['submitted', 'paid'])->sum('units');

        return view('clinical.tcm.discharges.form', [
            'admission' => $admission,
            'discharge' => new DischargeSummary([
                'tcm_admission_id'        => $admission->id,
                'patient_id'              => $admission->patient_id,
                'discharge_date'          => now()->toDateString(),
                'admission_date'          => $admission->admission_date,
                'primary_dx_code'         => $admission->diagnosis_code,
                'primary_dx_description'  => $admission->diagnosis_description,
                'case_manager_id'         => $admission->case_manager_id,
                'total_contacts'          => $totalContacts,
                'total_units_billed'      => $unitsBilled,
                'days_in_program'         => (int) $admission->admission_date->diffInDays(now()),
                'goals_outcome' => $admission->treatmentPlans->flatMap->goals->map(fn ($g) => [
                    'goal_id'     => $g->id,
                    'goal_code'   => $g->goal_code,
                    'description' => $g->description,
                    'status'      => 'in_progress',
                    'notes'       => null,
                ])->all(),
                'status' => 'draft',
            ]),
            'caseManagers'    => Employee::where('active', true)->orderBy('last_name')->get(),
            'dischargeTypes'  => DischargeSummary::DISCHARGE_TYPES,
            'reasons'         => DischargeSummary::DISCHARGE_REASONS,
            'prognoses'       => DischargeSummary::PROGNOSES,
            'statuses'        => DischargeSummary::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = auth()->id();
        $discharge = DischargeSummary::create($data);
        $discharge->admission?->update([
            'status'         => 'discharged',
            'discharge_date' => $discharge->discharge_date,
        ]);
        return redirect()->route('clinical.tcm.discharges.show', $discharge)
            ->with('status', 'Discharge summary saved.');
    }

    public function show(DischargeSummary $discharge): View
    {
        $discharge->load(['patient', 'admission', 'caseManager']);
        return view('clinical.tcm.discharges.show', compact('discharge'));
    }

    public function edit(DischargeSummary $discharge): View
    {
        abort_if($discharge->is_signed, 403, 'Signed discharges cannot be edited.');
        return view('clinical.tcm.discharges.form', [
            'admission'      => $discharge->admission()->with('patient')->first(),
            'discharge'      => $discharge,
            'caseManagers'   => Employee::where('active', true)->orderBy('last_name')->get(),
            'dischargeTypes' => DischargeSummary::DISCHARGE_TYPES,
            'reasons'        => DischargeSummary::DISCHARGE_REASONS,
            'prognoses'      => DischargeSummary::PROGNOSES,
            'statuses'       => DischargeSummary::STATUSES,
        ]);
    }

    public function update(Request $request, DischargeSummary $discharge): RedirectResponse
    {
        abort_if($discharge->is_signed, 403, 'Signed discharges cannot be edited.');
        $discharge->update($this->validated($request));
        return redirect()->route('clinical.tcm.discharges.show', $discharge)
            ->with('status', 'Discharge summary updated.');
    }

    public function destroy(DischargeSummary $discharge): RedirectResponse
    {
        abort_if($discharge->is_signed, 403, 'Signed discharges cannot be deleted.');
        $admissionId = $discharge->tcm_admission_id;
        $discharge->delete();
        return redirect()->route('clinical.tcm.admissions.show', $admissionId)
            ->with('status', 'Discharge deleted.');
    }

    public function sign(DischargeSummary $discharge): RedirectResponse
    {
        $discharge->update([
            'is_signed'         => true,
            'signed_at'         => now(),
            'signed_by_user_id' => auth()->id(),
            'status'            => 'signed',
        ]);
        if ($discharge->admission) {
            $discharge->admission->update([
                'status'         => 'discharged',
                'discharge_date' => $discharge->discharge_date,
            ]);
        }
        return back()->with('status', 'Discharge signed; admission marked as discharged.');
    }

    private function validated(Request $request): array
    {
        foreach (['dx_at_discharge_code', 'dx_at_discharge_description', 'discharge_reason', 'aftercare_level', 'prognosis', 'case_manager_id'] as $f) {
            if ($request->input($f) === '') $request->merge([$f => null]);
        }
        return $request->validate([
            'tcm_admission_id' => ['required', 'exists:tcm_admissions,id'],
            'patient_id'       => ['required', 'exists:patients,id'],
            'discharge_date'   => ['required', 'date'],
            'discharge_type'   => ['required', Rule::in(array_keys(DischargeSummary::DISCHARGE_TYPES))],
            'discharge_reason' => ['nullable', Rule::in(array_keys(DischargeSummary::DISCHARGE_REASONS))],

            'admission_date'              => ['required', 'date'],
            'primary_dx_code'             => ['nullable', 'string', 'max:20'],
            'primary_dx_description'      => ['nullable', 'string', 'max:255'],
            'dx_at_discharge_code'        => ['nullable', 'string', 'max:20'],
            'dx_at_discharge_description' => ['nullable', 'string', 'max:255'],

            'presenting_problems'         => ['nullable', 'string'],
            'case_management_summary'     => ['nullable', 'string'],
            'coordination_outcomes'       => ['nullable', 'string'],
            'response_to_services'        => ['nullable', 'string'],
            'barriers_identified'         => ['nullable', 'string'],
            'risk_assessment_at_discharge'=> ['nullable', 'string'],

            'goals_outcome'      => ['nullable', 'array'],
            'total_contacts'     => ['required', 'integer', 'min:0'],
            'total_units_billed' => ['required', 'integer', 'min:0'],
            'days_in_program'    => ['required', 'integer', 'min:0'],

            'aftercare_plan'        => ['nullable', 'string'],
            'aftercare_level'       => ['nullable', 'string', 'max:60'],
            'aftercare_referrals'   => ['nullable', 'string'],
            'community_resources'   => ['nullable', 'string'],
            'crisis_plan'           => ['nullable', 'string'],
            'patient_instructions'  => ['nullable', 'string'],

            'case_manager_recommendation' => ['nullable', 'string'],
            'prognosis'                   => ['nullable', Rule::in(array_keys(DischargeSummary::PROGNOSES))],
            'case_manager_id'             => ['nullable', 'exists:employees,id'],

            'status' => ['required', Rule::in(array_keys(DischargeSummary::STATUSES))],
        ]);
    }
}
