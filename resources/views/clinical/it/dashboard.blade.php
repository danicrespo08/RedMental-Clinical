@extends('layouts.app')
@section('title', 'IT — Dashboard')

@section('content')
@include('clinical.psr._service_styles')

<div class="space-y-7 max-w-7xl mx-auto">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <div class="w-10 h-10 rounded-xl bg-violet-500/10 text-violet-600 flex items-center justify-center">
                    <i data-lucide="user-round-search" class="w-5 h-5"></i>
                </div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight">Individual Therapy</h1>
            </div>
            <p class="text-sm text-slate-500 ml-[52px]">{{ $client?->name ?? '—' }} &middot; Overview &amp; Metrics</p>
        </div>
        @can('clinical.it.create')
            <a href="{{ route('clinical.it.admissions.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-violet-500/25 transition-all">
                <i data-lucide="plus" class="w-4 h-4"></i> New admission
            </a>
        @endcan
    </div>

    {{-- 5 STAT CARDS --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <div class="svc-stat">
            <div class="svc-stat-icon bg-emerald-50 text-emerald-600"><i data-lucide="users" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800">{{ $admissionStats['admitted'] }}</div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active</div>
            </div>
        </div>
        <div class="svc-stat">
            <div class="svc-stat-icon bg-blue-50 text-blue-600"><i data-lucide="users-round" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800">{{ $admissionStats['total'] }}</div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total</div>
            </div>
        </div>
        <div class="svc-stat">
            <div class="svc-stat-icon bg-amber-50 text-amber-600"><i data-lucide="pause-circle" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800">{{ $admissionStats['hold'] }}</div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">On hold</div>
            </div>
        </div>
        <div class="svc-stat">
            <div class="svc-stat-icon bg-violet-50 text-violet-600"><i data-lucide="calendar-check" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800">{{ $sessionStats['this_month'] }}</div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Sessions / month</div>
            </div>
        </div>
        <div class="svc-stat">
            <div class="svc-stat-icon bg-purple-50 text-purple-600"><i data-lucide="hash" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800">{{ $sessionStats['units_month'] }}</div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Units / month</div>
            </div>
        </div>
    </div>

    {{-- QUICK LINKS --}}
    <div>
        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3">Quick links</div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            @php
                $quickLinks = [
                    ['route' => 'clinical.it.admissions.index',      'icon' => 'users',          'name' => 'Admissions',      'color' => 'violet',  'permission' => 'clinical.it.view'],
                    ['route' => 'clinical.it.sessions.index',        'icon' => 'calendar-check', 'name' => 'Sessions',        'color' => 'blue',    'permission' => 'clinical.it.view'],
                    ['route' => 'clinical.it.treatment_plans.index', 'icon' => 'list-checks',    'name' => 'Treatment plans', 'color' => 'indigo',  'permission' => 'clinical.it.treatment_plans.view'],
                    ['route' => 'clinical.it.authorizations.index',  'icon' => 'key-round',      'name' => 'Authorizations',  'color' => 'emerald', 'permission' => 'clinical.it.authorizations.view'],
                    ['route' => 'clinical.it.service_log.index',     'icon' => 'list',           'name' => 'Service log',     'color' => 'purple',  'permission' => 'clinical.it.service_log.view'],
                    ['route' => 'clinical.it.superbill.index',       'icon' => 'table-2',        'name' => 'Superbill',       'color' => 'amber',   'permission' => 'clinical.it.superbill.view'],
                    ['route' => 'clinical.it.discharges.index',      'icon' => 'log-out',        'name' => 'Discharges',      'color' => 'rose',    'permission' => 'clinical.it.discharges.view'],
                ];
            @endphp
            @foreach($quickLinks as $link)
                @can($link['permission'])
                    <a href="{{ route($link['route']) }}" class="svc-link">
                        <div class="svc-link-icon bg-{{ $link['color'] }}-50 text-{{ $link['color'] }}-600">
                            <i data-lucide="{{ $link['icon'] }}" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <div class="font-bold text-slate-800 text-sm">{{ $link['name'] }}</div>
                        </div>
                    </a>
                @endcan
            @endforeach
        </div>
    </div>

    {{-- CONTENT GRID --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        {{-- Recent admissions --}}
        <div class="svc-card">
            <div class="svc-card-header">
                <h3 class="font-bold text-slate-800 text-sm">Recent admissions</h3>
                <a href="{{ route('clinical.it.admissions.index') }}" class="text-[11px] font-bold text-violet-600 hover:text-violet-700">View all →</a>
            </div>
            <div class="svc-card-body p-0">
                <table class="svc-table">
                    <thead><tr><th>Patient</th><th>Therapist</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($recentAdmissions as $admission)
                            <tr>
                                <td>
                                    <a href="{{ route('clinical.it.admissions.show', $admission) }}" class="font-bold hover:text-violet-700">{{ $admission->patient?->full_name ?? '—' }}</a>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $admission->patient?->mrn }}</div>
                                </td>
                                <td>{{ $admission->therapist?->full_name ?? '—' }}</td>
                                <td><span class="svc-badge status-{{ $admission->status }}">{{ \App\Models\It\Admission::STATUSES[$admission->status] ?? $admission->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-slate-400 py-6">No admissions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent sessions + CPT mix --}}
        <div class="space-y-5">
            <div class="svc-card">
                <div class="svc-card-header">
                    <h3 class="font-bold text-slate-800 text-sm">Recent sessions</h3>
                    <a href="{{ route('clinical.it.sessions.index') }}" class="text-[11px] font-bold text-violet-600 hover:text-violet-700">View all →</a>
                </div>
                <div class="svc-card-body p-0">
                    <table class="svc-table">
                        <thead><tr><th>Date</th><th>Patient</th><th>CPT</th><th class="text-right">Units</th></tr></thead>
                        <tbody>
                            @forelse($recentSessions as $session)
                                <tr>
                                    <td class="font-semibold text-[12px]">{{ $session->session_date->format('M j') }}</td>
                                    <td class="text-[12px]">{{ $session->admission?->patient?->full_name ?? '—' }}</td>
                                    <td><span class="font-mono text-[10px] font-bold bg-violet-50 text-violet-700 border border-violet-200 px-1.5 py-0.5 rounded">{{ $session->cpt_code }}</span></td>
                                    <td class="text-right font-mono font-bold text-blue-600">{{ $session->units }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-slate-400 py-6">No sessions yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($cptMix->count() > 0)
                <div class="svc-card">
                    <div class="svc-card-header">
                        <h3 class="font-bold text-slate-800 text-sm">CPT mix · this month</h3>
                    </div>
                    <div class="svc-card-body">
                        <div class="space-y-2">
                            @foreach($cptMix as $row)
                                <div class="flex items-center gap-3">
                                    <span class="font-mono text-[10px] font-bold bg-violet-50 text-violet-700 border border-violet-200 px-2 py-0.5 rounded min-w-[80px] text-center">{{ $row->cpt_code }}</span>
                                    <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-violet-400 to-purple-600" style="width: {{ min(100, ($row->count / max(1, $cptMix->max('count'))) * 100) }}%;"></div>
                                    </div>
                                    <span class="font-mono text-[12px] font-bold text-slate-700 min-w-[40px] text-right">{{ $row->count }}</span>
                                    <span class="font-mono text-[10px] text-slate-400 min-w-[60px] text-right">{{ $row->units }}u</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
