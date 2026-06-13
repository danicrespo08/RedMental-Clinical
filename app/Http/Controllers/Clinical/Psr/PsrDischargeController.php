<?php

namespace App\Http\Controllers\Clinical\Psr;

use App\Http\Controllers\Controller;
use App\Models\Hhrr\Employee;
use App\Models\Psr\Admission;
use App\Models\Psr\DischargeSummary;
use App\Models\Psr\ServiceLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * PSR Discharge Summary controller.
 *
 * Captures the end-of-episode summary including outcomes, FARS comparison
 * (admission vs discharge), aftercare plan, and clinical recommendation.
 * Setting status="signed" also flips the underlying admission to "discharged".
 */
class PsrDischargeController extends Controller
{
    public function index(): View
    {
        $discharges = DischargeSummary::query()
            ->with(['patient', 'admission', 'therapist'])
            ->orderByDesc('discharge_date')
            ->paginate(20);

        // Discharged admissions that still have no discharge summary on file.
        $pending = Admission::query()
            ->where('status', 'discharged')
            ->whereDoesntHave('dischargeSummary')
            ->with('patient')
            ->orderByDesc('discharge_date')
            ->get();

        return view('clinical.psr.discharges.index', compact('discharges', 'pending'));
    }

    public function create(Request $request): View
    {
        $admission = Admission::with(['patient', 'farsAssessments', 'treatmentPlans.goals'])
            ->findOrFail($request->query('admission_id'));

        $admissionFars = $admission->farsAssessments->firstWhere('evaluation_type', 'admission');
        $latestFars    = $admission->farsAssessments->sortByDesc('evaluation_date')->first();

        $sessionsAttended = $admission->groupSessionAttendances()
            ->where('attendance_status', 'present')->count();
        $sessionsAbsent   = $admission->groupSessionAttendances()
            ->where('attendance_status', 'absent')->count();
        $unitsBilled = ServiceLog::where('psr_admission_id', $admission->id)
            ->whereIn('billing_status', ['submitted', 'paid'])->sum('units');

        return view('clinical.psr.discharges.form', [
            'admission' => $admission,
            'discharge' => new DischargeSummary([
                'psr_admission_id'  => $admission->id,
                'patient_id'        => $admission->patient_id,
                'clinic_id'         => $admission->clinic_id,
                'discharge_date'    => now()->toDateString(),
                'admission_date'    => $admission->admission_date,
                'primary_dx_code'   => $admission->primary_dx_code,
                'primary_dx_description'   => $admission->primary_dx_description,
                'secondary_dx_code'        => $admission->secondary_dx_code,
                'secondary_dx_description' => $admission->secondary_dx_description,
                'therapist_id'      => $admission->assigned_therapist_id,
                'total_sessions_attended' => $sessionsAttended,
                'total_sessions_absent'   => $sessionsAbsent,
                'total_units_billed'      => $unitsBilled,
                'days_in_program'         => $admission->days_in_program,
                'fars_comparison'   => [
                    'admission' => $admissionFars?->only(\App\Models\Psr\Fars::DOMAINS),
                    'discharge' => $latestFars && $latestFars->id !== $admissionFars?->id
                        ? $latestFars->only(\App\Models\Psr\Fars::DOMAINS) : null,
                ],
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
        // Closing the chart: move the admission to discharged.
        $discharge->admission?->update([
            'status'         => 'discharged',
            'discharge_date' => $discharge->discharge_date,
        ]);

        return redirect()
            ->route('clinical.psr.discharges.show', $discharge)
            ->with('status', 'Discharge summary saved.');
    }

    public function show(DischargeSummary $discharge): View
    {
        $discharge->load(['patient', 'admission.clinic', 'therapist', 'coSigner']);
        return view('clinical.psr.discharges.show', compact('discharge'));
    }

    public function edit(DischargeSummary $discharge): View
    {
        abort_if($discharge->is_signed, 403, 'Signed discharges cannot be edited.');

        return view('clinical.psr.discharges.form', [
            'admission'  => $discharge->admission()->with('patient')->first(),
            'discharge'  => $discharge,
            'therapists' => Employee::where('active', true)->where('is_provider', true)->orderBy('last_name')->get(),
            'dischargeTypes' => DischargeSummary::DISCHARGE_TYPES,
            'reasons'    => DischargeSummary::DISCHARGE_REASONS,
            'prognoses'  => DischargeSummary::PROGNOSES,
            'statuses'   => DischargeSummary::STATUSES,
        ]);
    }

    public function update(Request $request, DischargeSummary $discharge): RedirectResponse
    {
        abort_if($discharge->is_signed, 403, 'Signed discharges cannot be edited.');
        $discharge->update($this->validated($request));
        return redirect()
            ->route('clinical.psr.discharges.show', $discharge)
            ->with('status', 'Discharge summary updated.');
    }

    public function destroy(DischargeSummary $discharge): RedirectResponse
    {
        abort_if($discharge->is_signed, 403, 'Signed discharges cannot be deleted.');
        $admissionId = $discharge->psr_admission_id;
        $discharge->delete();
        return redirect()
            ->route('clinical.psr.admissions.show', $admissionId)
            ->with('status', 'Discharge summary deleted.');
    }

    /** Sign-off: locks discharge and moves admission to "discharged". */
    public function sign(DischargeSummary $discharge): RedirectResponse
    {
        $user = auth()->user();
        $discharge->update([
            'is_signed'         => true,
            'signed_at'         => now(),
            'signed_by_user_id' => $user->id,
            'status'            => 'signed',
        ]);
        if ($discharge->admission) {
            $discharge->admission->update([
                'status'         => 'discharged',
                'discharge_date' => $discharge->discharge_date,
            ]);
        }
        return back()->with('status', 'Discharge signed. Admission moved to "discharged".');
    }

    private function validated(Request $request): array
    {
        foreach (['secondary_dx_code', 'secondary_dx_description', 'dx_at_discharge_code',
                  'dx_at_discharge_description', 'discharge_reason', 'aftercare_level',
                  'prognosis', 'therapist_id'] as $f) {
            if ($request->input($f) === '') $request->merge([$f => null]);
        }

        return $request->validate([
            'psr_admission_id' => ['required', 'exists:psr_admissions,id'],
            'patient_id'       => ['required', 'exists:patients,id'],
            'clinic_id'        => ['required', 'exists:clinics,id'],
            'discharge_date'   => ['required', 'date'],
            'discharge_type'   => ['required', Rule::in(array_keys(DischargeSummary::DISCHARGE_TYPES))],
            'discharge_reason' => ['nullable', Rule::in(array_keys(DischargeSummary::DISCHARGE_REASONS))],

            'admission_date'        => ['required', 'date'],
            'primary_dx_code'       => ['nullable', 'string', 'max:20'],
            'primary_dx_description'=> ['nullable', 'string', 'max:255'],
            'secondary_dx_code'     => ['nullable', 'string', 'max:20'],
            'secondary_dx_description' => ['nullable', 'string', 'max:255'],
            'dx_at_discharge_code'  => ['nullable', 'string', 'max:20'],
            'dx_at_discharge_description' => ['nullable', 'string', 'max:255'],

            'presenting_problems'         => ['nullable', 'string'],
            'treatment_summary'           => ['nullable', 'string'],
            'clinical_course'             => ['nullable', 'string'],
            'response_to_treatment'       => ['nullable', 'string'],
            'medications_at_discharge'    => ['nullable', 'string'],
            'risk_assessment_at_discharge'=> ['nullable', 'string'],

            'goals_outcome'   => ['nullable', 'array'],
            'fars_comparison' => ['nullable', 'array'],
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
