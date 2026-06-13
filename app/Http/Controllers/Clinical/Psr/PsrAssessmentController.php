<?php

namespace App\Http\Controllers\Clinical\Psr;

use App\Http\Controllers\Controller;
use App\Models\Psr\Admission;
use App\Models\Psr\AssessmentBio;
use App\Models\Psr\Fars;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * PSR Bio-Psychosocial Assessment + FARS controller.
 *
 * One bio-psychosocial assessment exists per admission (1:1) and is editable
 * until signed. FARS (Functional Assessment Rating Scale) is appended over
 * time — admission, periodic (90-day), and discharge instances.
 */
class PsrAssessmentController extends Controller
{
    public function index(Request $request): View
    {
        $search       = trim((string) $request->query('search', ''));
        $clinicFilter = $request->query('clinic_id');
        $bioFilter    = $request->query('bio_status');   // pending | draft | signed
        $farsFilter   = $request->query('fars_status');

        // Pivot around admissions ('s chart-overview)
        $admissions = \App\Models\Psr\Admission::query()
            ->with([
                'patient', 'clinic', 'assignedTherapist',
                'intake', 'bioAssessment.signedByEmployee',
                'farsAssessments' => fn ($q) => $q->orderByDesc('evaluation_date'),
                'treatmentPlans'  => fn ($q) => $q->orderByDesc('start_date'),
            ])
            ->whereIn('status', ['admitted', 'on_hold', 'pending_intake', 'intake_complete'])
            ->when($search !== '', fn ($q) => $q->whereHas('patient', fn ($p) => $p
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('mrn', 'like', "%{$search}%")))
            ->when($clinicFilter, fn ($q) => $q->where('clinic_id', $clinicFilter))
            ->orderByDesc('admission_date')
            ->paginate(20)
            ->withQueryString();

        // Post-query filter on bio/fars compliance state
        if ($bioFilter || $farsFilter) {
            $filtered = $admissions->getCollection()->filter(function ($a) use ($bioFilter, $farsFilter) {
                if ($bioFilter) {
                    $bs = ! $a->bioAssessment ? 'pending' : ($a->bioAssessment->is_signed ? 'signed' : 'draft');
                    if ($bs !== $bioFilter) return false;
                }
                if ($farsFilter) {
                    $fars = $a->farsAssessments->first();
                    $fs = ! $fars ? 'pending' : ($fars->is_signed ? 'signed' : 'draft');
                    if ($fs !== $farsFilter) return false;
                }
                return true;
            })->values();
            $admissions->setCollection($filtered);
        }

        $base = \App\Models\Psr\Admission::whereIn('status', ['admitted', 'on_hold', 'pending_intake', 'intake_complete']);
        $stats = [
            'totalActive'  => (clone $base)->count(),
            'bioPending'   => (clone $base)->whereDoesntHave('bioAssessment')->count(),
            'bioDrafts'    => (clone $base)->whereHas('bioAssessment', fn ($q) => $q->where('is_signed', false))->count(),
            'bioSigned'    => (clone $base)->whereHas('bioAssessment', fn ($q) => $q->where('is_signed', true))->count(),
            'farsSigned'   => (clone $base)->whereHas('farsAssessments', fn ($q) => $q->where('is_signed', true))->count(),
            'intakeSigned' => (clone $base)->whereHas('intake', fn ($q) => $q->where('is_signed', true))->count(),
            'mtpSigned'    => (clone $base)->whereHas('treatmentPlans', fn ($q) => $q->where('is_signed', true))->count(),
        ];

        return view('clinical.psr.assessments.index', [
            'admissions'    => $admissions,
            'stats'         => $stats,
            'filterClinics' => \App\Models\Hhrr\Clinic::where('active', true)->orderBy('name')->get(),
            'search'        => $search,
            'clinicFilter'  => $clinicFilter,
            'bioFilter'     => $bioFilter,
            'farsFilter'    => $farsFilter,
        ]);
    }

    public function create(Request $request): View
    {
        $admission = Admission::with('patient')->findOrFail($request->query('admission_id'));

        return view('clinical.psr.assessments.form', [
            'admission'  => $admission,
            'assessment' => new AssessmentBio(['psr_admission_id' => $admission->id]),
            'fars'       => $admission->farsAssessments,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $assessment = AssessmentBio::create($data);

        return redirect()
            ->route('clinical.psr.assessments.edit', $assessment)
            ->with('status', 'Bio-psychosocial assessment saved.');
    }

    public function show(AssessmentBio $assessment): View
    {
        $assessment->load(['admission.patient', 'signedByEmployee']);
        return view('clinical.psr.assessments.show', compact('assessment'));
    }

    public function edit(AssessmentBio $assessment): View
    {
        $assessment->load(['admission.patient']);
        return view('clinical.psr.assessments.form', [
            'admission'  => $assessment->admission,
            'assessment' => $assessment,
            'fars'       => $assessment->admission->farsAssessments()->orderByDesc('evaluation_date')->get(),
        ]);
    }

    public function update(Request $request, AssessmentBio $assessment): RedirectResponse
    {
        abort_if($assessment->is_signed, 403, 'Signed assessments cannot be edited.');

        $assessment->update($this->validated($request, $assessment->id));
        return redirect()
            ->route('clinical.psr.assessments.edit', $assessment)
            ->with('status', 'Assessment updated.');
    }

    public function destroy(AssessmentBio $assessment): RedirectResponse
    {
        abort_if($assessment->is_signed, 403, 'Signed assessments cannot be deleted.');
        $admissionId = $assessment->psr_admission_id;
        $assessment->delete();
        return redirect()
            ->route('clinical.psr.admissions.show', $admissionId)
            ->with('status', 'Assessment deleted.');
    }

    /** Sign-off action — locks the assessment from further edits. */
    public function sign(AssessmentBio $assessment): RedirectResponse
    {
        $user = auth()->user();
        $assessment->update([
            'is_signed'         => true,
            'signed_at'         => now(),
            'signed_by_user_id' => $user->id,
            'signed_by'         => $user->employee_id ?? null,
        ]);
        return back()->with('status', 'Assessment signed.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'psr_admission_id'   => ['required', 'exists:psr_admissions,id',
                Rule::unique('psr_assessments_bio', 'psr_admission_id')->ignore($ignoreId)],
            'presenting_problem'  => ['nullable', 'string'],
            'history_illness'     => ['nullable', 'string'],
            'family_history'      => ['nullable', 'string'],
            'medical_history'     => ['nullable', 'string'],
            'medications'         => ['nullable', 'string'],
            'risk_assessment'     => ['nullable', 'string'],
            'clinical_impression' => ['nullable', 'string'],
        ]);
    }


    public function farsCreate(Request $request, Admission $admission): View
    {
        return view('clinical.psr.assessments.fars-form', [
            'admission' => $admission->load('patient'),
            'fars'      => new Fars([
                'psr_admission_id' => $admission->id,
                'evaluation_type'  => 'admission',
                'evaluation_date'  => now()->format('Y-m-d\TH:i'),
            ]),
        ]);
    }

    public function farsStore(Request $request, Admission $admission): RedirectResponse
    {
        $data = $this->validatedFars($request);
        $data['psr_admission_id'] = $admission->id;
        $fars = new Fars($data);
        $fars->recalculateTotal();
        $fars->save();

        return redirect()
            ->route('clinical.psr.admissions.show', $admission)
            ->with('status', 'FARS assessment recorded.');
    }

    public function farsEdit(Fars $fars): View
    {
        $fars->load('admission.patient');
        return view('clinical.psr.assessments.fars-form', [
            'admission' => $fars->admission,
            'fars'      => $fars,
        ]);
    }

    public function farsUpdate(Request $request, Fars $fars): RedirectResponse
    {
        abort_if($fars->is_signed, 403, 'Signed FARS cannot be edited.');
        $fars->fill($this->validatedFars($request));
        $fars->recalculateTotal();
        $fars->save();
        return redirect()
            ->route('clinical.psr.admissions.show', $fars->psr_admission_id)
            ->with('status', 'FARS updated.');
    }

    public function farsDestroy(Fars $fars): RedirectResponse
    {
        abort_if($fars->is_signed, 403, 'Signed FARS cannot be deleted.');
        $admissionId = $fars->psr_admission_id;
        $fars->delete();
        return redirect()
            ->route('clinical.psr.admissions.show', $admissionId)
            ->with('status', 'FARS deleted.');
    }

    private function validatedFars(Request $request): array
    {
        $rules = [
            'evaluation_type' => ['required', Rule::in(array_keys(Fars::EVALUATION_TYPES))],
            'evaluation_date' => ['required', 'date'],
            'mgaf_score'      => ['nullable', 'integer', 'min:0', 'max:100'],
            'substance_abuse_history' => ['sometimes', 'boolean'],
        ];
        foreach (Fars::DOMAINS as $d) {
            $rules[$d] = ['required', 'integer', 'min:1', 'max:9'];
        }
        foreach (Fars::DOMAINS as $d) {
            $rules["{$d}_indicators"]   = ['sometimes', 'array'];
            $rules["{$d}_indicators.*"] = ['string'];
        }
        $data = $request->validate($rules);
        $data['substance_abuse_history'] = $request->boolean('substance_abuse_history');

        $indicators = [];
        foreach (Fars::DOMAINS as $d) {
            $indicators[$d] = array_values((array) $request->input("{$d}_indicators", []));
            unset($data["{$d}_indicators"]);
        }
        $data['indicators_json'] = json_encode($indicators);

        return $data;
    }

    /** Sign-off action — locks the FARS from further edits. */
    public function farsSign(Fars $fars): RedirectResponse
    {
        $user = auth()->user();
        $fars->update([
            'is_signed'         => true,
            'signed_at'         => now(),
            'signed_by_user_id' => $user->id,
            'signed_by'         => $user->employee_id ?? null,
        ]);
        return back()->with('status', 'FARS signed.');
    }
}
