@extends('layouts.app')
@section('title', 'PSR — Authorization ' . ($auth->auth_number ?: '#'.$auth->id))

@section('content')

@php
    $patient   = $auth->admission?->patient;
    $admission = $auth->admission;
    $payer     = $auth->payer;
    $clinic    = $auth->clinic;
    $rp        = $auth->renderingProvider;
    $sp        = $auth->supervisingProvider;

    $statusLabel = \App\Models\Psr\Authorization::STATUSES[$auth->status] ?? $auth->status;
    $statusMap = match($auth->status) {
        'pending'        => ['bg'=>'bg-amber-50','text'=>'text-amber-700','border'=>'border-amber-200','dot'=>'bg-amber-400'],
        'submitted'      => ['bg'=>'bg-blue-50','text'=>'text-blue-700','border'=>'border-blue-200','dot'=>'bg-blue-400'],
        'approved'       => ['bg'=>'bg-emerald-50','text'=>'text-emerald-700','border'=>'border-emerald-200','dot'=>'bg-emerald-400'],
        'denied'         => ['bg'=>'bg-red-50','text'=>'text-red-700','border'=>'border-red-200','dot'=>'bg-red-400'],
        'expired'        => ['bg'=>'bg-slate-100','text'=>'text-slate-600','border'=>'border-slate-300','dot'=>'bg-slate-400'],
        'pending_review' => ['bg'=>'bg-purple-50','text'=>'text-purple-700','border'=>'border-purple-200','dot'=>'bg-purple-400'],
        default          => ['bg'=>'bg-slate-50','text'=>'text-slate-500','border'=>'border-slate-200','dot'=>'bg-slate-400'],
    };

    $utilPct   = $auth->units_used_percent;
    $daysLeft  = $auth->days_to_expiry;
    $isExpired = $auth->is_expired;
    $expSoon   = $daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 30;
    $unitsLow  = $utilPct !== null && $utilPct >= 80;

    $utilColor = $utilPct === null ? '#94a3b8' : ($utilPct >= 90 ? '#ef4444' : ($utilPct >= 70 ? '#f59e0b' : '#10b981'));

    $patientInitials = $patient
        ? strtoupper(mb_substr($patient->first_name ?? '', 0, 1) . mb_substr($patient->last_name ?? '', 0, 1))
        : 'P';
@endphp

<style>
    body { background: #f8fafc; }

    .info-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem;
        overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.02);
    }
    .info-card-hd {
        padding: .75rem 1.25rem; font-size: .6rem; font-weight: 800;
        color: #64748b; text-transform: uppercase; letter-spacing: .06em;
        border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: .5rem;
        background: linear-gradient(180deg,#f8fafc,#fff);
    }
    .info-card-bd { padding: .85rem 1.25rem; }
    .info-row {
        display: flex; justify-content: space-between; align-items: flex-start;
        padding: .4rem 0; border-bottom: 1px dotted #f1f5f9; gap: .75rem;
    }
    .info-row:last-child { border-bottom: none; }
    .info-row .lbl {
        font-size: .58rem; font-weight: 800; color: #94a3b8;
        text-transform: uppercase; letter-spacing: .05em; flex-shrink: 0;
    }
    .info-row .val {
        font-size: .78rem; font-weight: 600; color: #334155;
        text-align: right; max-width: 65%; word-break: break-word;
    }

    .section-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem;
        overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.02);
    }
    .section-hd {
        padding: .85rem 1.25rem; font-size: .7rem; font-weight: 800;
        color: #1e293b; text-transform: uppercase; letter-spacing: .04em;
        border-bottom: 2px solid #f1f5f9; display: flex; align-items: center; gap: .5rem;
        background: linear-gradient(180deg,#f8fafc,#fff);
    }
    .section-bd { padding: 1.25rem; }

    .util-section {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: 1px solid #bae6fd; border-radius: 1rem; padding: 1.5rem;
    }
    .util-bar-lg {
        height: 18px; border-radius: 9px; background: #e2e8f0;
        overflow: hidden; position: relative;
    }
    .util-fill-lg {
        height: 100%; border-radius: 9px;
        transition: width .5s ease; position: relative;
    }
    .util-label {
        position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
        font-size: .55rem; font-weight: 800; color: #fff;
        text-shadow: 0 1px 2px rgba(0,0,0,.2);
    }

    .alert-box {
        border-radius: .75rem; padding: .85rem 1rem; margin-bottom: 1rem;
        display: flex; align-items: center; gap: .6rem;
        font-size: .8rem; font-weight: 600;
    }

    .text-block {
        font-size: .82rem; color: #475569; line-height: 1.6;
        white-space: pre-wrap;
    }

    .code-badge {
        display: inline-flex; align-items: center; gap: .3rem;
        background: #1e293b; color: #fff;
        padding: .25rem .55rem; border-radius: .35rem;
        font-size: .68rem; font-weight: 800; font-family: monospace;
    }

    .status-badge {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .25rem .6rem; border-radius: .4rem;
        font-size: .58rem; font-weight: 800;
        text-transform: uppercase; letter-spacing: .05em; white-space: nowrap;
    }

    .header-card {
        background:
            radial-gradient(circle at 100% 0, rgba(59,130,246,.10), transparent 50%),
            radial-gradient(circle at 0 100%, rgba(6,182,212,.08), transparent 50%),
            #fff;
        border: 1px solid #e2e8f0; border-radius: 1.25rem;
        padding: 1.4rem 1.6rem;
    }

    .patient-avatar {
        width: 56px; height: 56px; border-radius: 14px;
        background: linear-gradient(135deg, #3b82f6, #06b6d4);
        color: #fff; display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.15rem; font-weight: 800; letter-spacing: .04em;
        box-shadow: 0 6px 14px -4px rgba(59,130,246,.45);
    }

    .stat-tile {
        background: #fff; border: 1px solid #e2e8f0; border-radius: .85rem;
        padding: .75rem .9rem; text-align: center;
    }
    .stat-tile .lbl { font-size: .55rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; }
    .stat-tile .val { font-size: 1rem; font-weight: 800; color: #1e293b; margin-top: .25rem; font-family: monospace; }

    .modifier-chip {
        display: inline-block; background: #eff6ff; color: #2563eb;
        padding: .15rem .5rem; border-radius: .35rem;
        font-size: .65rem; font-weight: 800; font-family: monospace;
        margin-right: .3rem;
    }
</style>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <a href="{{ route('clinical.psr.authorizations.index') }}"
       class="inline-flex items-center gap-1 text-xs font-bold text-slate-500 hover:text-blue-600 uppercase tracking-wider mb-4">
        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Authorizations
    </a>

    {{-- HEADER --}}
    <div class="header-card mb-5">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="patient-avatar">{{ $patientInitials }}</div>
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-blue-600 mb-1 flex items-center gap-1.5">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i> PSR Authorization
                    </div>
                    <h1 class="text-2xl font-black text-slate-900 leading-tight">
                        {{ $auth->auth_number ?: '#'.$auth->id }}
                    </h1>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1.5 text-xs text-slate-500">
                        <span class="font-bold text-slate-700">{{ $patient?->full_name ?? '—' }}</span>
                        @if($patient?->mrn)<span class="text-slate-300">·</span><span class="font-mono">MRN {{ $patient->mrn }}</span>@endif
                        @if($payer)<span class="text-slate-300">·</span><span>{{ $payer->name }}</span>@endif
                        @if($clinic)<span class="text-slate-300">·</span><span>{{ $clinic->name }}</span>@endif
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="status-badge {{ $statusMap['bg'] }} {{ $statusMap['text'] }} border {{ $statusMap['border'] }}">
                    <span class="w-2 h-2 rounded-full {{ $statusMap['dot'] }} animate-pulse"></span>
                    {{ $statusLabel }}
                </span>
                @if($admission)
                <a href="{{ route('clinical.psr.admissions.show', $admission) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold uppercase
                          bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">
                    <i data-lucide="folder-open" class="w-3.5 h-3.5"></i> Admission
                </a>
                @endif
                <a href="{{ route('clinical.psr.authorizations.edit', $auth) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold uppercase
                          bg-blue-600 hover:bg-blue-700 text-white transition-colors">
                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
                </a>
            </div>
        </div>
    </div>

    {{-- ALERT BOXES --}}
    @if($auth->status === 'denied')
    <div class="alert-box bg-red-50 border border-red-200 text-red-700">
        <i data-lucide="x-circle" class="w-5 h-5 flex-shrink-0"></i>
        <span>This authorization has been <strong>DENIED</strong>.{{ $auth->denial_reason ? ' Reason: '.\Illuminate\Support\Str::limit($auth->denial_reason, 100) : '' }}</span>
    </div>
    @endif

    @if($auth->status === 'approved' && $isExpired)
    <div class="alert-box bg-red-50 border border-red-200 text-red-700">
        <i data-lucide="timer-off" class="w-5 h-5 flex-shrink-0"></i>
        <span>This authorization <strong>EXPIRED {{ abs((int)$daysLeft) }} days ago</strong> on {{ $auth->approved_end_date->format('m/d/Y') }}. Submit a concurrent request.</span>
    </div>
    @elseif($auth->status === 'approved' && $expSoon)
    <div class="alert-box bg-orange-50 border border-orange-200 text-orange-700">
        <i data-lucide="calendar-clock" class="w-5 h-5 flex-shrink-0"></i>
        <span>Authorization <strong>expires in {{ (int)$daysLeft }} days</strong> ({{ $auth->approved_end_date->format('m/d/Y') }}). Plan a concurrent request.</span>
    </div>
    @endif

    @if($auth->status === 'approved' && $unitsLow)
    <div class="alert-box bg-amber-50 border border-amber-200 text-amber-700">
        <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0"></i>
        <span><strong>Units running low:</strong> {{ $auth->units_used }}/{{ $auth->units_approved }} used ({{ $utilPct }}%). {{ $auth->units_remaining }} units remaining.</span>
    </div>
    @endif

    {{-- UTILIZATION GAUGE --}}
    @if($auth->units_approved)
    <div class="util-section mb-5">
        <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
            <div class="text-xs font-black text-slate-700 uppercase flex items-center gap-1.5">
                <i data-lucide="bar-chart-3" class="w-4 h-4"></i> Unit Utilization
            </div>
            <div class="text-xs font-bold text-slate-600 font-mono">
                {{ $auth->units_used }} / {{ $auth->units_approved }}
                {{ \App\Models\Psr\Authorization::UNIT_TYPES[$auth->unit_type] ?? $auth->unit_type }}
                <span class="text-slate-400">({{ $auth->units_remaining }} remaining)</span>
            </div>
        </div>
        <div class="util-bar-lg">
            <div class="util-fill-lg" style="width:{{ min((float)$utilPct, 100) }}%; background: {{ $utilColor }};">
                @if($utilPct !== null && $utilPct >= 15)
                <span class="util-label">{{ $utilPct }}%</span>
                @endif
            </div>
        </div>
        @if($auth->frequency)
        <div class="text-[11px] text-slate-500 mt-2">Frequency: {{ $auth->frequency }}</div>
        @endif
    </div>
    @endif

    {{-- MAIN GRID --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

        {{-- LEFT SIDEBAR --}}
        <div class="lg:col-span-4 space-y-4">

            <div class="info-card">
                <div class="info-card-hd"><i data-lucide="user" class="w-3.5 h-3.5"></i> Patient Information</div>
                <div class="info-card-bd">
                    <div class="info-row"><span class="lbl">Name</span><span class="val">{{ $patient?->full_name ?? '—' }}</span></div>
                    <div class="info-row"><span class="lbl">MRN</span><span class="val font-mono">{{ $patient?->mrn ?? '—' }}</span></div>
                    <div class="info-row"><span class="lbl">DOB</span><span class="val">{{ $patient?->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->format('m/d/Y') : '—' }}</span></div>
                    <div class="info-row"><span class="lbl">Clinic</span><span class="val">{{ $clinic?->name ?? '—' }}</span></div>
                    <div class="info-row"><span class="lbl">Therapist</span><span class="val">{{ $admission?->assignedTherapist?->full_name ?? '—' }}</span></div>
                    <div class="info-row"><span class="lbl">Adm. Date</span><span class="val">{{ $admission?->admission_date?->format('m/d/Y') ?? '—' }}</span></div>
                    <div class="info-row"><span class="lbl">Adm. Status</span><span class="val">{{ $admission?->status_label ?? '—' }}</span></div>
                </div>
            </div>

            <div class="info-card">
                <div class="info-card-hd"><i data-lucide="building-2" class="w-3.5 h-3.5"></i> Insurance Details</div>
                <div class="info-card-bd">
                    <div class="info-row"><span class="lbl">Payer</span><span class="val">{{ $payer?->name ?? '—' }}</span></div>
                    <div class="info-row"><span class="lbl">External ID</span><span class="val font-mono">{{ $auth->payer_external_id ?: '—' }}</span></div>
                    <div class="info-row"><span class="lbl">Member ID</span><span class="val font-mono">{{ $auth->member_id ?: '—' }}</span></div>
                    <div class="info-row"><span class="lbl">Medicaid ID</span><span class="val font-mono">{{ $auth->medicaid_id ?: '—' }}</span></div>
                    <div class="info-row"><span class="lbl">Plan Type</span><span class="val">{{ $auth->plan_type ?: '—' }}</span></div>
                </div>
            </div>

            <div class="info-card">
                <div class="info-card-hd"><i data-lucide="stethoscope" class="w-3.5 h-3.5"></i> Provider Information</div>
                <div class="info-card-bd">
                    <div class="info-row"><span class="lbl">Rendering</span><span class="val">{{ $rp?->full_name ?? '—' }}</span></div>
                    <div class="info-row"><span class="lbl">Rendering NPI</span><span class="val font-mono">{{ $auth->rendering_npi ?: '—' }}</span></div>
                    <div class="info-row"><span class="lbl">Group NPI</span><span class="val font-mono">{{ $auth->group_npi ?: '—' }}</span></div>
                    <div class="info-row"><span class="lbl">Taxonomy</span><span class="val font-mono">{{ $auth->taxonomy_code ?: '—' }}</span></div>
                    <div class="info-row"><span class="lbl">Supervising</span><span class="val">{{ $sp?->full_name ?? '—' }}</span></div>
                </div>
            </div>

            <div class="info-card">
                <div class="info-card-hd"><i data-lucide="clock" class="w-3.5 h-3.5"></i> Tracking</div>
                <div class="info-card-bd">
                    <div class="info-row"><span class="lbl">Created</span><span class="val">{{ $auth->createdBy?->name ?? '—' }}</span></div>
                    <div class="info-row"><span class="lbl">Created at</span><span class="val">{{ $auth->created_at?->format('m/d/Y g:ia') }}</span></div>
                    @if($auth->updatedBy)
                    <div class="info-row"><span class="lbl">Updated by</span><span class="val">{{ $auth->updatedBy->name }}</span></div>
                    @endif
                    <div class="info-row"><span class="lbl">Updated at</span><span class="val">{{ $auth->updated_at?->format('m/d/Y g:ia') }}</span></div>
                    @if($auth->contact_name)
                    <div class="info-row"><span class="lbl">Ins. Contact</span><span class="val">{{ $auth->contact_name }}</span></div>
                    @endif
                    @if($auth->contact_phone)
                    <div class="info-row"><span class="lbl">Contact phone</span><span class="val font-mono">{{ $auth->contact_phone }}</span></div>
                    @endif
                </div>
            </div>
        </div>

        {{-- RIGHT MAIN COLUMN --}}
        <div class="lg:col-span-8 space-y-4">

            {{-- Status & dates --}}
            <div class="section-card">
                <div class="section-hd"><i data-lucide="shield-check" class="w-4 h-4"></i> Authorization Status</div>
                <div class="section-bd">
                    <div class="flex flex-wrap items-start gap-x-6 gap-y-3 mb-4">
                        <div>
                            <div class="text-[9px] font-bold text-slate-400 uppercase mb-1">Status</div>
                            <span class="status-badge {{ $statusMap['bg'] }} {{ $statusMap['text'] }} border {{ $statusMap['border'] }}">
                                <span class="w-2 h-2 rounded-full {{ $statusMap['dot'] }}"></span>
                                {{ $statusLabel }}
                            </span>
                        </div>
                        <div>
                            <div class="text-[9px] font-bold text-slate-400 uppercase mb-1">Auth #</div>
                            <span class="font-mono font-bold text-slate-800">{{ $auth->auth_number ?: 'Pending' }}</span>
                        </div>
                        <div>
                            <div class="text-[9px] font-bold text-slate-400 uppercase mb-1">Type</div>
                            <span class="text-sm font-semibold text-slate-700">{{ \App\Models\Psr\Authorization::AUTH_TYPES[$auth->auth_type] ?? $auth->auth_type }}</span>
                        </div>
                        @if($auth->reference_number)
                        <div>
                            <div class="text-[9px] font-bold text-slate-400 uppercase mb-1">Internal Ref</div>
                            <span class="text-sm font-mono text-slate-600">{{ $auth->reference_number }}</span>
                        </div>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="stat-tile">
                            <div class="lbl">Submitted</div>
                            <div class="val">{{ $auth->submission_date?->format('m/d/Y') ?? '—' }}</div>
                        </div>
                        <div class="stat-tile">
                            <div class="lbl">Decision</div>
                            <div class="val">{{ $auth->decision_date?->format('m/d/Y') ?? '—' }}</div>
                        </div>
                        <div class="stat-tile">
                            <div class="lbl">Effective</div>
                            <div class="val">{{ $auth->approved_start_date?->format('m/d/Y') ?? '—' }}</div>
                        </div>
                        <div class="stat-tile">
                            <div class="lbl">Expires</div>
                            <div class="val {{ $isExpired ? 'text-red-600' : ($expSoon ? 'text-amber-600' : '') }}">
                                {{ $auth->approved_end_date?->format('m/d/Y') ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Service & billing --}}
            <div class="section-card">
                <div class="section-hd"><i data-lucide="receipt" class="w-4 h-4"></i> Service &amp; Billing Codes</div>
                <div class="section-bd">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <div>
                            <div class="text-[9px] font-bold text-slate-400 uppercase mb-1">Service Code</div>
                            <span class="code-badge">{{ $auth->service_code ?: '—' }}</span>
                            @if($auth->service_description)
                            <div class="text-[11px] text-slate-500 mt-1.5">{{ $auth->service_description }}</div>
                            @endif
                        </div>
                        <div>
                            <div class="text-[9px] font-bold text-slate-400 uppercase mb-1">Modifiers</div>
                            @php $mods = collect([$auth->modifier_1, $auth->modifier_2, $auth->modifier_3, $auth->modifier_4])->filter(); @endphp
                            @if($mods->count())
                                @foreach($mods as $mod)<span class="modifier-chip">{{ $mod }}</span>@endforeach
                            @else
                                <span class="text-slate-400 text-xs italic">None</span>
                            @endif
                        </div>
                        <div>
                            <div class="text-[9px] font-bold text-slate-400 uppercase mb-1">Place of Service</div>
                            <span class="text-sm font-semibold text-slate-700 font-mono">{{ $auth->place_of_service ?: '—' }}</span>
                        </div>
                        @if($auth->revenue_code)
                        <div>
                            <div class="text-[9px] font-bold text-slate-400 uppercase mb-1">Revenue Code</div>
                            <span class="font-mono text-sm text-slate-700">{{ $auth->revenue_code }}</span>
                        </div>
                        @endif
                        <div>
                            <div class="text-[9px] font-bold text-slate-400 uppercase mb-1">Frequency</div>
                            <span class="text-sm text-slate-700">{{ $auth->frequency ?: '—' }}</span>
                        </div>
                        <div>
                            <div class="text-[9px] font-bold text-slate-400 uppercase mb-1">Unit Type</div>
                            <span class="text-sm text-slate-700">{{ \App\Models\Psr\Authorization::UNIT_TYPES[$auth->unit_type] ?? $auth->unit_type }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4 pt-4 border-t border-slate-100">
                        <div class="text-center">
                            <div class="text-[9px] font-bold text-slate-400 uppercase">Requested</div>
                            <div class="text-2xl font-black text-slate-700 font-mono mt-1">{{ $auth->units_requested ?? '—' }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-[9px] font-bold text-slate-400 uppercase">Approved</div>
                            <div class="text-2xl font-black text-emerald-600 font-mono mt-1">{{ $auth->units_approved ?? '—' }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-[9px] font-bold text-slate-400 uppercase">Used</div>
                            <div class="text-2xl font-black font-mono mt-1" style="color:{{ $utilColor }}">{{ $auth->units_used }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-[9px] font-bold text-slate-400 uppercase">Remaining</div>
                            <div class="text-2xl font-black text-blue-600 font-mono mt-1">{{ $auth->units_remaining ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Diagnoses --}}
            <div class="section-card">
                <div class="section-hd"><i data-lucide="heart-pulse" class="w-4 h-4"></i> Diagnosis Codes (ICD-10)</div>
                <div class="section-bd">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="text-[9px] font-bold text-slate-400 uppercase mb-1">Primary Diagnosis</div>
                            <span class="code-badge">{{ $auth->primary_dx_code ?: 'N/A' }}</span>
                            @if($auth->primary_dx_description)
                            <div class="text-[11px] text-slate-600 mt-1.5">{{ $auth->primary_dx_description }}</div>
                            @endif
                        </div>
                        @if($auth->secondary_dx_code)
                        <div>
                            <div class="text-[9px] font-bold text-slate-400 uppercase mb-1">Secondary Diagnosis</div>
                            <span class="code-badge" style="background:#475569;">{{ $auth->secondary_dx_code }}</span>
                            @if($auth->secondary_dx_description)
                            <div class="text-[11px] text-slate-600 mt-1.5">{{ $auth->secondary_dx_description }}</div>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Clinical justification --}}
            @if($auth->clinical_justification || $auth->medical_necessity_statement)
            <div class="section-card">
                <div class="section-hd"><i data-lucide="clipboard-list" class="w-4 h-4"></i> Clinical Justification</div>
                <div class="section-bd space-y-4">
                    @if($auth->clinical_justification)
                    <div>
                        <div class="text-[9px] font-bold text-slate-400 uppercase mb-1.5">Clinical Justification</div>
                        <div class="text-block bg-slate-50 rounded-lg p-3 border border-slate-100">{{ $auth->clinical_justification }}</div>
                    </div>
                    @endif
                    @if($auth->medical_necessity_statement)
                    <div>
                        <div class="text-[9px] font-bold text-slate-400 uppercase mb-1.5">Medical Necessity Statement</div>
                        <div class="text-block bg-slate-50 rounded-lg p-3 border border-slate-100">{{ $auth->medical_necessity_statement }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Denial / appeal --}}
            @if($auth->status === 'denied' || $auth->denial_reason || $auth->appeal_notes)
            <div class="section-card" style="border-color:#fecaca;">
                <div class="section-hd" style="color:#dc2626; background: linear-gradient(180deg,#fef2f2,#fff); border-bottom-color:#fee2e2;">
                    <i data-lucide="file-warning" class="w-4 h-4"></i> Denial &amp; Appeal Information
                </div>
                <div class="section-bd space-y-4">
                    @if($auth->denial_reason)
                    <div>
                        <div class="text-[9px] font-bold text-red-400 uppercase mb-1.5">Denial Reason</div>
                        <div class="text-block bg-red-50 rounded-lg p-3 text-red-800 border border-red-100">{{ $auth->denial_reason }}</div>
                    </div>
                    @endif
                    @if($auth->appeal_notes)
                    <div>
                        <div class="text-[9px] font-bold text-amber-500 uppercase mb-1.5">Appeal Notes</div>
                        <div class="text-block bg-amber-50 rounded-lg p-3 text-amber-800 border border-amber-100">{{ $auth->appeal_notes }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Notes --}}
            @if($auth->notes)
            <div class="section-card">
                <div class="section-hd"><i data-lucide="sticky-note" class="w-4 h-4"></i> Internal Notes</div>
                <div class="section-bd">
                    <div class="text-block">{{ $auth->notes }}</div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>
@endpush
@endsection
