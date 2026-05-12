@extends('layouts.app')
@section('title', 'TCM — Weekly superbill')

@section('content')
<style>
    .sb-stat { background:#fff; border:1px solid #e2e8f0; border-radius:.85rem; padding:.85rem 1rem; box-shadow:0 1px 3px rgba(0,0,0,.02); }
    .sb-stat-label { font-size:.6rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; }
    .sb-stat-value { font-size:1.45rem; font-weight:800; color:#1e293b; line-height:1.1; margin-top:.15rem; font-family:'JetBrains Mono', ui-monospace, monospace; }

    .sb-grid-wrap { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.02); }
    .sb-grid { width:100%; border-collapse:separate; border-spacing:0; font-size:.78rem; }
    .sb-grid thead th {
        background:#f8fafc; color:#64748b; font-size:.6rem; font-weight:800;
        text-transform:uppercase; letter-spacing:.05em; padding:.55rem .5rem;
        border-bottom:1px solid #e2e8f0; text-align:center; white-space:nowrap;
        position:sticky; top:0; z-index:5;
    }
    .sb-grid thead th.day { background:linear-gradient(180deg,#fafafa,#f1f5f9); }
    .sb-grid thead th.today { background:linear-gradient(180deg,#fff7ed,#fed7aa); color:#9a3412; }
    .sb-grid tbody td { padding:.5rem .5rem; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
    .sb-grid tbody tr:hover td { background:#fffaf5; }
    .sb-grid .col-name { position:sticky; left:0; background:#fff; z-index:4; border-right:2px solid #e2e8f0; min-width:240px; max-width:240px; padding:.6rem .85rem; }
    .sb-grid .col-meta { position:sticky; left:240px; background:#fff; z-index:4; border-right:2px solid #e2e8f0; min-width:170px; max-width:170px; padding:.6rem .85rem; }
    .sb-grid .day-cell { width:11%; min-width:110px; text-align:center; }

    .att-pill { display:inline-flex; align-items:center; gap:.3rem; padding:.2rem .55rem; border-radius:999px; font-size:.65rem; font-weight:800; letter-spacing:.04em; text-transform:uppercase; }
    .att-yes { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
    .att-no  { background:#f8fafc; color:#cbd5e1; border:1px dashed #e2e8f0; }
    .day-cell .units { font-family:'JetBrains Mono', ui-monospace, monospace; font-size:.7rem; color:#475569; margin-top:.2rem; }
    .day-cell .code  { font-family:'JetBrains Mono', ui-monospace, monospace; font-size:.6rem; color:#94a3b8; }
    .day-cell .note-warn { color:#f59e0b; font-size:.6rem; font-weight:700; margin-top:.15rem; }

    .row-total { font-family:'JetBrains Mono', ui-monospace, monospace; font-weight:800; font-size:.85rem; background:#f8fafc; border-left:1px solid #e2e8f0; text-align:center; }
    .col-total { background:linear-gradient(180deg,#f8fafc,#fff7ed); font-weight:800; text-align:center; font-family:'JetBrains Mono', ui-monospace, monospace; color:#c2410c; border-top:2px solid #fed7aa; }

    .pat-name { font-weight:700; font-size:.82rem; color:#1e293b; line-height:1.15; }
    .pat-meta { font-size:.65rem; color:#64748b; margin-top:.1rem; }
    .pat-mrn  { font-family:'JetBrains Mono', ui-monospace, monospace; font-size:.6rem; color:#3b82f6; background:#eff6ff; padding:.05rem .35rem; border-radius:.25rem; border:1px solid #bfdbfe; }

    .week-nav-btn { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:.55rem; background:#fff; border:1px solid #e2e8f0; color:#475569; transition:all .15s; }
    .week-nav-btn:hover { background:#f1f5f9; color:#1e293b; }

    .lock-banner { background:linear-gradient(135deg,#fef3c7,#fde68a); border-left:4px solid #f59e0b; padding:.75rem 1.1rem; border-radius:.75rem; display:flex; align-items:center; gap:.75rem; }
    .lock-banner.unlocked { background:linear-gradient(135deg,#ecfdf5,#d1fae5); border-color:#10b981; }
</style>

<div class="max-w-[1400px] mx-auto">
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-gradient-to-br from-orange-500 to-orange-700 text-white rounded-xl shadow-md shadow-orange-500/25">
                    <i data-lucide="table-2" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest text-orange-500">TCM · Superbill</div>
                    <h1 class="text-xl font-black text-slate-800">Week of {{ $monday->format('M j') }} – {{ $saturday->format('M j, Y') }}</h1>
                    <div class="text-[11px] text-slate-400 font-semibold">ISO week {{ $monday->isoWeek() }} · {{ $monday->year }}</div>
                </div>
            </div>

            <form method="GET" class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('clinical.tcm.superbill.index', array_merge(request()->except('week'), ['week' => $monday->copy()->subWeek()->toDateString()])) }}" class="week-nav-btn"><i data-lucide="chevron-left" class="w-4 h-4"></i></a>
                <input type="date" name="week" value="{{ $monday->toDateString() }}" onchange="this.form.submit()" class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm font-semibold text-slate-700">
                <a href="{{ route('clinical.tcm.superbill.index', array_merge(request()->except('week'), ['week' => $monday->copy()->addWeek()->toDateString()])) }}" class="week-nav-btn"><i data-lucide="chevron-right" class="w-4 h-4"></i></a>
                <a href="{{ route('clinical.tcm.superbill.index', request()->except('week')) }}" class="px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-600 text-xs font-bold uppercase tracking-wider rounded-lg border border-slate-200">Today</a>
                @if($caseManagerId)<input type="hidden" name="case_manager_id" value="{{ $caseManagerId }}">@endif
                @if($statusOnly !== 'admitted')<input type="hidden" name="status" value="{{ $statusOnly }}">@endif
            </form>
        </div>
    </div>

    <form method="GET" class="bg-white border border-slate-200 rounded-2xl p-4 mb-4 flex flex-wrap items-end gap-3 shadow-sm">
        <input type="hidden" name="week" value="{{ $monday->toDateString() }}">
        <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Case manager</label>
            <select name="case_manager_id" class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm min-w-[180px]">
                <option value="">All case managers</option>
                @foreach($caseManagers as $cm)<option value="{{ $cm->id }}" @selected((string) $caseManagerId === (string) $cm->id)>{{ $cm->full_name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Status</label>
            <select name="status" class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm">
                @foreach(['admitted' => 'Admitted', 'on_hold' => 'On hold', 'discharged' => 'Discharged', 'all' => 'All'] as $k => $v)
                    <option value="{{ $k }}" @selected($statusOnly === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <button class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5">
            <i data-lucide="filter" class="w-3.5 h-3.5"></i> Apply
        </button>
    </form>

    <div class="lock-banner mb-4 {{ $lock ? '' : 'unlocked' }}">
        @if($lock)
            <i data-lucide="lock" class="w-5 h-5 text-amber-700 flex-shrink-0"></i>
            <div class="flex-1 text-[12px]">
                <strong class="font-black text-amber-900 uppercase tracking-wider text-[10px]">Week locked</strong>
                <div class="text-amber-800 mt-0.5">Locked at {{ $lock->locked_at?->format('M j, g:i A') }}@if($lock->supervisor_name) by <strong>{{ $lock->supervisor_name }}</strong>@endif</div>
            </div>
            @can('clinical.tcm.superbill.lock')
                <form method="POST" action="{{ route('clinical.tcm.superbill.unlock', $lock) }}">@csrf @method('DELETE')
                    <button class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-[11px] font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5"><i data-lucide="unlock" class="w-3.5 h-3.5"></i> Unlock</button>
                </form>
            @endcan
        @else
            <i data-lucide="unlock" class="w-5 h-5 text-emerald-700 flex-shrink-0"></i>
            <div class="flex-1 text-[12px]">
                <strong class="font-black text-emerald-900 uppercase tracking-wider text-[10px]">Open for editing</strong>
                <div class="text-emerald-800 mt-0.5">No supervisor sign-off yet for this week.</div>
            </div>
            @can('clinical.tcm.superbill.lock')
                <form method="POST" action="{{ route('clinical.tcm.superbill.lock') }}" class="flex items-end gap-2 flex-wrap">@csrf
                    <input type="hidden" name="week_start_date" value="{{ $monday->toDateString() }}">
                    <input type="text" name="supervisor_name" placeholder="Supervisor name" required class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm min-w-[180px]">
                    <input type="text" name="notes" placeholder="Notes (optional)" class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm min-w-[180px]">
                    <button class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-[11px] font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5"><i data-lucide="lock" class="w-3.5 h-3.5"></i> Lock week</button>
                </form>
            @endcan
        @endif
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2 mb-4">
        <div class="sb-stat"><div class="sb-stat-label">Patients</div><div class="sb-stat-value">{{ $stats['admissions'] }}</div></div>
        <div class="sb-stat"><div class="sb-stat-label">Encounters</div><div class="sb-stat-value">{{ $stats['rows'] }}</div></div>
        <div class="sb-stat"><div class="sb-stat-label">Total units</div><div class="sb-stat-value text-orange-600">{{ $stats['units'] }}</div></div>
        <div class="sb-stat"><div class="sb-stat-label">Unbilled</div><div class="sb-stat-value text-amber-600">{{ $stats['unbilled'] }}</div></div>
        <div class="sb-stat"><div class="sb-stat-label">Submitted</div><div class="sb-stat-value text-blue-600">{{ $stats['submitted'] }}</div></div>
        <div class="sb-stat"><div class="sb-stat-label">Paid total</div><div class="sb-stat-value text-emerald-600">${{ number_format($stats['paid_total'], 0) }}</div></div>
        <div class="sb-stat"><div class="sb-stat-label">Missing notes</div><div class="sb-stat-value {{ $stats['missing_note'] > 0 ? 'text-rose-600' : 'text-slate-300' }}">{{ $stats['missing_note'] }}</div></div>
    </div>

    <div class="sb-grid-wrap overflow-auto">
        <table class="sb-grid">
            <thead>
                <tr>
                    <th class="col-name text-left">Patient</th>
                    <th class="col-meta text-left">Case manager</th>
                    @foreach($weekDates as $wd)
                        <th class="day-cell day {{ $wd['today'] ? 'today' : '' }}">{{ $wd['label'] }}<br><span class="font-mono text-[10px] text-slate-400 font-bold">{{ $wd['short'] }}</span></th>
                    @endforeach
                    <th class="day-cell">Units</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admissions as $admission)
                    @php
                        $rowLogs = collect($grid[$admission->id] ?? []);
                        $rowUnits = $rowLogs->sum('units');
                    @endphp
                    <tr>
                        <td class="col-name">
                            <a href="{{ route('clinical.tcm.admissions.show', $admission) }}" class="block hover:bg-orange-50 -m-1 p-1 rounded">
                                <div class="pat-name">{{ $admission->patient?->full_name ?? '—' }}</div>
                                <div class="pat-meta flex items-center gap-1.5"><span class="pat-mrn">{{ $admission->patient?->mrn ?? '---' }}</span></div>
                            </a>
                        </td>
                        <td class="col-meta">
                            <div class="text-[11px] font-bold text-slate-700 truncate">{{ $admission->caseManager?->full_name ?? '—' }}</div>
                        </td>
                        @foreach($weekDates as $wd)
                            @php $log = $grid[$admission->id][$wd['date']] ?? null; @endphp
                            <td class="day-cell {{ $wd['today'] ? 'bg-orange-50/40' : '' }}">
                                @if($log)
                                    <a href="{{ route('clinical.tcm.service_log.show', $log) }}" class="inline-block">
                                        <span class="att-pill att-yes"><i data-lucide="check" class="w-3 h-3"></i> {{ $log->units }}u</span>
                                        <div class="code">{{ $log->cpt_code }}{{ $log->modifier ? ' '.$log->modifier : '' }}</div>
                                        @if(! $log->has_contact_note)
                                            <div class="note-warn flex items-center justify-center gap-0.5"><i data-lucide="alert-triangle" class="w-2.5 h-2.5"></i> note</div>
                                        @endif
                                    </a>
                                @else
                                    <span class="att-pill att-no">—</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="row-total {{ $rowUnits > 0 ? 'text-orange-600' : 'text-slate-300' }}">{{ $rowUnits ?: '0' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($weekDates) + 3 }}" class="text-center py-12 text-slate-400 text-sm">No admissions match the current filters.</td></tr>
                @endforelse
            </tbody>
            @if($admissions->count() > 0)
                <tfoot>
                    <tr>
                        <td colspan="2" class="col-total text-right pr-3">Daily totals</td>
                        @foreach($weekDates as $wd)
                            <td class="col-total">{{ $dayTotals[$wd['date']] ?? 0 }}</td>
                        @endforeach
                        <td class="col-total">{{ $stats['units'] }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
