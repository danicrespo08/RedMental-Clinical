@extends('layouts.app')
@section('title', 'TCM — Dashboard')

@section('content')
@include('clinical.psr._service_styles')

@php
    use App\Models\Tcm\Contact;
    $typeIcons = [
        'in_person' => 'user-check', 'phone' => 'phone', 'video' => 'video',
        'email' => 'mail', 'collateral' => 'users', 'home_visit' => 'home',
    ];
@endphp

<div class="space-y-7 max-w-7xl mx-auto">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <div class="w-10 h-10 rounded-xl bg-orange-500/10 text-orange-600 flex items-center justify-center">
                    <i data-lucide="clipboard-list" class="w-5 h-5"></i>
                </div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight">Targeted Case Management</h1>
            </div>
            <p class="text-sm text-slate-500 ml-[52px]">{{ $client?->name ?? '—' }} &middot; Overview &amp; Metrics</p>
        </div>
        @can('clinical.tcm.create')
            <a href="{{ route('clinical.tcm.admissions.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-orange-500/25 transition-all">
                <i data-lucide="plus" class="w-4 h-4"></i> New admission
            </a>
        @endcan
    </div>

    {{-- 5 STATS --}}
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
            <div class="svc-stat-icon bg-orange-50 text-orange-600"><i data-lucide="phone" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800">{{ $contactStats['this_month'] }}</div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Contacts / month</div>
            </div>
        </div>
        <div class="svc-stat">
            <div class="svc-stat-icon bg-purple-50 text-purple-600"><i data-lucide="timer" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800">{{ $contactStats['minutes_month'] }}</div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Minutes / month</div>
            </div>
        </div>
    </div>

    {{-- QUICK LINKS --}}
    <div>
        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3">Quick links</div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            @php
                $quickLinks = [
                    ['route' => 'clinical.tcm.admissions.index',      'icon' => 'users',       'name' => 'Admissions',     'color' => 'orange',  'permission' => 'clinical.tcm.view'],
                    ['route' => 'clinical.tcm.contacts.index',        'icon' => 'phone',       'name' => 'Contacts',       'color' => 'blue',    'permission' => 'clinical.tcm.view'],
                    ['route' => 'clinical.tcm.treatment_plans.index', 'icon' => 'list-checks', 'name' => 'Service plans',  'color' => 'indigo',  'permission' => 'clinical.tcm.treatment_plans.view'],
                    ['route' => 'clinical.tcm.authorizations.index',  'icon' => 'key-round',   'name' => 'Authorizations', 'color' => 'emerald', 'permission' => 'clinical.tcm.authorizations.view'],
                    ['route' => 'clinical.tcm.service_log.index',     'icon' => 'list',        'name' => 'Service log',    'color' => 'purple',  'permission' => 'clinical.tcm.service_log.view'],
                    ['route' => 'clinical.tcm.superbill.index',       'icon' => 'table-2',     'name' => 'Superbill',      'color' => 'amber',   'permission' => 'clinical.tcm.superbill.view'],
                    ['route' => 'clinical.tcm.discharges.index',      'icon' => 'log-out',     'name' => 'Discharges',     'color' => 'rose',    'permission' => 'clinical.tcm.discharges.view'],
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
                <a href="{{ route('clinical.tcm.admissions.index') }}" class="text-[11px] font-bold text-orange-600 hover:text-orange-700">View all →</a>
            </div>
            <div class="svc-card-body p-0">
                <table class="svc-table">
                    <thead><tr><th>Patient</th><th>Case manager</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($recentAdmissions as $admission)
                            <tr>
                                <td>
                                    <a href="{{ route('clinical.tcm.admissions.show', $admission) }}" class="font-bold hover:text-orange-700">{{ $admission->patient?->full_name ?? '—' }}</a>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $admission->patient?->mrn }}</div>
                                </td>
                                <td>{{ $admission->caseManager?->full_name ?? '—' }}</td>
                                <td><span class="svc-badge status-{{ $admission->status }}">{{ \App\Models\Tcm\Admission::STATUSES[$admission->status] ?? $admission->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-slate-400 py-6">No admissions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent contacts + type mix --}}
        <div class="space-y-5">
            <div class="svc-card">
                <div class="svc-card-header">
                    <h3 class="font-bold text-slate-800 text-sm">Recent contacts</h3>
                    <a href="{{ route('clinical.tcm.contacts.index') }}" class="text-[11px] font-bold text-orange-600 hover:text-orange-700">View all →</a>
                </div>
                <div class="svc-card-body p-0">
                    <table class="svc-table">
                        <thead><tr><th>When</th><th>Patient</th><th>Type</th><th class="text-right">Min</th></tr></thead>
                        <tbody>
                            @forelse($recentContacts as $contact)
                                <tr>
                                    <td class="font-semibold text-[12px]">{{ $contact->contact_at->format('M j') }}</td>
                                    <td class="text-[12px]">{{ $contact->admission?->patient?->full_name ?? '—' }}</td>
                                    <td>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-orange-700 bg-orange-50 border border-orange-200 px-1.5 py-0.5 rounded">
                                            <i data-lucide="{{ $typeIcons[$contact->contact_type] ?? 'circle' }}" class="w-3 h-3"></i> {{ Contact::CONTACT_TYPES[$contact->contact_type] ?? $contact->contact_type }}
                                        </span>
                                    </td>
                                    <td class="text-right font-mono font-bold text-blue-600">{{ $contact->duration_minutes ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-slate-400 py-6">No contacts yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($typeMix->count() > 0)
                <div class="svc-card">
                    <div class="svc-card-header">
                        <h3 class="font-bold text-slate-800 text-sm">Contact-type mix · this month</h3>
                    </div>
                    <div class="svc-card-body">
                        <div class="space-y-2">
                            @foreach($typeMix as $row)
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-orange-700 bg-orange-50 border border-orange-200 px-2 py-0.5 rounded min-w-[120px]">
                                        <i data-lucide="{{ $typeIcons[$row->contact_type] ?? 'circle' }}" class="w-3 h-3"></i>
                                        {{ Contact::CONTACT_TYPES[$row->contact_type] ?? $row->contact_type }}
                                    </span>
                                    <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-orange-400 to-amber-600" style="width: {{ min(100, ($row->count / max(1, $typeMix->max('count'))) * 100) }}%;"></div>
                                    </div>
                                    <span class="font-mono text-[12px] font-bold text-slate-700 min-w-[40px] text-right">{{ $row->count }}</span>
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
