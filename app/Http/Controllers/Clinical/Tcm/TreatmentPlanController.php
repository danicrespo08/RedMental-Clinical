<?php

namespace App\Http\Controllers\Clinical\Tcm;

use App\Http\Controllers\Controller;
use App\Models\Tcm\Admission;
use App\Models\Tcm\Goal;
use App\Models\Tcm\Objective;
use App\Models\Tcm\TreatmentPlan;
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
        return view('clinical.tcm.treatment-plans.index', compact('plans'));
    }

    public function create(Request $request): View
    {
        $admission = Admission::with('patient')->findOrFail($request->query('admission_id'));
        return view('clinical.tcm.treatment-plans.form', [
            'admission' => $admission,
            'plan'      => new TreatmentPlan([
                'tcm_admission_id' => $admission->id,
                'start_date'       => now()->toDateString(),
                'end_date'         => now()->addMonths(6)->toDateString(),
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
        return redirect()->route('clinical.tcm.treatment_plans.show', $plan)
            ->with('status', 'Service plan created.');
    }

    public function show(TreatmentPlan $treatmentPlan): View
    {
        $treatmentPlan->load(['admission.patient', 'goals.objectives', 'signedByEmployee', 'signedByUser']);
        return view('clinical.tcm.treatment-plans.show', ['plan' => $treatmentPlan]);
    }

    public function edit(TreatmentPlan $treatmentPlan): View
    {
        $treatmentPlan->load(['admission.patient', 'goals.objectives']);
        return view('clinical.tcm.treatment-plans.form', [
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
        return redirect()->route('clinical.tcm.treatment_plans.show', $treatmentPlan)
            ->with('status', 'Service plan updated.');
    }

    public function destroy(TreatmentPlan $treatmentPlan): RedirectResponse
    {
        abort_if($treatmentPlan->is_signed, 403, 'Signed plans cannot be deleted.');
        $admissionId = $treatmentPlan->tcm_admission_id;
        $treatmentPlan->delete();
        return redirect()->route('clinical.tcm.admissions.show', $admissionId)
            ->with('status', 'Service plan deleted.');
    }

    public function sign(TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $treatmentPlan->update([
            'is_signed'         => true,
            'signed_at'         => now(),
            'signed_by_user_id' => auth()->id(),
        ]);
        return back()->with('status', 'Service plan signed.');
    }

    public function aiSuggestGoals(Admission $admission): JsonResponse
    {
        $admission->load('patient');
        $context = "PATIENT: " . ($admission->patient?->first_name ?? '—') . "\n"
            . "DIAGNOSIS: " . ($admission->diagnosis_code ?? 'N/A')
            . " — " . ($admission->diagnosis_description ?? 'unspecified') . "\n";

        $prompt = <<<PROMPT
You are a clinical documentation specialist for a Targeted Case Management (TCM) program.
Generate 2-3 measurable case-management service-plan goals + objectives for this patient.

{$context}

INSTRUCTIONS:
- Goals should target TCM-appropriate outcomes (care coordination, community-resource access, treatment adherence, advocacy).
- Each goal needs 2-3 SMART objectives describing concrete coordination tasks.
- Reference the diagnosis where applicable.

Return ONLY valid JSON:
{
  "goals": [{"description": "...", "objectives": [{"description": "..."}]}],
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
                    'description' => "Patient will access and engage consistently with community-based services to support {$dx} management.",
                    'objectives'  => [
                        ['description' => 'Patient will attend 90% of scheduled medical and behavioral-health appointments over the next 90 days.'],
                        ['description' => 'Case manager will coordinate three referrals (housing, primary care, behavioral health) within 30 days.'],
                    ],
                ],
                [
                    'description' => 'Patient will demonstrate independence with treatment-adherence and self-advocacy skills.',
                    'objectives'  => [
                        ['description' => 'Patient will independently call to schedule appointments at least once per month for 3 consecutive months.'],
                        ['description' => 'Patient will identify and verbalize three community resources during case-management contacts.'],
                    ],
                ],
            ],
            'long_term_goal'    => "Patient will achieve stable community functioning with {$dx} effectively managed through coordinated services.",
            'discharge_criteria'=> 'Stable engagement with all natural supports, demonstrated treatment adherence ≥90% for 60 consecutive days, no crisis contacts in past 30 days.',
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'tcm_admission_id'      => ['required', 'exists:tcm_admissions,id'],
            'start_date'            => ['required', 'date'],
            'end_date'              => ['required', 'date', 'after:start_date'],
            'presenting_problem'    => ['nullable', 'string'],
            'long_term_goal'        => ['nullable', 'string'],
            'discharge_criteria'    => ['nullable', 'string'],
            'coordination_strategy' => ['nullable', 'string'],

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
                ? tap(Goal::where('id', $g['id'])->where('tcm_treatment_plan_id', $plan->id)->firstOrFail())->update($payload)
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
                    ? tap(Objective::where('id', $o['id'])->where('tcm_goal_id', $goal->id)->firstOrFail())->update($oPayload)
                    : $goal->objectives()->create($oPayload);
                $keptObjIds[] = $obj->id;
            }
            $goal->objectives()->whereNotIn('id', $keptObjIds ?: [0])->delete();
        }
        $plan->goals()->whereNotIn('id', $keptGoalIds ?: [0])->delete();
    }
}
