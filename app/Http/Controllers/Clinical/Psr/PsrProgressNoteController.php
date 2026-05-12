<?php

namespace App\Http\Controllers\Clinical\Psr;

use App\Http\Controllers\Controller;
use App\Models\Hhrr\Employee;
use App\Models\Psr\Admission;
use App\Models\Psr\GroupSession;
use App\Models\Psr\NoteTemplate;
use App\Models\Psr\ProgressNote;
use App\Services\AiClinicalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * PSR Progress Note controller.
 *
 * Documents per-patient SOAP/DAP/BIRP/GIRP notes for each session attended.
 * Notes start as `draft`, transition to `signed` (locked), and may receive
 * `addendum` text without re-opening the original record.
 */
class PsrProgressNoteController extends Controller
{
    public function index(Request $request): View
    {
        $status      = $request->query('status');
        $patientId   = $request->query('patient_id');
        $admissionId = $request->query('admission_id');

        $notes = ProgressNote::query()
            ->with(['patient', 'admission', 'therapist', 'template'])
            ->when($status,      fn ($q) => $q->where('status', $status))
            ->when($patientId,   fn ($q) => $q->where('patient_id', $patientId))
            ->when($admissionId, fn ($q) => $q->where('psr_admission_id', $admissionId))
            ->orderByDesc('note_date')
            ->paginate(20)
            ->withQueryString();

        return view('clinical.psr.progress-notes.index', [
            'notes'    => $notes,
            'statuses' => ProgressNote::STATUSES,
            'status'   => $status,
        ]);
    }

    public function create(Request $request): View
    {
        $admission = $request->query('admission_id')
            ? Admission::with('patient')->find($request->query('admission_id'))
            : null;
        $session = $request->query('group_session_id')
            ? GroupSession::find($request->query('group_session_id'))
            : null;

        $note = new ProgressNote([
            'psr_admission_id'    => $admission?->id,
            'patient_id'          => $admission?->patient_id,
            'psr_group_session_id' => $session?->id,
            'note_date'           => $session?->session_date ?? now()->toDateString(),
            'start_time'          => $session?->start_time,
            'end_time'            => $session?->end_time,
            'service_code'        => $session?->service_code ?? 'H2017',
            'place_of_service'    => $session?->place_of_service ?? '11',
            'modifier'            => $session?->modifier,
            'status'              => 'draft',
            'risk_level'          => 'none',
            'session_type'        => 'group_therapy',
        ]);

        return view('clinical.psr.progress-notes.form', $this->formViewData($note));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = auth()->id();
        $note = ProgressNote::create($data);

        return redirect()
            ->route('clinical.psr.progress_notes.show', $note)
            ->with('status', 'Progress note saved as draft.');
    }

    public function show(ProgressNote $progressNote): View
    {
        $progressNote->load([
            'patient', 'admission.clinic', 'therapist', 'template',
            'groupSession', 'signedByEmployee', 'signedByUser',
            'coSigner', 'addendumBy',
        ]);
        return view('clinical.psr.progress-notes.show', ['note' => $progressNote]);
    }

    public function edit(ProgressNote $progressNote): View
    {
        abort_if($progressNote->is_signed && $progressNote->status !== 'addendum', 403,
            'Signed notes can only receive an addendum, not edits.');

        return view('clinical.psr.progress-notes.form', $this->formViewData($progressNote));
    }

    public function update(Request $request, ProgressNote $progressNote): RedirectResponse
    {
        abort_if($progressNote->is_signed, 403, 'Use the addendum action on signed notes.');
        $progressNote->update($this->validated($request));
        return redirect()
            ->route('clinical.psr.progress_notes.show', $progressNote)
            ->with('status', 'Progress note updated.');
    }

    public function destroy(ProgressNote $progressNote): RedirectResponse
    {
        abort_if($progressNote->is_signed, 403, 'Signed notes cannot be deleted.');
        $admissionId = $progressNote->psr_admission_id;
        $progressNote->delete();
        return redirect()
            ->route('clinical.psr.admissions.show', $admissionId)
            ->with('status', 'Progress note deleted.');
    }

    /** Sign a draft note — locks editing but allows addenda. */
    public function sign(ProgressNote $progressNote): RedirectResponse
    {
        abort_if($progressNote->is_signed, 422, 'Note is already signed.');
        $user = auth()->user();
        $progressNote->update([
            'status'            => 'signed',
            'is_signed'         => true,
            'signed_at'         => now(),
            'signed_by_user_id' => $user->id,
        ]);
        return back()->with('status', 'Progress note signed.');
    }

    /**
     * Generate AI-suggested note content for a given format (SOAP/DAP/BIRP/GIRP).
     * Returns JSON with the field names matching the form (subjective, objective, etc.)
     */
    public function aiSuggest(Request $request): JsonResponse
    {
        $request->validate([
            'admission_id' => ['nullable', 'exists:psr_admissions,id'],
            'format'       => ['nullable', Rule::in(['soap', 'dap', 'birp', 'girp'])],
            'session_type' => ['nullable', 'string'],
        ]);

        $format = $request->input('format', 'soap');
        $admission = $request->filled('admission_id')
            ? Admission::with('patient')->find($request->input('admission_id'))
            : null;

        $patient = $admission?->patient;
        $context = "PATIENT: " . ($patient?->first_name ?? 'unknown') . "\n";
        $context .= 'DIAGNOSIS: ' . ($admission?->primary_dx_code ?? 'N/A')
            . ' — ' . ($admission?->primary_dx_description ?? 'unspecified') . "\n";
        $context .= 'SESSION TYPE: ' . ($request->input('session_type') ?? 'group_therapy') . "\n";

        $formatSpec = match ($format) {
            'dap'  => ['data', 'assessment', 'plan'],
            'birp' => ['behavior', 'intervention', 'response', 'plan'],
            'girp' => ['goal', 'intervention', 'response', 'plan'],
            default => ['subjective', 'objective', 'intervention', 'response', 'progress', 'plan'],
        };
        $fieldList = implode(', ', $formatSpec);

        $prompt = <<<PROMPT
You are a clinical documentation assistant for a Psychosocial Rehabilitation (PSR) program in Florida.
Generate a {$format}-format progress-note draft for a 60-minute group session.

{$context}

INSTRUCTIONS:
- Produce 1-2 short paragraphs per field, in measurable, observable language.
- Reference the patient's diagnosis where appropriate.
- Avoid fabricated specifics — keep statements plausible and generic.
- Follow Florida Medicaid PSR documentation standards.
- Output JSON only with these keys: {$fieldList}, mood, affect, progress_rating (1-5).
PROMPT;

        $result = AiClinicalService::call($prompt, 1200, [
            'mock' => fn () => $this->mockNoteSuggestion($admission, $format),
        ]);

        return response()->json($result);
    }

    private function mockNoteSuggestion(?Admission $admission, string $format): array
    {
        $name = $admission?->patient?->first_name ?? 'The patient';
        $dx   = $admission?->primary_dx_description ?? 'mental health condition';

        $base = [
            'subjective'  => "{$name} reported feeling \"a little better\" since last session and described improved sleep over the past week. Endorsed continued worry related to {$dx} but denied any active suicidal or homicidal ideation.",
            'objective'   => "Patient was alert, oriented x4, well-groomed and casually dressed. Mood euthymic, affect congruent and full range. Speech regular rate and rhythm. Engaged appropriately throughout the 60-minute group session.",
            'intervention'=> "Co-led psychoeducation on cognitive restructuring; facilitated peer feedback exercise. Reinforced previously taught grounding techniques and role-played a recent triggering scenario.",
            'response'    => "{$name} actively participated, identified two cognitive distortions, and verbalized a balanced thought. Receptive to peer feedback. Demonstrated use of 5-4-3-2-1 grounding when prompted.",
            'progress'    => "Patient is progressing toward goal #1 (manage {$dx} symptoms) — partial mastery of coping skills observed. Group attendance has improved to 90% over the past 30 days.",
            'plan'        => "Continue weekly group sessions at current frequency. Patient to complete one cognitive-restructuring worksheet daily before next session. Reassess goal #1 progress at next 30-day mark.",
            'data'        => "{$name} attended group, mood euthymic, affect congruent. Reported improved sleep and reduced worry frequency. Engaged in psychoeducation on cognitive restructuring; identified two distortions and verbalized a balanced thought.",
            'assessment'  => "Patient demonstrating partial mastery of cognitive-restructuring skill. Mood improving relative to prior session; symptoms of {$dx} appear less acute. Continues to benefit from group milieu and peer feedback.",
            'behavior'    => "{$name} arrived on time, appeared engaged, made appropriate eye contact, and shared spontaneously during group discussion. No agitation or withdrawal observed.",
            'goal'        => "Address goal #1 — patient to demonstrate use of cognitive-restructuring techniques to manage {$dx} symptoms (90-day target).",
            'mood'        => 'euthymic',
            'affect'      => 'congruent, full range',
            'progress_rating' => 4,
        ];

        $keys = match ($format) {
            'dap'  => ['data', 'assessment', 'plan', 'mood', 'affect', 'progress_rating'],
            'birp' => ['behavior', 'intervention', 'response', 'plan', 'mood', 'affect', 'progress_rating'],
            'girp' => ['goal', 'intervention', 'response', 'plan', 'mood', 'affect', 'progress_rating'],
            default => ['subjective', 'objective', 'intervention', 'response', 'progress', 'plan', 'mood', 'affect', 'progress_rating'],
        };
        return array_intersect_key($base, array_flip($keys));
    }

    /** Append an addendum to a signed note. */
    public function addendum(Request $request, ProgressNote $progressNote): RedirectResponse
    {
        abort_unless($progressNote->is_signed, 422, 'Only signed notes can receive addenda.');
        $request->validate(['addendum_text' => ['required', 'string']]);
        $progressNote->update([
            'addendum_text' => trim(($progressNote->addendum_text ? $progressNote->addendum_text . "\n\n---\n\n" : '')
                . '['.now()->format('Y-m-d H:i').' — '.auth()->user()->name."]\n".$request->input('addendum_text')),
            'addendum_date' => now(),
            'addendum_by'   => auth()->id(),
            'status'        => 'addendum',
        ]);
        return back()->with('status', 'Addendum added.');
    }

    private function formViewData(ProgressNote $note): array
    {
        return [
            'note'       => $note,
            'admission'  => $note->admission,
            'admissions' => Admission::with('patient')->orderByDesc('admission_date')->limit(200)->get(),
            'therapists' => Employee::where('active', true)->where('is_provider', true)->orderBy('last_name')->get(),
            'templates'  => NoteTemplate::where('is_active', true)->orderBy('name')->get(),
            'sessions'   => GroupSession::orderByDesc('session_date')->limit(50)->get(),
            'statuses'   => ProgressNote::STATUSES,
            'risks'      => ProgressNote::RISK_LEVELS,
        ];
    }

    private function validated(Request $request): array
    {
        foreach (['psr_group_session_id', 'note_template_id', 'start_time', 'end_time'] as $f) {
            if ($request->input($f) === '') $request->merge([$f => null]);
        }
        return $request->validate([
            'psr_admission_id'      => ['required', 'exists:psr_admissions,id'],
            'patient_id'            => ['required', 'exists:patients,id'],
            'psr_group_session_id'  => ['nullable', 'exists:psr_group_sessions,id'],
            'note_template_id'      => ['nullable', 'exists:psr_note_templates,id'],
            'note_date'             => ['required', 'date'],
            'start_time'            => ['nullable', 'date_format:H:i'],
            'end_time'              => ['nullable', 'date_format:H:i', 'after_or_equal:start_time'],
            'units'                 => ['nullable', 'integer', 'min:0'],
            'service_code'          => ['nullable', 'string', 'max:20'],
            'modifier'              => ['nullable', 'string', 'max:20'],
            'place_of_service'      => ['nullable', 'string', 'max:10'],
            'therapist_id'          => ['required', 'exists:employees,id'],

            'subjective'            => ['nullable', 'string'],
            'objective'             => ['nullable', 'string'],
            'intervention'          => ['nullable', 'string'],
            'response'              => ['nullable', 'string'],
            'progress'              => ['nullable', 'string'],
            'plan'                  => ['nullable', 'string'],

            'mood'                  => ['nullable', 'string', 'max:50'],
            'affect'                => ['nullable', 'string', 'max:50'],
            'risk_level'            => ['required', Rule::in(array_keys(ProgressNote::RISK_LEVELS))],
            'risk_notes'            => ['nullable', 'string'],

            'participation_level'   => ['nullable', 'string', 'max:30'],
            'session_type'          => ['nullable', 'string', 'max:50'],
            'progress_rating'       => ['nullable', 'integer', 'min:1', 'max:5'],

            'status'                => ['required', Rule::in(array_keys(ProgressNote::STATUSES))],
        ]);
    }
}
