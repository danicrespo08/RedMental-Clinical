@extends('layouts.app')
@section('title', 'IT — Treatment plan')

@section('content')
@php
    $patient = $plan->admission?->patient;
    $admission = $plan->admission;
    $goalsCount = $plan->goals->count();
    $objectivesCount = $plan->goals->sum(fn ($g) => $g->objectives->count());
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
    .goal-card { background:#fff; border:1px solid #e2e8f0; border-radius:.75rem; padding:1rem 1.25rem; margin-bottom:.75rem; }
    .goal-code-badge { background:linear-gradient(135deg,#7c3aed,#a855f7); color:#fff; padding:3px 12px; border-radius:20px; font-weight:700; font-size:.72rem; }
    .objective-card { background:#faf5ff; border-left:3px solid #8b5cf6; padding:.75rem 1rem; margin:.5rem 0 .5rem 1.25rem; border-radius:0 .5rem .5rem 0; }
    .sig-box { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:.75rem; padding:14px 18px; display:flex; justify-content:space-between; align-items:center; }
    .sig-box.draft { background:#fffbeb; border-color:#fde68a; }
</style>

<div class="max-w-7xl mx-auto">
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('clinical.it.treatment_plans.index') }}" class="w-9 h-9 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-violet-600 transition-colors border border-slate-200 flex-shrink-0">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-violet-400 to-purple-600 text-white flex items-center justify-center font-black text-lg shadow-md shadow-violet-500/25">
                    {{ strtoupper(mb_substr($patient?->first_name ?? '?', 0, 1)) }}{{ strtoupper(mb_substr($patient?->last_name ?? '?', 0, 1)) }}
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest text-violet-500">IT · Treatment plan</div>
                    <h1 class="text-xl font-black text-slate-800">{{ $patient?->full_name ?? '—' }}</h1>
                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                        <span class="font-mono font-bold text-[10px] bg-blue-50 text-blue-600 border border-blue-200 px-2 py-0.5 rounded-md">{{ $patient?->mrn ?? '---' }}</span>
                        <span class="text-slate-200">|</span>
                        <span class="text-[10px] text-slate-400 font-medium">Plan: {{ $plan->start_date->format('M j, Y') }} → {{ $plan->end_date->format('M j, Y') }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if($plan->is_signed)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider border bg-emerald-50 text-emerald-700 border-emerald-200">
                        <i data-lucide="lock" class="w-3.5 h-3.5"></i> Signed
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider border bg-amber-50 text-amber-700 border-amber-200">
                        <i data-lucide="clock" class="w-3.5 h-3.5"></i> Draft
                    </span>
                    @can('clinical.it.treatment_plans.edit')<a href="{{ route('clinical.it.treatment_plans.edit', $plan) }}" class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5"><i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit</a>@endcan
                    @can('clinical.it.treatment_plans.sign')
                        <form method="POST" action="{{ route('clinical.it.treatment_plans.sign', $plan) }}" class="inline">@csrf
                            <button class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5">
                                <i data-lucide="pen-tool" class="w-3.5 h-3.5"></i> Sign
                            </button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-1 space-y-4">
            <div class="it-section">
                <div class="it-hd"><div class="it-num">i</div><div><div class="it-title">Plan period</div></div></div>
                <div class="it-body space-y-2 text-[12px]">
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Start</span><span class="font-semibold text-slate-700">{{ $plan->start_date->format('M j, Y') }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">End</span><span class="font-semibold text-slate-700">{{ $plan->end_date->format('M j, Y') }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Duration</span><span class="font-semibold text-slate-700">{{ $plan->start_date->diffInDays($plan->end_date) }} days</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Goals</span><span class="font-bold text-violet-600">{{ $goalsCount }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Objectives</span><span class="font-bold text-violet-600">{{ $objectivesCount }}</span></div>
                </div>
            </div>

            <div class="it-section">
                <div class="it-hd"><div class="it-num"><i data-lucide="user-check" class="w-3.5 h-3.5"></i></div><div><div class="it-title">Therapist</div></div></div>
                <div class="it-body text-[13px] font-bold text-slate-700">{{ $admission?->therapist?->full_name ?? '—' }}</div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-4">
            @foreach([
                'presenting_problem'=>['Presenting problem', '1'],
                'long_term_goal'    =>['Long-term goal', '2'],
                'discharge_criteria'=>['Discharge criteria', '3'],
                'interventions'     =>['Interventions', '4'],
            ] as $f => $meta)
                <div class="it-section">
                    <div class="it-hd"><div class="it-num">{{ $meta[1] }}</div><div><div class="it-title">{{ $meta[0] }}</div></div></div>
                    <div class="it-body">
                        @if($plan->{$f})
                            <div class="narr-block"><div class="narr-content">{{ $plan->{$f} }}</div></div>
                        @else
                            <p class="text-slate-400 italic text-sm">Not documented.</p>
                        @endif
                    </div>
                </div>
            @endforeach

            <div class="it-section">
                <div class="it-hd"><div class="it-num">5</div><div><div class="it-title">Goals &amp; objectives ({{ $goalsCount }})</div></div></div>
                <div class="it-body">
                    @forelse($plan->goals as $goal)
                        <div class="goal-card">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="goal-code-badge">{{ $goal->goal_code }}</span>
                                    <span class="text-[10px] text-slate-400 font-semibold">
                                        {{ optional($goal->start_date)->format('m/d/Y') }} → {{ optional($goal->target_date)->format('m/d/Y') }}
                                    </span>
                                </div>
                                <span class="text-[9px] font-bold text-slate-400">{{ $goal->objectives->count() }} obj.</span>
                            </div>
                            @if($goal->problem_statement)
                                <div class="text-[.78rem] text-slate-500 mb-2"><span class="font-bold text-slate-600">Problem:</span> {{ $goal->problem_statement }}</div>
                            @endif
                            <div class="text-[.85rem] text-slate-700 font-medium leading-relaxed">{{ $goal->description }}</div>
                            @foreach($goal->objectives as $obj)
                                <div class="objective-card">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <span class="font-bold text-violet-600 text-[.78rem]">{{ $obj->objective_code }}</span>
                                            @if($obj->intervention_type)
                                                <span class="text-[10px] text-slate-400 font-semibold ml-2">{{ $obj->intervention_type }}</span>
                                            @endif
                                        </div>
                                        <span class="text-[10px] text-slate-400">Target: {{ optional($obj->target_date)->format('m/d/Y') }}</span>
                                    </div>
                                    <div class="text-[.82rem] text-slate-700 mt-1.5">{{ $obj->description }}</div>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <p class="text-slate-400 italic text-sm text-center py-4">No goals defined yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <div class="sig-box {{ $plan->is_signed ? '' : 'draft' }}">
                    <div>
                        <div class="font-bold text-sm">{{ $plan->signedByEmployee?->full_name ?? $plan->signedByUser?->name ?? '—' }}</div>
                    </div>
                    <div class="text-right">
                        @if($plan->is_signed)
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-bold uppercase">
                                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Signed {{ $plan->signed_at?->format('m/d/Y g:i A') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-amber-100 text-amber-700 rounded-lg text-xs font-bold uppercase">
                                <i data-lucide="clock" class="w-3.5 h-3.5"></i> Draft
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
