@extends('layouts.app')
@section('title', 'TCM — Service log entry')

@section('content')
@php
    $patient = $log->admission?->patient;
    $statusBadge = match($log->billing_status){
        'paid'      => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'check-circle-2', 'Paid'],
        'submitted' => ['bg-blue-50 text-blue-700 border-blue-200', 'send', 'Submitted'],
        'denied'    => ['bg-rose-50 text-rose-700 border-rose-200', 'x-circle', 'Denied'],
        'void'      => ['bg-slate-50 text-slate-500 border-slate-200', 'ban', 'Void'],
        default     => ['bg-amber-50 text-amber-700 border-amber-200', 'circle-dashed', 'Unbilled'],
    };
@endphp

<style>
    .tcm-section { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.02); margin-bottom:1rem; }
    .tcm-hd { padding:.75rem 1.25rem; display:flex; align-items:center; gap:.6rem; border-bottom:1px solid #e2e8f0; background:linear-gradient(180deg,#fff,#fafbff); }
    .tcm-num { width:26px; height:26px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:800; color:#fff; flex-shrink:0; background:linear-gradient(135deg,#ea580c,#f97316); }
    .tcm-title { font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#1e293b; }
    .tcm-body { padding:1rem 1.25rem; }
    .stat-card { background:#fff; border:1px solid #e2e8f0; border-radius:.85rem; padding:.85rem 1rem; }
    .stat-label { font-size:.6rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; }
    .stat-value { font-size:1.4rem; font-weight:800; line-height:1.1; margin-top:.15rem; font-family:'JetBrains Mono', ui-monospace, monospace; }
</style>

<div class="max-w-7xl mx-auto">
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('clinical.tcm.service_log.index') }}" class="w-9 h-9 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-orange-600 transition-colors border border-slate-200 flex-shrink-0">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-orange-400 to-amber-600 text-white flex items-center justify-center font-black text-lg shadow-md shadow-orange-500/25">
                    {{ strtoupper(mb_substr($patient?->first_name ?? '?', 0, 1)) }}{{ strtoupper(mb_substr($patient?->last_name ?? '?', 0, 1)) }}
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest text-orange-500">TCM · Service log</div>
                    <h1 class="text-xl font-black text-slate-800">{{ $patient?->full_name ?? '—' }}</h1>
                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                        <span class="font-mono font-bold text-[10px] bg-blue-50 text-blue-600 border border-blue-200 px-2 py-0.5 rounded-md">{{ $patient?->mrn ?? '---' }}</span>
                        <span class="text-slate-200">|</span>
                        <span class="text-[10px] text-slate-400 font-medium">{{ $log->service_date->format('M j, Y') }}</span>
                        <span class="text-slate-200">|</span>
                        <span class="font-mono text-[10px] font-bold bg-orange-50 text-orange-700 border border-orange-200 px-1.5 py-0.5 rounded">{{ $log->cpt_code }}{{ $log->modifier ? ' '.$log->modifier : '' }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider border {{ $statusBadge[0] }}">
                    <i data-lucide="{{ $statusBadge[1] }}" class="w-3.5 h-3.5"></i> {{ $statusBadge[2] }}
                </span>
                @can('clinical.tcm.service_log.edit')
                    <a href="{{ route('clinical.tcm.service_log.edit', $log) }}" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5">
                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="stat-card"><div class="stat-label">Units</div><div class="stat-value text-orange-600">{{ $log->units }}</div></div>
        <div class="stat-card"><div class="stat-label">Paid amount</div><div class="stat-value text-emerald-600">{{ $log->paid_amount ? '$'.number_format((float) $log->paid_amount, 2) : '—' }}</div></div>
        <div class="stat-card"><div class="stat-label">Note attached</div><div class="stat-value {{ $log->has_contact_note ? 'text-emerald-600' : 'text-slate-300' }}">@if($log->has_contact_note)<i data-lucide="check" class="w-6 h-6 inline"></i>@else—@endif</div></div>
        <div class="stat-card"><div class="stat-label">Diagnosis</div><div class="stat-value text-rose-600 text-base">{{ $log->diagnosis_code ?: '—' }}</div></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-1 space-y-4">
            <div class="tcm-section">
                <div class="tcm-hd"><div class="tcm-num">i</div><div><div class="tcm-title">Encounter</div></div></div>
                <div class="tcm-body space-y-2 text-[12px]">
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Date</span><span class="font-semibold text-slate-700">{{ $log->service_date->format('M j, Y') }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Time</span><span class="font-semibold text-slate-700">{{ $log->start_time }}@if($log->end_time) – {{ $log->end_time }}@endif</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Case manager</span><span class="font-semibold text-slate-700 text-right">{{ $log->caseManager?->full_name ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">CPT</span><span class="font-mono font-bold text-slate-700">{{ $log->cpt_code }}{{ $log->modifier ? ' '.$log->modifier : '' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">POS</span><span class="font-mono font-bold text-slate-700">{{ $log->place_of_service ?: '—' }}</span></div>
                </div>
            </div>

            <div class="tcm-section">
                <div class="tcm-hd"><div class="tcm-num"><i data-lucide="receipt" class="w-3.5 h-3.5"></i></div><div><div class="tcm-title">Billing</div></div></div>
                <div class="tcm-body space-y-2 text-[12px]">
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Auth #</span><span class="font-mono font-bold text-slate-700">{{ $log->auth_number ?: '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Claim #</span><span class="font-mono font-bold text-slate-700">{{ $log->claim_number ?: '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Billed</span><span class="font-semibold text-slate-700">{{ optional($log->billed_date)->format('M j, Y') ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Paid</span><span class="font-semibold text-slate-700">{{ optional($log->paid_date)->format('M j, Y') ?? '—' }}</span></div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-4">
            @if($log->denial_reason)
                <div class="tcm-section">
                    <div class="tcm-hd"><div class="tcm-num"><i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i></div><div><div class="tcm-title">Denial reason</div></div></div>
                    <div class="tcm-body text-[13px] text-slate-700 whitespace-pre-line">{{ $log->denial_reason }}</div>
                </div>
            @endif
            @if($log->notes)
                <div class="tcm-section">
                    <div class="tcm-hd"><div class="tcm-num"><i data-lucide="sticky-note" class="w-3.5 h-3.5"></i></div><div><div class="tcm-title">Notes</div></div></div>
                    <div class="tcm-body text-[13px] text-slate-700 whitespace-pre-line">{{ $log->notes }}</div>
                </div>
            @endif
            @if(! $log->denial_reason && ! $log->notes)
                <div class="tcm-section"><div class="tcm-body text-center py-8 text-slate-400 italic text-sm">No additional notes documented.</div></div>
            @endif
        </div>
    </div>
</div>
@endsection
