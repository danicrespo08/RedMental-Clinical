@extends('layouts.app')

@section('title', 'PSR Admissions | RedMental')

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
    .stat-card.accent-emerald { --accent: #34d399; }
    .stat-card.accent-amber   { --accent: #fbbf24; }
    .stat-card.accent-slate   { --accent: #94a3b8; }
    .stat-card.accent-blue    { --accent: #60a5fa; }

    .data-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .data-table th {
        background: linear-gradient(180deg, #f8fafc, #f1f5f9);
        padding: .85rem 1.15rem; font-size: .58rem; font-weight: 800;
        color: #94a3b8; text-transform: uppercase; letter-spacing: .05em;
        border-bottom: 1px solid #e2e8f0; text-align: left; white-space: nowrap;
    }
    .data-table td {
        padding: .9rem 1.15rem; font-size: .8rem; color: #334155;
        border-bottom: 1px solid #f1f5f9; vertical-align: middle;
    }
    .data-table tbody tr { transition: background .15s ease; }
    .data-table tbody tr:hover td { background-color: #fafbff; }

    .avatar {
        width: 38px; height: 38px; border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #4338ca);
        color: #fff; display: inline-flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: .75rem; flex-shrink: 0; letter-spacing: .02em;
        box-shadow: 0 2px 5px -1px rgba(99,102,241,.4);
    }
    .avatar.discharged { background: linear-gradient(135deg, #94a3b8, #64748b); box-shadow: 0 2px 5px -1px rgba(100,116,139,.3); }
    .avatar.hold       { background: linear-gradient(135deg, #fb923c, #ea580c); box-shadow: 0 2px 5px -1px rgba(234,88,12,.3); }
    .avatar.pending    { background: linear-gradient(135deg, #fbbf24, #d97706); box-shadow: 0 2px 5px -1px rgba(217,119,6,.3); }

    .action-btn {
        width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; border: 1px solid #e2e8f0; background: #fff;
        color: #94a3b8; transition: all .2s ease; cursor: pointer; text-decoration: none;
    }
    .action-btn.view:hover   { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
    .action-btn.edit:hover   { background: #fef3c7; color: #d97706; border-color: #fcd34d; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .status-badge {
        display: inline-flex; align-items: center; gap: .25rem; padding: .25rem .6rem;
        border-radius: .4rem; font-size: .55rem; font-weight: 800; text-transform: uppercase;
        letter-spacing: .04em; border: 1px solid transparent; white-space: nowrap;
    }

    .filter-input {
        border: 1px solid #e2e8f0; border-radius: .6rem; padding: .45rem .7rem;
        font-size: .8rem; font-weight: 500; outline: none; transition: all .2s;
        background: #fff;
    }
    .filter-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.08); }

    /* Compliance chips */
    .doc-chips { display: inline-flex; gap: .25rem; align-items: center; }
    .doc-chip {
        width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center;
        border-radius: 6px; font-size: .58rem; font-weight: 900; letter-spacing: .03em;
        cursor: help; transition: all .15s ease; position: relative;
    }
    .doc-chip:hover { transform: scale(1.1); z-index: 5; }
    .doc-chip.ok      { background: #dcfce7; color: #15803d; box-shadow: inset 0 0 0 1px #86efac; }
    .doc-chip.partial { background: #fef3c7; color: #b45309; box-shadow: inset 0 0 0 1px #fcd34d; }
    .doc-chip.missing { background: #fee2e2; color: #b91c1c; box-shadow: inset 0 0 0 1px #fca5a5; }

    /* Tiny completion bar in patient cell */
    .completion-bar { height: 4px; border-radius: 99px; background: #f1f5f9; overflow: hidden; min-width: 80px; max-width: 120px; }
    .completion-bar > div { height: 100%; border-radius: 99px; transition: width .35s ease; }
    .completion-fill-100 { background: linear-gradient(90deg, #10b981, #059669); }
    .completion-fill-75  { background: linear-gradient(90deg, #34d399, #10b981); }
    .completion-fill-50  { background: linear-gradient(90deg, #fbbf24, #f59e0b); }
    .completion-fill-25  { background: linear-gradient(90deg, #fb923c, #ea580c); }
    .completion-fill-0   { background: #e2e8f0; }

    .header-card {
        background:
            radial-gradient(circle at 100% 0, rgba(59,130,246,.08), transparent 50%),
            radial-gradient(circle at 0 100%, rgba(168,85,247,.06), transparent 50%),
            #fff;
        border: 1px solid #e2e8f0;
    }

    @keyframes pulse-soft { 0%, 100% { opacity: 1 } 50% { opacity: .7 } }
    .pulse-soft { animation: pulse-soft 2s ease-in-out infinite; }
</style>

@php
    $statusColors = [
        'admitted'        => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'on_hold'         => 'bg-orange-50 text-orange-700 border-orange-200',
        'pending_intake'  => 'bg-amber-50 text-amber-700 border-amber-200',
        'intake_complete' => 'bg-blue-50 text-blue-700 border-blue-200',
        'discharged'      => 'bg-slate-50 text-slate-500 border-slate-200',
    ];

    // Compliance helper — returns ['state' => 'ok|partial|missing', 'tip' => '...']
    $checkBio = function ($adm) {
        $bio = $adm->bioAssessment;
        if (! $bio)            return ['state' => 'missing', 'tip' => 'No bio-psychosocial assessment'];
        if (! $bio->is_signed) return ['state' => 'partial', 'tip' => 'Bio assessment started but unsigned'];
        return ['state' => 'ok', 'tip' => 'Bio assessment signed'];
    };
    $checkPlan = function ($adm) {
        $plan = $adm->treatmentPlans->sortByDesc('start_date')->first();
        if (! $plan)            return ['state' => 'missing', 'tip' => 'No treatment plan (MTP)'];
        if (! $plan->is_signed) return ['state' => 'partial', 'tip' => 'MTP draft, not signed'];
        return ['state' => 'ok', 'tip' => 'MTP signed (' . $plan->start_date->format('M j') . ' → ' . $plan->end_date->format('M j, Y') . ')'];
    };
    $checkFars = function ($adm) {
        $fars = $adm->farsAssessments;
        if ($fars->isEmpty())                                              return ['state' => 'missing', 'tip' => 'No FARS recorded'];
        if (! $fars->contains(fn ($f) => $f->evaluation_type === 'admission')) return ['state' => 'partial', 'tip' => 'FARS recorded but no baseline (admission type)'];
        return ['state' => 'ok', 'tip' => $fars->count() . ' FARS recorded'];
    };
    $checkAuth = function ($adm) {
        $approved = $adm->authorizations->first(fn ($a) => $a->status === 'approved'
            && (is_null($a->approved_end_date) || $a->approved_end_date->isFuture()));
        if (! $approved && $adm->authorizations->isEmpty()) return ['state' => 'missing', 'tip' => 'No authorization on file'];
        if (! $approved)                                    return ['state' => 'partial', 'tip' => 'Authorization not yet approved or expired'];
        return ['state' => 'ok', 'tip' => 'Active authorization (expires ' . $approved->approved_end_date?->format('M j, Y') . ')'];
    };
    $checkIntake = function ($adm) {
        if (! $adm->intake)            return ['state' => 'missing', 'tip' => 'No intake form'];
        if (! $adm->intake->is_signed) return ['state' => 'partial', 'tip' => 'Intake started, unsigned'];
        return ['state' => 'ok', 'tip' => 'Intake signed'];
    };
@endphp

<div class="max-w-7xl mx-auto">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-7 gap-4 header-card p-5 rounded-2xl shadow-sm">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('clinical.psr.dashboard') }}"
               class="w-9 h-9 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors border border-slate-200 flex-shrink-0">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div class="p-2.5 bg-gradient-to-br from-blue-500 to-indigo-600 text-white rounded-xl shadow-md shadow-blue-500/30">
                <i data-lucide="clipboard-list" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-xl font-black text-slate-800 tracking-tight uppercase">PSR Admissions</h1>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Intake &amp; enrollment management</p>
            </div>
        </div>
        @can('clinical.psr.admissions.create')
            <a href="{{ route('clinical.psr.admissions.create') }}"
               class="bg-gradient-to-br from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider shadow-lg shadow-blue-500/30 flex items-center gap-2 transition-all hover:-translate-y-0.5">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> New admission
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
        <div class="stat-card accent-blue">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 text-blue-600 flex items-center justify-center"><i data-lucide="users" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800 leading-tight">{{ $stats['total'] }}</div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Total</div>
            </div>
        </div>
        <div class="stat-card accent-emerald">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-50 to-green-50 text-emerald-600 flex items-center justify-center"><i data-lucide="circle-check" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-emerald-600 leading-tight">{{ $stats['admitted'] }}</div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Admitted</div>
            </div>
        </div>
        <div class="stat-card accent-amber">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-50 to-orange-50 text-orange-600 flex items-center justify-center"><i data-lucide="pause-circle" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-orange-600 leading-tight">{{ $stats['hold'] }}</div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">On hold</div>
            </div>
        </div>
        <div class="stat-card accent-slate">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-slate-50 to-slate-100 text-slate-500 flex items-center justify-center"><i data-lucide="log-out" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-500 leading-tight">{{ $stats['discharged'] }}</div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Discharged</div>
            </div>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-3 mb-5 flex flex-wrap items-center justify-between gap-3 text-[11px]">
        <div class="flex items-center gap-3 flex-wrap">
            <span class="font-bold text-slate-500 uppercase tracking-widest text-[9px]">Documentation status</span>
            <span class="flex items-center gap-1.5"><span class="doc-chip ok" style="cursor:default">✓</span><span class="text-slate-500">complete &amp; signed</span></span>
            <span class="flex items-center gap-1.5"><span class="doc-chip partial" style="cursor:default">~</span><span class="text-slate-500">started, unsigned</span></span>
            <span class="flex items-center gap-1.5"><span class="doc-chip missing" style="cursor:default">!</span><span class="text-slate-500">missing</span></span>
        </div>
        <div class="text-slate-400 text-[10px]">I = Intake · B = Bio · P = Plan/MTP · F = FARS · A = Auth</div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-3.5 mb-5">
        <form method="GET" class="flex flex-wrap items-center gap-2.5">
            <div class="relative flex-1 min-w-[160px] max-w-[280px]">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-300"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search MRN, name, clinic…"
                       class="w-full pl-8 pr-3 filter-input">
            </div>
            <select name="status" class="filter-input min-w-[140px]">
                <option value="">All statuses</option>
                @foreach(\App\Models\Psr\Admission::STATUSES as $key => $label)
                    <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="clinic_id" class="filter-input min-w-[140px]">
                <option value="">All clinics</option>
                @foreach($filterClinics as $fc)
                    <option value="{{ $fc->id }}" {{ (string) $clinicFilter === (string) $fc->id ? 'selected' : '' }}>{{ $fc->name }}</option>
                @endforeach
            </select>
            <div class="flex items-center gap-1.5">
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="filter-input w-[130px]" title="From">
                <span class="text-slate-300 text-[10px] font-bold">to</span>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="filter-input w-[130px]" title="To">
            </div>
            <button type="submit" class="bg-gradient-to-br from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-3.5 py-2 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-colors flex items-center gap-1.5 shadow-sm shadow-blue-500/20">
                <i data-lucide="filter" class="w-3 h-3"></i> Filter
            </button>
            @if($search || $status || $clinicFilter || $dateFrom || $dateTo)
                <a href="{{ route('clinical.psr.admissions.index') }}"
                   class="bg-slate-100 hover:bg-slate-200 text-slate-500 px-3.5 py-2 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-colors flex items-center gap-1.5">
                    <i data-lucide="x" class="w-3 h-3"></i> Clear
                </a>
            @endif
        </form>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        @if($admissions->count() > 0)
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>MRN</th>
                            <th>Clinic</th>
                            <th>Admitted</th>
                            <th>Therapist</th>
                            <th>Status</th>
                            <th>Documentation</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($admissions as $adm)
                            @php
                                $checks = [
                                    ['letter' => 'I', 'data' => $checkIntake($adm)],
                                    ['letter' => 'B', 'data' => $checkBio($adm)],
                                    ['letter' => 'P', 'data' => $checkPlan($adm)],
                                    ['letter' => 'F', 'data' => $checkFars($adm)],
                                    ['letter' => 'A', 'data' => $checkAuth($adm)],
                                ];
                                $okCount = collect($checks)->where('data.state', 'ok')->count();
                                $pct = (int) round(($okCount / count($checks)) * 100);
                                $fillClass = $pct === 100 ? 'completion-fill-100'
                                    : ($pct >= 75 ? 'completion-fill-75'
                                    : ($pct >= 50 ? 'completion-fill-50'
                                    : ($pct >= 25 ? 'completion-fill-25' : 'completion-fill-0')));

                                $avatarClass = match($adm->status) {
                                    'discharged' => 'discharged',
                                    'on_hold'    => 'hold',
                                    'pending_intake' => 'pending',
                                    default      => '',
                                };
                                $initials = strtoupper(
                                    mb_substr($adm->patient?->first_name ?? '?', 0, 1) .
                                    mb_substr($adm->patient?->last_name  ?? '?', 0, 1)
                                );
                            @endphp
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="avatar {{ $avatarClass }}">{{ $initials }}</div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-800 text-sm truncate">{{ $adm->patient?->full_name ?? '—' }}</div>
                                            <div class="flex items-center gap-2 mt-1">
                                                @if($adm->patient?->age)
                                                    <span class="text-[10px] text-slate-400 font-semibold">{{ $adm->patient->age }} y/o</span>
                                                @endif
                                                <div class="completion-bar"><div class="{{ $fillClass }}" style="width: {{ $pct }}%"></div></div>
                                                <span class="text-[10px] font-bold {{ $pct === 100 ? 'text-emerald-600' : ($pct >= 50 ? 'text-amber-600' : 'text-rose-600') }}">{{ $pct }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="font-mono text-xs font-bold text-slate-400">{{ $adm->patient?->mrn ?: '---' }}</td>
                                <td class="text-xs font-bold">{{ $adm->clinic?->name ?? '—' }}</td>
                                <td class="text-xs text-slate-500">{{ optional($adm->admission_date)->format('m/d/Y') ?: '—' }}</td>
                                <td class="text-xs text-slate-500">{{ $adm->assignedTherapist?->full_name ?? '---' }}</td>
                                <td>
                                    <span class="status-badge {{ $statusColors[$adm->status] ?? 'bg-slate-50 text-slate-600 border-slate-200' }}">
                                        {{ $adm->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="doc-chips">
                                        @foreach($checks as $c)
                                            <span class="doc-chip {{ $c['data']['state'] }}" title="{{ $c['letter'] }} — {{ $c['data']['tip'] }}">{{ $c['letter'] }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('clinical.psr.admissions.show', $adm) }}" class="action-btn view" title="View">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        </a>
                                        @can('clinical.psr.admissions.edit')
                                            <a href="{{ route('clinical.psr.admissions.edit', $adm) }}" class="action-btn edit" title="Edit">
                                                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                            </a>
                                        @endcan
                                        @can('clinical.psr.admissions.delete')
                                            <button type="button" class="action-btn delete" title="Delete"
                                                    onclick="confirmDelete({{ $adm->id }}, {!! htmlspecialchars(json_encode($adm->patient?->full_name ?? 'this admission'), ENT_QUOTES) !!})">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">
                {{ $admissions->links() }}
            </div>
        @else
            <div class="p-14 text-center">
                <div class="mx-auto w-16 h-16 bg-slate-50 text-slate-200 rounded-2xl flex items-center justify-center mb-4">
                    <i data-lucide="clipboard-list" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-600">No admissions found</h3>
                <p class="text-slate-400 text-sm mt-1.5">
                    @if($search || $status || $clinicFilter || $dateFrom || $dateTo)
                        No results match your filters. <a href="{{ route('clinical.psr.admissions.index') }}" class="text-blue-600 font-bold hover:underline">Clear filters</a>
                    @else
                        Start by creating a new PSR admission.
                    @endif
                </p>
            </div>
        @endif
    </div>
</div>

@can('clinical.psr.admissions.delete')
    @foreach($admissions as $adm)
        <form id="delete-form-{{ $adm->id }}" action="{{ route('clinical.psr.admissions.destroy', $adm) }}" method="POST" style="display:none;">
            @csrf @method('DELETE')
        </form>
    @endforeach
@endcan

<script>
document.addEventListener('DOMContentLoaded', () => { if (typeof lucide !== 'undefined') lucide.createIcons(); });

function confirmDelete(admissionId, patientName) {
    Swal.fire({
        icon: 'warning',
        title: '<span style="font-size:1rem;font-weight:900;text-transform:uppercase">Delete PSR admission</span>',
        html: '<div style="text-align:left;padding:.5rem 0">'
            + '<div style="background:#fef2f2;border:1.5px solid #fca5a5;border-radius:12px;padding:1.15rem;margin-bottom:1rem">'
            + '<p style="font-size:.85rem;color:#991b1b;font-weight:700;margin:0">'
            + 'You are about to <strong>permanently delete</strong> the PSR admission for:</p>'
            + '<p style="font-size:1.05rem;color:#dc2626;font-weight:900;margin:.65rem 0 0;text-transform:uppercase">' + patientName + '</p>'
            + '</div>'
            + '<p style="font-size:.8rem;color:#64748b;line-height:1.6;margin:0">'
            + 'This will delete <strong>all related clinical forms</strong> (intake, bio-psychosocial, treatment plan, goals, FARS, authorizations, progress notes, service log entries). '
            + 'The patient record itself will <strong>not</strong> be deleted.'
            + '</p>'
            + '</div>',
        showCancelButton: true,
        confirmButtonText: 'Delete permanently',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        width: 480,
        reverseButtons: true,
        customClass: { popup: 'rounded-2xl' }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-form-' + admissionId).submit();
        }
    });
}
</script>

@endsection
