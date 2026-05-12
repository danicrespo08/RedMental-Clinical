@extends('layouts.app')
@section('title', 'IT — Discharge summary')

@section('content')
@php
    use App\Models\It\DischargeSummary;
    $patient = $discharge->patient;
    $statusBadge = match($discharge->status){
        'signed' => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'check-circle', 'Signed'],
        default  => ['bg-amber-50 text-amber-700 border-amber-200', 'clock', 'Draft'],
    };
    $prognosisColor = [
        'good' => 'text-emerald-600 bg-emerald-50 border-emerald-200',
        'fair' => 'text-blue-600 bg-blue-50 border-blue-200',
        'guarded' => 'text-amber-600 bg-amber-50 border-amber-200',
        'poor' => 'text-rose-600 bg-rose-50 border-rose-200',
    ];
    $narrative = [
        'presenting_problems' => 'Presenting problems',
        'treatment_summary' => 'Treatment summary',
        'clinical_course' => 'Clinical course',
        'response_to_treatment' => 'Response to treatment',
        'medications_at_discharge' => 'Medications at discharge',
        'risk_assessment_at_discharge' => 'Risk assessment at discharge',
    ];
    $aftercare = [
        'aftercare_plan' => 'Aftercare plan',
        'aftercare_referrals' => 'Aftercare referrals',
        'follow_up_appointments' => 'Follow-up appointments',
        'crisis_plan' => 'Crisis plan',
        'patient_instructions' => 'Patient instructions',
        'therapist_recommendation' => 'Therapist recommendation',
    ];
@endphp

<style>
    .it-section { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.02); margin-bottom:1rem; }
    .it-hd { padding:.75rem 1.25rem; display:flex; align-items:center; gap:.6rem; border-bottom:1px solid #e2e8f0; background:linear-gradient(180deg,#fff,#fafbff); }
    .it-num { width:26px; height:26px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:800; color:#fff; flex-shrink:0; background:linear-gradient(135deg,#7c3aed,#a855f7); }
    .it-title { font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#1e293b; }
    .it-body { padding:1rem 1.25rem; }

    .narr-block { padding:.85rem 1rem; border-left:3px solid #c7d2fe; background:#f5f3ff; border-radius:0 .5rem .5rem 0; margin-bottom:.65rem; }
    .narr-label { font-size:.6rem; font-weight:800; color:#6d28d9; text-transform:uppercase; letter-spacing:.05em; }
    .narr-content { font-size:.85rem; color:#334155; line-height:1.6; white-space:pre-wrap; margin-top:.25rem; }

    .stat-card { background:#fff; border:1px solid #e2e8f0; border-radius:.85rem; padding:.85rem 1rem; }
    .stat-label { font-size:.6rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; }
    .stat-value { font-size:1.45rem; font-weight:800; line-height:1.1; margin-top:.15rem; font-family:'JetBrains Mono', ui-monospace, monospace; }

    .sig-box { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:.75rem; padding:14px 18px; display:flex; justify-content:space-between; align-items:center; }
    .sig-box.draft { background:#fffbeb; border-color:#fde68a; }
</style>

<div class="max-w-7xl mx-auto">
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('clinical.it.discharges.index') }}" class="w-9 h-9 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-violet-600 transition-colors border border-slate-200 flex-shrink-0">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-violet-400 to-purple-600 text-white flex items-center justify-center font-black text-lg shadow-md shadow-violet-500/25">
                    {{ strtoupper(mb_substr($patient?->first_name ?? '?', 0, 1)) }}{{ strtoupper(mb_substr($patient?->last_name ?? '?', 0, 1)) }}
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest text-violet-500">IT · Discharge summary</div>
                    <h1 class="text-xl font-black text-slate-800">{{ $patient?->full_name ?? '—' }}</h1>
                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                        <span class="font-mono font-bold text-[10px] bg-blue-50 text-blue-600 border border-blue-200 px-2 py-0.5 rounded-md">{{ $patient?->mrn ?? '---' }}</span>
                        <span class="text-slate-200">|</span>
                        <span class="text-[10px] text-slate-400 font-medium">Discharged {{ $discharge->discharge_date->format('M j, Y') }}</span>
                        <span class="text-slate-200">|</span>
                        <span class="text-[10px] text-slate-400 font-medium">{{ DischargeSummary::DISCHARGE_TYPES[$discharge->discharge_type] ?? $discharge->discharge_type }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider border {{ $statusBadge[0] }}">
                    <i data-lucide="{{ $statusBadge[1] }}" class="w-3.5 h-3.5"></i> {{ $statusBadge[2] }}
                </span>
                @if(! $discharge->is_signed)
                    @can('clinical.it.discharges.edit')
                        <a href="{{ route('clinical.it.discharges.edit', $discharge) }}" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5"><i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit</a>
                    @endcan
                    @can('clinical.it.discharges.sign')
                        <form method="POST" action="{{ route('clinical.it.discharges.sign', $discharge) }}" class="inline" data-confirm="Sign this discharge?">@csrf
                            <button class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5"><i data-lucide="pen-tool" class="w-3.5 h-3.5"></i> Sign &amp; close</button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
        <div class="stat-card"><div class="stat-label">Sessions attended</div><div class="stat-value text-emerald-600">{{ $discharge->total_sessions_attended }}</div></div>
        <div class="stat-card"><div class="stat-label">Sessions absent</div><div class="stat-value text-rose-500">{{ $discharge->total_sessions_absent }}</div></div>
        <div class="stat-card"><div class="stat-label">Units billed</div><div class="stat-value text-violet-600">{{ $discharge->total_units_billed }}</div></div>
        <div class="stat-card"><div class="stat-label">Days in program</div><div class="stat-value text-blue-600">{{ $discharge->days_in_program }}</div></div>
        <div class="stat-card">
            <div class="stat-label">Prognosis</div>
            <div class="mt-1">
                @if($discharge->prognosis)
                    <span class="inline-block px-2 py-1 rounded-md text-[11px] font-bold uppercase border {{ $prognosisColor[$discharge->prognosis] ?? '' }}">{{ $discharge->prognosis }}</span>
                @else
                    <span class="text-slate-300 text-2xl">—</span>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-1 space-y-4">
            <div class="it-section">
                <div class="it-hd"><div class="it-num">i</div><div><div class="it-title">Episode summary</div></div></div>
                <div class="it-body space-y-2 text-[12px]">
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Admission</span><span class="font-semibold text-slate-700">{{ $discharge->admission_date?->format('M j, Y') }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Discharge</span><span class="font-semibold text-slate-700">{{ $discharge->discharge_date->format('M j, Y') }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Type</span><span class="font-semibold text-slate-700">{{ DischargeSummary::DISCHARGE_TYPES[$discharge->discharge_type] ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Reason</span><span class="font-semibold text-slate-700">{{ DischargeSummary::DISCHARGE_REASONS[$discharge->discharge_reason] ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Therapist</span><span class="font-semibold text-slate-700">{{ $discharge->therapist?->full_name ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Aftercare level</span><span class="font-semibold text-slate-700">{{ $discharge->aftercare_level ?? '—' }}</span></div>
                </div>
            </div>

            <div class="it-section">
                <div class="it-hd"><div class="it-num"><i data-lucide="heart-pulse" class="w-3.5 h-3.5"></i></div><div><div class="it-title">Diagnoses</div></div></div>
                <div class="it-body space-y-3">
                    @if($discharge->primary_dx_code)
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Primary (admission)</div>
                            <div class="font-mono text-[11px] bg-rose-50 text-rose-700 border border-rose-200 px-2 py-0.5 rounded inline-block mt-1">{{ $discharge->primary_dx_code }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">{{ $discharge->primary_dx_description }}</div>
                        </div>
                    @endif
                    @if($discharge->dx_at_discharge_code)
                        <div class="pt-2 border-t border-slate-100">
                            <div class="text-[10px] font-bold text-slate-400 uppercase">At discharge</div>
                            <div class="font-mono text-[11px] bg-slate-50 text-slate-700 border border-slate-200 px-2 py-0.5 rounded inline-block mt-1">{{ $discharge->dx_at_discharge_code }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">{{ $discharge->dx_at_discharge_description }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-4">
            <div class="it-section">
                <div class="it-hd"><div class="it-num">1</div><div><div class="it-title">Clinical course</div></div></div>
                <div class="it-body">
                    @php $hasAny = false; @endphp
                    @foreach($narrative as $f => $label)
                        @if($discharge->{$f})
                            @php $hasAny = true; @endphp
                            <div class="narr-block"><div class="narr-label">{{ $label }}</div><div class="narr-content">{{ $discharge->{$f} }}</div></div>
                        @endif
                    @endforeach
                    @unless($hasAny)<p class="text-slate-400 italic text-sm text-center py-4">No clinical narrative documented.</p>@endunless
                </div>
            </div>

            <div class="it-section">
                <div class="it-hd"><div class="it-num">2</div><div><div class="it-title">Aftercare plan</div></div></div>
                <div class="it-body">
                    @php $hasAny = false; @endphp
                    @foreach($aftercare as $f => $label)
                        @if($discharge->{$f})
                            @php $hasAny = true; @endphp
                            <div class="narr-block" style="background:#eff6ff; border-left-color:#60a5fa;">
                                <div class="narr-label" style="color:#1d4ed8;">{{ $label }}</div>
                                <div class="narr-content">{{ $discharge->{$f} }}</div>
                            </div>
                        @endif
                    @endforeach
                    @unless($hasAny)<p class="text-slate-400 italic text-sm text-center py-4">No aftercare plan documented.</p>@endunless
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <div class="sig-box {{ $discharge->is_signed ? '' : 'draft' }}">
                    <div>
                        <div class="font-bold text-sm">{{ $discharge->therapist?->full_name ?? '—' }}</div>
                    </div>
                    <div class="text-right">
                        @if($discharge->is_signed)
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-bold uppercase">
                                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Signed {{ $discharge->signed_at?->format('m/d/Y g:i A') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-amber-100 text-amber-700 rounded-lg text-xs font-bold uppercase">
                                <i data-lucide="clock" class="w-3.5 h-3.5"></i> Draft — awaiting signature
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
