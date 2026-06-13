@extends('layouts.app')
@section('title', 'PSR — Prior Authorizations')

@section('content')

<style>
    .stat-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem;
        padding: 1.15rem 1.25rem; display: flex; align-items: center; gap: .85rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.02); transition: all .25s ease;
        position: relative; overflow: hidden;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px -6px rgba(0,0,0,.08); }
    .stat-card::after {
        content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 3px;
        background: linear-gradient(90deg, transparent, var(--accent, transparent), transparent);
    }
    .stat-card.accent-slate   { --accent: #94a3b8; }
    .stat-card.accent-amber   { --accent: #fbbf24; }
    .stat-card.accent-emerald { --accent: #34d399; }
    .stat-card.accent-rose    { --accent: #f87171; }
    .stat-card.accent-blue    { --accent: #60a5fa; }

    .data-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .data-table th {
        background: linear-gradient(180deg, #f8fafc, #f1f5f9);
        padding: .85rem 1rem; font-size: .58rem; font-weight: 800;
        color: #94a3b8; text-transform: uppercase; letter-spacing: .05em;
        border-bottom: 1px solid #e2e8f0; text-align: left; white-space: nowrap;
    }
    .data-table td {
        padding: .85rem 1rem; font-size: .8rem; color: #334155;
        border-bottom: 1px solid #f1f5f9; vertical-align: middle;
    }
    .data-table tbody tr { transition: background .15s ease; }
    .data-table tbody tr:hover td { background-color: #f0f9ff; }

    .action-btn {
        width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; border: 1px solid #e2e8f0; background: #fff;
        color: #94a3b8; transition: all .2s ease; cursor: pointer; text-decoration: none;
    }
    .action-btn.view:hover   { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
    .action-btn.edit:hover   { background: #fef3c7; color: #d97706; border-color: #fcd34d; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .status-badge {
        display: inline-flex; align-items: center; gap: .25rem; padding: .25rem .55rem;
        border-radius: .4rem; font-size: .55rem; font-weight: 800; text-transform: uppercase;
        letter-spacing: .04em; border: 1px solid transparent; white-space: nowrap;
    }

    .util-bar { height: 6px; border-radius: 3px; background: #e2e8f0; overflow: hidden; min-width: 70px; }
    .util-fill { height: 100%; border-radius: 3px; transition: width .3s ease; }
    .util-low  { background: linear-gradient(90deg, #34d399, #10b981); }
    .util-mid  { background: linear-gradient(90deg, #fbbf24, #f59e0b); }
    .util-high { background: linear-gradient(90deg, #fb923c, #ea580c); }
    .util-crit { background: linear-gradient(90deg, #f87171, #dc2626); }

    .filter-input {
        border: 1px solid #e2e8f0; border-radius: .6rem; padding: .45rem .7rem;
        font-size: .8rem; font-weight: 500; outline: none; transition: all .2s; background: #fff;
    }
    .filter-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.08); }

    .header-card {
        background:
            radial-gradient(circle at 100% 0, rgba(59,130,246,.08), transparent 50%),
            radial-gradient(circle at 0 100%, rgba(6,182,212,.06), transparent 50%),
            #fff;
        border: 1px solid #e2e8f0;
    }

    /* Alert panels (collapsible) */
    .alert-panel {
        border-radius: 1rem; margin-bottom: .85rem;
        overflow: hidden; border: 1px solid;
    }
    .alert-panel-hd {
        padding: .85rem 1.25rem;
        display: flex; align-items: center; justify-content: space-between;
        cursor: pointer; user-select: none;
    }
    .alert-panel-hd h3 {
        font-size: .75rem; font-weight: 800;
        text-transform: uppercase; letter-spacing: .04em;
        display: flex; align-items: center; gap: .5rem;
    }
    .alert-panel-hd .count-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 22px; height: 22px; border-radius: 11px;
        font-size: .65rem; font-weight: 900; padding: 0 7px;
    }
    .alert-panel-bd { padding: 0 1.25rem 1rem; display: none; }
    .alert-panel-bd.open { display: block; }
    .alert-item {
        display: flex; align-items: center; gap: .75rem;
        padding: .55rem .75rem; border-radius: .5rem;
        margin-bottom: .35rem; transition: background .15s;
    }
    .alert-item:hover { background: rgba(0,0,0,.03); }
    .alert-item .patient { font-size: .82rem; font-weight: 700; color: #1e293b; }
    .alert-item .meta { font-size: .65rem; color: #64748b; line-height: 1.5; margin-top: 1px; }
    .alert-item .action-link {
        font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
        text-decoration: none; flex-shrink: 0;
    }
    .chev { transition: transform .25s ease; }
    .chev.open { transform: rotate(180deg); }
</style>

@php
    $statusColors = [
        'pending'        => 'bg-amber-50 text-amber-700 border-amber-200',
        'submitted'      => 'bg-blue-50 text-blue-700 border-blue-200',
        'pending_review' => 'bg-violet-50 text-violet-700 border-violet-200',
        'approved'       => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'denied'         => 'bg-rose-50 text-rose-700 border-rose-200',
        'expired'        => 'bg-slate-100 text-slate-600 border-slate-200',
    ];
@endphp

<div class="max-w-7xl mx-auto">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4 header-card p-5 rounded-2xl shadow-sm">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('clinical.psr.dashboard') }}"
               class="w-9 h-9 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors border border-slate-200 flex-shrink-0">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div class="p-2.5 bg-gradient-to-br from-blue-500 to-cyan-600 text-white rounded-xl shadow-md shadow-blue-500/30">
                <i data-lucide="shield-check" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-xl font-black text-slate-800 tracking-tight uppercase">Prior Authorizations &amp; Units</h1>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">FL Medicaid PSR compliance · insurance auth tracking &amp; unit management</p>
            </div>
        </div>
        @can('clinical.psr.authorizations.create')
            <a href="{{ route('clinical.psr.authorizations.create') }}"
               class="bg-gradient-to-br from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider shadow-lg shadow-blue-500/30 flex items-center gap-2 transition-all hover:-translate-y-0.5">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> New authorization
            </a>
        @endcan
    </div>

    @if($alerts['expired']->count() || $alerts['expiring']->count() || $alerts['unitsLow']->count() || $alerts['denied']->count() || $alerts['noAuth']->count())
        <div class="mb-6">

            {{-- EXPIRED --}}
            @if($alerts['expired']->count())
                <div class="alert-panel" style="border-color:#fecaca;background:#fff5f5;">
                    <div class="alert-panel-hd" onclick="togglePanel('expired')" style="background:#fef2f2;">
                        <h3 style="color:#dc2626;">
                            <i data-lucide="timer-off" class="w-4 h-4"></i> Expired authorizations
                            <span class="count-badge" style="background:#dc2626;color:#fff;">{{ $alerts['expired']->count() }}</span>
                        </h3>
                        <i data-lucide="chevron-down" class="w-4 h-4 chev open" id="chev-expired" style="color:#dc2626;"></i>
                    </div>
                    <div class="alert-panel-bd open" id="panel-expired">
                        @foreach($alerts['expired'] as $ea)
                            @php $daysAgo = (int) abs(now()->startOfDay()->diffInDays($ea->approved_end_date->startOfDay(), false)); @endphp
                            <div class="alert-item" style="background:#fef2f2;">
                                <div class="w-8 h-8 rounded-full bg-red-100 text-red-500 flex items-center justify-center flex-shrink-0"><i data-lucide="alert-circle" class="w-4 h-4"></i></div>
                                <div class="flex-1 min-w-0">
                                    <div class="patient">{{ $ea->admission?->patient?->full_name ?? '—' }} <span class="text-[10px] text-slate-400 font-mono ml-1">{{ $ea->admission?->patient?->mrn ?? '' }}</span></div>
                                    <div class="meta">
                                        Auth # <span class="font-mono font-bold">{{ $ea->auth_number ?: 'N/A' }}</span> ·
                                        {{ $ea->payer?->name ?? '—' }} ·
                                        <strong style="color:#dc2626;">Expired {{ $ea->approved_end_date->format('m/d/Y') }} ({{ $daysAgo }}d ago)</strong>
                                        · {{ $ea->units_remaining }} units remaining · {{ $ea->clinic?->name ?? '' }}
                                    </div>
                                </div>
                                <a href="{{ route('clinical.psr.authorizations.show', $ea) }}" class="action-link" style="color:#dc2626;">View</a>
                                @can('clinical.psr.authorizations.create')
                                    <a href="{{ route('clinical.psr.authorizations.create', ['admission_id' => $ea->psr_admission_id]) }}" class="action-link" style="color:#2563eb;">Renew</a>
                                @endcan
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- EXPIRING ≤ 30d --}}
            @if($alerts['expiring']->count())
                <div class="alert-panel" style="border-color:#fed7aa;background:#fffbf5;">
                    <div class="alert-panel-hd" onclick="togglePanel('expiring')" style="background:#fff7ed;">
                        <h3 style="color:#ea580c;">
                            <i data-lucide="calendar-clock" class="w-4 h-4"></i> Expiring in 30 days
                            <span class="count-badge" style="background:#ea580c;color:#fff;">{{ $alerts['expiring']->count() }}</span>
                        </h3>
                        <i data-lucide="chevron-down" class="w-4 h-4 chev open" id="chev-expiring" style="color:#ea580c;"></i>
                    </div>
                    <div class="alert-panel-bd open" id="panel-expiring">
                        @foreach($alerts['expiring'] as $ea)
                            <div class="alert-item">
                                <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-500 flex items-center justify-center flex-shrink-0"><i data-lucide="clock" class="w-4 h-4"></i></div>
                                <div class="flex-1 min-w-0">
                                    <div class="patient">{{ $ea->admission?->patient?->full_name ?? '—' }} <span class="text-[10px] text-slate-400 font-mono ml-1">{{ $ea->admission?->patient?->mrn ?? '' }}</span></div>
                                    <div class="meta">
                                        Auth # <span class="font-mono font-bold">{{ $ea->auth_number ?: 'N/A' }}</span> · {{ $ea->payer?->name ?? '—' }} ·
                                        <strong style="color:#ea580c;">Expires {{ $ea->approved_end_date->format('m/d/Y') }} ({{ $ea->days_to_expiry }}d left)</strong>
                                        · {{ $ea->units_used }}/{{ $ea->units_approved ?? '—' }} units used
                                    </div>
                                </div>
                                <a href="{{ route('clinical.psr.authorizations.show', $ea) }}" class="action-link" style="color:#ea580c;">View</a>
                                @can('clinical.psr.authorizations.create')
                                    <a href="{{ route('clinical.psr.authorizations.create', ['admission_id' => $ea->psr_admission_id]) }}" class="action-link" style="color:#2563eb;">Renew</a>
                                @endcan
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- UNITS LOW (≥ 80%) --}}
            @if($alerts['unitsLow']->count())
                <div class="alert-panel" style="border-color:#fde68a;background:#fffdf5;">
                    <div class="alert-panel-hd" onclick="togglePanel('units')" style="background:#fefce8;">
                        <h3 style="color:#ca8a04;">
                            <i data-lucide="alert-triangle" class="w-4 h-4"></i> Units running low (≥ 80% used)
                            <span class="count-badge" style="background:#ca8a04;color:#fff;">{{ $alerts['unitsLow']->count() }}</span>
                        </h3>
                        <i data-lucide="chevron-down" class="w-4 h-4 chev" id="chev-units" style="color:#ca8a04;"></i>
                    </div>
                    <div class="alert-panel-bd" id="panel-units">
                        @foreach($alerts['unitsLow'] as $ea)
                            <div class="alert-item">
                                <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-500 flex items-center justify-center flex-shrink-0"><i data-lucide="gauge" class="w-4 h-4"></i></div>
                                <div class="flex-1 min-w-0">
                                    <div class="patient">{{ $ea->admission?->patient?->full_name ?? '—' }}</div>
                                    <div class="meta">
                                        Auth # <span class="font-mono font-bold">{{ $ea->auth_number ?: 'N/A' }}</span> ·
                                        <strong style="color:#ca8a04;">{{ $ea->units_used }}/{{ $ea->units_approved }} units ({{ $ea->units_used_percent }}%)</strong>
                                        · {{ $ea->units_remaining }} remaining · expires {{ optional($ea->approved_end_date)->format('m/d/Y') ?? '—' }}
                                    </div>
                                </div>
                                <a href="{{ route('clinical.psr.authorizations.show', $ea) }}" class="action-link" style="color:#ca8a04;">View</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- DENIED --}}
            @if($alerts['denied']->count())
                <div class="alert-panel" style="border-color:#fecdd3;background:#fff5f7;">
                    <div class="alert-panel-hd" onclick="togglePanel('denied')" style="background:#fee2e2;">
                        <h3 style="color:#be123c;">
                            <i data-lucide="x-circle" class="w-4 h-4"></i> Denied authorizations
                            <span class="count-badge" style="background:#be123c;color:#fff;">{{ $alerts['denied']->count() }}</span>
                        </h3>
                        <i data-lucide="chevron-down" class="w-4 h-4 chev" id="chev-denied" style="color:#be123c;"></i>
                    </div>
                    <div class="alert-panel-bd" id="panel-denied">
                        @foreach($alerts['denied'] as $ea)
                            <div class="alert-item">
                                <div class="w-8 h-8 rounded-full bg-rose-100 text-rose-500 flex items-center justify-center flex-shrink-0"><i data-lucide="x" class="w-4 h-4"></i></div>
                                <div class="flex-1 min-w-0">
                                    <div class="patient">{{ $ea->admission?->patient?->full_name ?? '—' }}</div>
                                    <div class="meta">
                                        Auth # <span class="font-mono font-bold">{{ $ea->auth_number ?: 'N/A' }}</span> · {{ $ea->payer?->name ?? '—' }}
                                        @if($ea->decision_date) · denied {{ $ea->decision_date->format('m/d/Y') }} @endif
                                        @if($ea->denial_reason) · <em>{{ \Illuminate\Support\Str::limit($ea->denial_reason, 80) }}</em>@endif
                                    </div>
                                </div>
                                <a href="{{ route('clinical.psr.authorizations.show', $ea) }}" class="action-link" style="color:#be123c;">View</a>
                                @can('clinical.psr.authorizations.create')
                                    <a href="{{ route('clinical.psr.authorizations.create', ['admission_id' => $ea->psr_admission_id]) }}" class="action-link" style="color:#2563eb;">Resubmit</a>
                                @endcan
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- NO AUTH ON ADMITTED PATIENTS --}}
            @if($alerts['noAuth']->count())
                <div class="alert-panel" style="border-color:#cbd5e1;background:#fafbfc;">
                    <div class="alert-panel-hd" onclick="togglePanel('noauth')" style="background:#f1f5f9;">
                        <h3 style="color:#475569;">
                            <i data-lucide="shield-off" class="w-4 h-4"></i> Admitted patients without active auth
                            <span class="count-badge" style="background:#64748b;color:#fff;">{{ $alerts['noAuth']->count() }}</span>
                        </h3>
                        <i data-lucide="chevron-down" class="w-4 h-4 chev" id="chev-noauth" style="color:#475569;"></i>
                    </div>
                    <div class="alert-panel-bd" id="panel-noauth">
                        @foreach($alerts['noAuth'] as $a)
                            <div class="alert-item">
                                <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center flex-shrink-0"><i data-lucide="user" class="w-4 h-4"></i></div>
                                <div class="flex-1 min-w-0">
                                    <div class="patient">{{ $a->patient?->full_name ?? '—' }}</div>
                                    <div class="meta">
                                        {{ $a->clinic?->name ?? '—' }} · admitted {{ optional($a->admission_date)->format('m/d/Y') ?? '—' }} · primary Dx <span class="font-mono">{{ $a->primary_dx_code ?: '—' }}</span>
                                    </div>
                                </div>
                                <a href="{{ route('clinical.psr.admissions.show', $a) }}" class="action-link" style="color:#475569;">Chart</a>
                                @can('clinical.psr.authorizations.create')
                                    <a href="{{ route('clinical.psr.authorizations.create', ['admission_id' => $a->id]) }}" class="action-link" style="color:#2563eb;">+ Create auth</a>
                                @endcan
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    @endif

    @php
        $tabs = [
            'all'      => ['Total',     'slate',   $counts['all']],
            'pending'  => ['Pending',   'amber',   $counts['pending']],
            'approved' => ['Approved',  'emerald', $counts['approved']],
            'denied'   => ['Denied',    'rose',    $counts['denied']],
            'expired'  => ['Expired',   'slate',   $counts['expired']],
        ];
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-5">
        @foreach($tabs as $key => [$label, $color, $count])
            @php $isActive = ($status === $key) || ($key === 'all' && !$status); @endphp
            <a href="{{ route('clinical.psr.authorizations.index', $key === 'all' ? [] : ['status' => $key]) }}"
               class="stat-card accent-{{ $color }} {{ $isActive ? 'ring-2 ring-' . $color . '-200' : '' }}">
                <div class="w-11 h-11 rounded-xl bg-{{ $color }}-50 text-{{ $color }}-600 flex items-center justify-center">
                    <i data-lucide="{{ ['all'=>'shield-check','pending'=>'clock','approved'=>'circle-check','denied'=>'x-circle','expired'=>'timer-off'][$key] }}" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="text-2xl font-black text-{{ $color }}-600 leading-tight">{{ $count }}</div>
                    <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ $label }}</div>
                </div>
            </a>
        @endforeach
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-3.5 mb-5">
        <form method="GET" class="flex flex-wrap items-center gap-2.5">
            <div class="relative flex-1 min-w-[180px] max-w-[280px]">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-300"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search auth #, patient, code…"
                       class="w-full pl-8 pr-3 filter-input">
            </div>
            <select name="status" class="filter-input min-w-[130px]">
                <option value="">All statuses</option>
                @foreach($statuses as $k => $v)<option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>@endforeach
            </select>
            <select name="payer_id" class="filter-input min-w-[160px]">
                <option value="">All payers</option>
                @foreach($filterPayers as $p)<option value="{{ $p->id }}" @selected((string) $payerFilter === (string) $p->id)>{{ $p->name }}</option>@endforeach
            </select>
            <div class="flex items-center gap-1.5">
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="filter-input w-[130px]" title="Approved from">
                <span class="text-slate-300 text-[10px] font-bold">to</span>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="filter-input w-[130px]" title="Approved to">
            </div>
            <button type="submit" class="bg-gradient-to-br from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white px-3.5 py-2 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-colors flex items-center gap-1.5 shadow-sm shadow-blue-500/20">
                <i data-lucide="filter" class="w-3 h-3"></i> Filter
            </button>
            @if($search || $status || $payerFilter || $dateFrom || $dateTo)
                <a href="{{ route('clinical.psr.authorizations.index') }}"
                   class="bg-slate-100 hover:bg-slate-200 text-slate-500 px-3.5 py-2 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-colors flex items-center gap-1.5">
                    <i data-lucide="x" class="w-3 h-3"></i> Clear
                </a>
            @endif
        </form>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        @if($auths->count() > 0)
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Auth #</th>
                            <th>Patient</th>
                            <th>Service</th>
                            <th>Payer</th>
                            <th style="text-align:right;">Units used</th>
                            <th>Period</th>
                            <th>Status</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($auths as $a)
                            @php
                                $pct = $a->units_used_percent ?? 0;
                                $util = $pct >= 90 ? 'util-crit' : ($pct >= 75 ? 'util-high' : ($pct >= 50 ? 'util-mid' : 'util-low'));
                                $expDays = $a->days_to_expiry;
                            @endphp
                            <tr>
                                <td class="font-mono text-xs font-bold text-blue-700">
                                    <a href="{{ route('clinical.psr.authorizations.show', $a) }}" class="hover:underline">{{ $a->auth_number ?: '#'.$a->id }}</a>
                                </td>
                                <td>
                                    <div class="font-bold text-slate-800 text-sm">{{ $a->admission?->patient?->full_name ?? '—' }}</div>
                                    @if($a->admission?->patient?->mrn)
                                        <div class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $a->admission->patient->mrn }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="font-mono text-xs font-bold text-slate-700">{{ $a->service_code ?: '—' }}@if($a->modifier_1) <span class="text-slate-400">{{ $a->modifier_1 }}</span>@endif</div>
                                    @if($a->service_description)<div class="text-[10px] text-slate-400 mt-0.5">{{ \Illuminate\Support\Str::limit($a->service_description, 28) }}</div>@endif
                                </td>
                                <td class="text-xs">{{ $a->payer?->name ?? '—' }}</td>
                                <td style="text-align:right;">
                                    @if($a->units_approved)
                                        <div class="font-mono font-bold text-xs">{{ $a->units_used }}<span class="text-slate-400">/{{ $a->units_approved }}</span></div>
                                        <div class="util-bar mt-1.5"><div class="util-fill {{ $util }}" style="width: {{ min(100, $pct) }}%"></div></div>
                                        <div class="text-[10px] font-bold text-slate-500 mt-0.5">{{ $pct }}%</div>
                                    @else
                                        <span class="text-slate-300 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="text-xs text-slate-600">
                                    @if($a->approved_start_date && $a->approved_end_date)
                                        <div>{{ $a->approved_start_date->format('m/d/Y') }}</div>
                                        <div class="text-slate-400">→ {{ $a->approved_end_date->format('m/d/Y') }}</div>
                                        @if($expDays !== null)
                                            @if($expDays < 0)
                                                <div class="text-rose-600 font-bold text-[10px] mt-0.5">Expired {{ abs($expDays) }}d ago</div>
                                            @elseif($expDays <= 30)
                                                <div class="text-orange-600 font-bold text-[10px] mt-0.5">In {{ $expDays }}d</div>
                                            @endif
                                        @endif
                                    @else <span class="text-slate-300">—</span> @endif
                                </td>
                                <td>
                                    <span class="status-badge {{ $statusColors[$a->status] ?? 'bg-slate-50 text-slate-600 border-slate-200' }}">
                                        {{ $statuses[$a->status] ?? $a->status }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('clinical.psr.authorizations.show', $a) }}" class="action-btn view" title="View">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        </a>
                                        @if($a->is_locked)
                                            <span class="action-btn" title="Approved — locked" style="cursor:default;opacity:.45;">
                                                <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                                            </span>
                                        @else
                                            @can('clinical.psr.authorizations.edit')
                                                <a href="{{ route('clinical.psr.authorizations.edit', $a) }}" class="action-btn edit" title="Edit">
                                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                                </a>
                                            @endcan
                                            @can('clinical.psr.authorizations.delete')
                                                <button type="button" class="action-btn delete" title="Delete"
                                                        onclick="confirmDeleteAuth({{ $a->id }}, {!! htmlspecialchars(json_encode($a->auth_number ?: '#'.$a->id), ENT_QUOTES) !!})">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                </button>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">
                {{ $auths->links() }}
            </div>
        @else
            <div class="p-14 text-center">
                <div class="mx-auto w-16 h-16 bg-slate-50 text-slate-200 rounded-2xl flex items-center justify-center mb-4">
                    <i data-lucide="shield-check" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-600">No authorizations found</h3>
                <p class="text-slate-400 text-sm mt-1.5">
                    @if($search || $status || $payerFilter || $dateFrom || $dateTo)
                        No results match your filters. <a href="{{ route('clinical.psr.authorizations.index') }}" class="text-blue-600 font-bold hover:underline">Clear filters</a>
                    @else
                        Create the first authorization from a patient's admission.
                    @endif
                </p>
            </div>
        @endif
    </div>
</div>

@can('clinical.psr.authorizations.delete')
    @foreach($auths as $a)
        <form id="delete-auth-{{ $a->id }}" action="{{ route('clinical.psr.authorizations.destroy', $a) }}" method="POST" style="display:none;">
            @csrf @method('DELETE')
        </form>
    @endforeach
@endcan

<script>
document.addEventListener('DOMContentLoaded', () => { if (typeof lucide !== 'undefined') lucide.createIcons(); });

function togglePanel(name) {
    document.getElementById('panel-' + name)?.classList.toggle('open');
    document.getElementById('chev-' + name)?.classList.toggle('open');
}

function confirmDeleteAuth(id, label) {
    Swal.fire({
        icon: 'warning',
        title: '<span style="font-size:1rem;font-weight:900;text-transform:uppercase">Delete authorization</span>',
        html: '<div style="text-align:left;padding:.5rem 0">'
            + '<div style="background:#fef2f2;border:1.5px solid #fca5a5;border-radius:12px;padding:1.15rem;margin-bottom:1rem">'
            + '<p style="font-size:.85rem;color:#991b1b;font-weight:700;margin:0">'
            + 'You are about to <strong>permanently delete</strong> authorization:</p>'
            + '<p style="font-size:1.05rem;color:#dc2626;font-weight:900;margin:.65rem 0 0;text-transform:uppercase">' + label + '</p>'
            + '</div>'
            + '<p style="font-size:.8rem;color:#64748b;line-height:1.6;margin:0">'
            + 'This will detach service-log entries from the authorization. Service-log rows themselves are preserved.'
            + '</p></div>',
        showCancelButton: true,
        confirmButtonText: 'Delete permanently',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        width: 480,
        reverseButtons: true,
        customClass: { popup: 'rounded-2xl' }
    }).then((result) => { if (result.isConfirmed) document.getElementById('delete-auth-' + id).submit(); });
}
</script>
@endsection
