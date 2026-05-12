@extends('layouts.app')

@section('title', 'PSR Dashboard')

@section('content')

@include('clinical.psr._service_styles')

<div class="space-y-7">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-600 flex items-center justify-center">
                    <i data-lucide="blocks" class="w-5 h-5"></i>
                </div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight">Psychosocial Rehabilitation</h1>
            </div>
            <p class="text-sm text-slate-500 ml-[52px]">{{ $client?->name ?? '—' }} &middot; Overview &amp; Metrics</p>
        </div>
        @can('clinical.psr.admissions.create')
            <a href="{{ route('clinical.psr.admissions.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-blue-500/25 transition-all">
                <i data-lucide="plus" class="w-4 h-4"></i> New admission
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <div class="svc-stat">
            <div class="svc-stat-icon bg-blue-50 text-blue-600"><i data-lucide="users" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800">{{ $admissionStats['admitted'] }}</div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active</div>
            </div>
        </div>
        <div class="svc-stat">
            <div class="svc-stat-icon bg-emerald-50 text-emerald-600"><i data-lucide="check-circle" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800">{{ $admissionStats['total'] }}</div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total</div>
            </div>
        </div>
        <div class="svc-stat">
            <div class="svc-stat-icon bg-amber-50 text-amber-600"><i data-lucide="key-round" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800">{{ $authStats['pending'] }}</div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pending Auth</div>
            </div>
        </div>
        <div class="svc-stat">
            <div class="svc-stat-icon bg-red-50 text-red-600"><i data-lucide="file-warning" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800">{{ $noteStats['unsigned'] }}</div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Unsigned Notes</div>
            </div>
        </div>
        <div class="svc-stat">
            <div class="svc-stat-icon bg-violet-50 text-violet-600"><i data-lucide="receipt" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800">{{ $billingStats['unbilled'] }}</div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Unbilled</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- ADMISSIONS BY STATUS --}}
        <div class="svc-card">
            <div class="svc-card-header">
                <h3 class="text-sm font-bold text-slate-700">Admission status</h3>
                @can('clinical.psr.admissions.view')
                    <a href="{{ route('clinical.psr.admissions.index') }}" class="text-xs font-bold text-blue-600 hover:underline">View all</a>
                @endcan
            </div>
            <div class="svc-card-body space-y-3">
                @php $admTotal = max($admissionStats['total'], 1); @endphp
                @php
                    $bars = [
                        ['label' => 'Admitted',        'count' => $admissionStats['admitted'],        'color' => 'bg-emerald-500', 'bg' => 'bg-emerald-50'],
                        ['label' => 'Intake complete', 'count' => $admissionStats['intake_complete'], 'color' => 'bg-blue-500',    'bg' => 'bg-blue-50'],
                        ['label' => 'Pending intake',  'count' => $admissionStats['pending_intake'],  'color' => 'bg-amber-500',   'bg' => 'bg-amber-50'],
                        ['label' => 'On hold',         'count' => $admissionStats['hold'],            'color' => 'bg-orange-400',  'bg' => 'bg-orange-50'],
                        ['label' => 'Discharged',      'count' => $admissionStats['discharged'],      'color' => 'bg-slate-400',   'bg' => 'bg-slate-100'],
                    ];
                @endphp
                @foreach($bars as $bar)
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-bold text-slate-600">{{ $bar['label'] }}</span>
                            <span class="text-xs font-black text-slate-800">{{ $bar['count'] }}</span>
                        </div>
                        <div class="h-2 rounded-full {{ $bar['bg'] }} overflow-hidden">
                            <div class="h-full rounded-full {{ $bar['color'] }} transition-all duration-500" style="width: {{ round(($bar['count'] / $admTotal) * 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- AUTHORIZATIONS --}}
        <div class="svc-card">
            <div class="svc-card-header">
                <h3 class="text-sm font-bold text-slate-700">Authorizations</h3>
                @can('clinical.psr.authorizations.view')
                    <a href="{{ route('clinical.psr.authorizations.index') }}" class="text-xs font-bold text-blue-600 hover:underline">View all</a>
                @endcan
            </div>
            <div class="svc-card-body">
                <div class="grid grid-cols-2 gap-3">
                    <div class="text-center p-3 rounded-xl bg-emerald-50">
                        <div class="text-xl font-black text-emerald-600">{{ $authStats['approved'] }}</div>
                        <div class="text-[10px] font-bold text-emerald-600/70 uppercase">Approved</div>
                    </div>
                    <div class="text-center p-3 rounded-xl bg-amber-50">
                        <div class="text-xl font-black text-amber-600">{{ $authStats['pending'] }}</div>
                        <div class="text-[10px] font-bold text-amber-600/70 uppercase">Pending</div>
                    </div>
                    <div class="text-center p-3 rounded-xl bg-red-50">
                        <div class="text-xl font-black text-red-600">{{ $authStats['denied'] }}</div>
                        <div class="text-[10px] font-bold text-red-600/70 uppercase">Denied</div>
                    </div>
                    <div class="text-center p-3 rounded-xl bg-slate-100">
                        <div class="text-xl font-black text-slate-600">{{ $authStats['expired'] }}</div>
                        <div class="text-[10px] font-bold text-slate-500/70 uppercase">Expired</div>
                    </div>
                </div>
                @if($authStats['expiring_soon'] > 0)
                    <div class="mt-4 p-3 rounded-xl bg-amber-50 border border-amber-200 flex items-center gap-3">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 flex-shrink-0"></i>
                        <span class="text-xs font-bold text-amber-700">{{ $authStats['expiring_soon'] }} authorization(s) expiring within 30 days</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- BILLING & SESSIONS --}}
        <div class="svc-card">
            <div class="svc-card-header">
                <h3 class="text-sm font-bold text-slate-700">Billing &amp; sessions</h3>
                @can('clinical.psr.service_log.view')
                    <a href="{{ route('clinical.psr.service_log.index') }}" class="text-xs font-bold text-blue-600 hover:underline">Service log</a>
                @endcan
            </div>
            <div class="svc-card-body space-y-4">
                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                    <div class="text-xs font-bold text-slate-500">Total revenue</div>
                    <div class="text-lg font-black text-emerald-600">${{ number_format($billingStats['total_paid'], 2) }}</div>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                    <div class="text-xs font-bold text-slate-500">Total units</div>
                    <div class="text-lg font-black text-slate-800">{{ number_format($billingStats['total_units']) }}</div>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                    <div class="text-xs font-bold text-slate-500">Sessions this month</div>
                    <div class="text-lg font-black text-blue-600">{{ $sessionsCompleted }}<span class="text-sm text-slate-400 font-bold">/{{ $sessionsThisMonth }}</span></div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="text-center p-2 rounded-lg bg-blue-50">
                        <div class="text-sm font-black text-blue-600">{{ $billingStats['submitted'] }}</div>
                        <div class="text-[9px] font-bold text-blue-500/70 uppercase">Submitted</div>
                    </div>
                    <div class="text-center p-2 rounded-lg bg-red-50">
                        <div class="text-sm font-black text-red-600">{{ $billingStats['denied'] }}</div>
                        <div class="text-[9px] font-bold text-red-500/70 uppercase">Denied</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="svc-card">
        <div class="svc-card-header">
            <h3 class="text-sm font-bold text-slate-700">Recent admissions</h3>
            @can('clinical.psr.admissions.view')
                <a href="{{ route('clinical.psr.admissions.index') }}" class="text-xs font-bold text-blue-600 hover:underline">View all</a>
            @endcan
        </div>
        <div class="overflow-x-auto">
            <table class="svc-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Therapist</th>
                        <th>Clinic</th>
                        <th>Admission date</th>
                        <th style="text-align:center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentAdmissions as $adm)
                        <tr>
                            <td class="font-bold text-slate-800" style="white-space:nowrap;">
                                <a href="{{ route('clinical.psr.admissions.show', $adm) }}" class="hover:text-blue-600">{{ $adm->patient?->full_name ?? '—' }}</a>
                            </td>
                            <td style="white-space:nowrap;">{{ $adm->assignedTherapist?->full_name ?? '—' }}</td>
                            <td style="white-space:nowrap;">{{ $adm->clinic?->name ?? '—' }}</td>
                            <td style="white-space:nowrap;">{{ optional($adm->admission_date)->format('M j, Y') ?: '—' }}</td>
                            <td style="text-align:center;"><span class="svc-badge status-{{ $adm->status }}">{{ \App\Models\Psr\Admission::STATUSES[$adm->status] ?? $adm->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-slate-400 py-8">No admissions yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        {{-- Compliance snapshot --}}
        <div class="svc-card">
            <div class="svc-card-header">
                <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
                    <i data-lucide="shield-check" class="w-4 h-4 text-amber-500"></i> Compliance snapshot
                </h3>
            </div>
            <div class="svc-card-body">
                @php
                    $compItems = [
                        ['label' => 'Missing Bio Assessment', 'count' => $complianceStats['missing_bio'],  'icon' => 'file-x',      'color' => 'red'],
                        ['label' => 'Missing Treatment Plan', 'count' => $complianceStats['missing_mtp'],  'icon' => 'file-x-2',    'color' => 'red'],
                        ['label' => 'Missing FARS',           'count' => $complianceStats['missing_fars'], 'icon' => 'bar-chart-3', 'color' => 'amber'],
                        ['label' => 'No active authorization','count' => $complianceStats['no_auth'],      'icon' => 'key-round',   'color' => 'amber'],
                    ];
                    $totalGaps = collect($compItems)->sum('count');
                @endphp

                @if($totalGaps === 0)
                    <div class="flex items-center gap-3 p-4 rounded-xl bg-emerald-50">
                        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500"></i>
                        <span class="text-sm font-bold text-emerald-700">All active admissions are fully compliant</span>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($compItems as $item)
                            @if($item['count'] > 0)
                                <div class="flex items-center justify-between p-3 rounded-xl bg-{{ $item['color'] }}-50">
                                    <div class="flex items-center gap-3">
                                        <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 text-{{ $item['color'] }}-500"></i>
                                        <span class="text-sm font-semibold text-slate-700">{{ $item['label'] }}</span>
                                    </div>
                                    <span class="text-sm font-black text-{{ $item['color'] }}-600">{{ $item['count'] }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- At-risk --}}
        <div class="svc-card">
            <div class="svc-card-header">
                <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-red-500"></i> At-risk patients
                </h3>
            </div>
            <div class="svc-card-body">
                @if($atRiskAdmissions->isEmpty())
                    <div class="flex items-center gap-3 p-4 rounded-xl bg-slate-50">
                        <i data-lucide="smile" class="w-5 h-5 text-emerald-500"></i>
                        <span class="text-sm font-bold text-slate-500">No at-risk patients detected</span>
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach($atRiskAdmissions as $risk)
                            <a href="{{ route('clinical.psr.admissions.show', $risk) }}" class="flex items-center justify-between p-3 rounded-xl bg-red-50 hover:bg-red-100 transition">
                                <div>
                                    <div class="text-sm font-bold text-slate-800">{{ $risk->patient?->first_name }} {{ substr($risk->patient?->last_name ?? '', 0, 1) }}.</div>
                                    <div class="text-[10px] text-slate-400">{{ $risk->clinic?->name ?? '' }} &middot; {{ $risk->assignedTherapist?->first_name ?? 'Unassigned' }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-black {{ $risk->risk_score >= 80 ? 'text-red-600' : 'text-amber-600' }}">{{ $risk->risk_score }}</div>
                                    <div class="text-[9px] font-bold text-slate-400 uppercase">Risk score</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div>
        <h2 class="text-[.7rem] font-extrabold text-slate-400 uppercase tracking-widest mb-4">Quick access</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
            @php
                $quickLinks = [
                    ['route' => 'clinical.psr.admissions.index',      'icon' => 'clipboard-list', 'name' => 'Admissions',      'color' => 'blue',   'permission' => 'clinical.psr.admissions.view'],
                    ['route' => 'clinical.psr.assessments.index',     'icon' => 'brain',          'name' => 'Assessments',     'color' => 'blue',   'permission' => 'clinical.psr.assessments.view'],
                    ['route' => 'clinical.psr.treatment_plans.index', 'icon' => 'file-check-2',   'name' => 'Treatment plans', 'color' => 'blue',   'permission' => 'clinical.psr.treatment_plans.view'],
                    ['route' => 'clinical.psr.authorizations.index',  'icon' => 'key-round',      'name' => 'Authorizations',  'color' => 'blue',   'permission' => 'clinical.psr.authorizations.view'],
                    ['route' => 'clinical.psr.group_sessions.index',  'icon' => 'users',          'name' => 'Group sessions',  'color' => 'blue',   'permission' => 'clinical.psr.group_sessions.view'],
                    ['route' => 'clinical.psr.progress_notes.index',  'icon' => 'file-text',      'name' => 'Progress notes',  'color' => 'blue',   'permission' => 'clinical.psr.progress_notes.view'],
                    ['route' => 'clinical.psr.service_log.index',     'icon' => 'calendar-check', 'name' => 'Service log',     'color' => 'blue',   'permission' => 'clinical.psr.service_log.view'],
                    ['route' => 'clinical.psr.superbill.index',       'icon' => 'table-2',        'name' => 'Superbill',       'color' => 'amber',  'permission' => 'clinical.psr.superbill.view'],
                    ['route' => 'clinical.psr.discharges.index',      'icon' => 'log-out',        'name' => 'Discharges',      'color' => 'red',    'permission' => 'clinical.psr.discharges.view'],
                ];
            @endphp
            @foreach($quickLinks as $link)
                @can($link['permission'])
                    <a href="{{ route($link['route']) }}" class="svc-link">
                        <div class="svc-link-icon bg-{{ $link['color'] }}-50 text-{{ $link['color'] }}-600">
                            <i data-lucide="{{ $link['icon'] }}" class="w-4 h-4"></i>
                        </div>
                        <span class="text-xs font-bold text-slate-700">{{ $link['name'] }}</span>
                    </a>
                @endcan
            @endforeach
        </div>
    </div>
</div>

<script>document.addEventListener('DOMContentLoaded', () => { if (typeof lucide !== 'undefined') lucide.createIcons(); });</script>
@endsection
