@extends('layouts.app')
@section('title', 'PSR — ' . $session->title)

@section('content')

<style>
    .detail-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1.25rem 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,.02); }
    .detail-title {
        font-size: .58rem; font-weight: 800; color: #94a3b8;
        text-transform: uppercase; letter-spacing: .06em;
        margin-bottom: 1rem; display: flex; align-items: center; gap: .4rem;
    }
    .info-label { font-size: .55rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; }
    .info-value { font-size: .85rem; font-weight: 700; color: #1e293b; margin-top: .15rem; }
    .info-value.mono { font-family: ui-monospace, monospace; font-size: .8rem; }

    .att-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .att-table th {
        background: linear-gradient(180deg, #f8fafc, #f1f5f9);
        padding: .85rem 1rem; font-size: .58rem; font-weight: 800;
        color: #94a3b8; text-transform: uppercase; letter-spacing: .05em;
        border-bottom: 1px solid #e2e8f0; text-align: left; white-space: nowrap;
    }
    .att-table td {
        padding: .8rem 1rem; font-size: .82rem; color: #334155;
        border-bottom: 1px solid #f1f5f9; vertical-align: middle;
    }
    .att-table tbody tr { transition: background .15s ease; }
    .att-table tbody tr:hover td { background: #fafbff; }

    .att-badge {
        display: inline-flex; align-items: center; gap: .25rem;
        padding: .25rem .6rem; border-radius: .4rem;
        font-size: .58rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
    }

    .pat-avatar {
        width: 36px; height: 36px; border-radius: .65rem;
        display: flex; align-items: center; justify-content: center;
        font-size: .72rem; font-weight: 900; flex-shrink: 0; letter-spacing: .02em;
    }

    .header-card {
        background:
            radial-gradient(circle at 100% 0, rgba(99,102,241,.08), transparent 50%),
            radial-gradient(circle at 0 100%, rgba(56,189,248,.06), transparent 50%),
            #fff;
        border: 1px solid #e2e8f0;
    }

    .timeline {
        position: relative; padding-left: 22px; margin: .5rem 0;
        border-left: 2px solid #e0e7ff;
    }
    .tl-item { position: relative; padding: 0 0 .85rem 14px; }
    .tl-item::before {
        content: ''; position: absolute; left: -7px; top: 5px;
        width: 12px; height: 12px; border-radius: 50%;
        background: #6366f1; border: 2px solid #fff; box-shadow: 0 0 0 2px #c7d2fe;
    }
    .tl-time { font-size: .7rem; font-weight: 800; color: #4338ca; text-transform: uppercase; letter-spacing: .04em; }
    .tl-text { font-size: .82rem; color: #334155; margin-top: 2px; }
    .tl-dur  { font-size: .65rem; color: #94a3b8; font-weight: 600; }

    @keyframes pulse-dot { 0%, 100% { opacity: 1 } 50% { opacity: .5 } }
    .pulse-dot { animation: pulse-dot 1.6s ease-in-out infinite; }
</style>

@php
    $statusColors = [
        'scheduled'   => 'bg-blue-50 text-blue-700 border-blue-200',
        'in_progress' => 'bg-amber-50 text-amber-700 border-amber-200',
        'completed'   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'cancelled'   => 'bg-slate-50 text-slate-500 border-slate-200',
    ];
    $statusDots = [
        'scheduled'   => 'bg-blue-400 pulse-dot',
        'in_progress' => 'bg-amber-400 pulse-dot',
        'completed'   => 'bg-emerald-400',
        'cancelled'   => 'bg-slate-400',
    ];
    $attColors = [
        'present'    => 'bg-emerald-50 text-emerald-700',
        'absent'     => 'bg-rose-50 text-rose-600',
        'late'       => 'bg-orange-50 text-orange-700',
        'left_early' => 'bg-yellow-50 text-yellow-700',
    ];
    $avatarColors = ['bg-indigo-100 text-indigo-600', 'bg-emerald-100 text-emerald-600', 'bg-amber-100 text-amber-600', 'bg-rose-100 text-rose-600', 'bg-sky-100 text-sky-600', 'bg-violet-100 text-violet-600', 'bg-teal-100 text-teal-600', 'bg-pink-100 text-pink-600'];

    $totalAtt   = $session->attendees->count();
    $present    = $session->attendees->where('attendance_status', 'present')->count();
    $absent     = $session->attendees->where('attendance_status', 'absent')->count();
    $late       = $session->attendees->where('attendance_status', 'late')->count();
    $totalUnits = $session->attendees->whereIn('attendance_status', ['present', 'late', 'left_early'])->sum('units');
    $duration   = $session->duration_minutes;
    $capPct     = $session->max_capacity > 0 ? round(($totalAtt / $session->max_capacity) * 100) : 0;
@endphp

<div class="max-w-7xl mx-auto">

    <div class="header-card rounded-2xl p-5 mb-5 shadow-sm">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-3.5 min-w-0">
                <a href="{{ route('clinical.psr.group_sessions.index') }}"
                   class="w-9 h-9 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors border border-slate-200 flex-shrink-0">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div class="p-2.5 bg-gradient-to-br from-indigo-500 to-violet-600 text-white rounded-xl shadow-md shadow-indigo-500/30 flex-shrink-0">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
                <div class="min-w-0">
                    <h1 class="text-xl font-black text-slate-800 tracking-tight truncate">{{ $session->title }}</h1>
                    <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                        <span class="text-[10px] text-slate-500 font-bold inline-flex items-center gap-1"><i data-lucide="calendar" class="w-3 h-3"></i> {{ $session->session_date->format('l, M j, Y') }}</span>
                        <span class="text-slate-300">·</span>
                        <span class="text-[10px] text-slate-500 font-bold inline-flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3"></i> {{ \Carbon\Carbon::parse($session->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($session->end_time)->format('g:i A') }}</span>
                        <span class="text-slate-300">·</span>
                        <span class="text-[10px] text-slate-500 font-bold inline-flex items-center gap-1"><i data-lucide="hospital" class="w-3 h-3"></i> {{ $session->clinic?->name ?? '—' }}</span>
                        <span class="text-slate-300">·</span>
                        <span class="text-[10px] font-mono font-bold text-indigo-600 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded">{{ $session->service_code }}@if($session->modifier) {{ $session->modifier }}@endif</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <div class="w-2 h-2 rounded-full {{ $statusDots[$session->status] ?? 'bg-slate-400' }}"></div>
                <span class="px-3.5 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider border {{ $statusColors[$session->status] ?? '' }}">
                    {{ $session->status_label }}
                </span>
                @can('clinical.psr.group_sessions.edit')
                    @if(! $session->is_signed)
                        <a href="{{ route('clinical.psr.group_sessions.edit', $session) }}"
                           class="ml-1 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 text-[10px] font-bold uppercase tracking-wider rounded-lg border border-amber-200 inline-flex items-center gap-1.5 transition">
                            <i data-lucide="pencil" class="w-3 h-3"></i> Edit
                        </a>
                    @endif
                @endcan
                @if($session->is_signed)
                    <span class="ml-1 px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5">
                        <i data-lucide="lock" class="w-3 h-3"></i> Signed
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
        <div class="detail-card flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center"><i data-lucide="user-check" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-emerald-600 leading-none">{{ $present }}</div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Present</div>
            </div>
        </div>
        <div class="detail-card flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center"><i data-lucide="clock-alert" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-orange-600 leading-none">{{ $late }}</div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Late</div>
            </div>
        </div>
        <div class="detail-card flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center"><i data-lucide="user-x" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-rose-600 leading-none">{{ $absent }}</div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Absent</div>
            </div>
        </div>
        <div class="detail-card flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center"><i data-lucide="receipt" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800 leading-none">{{ $totalUnits }}</div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Units billed</div>
            </div>
        </div>
        <div class="detail-card flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center"><i data-lucide="timer" class="w-5 h-5"></i></div>
            <div>
                <div class="text-2xl font-black text-slate-800 leading-none">{{ $duration }}<span class="text-base text-slate-400">m</span></div>
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Duration</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

        {{-- LEFT (4 cols): Session info, Therapists, Activities --}}
        <div class="lg:col-span-4 space-y-4">

            <div class="detail-card">
                <h3 class="detail-title"><i data-lucide="info" class="w-3.5 h-3.5 text-indigo-500"></i> Session details</h3>
                <div class="space-y-3">
                    <div>
                        <div class="info-label">Session type</div>
                        <div class="info-value">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $session->session_type)) }}</div>
                    </div>
                    <div class="pt-2.5 border-t border-slate-100">
                        <div class="info-label">CPT/HCPCS code</div>
                        <div class="info-value mono">{{ $session->service_code }}@if($session->modifier) <span class="text-slate-400">/ {{ $session->modifier }}</span>@endif</div>
                    </div>
                    <div class="pt-2.5 border-t border-slate-100">
                        <div class="info-label">Place of service</div>
                        <div class="info-value mono">{{ $session->place_of_service ?: '—' }}</div>
                    </div>
                    <div class="pt-2.5 border-t border-slate-100">
                        <div class="info-label">Capacity</div>
                        <div class="info-value">
                            <span class="font-mono">{{ $totalAtt }} / {{ $session->max_capacity }}</span>
                            <span class="ml-2 text-[10px] font-bold {{ $capPct >= 90 ? 'text-rose-600' : ($capPct >= 70 ? 'text-amber-600' : 'text-emerald-600') }}">{{ $capPct }}%</span>
                        </div>
                    </div>
                    @if($session->break_minutes)
                        <div class="pt-2.5 border-t border-slate-100">
                            <div class="info-label">Break</div>
                            <div class="info-value">
                                {{ $session->break_minutes }} min
                                @if($session->break_start_time && $session->break_end_time)
                                    <span class="text-[10px] text-slate-400 font-medium ml-1">({{ \Carbon\Carbon::parse($session->break_start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($session->break_end_time)->format('g:i A') }})</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="detail-card">
                <h3 class="detail-title"><i data-lucide="user-cog" class="w-3.5 h-3.5 text-blue-500"></i> Staff</h3>
                <div class="space-y-3">
                    <div>
                        <div class="info-label">Lead therapist</div>
                        <div class="info-value">{{ $session->leadTherapist?->full_name ?? '—' }}</div>
                        @if($session->leadTherapist?->position)
                            <div class="text-[10px] text-slate-400 font-medium">{{ $session->leadTherapist->position }}</div>
                        @endif
                    </div>
                    @if($session->coTherapist)
                        <div class="pt-2.5 border-t border-slate-100">
                            <div class="info-label">Co-therapist</div>
                            <div class="info-value">{{ $session->coTherapist->full_name }}</div>
                            @if($session->coTherapist->position)
                                <div class="text-[10px] text-slate-400 font-medium">{{ $session->coTherapist->position }}</div>
                            @endif
                        </div>
                    @endif
                    @if($session->is_signed)
                        <div class="pt-2.5 border-t border-slate-100">
                            <div class="info-label">Signed by</div>
                            <div class="info-value">{{ $session->signer?->name ?? 'system' }}</div>
                            <div class="text-[10px] text-slate-400 font-medium">{{ optional($session->signed_at)->format('M j, Y g:i A') }}</div>
                        </div>
                    @endif
                </div>
            </div>

            @if($session->activities && count($session->activities))
                <div class="detail-card">
                    <h3 class="detail-title"><i data-lucide="list-ordered" class="w-3.5 h-3.5 text-emerald-500"></i> Activities timeline</h3>
                    <div class="timeline">
                        @foreach($session->activities as $act)
                            <div class="tl-item">
                                <div class="tl-time">+{{ $act['minute'] ?? 0 }} min @if(isset($act['duration'])) <span class="tl-dur">· {{ $act['duration'] }}m</span>@endif</div>
                                <div class="tl-text">{{ $act['activity'] ?? '—' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- RIGHT (8 cols): Attendees, Summary, Notes --}}
        <div class="lg:col-span-8 space-y-4">

            {{-- Attendees table --}}
            <div class="detail-card p-0 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                    <h3 class="detail-title mb-0"><i data-lucide="users-round" class="w-3.5 h-3.5 text-indigo-500"></i> Roster — {{ $totalAtt }} attendees</h3>
                    <div class="flex items-center gap-1.5 text-[10px] font-bold">
                        <span class="att-badge bg-emerald-50 text-emerald-700">{{ $present }} P</span>
                        @if($late)<span class="att-badge bg-orange-50 text-orange-700">{{ $late }} L</span>@endif
                        @if($absent)<span class="att-badge bg-rose-50 text-rose-700">{{ $absent }} A</span>@endif
                    </div>
                </div>
                @if($totalAtt > 0)
                    <div class="overflow-x-auto">
                        <table class="att-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Patient</th>
                                    <th>Status</th>
                                    <th>In / Out</th>
                                    <th class="text-right" style="text-align:right;">Units</th>
                                    <th>Participation</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($session->attendees->sortBy('patient.last_name') as $i => $att)
                                    @php
                                        $color = $avatarColors[$i % count($avatarColors)];
                                        $initials = strtoupper(mb_substr($att->patient?->first_name ?? '?', 0, 1) . mb_substr($att->patient?->last_name ?? '?', 0, 1));
                                        $attLabel = ucfirst(str_replace('_', ' ', $att->attendance_status));
                                    @endphp
                                    <tr>
                                        <td class="font-bold text-slate-400">{{ $i + 1 }}</td>
                                        <td>
                                            <div class="flex items-center gap-2.5">
                                                <div class="pat-avatar {{ $color }}">{{ $initials }}</div>
                                                <div>
                                                    <div class="font-bold text-slate-800 text-sm">{{ $att->patient?->full_name ?? '—' }}</div>
                                                    <div class="text-[10px] text-slate-400 font-mono">{{ $att->patient?->mrn ?: '—' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="att-badge {{ $attColors[$att->attendance_status] ?? 'bg-slate-100 text-slate-600' }}">
                                                {{ $attLabel }}
                                            </span>
                                        </td>
                                        <td class="text-xs font-mono text-slate-500">
                                            @if($att->check_in_time || $att->check_out_time)
                                                {{ $att->check_in_time ?: '—' }} → {{ $att->check_out_time ?: '—' }}
                                            @else
                                                <span class="text-slate-300">—</span>
                                            @endif
                                        </td>
                                        <td class="text-right font-mono font-bold {{ $att->attendance_status === 'absent' ? 'text-slate-300' : 'text-indigo-600' }}" style="text-align:right;">
                                            {{ $att->attendance_status === 'absent' ? '—' : $att->units }}
                                        </td>
                                        <td class="text-xs text-slate-600">
                                            @if($att->participation_level)
                                                {{ ucfirst($att->participation_level) }}
                                            @else <span class="text-slate-300">—</span> @endif
                                        </td>
                                        <td class="text-xs text-slate-500 max-w-[220px] truncate" title="{{ $att->individual_notes }}">
                                            {{ $att->individual_notes ?: '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-12 text-center text-slate-400 text-sm">No attendees registered for this session.</div>
                @endif
            </div>

            {{-- Session summary --}}
            @if($session->session_summary || $session->notes)
                <div class="detail-card">
                    <h3 class="detail-title"><i data-lucide="notebook-pen" class="w-3.5 h-3.5 text-violet-500"></i> Session summary</h3>
                    @if($session->session_summary)
                        <p class="text-sm text-slate-700 whitespace-pre-line leading-relaxed">{{ $session->session_summary }}</p>
                    @endif
                    @if($session->notes)
                        <div class="mt-3 pt-3 border-t border-slate-100">
                            <div class="info-label mb-1">Internal notes</div>
                            <p class="text-sm text-slate-600 whitespace-pre-line leading-relaxed">{{ $session->notes }}</p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Linked progress notes --}}
            @if($session->progressNotes->count())
                <div class="detail-card p-0 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h3 class="detail-title mb-0"><i data-lucide="file-text" class="w-3.5 h-3.5 text-blue-500"></i> Progress notes generated from this session</h3>
                    </div>
                    <table class="att-table">
                        <thead>
                            <tr><th>Patient</th><th>Therapist</th><th>Status</th><th>Risk</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach($session->progressNotes as $n)
                                @php
                                    $sc = match($n->status){ 'signed'=>'bg-emerald-100 text-emerald-700', 'addendum'=>'bg-blue-100 text-blue-700', default=>'bg-amber-100 text-amber-700' };
                                    $rc = match($n->risk_level){ 'high'=>'bg-rose-100 text-rose-700', 'moderate'=>'bg-amber-100 text-amber-700', 'low'=>'bg-blue-100 text-blue-700', default=>'bg-slate-100 text-slate-600' };
                                @endphp
                                <tr>
                                    <td class="font-bold text-slate-800">{{ $n->patient?->full_name ?? '—' }}</td>
                                    <td class="text-xs">{{ $n->therapist?->full_name ?? '—' }}</td>
                                    <td><span class="att-badge {{ $sc }}">{{ $n->status }}</span></td>
                                    <td><span class="att-badge {{ $rc }}">{{ $n->risk_level }}</span></td>
                                    <td class="text-right" style="text-align:right;">
                                        <a href="{{ route('clinical.psr.progress_notes.show', $n) }}" class="text-[10px] font-black text-blue-600 hover:underline uppercase tracking-wider">View</a>
                                    </td>
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
