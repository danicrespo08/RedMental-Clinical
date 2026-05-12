@extends('layouts.app')
@section('title', 'PSR — Group Sessions')

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
    .stat-card.accent-indigo  { --accent: #6366f1; }
    .stat-card.accent-emerald { --accent: #34d399; }
    .stat-card.accent-amber   { --accent: #fbbf24; }
    .stat-card.accent-violet  { --accent: #a78bfa; }

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

    .action-btn {
        width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; border: 1px solid #e2e8f0; background: #fff;
        color: #94a3b8; transition: all .2s ease; cursor: pointer; text-decoration: none;
    }
    .action-btn.view:hover   { background: #eef2ff; color: #4f46e5; border-color: #c7d2fe; }
    .action-btn.edit:hover   { background: #fef3c7; color: #d97706; border-color: #fcd34d; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .status-badge {
        display: inline-flex; align-items: center; gap: .25rem; padding: .25rem .6rem;
        border-radius: .4rem; font-size: .55rem; font-weight: 800; text-transform: uppercase;
        letter-spacing: .04em; border: 1px solid transparent; white-space: nowrap;
    }
    .att-pill {
        display: inline-flex; align-items: center; gap: .2rem; padding: .15rem .45rem;
        border-radius: .3rem; font-size: .55rem; font-weight: 700; line-height: 1;
    }
    .filter-input {
        border: 1px solid #e2e8f0; border-radius: .6rem; padding: .45rem .7rem;
        font-size: .8rem; font-weight: 500; outline: none; transition: all .2s;
        background: #fff;
    }
    .filter-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.08); }

    .header-card {
        background:
            radial-gradient(circle at 100% 0, rgba(99,102,241,.08), transparent 50%),
            radial-gradient(circle at 0 100%, rgba(56,189,248,.06), transparent 50%),
            #fff;
        border: 1px solid #e2e8f0;
    }

    /* Capacity bar inside the attendance pill cell */
    .cap-bar { height: 4px; border-radius: 99px; background: #f1f5f9; overflow: hidden; min-width: 70px; }
    .cap-bar > div { height: 100%; border-radius: 99px; transition: width .35s ease; }
</style>

@php
    $statusColors = [
        'scheduled'   => 'bg-blue-50 text-blue-700 border-blue-200',
        'in_progress' => 'bg-amber-50 text-amber-700 border-amber-200',
        'completed'   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'cancelled'   => 'bg-slate-50 text-slate-500 border-slate-200',
    ];
@endphp

<div class="max-w-7xl mx-auto">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-7 gap-4 header-card p-5 rounded-2xl shadow-sm">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('clinical.psr.dashboard') }}"
               class="w-9 h-9 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors border border-slate-200 flex-shrink-0">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div class="p-2.5 bg-gradient-to-br from-indigo-500 to-violet-600 text-white rounded-xl shadow-md shadow-indigo-500/30">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-xl font-black text-slate-800 tracking-tight uppercase">Group Sessions</h1>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Daily roster &amp; attendance tracking</p>
            </div>
        </div>
        @can('clinical.psr.group_sessions.create')
            <a href="{{ route('clinical.psr.group_sessions.create') }}"
               class="bg-gradient-to-br from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider shadow-lg shadow-indigo-500/30 flex items-center gap-2 transition-all hover:-translate-y-0.5">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> New session
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
        <div class="stat-card accent-indigo">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-50 to-violet-50 text-indigo-600 flex items-center justify-center"><i data-lucide="calendar-clock" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800 leading-tight">{{ $stats['today'] }}</div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Today's sessions</div>
            </div>
        </div>
        <div class="stat-card accent-emerald">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-50 to-green-50 text-emerald-600 flex items-center justify-center"><i data-lucide="check-circle" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-emerald-600 leading-tight">{{ $stats['completed_today'] }}</div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Completed today</div>
            </div>
        </div>
        <div class="stat-card accent-amber">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-50 to-orange-50 text-amber-600 flex items-center justify-center"><i data-lucide="calendar-days" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800 leading-tight">{{ $stats['week'] }}</div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">This week</div>
            </div>
        </div>
        <div class="stat-card accent-violet">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-violet-50 to-fuchsia-50 text-violet-600 flex items-center justify-center"><i data-lucide="users-round" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800 leading-tight">{{ $stats['patients_this_week'] }}</div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Patients this week</div>
            </div>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-3.5 mb-5">
        <form method="GET" class="flex flex-wrap items-center gap-2.5">
            <div class="relative flex-1 min-w-[180px] max-w-[280px]">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-300"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search title, code, clinic…"
                       class="w-full pl-8 pr-3 filter-input">
            </div>
            <select name="status" class="filter-input min-w-[130px]">
                <option value="">All statuses</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="clinic_id" class="filter-input min-w-[140px]">
                <option value="">All clinics</option>
                @foreach($filterClinics as $fc)
                    <option value="{{ $fc->id }}" {{ (string) $clinicFilter === (string) $fc->id ? 'selected' : '' }}>{{ $fc->name }}</option>
                @endforeach
            </select>
            <select name="therapist_id" class="filter-input min-w-[150px]">
                <option value="">All therapists</option>
                @foreach($filterTherapists as $ft)
                    <option value="{{ $ft->id }}" {{ (string) $therapistId === (string) $ft->id ? 'selected' : '' }}>{{ $ft->full_name }}</option>
                @endforeach
            </select>
            <div class="flex items-center gap-1.5">
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="filter-input w-[130px]" title="From">
                <span class="text-slate-300 text-[10px] font-bold">to</span>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="filter-input w-[130px]" title="To">
            </div>
            <button type="submit" class="bg-gradient-to-br from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white px-3.5 py-2 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-colors flex items-center gap-1.5 shadow-sm shadow-indigo-500/20">
                <i data-lucide="filter" class="w-3 h-3"></i> Filter
            </button>
            @if($search || $status || $clinicFilter || $therapistId || $dateFrom || $dateTo)
                <a href="{{ route('clinical.psr.group_sessions.index') }}"
                   class="bg-slate-100 hover:bg-slate-200 text-slate-500 px-3.5 py-2 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-colors flex items-center gap-1.5">
                    <i data-lucide="x" class="w-3 h-3"></i> Clear
                </a>
            @endif
        </form>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        @if($sessions->count() > 0)
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Title / Type</th>
                            <th>Clinic</th>
                            <th>Lead therapist</th>
                            <th>Attendance</th>
                            <th>Status</th>
                            <th>Signed</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sessions as $gs)
                            @php
                                $totalAtt   = $gs->attendees_count;
                                $presentAtt = $gs->attendees->where('attendance_status', 'present')->count();
                                $absentAtt  = $gs->attendees->where('attendance_status', 'absent')->count();
                                $lateAtt    = $gs->attendees->where('attendance_status', 'late')->count();
                                $capPct     = $gs->max_capacity > 0 ? round(($totalAtt / $gs->max_capacity) * 100) : 0;
                                $capColor   = $capPct >= 90 ? 'bg-rose-500' : ($capPct >= 70 ? 'bg-amber-500' : 'bg-emerald-500');
                            @endphp
                            <tr>
                                <td>
                                    <div class="font-bold text-slate-800 text-sm">{{ $gs->session_date->format('m/d/Y') }}</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5 font-semibold">{{ $gs->session_date->format('l') }}</div>
                                </td>
                                <td class="text-xs font-mono text-slate-500">
                                    {{ \Carbon\Carbon::parse($gs->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($gs->end_time)->format('g:i A') }}
                                    @if($gs->break_minutes)<div class="text-[9px] text-slate-400 mt-0.5">Break {{ $gs->break_minutes }}m</div>@endif
                                </td>
                                <td>
                                    <div class="font-bold text-slate-800 text-sm">{{ $gs->title }}</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5 font-semibold">
                                        {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $gs->session_type)) }}
                                        · <span class="font-mono">{{ $gs->service_code }}</span>
                                        @if($gs->modifier) <span class="font-mono text-slate-300">{{ $gs->modifier }}</span> @endif
                                    </div>
                                </td>
                                <td class="text-xs font-bold">{{ $gs->clinic?->name ?? '—' }}</td>
                                <td>
                                    <div class="text-xs text-slate-700 font-semibold">{{ $gs->leadTherapist?->full_name ?? '—' }}</div>
                                    @if($gs->coTherapist)<div class="text-[10px] text-slate-400">+ {{ $gs->coTherapist->full_name }}</div>@endif
                                </td>
                                <td>
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <span class="att-pill bg-emerald-50 text-emerald-700">{{ $presentAtt }}<span class="text-[8px] opacity-70">P</span></span>
                                        @if($lateAtt > 0)<span class="att-pill bg-orange-50 text-orange-700">{{ $lateAtt }}<span class="text-[8px] opacity-70">L</span></span>@endif
                                        @if($absentAtt > 0)<span class="att-pill bg-rose-50 text-rose-700">{{ $absentAtt }}<span class="text-[8px] opacity-70">A</span></span>@endif
                                        <span class="text-[10px] text-slate-400 font-bold">/ {{ $gs->max_capacity }}</span>
                                    </div>
                                    <div class="cap-bar"><div class="{{ $capColor }}" style="width: {{ min(100, $capPct) }}%"></div></div>
                                </td>
                                <td>
                                    <span class="status-badge {{ $statusColors[$gs->status] ?? 'bg-slate-50 text-slate-600 border-slate-200' }}">
                                        {{ $gs->status_label }}
                                    </span>
                                </td>
                                <td>
                                    @if($gs->is_signed)
                                        <span class="text-emerald-500" title="Signed {{ optional($gs->signed_at)->format('M j') }}"><i data-lucide="check-circle" class="w-4 h-4 inline"></i></span>
                                    @else
                                        <span class="text-slate-200" title="Not yet signed"><i data-lucide="minus-circle" class="w-4 h-4 inline"></i></span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('clinical.psr.group_sessions.show', $gs) }}" class="action-btn view" title="View">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        </a>
                                        @can('clinical.psr.group_sessions.edit')
                                            @if(! $gs->is_signed)
                                                <a href="{{ route('clinical.psr.group_sessions.edit', $gs) }}" class="action-btn edit" title="Edit">
                                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                                </a>
                                            @endif
                                        @endcan
                                        @can('clinical.psr.group_sessions.delete')
                                            @if(! $gs->is_signed)
                                                <button type="button" class="action-btn delete" title="Delete"
                                                        onclick="confirmDeleteGs({{ $gs->id }}, {!! htmlspecialchars(json_encode($gs->title), ENT_QUOTES) !!})">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                </button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">
                {{ $sessions->links() }}
            </div>
        @else
            <div class="p-14 text-center">
                <div class="mx-auto w-16 h-16 bg-slate-50 text-slate-200 rounded-2xl flex items-center justify-center mb-4">
                    <i data-lucide="users" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-600">No group sessions found</h3>
                <p class="text-slate-400 text-sm mt-1.5">
                    @if($search || $status || $clinicFilter || $therapistId || $dateFrom || $dateTo)
                        No results match your filters. <a href="{{ route('clinical.psr.group_sessions.index') }}" class="text-indigo-600 font-bold hover:underline">Clear filters</a>
                    @else
                        Start by creating a new group session.
                    @endif
                </p>
            </div>
        @endif
    </div>
</div>

@can('clinical.psr.group_sessions.delete')
    @foreach($sessions as $gs)
        @if(! $gs->is_signed)
            <form id="delete-gs-{{ $gs->id }}" action="{{ route('clinical.psr.group_sessions.destroy', $gs) }}" method="POST" style="display:none;">
                @csrf @method('DELETE')
            </form>
        @endif
    @endforeach
@endcan

<script>
document.addEventListener('DOMContentLoaded', () => { if (typeof lucide !== 'undefined') lucide.createIcons(); });

function confirmDeleteGs(id, title) {
    Swal.fire({
        icon: 'warning',
        title: '<span style="font-size:1rem;font-weight:900;text-transform:uppercase">Delete group session</span>',
        html: '<div style="text-align:left;padding:.5rem 0">'
            + '<div style="background:#fef2f2;border:1.5px solid #fca5a5;border-radius:12px;padding:1.15rem;margin-bottom:1rem">'
            + '<p style="font-size:.85rem;color:#991b1b;font-weight:700;margin:0">'
            + 'You are about to <strong>permanently delete</strong> this group session:</p>'
            + '<p style="font-size:1.05rem;color:#dc2626;font-weight:900;margin:.65rem 0 0;text-transform:uppercase">' + title + '</p>'
            + '</div>'
            + '<p style="font-size:.8rem;color:#64748b;line-height:1.6;margin:0">'
            + 'All attendance records and individual notes will be deleted. The patients themselves will <strong>not</strong> be removed.'
            + '</p></div>',
        showCancelButton: true,
        confirmButtonText: 'Delete permanently',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        width: 480,
        reverseButtons: true,
        customClass: { popup: 'rounded-2xl' }
    }).then((result) => {
        if (result.isConfirmed) document.getElementById('delete-gs-' + id).submit();
    });
}
</script>

@endsection
