<?php

namespace App\Http\Controllers\Clinical\It;

use App\Http\Controllers\Controller;
use App\Models\Hhrr\Employee;
use App\Models\It\Admission;
use App\Models\It\DischargeSummary;
use App\Models\It\ServiceLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DischargeController extends Controller
{
    public function index(): View
    {
        $discharges = DischargeSummary::query()
            ->with(['patient', 'admission', 'therapist'])
            ->orderByDesc('discharge_date')
            ->paginate(20);
        return view('clinical.it.discharges.index', compact('discharges'));
    }

    public function create(Request $request): View
    {
        $admission = Admission::with('patient', 'sessions', 'treatmentPlans.goals')->findOrFail($request->query('admission_id'));

        $sessionsAttended = $admission->sessions->count();
        $unitsBilled = ServiceLog::where('it_admission_id', $admission->id)
            ->whereIn('billing_status', ['submitted', 'paid'])->sum('units');

        return view('clinical.it.discharges.form', [
            'admission' => $admission,
            'discharge' => new DischargeSummary([
                'it_admission_id'        => $admission->id,
                'patient_id'             => $admission->patient_id,
                'discharge_date'         => now()->toDateString(),
                'admission_date'         => $admission->admission_date,
                'primary_dx_code'        => $admission->diagnosis_code,
                'primary_dx_description' => $admission->diagnosis_description,
                'therapist_id'           => $admission->therapist_id,
                'total_sessions_attended'=> $sessionsAttended,
                'total_sessions_absent'  => 0,
                'total_units_billed'     => $unitsBilled,
                'days_in_program'        => (int) $admission->admission_date->diffInDays(now()),
                'goals_outcome' => $admission->treatmentPlans->flatMap->goals->map(fn ($g) => [
                    'goal_id'     => $g->id,
                    'goal_code'   => $g->goal_code,
                    'description' => $g->description,
                    'status'      => 'in_progress',
                    'notes'       => null,
                ])->all(),
                'status' => 'draft',
            ]),
            'therapists'      => Employee::where('active', true)->where('is_provider', true)->orderBy('last_name')->get(),
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
        return redirect()->route('clinical.it.discharges.show', $discharge)
            ->with('status', 'Discharge summary saved.');
    }

    public function show(DischargeSummary $discharge): View
    {
        $discharge->load(['patient', 'admission', 'therapist']);
        return view('clinical.it.discharges.show', compact('discharge'));
    }

    public function edit(DischargeSummary $discharge): View
    {
        abort_if($discharge->is_signed, 403, 'Signed discharges cannot be edited.');
        return view('clinical.it.discharges.form', [
            'admission'      => $discharge->admission()->with('patient')->first(),
            'discharge'      => $discharge,
            'therapists'     => Employee::where('active', true)->where('is_provider', true)->orderBy('last_name')->get(),
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
        return redirect()->route('clinical.it.discharges.show', $discharge)
            ->with('status', 'Discharge summary updated.');
    }

    public function destroy(DischargeSummary $discharge): RedirectResponse
    {
        abort_if($discharge->is_signed, 403, 'Signed discharges cannot be deleted.');
        $admissionId = $discharge->it_admission_id;
        $discharge->delete();
        return redirect()->route('clinical.it.admissions.show', $admissionId)
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
        foreach (['dx_at_discharge_code', 'dx_at_discharge_description', 'discharge_reason', 'aftercare_level', 'prognosis', 'therapist_id'] as $f) {
            if ($request->input($f) === '') $request->merge([$f => null]);
        }
        return $request->validate([
            'it_admission_id' => ['required', 'exists:it_admissions,id'],
            'patient_id'      => ['required', 'exists:patients,id'],
            'discharge_date'  => ['required', 'date'],
            'discharge_type'  => ['required', Rule::in(array_keys(DischargeSummary::DISCHARGE_TYPES))],
            'discharge_reason'=> ['nullable', Rule::in(array_keys(DischargeSummary::DISCHARGE_REASONS))],

            'admission_date'              => ['required', 'date'],
            'primary_dx_code'             => ['nullable', 'string', 'max:20'],
            'primary_dx_description'      => ['nullable', 'string', 'max:255'],
            'dx_at_discharge_code'        => ['nullable', 'string', 'max:20'],
            'dx_at_discharge_description' => ['nullable', 'string', 'max:255'],

            'presenting_problems'         => ['nullable', 'string'],
            'treatment_summary'           => ['nullable', 'string'],
            'clinical_course'             => ['nullable', 'string'],
            'response_to_treatment'       => ['nullable', 'string'],
            'medications_at_discharge'    => ['nullable', 'string'],
            'risk_assessment_at_discharge'=> ['nullable', 'string'],

            'goals_outcome'           => ['nullable', 'array'],
            'total_sessions_attended' => ['required', 'integer', 'min:0'],
            'total_sessions_absent'   => ['required', 'integer', 'min:0'],
            'total_units_billed'      => ['required', 'integer', 'min:0'],
            'days_in_program'         => ['required', 'integer', 'min:0'],

            'aftercare_plan'        => ['nullable', 'string'],
            'aftercare_level'       => ['nullable', 'string', 'max:60'],
            'aftercare_referrals'   => ['nullable', 'string'],
            'follow_up_appointments'=> ['nullable', 'string'],
            'crisis_plan'           => ['nullable', 'string'],
            'patient_instructions'  => ['nullable', 'string'],

            'therapist_recommendation' => ['nullable', 'string'],
            'prognosis'                => ['nullable', Rule::in(array_keys(DischargeSummary::PROGNOSES))],
            'therapist_id'             => ['nullable', 'exists:employees,id'],

            'status'                   => ['required', Rule::in(array_keys(DischargeSummary::STATUSES))],
        ]);
    }
}
