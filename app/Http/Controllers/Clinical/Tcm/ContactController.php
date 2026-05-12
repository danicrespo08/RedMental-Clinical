<?php

namespace App\Http\Controllers\Clinical\Tcm;

use App\Http\Controllers\Controller;
use App\Models\Hhrr\Employee;
use App\Models\Tcm\Admission;
use App\Models\Tcm\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function create(Admission $admission): View
    {
        return view('clinical.tcm.contacts.form', [
            'admission'    => $admission->load('patient'),
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
        $data = $this->validated($request);
        $data['tcm_admission_id'] = $admission->id;
        Contact::create($data);
        return redirect()->route('clinical.tcm.admissions.show', $admission)->with('status', 'Contact recorded.');
    }

    public function edit(Admission $admission, Contact $contact): View
    {
        return view('clinical.tcm.contacts.form', [
            'admission'    => $admission->load('patient'),
            'contact'      => $contact,
            'caseManagers' => Employee::where('active', true)->orderBy('last_name')->get(),
            'types'        => Contact::CONTACT_TYPES,
        ]);
    }

    public function update(Request $request, Admission $admission, Contact $contact): RedirectResponse
    {
        $contact->update($this->validated($request));
        return redirect()->route('clinical.tcm.admissions.show', $admission)->with('status', 'Contact updated.');
    }

    public function destroy(Admission $admission, Contact $contact): RedirectResponse
    {
        $contact->delete();
        return redirect()->route('clinical.tcm.admissions.show', $admission)->with('status', 'Contact deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
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
    }
}
