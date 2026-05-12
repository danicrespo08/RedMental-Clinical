@extends('layouts.app')
@section('title', 'PSR — ' . ($admission->patient?->full_name ?? 'Admission'))

@section('content')

<style>
    /* ── Document cards (clinical forms grid) ─────────────── */
    .doc-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem;
        padding: 1.1rem 1.25rem; transition: all .25s ease;
        display: flex; align-items: center; gap: .85rem;
        text-decoration: none; position: relative;
    }
    .doc-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px -6px rgba(0,0,0,.08); border-color: #93c5fd; }
    .doc-icon { width: 44px; height: 44px; border-radius: 11px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .status-dot.pulse { animation: pulse-dot 1.6s ease-in-out infinite; }
    @keyframes pulse-dot { 0%, 100% { opacity: 1 } 50% { opacity: .5 } }

    /* ── Detail panel cards ───────────────────────────────── */
    .detail-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem;
        padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,.02);
    }
    .detail-title {
        font-size: .58rem; font-weight: 800; color: #94a3b8;
        text-transform: uppercase; letter-spacing: .06em;
        margin-bottom: 1rem; display: flex; align-items: center; gap: .4rem;
    }
    .info-label { font-size: .55rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; }
    .info-value { font-size: .82rem; font-weight: 700; color: #334155; margin-top: .15rem; }

    /* ── Patient avatar (initials in indigo gradient) ─────── */
    .patient-avatar {
        width: 56px; height: 56px; border-radius: 14px;
        background: linear-gradient(135deg, #6366f1, #4338ca); color: #fff;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 1.05rem; flex-shrink: 0; letter-spacing: .02em;
        box-shadow: 0 4px 10px -2px rgba(99,102,241,.4);
    }
    .patient-avatar.discharged { background: linear-gradient(135deg, #94a3b8, #64748b); box-shadow: 0 4px 10px -2px rgba(100,116,139,.3); }
    .patient-avatar.hold       { background: linear-gradient(135deg, #fb923c, #ea580c); box-shadow: 0 4px 10px -2px rgba(234,88,12,.35); }
    .patient-avatar.pending    { background: linear-gradient(135deg, #fbbf24, #d97706); box-shadow: 0 4px 10px -2px rgba(217,119,6,.35); }

    /* ── Header card with subtle accent ───────────────────── */
    .hero-card {
        background:
            radial-gradient(circle at 100% 0, rgba(99,102,241,.08), transparent 50%),
            radial-gradient(circle at 0 100%, rgba(56,189,248,.06), transparent 50%),
            #fff;
        border: 1px solid #e2e8f0;
    }

    /* ── Compliance ribbon ────────────────────────────────── */
    .compl-bar {
        height: 8px; border-radius: 99px;
        background: #f1f5f9; overflow: hidden;
    }
    .compl-bar > div { height: 100%; border-radius: 99px; transition: width .4s ease; }
    .compl-fill-100 { background: linear-gradient(90deg, #10b981, #059669); }
    .compl-fill-75  { background: linear-gradient(90deg, #34d399, #10b981); }
    .compl-fill-50  { background: linear-gradient(90deg, #fbbf24, #f59e0b); }
    .compl-fill-25  { background: linear-gradient(90deg, #fb923c, #ea580c); }
    .compl-fill-0   { background: #cbd5e1; }
</style>

@php
    $statusColors = [
        'admitted'        => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'on_hold'         => 'bg-orange-50 text-orange-700 border-orange-200',
        'pending_intake'  => 'bg-amber-50 text-amber-700 border-amber-200',
        'intake_complete' => 'bg-blue-50 text-blue-700 border-blue-200',
        'discharged'      => 'bg-slate-100 text-slate-500 border-slate-200',
    ];
    $statusDots = [
        'admitted' => 'bg-emerald-400 pulse',
        'on_hold'  => 'bg-orange-400 pulse',
        'pending_intake'  => 'bg-amber-400 pulse',
        'intake_complete' => 'bg-blue-400',
        'discharged' => 'bg-slate-400',
    ];
    $avatarClass = match($admission->status) {
        'discharged'     => 'discharged',
        'on_hold'        => 'hold',
        'pending_intake' => 'pending',
        default          => '',
    };
    $initials = strtoupper(
        mb_substr($admission->patient?->first_name ?? '?', 0, 1) .
        mb_substr($admission->patient?->last_name  ?? '?', 0, 1)
    );

    $bio  = $admission->bioAssessment;
    $plan = $admission->treatmentPlans->sortByDesc('start_date')->first();
    $latestFars   = $admission->farsAssessments->sortByDesc('evaluation_date')->first();
    $intake       = $admission->intake;
    $activeAuth   = $admission->authorizations->first(fn ($a) => $a->status === 'approved'
        && (is_null($a->approved_end_date) || $a->approved_end_date->isFuture()));

    $checks = [
        ['key' => 'intake', 'label' => 'Intake form',          'icon' => 'clipboard-list',
         'state' => ! $intake ? 'pending' : ($intake->is_signed ? 'signed' : 'draft'),
         'href'  => '#'],
        ['key' => 'bio', 'label' => 'Bio-psychosocial',        'icon' => 'brain',
         'state' => ! $bio ? 'pending' : ($bio->is_signed ? 'signed' : 'draft'),
         'href'  => $bio
                    ? route('clinical.psr.assessments.edit', $bio)
                    : route('clinical.psr.assessments.create', ['admission_id' => $admission->id])],
        ['key' => 'mtp', 'label' => 'Treatment plan',          'icon' => 'file-check-2',
         'state' => ! $plan ? 'pending' : ($plan->is_signed ? 'signed' : 'draft'),
         'href'  => $plan
                    ? route('clinical.psr.treatment_plans.edit', $plan)
                    : route('clinical.psr.treatment_plans.create', ['admission_id' => $admission->id])],
        ['key' => 'fars', 'label' => 'FARS',                   'icon' => 'gauge',
         'state' => ! $latestFars ? 'pending' : ($latestFars->is_signed ? 'signed' : 'draft'),
         'href'  => route('clinical.psr.assessments.fars.create', $admission)],
        ['key' => 'auth', 'label' => 'Authorization',          'icon' => 'key-round',
         'state' => $activeAuth ? 'signed' : ($admission->authorizations->isEmpty() ? 'pending' : 'draft'),
         'href'  => $admission->authorizations->isNotEmpty()
                    ? route('clinical.psr.authorizations.show', $admission->authorizations->first())
                    : route('clinical.psr.authorizations.create', ['admission_id' => $admission->id])],
    ];

    $signedCount = collect($checks)->where('state', 'signed')->count();
    $totalChecks = count($checks);
    $pct         = (int) round(($signedCount / $totalChecks) * 100);
    $fillClass   = $pct === 100 ? 'compl-fill-100'
        : ($pct >= 75 ? 'compl-fill-75'
        : ($pct >= 50 ? 'compl-fill-50'
        : ($pct >= 25 ? 'compl-fill-25' : 'compl-fill-0')));

    // Stats
    $totalSessionsAttended = $admission->groupSessionAttendances->where('attendance_status', 'present')->count();
    $totalProgressNotes    = $admission->progressNotes->count();
    $totalServiceLogs      = $admission->serviceLogs->count();
    $totalUnits            = $admission->serviceLogs->sum('units');
@endphp

<div class="max-w-7xl mx-auto">

    <div class="hero-card rounded-2xl p-5 mb-5 shadow-sm">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-3.5 min-w-0">
                <a href="{{ route('clinical.psr.admissions.index') }}"
                   class="w-9 h-9 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors border border-slate-200 flex-shrink-0">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div class="patient-avatar {{ $avatarClass }}">{{ $initials }}</div>
                <div class="min-w-0">
                    <h1 class="text-xl font-black text-slate-800 tracking-tight truncate">{{ $admission->patient?->full_name ?? '—' }}</h1>
                    <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                        @if($admission->patient?->mrn)
                            <span class="font-mono font-bold text-[10px] bg-blue-50 text-blue-600 border border-blue-200 px-2 py-0.5 rounded-md">{{ $admission->patient->mrn }}</span>
                        @endif
                        @if($admission->patient?->date_of_birth)
                            <span class="text-slate-300">·</span>
                            <span class="text-[10px] text-slate-500 font-semibold">DOB {{ $admission->patient->date_of_birth->format('m/d/Y') }} ({{ $admission->patient->age }} y/o)</span>
                        @endif
                        <span class="text-slate-300">·</span>
                        <span class="text-[10px] text-slate-500 font-semibold inline-flex items-center gap-1"><i data-lucide="hospital" class="w-3 h-3"></i> {{ $admission->clinic?->name ?? '—' }}</span>
                        <span class="text-slate-300">·</span>
                        <span class="text-[10px] text-slate-500 font-semibold inline-flex items-center gap-1"><i data-lucide="calendar" class="w-3 h-3"></i> Admitted {{ optional($admission->admission_date)->format('M j, Y') ?? '—' }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <div class="w-2 h-2 rounded-full {{ $statusDots[$admission->status] ?? 'bg-slate-400' }}"></div>
                <span class="px-3.5 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider border {{ $statusColors[$admission->status] ?? '' }}">
                    {{ $admission->status_label }}
                </span>
                @can('clinical.psr.admissions.edit')
                    <a href="{{ route('clinical.psr.admissions.edit', $admission) }}"
                       class="ml-1 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 text-[10px] font-bold uppercase tracking-wider rounded-lg border border-amber-200 inline-flex items-center gap-1.5 transition">
                        <i data-lucide="pencil" class="w-3 h-3"></i> Edit
                    </a>
                @endcan
            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-slate-100">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Documentation completion</span>
                <span class="text-xs font-black {{ $pct === 100 ? 'text-emerald-600' : ($pct >= 50 ? 'text-amber-600' : 'text-rose-600') }}">{{ $signedCount }} / {{ $totalChecks }} signed · {{ $pct }}%</span>
            </div>
            <div class="compl-bar">
                <div class="{{ $fillClass }}" style="width: {{ $pct }}%"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        <div class="detail-card flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center"><i data-lucide="user-check" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800 leading-none">{{ $totalSessionsAttended }}</div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Sessions attended</div>
            </div>
        </div>
        <div class="detail-card flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center"><i data-lucide="file-text" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800 leading-none">{{ $totalProgressNotes }}</div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Progress notes</div>
            </div>
        </div>
        <div class="detail-card flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center"><i data-lucide="receipt" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800 leading-none">{{ number_format($totalUnits) }}</div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Units billed</div>
            </div>
        </div>
        <div class="detail-card flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center"><i data-lucide="calendar-clock" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800 leading-none">{{ $admission->days_in_program ?? 0 }}</div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Days in program</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

        <div class="lg:col-span-4 space-y-4">

            {{-- Admission details --}}
            <div class="detail-card">
                <h3 class="detail-title">
                    <i data-lucide="clipboard-list" class="w-3.5 h-3.5 text-blue-500"></i> Admission details
                </h3>
                <div class="space-y-3">
                    <div>
                        <div class="info-label">Assigned therapist</div>
                        <div class="info-value">{{ $admission->assignedTherapist?->full_name ?? 'Not assigned' }}</div>
                        @if($admission->assignedTherapist?->position)
                            <div class="text-[10px] text-slate-400 font-medium">{{ $admission->assignedTherapist->position }}</div>
                        @endif
                    </div>
                    <div class="pt-3 border-t border-slate-100">
                        <div class="info-label">Referring provider</div>
                        <div class="info-value">{{ $admission->referringProvider?->full_name ?? 'Not assigned' }}</div>
                        @if($admission->referringProvider?->npi)
                            <div class="text-[10px] text-slate-400 font-mono">NPI: {{ $admission->referringProvider->npi }}</div>
                        @endif
                    </div>
                    @if($admission->referral_date)
                        <div class="pt-3 border-t border-slate-100">
                            <div class="info-label">Referred</div>
                            <div class="info-value">{{ $admission->referral_date->format('M j, Y') }}</div>
                        </div>
                    @endif
                    <div class="pt-3 border-t border-slate-100">
                        <div class="info-label">Primary diagnosis</div>
                        <div class="info-value">
                            <span class="font-mono">{{ $admission->primary_dx_code ?: '—' }}</span>
                            @if($admission->primary_dx_description)
                                <div class="text-[10px] text-slate-400 font-medium mt-0.5">{{ $admission->primary_dx_description }}</div>
                            @endif
                        </div>
                    </div>
                    @if($admission->secondary_dx_code)
                        <div class="pt-3 border-t border-slate-100">
                            <div class="info-label">Secondary diagnosis</div>
                            <div class="info-value">
                                <span class="font-mono">{{ $admission->secondary_dx_code }}</span>
                                @if($admission->secondary_dx_description)
                                    <div class="text-[10px] text-slate-400 font-medium mt-0.5">{{ $admission->secondary_dx_description }}</div>
                                @endif
                            </div>
                        </div>
                    @endif
                    <div class="pt-3 border-t border-slate-100">
                        <div class="info-label">Default place of service</div>
                        <div class="info-value font-mono">{{ $admission->default_shift_pos ?: '—' }}</div>
                    </div>
                </div>
            </div>

            {{-- Patient quick info --}}
            <div class="detail-card">
                <h3 class="detail-title">
                    <i data-lucide="user" class="w-3.5 h-3.5 text-violet-500"></i> Patient info
                </h3>
                <div class="space-y-2">
                    <div class="flex justify-between text-[11px]"><span class="text-slate-400 font-bold">Phone</span><span class="font-bold text-slate-700">{{ $admission->patient?->phone ?: '—' }}</span></div>
                    <div class="flex justify-between text-[11px]"><span class="text-slate-400 font-bold">Email</span><span class="font-bold text-slate-700 truncate ml-2">{{ $admission->patient?->email ?: '—' }}</span></div>
                    <div class="flex justify-between text-[11px]"><span class="text-slate-400 font-bold">Language</span><span class="font-bold text-slate-700">{{ $admission->patient?->preferred_language ?: '—' }}</span></div>
                    <div class="flex justify-between text-[11px]"><span class="text-slate-400 font-bold">City / State</span><span class="font-bold text-slate-700">{{ $admission->patient?->city ?: '—' }}, {{ $admission->patient?->state ?: '—' }}</span></div>
                    @if($admission->patient?->emergency_contact_name)
                        <div class="flex justify-between text-[11px] pt-2 border-t border-slate-100"><span class="text-slate-400 font-bold">Emergency</span><span class="font-bold text-slate-700 truncate ml-2">{{ $admission->patient->emergency_contact_name }}</span></div>
                        <div class="flex justify-between text-[11px]"><span class="text-slate-400 font-bold">Emerg. phone</span><span class="font-bold text-slate-700">{{ $admission->patient->emergency_contact_phone ?: '—' }}</span></div>
                    @endif
                    <div class="pt-2">
                        <a href="{{ route('hhrr.patients.show', $admission->patient) }}"
                           class="w-full py-2 bg-slate-50 hover:bg-blue-50 text-slate-500 hover:text-blue-600 border border-slate-200 hover:border-blue-200 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all flex items-center justify-center gap-1.5">
                            <i data-lucide="external-link" class="w-3 h-3"></i> View full profile
                        </a>
                    </div>
                </div>
            </div>

            {{-- Risk meter --}}
            @if($admission->risk_score !== null)
                <div class="detail-card">
                    <h3 class="detail-title">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-rose-500"></i> Risk score
                    </h3>
                    @php
                        $rs = (int) $admission->risk_score;
                        $rsColor = $rs >= 80 ? 'text-rose-600' : ($rs >= 60 ? 'text-amber-600' : ($rs >= 40 ? 'text-blue-600' : 'text-emerald-600'));
                        $rsBar   = $rs >= 80 ? 'bg-rose-500' : ($rs >= 60 ? 'bg-amber-500' : ($rs >= 40 ? 'bg-blue-500' : 'bg-emerald-500'));
                    @endphp
                    <div class="flex items-end justify-between mb-1.5">
                        <div class="text-3xl font-black {{ $rsColor }} leading-none">{{ $rs }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest pb-0.5">/ 200</div>
                    </div>
                    <div class="compl-bar"><div class="{{ $rsBar }}" style="width: {{ min(100, $rs / 2) }}%"></div></div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-2">
                        {{ $rs >= 80 ? 'Critical — review urgently' : ($rs >= 60 ? 'Elevated risk' : ($rs >= 40 ? 'Moderate' : 'Low')) }}
                    </div>
                </div>
            @endif

            {{-- Status transitions --}}
            @can('clinical.psr.admissions.edit')
                <div class="detail-card">
                    <h3 class="detail-title">
                        <i data-lucide="repeat" class="w-3.5 h-3.5 text-blue-500"></i> Quick actions
                    </h3>
                    <form method="POST" action="{{ route('clinical.psr.admissions.transition', $admission) }}" class="flex gap-2">
                        @csrf
                        <select name="status" class="flex-1 px-3 py-2 border border-slate-300 rounded-lg text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach(\App\Models\Psr\Admission::STATUSES as $k => $v)
                                <option value="{{ $k }}" @selected($admission->status === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                        <button class="px-3 py-2 bg-slate-800 hover:bg-slate-900 text-white text-[10px] font-black rounded-lg uppercase tracking-wider">Apply</button>
                    </form>
                </div>
            @endcan
        </div>

        <div class="lg:col-span-8 space-y-5">

            {{-- Clinical documentation grid --}}
            <div>
                <h2 class="text-[11px] font-black text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i data-lucide="file-text" class="w-3.5 h-3.5 text-blue-500"></i> Clinical documentation
                    <span class="flex-1 h-px bg-slate-200 mx-2"></span>
                    <span class="text-[10px] text-slate-400 normal-case font-semibold">{{ $signedCount }}/{{ $totalChecks }} signed</span>
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($checks as $c)
                        @php
                            $iconClass = match($c['state']) {
                                'signed'  => 'bg-emerald-50 text-emerald-500',
                                'draft'   => 'bg-amber-50 text-amber-500',
                                default   => 'bg-slate-50 text-slate-300',
                            };
                            $dotClass = match($c['state']) {
                                'signed'  => 'bg-emerald-400',
                                'draft'   => 'bg-amber-400',
                                default   => 'bg-slate-200',
                            };
                            $stateLabel = match($c['state']) {
                                'signed'  => 'Completed & signed',
                                'draft'   => 'Draft — needs signature',
                                default   => 'Not started',
                            };
                            $stateText = match($c['state']) {
                                'signed'  => 'text-emerald-600',
                                'draft'   => 'text-amber-600',
                                default   => 'text-slate-400',
                            };
                        @endphp
                        <a href="{{ $c['href'] }}" class="doc-card">
                            <div class="doc-icon {{ $iconClass }}">
                                <i data-lucide="{{ $c['icon'] }}" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-black text-slate-800 text-sm">{{ $c['label'] }}</div>
                                <div class="text-[10px] font-bold uppercase tracking-wider mt-0.5 {{ $stateText }}">{{ $stateLabel }}</div>
                            </div>
                            <div class="status-dot {{ $dotClass }}"></div>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- More clinical sub-records (2nd row) --}}
            <div>
                <h2 class="text-[11px] font-black text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i data-lucide="layers" class="w-3.5 h-3.5 text-violet-500"></i> Records &amp; activity
                    <span class="flex-1 h-px bg-slate-200 mx-2"></span>
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <a href="{{ route('clinical.psr.progress_notes.create', ['admission_id' => $admission->id]) }}" class="doc-card">
                        <div class="doc-icon bg-blue-50 text-blue-600"><i data-lucide="file-text" class="w-5 h-5"></i></div>
                        <div class="flex-1 min-w-0">
                            <div class="font-black text-slate-800 text-sm leading-tight">Progress notes</div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-0.5">{{ $totalProgressNotes }} on file</div>
                        </div>
                    </a>
                    <a href="{{ route('clinical.psr.service_log.create', ['admission_id' => $admission->id]) }}" class="doc-card">
                        <div class="doc-icon bg-violet-50 text-violet-600"><i data-lucide="list" class="w-5 h-5"></i></div>
                        <div class="flex-1 min-w-0">
                            <div class="font-black text-slate-800 text-sm leading-tight">Service log</div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-0.5">{{ $totalServiceLogs }} entries</div>
                        </div>
                    </a>
                    @if($admission->dischargeSummary)
                        <a href="{{ route('clinical.psr.discharges.show', $admission->dischargeSummary) }}" class="doc-card">
                            <div class="doc-icon bg-rose-50 text-rose-600"><i data-lucide="door-open" class="w-5 h-5"></i></div>
                            <div class="flex-1 min-w-0">
                                <div class="font-black text-slate-800 text-sm leading-tight">Discharge</div>
                                <div class="text-[10px] font-bold text-rose-500 uppercase tracking-wider mt-0.5">{{ $admission->dischargeSummary->discharge_date->format('M j, Y') }}</div>
                            </div>
                        </a>
                    @else
                        <a href="{{ route('clinical.psr.discharges.create', ['admission_id' => $admission->id]) }}" class="doc-card">
                            <div class="doc-icon bg-slate-50 text-slate-400"><i data-lucide="door-open" class="w-5 h-5"></i></div>
                            <div class="flex-1 min-w-0">
                                <div class="font-black text-slate-800 text-sm leading-tight">Discharge</div>
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-0.5">Not yet discharged</div>
                            </div>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Recent progress notes --}}
            @if($admission->progressNotes->count())
                <div class="detail-card p-0 overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-[11px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-2">
                            <i data-lucide="file-text" class="w-3.5 h-3.5 text-blue-500"></i> Recent progress notes
                        </h3>
                        <a href="{{ route('clinical.psr.progress_notes.index', ['admission_id' => $admission->id]) }}" class="text-[10px] font-black text-blue-600 hover:underline uppercase tracking-wider">View all</a>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                            <tr><th class="px-4 py-2.5 text-left">Date</th><th class="px-4 py-2.5 text-left">Therapist</th><th class="px-4 py-2.5 text-center">Risk</th><th class="px-4 py-2.5 text-center">Status</th><th class="px-4 py-2.5"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($admission->progressNotes->take(5) as $n)
                                @php
                                    $sc = match($n->status) { 'signed'=>'bg-emerald-100 text-emerald-700', 'addendum'=>'bg-blue-100 text-blue-700', default=>'bg-amber-100 text-amber-700' };
                                    $rc = match($n->risk_level) { 'high'=>'bg-rose-100 text-rose-700', 'moderate'=>'bg-amber-100 text-amber-700', 'low'=>'bg-blue-100 text-blue-700', default=>'bg-slate-100 text-slate-600' };
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2.5 text-xs font-semibold">{{ $n->note_date->format('M j, Y') }}</td>
                                    <td class="px-4 py-2.5 text-xs">{{ $n->therapist?->full_name ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-center"><span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-black uppercase {{ $rc }}">{{ $n->risk_level }}</span></td>
                                    <td class="px-4 py-2.5 text-center"><span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-black uppercase {{ $sc }}">{{ $n->status }}</span></td>
                                    <td class="px-4 py-2.5 text-right"><a href="{{ route('clinical.psr.progress_notes.show', $n) }}" class="text-[10px] font-black text-blue-600 hover:underline uppercase tracking-wider">View</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Active authorizations --}}
            @if($admission->authorizations->count())
                <div class="detail-card p-0 overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-[11px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-2">
                            <i data-lucide="key-round" class="w-3.5 h-3.5 text-blue-500"></i> Authorizations
                        </h3>
                        <a href="{{ route('clinical.psr.authorizations.create', ['admission_id' => $admission->id]) }}" class="text-[10px] font-black text-blue-600 hover:underline uppercase tracking-wider">+ New</a>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                            <tr><th class="px-4 py-2.5 text-left">Auth #</th><th class="px-4 py-2.5 text-left">Service</th><th class="px-4 py-2.5 text-right">Units</th><th class="px-4 py-2.5 text-left">Period</th><th class="px-4 py-2.5 text-left">Status</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($admission->authorizations as $a)
                                @php $ac = match($a->status){ 'approved'=>'bg-emerald-100 text-emerald-700', 'denied'=>'bg-rose-100 text-rose-700', 'pending'=>'bg-amber-100 text-amber-700', 'expired'=>'bg-slate-100 text-slate-600', default=>'bg-slate-100 text-slate-700' } @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2.5"><a href="{{ route('clinical.psr.authorizations.show', $a) }}" class="text-xs font-mono font-bold text-blue-700 hover:underline">{{ $a->auth_number ?: '#'.$a->id }}</a></td>
                                    <td class="px-4 py-2.5 text-xs font-mono">{{ $a->service_code }} {{ $a->modifier_1 }}</td>
                                    <td class="px-4 py-2.5 text-right text-xs font-mono font-bold">{{ $a->units_used }}<span class="text-slate-400">/{{ $a->units_approved ?? '—' }}</span></td>
                                    <td class="px-4 py-2.5 text-xs text-slate-500">
                                        @if($a->approved_start_date && $a->approved_end_date)
                                            {{ $a->approved_start_date->format('M j') }} → {{ $a->approved_end_date->format('M j, Y') }}
                                        @else — @endif
                                    </td>
                                    <td class="px-4 py-2.5"><span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-black uppercase {{ $ac }}">{{ $a->status }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
</script>
@endsection
