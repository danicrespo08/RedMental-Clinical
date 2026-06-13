<?php

namespace App\Http\Controllers\Clinical\It;

use App\Http\Controllers\Controller;
use App\Models\Hhrr\Employee;
use App\Models\It\Admission;
use App\Models\It\Authorization;
use App\Models\It\ServiceLog;
use App\Models\It\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SessionController extends Controller
{
    /** Cross-admission session list. */
    public function index(Request $request): View
    {
        $q       = trim((string) $request->query('q', ''));
        $month   = $request->query('month');
        $cpt     = $request->query('cpt');

        $sessions = Session::query()
            ->with(['admission.patient', 'therapist'])
            ->when($month, fn ($qb) => $qb->whereYear('session_date', substr($month, 0, 4))->whereMonth('session_date', substr($month, 5, 2)))
            ->when($cpt,   fn ($qb) => $qb->where('cpt_code', $cpt))
            ->when($q !== '', fn ($qb) => $qb->whereHas('admission.patient', function ($p) use ($q) {
                $p->where('first_name', 'like', "%{$q}%")
                  ->orWhere('last_name', 'like', "%{$q}%")
                  ->orWhere('mrn', 'like', "%{$q}%");
            }))
            ->orderByDesc('session_date')
            ->paginate(20)
            ->withQueryString();

        $cptOptions = Session::query()
            ->select('cpt_code')->whereNotNull('cpt_code')->distinct()
            ->orderBy('cpt_code')->pluck('cpt_code');

        return view('clinical.it.sessions.index', compact('sessions', 'q', 'month', 'cpt', 'cptOptions'));
    }

    /** Standalone "new session" reachable from the cross-patient sessions list. */
    public function createAny(): View
    {
        $admissions = $this->admissionOptions();

        return view('clinical.it.sessions.form', [
            'admission'        => null,
            'admissions'       => $admissions,
            'goalsByAdmission' => $admissions->mapWithKeys(fn ($a) => [$a->id => $this->goalsArray($a)]),
            'session'    => new Session([
                'session_date'     => now()->toDateString(),
                'cpt_code'         => '90834',
                'place_of_service' => '11',
                'units'            => 1,
            ]),
            'therapists' => Employee::where('active', true)->orderBy('last_name')->get(),
        ]);
    }

    public function storeAny(Request $request): RedirectResponse
    {
        $request->validate(['it_admission_id' => ['required', 'exists:it_admissions,id']]);
        $admission = Admission::findOrFail($request->input('it_admission_id'));
        abort_unless($admission->hasSignedTreatmentPlan(), 403, 'A signed treatment plan is required before logging sessions.');

        $data = $this->validated($request);
        $data['it_admission_id'] = $admission->id;
        DB::transaction(function () use ($data) {
            $session = Session::create($data);
            $this->syncServiceLog($session);
        });

        return redirect()->route('clinical.it.sessions.index')->with('status', 'Session recorded.');
    }

    /** Goals from the admission's signed (or latest) treatment plan, for the picker. */
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

    /** Admissions eligible for new sessions: active episode with a signed treatment plan. */
    private function admissionOptions()
    {
        return Admission::query()
            ->where('status', '!=', 'discharged')
            ->whereHas('treatmentPlans', fn ($q) => $q->where('is_signed', true))
            ->with('patient')
            ->orderByDesc('admission_date')
            ->get();
    }

    public function show(Admission $admission, Session $session): View
    {
        abort_if($session->it_admission_id !== $admission->id, 404);
        $session->load(['admission.patient', 'therapist']);
        return view('clinical.it.sessions.show', compact('admission', 'session'));
    }

    public function create(Admission $admission): View|RedirectResponse
    {
        if (! $admission->hasSignedTreatmentPlan()) {
            return redirect()
                ->route('clinical.it.treatment_plans.create', ['admission_id' => $admission->id])
                ->with('error', 'Complete and sign a treatment plan before logging therapy sessions.');
        }

        return view('clinical.it.sessions.form', [
            'admission'        => $admission->load('patient'),
            'goalsByAdmission' => [$admission->id => $this->goalsArray($admission)],
            'session'    => new Session([
                'session_date'     => now()->toDateString(),
                'cpt_code'         => '90834',
                'place_of_service' => '11',
                'units'            => 1,
                'therapist_id'     => $admission->therapist_id,
            ]),
            'therapists' => Employee::where('active', true)->orderBy('last_name')->get(),
        ]);
    }

    public function store(Request $request, Admission $admission): RedirectResponse
    {
        abort_unless($admission->hasSignedTreatmentPlan(), 403, 'A signed treatment plan is required before logging sessions.');

        $data = $this->validated($request);
        $data['it_admission_id'] = $admission->id;
        DB::transaction(function () use ($data) {
            $session = Session::create($data);
            $this->syncServiceLog($session);
        });
        return redirect()->route('clinical.it.admissions.show', $admission)->with('status', 'Session recorded.');
    }

    public function edit(Admission $admission, Session $session): View
    {
        return view('clinical.it.sessions.form', [
            'admission'        => $admission->load('patient'),
            'goalsByAdmission' => [$admission->id => $this->goalsArray($admission)],
            'session'          => $session,
            'therapists' => Employee::where('active', true)->orderBy('last_name')->get(),
        ]);
    }

    public function update(Request $request, Admission $admission, Session $session): RedirectResponse
    {
        DB::transaction(function () use ($request, $session) {
            $session->update($this->validated($request));
            $this->syncServiceLog($session->fresh());
        });
        return redirect()->route('clinical.it.admissions.show', $admission)->with('status', 'Session updated.');
    }

    public function destroy(Admission $admission, Session $session): RedirectResponse
    {
        DB::transaction(function () use ($session) {
            $log = ServiceLog::where('it_session_id', $session->id)->first();
            $authId = $log?->it_authorization_id;
            $log?->delete();
            $session->delete();
            if ($authId) Authorization::find($authId)?->recalcUnitsUsed();
        });
        return redirect()->route('clinical.it.admissions.show', $admission)->with('status', 'Session deleted.');
    }

    /**
     * Mirror a session into the billable service log so it flows into the superbill.
     * One log per session (keyed by it_session_id); links to the admission's active
     * authorization when present so used-unit counters stay in sync.
     */
    private function syncServiceLog(Session $session): void
    {
        $admission   = $session->admission;
        $therapistId = $session->therapist_id ?? $admission->therapist_id;
        if (! $therapistId) return; // service log requires a rendering provider

        $auth = $admission->authorizations()
            ->where('status', 'approved')->latest('id')->first()
            ?? $admission->authorizations()->latest('id')->first();

        $hasNote = filled($session->subjective) || filled($session->objective)
                || filled($session->assessment) || filled($session->plan);

        ServiceLog::updateOrCreate(
            ['it_session_id' => $session->id],
            [
                'client_id'             => $admission->client_id,
                'patient_id'            => $admission->patient_id,
                'it_admission_id'       => $admission->id,
                'service_date'          => $session->session_date,
                'start_time'            => $session->start_time,
                'end_time'              => $session->end_time,
                'units'                 => $session->units,
                'cpt_code'              => $session->cpt_code,
                'modifier'              => $session->modifier,
                'place_of_service'      => $session->place_of_service,
                'diagnosis_code'        => $admission->diagnosis_code,
                'diagnosis_description' => $admission->diagnosis_description,
                'therapist_id'          => $therapistId,
                'it_authorization_id'   => $auth?->id,
                'auth_number'           => $auth?->auth_number,
                'has_progress_note'     => $hasNote,
                'created_by'            => auth()->id(),
            ]
        );

        $auth?->recalcUnitsUsed();
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'therapist_id'     => ['nullable', 'exists:employees,id'],
            'session_date'     => ['required', 'date'],
            'start_time'       => ['nullable', 'date_format:H:i'],
            'end_time'         => ['nullable', 'date_format:H:i', 'after:start_time'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'cpt_code'         => ['required', 'string', 'max:10'],
            'modifier'         => ['nullable', 'string', 'max:10'],
            'place_of_service' => ['required', 'string', 'max:4'],
            'units'            => ['required', 'integer', 'min:1'],
            'subjective'       => ['nullable', 'string'],
            'objective'        => ['nullable', 'string'],
            'assessment'       => ['nullable', 'string'],
            'plan'             => ['nullable', 'string'],
            'goals_addressed'  => ['nullable', 'string'],
        ]);

        // Duration is authoritative when both times are present — keep the metrics honest.
        if (! empty($data['start_time']) && ! empty($data['end_time'])) {
            $start = Carbon::createFromFormat('H:i', $data['start_time']);
            $end   = Carbon::createFromFormat('H:i', $data['end_time']);
            $data['duration_minutes'] = max(0, $start->diffInMinutes($end));
        }

        return $data;
    }
}
