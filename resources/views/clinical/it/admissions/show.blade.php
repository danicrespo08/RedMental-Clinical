@extends('layouts.app')
@section('title', 'IT — ' . $admission->patient->full_name)

@section('content')
    @if($admission->status === 'discharged')
        <div class="max-w-7xl mx-auto mb-5 flex items-center gap-3 px-5 py-3.5 rounded-xl
                    bg-amber-50 border border-amber-300 text-amber-800 text-sm font-semibold">
            <i data-lucide="lock" class="w-4 h-4 flex-shrink-0"></i>
            This admission is discharged — the chart is closed and its clinical records are read-only.
        </div>
    @endif
@php
    use App\Models\It\Admission;
    $patient = $admission->patient;
    $statusBadge = match($admission->status){
        'admitted'   => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'check-circle', 'Admitted'],
        'on_hold'    => ['bg-amber-50 text-amber-700 border-amber-200', 'pause-circle', 'On hold'],
        default      => ['bg-slate-50 text-slate-500 border-slate-200', 'log-out', 'Discharged'],
    };
    $totalUnits = $admission->sessions->sum('units');
    $totalMinutes = $admission->sessions->sum('duration_minutes');
    $mtp        = $admission->treatmentPlans->sortByDesc('id')->first();
    $signedMtp  = $admission->treatmentPlans->firstWhere('is_signed', true);
    $isDischarged = $admission->status === 'discharged';
@endphp

<style>
    .it-section { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.02); margin-bottom:1rem; }
    .it-hd { padding:.75rem 1.25rem; display:flex; align-items:center; gap:.6rem; border-bottom:1px solid #e2e8f0; background:linear-gradient(180deg,#fff,#fafbff); }
    .it-hd .it-num { width:26px; height:26px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:800; color:#fff; flex-shrink:0; background:linear-gradient(135deg,#7c3aed,#a855f7); }
    .it-hd .it-title { font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#1e293b; }
    .it-hd .it-sub { font-size:.6rem; color:#94a3b8; font-weight:600; margin-top:1px; }
    .it-body { padding:1rem 1.25rem; }

    .stat-card { background:#fff; border:1px solid #e2e8f0; border-radius:.85rem; padding:.85rem 1rem; }
    .stat-label { font-size:.6rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; }
    .stat-value { font-size:1.45rem; font-weight:800; line-height:1.1; margin-top:.15rem; font-family:'JetBrains Mono', ui-monospace, monospace; }
</style>

<div class="max-w-7xl mx-auto">
    {{-- HEADER --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('clinical.it.admissions.index') }}" class="w-9 h-9 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-violet-600 transition-colors border border-slate-200 flex-shrink-0">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-violet-400 to-purple-600 text-white flex items-center justify-center font-black text-lg shadow-md shadow-violet-500/25">
                    {{ strtoupper(mb_substr($patient->first_name ?? '?', 0, 1)) }}{{ strtoupper(mb_substr($patient->last_name ?? '?', 0, 1)) }}
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest text-violet-500">IT · Individual therapy</div>
                    <h1 class="text-xl font-black text-slate-800">{{ $patient->full_name }}</h1>
                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                        <span class="font-mono font-bold text-[10px] bg-blue-50 text-blue-600 border border-blue-200 px-2 py-0.5 rounded-md">{{ $patient->mrn ?? '---' }}</span>
                        <span class="text-slate-200">|</span>
                        <span class="text-[10px] text-slate-400 font-medium">DOB: {{ $patient->date_of_birth ?? '---' }}</span>
                        <span class="text-slate-200">|</span>
                        <span class="text-[10px] text-slate-400 font-medium">Admitted {{ $admission->admission_date->format('M j, Y') }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider border {{ $statusBadge[0] }}">
                    <i data-lucide="{{ $statusBadge[1] }}" class="w-3.5 h-3.5"></i> {{ $statusBadge[2] }}
                </span>
                @can('clinical.it.create')
                    @if($signedMtp && ! $isDischarged)
                        <a href="{{ route('clinical.it.sessions.create', $admission) }}" class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i> New session
                        </a>
                    @elseif(! $isDischarged)
                        <span title="Sign a treatment plan first" class="px-3 py-1.5 bg-slate-100 text-slate-400 border border-slate-200 text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5 cursor-not-allowed">
                            <i data-lucide="lock" class="w-3.5 h-3.5"></i> New session
                        </span>
                    @endif
                @endcan
                @can('clinical.it.edit')
                    <a href="{{ route('clinical.it.admissions.edit', $admission) }}" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5">
                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
                    </a>
                @endcan
            </div>
        </div>
    </div>

    {{-- METRICS --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="stat-card">
            <div class="stat-label">Sessions</div>
            <div class="stat-value text-violet-600">{{ $admission->sessions->count() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total units</div>
            <div class="stat-value text-blue-600">{{ $totalUnits }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Therapy minutes</div>
            <div class="stat-value text-emerald-600">{{ $totalMinutes }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Days in program</div>
            <div class="stat-value text-amber-600">{{ (int) $admission->admission_date->diffInDays($admission->discharge_date ?? now()) }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- LEFT COLUMN --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="it-section">
                <div class="it-hd"><div class="it-num">i</div><div><div class="it-title">Admission details</div></div></div>
                <div class="it-body space-y-2 text-[12px]">
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Therapist</span><span class="font-semibold text-slate-700 text-right">{{ $admission->therapist?->full_name ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Authorization</span><span class="font-mono font-semibold text-slate-700">{{ $admission->authorization_number ?: '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Admission date</span><span class="font-semibold text-slate-700">{{ $admission->admission_date->format('M j, Y') }}</span></div>
                    @if($admission->discharge_date)
                        <div class="flex justify-between"><span class="text-slate-400 font-bold">Discharge date</span><span class="font-semibold text-slate-700">{{ $admission->discharge_date->format('M j, Y') }}</span></div>
                    @endif
                </div>
            </div>

            <div class="it-section">
                <div class="it-hd"><div class="it-num"><i data-lucide="folder-open" class="w-3.5 h-3.5"></i></div><div><div class="it-title">Clinical documents</div></div></div>
                <div class="it-body space-y-2">
                    {{-- Treatment plan (MTP) --}}
                    @php
                        $mtpState = ! $mtp ? 'pending' : ($mtp->is_signed ? 'signed' : 'draft');
                        $mtpMap = [
                            'pending' => ['Not started', 'text-slate-400 bg-slate-50 border-slate-200'],
                            'draft'   => ['Draft — needs signature', 'text-amber-600 bg-amber-50 border-amber-200'],
                            'signed'  => ['Signed', 'text-emerald-600 bg-emerald-50 border-emerald-200'],
                        ];
                    @endphp
                    <div class="flex items-center justify-between gap-2 border border-slate-200 rounded-lg px-3 py-2">
                        <div class="min-w-0">
                            <div class="text-[12px] font-bold text-slate-700">Treatment plan</div>
                            <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase border {{ $mtpMap[$mtpState][1] }}">{{ $mtpMap[$mtpState][0] }}</span>
                        </div>
                        @if($isDischarged)
                            @if($mtp)<a href="{{ route('clinical.it.treatment_plans.show', $mtp) }}" class="text-[10px] font-bold uppercase text-slate-500 hover:text-violet-600">View</a>@endif
                        @elseif(! $mtp)
                            @can('clinical.it.treatment_plans.create')
                                <a href="{{ route('clinical.it.treatment_plans.create', ['admission_id' => $admission->id]) }}" class="px-2.5 py-1 bg-violet-600 hover:bg-violet-700 text-white text-[10px] font-bold uppercase tracking-wider rounded-md inline-flex items-center gap-1 whitespace-nowrap">
                                    <i data-lucide="plus" class="w-3 h-3"></i> Create
                                </a>
                            @endcan
                        @elseif(! $mtp->is_signed)
                            @can('clinical.it.treatment_plans.edit')
                                <a href="{{ route('clinical.it.treatment_plans.edit', $mtp) }}" class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-[10px] font-bold uppercase tracking-wider rounded-md whitespace-nowrap">Continue</a>
                            @endcan
                        @else
                            <a href="{{ route('clinical.it.treatment_plans.show', $mtp) }}" class="text-[10px] font-bold uppercase text-violet-600 hover:underline">View</a>
                        @endif
                    </div>

                    {{-- Discharge summary --}}
                    <div class="flex items-center justify-between gap-2 border border-slate-200 rounded-lg px-3 py-2">
                        <div class="min-w-0">
                            <div class="text-[12px] font-bold text-slate-700">Discharge summary</div>
                            <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase border {{ $admission->dischargeSummary ? 'text-emerald-600 bg-emerald-50 border-emerald-200' : 'text-slate-400 bg-slate-50 border-slate-200' }}">{{ $admission->dischargeSummary ? 'On file' : 'Not started' }}</span>
                        </div>
                        @if($admission->dischargeSummary)
                            <a href="{{ route('clinical.it.discharges.show', $admission->dischargeSummary) }}" class="text-[10px] font-bold uppercase text-violet-600 hover:underline">View</a>
                        @else
                            @can('clinical.it.discharges.create')
                                <a href="{{ route('clinical.it.discharges.create', ['admission_id' => $admission->id]) }}" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-[10px] font-bold uppercase tracking-wider rounded-md inline-flex items-center gap-1 whitespace-nowrap">
                                    <i data-lucide="log-out" class="w-3 h-3"></i> Discharge
                                </a>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>

            <div class="it-section">
                <div class="it-hd"><div class="it-num"><i data-lucide="heart-pulse" class="w-3.5 h-3.5"></i></div><div><div class="it-title">Diagnosis (ICD-10)</div></div></div>
                <div class="it-body">
                    @if($admission->diagnosis_code)
                        <div class="font-mono text-[11px] bg-rose-50 text-rose-700 border border-rose-200 px-2 py-0.5 rounded inline-block">{{ $admission->diagnosis_code }}</div>
                        @if($admission->diagnosis_description)
                            <div class="text-[12px] text-slate-600 mt-1.5">{{ $admission->diagnosis_description }}</div>
                        @endif
                    @else
                        <p class="text-slate-400 italic text-sm">No diagnosis assigned.</p>
                    @endif
                </div>
            </div>

            @if($admission->notes)
                <div class="it-section">
                    <div class="it-hd"><div class="it-num"><i data-lucide="sticky-note" class="w-3.5 h-3.5"></i></div><div><div class="it-title">Notes</div></div></div>
                    <div class="it-body text-[13px] text-slate-700 whitespace-pre-line leading-relaxed">{{ $admission->notes }}</div>
                </div>
            @endif
        </div>

        {{-- RIGHT COLUMN --}}
        <div class="lg:col-span-2">
            <div class="it-section">
                <div class="it-hd">
                    <div class="it-num">{{ $admission->sessions->count() }}</div>
                    <div class="flex-1">
                        <div class="it-title">Therapy sessions</div>
                        <div class="it-sub">SOAP-format notes per session</div>
                    </div>
                    @can('clinical.it.create')
                        @if($signedMtp && ! $isDischarged)
                            <a href="{{ route('clinical.it.sessions.create', $admission) }}" class="px-2.5 py-1 bg-violet-50 hover:bg-violet-100 text-violet-700 border border-violet-200 text-[10px] font-bold uppercase tracking-wider rounded-md inline-flex items-center gap-1">
                                <i data-lucide="plus" class="w-3 h-3"></i> New
                            </a>
                        @endif
                    @endcan
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-2 text-left">Date</th>
                            <th class="px-4 py-2 text-left">Time</th>
                            <th class="px-4 py-2 text-center">CPT</th>
                            <th class="px-4 py-2 text-center">Units</th>
                            <th class="px-4 py-2 text-center">Min</th>
                            <th class="px-4 py-2 text-left">Therapist</th>
                            <th class="px-4 py-2 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($admission->sessions as $session)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-2.5">
                                    <a href="{{ route('clinical.it.sessions.show', [$admission, $session]) }}" class="font-semibold text-slate-700 text-[12px] hover:text-violet-700">{{ $session->session_date->format('M j, Y') }}</a>
                                </td>
                                <td class="px-4 py-2.5 text-[11px] text-slate-500">{{ $session->start_time ?: '—' }}{{ $session->end_time ? ' – '.$session->end_time : '' }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="font-mono text-[10px] font-bold bg-violet-50 text-violet-700 border border-violet-200 px-1.5 py-0.5 rounded">{{ $session->cpt_code }}{{ $session->modifier ? ' '.$session->modifier : '' }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-center font-mono text-[12px] font-bold text-blue-600">{{ $session->units }}</td>
                                <td class="px-4 py-2.5 text-center font-mono text-[11px] text-slate-500">{{ $session->duration_minutes ?: '—' }}</td>
                                <td class="px-4 py-2.5 text-[12px] text-slate-600">{{ $session->therapist?->full_name ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('clinical.it.sessions.show', [$admission, $session]) }}" class="p-1 text-slate-500 hover:text-violet-600 hover:bg-violet-50 rounded"><i data-lucide="eye" class="w-3.5 h-3.5"></i></a>
                                        @can('clinical.it.edit')<a href="{{ route('clinical.it.sessions.edit', [$admission, $session]) }}" class="p-1 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></a>@endcan
                                        @can('clinical.it.delete')
                                            <form method="POST" action="{{ route('clinical.it.sessions.destroy', [$admission, $session]) }}" data-confirm-delete="this session">@csrf @method('DELETE')
                                                <button class="p-1 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-12 text-center text-slate-400 text-sm">
                                <i data-lucide="calendar-x" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                                No sessions recorded — click "New session" to start.
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
