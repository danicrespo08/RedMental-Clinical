@extends('layouts.app')
@section('title', 'PSR — Treatment plan')

@section('content')
@php
    use App\Models\Psr\TreatmentPlan;
    $patient   = $plan->admission?->patient;
    $admission = $plan->admission;
    $strengthLabels  = TreatmentPlan::STRENGTHS;
    $weaknessLabels  = TreatmentPlan::WEAKNESSES;
    $serviceLabels   = TreatmentPlan::SERVICES;
    $savedStrengths  = $plan->strengths  ?? [];
    $savedWeaknesses = $plan->weaknesses ?? [];
    $savedServices   = $plan->services   ?? [];
    $goalsCount      = $plan->goals->count();
    $objectivesCount = $plan->goals->sum(fn ($g) => $g->objectives->count());
@endphp

<style>
    .detail-card    { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,.02); }
    .detail-title   { font-size:.6rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; margin-bottom:1rem; display:flex; align-items:center; gap:.4rem; }
    .info-label     { font-size:.55rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; }
    .info-value     { font-size:.82rem; font-weight:700; color:#334155; margin-top:.15rem; }

    .section-card   { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; overflow:hidden; margin-bottom:1rem; box-shadow:0 1px 3px rgba(0,0,0,.02); }
    .section-hd     { padding:.75rem 1.25rem; display:flex; align-items:center; gap:.6rem; border-bottom:1px solid #e2e8f0; }
    .section-hd .s-num { width:26px; height:26px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:800; color:#fff; flex-shrink:0; background:#7c3aed; }
    .section-hd .s-title { font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#1e293b; }
    .section-hd .s-sub   { font-size:.6rem; color:#94a3b8; font-weight:600; margin-top:1px; }
    .section-body        { padding:1rem 1.25rem; }
    .section-content     { font-size:.85rem; line-height:1.7; color:#334155; white-space:pre-wrap; }
    .section-content.empty { color:#94a3b8; font-style:italic; }

    .checkbox-display     { display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:.5rem; }
    .checkbox-item        { display:flex; align-items:center; gap:.45rem; font-size:.8rem; color:#475569; font-weight:500; }
    .checkbox-item .check { color:#10b981; }
    .checkbox-item.bad .check { color:#ef4444; }

    .goal-card           { background:#fff; border:1px solid #e2e8f0; border-radius:.75rem; padding:1rem 1.25rem; margin-bottom:.75rem; }
    .goal-code-badge     { background:linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%); color:#fff; padding:3px 12px; border-radius:20px; font-weight:700; font-size:.72rem; }
    .objective-card      { background:#faf5ff; border-left:3px solid #8b5cf6; padding:.75rem 1rem; margin:.5rem 0 .5rem 1.25rem; border-radius:0 .5rem .5rem 0; }

    .sig-box             { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:.75rem; padding:16px 20px; display:flex; justify-content:space-between; align-items:center; }
    .sig-box.draft       { background:#fffbeb; border-color:#fde68a; }
</style>

<div class="max-w-7xl mx-auto">

    {{-- HEADER --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-5 shadow-sm">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('clinical.psr.treatment_plans.index') }}" class="w-9 h-9 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 hover:text-violet-600 hover:bg-violet-50 transition-colors border border-slate-200 flex-shrink-0">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-violet-400 to-violet-600 text-white flex items-center justify-center font-black text-lg shadow-md shadow-violet-500/20">
                    {{ strtoupper(mb_substr($patient?->first_name ?? '?', 0, 1)) }}{{ strtoupper(mb_substr($patient?->last_name ?? '?', 0, 1)) }}
                </div>
                <div>
                    <h1 class="text-lg font-black text-slate-800 tracking-tight">{{ $patient?->full_name }}</h1>
                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                        <span class="font-mono font-bold text-[10px] bg-blue-50 text-blue-600 border border-blue-200 px-2 py-0.5 rounded-md">{{ $patient?->mrn ?? '---' }}</span>
                        <span class="text-slate-200">|</span>
                        <span class="text-[10px] text-slate-400 font-medium">{{ $admission?->clinic?->name ?? '---' }}</span>
                        <span class="text-slate-200">|</span>
                        <span class="text-[10px] text-slate-400 font-medium">Plan: {{ $plan->start_date->format('m/d/Y') }} — {{ $plan->end_date->format('m/d/Y') }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if($plan->is_signed)
                    <span class="px-3.5 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider border bg-emerald-50 text-emerald-700 border-emerald-200 inline-flex items-center gap-1">
                        <i data-lucide="lock" class="w-3 h-3"></i> Signed
                    </span>
                @else
                    <span class="px-3.5 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider border bg-amber-50 text-amber-700 border-amber-200 inline-flex items-center gap-1">
                        <i data-lucide="clock" class="w-3 h-3"></i> Draft
                    </span>
                    @can('clinical.psr.treatment_plans.edit')
                        <a href="{{ route('clinical.psr.treatment_plans.edit', $plan) }}" class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5 transition-colors">
                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit MTP
                        </a>
                    @endcan
                    @can('clinical.psr.treatment_plans.sign')
                        <form method="POST" action="{{ route('clinical.psr.treatment_plans.sign', $plan) }}" class="inline">@csrf
                            <button class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5 transition-colors">
                                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Sign
                            </button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

        {{-- LEFT SIDEBAR --}}
        <div class="lg:col-span-4 space-y-5">
            <div class="detail-card">
                <h3 class="detail-title"><i data-lucide="user" class="w-3 h-3 text-violet-500"></i> Patient info</h3>
                <div class="space-y-2.5">
                    <div class="flex justify-between"><span class="text-[11px] text-slate-400 font-bold">Age</span><span class="text-[11px] font-bold text-slate-700">{{ $patient?->age ?? '---' }}</span></div>
                    <div class="flex justify-between"><span class="text-[11px] text-slate-400 font-bold">Gender</span><span class="text-[11px] font-bold text-slate-700">{{ ucfirst($patient?->gender ?? '---') }}</span></div>
                    <div class="flex justify-between"><span class="text-[11px] text-slate-400 font-bold">Phone</span><span class="text-[11px] font-bold text-slate-700">{{ $patient?->phone ?? '---' }}</span></div>
                    <div class="flex justify-between"><span class="text-[11px] text-slate-400 font-bold">MRN</span><span class="text-[11px] font-bold text-slate-700 font-mono">{{ $patient?->mrn ?? '---' }}</span></div>
                </div>
            </div>

            <div class="detail-card">
                <h3 class="detail-title"><i data-lucide="heart-pulse" class="w-3 h-3 text-rose-500"></i> Diagnosis (ICD-10)</h3>
                <div class="space-y-2.5">
                    @if($admission?->primary_dx_code)
                        <div>
                            <div class="info-label">Primary</div>
                            <div class="info-value flex items-center gap-2">
                                <span class="font-mono text-[11px] bg-rose-50 text-rose-700 border border-rose-200 px-1.5 py-0.5 rounded">{{ $admission->primary_dx_code }}</span>
                            </div>
                            @if($admission->primary_dx_description)
                                <div class="text-[10px] text-slate-500 mt-0.5">{{ $admission->primary_dx_description }}</div>
                            @endif
                        </div>
                    @endif
                    @if($admission?->secondary_dx_code)
                        <div class="pt-2 border-t border-slate-100">
                            <div class="info-label">Secondary</div>
                            <div class="info-value flex items-center gap-2">
                                <span class="font-mono text-[11px] bg-slate-50 text-slate-600 border border-slate-200 px-1.5 py-0.5 rounded">{{ $admission->secondary_dx_code }}</span>
                            </div>
                            @if($admission->secondary_dx_description)
                                <div class="text-[10px] text-slate-500 mt-0.5">{{ $admission->secondary_dx_description }}</div>
                            @endif
                        </div>
                    @endif
                    @if(!$admission?->primary_dx_code && !$admission?->secondary_dx_code)
                        <div class="text-[10px] text-amber-500 font-semibold flex items-center gap-1">
                            <i data-lucide="alert-triangle" class="w-3 h-3"></i> No diagnosis assigned
                        </div>
                    @endif
                </div>
            </div>

            <div class="detail-card">
                <h3 class="detail-title"><i data-lucide="calendar-range" class="w-3 h-3 text-violet-500"></i> Plan period</h3>
                <div class="space-y-2.5">
                    <div class="flex justify-between"><span class="text-[11px] text-slate-400 font-bold">Start</span><span class="text-[11px] font-bold text-slate-700">{{ $plan->start_date->format('m/d/Y') }}</span></div>
                    <div class="flex justify-between"><span class="text-[11px] text-slate-400 font-bold">End</span><span class="text-[11px] font-bold text-slate-700">{{ $plan->end_date->format('m/d/Y') }}</span></div>
                    <div class="flex justify-between"><span class="text-[11px] text-slate-400 font-bold">Duration</span><span class="text-[11px] font-bold text-slate-700">{{ $plan->start_date->diffInDays($plan->end_date) }} days</span></div>
                    <div class="flex justify-between"><span class="text-[11px] text-slate-400 font-bold">Goals</span><span class="text-[11px] font-bold text-violet-600">{{ $goalsCount }}</span></div>
                    <div class="flex justify-between"><span class="text-[11px] text-slate-400 font-bold">Objectives</span><span class="text-[11px] font-bold text-violet-600">{{ $objectivesCount }}</span></div>
                </div>
            </div>

            <div class="detail-card">
                <h3 class="detail-title"><i data-lucide="user-check" class="w-3 h-3 text-blue-500"></i> Assigned therapist</h3>
                <div class="info-value">{{ $admission?->assignedTherapist?->full_name ?? 'Not assigned' }}</div>
            </div>
        </div>

        {{-- RIGHT: Clinical content --}}
        <div class="lg:col-span-8 space-y-4">

            {{-- 1. Strengths --}}
            <div class="section-card">
                <div class="section-hd">
                    <div class="s-num">1</div>
                    <div>
                        <div class="s-title">Strengths, Resources, Abilities &amp; Preferences</div>
                        <div class="s-sub">Client identified strengths to build treatment upon</div>
                    </div>
                </div>
                <div class="section-body">
                    @if(!empty($savedStrengths))
                        <div class="checkbox-display">
                            @foreach($strengthLabels as $key => $label)
                                @if(in_array($key, $savedStrengths))
                                    <div class="checkbox-item">
                                        <span class="check"><i data-lucide="check-square" class="w-3.5 h-3.5"></i></span>
                                        <span>{{ $label }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <p class="text-slate-400 italic text-sm">No strengths selected</p>
                    @endif
                    @if($plan->strengths_other)
                        <div class="mt-3 pt-2 border-t border-slate-100">
                            <span class="text-[10px] text-slate-400 font-bold uppercase">Other:</span>
                            <span class="text-sm text-slate-700 font-medium ml-1">{{ $plan->strengths_other }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- 2. Weaknesses --}}
            <div class="section-card">
                <div class="section-hd">
                    <div class="s-num">2</div>
                    <div>
                        <div class="s-title">Weaknesses, Barriers, Challenges &amp; Limitations</div>
                        <div class="s-sub">Areas to address through treatment interventions</div>
                    </div>
                </div>
                <div class="section-body">
                    @if(!empty($savedWeaknesses))
                        <div class="checkbox-display">
                            @foreach($weaknessLabels as $key => $label)
                                @if(in_array($key, $savedWeaknesses))
                                    <div class="checkbox-item bad">
                                        <span class="check"><i data-lucide="x-square" class="w-3.5 h-3.5"></i></span>
                                        <span>{{ $label }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <p class="text-slate-400 italic text-sm">No barriers selected</p>
                    @endif
                    @if($plan->weaknesses_other)
                        <div class="mt-3 pt-2 border-t border-slate-100">
                            <span class="text-[10px] text-slate-400 font-bold uppercase">Other:</span>
                            <span class="text-sm text-slate-700 font-medium ml-1">{{ $plan->weaknesses_other }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- 3. Services --}}
            <div class="section-card">
                <div class="section-hd">
                    <div class="s-num">3</div>
                    <div>
                        <div class="s-title">Services to be Provided</div>
                        <div class="s-sub">Approved treatment services</div>
                    </div>
                </div>
                <div class="section-body">
                    @if(!empty($savedServices))
                        <div class="space-y-2">
                            @foreach($serviceLabels as $key => $label)
                                @if(in_array($key, $savedServices))
                                    <div class="flex items-center gap-2 text-[.82rem] text-slate-700 font-medium">
                                        <span class="w-5 h-5 rounded bg-violet-50 text-violet-600 flex items-center justify-center flex-shrink-0"><i data-lucide="check" class="w-3 h-3"></i></span>
                                        {{ $label }}
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <p class="text-slate-400 italic text-sm">No services selected</p>
                    @endif
                </div>
            </div>

            {{-- 4. Discharge criteria --}}
            <div class="section-card">
                <div class="section-hd">
                    <div class="s-num">4</div>
                    <div>
                        <div class="s-title">Individualized Discharge Criteria</div>
                        <div class="s-sub">Specific criteria for discharge consideration</div>
                    </div>
                </div>
                <div class="section-body">
                    <div class="section-content {{ !$plan->discharge_criteria ? 'empty' : '' }}">{{ $plan->discharge_criteria ?: 'Not documented' }}</div>
                </div>
            </div>

            {{-- 5. Long term goal --}}
            <div class="section-card">
                <div class="section-hd">
                    <div class="s-num">5</div>
                    <div>
                        <div class="s-title">Long-term Goal</div>
                        <div class="s-sub">Patient-stated recovery goal and motivation</div>
                    </div>
                </div>
                <div class="section-body">
                    <div class="section-content {{ !$plan->long_term_goal ? 'empty' : '' }}">{{ $plan->long_term_goal ?: 'Not documented' }}</div>
                </div>
            </div>

            {{-- 6. Goals & objectives --}}
            <div class="section-card">
                <div class="section-hd">
                    <div class="s-num">6</div>
                    <div>
                        <div class="s-title">Treatment Goals &amp; Objectives ({{ $goalsCount }})</div>
                        <div class="s-sub">Measurable goals with specific intervention strategies</div>
                    </div>
                </div>
                <div class="section-body">
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
                                    @if($obj->intervention_description)
                                        <div class="text-[.78rem] text-slate-500 mt-1 italic">Intervention: {{ $obj->intervention_description }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <p class="text-slate-400 italic text-sm text-center py-4">No goals defined yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Signature --}}
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <div class="sig-box {{ $plan->is_signed ? '' : 'draft' }}">
                    <div>
                        <div class="font-bold text-sm">
                            {{ $plan->signedByEmployee?->full_name ?? $plan->signedByUser?->name ?? '—' }}
                        </div>
                        @if($plan->signedByEmployee?->credentials ?? false)
                            <div class="text-xs text-slate-500 mt-0.5">{{ $plan->signedByEmployee->credentials }}</div>
                        @endif
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

<script>
    document.addEventListener('DOMContentLoaded', () => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
</script>
@endsection
