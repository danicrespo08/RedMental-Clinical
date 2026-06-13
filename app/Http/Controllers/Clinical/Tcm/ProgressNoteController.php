<?php

namespace App\Http\Controllers\Clinical\Tcm;

use App\Http\Controllers\Controller;
use App\Models\Hhrr\Employee;
use App\Models\Tcm\Admission;
use App\Models\Tcm\ProgressNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * TCM case-management progress notes. A note is tied to an admission, editable
 * until signed; once signed it is locked and can only receive an addendum.
 */
class ProgressNoteController extends Controller
{
    public function index(Request $request): View
    {
        $q      = trim((string) $request->query('q', ''));
        $month  = $request->query('month');
        $status = $request->query('status');

        $notes = ProgressNote::query()
            ->with(['admission.patient', 'caseManager'])
            ->when($month, fn ($qb) => $qb->whereYear('note_date', substr($month, 0, 4))->whereMonth('note_date', substr($month, 5, 2)))
            ->when($status, fn ($qb) => $qb->where('status', $status))
            ->when($q !== '', fn ($qb) => $qb->whereHas('admission.patient', function ($p) use ($q) {
                $p->where('first_name', 'like', "%{$q}%")
                  ->orWhere('last_name', 'like', "%{$q}%")
                  ->orWhere('mrn', 'like', "%{$q}%");
            }))
            ->orderByDesc('note_date')
            ->paginate(20)
            ->withQueryString();

        return view('clinical.tcm.progress-notes.index', [
            'notes'    => $notes,
            'q'        => $q,
            'month'    => $month,
            'status'   => $status,
            'statuses' => ProgressNote::STATUSES,
        ]);
    }

    public function create(Request $request): View
    {
        $admissions = $this->admissionOptions();
        $preselect  = $request->query('admission_id');

        return view('clinical.tcm.progress-notes.form', [
            'admissions'       => $admissions,
            'goalsByAdmission' => $admissions->mapWithKeys(fn ($a) => [$a->id => $this->goalsArray($a)]),
            'note'             => new ProgressNote([
                'tcm_admission_id' => $preselect,
                'note_date'        => now()->toDateString(),
                'note_type'        => 'coordination',
                'risk_level'       => 'none',
            ]),
            'caseManagers' => Employee::where('active', true)->orderBy('last_name')->get(),
            'noteTypes'    => ProgressNote::NOTE_TYPES,
            'riskLevels'   => ProgressNote::RISK_LEVELS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $admission = Admission::findOrFail($request->input('tcm_admission_id'));
        $data = $this->validated($request);
        $data['patient_id'] = $admission->patient_id;
        $data['created_by'] = auth()->id();
        ProgressNote::create($data);

        return redirect()->route('clinical.tcm.progress_notes.index')->with('status', 'Progress note saved.');
    }

    public function show(ProgressNote $progressNote): View
    {
        $progressNote->load(['admission.patient', 'caseManager', 'signedByEmployee', 'signedByUser', 'addendumBy']);
        return view('clinical.tcm.progress-notes.show', [
            'note'       => $progressNote,
            'noteTypes'  => ProgressNote::NOTE_TYPES,
            'riskLevels' => ProgressNote::RISK_LEVELS,
        ]);
    }

    public function edit(ProgressNote $progressNote): View|RedirectResponse
    {
        if ($progressNote->is_signed) {
            return redirect()->route('clinical.tcm.progress_notes.show', $progressNote)
                ->with('error', 'Signed notes are locked — add an addendum instead.');
        }

        $admission = $progressNote->admission;
        return view('clinical.tcm.progress-notes.form', [
            'admissions'       => collect([$admission])->merge($this->admissionOptions())->unique('id')->values(),
            'goalsByAdmission' => [$admission->id => $this->goalsArray($admission)],
            'note'             => $progressNote,
            'caseManagers' => Employee::where('active', true)->orderBy('last_name')->get(),
            'noteTypes'    => ProgressNote::NOTE_TYPES,
            'riskLevels'   => ProgressNote::RISK_LEVELS,
        ]);
    }

    public function update(Request $request, ProgressNote $progressNote): RedirectResponse
    {
        abort_if($progressNote->is_signed, 403, 'Signed notes cannot be edited.');
        $progressNote->update($this->validated($request));

        return redirect()->route('clinical.tcm.progress_notes.show', $progressNote)->with('status', 'Progress note updated.');
    }

    public function destroy(ProgressNote $progressNote): RedirectResponse
    {
        abort_if($progressNote->is_signed, 403, 'Signed notes cannot be deleted.');
        $progressNote->delete();

        return redirect()->route('clinical.tcm.progress_notes.index')->with('status', 'Progress note deleted.');
    }

    public function sign(ProgressNote $progressNote): RedirectResponse
    {
        $user = auth()->user();
        $progressNote->update([
            'status'            => 'signed',
            'is_signed'         => true,
            'signed_at'         => now(),
            'signed_by_user_id' => $user->id,
            'signed_by'         => $user->employee_id ?? null,
        ]);

        return back()->with('status', 'Progress note signed.');
    }

    public function addendum(Request $request, ProgressNote $progressNote): RedirectResponse
    {
        $data = $request->validate(['addendum_text' => ['required', 'string']]);
        $progressNote->update([
            'addendum_text' => $data['addendum_text'],
            'addendum_date' => now(),
            'addendum_by'   => auth()->id(),
            'status'        => 'addendum',
        ]);

        return back()->with('status', 'Addendum added.');
    }

    private function admissionOptions()
    {
        return Admission::query()
            ->where('status', '!=', 'discharged')
            ->with('patient')
            ->orderByDesc('admission_date')
            ->get();
    }

    private function goalsArray(?Admission $admission): array
    {
        if (! $admission) return [];
        $plan = $admission->treatmentPlans()->where('is_signed', true)->latest('id')->first()
             ?? $admission->treatmentPlans()->latest('id')->first();
        if (! $plan) return [];
        return $plan->goals()->orderBy('goal_code')->get()->map(fn ($g) => [
            'code'  => $g->goal_code,
            'label' => trim($g->goal_code . ' — ' . $g->description),
        ])->values()->all();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'tcm_admission_id' => ['required', 'exists:tcm_admissions,id'],
            'note_date'        => ['required', 'date'],
            'case_manager_id'  => ['nullable', 'exists:employees,id'],
            'note_type'        => ['required', Rule::in(array_keys(ProgressNote::NOTE_TYPES))],
            'summary'          => ['nullable', 'string'],
            'interventions'    => ['nullable', 'string'],
            'coordination'     => ['nullable', 'string'],
            'progress'         => ['nullable', 'string'],
            'plan'             => ['nullable', 'string'],
            'risk_level'       => ['required', Rule::in(array_keys(ProgressNote::RISK_LEVELS))],
            'risk_notes'       => ['nullable', 'string'],
            'goals_addressed'  => ['nullable', 'string'],
        ]);
    }
}
