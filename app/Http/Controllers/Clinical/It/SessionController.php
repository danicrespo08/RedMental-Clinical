<?php

namespace App\Http\Controllers\Clinical\It;

use App\Http\Controllers\Controller;
use App\Models\Hhrr\Employee;
use App\Models\It\Admission;
use App\Models\It\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function show(Admission $admission, Session $session): View
    {
        abort_if($session->it_admission_id !== $admission->id, 404);
        $session->load(['admission.patient', 'therapist']);
        return view('clinical.it.sessions.show', compact('admission', 'session'));
    }

    public function create(Admission $admission): View
    {
        return view('clinical.it.sessions.form', [
            'admission'  => $admission->load('patient'),
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
        $data = $this->validated($request);
        $data['it_admission_id'] = $admission->id;
        Session::create($data);
        return redirect()->route('clinical.it.admissions.show', $admission)->with('status', 'Session recorded.');
    }

    public function edit(Admission $admission, Session $session): View
    {
        return view('clinical.it.sessions.form', [
            'admission'  => $admission->load('patient'),
            'session'    => $session,
            'therapists' => Employee::where('active', true)->orderBy('last_name')->get(),
        ]);
    }

    public function update(Request $request, Admission $admission, Session $session): RedirectResponse
    {
        $session->update($this->validated($request));
        return redirect()->route('clinical.it.admissions.show', $admission)->with('status', 'Session updated.');
    }

    public function destroy(Admission $admission, Session $session): RedirectResponse
    {
        $session->delete();
        return redirect()->route('clinical.it.admissions.show', $admission)->with('status', 'Session deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
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
    }
}
