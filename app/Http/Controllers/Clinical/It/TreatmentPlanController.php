<?php

namespace App\Http\Controllers\Clinical\It;

use App\Http\Controllers\Controller;
use App\Models\It\Admission;
use App\Models\It\Goal;
use App\Models\It\Objective;
use App\Models\It\TreatmentPlan;
use App\Services\AiClinicalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TreatmentPlanController extends Controller
{
    public function index(): View
    {
        $plans = TreatmentPlan::query()
            ->with(['admission.patient', 'goals.objectives'])
            ->orderByDesc('start_date')
            ->paginate(20);
        return view('clinical.it.treatment-plans.index', compact('plans'));
    }

    public function create(Request $request): View
    {
        $admission = Admission::with('patient')->findOrFail($request->query('admission_id'));
        return view('clinical.it.treatment-plans.form', [
            'admission' => $admission,
            'plan'      => new TreatmentPlan([
                'it_admission_id' => $admission->id,
                'start_date'      => now()->toDateString(),
                'end_date'        => now()->addMonths(6)->toDateString(),
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
        return redirect()->route('clinical.it.treatment_plans.show', $plan)
            ->with('status', 'Treatment plan created.');
    }

    public function show(TreatmentPlan $treatmentPlan): View
    {
        $treatmentPlan->load(['admission.patient', 'goals.objectives', 'signedByEmployee', 'signedByUser']);
        return view('clinical.it.treatment-plans.show', ['plan' => $treatmentPlan]);
    }

    public function edit(TreatmentPlan $treatmentPlan): View
    {
        $treatmentPlan->load(['admission.patient', 'goals.objectives']);
        return view('clinical.it.treatment-plans.form', [
            'admission' => $treatmentPlan->admission,
            'plan'      => $treatmentPlan,
            'goals'     => $treatmentPlan->goals,
        ]);
    }

    public function update(Request $request, TreatmentPlan $treatmentPlan): RedirectResponse
    {
        abort_if($treatmentPlan->is_signed, 403, 'Signed plans cannot be edited.');
        $data = $this->validated($request);
        DB::transaction(function () use ($treatmentPlan, $data, $request) {
            $treatmentPlan->update($data);
            $this->syncGoals($treatmentPlan, $request->input('goals', []));
        });
        return redirect()->route('clinical.it.treatment_plans.show', $treatmentPlan)
            ->with('status', 'Treatment plan updated.');
    }

    public function destroy(TreatmentPlan $treatmentPlan): RedirectResponse
    {
        abort_if($treatmentPlan->is_signed, 403, 'Signed plans cannot be deleted.');
        $admissionId = $treatmentPlan->it_admission_id;
        $treatmentPlan->delete();
        return redirect()->route('clinical.it.admissions.show', $admissionId)
            ->with('status', 'Treatment plan deleted.');
    }

    public function sign(TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $treatmentPlan->update([
            'is_signed'         => true,
            'signed_at'         => now(),
            'signed_by_user_id' => auth()->id(),
        ]);
        return back()->with('status', 'Treatment plan signed.');
    }

    public function aiSuggestGoals(Admission $admission): JsonResponse
    {
        $admission->load('patient');
        $context = "PATIENT: " . ($admission->patient?->first_name ?? '—') . "\n"
            . "DIAGNOSIS: " . ($admission->diagnosis_code ?? 'N/A')
            . " — " . ($admission->diagnosis_description ?? 'unspecified') . "\n";

        $prompt = <<<PROMPT
You are a clinical documentation specialist for an outpatient Individual Therapy (IT) program.
Generate 2-3 measurable treatment plan goals + objectives for this patient.

{$context}

INSTRUCTIONS:
- Goals should target IT-appropriate outcomes (symptom reduction, coping skills, insight).
- Each goal needs 2-3 SMART objectives.
- Reference the diagnosis where applicable.

Return ONLY valid JSON:
{
  "goals": [
    {"description": "...", "objectives": [{"description": "..."}]}
  ],
  "long_term_goal": "...",
  "discharge_criteria": "..."
}
PROMPT;

        $result = AiClinicalService::call($prompt, 1800, [
            'mock' => fn () => $this->mockGoalSuggestion($admission),
        ]);
        return response()->json($result);
    }

    private function mockGoalSuggestion(Admission $admission): array
    {
        $dx = strtolower($admission->diagnosis_description ?? 'mental health condition');
        return [
            'goals' => [
                [
                    'description' => "Patient will demonstrate reduction in {$dx} symptoms and improved daily functioning.",
                    'objectives'  => [
                        ['description' => 'Patient will identify three triggers and verbalize a coping strategy for each within 30 days.'],
                        ['description' => 'Patient will report a 30% reduction on self-reported symptom rating scale within 90 days.'],
                    ],
                ],
                [
                    'description' => 'Patient will develop and apply cognitive-behavioral coping skills.',
                    'objectives'  => [
                        ['description' => 'Patient will complete one cognitive-restructuring worksheet between weekly sessions for 8 consecutive weeks.'],
                        ['description' => 'Patient will demonstrate one grounding technique during session with appropriate prompting in 4 of 5 sessions.'],
                    ],
                ],
            ],
            'long_term_goal'    => "Patient will achieve sustained remission of {$dx} symptoms and resume baseline functioning.",
            'discharge_criteria'=> 'Symptom rating below clinical threshold for 60 consecutive days, demonstrated independent use of three coping skills, and consistent attendance ≥80%.',
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'it_admission_id'   => ['required', 'exists:it_admissions,id'],
            'start_date'        => ['required', 'date'],
            'end_date'          => ['required', 'date', 'after:start_date'],
            'presenting_problem'=> ['nullable', 'string'],
            'long_term_goal'    => ['nullable', 'string'],
            'discharge_criteria'=> ['nullable', 'string'],
            'interventions'     => ['nullable', 'string'],

            'goals'                            => ['array'],
            'goals.*.id'                       => ['nullable', 'integer'],
            'goals.*.goal_code'                => ['required', 'string', 'max:20'],
            'goals.*.description'              => ['required', 'string'],
            'goals.*.problem_statement'        => ['nullable', 'string'],
            'goals.*.start_date'               => ['required', 'date'],
            'goals.*.target_date'              => ['required', 'date'],
            'goals.*.is_active'                => ['sometimes', 'boolean'],
            'goals.*.objectives'               => ['array'],
            'goals.*.objectives.*.id'                       => ['nullable', 'integer'],
            'goals.*.objectives.*.objective_code'           => ['required', 'string', 'max:20'],
            'goals.*.objectives.*.description'              => ['required', 'string'],
            'goals.*.objectives.*.intervention_type'        => ['nullable', 'string', 'max:100'],
            'goals.*.objectives.*.intervention_description' => ['nullable', 'string'],
            'goals.*.objectives.*.start_date'               => ['required', 'date'],
            'goals.*.objectives.*.target_date'              => ['required', 'date'],
            'goals.*.objectives.*.is_active'                => ['sometimes', 'boolean'],
        ]);
    }

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
                ? tap(Goal::where('id', $g['id'])->where('it_treatment_plan_id', $plan->id)->firstOrFail())->update($payload)
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
                    ? tap(Objective::where('id', $o['id'])->where('it_goal_id', $goal->id)->firstOrFail())->update($oPayload)
                    : $goal->objectives()->create($oPayload);
                $keptObjIds[] = $obj->id;
            }
            $goal->objectives()->whereNotIn('id', $keptObjIds ?: [0])->delete();
        }
        $plan->goals()->whereNotIn('id', $keptGoalIds ?: [0])->delete();
    }
}
