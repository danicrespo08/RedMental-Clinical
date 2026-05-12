@extends('layouts.app')
@section('title', 'IT — Authorization ' . $auth->auth_number)

@section('content')
@php
    use App\Models\It\Authorization;
    $patient = $auth->admission?->patient;
    $statusBadge = match($auth->status){
        'approved'  => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'check-circle', 'Approved'],
        'submitted' => ['bg-blue-50 text-blue-700 border-blue-200', 'send', 'Submitted'],
        'pending'   => ['bg-amber-50 text-amber-700 border-amber-200', 'clock', 'Pending'],
        'denied'    => ['bg-rose-50 text-rose-700 border-rose-200', 'x-circle', 'Denied'],
        default     => ['bg-slate-50 text-slate-500 border-slate-200', 'calendar-x', 'Expired'],
    };
    $remaining = max(0, ($auth->approved_units ?? 0) - ($auth->used_units ?? 0));
    $pct = $auth->approved_units > 0 ? min(100, round(($auth->used_units / $auth->approved_units) * 100)) : 0;
@endphp

<style>
    .it-section { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.02); margin-bottom:1rem; }
    .it-hd { padding:.75rem 1.25rem; display:flex; align-items:center; gap:.6rem; border-bottom:1px solid #e2e8f0; background:linear-gradient(180deg,#fff,#fafbff); }
    .it-num { width:26px; height:26px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:800; color:#fff; flex-shrink:0; background:linear-gradient(135deg,#7c3aed,#a855f7); }
    .it-title { font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#1e293b; }
    .it-body { padding:1rem 1.25rem; }
    .stat-card { background:#fff; border:1px solid #e2e8f0; border-radius:.85rem; padding:.85rem 1rem; }
    .stat-label { font-size:.6rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; }
    .stat-value { font-size:1.45rem; font-weight:800; line-height:1.1; margin-top:.15rem; font-family:'JetBrains Mono', ui-monospace, monospace; }
</style>

<div class="max-w-7xl mx-auto">
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('clinical.it.authorizations.index') }}" class="w-9 h-9 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-violet-600 transition-colors border border-slate-200 flex-shrink-0">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-violet-400 to-purple-600 text-white flex items-center justify-center font-black text-lg shadow-md shadow-violet-500/25">
                    {{ strtoupper(mb_substr($patient?->first_name ?? '?', 0, 1)) }}{{ strtoupper(mb_substr($patient?->last_name ?? '?', 0, 1)) }}
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest text-violet-500">IT · Authorization</div>
                    <h1 class="text-xl font-black text-slate-800">{{ $patient?->full_name ?? '—' }}</h1>
                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                        <span class="font-mono font-bold text-[10px] bg-blue-50 text-blue-600 border border-blue-200 px-2 py-0.5 rounded-md">Auth #{{ $auth->auth_number }}</span>
                        <span class="text-slate-200">|</span>
                        <span class="text-[10px] text-slate-400 font-medium">{{ Authorization::TYPES[$auth->auth_type] ?? $auth->auth_type }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider border {{ $statusBadge[0] }}">
                    <i data-lucide="{{ $statusBadge[1] }}" class="w-3.5 h-3.5"></i> {{ $statusBadge[2] }}
                </span>
                @can('clinical.it.authorizations.edit')
                    <a href="{{ route('clinical.it.authorizations.edit', $auth) }}" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5">
                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="stat-card">
            <div class="stat-label">Approved units</div>
            <div class="stat-value text-violet-600">{{ $auth->approved_units }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Used units</div>
            <div class="stat-value text-blue-600">{{ $auth->used_units }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Remaining</div>
            <div class="stat-value text-emerald-600">{{ $remaining }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Utilization</div>
            <div class="stat-value text-amber-600">{{ $pct }}%</div>
            <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden mt-1">
                <div class="h-full bg-gradient-to-r from-violet-400 to-purple-600" style="width: {{ $pct }}%;"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-1 space-y-4">
            <div class="it-section">
                <div class="it-hd"><div class="it-num">i</div><div><div class="it-title">Details</div></div></div>
                <div class="it-body space-y-2 text-[12px]">
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Payer</span><span class="font-semibold text-slate-700 text-right">{{ $auth->payer?->name ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Type</span><span class="font-semibold text-slate-700">{{ Authorization::TYPES[$auth->auth_type] ?? $auth->auth_type }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Auth #</span><span class="font-mono font-bold text-slate-700">{{ $auth->auth_number }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">CPT codes</span>
                        <span class="font-mono font-bold text-slate-700 text-right">
                            @if(is_array($auth->cpt_codes))
                                {{ implode(', ', $auth->cpt_codes) }}
                            @else
                                —
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <div class="it-section">
                <div class="it-hd"><div class="it-num"><i data-lucide="calendar-range" class="w-3.5 h-3.5"></i></div><div><div class="it-title">Date ranges</div></div></div>
                <div class="it-body space-y-3 text-[12px]">
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase mb-1">Requested</div>
                        <div class="text-slate-700">{{ optional($auth->requested_start_date)->format('M j, Y') ?? '—' }} → {{ optional($auth->requested_end_date)->format('M j, Y') ?? '—' }}</div>
                    </div>
                    <div class="pt-2 border-t border-slate-100">
                        <div class="text-[10px] font-bold text-slate-400 uppercase mb-1">Approved</div>
                        <div class="text-slate-700 font-semibold">{{ optional($auth->approved_start_date)->format('M j, Y') ?? '—' }} → {{ optional($auth->approved_end_date)->format('M j, Y') ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-4">
            @if($auth->denial_reason)
                <div class="it-section">
                    <div class="it-hd"><div class="it-num"><i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i></div><div><div class="it-title">Denial reason</div></div></div>
                    <div class="it-body text-[13px] text-slate-700 whitespace-pre-line">{{ $auth->denial_reason }}</div>
                </div>
            @endif
            @if($auth->notes)
                <div class="it-section">
                    <div class="it-hd"><div class="it-num"><i data-lucide="sticky-note" class="w-3.5 h-3.5"></i></div><div><div class="it-title">Notes</div></div></div>
                    <div class="it-body text-[13px] text-slate-700 whitespace-pre-line">{{ $auth->notes }}</div>
                </div>
            @endif
            @if(!$auth->denial_reason && !$auth->notes)
                <div class="it-section">
                    <div class="it-body text-center py-8 text-slate-400 italic text-sm">No notes documented.</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
