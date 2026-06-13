<?php

namespace App\Http\Controllers\Clinical\Tcm;

use App\Http\Controllers\Controller;
use App\Models\Hhrr\Employee;
use App\Models\Tcm\Admission;
use App\Models\Tcm\Authorization;
use App\Models\Tcm\Contact;
use App\Models\Tcm\ServiceLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController extends Controller
{
    /** Cross-admission contact list. */
    public function index(Request $request): View
    {
        $q     = trim((string) $request->query('q', ''));
        $month = $request->query('month');
        $type  = $request->query('type');

        $contacts = Contact::query()
            ->with(['admission.patient', 'caseManager'])
            ->when($month, fn ($qb) => $qb->whereYear('contact_at', substr($month, 0, 4))->whereMonth('contact_at', substr($month, 5, 2)))
            ->when($type,  fn ($qb) => $qb->where('contact_type', $type))
            ->when($q !== '', fn ($qb) => $qb->whereHas('admission.patient', function ($p) use ($q) {
                $p->where('first_name', 'like', "%{$q}%")
                  ->orWhere('last_name', 'like', "%{$q}%")
                  ->orWhere('mrn', 'like', "%{$q}%");
            }))
            ->orderByDesc('contact_at')
            ->paginate(20)
            ->withQueryString();

        return view('clinical.tcm.contacts.index', [
            'contacts' => $contacts,
            'q'        => $q,
            'month'    => $month,
            'type'     => $type,
            'types'    => Contact::CONTACT_TYPES,
        ]);
    }

    /** CPT codes whose unit represents this many minutes (case-management billing basis). */
    private const UNIT_MINUTES = ['T1017' => 15, 'T1016' => 15, 'H0006' => 15, 'G9012' => 15];

    /** Standalone "record contact" reachable from the cross-patient contacts list. */
    public function createAny(): View
    {
        $admissions = $this->admissionOptions();

        return view('clinical.tcm.contacts.form', [
            'admission'        => null,
            'admissions'       => $admissions,
            'goalsByAdmission' => $admissions->mapWithKeys(fn ($a) => [$a->id => $this->goalsArray($a)]),
            'contact'      => new Contact([
                'contact_at'       => now()->format('Y-m-d\TH:i'),
                'contact_type'     => 'in_person',
                'cpt_code'         => 'T1017',
                'place_of_service' => '12',
                'units'            => 1,
            ]),
            'caseManagers' => Employee::where('active', true)->orderBy('last_name')->get(),
            'types'        => Contact::CONTACT_TYPES,
        ]);
    }

    public function storeAny(Request $request): RedirectResponse
    {
        $request->validate(['tcm_admission_id' => ['required', 'exists:tcm_admissions,id']]);
        $admission = Admission::findOrFail($request->input('tcm_admission_id'));
        abort_unless($admission->hasSignedTreatmentPlan(), 403, 'A signed service plan is required before logging contacts.');

        $data = $this->validated($request);
        $data['tcm_admission_id'] = $admission->id;
        DB::transaction(function () use ($data) {
            $contact = Contact::create($data);
            $this->syncServiceLog($contact);
        });

        return redirect()->route('clinical.tcm.contacts.index')->with('status', 'Contact recorded.');
    }

    private function admissionOptions()
    {
        return Admission::query()
            ->where('status', '!=', 'discharged')
            ->whereHas('treatmentPlans', fn ($q) => $q->where('is_signed', true))
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

    public function show(Admission $admission, Contact $contact): View
    {
        abort_if($contact->tcm_admission_id !== $admission->id, 404);
        $contact->load(['admission.patient', 'caseManager']);
        return view('clinical.tcm.contacts.show', [
            'admission' => $admission,
            'contact'   => $contact,
            'types'     => Contact::CONTACT_TYPES,
        ]);
    }

    public function create(Admission $admission): View|RedirectResponse
    {
        if (! $admission->hasSignedTreatmentPlan()) {
            return redirect()
                ->route('clinical.tcm.treatment_plans.create', ['admission_id' => $admission->id])
                ->with('error', 'Complete and sign a service plan before logging contacts.');
        }

        return view('clinical.tcm.contacts.form', [
            'admission'        => $admission->load('patient'),
            'goalsByAdmission' => [$admission->id => $this->goalsArray($admission)],
            'contact'      => new Contact([
                'contact_at'       => now()->format('Y-m-d\TH:i'),
                'contact_type'     => 'in_person',
                'cpt_code'         => 'T1017',
                'place_of_service' => '12',
                'units'            => 1,
                'case_manager_id'  => $admission->case_manager_id,
            ]),
            'caseManagers' => Employee::where('active', true)->orderBy('last_name')->get(),
            'types'        => Contact::CONTACT_TYPES,
        ]);
    }

    public function store(Request $request, Admission $admission): RedirectResponse
    {
        abort_unless($admission->hasSignedTreatmentPlan(), 403, 'A signed service plan is required before logging contacts.');

        $data = $this->validated($request);
        $data['tcm_admission_id'] = $admission->id;
        DB::transaction(function () use ($data) {
            $contact = Contact::create($data);
            $this->syncServiceLog($contact);
        });
        return redirect()->route('clinical.tcm.admissions.show', $admission)->with('status', 'Contact recorded.');
    }

    public function edit(Admission $admission, Contact $contact): View
    {
        return view('clinical.tcm.contacts.form', [
            'admission'        => $admission->load('patient'),
            'goalsByAdmission' => [$admission->id => $this->goalsArray($admission)],
            'contact'      => $contact,
            'caseManagers' => Employee::where('active', true)->orderBy('last_name')->get(),
            'types'        => Contact::CONTACT_TYPES,
        ]);
    }

    public function update(Request $request, Admission $admission, Contact $contact): RedirectResponse
    {
        DB::transaction(function () use ($request, $contact) {
            $contact->update($this->validated($request));
            $this->syncServiceLog($contact->fresh());
        });
        return redirect()->route('clinical.tcm.admissions.show', $admission)->with('status', 'Contact updated.');
    }

    public function destroy(Admission $admission, Contact $contact): RedirectResponse
    {
        DB::transaction(function () use ($contact) {
            $log = ServiceLog::where('tcm_contact_id', $contact->id)->first();
            $authId = $log?->tcm_authorization_id;
            $log?->delete();
            $contact->delete();
            if ($authId) Authorization::find($authId)?->recalcUnitsUsed();
        });
        return redirect()->route('clinical.tcm.admissions.show', $admission)->with('status', 'Contact deleted.');
    }

    /**
     * Mirror a care contact into the billable service log so it flows into the superbill.
     * One log per contact (keyed by tcm_contact_id); links to the admission's active
     * authorization when present so used-unit counters stay in sync.
     */
    private function syncServiceLog(Contact $contact): void
    {
        $admission = $contact->admission;
        $managerId = $contact->case_manager_id ?? $admission->case_manager_id;
        if (! $managerId) return; // service log requires a rendering case manager

        $auth = $admission->authorizations()
            ->where('status', 'approved')->latest('id')->first()
            ?? $admission->authorizations()->latest('id')->first();

        ServiceLog::updateOrCreate(
            ['tcm_contact_id' => $contact->id],
            [
                'client_id'             => $admission->client_id,
                'patient_id'            => $admission->patient_id,
                'tcm_admission_id'      => $admission->id,
                'service_date'          => optional($contact->contact_at)->toDateString(),
                'units'                 => $contact->units,
                'cpt_code'              => $contact->cpt_code,
                'place_of_service'      => $contact->place_of_service,
                'diagnosis_code'        => $admission->diagnosis_code,
                'diagnosis_description' => $admission->diagnosis_description,
                'case_manager_id'       => $managerId,
                'tcm_authorization_id'  => $auth?->id,
                'auth_number'           => $auth?->auth_number,
                'has_contact_note'      => filled($contact->summary),
                'created_by'            => auth()->id(),
            ]
        );

        $auth?->recalcUnitsUsed();
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'case_manager_id'  => ['nullable', 'exists:employees,id'],
            'contact_at'       => ['required', 'date'],
            'contact_type'     => ['required', Rule::in(array_keys(Contact::CONTACT_TYPES))],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'cpt_code'         => ['required', 'string', 'max:10'],
            'units'            => ['required', 'integer', 'min:1'],
            'place_of_service' => ['required', 'string', 'max:4'],
            'with_whom'        => ['nullable', 'string', 'max:200'],
            'goals_addressed'  => ['nullable', 'string'],
            'summary'          => ['nullable', 'string'],
            'next_actions'     => ['nullable', 'string'],
        ]);

        // Units are derived from duration ÷ the CPT's per-unit minutes (e.g. T1017 = 15 min/unit).
        $basis = self::UNIT_MINUTES[$data['cpt_code']] ?? null;
        if ($basis && ! empty($data['duration_minutes'])) {
            $data['units'] = max(1, (int) round($data['duration_minutes'] / $basis));
        }

        return $data;
    }
}
