<?php

namespace App\Http\Controllers\Clinical\Psr;

use App\Http\Controllers\Controller;
use App\Models\Hhrr\Clinic;
use App\Models\Hhrr\Employee;
use App\Models\Psr\Admission;
use App\Models\Psr\GroupSession;
use App\Models\Psr\GroupSessionAttendee;
use App\Models\Psr\Authorization;
use App\Models\Psr\ServiceLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * PSR Group Session controller.
 *
 * Schedules and runs group therapy sessions: lead/co-therapist, capacity,
 * service code/modifier/POS, lunch break tracking, structured activities,
 * attendees with per-attendee schedule segments, units, participation, and
 * individual notes. Each attendee row generates a service-log row downstream.
 */
class PsrGroupSessionController extends Controller
{
    public function index(Request $request): View
    {
        $search       = trim((string) $request->query('search', ''));
        $status       = $request->query('status');
        $clinicFilter = $request->query('clinic_id');
        $therapistId  = $request->query('therapist_id');
        $dateFrom     = $request->query('date_from');
        $dateTo       = $request->query('date_to');

        $sessions = GroupSession::query()
            ->with(['clinic', 'leadTherapist', 'coTherapist', 'attendees'])
            ->withCount('attendees')
            ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                $w->where('title', 'like', "%{$search}%")
                  ->orWhere('service_code', 'like', "%{$search}%")
                  ->orWhereHas('clinic', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            }))
            ->when($status,       fn ($q) => $q->where('status', $status))
            ->when($clinicFilter, fn ($q) => $q->where('clinic_id', $clinicFilter))
            ->when($therapistId,  fn ($q) => $q->where(function ($w) use ($therapistId) {
                $w->where('lead_therapist_id', $therapistId)->orWhere('co_therapist_id', $therapistId);
            }))
            ->when($dateFrom, fn ($q) => $q->whereDate('session_date', '>=', $dateFrom))
            ->when($dateTo,   fn ($q) => $q->whereDate('session_date', '<=', $dateTo))
            ->orderByDesc('session_date')->orderByDesc('start_time')
            ->paginate(20)
            ->withQueryString();

        $today     = now()->startOfDay();
        $weekStart = now()->startOfWeek();
        $weekEnd   = now()->endOfWeek();

        $todayCounts = GroupSession::whereDate('session_date', $today)
            ->selectRaw("COUNT(*) AS total, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed")
            ->first();

        $patientsThisWeek = \App\Models\Psr\GroupSessionAttendee::query()
            ->whereHas('session', fn ($q) => $q->whereBetween('session_date', [$weekStart, $weekEnd]))
            ->where('attendance_status', 'present')
            ->distinct('patient_id')
            ->count('patient_id');

        $stats = [
            'today'              => (int) ($todayCounts->total ?? 0),
            'completed_today'    => (int) ($todayCounts->completed ?? 0),
            'week'               => GroupSession::whereBetween('session_date', [$weekStart, $weekEnd])->count(),
            'patients_this_week' => $patientsThisWeek,
        ];

        return view('clinical.psr.group-sessions.index', [
            'sessions'         => $sessions,
            'statuses'         => GroupSession::STATUSES,
            'filterClinics'    => Clinic::where('active', true)->orderBy('name')->get(),
            'filterTherapists' => Employee::where('active', true)->where('is_provider', true)->orderBy('last_name')->get(),
            'stats'            => $stats,
            'search'           => $search,
            'status'           => $status,
            'clinicFilter'     => $clinicFilter,
            'therapistId'      => $therapistId,
            'dateFrom'         => $dateFrom,
            'dateTo'           => $dateTo,
        ]);
    }

    public function create(): View
    {
        return view('clinical.psr.group-sessions.form', $this->formViewData(new GroupSession([
            'session_date'     => now()->toDateString(),
            'start_time'       => '09:00',
            'end_time'         => '13:00',
            'service_code'     => 'H2017',
            'place_of_service' => '11',
            'session_type'     => 'group_therapy',
            'max_capacity'     => 10,
            'status'           => 'scheduled',
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = auth()->id();

        $session = DB::transaction(function () use ($data, $request) {
            $session = GroupSession::create($data);
            $this->syncAttendees($session, $request->input('attendees', []));
            return $session;
        });

        return redirect()
            ->route('clinical.psr.group_sessions.show', $session)
            ->with('status', 'Group session created.');
    }

    public function show(GroupSession $groupSession): View
    {
        $groupSession->load([
            'clinic', 'leadTherapist', 'coTherapist',
            'attendees.patient', 'attendees.admission',
            'progressNotes',
        ]);
        return view('clinical.psr.group-sessions.show', ['session' => $groupSession]);
    }

    public function edit(GroupSession $groupSession): View
    {
        $groupSession->load(['attendees.patient', 'attendees.admission']);
        return view('clinical.psr.group-sessions.form', $this->formViewData($groupSession));
    }

    public function update(Request $request, GroupSession $groupSession): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($groupSession, $data, $request) {
            $groupSession->update($data);
            $this->syncAttendees($groupSession, $request->input('attendees', []));
        });
        return redirect()
            ->route('clinical.psr.group_sessions.show', $groupSession)
            ->with('status', 'Group session updated.');
    }

    public function destroy(GroupSession $groupSession): RedirectResponse
    {
        $groupSession->delete();
        return redirect()
            ->route('clinical.psr.group_sessions.index')
            ->with('status', 'Group session deleted.');
    }

    private function formViewData(GroupSession $session): array
    {
        return [
            'session'    => $session,
            'clinics'    => Clinic::where('active', true)->orderBy('name')->get(),
            'therapists' => Employee::where('active', true)->where('is_provider', true)->orderBy('last_name')->get(),
            'admissions' => Admission::where('status', 'admitted')->with('patient')->get(),
            'statuses'   => GroupSession::STATUSES,
            'attendance' => GroupSessionAttendee::ATTENDANCE,
        ];
    }

    private function validated(Request $request): array
    {
        foreach (['co_therapist_id', 'modifier', 'break_start_time', 'break_end_time', 'session_summary', 'notes'] as $f) {
            if ($request->input($f) === '') $request->merge([$f => null]);
        }
        return $request->validate([
            'clinic_id'        => ['required', 'exists:clinics,id'],
            'session_date'     => ['required', 'date'],
            'start_time'       => ['required', 'date_format:H:i'],
            'end_time'         => ['required', 'date_format:H:i', 'after:start_time'],
            'title'            => ['required', 'string', 'max:255'],
            'session_type'     => ['required', 'string', 'max:50'],
            'service_code'     => ['required', 'string', 'max:20'],
            'modifier'         => ['nullable', 'string', 'max:20'],
            'place_of_service' => ['required', 'string', 'max:10'],
            'lead_therapist_id'=> ['required', 'exists:employees,id'],
            'co_therapist_id'  => ['nullable', 'exists:employees,id'],
            'max_capacity'     => ['required', 'integer', 'min:1', 'max:50'],
            'break_start_time' => ['nullable', 'date_format:H:i'],
            'break_end_time'   => ['nullable', 'date_format:H:i', 'after_or_equal:break_start_time'],
            'break_minutes'    => ['nullable', 'integer', 'min:0', 'max:240'],
            'activities'       => ['nullable', 'array'],
            'session_summary'  => ['nullable', 'string'],
            'notes'            => ['nullable', 'string'],
            'status'           => ['required', Rule::in(array_keys(GroupSession::STATUSES))],
        ]);
    }

    private function syncAttendees(GroupSession $session, array $attendees): void
    {
        $kept = [];
        foreach ($attendees as $a) {
            if (empty($a['include']) && empty($a['id'])) continue;
            if (empty($a['psr_admission_id'])) continue;

            $admission = Admission::find($a['psr_admission_id']);
            if (! $admission) continue;

            $payload = [
                'psr_admission_id'    => $admission->id,
                'patient_id'          => $admission->patient_id,
                'attendance_status'   => $a['attendance_status'] ?? 'present',
                'check_in_time'       => $a['check_in_time']  ?? null,
                'check_out_time'      => $a['check_out_time'] ?? null,
                'units'               => (int) ($a['units'] ?? 4),
                'participation_level' => $a['participation_level'] ?? null,
                'individual_notes'    => $a['individual_notes'] ?? null,
                'created_by'          => auth()->id(),
            ];
            $row = !empty($a['id'])
                ? tap(GroupSessionAttendee::where('id', $a['id'])->where('psr_group_session_id', $session->id)->firstOrFail())->update($payload)
                : $session->attendees()->create($payload);
            $kept[] = $row->id;
            $this->syncServiceLog($session, $row, $admission);
        }
        // Drop attendees (and their billable logs) that are no longer on the roster.
        $removed = $session->attendees()->whereNotIn('id', $kept ?: [0])->pluck('id');
        if ($removed->isNotEmpty()) {
            ServiceLog::whereIn('psr_group_session_attendee_id', $removed)->delete();
            $session->attendees()->whereIn('id', $removed)->delete();
        }
    }

    /**
     * Mirror a group-session attendee into the billable service log so it flows into
     * the superbill. One log per attendee (unique psr_group_session_attendee_id);
     * links to the admission's active authorization to keep used-unit counters in sync.
     */
    private function syncServiceLog(GroupSession $session, GroupSessionAttendee $attendee, Admission $admission): void
    {
        $therapistId = $session->lead_therapist_id;
        if (! $therapistId) return;

        $auth = $admission->authorizations()
            ->where('status', 'approved')->latest('id')->first()
            ?? $admission->authorizations()->latest('id')->first();

        ServiceLog::updateOrCreate(
            ['psr_group_session_attendee_id' => $attendee->id],
            [
                'client_id'             => $session->client_id,
                'clinic_id'             => $session->clinic_id,
                'patient_id'            => $admission->patient_id,
                'psr_admission_id'      => $admission->id,
                'service_date'          => $session->session_date,
                'start_time'            => $attendee->check_in_time ?? $session->start_time,
                'end_time'              => $attendee->check_out_time ?? $session->end_time,
                'units'                 => $attendee->units,
                'service_code'          => $session->service_code,
                'modifier'              => $session->modifier,
                'place_of_service'      => $session->place_of_service,
                'diagnosis_code'        => $admission->primary_dx_code,
                'diagnosis_description' => $admission->primary_dx_description,
                'therapist_id'          => $therapistId,
                'source_type'           => 'group_session',
                'psr_group_session_id'  => $session->id,
                'psr_authorization_id'  => $auth?->id,
                'auth_number'           => $auth?->auth_number,
                'has_progress_note'     => false,
                'created_by'            => auth()->id(),
            ]
        );

        $auth?->recalcUnitsUsed();
    }
}
