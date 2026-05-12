<?php

namespace App\Http\Controllers\Clinical\Psr;

use App\Http\Controllers\Controller;
use App\Models\Psr\Admission;
use App\Models\Psr\Goal;
use App\Models\Psr\Objective;
use App\Models\Psr\TreatmentPlan;
use App\Services\AiClinicalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * PSR Treatment Plan controller.
 *
 * One Master Treatment Plan (MTP) per admission per period (start_date to
 * end_date). Each plan has nested Goals; each Goal has nested Objectives.
 * Strengths / weaknesses / services are stored as JSON arrays of checkbox
 * keys defined in TreatmentPlan::STRENGTHS / WEAKNESSES / SERVICES.
 */
class PsrTreatmentPlanController extends Controller
{
    public function index(Request $request): View
    {
        $plans = TreatmentPlan::query()
            ->whereHas('admission', fn ($q) => $q->whereNull('deleted_at'))
            ->with(['admission.patient', 'goals.objectives'])
            ->orderByDesc('start_date')
            ->paginate(20);

        return view('clinical.psr.treatment-plans.index', compact('plans'));
    }

    public function create(Request $request): View
    {
        $admission = Admission::with('patient', 'clinic')->findOrFail($request->query('admission_id'));

        return view('clinical.psr.treatment-plans.form', [
            'admission' => $admission,
            'plan'      => new TreatmentPlan([
                'psr_admission_id' => $admission->id,
                'start_date'       => now()->toDateString(),
                'end_date'         => now()->addMonths(6)->toDateString(),
                'strengths'        => [],
                'weaknesses'       => [],
                'services'         => [],
            ]),
            'goals'     => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $plan = DB::transaction(function () use ($data, $request) {
            $plan = TreatmentPlan::create($data);
            $this->syncGoals($plan, $request->input('goals', []));
            return $plan;
        });

        return redirect()
            ->route('clinical.psr.treatment_plans.show', $plan)
            ->with('status', 'Treatment plan created.');
    }

    public function show(TreatmentPlan $treatmentPlan): View
    {
        $treatmentPlan->load([
            'admission.patient', 'admission.clinic', 'admission.assignedTherapist',
            'goals.objectives', 'signedByEmployee', 'signedByUser',
        ]);
        return view('clinical.psr.treatment-plans.show', ['plan' => $treatmentPlan]);
    }

    public function edit(TreatmentPlan $treatmentPlan): View
    {
        $treatmentPlan->load(['admission.patient', 'admission.clinic', 'goals.objectives']);
        return view('clinical.psr.treatment-plans.form', [
            'admission' => $treatmentPlan->admission,
            'plan'      => $treatmentPlan,
            'goals'     => $treatmentPlan->goals,
        ]);
    }

    public function update(Request $request, TreatmentPlan $treatmentPlan): RedirectResponse
    {
        abort_if($treatmentPlan->is_signed, 403, 'Signed treatment plans cannot be edited.');

        $data = $this->validated($request);

        DB::transaction(function () use ($treatmentPlan, $data, $request) {
            $treatmentPlan->update($data);
            $this->syncGoals($treatmentPlan, $request->input('goals', []));
        });

        return redirect()
            ->route('clinical.psr.treatment_plans.show', $treatmentPlan)
            ->with('status', 'Treatment plan updated.');
    }

    public function destroy(TreatmentPlan $treatmentPlan): RedirectResponse
    {
        abort_if($treatmentPlan->is_signed, 403, 'Signed treatment plans cannot be deleted.');

        $admissionId = $treatmentPlan->psr_admission_id;
        $treatmentPlan->delete();

        return redirect()
            ->route('clinical.psr.admissions.show', $admissionId)
            ->with('status', 'Treatment plan deleted.');
    }

    public function sign(TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $user = auth()->user();
        $treatmentPlan->update([
            'is_signed'         => true,
            'signed_at'         => now(),
            'signed_by_user_id' => $user->id,
        ]);
        return back()->with('status', 'Treatment plan signed.');
    }

    /**
     * Generate AI-suggested goals + objectives for an admission's MTP.
     * Returns JSON: { goals: [{ description, objectives: [{ description }] }],
     *                 long_term_goal, discharge_criteria }
     */
    public function aiSuggestGoals(Admission $admission): JsonResponse
    {
        $admission->load('patient');
        $patient = $admission->patient;

        $context = "PATIENT FIRST NAME: {$patient->first_name}\n"
            . 'DIAGNOSIS: ' . ($admission->primary_dx_code ?? 'N/A')
            . ' — ' . ($admission->primary_dx_description ?? 'unspecified') . "\n";
        if ($admission->secondary_dx_code) {
            $context .= "Secondary: {$admission->secondary_dx_code} — {$admission->secondary_dx_description}\n";
        }

        $prompt = <<<PROMPT
You are a clinical documentation specialist for a Psychosocial Rehabilitation (PSR) program in Florida.
Generate measurable treatment plan goals and objectives based on the following patient information.

{$context}

INSTRUCTIONS:
- Create 2-3 PSR-appropriate treatment goals (functional rehabilitation, community integration, ADLs, coping skills, social skills).
- Each goal must have 2-3 measurable, time-limited objectives.
- Goals should reference the diagnosis and presenting problems.
- Use measurable language ("Patient will demonstrate...", "Patient will attend...").
- Follow Florida Medicaid PSR documentation standards.

Return ONLY valid JSON in the following structure:
{
  "goals": [
    {"description": "Goal 1 long-term description", "objectives": [{"description": "Objective 1.1"}, {"description": "Objective 1.2"}]},
    {"description": "Goal 2 long-term description", "objectives": [{"description": "Objective 2.1"}, {"description": "Objective 2.2"}]}
  ],
  "long_term_goal": "Broad recovery goal for the treatment period",
  "discharge_criteria": "Criteria indicating readiness for discharge (2-3 sentences)"
}
PROMPT;

        $result = AiClinicalService::call($prompt, 2000, [
            'mock' => fn () => $this->mockGoalSuggestion($admission),
        ]);

        return response()->json($result);
    }

    private function mockGoalSuggestion(Admission $admission): array
    {
        $dx = strtolower($admission->primary_dx_description ?? 'mental health condition');
        return [
            'goals' => [
                [
                    'description' => "Patient will demonstrate improved management of {$dx} symptoms and develop functional coping skills.",
                    'objectives'  => [
                        ['description' => 'Patient will identify three triggers and verbalize a coping strategy for each within 30 days.'],
                        ['description' => 'Patient will attend 80% of scheduled PSR group sessions over a 60-day period.'],
                        ['description' => 'Patient will complete a daily mood log for 4 of 5 weekdays for 2 consecutive weeks.'],
                    ],
                ],
                [
                    'description' => 'Patient will improve interpersonal and community-integration skills.',
                    'objectives'  => [
                        ['description' => 'Patient will initiate two reciprocal social interactions per group session for 4 consecutive weeks.'],
                        ['description' => 'Patient will independently complete one community-based ADL task (banking, shopping) per week for 30 days.'],
                    ],
                ],
                [
                    'description' => 'Patient will adhere to medication and treatment regimen as prescribed.',
                    'objectives'  => [
                        ['description' => 'Patient will report medication adherence ≥90% verified by pill count for 60 days.'],
                        ['description' => 'Patient will attend 100% of scheduled medication-management appointments for 90 days.'],
                    ],
                ],
            ],
            'long_term_goal' => "Patient will achieve sustained remission of {$dx} symptoms and resume baseline social and occupational functioning.",
            'discharge_criteria' => 'Symptom rating below clinical threshold for 60 consecutive days, consistent attendance ≥80%, demonstrated independent use of three coping skills, and stable medication adherence.',
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'psr_admission_id'   => ['required', 'exists:psr_admissions,id'],
            'start_date'         => ['required', 'date'],
            'end_date'           => ['required', 'date', 'after:start_date'],
            'strengths'          => ['array'],
            'strengths.*'        => ['string'],
            'weaknesses'         => ['array'],
            'weaknesses.*'       => ['string'],
            'services'           => ['array'],
            'services.*'         => ['string'],
            'strengths_other'    => ['nullable', 'string'],
            'weaknesses_other'   => ['nullable', 'string'],
            'long_term_goal'     => ['nullable', 'string'],
            'discharge_criteria' => ['nullable', 'string'],

            'goals'                            => ['array'],
            'goals.*.id'                       => ['nullable', 'integer'],
            'goals.*.goal_code'                => ['required', 'string', 'max:20'],
            'goals.*.description'              => ['required', 'string'],
            'goals.*.problem_statement'        => ['nullable', 'string'],
            'goals.*.start_date'               => ['required', 'date'],
            'goals.*.target_date'              => ['required', 'date'],
            'goals.*.is_active'                => ['sometimes', 'boolean'],
            'goals.*.objectives'               => ['array'],
            'goals.*.objectives.*.id'          => ['nullable', 'integer'],
            'goals.*.objectives.*.objective_code'           => ['required', 'string', 'max:20'],
            'goals.*.objectives.*.description'              => ['required', 'string'],
            'goals.*.objectives.*.intervention_type'        => ['nullable', 'string', 'max:100'],
            'goals.*.objectives.*.intervention_description' => ['nullable', 'string'],
            'goals.*.objectives.*.start_date'               => ['required', 'date'],
            'goals.*.objectives.*.target_date'              => ['required', 'date'],
            'goals.*.objectives.*.is_active'                => ['sometimes', 'boolean'],
        ]);

        $data['strengths']  = array_values(array_filter($data['strengths'] ?? []));
        $data['weaknesses'] = array_values(array_filter($data['weaknesses'] ?? []));
        $data['services']   = array_values(array_filter($data['services'] ?? []));

        return $data;
    }

    /** Sync goals + objectives diff: keep existing IDs, replace what's missing, add new. */
    private function syncGoals(TreatmentPlan $plan, array $goalsInput): void
    {
        $keptGoalIds = [];
        foreach ($goalsInput as $g) {
            $payload = [
                'goal_code'         => $g['goal_code'],
                'description'       => $g['description'],
                'problem_statement' => $g['problem_statement'] ?? null,
                'start_date'        => $g['start_date'],
                'target_date'       => $g['target_date'],
                'is_active'         => isset($g['is_active']) ? (bool) $g['is_active'] : true,
            ];
            $goal = !empty($g['id'])
                ? tap(Goal::where('id', $g['id'])->where('psr_treatment_plan_id', $plan->id)->firstOrFail())->update($payload)
                : $plan->goals()->create($payload);
            $keptGoalIds[] = $goal->id;

            $keptObjIds = [];
            foreach ($g['objectives'] ?? [] as $o) {
                $oPayload = [
                    'objective_code'           => $o['objective_code'],
                    'description'              => $o['description'],
                    'intervention_type'        => $o['intervention_type'] ?? null,
                    'intervention_description' => $o['intervention_description'] ?? null,
                    'start_date'               => $o['start_date'],
                    'target_date'              => $o['target_date'],
                    'is_active'                => isset($o['is_active']) ? (bool) $o['is_active'] : true,
                ];
                $obj = !empty($o['id'])
                    ? tap(Objective::where('id', $o['id'])->where('psr_goal_id', $goal->id)->firstOrFail())->update($oPayload)
                    : $goal->objectives()->create($oPayload);
                $keptObjIds[] = $obj->id;
            }
            $goal->objectives()->whereNotIn('id', $keptObjIds ?: [0])->delete();
        }
        $plan->goals()->whereNotIn('id', $keptGoalIds ?: [0])->delete();
    }
}
