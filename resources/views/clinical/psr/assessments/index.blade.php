@extends('layouts.app')
@section('title', 'PSR — Assessments')

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
    .stat-card.accent-blue    { --accent: #60a5fa; }
    .stat-card.accent-emerald { --accent: #34d399; }

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
    .data-table tbody tr:hover td { background-color: #f0fdfa; }

    .form-indicator {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .25rem .55rem; border-radius: .4rem;
        font-size: .58rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
        white-space: nowrap;
    }
    .form-indicator .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .form-indicator.signed  { background: #d1fae5; color: #047857; }
    .form-indicator.signed  .dot { background: #10b981; }
    .form-indicator.draft   { background: #fef3c7; color: #b45309; }
    .form-indicator.draft   .dot { background: #f59e0b; }
    .form-indicator.pending { background: #f1f5f9; color: #64748b; }
    .form-indicator.pending .dot { background: #cbd5e1; }

    .action-btn {
        width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; border: 1px solid #e2e8f0; background: #fff;
        color: #94a3b8; transition: all .2s ease; cursor: pointer; text-decoration: none;
    }
    .action-btn.bio:hover     { background: #f0fdfa; color: #0d9488; border-color: #99f6e4; }
    .action-btn.fars:hover    { background: #f0f9ff; color: #0284c7; border-color: #bae6fd; }
    .action-btn.admission:hover { background: #faf5ff; color: #7c3aed; border-color: #ddd6fe; }

    .filter-input {
        border: 1px solid #e2e8f0; border-radius: .6rem; padding: .45rem .7rem;
        font-size: .8rem; font-weight: 500; outline: none; transition: all .2s; background: #fff;
    }
    .filter-input:focus { border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,.08); }

    .header-card {
        background:
            radial-gradient(circle at 100% 0, rgba(20,184,166,.08), transparent 50%),
            radial-gradient(circle at 0 100%, rgba(56,189,248,.06), transparent 50%),
            #fff;
        border: 1px solid #e2e8f0;
    }

    .pat-avatar {
        width: 36px; height: 36px; border-radius: .65rem;
        display: flex; align-items: center; justify-content: center;
        font-size: .72rem; font-weight: 900; flex-shrink: 0;
        background: linear-gradient(135deg, #0d9488, #14b8a6); color: #fff;
        box-shadow: 0 2px 6px -1px rgba(13,148,136,.4);
    }

    /* Mini compliance grid (intake / bio / mtp / fars) */
    .mini-compl { display: inline-flex; gap: .25rem; }
    .mini-chip {
        width: 22px; height: 22px; border-radius: 6px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .58rem; font-weight: 900;
        cursor: help; transition: all .15s;
    }
    .mini-chip:hover { transform: scale(1.12); }
    .mini-chip.signed  { background: #d1fae5; color: #047857; box-shadow: inset 0 0 0 1px #6ee7b7; }
    .mini-chip.draft   { background: #fef3c7; color: #b45309; box-shadow: inset 0 0 0 1px #fcd34d; }
    .mini-chip.pending { background: #f1f5f9; color: #94a3b8; box-shadow: inset 0 0 0 1px #cbd5e1; }
</style>

@php
    // Helper: state of a form (signed / draft / pending)
    $stateOf = fn ($obj, $signedField = 'is_signed')
        => ! $obj ? 'pending' : (data_get($obj, $signedField) ? 'signed' : 'draft');
@endphp

<div class="max-w-7xl mx-auto">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-7 gap-4 header-card p-5 rounded-2xl shadow-sm">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('clinical.psr.dashboard') }}"
               class="w-9 h-9 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 hover:text-teal-600 hover:bg-teal-50 transition-colors border border-slate-200 flex-shrink-0">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div class="p-2.5 bg-gradient-to-br from-teal-500 to-emerald-600 text-white rounded-xl shadow-md shadow-teal-500/30">
                <i data-lucide="brain" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-xl font-black text-slate-800 tracking-tight uppercase">Bio-Psychosocial Assessments</h1>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">FL Admin Code 65E-4 compliant evaluations · clinical chart overview</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
        <div class="stat-card accent-slate">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-slate-50 to-slate-100 text-slate-500 flex items-center justify-center"><i data-lucide="users" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800 leading-tight">{{ $stats['totalActive'] }}</div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Active admissions</div>
            </div>
        </div>
        <div class="stat-card accent-amber">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-50 to-orange-50 text-amber-600 flex items-center justify-center"><i data-lucide="clock" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-amber-600 leading-tight">{{ $stats['bioPending'] }}</div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Bio pending</div>
            </div>
        </div>
        <div class="stat-card accent-blue">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 text-blue-600 flex items-center justify-center"><i data-lucide="file-edit" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-blue-600 leading-tight">{{ $stats['bioDrafts'] }}</div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Bio drafts</div>
            </div>
        </div>
        <div class="stat-card accent-emerald">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-50 to-green-50 text-emerald-600 flex items-center justify-center"><i data-lucide="circle-check" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-emerald-600 leading-tight">{{ $stats['bioSigned'] }}</div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Bio signed</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-7">
        @php
            $mini = [
                ['label' => 'Intakes signed', 'value' => $stats['intakeSigned'], 'icon' => 'clipboard-list', 'color' => 'violet'],
                ['label' => 'MTPs signed',    'value' => $stats['mtpSigned'],    'icon' => 'target',         'color' => 'cyan'],
                ['label' => 'FARS signed',    'value' => $stats['farsSigned'],   'icon' => 'bar-chart-3',    'color' => 'rose'],
            ];
        @endphp
        @foreach($mini as $m)
            <div class="bg-white border border-slate-200 rounded-xl p-3 flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-{{ $m['color'] }}-50 text-{{ $m['color'] }}-500 flex items-center justify-center border border-{{ $m['color'] }}-100"><i data-lucide="{{ $m['icon'] }}" class="w-4 h-4"></i></div>
                <div>
                    <div class="text-base font-black text-slate-700">{{ $m['value'] }}<span class="text-slate-300 font-bold">/{{ $stats['totalActive'] }}</span></div>
                    <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ $m['label'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-3.5 mb-5">
        <form method="GET" class="flex flex-wrap items-center gap-2.5">
            <div class="relative flex-1 min-w-[180px] max-w-[280px]">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-300"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search patient name or MRN…"
                       class="w-full pl-8 pr-3 filter-input">
            </div>
            <select name="clinic_id" class="filter-input min-w-[140px]">
                <option value="">All clinics</option>
                @foreach($filterClinics as $fc)
                    <option value="{{ $fc->id }}" {{ (string) $clinicFilter === (string) $fc->id ? 'selected' : '' }}>{{ $fc->name }}</option>
                @endforeach
            </select>
            <select name="bio_status" class="filter-input min-w-[140px]">
                <option value="">Bio: any</option>
                <option value="pending" @selected($bioFilter === 'pending')>Bio pending</option>
                <option value="draft"   @selected($bioFilter === 'draft')>Bio draft</option>
                <option value="signed"  @selected($bioFilter === 'signed')>Bio signed</option>
            </select>
            <select name="fars_status" class="filter-input min-w-[140px]">
                <option value="">FARS: any</option>
                <option value="pending" @selected($farsFilter === 'pending')>FARS pending</option>
                <option value="draft"   @selected($farsFilter === 'draft')>FARS draft</option>
                <option value="signed"  @selected($farsFilter === 'signed')>FARS signed</option>
            </select>
            <button type="submit" class="bg-gradient-to-br from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white px-3.5 py-2 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-colors flex items-center gap-1.5 shadow-sm shadow-teal-500/20">
                <i data-lucide="filter" class="w-3 h-3"></i> Filter
            </button>
            @if($search || $clinicFilter || $bioFilter || $farsFilter)
                <a href="{{ route('clinical.psr.assessments.index') }}"
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
                            <th>Clinic</th>
                            <th>Therapist</th>
                            <th>Admitted</th>
                            <th>Compliance</th>
                            <th>Bio status</th>
                            <th>FARS status</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($admissions as $adm)
                            @php
                                $bio       = $adm->bioAssessment;
                                $latestF   = $adm->farsAssessments->first();
                                $latestMtp = $adm->treatmentPlans->first();
                                $intake    = $adm->intake;

                                $bioState  = $stateOf($bio);
                                $farsState = $stateOf($latestF);
                                $mtpState  = $stateOf($latestMtp);
                                $intkState = $stateOf($intake);

                                $initials = strtoupper(
                                    mb_substr($adm->patient?->first_name ?? '?', 0, 1) .
                                    mb_substr($adm->patient?->last_name  ?? '?', 0, 1)
                                );

                                $tip = function ($state, $name) {
                                    return match ($state) {
                                        'signed'  => "$name — signed",
                                        'draft'   => "$name — draft, not signed",
                                        default   => "$name — not started",
                                    };
                                };
                            @endphp
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="pat-avatar">{{ $initials }}</div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-800 text-sm truncate">{{ $adm->patient?->full_name ?? '—' }}</div>
                                            <div class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $adm->patient?->mrn ?: '—' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-xs font-bold">{{ $adm->clinic?->name ?? '—' }}</td>
                                <td class="text-xs text-slate-500">{{ $adm->assignedTherapist?->full_name ?? '—' }}</td>
                                <td class="text-xs text-slate-500">{{ optional($adm->admission_date)->format('m/d/Y') ?: '—' }}</td>
                                <td>
                                    <div class="mini-compl">
                                        <span class="mini-chip {{ $intkState }}" title="{{ $tip($intkState, 'Intake') }}">I</span>
                                        <span class="mini-chip {{ $bioState }}"  title="{{ $tip($bioState,  'Bio') }}">B</span>
                                        <span class="mini-chip {{ $mtpState }}"  title="{{ $tip($mtpState,  'MTP') }}">M</span>
                                        <span class="mini-chip {{ $farsState }}" title="{{ $tip($farsState, 'FARS') }}">F</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="form-indicator {{ $bioState }}">
                                        <span class="dot"></span>
                                        {{ $bioState === 'signed' ? 'Signed' : ($bioState === 'draft' ? 'Draft' : 'Pending') }}
                                    </span>
                                    @if($bio?->signed_at)
                                        <div class="text-[10px] text-slate-400 mt-1">{{ $bio->signed_at->format('m/d/Y') }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="form-indicator {{ $farsState }}">
                                        <span class="dot"></span>
                                        {{ $farsState === 'signed' ? 'Signed' : ($farsState === 'draft' ? 'Draft' : 'Pending') }}
                                    </span>
                                    @if($adm->farsAssessments->count() > 1)
                                        <div class="text-[10px] text-slate-400 mt-1">{{ $adm->farsAssessments->count() }} on file</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ $bio ? route('clinical.psr.assessments.edit', $bio) : route('clinical.psr.assessments.create', ['admission_id' => $adm->id]) }}"
                                           class="action-btn bio" title="{{ $bio ? 'Edit bio' : 'Start bio' }}">
                                            <i data-lucide="brain" class="w-3.5 h-3.5"></i>
                                        </a>
                                        <a href="{{ $latestF ? route('clinical.psr.assessments.fars.edit', $latestF) : route('clinical.psr.assessments.fars.create', $adm) }}"
                                           class="action-btn fars" title="{{ $latestF ? 'Edit FARS' : 'New FARS' }}">
                                            <i data-lucide="gauge" class="w-3.5 h-3.5"></i>
                                        </a>
                                        <a href="{{ route('clinical.psr.admissions.show', $adm) }}" class="action-btn admission" title="Open admission chart">
                                            <i data-lucide="folder-open" class="w-3.5 h-3.5"></i>
                                        </a>
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
                    <i data-lucide="brain" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-600">No active admissions found</h3>
                <p class="text-slate-400 text-sm mt-1.5">
                    @if($search || $clinicFilter || $bioFilter || $farsFilter)
                        No results match your filters. <a href="{{ route('clinical.psr.assessments.index') }}" class="text-teal-600 font-bold hover:underline">Clear filters</a>
                    @else
                        Bio-psychosocial assessments are created from each patient's admission page.
                    @endif
                </p>
            </div>
        @endif
    </div>

    <div class="mt-4 bg-white border border-slate-200 rounded-xl p-3 flex flex-wrap items-center justify-between gap-2 text-[10px]">
        <div class="flex items-center gap-3 flex-wrap">
            <span class="font-bold text-slate-500 uppercase tracking-widest text-[9px]">Compliance chips</span>
            <span class="flex items-center gap-1.5"><span class="mini-chip signed" style="cursor:default">✓</span><span class="text-slate-500">complete &amp; signed</span></span>
            <span class="flex items-center gap-1.5"><span class="mini-chip draft" style="cursor:default">~</span><span class="text-slate-500">draft, unsigned</span></span>
            <span class="flex items-center gap-1.5"><span class="mini-chip pending" style="cursor:default">!</span><span class="text-slate-500">not started</span></span>
        </div>
        <div class="text-slate-400">I = Intake · B = Bio · M = MTP · F = FARS</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
</script>
@endsection
