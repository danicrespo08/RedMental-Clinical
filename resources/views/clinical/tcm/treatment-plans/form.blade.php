@extends('layouts.app')
@section('title', 'TCM — Service plan')

@section('content')
@php $isSigned = (bool) ($plan->is_signed ?? false); @endphp

<style>
    .tcm-section { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.02); margin-bottom:1rem; }
    .tcm-hd { padding:.75rem 1.25rem; display:flex; align-items:center; gap:.6rem; border-bottom:1px solid #e2e8f0; background:linear-gradient(180deg,#fff,#fafbff); }
    .tcm-num { width:26px; height:26px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:800; color:#fff; flex-shrink:0; background:linear-gradient(135deg,#ea580c,#f97316); }
    .tcm-title { font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#1e293b; }
    .tcm-sub { font-size:.6rem; color:#94a3b8; font-weight:600; margin-top:1px; }
    .tcm-body { padding:1.1rem 1.25rem; }
    .field-label { display:block; font-size:.65rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.3rem; }
    .field-input, .field-textarea {
        width:100%; padding:.55rem .75rem; border:1px solid #e2e8f0; border-radius:.55rem;
        font-size:.85rem; color:#1e293b; background:#fff; transition:all .15s;
    }
    .field-input:focus, .field-textarea:focus { outline:none; border-color:#ea580c; box-shadow:0 0 0 3px rgba(234,88,12,.08); }
    .field-textarea { min-height:96px; resize:vertical; line-height:1.55; }
    .input-clean { border:1px solid #e2e8f0; border-radius:.5rem; padding:7px 10px; font-size:.82rem; font-weight:500; background:#fff; transition:all .15s; }
    .input-clean:focus { outline:none; border-color:#ea580c; box-shadow:0 0 0 3px rgba(234,88,12,.08); }
    .goal-card { background:linear-gradient(180deg,#fff7ed 0%,#ffffff 100%); border:1.5px solid #fed7aa; border-radius:.85rem; padding:14px; margin-bottom:14px; position:relative; }
    .goal-card::before { content:''; position:absolute; top:0; left:0; bottom:0; width:4px; background:linear-gradient(180deg,#ea580c,#f97316); border-radius:.85rem 0 0 .85rem; }
    .goal-pill { display:inline-block; padding:4px 10px; border-radius:99px; background:linear-gradient(135deg,#ea580c,#f97316); color:#fff; font-size:.65rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; }
    .obj-card { background:#fff; border:1px solid #e2e8f0; border-left:3px solid #fb923c; border-radius:.55rem; padding:10px 12px; margin-bottom:6px; }
    .obj-pill { display:inline-block; padding:2px 8px; border-radius:99px; background:#fff7ed; color:#c2410c; font-size:.6rem; font-weight:800; }
    .ai-btn {
        background:linear-gradient(135deg,#0ea5e9,#6366f1); color:#fff; border:none; border-radius:.5rem;
        padding:6px 12px; font-size:.7rem; font-weight:800; cursor:pointer; text-transform:uppercase; letter-spacing:.04em;
        display:inline-flex; align-items:center; gap:6px; box-shadow:0 2px 8px rgba(14,165,233,.25);
    }
    .ai-btn:disabled { opacity:.55; cursor:wait; }
</style>

<div class="max-w-5xl mx-auto"
     x-data="{
        aiBusy: false,
        planStart: '{{ optional($plan->start_date)->format('Y-m-d') ?: now()->toDateString() }}',
        planEnd:   '{{ optional($plan->end_date)->format('Y-m-d') ?: now()->addMonths(6)->toDateString() }}',
        goals: @js($goals->map(fn ($g) => [
            'id' => $g->id, 'goal_code' => $g->goal_code, 'description' => $g->description,
            'problem_statement' => $g->problem_statement,
            'start_date' => optional($g->start_date)->format('Y-m-d'),
            'target_date' => optional($g->target_date)->format('Y-m-d'),
            'is_active' => (bool) $g->is_active,
            'objectives' => $g->objectives->map(fn ($o) => [
                'id' => $o->id, 'objective_code' => $o->objective_code, 'description' => $o->description,
                'intervention_type' => $o->intervention_type,
                'intervention_description' => $o->intervention_description,
                'start_date' => optional($o->start_date)->format('Y-m-d'),
                'target_date' => optional($o->target_date)->format('Y-m-d'),
                'is_active' => (bool) $o->is_active,
            ])->all(),
        ])->all()),
        async aiSuggestGoals() {
            if (this.aiBusy) return;
            this.aiBusy = true;
            try {
                const res = await fetch('{{ route('clinical.tcm.treatment_plans.ai_suggest_goals', $admission) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.error) { window.RM ? RM.toast('error', data.error) : alert(data.error); return; }
                const ltg = this.$root.querySelector('textarea[name=long_term_goal]');
                const dis = this.$root.querySelector('textarea[name=discharge_criteria]');
                if (data.long_term_goal && ltg && !ltg.value.trim()) ltg.value = data.long_term_goal;
                if (data.discharge_criteria && dis && !dis.value.trim()) dis.value = data.discharge_criteria;
                (data.goals || []).forEach((g, gi) => {
                    const code = 'G' + (this.goals.length + 1);
                    this.goals.push({
                        id: null, goal_code: code, description: g.description || '',
                        problem_statement: '', start_date: this.planStart, target_date: this.planEnd, is_active: true,
                        objectives: (g.objectives || []).map((o, oi) => ({
                            id: null, objective_code: code + '.' + (oi+1), description: o.description || '',
                            intervention_type: '', intervention_description: '',
                            start_date: this.planStart, target_date: this.planEnd, is_active: true,
                        })),
                    });
                });
                window.RM ? RM.toast('success', data._source === 'mock' ? 'AI suggestion ready (offline mode)' : 'AI suggestion ready') : null;
                this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
            } catch (e) { window.RM ? RM.toast('error', e.message) : alert(e.message); }
            finally { this.aiBusy = false; }
        }
     }">

    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="{{ route('clinical.tcm.admissions.show', $admission) }}" class="w-9 h-9 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-orange-600 transition-colors border border-slate-200 flex-shrink-0">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div class="p-2.5 bg-gradient-to-br from-orange-500 to-orange-700 text-white rounded-xl shadow-md shadow-orange-500/25">
                <i data-lucide="list-checks" class="w-5 h-5"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-bold uppercase tracking-widest text-orange-500">TCM · Service plan</div>
                <h1 class="text-xl font-black text-slate-800 truncate">{{ $plan->exists ? 'Edit service plan' : 'New service plan' }}</h1>
                <p class="text-[11px] text-slate-500 font-semibold mt-0.5">{{ $admission->patient?->full_name }} — MRN {{ $admission->patient?->mrn ?? '---' }}</p>
            </div>
            @if($isSigned)
                <span class="bg-emerald-100 text-emerald-700 border border-emerald-300 px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider inline-flex items-center gap-1.5">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Signed
                </span>
            @endif
        </div>
    </div>

    @include('hhrr._shared._flash')

    <form method="POST" action="{{ $plan->exists ? route('clinical.tcm.treatment_plans.update', $plan) : route('clinical.tcm.treatment_plans.store') }}">
        @csrf
        @if($plan->exists) @method('PUT') @endif
        <input type="hidden" name="tcm_admission_id" value="{{ $admission->id }}">

        <div class="tcm-section">
            <div class="tcm-hd"><div class="tcm-num">1</div><div><div class="tcm-title">Plan period</div></div></div>
            <div class="tcm-body grid grid-cols-2 gap-3">
                <div>
                    <label class="field-label">Start *</label>
                    <input type="date" name="start_date" required {{ $isSigned ? 'disabled' : '' }} value="{{ old('start_date', optional($plan->start_date)->format('Y-m-d')) }}" class="field-input" x-model="planStart">
                </div>
                <div>
                    <label class="field-label">End *</label>
                    <input type="date" name="end_date" required {{ $isSigned ? 'disabled' : '' }} value="{{ old('end_date', optional($plan->end_date)->format('Y-m-d')) }}" class="field-input" x-model="planEnd">
                </div>
            </div>
        </div>

        <div class="tcm-section">
            <div class="tcm-hd"><div class="tcm-num">2</div><div><div class="tcm-title">Care narrative</div></div></div>
            <div class="tcm-body grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="field-label">Presenting problem</label>
                    <textarea name="presenting_problem" {{ $isSigned ? 'disabled' : '' }} class="field-textarea" placeholder="Why is the patient presenting for case management?">{{ old('presenting_problem', $plan->presenting_problem) }}</textarea>
                </div>
                <div>
                    <label class="field-label">Long-term goal</label>
                    <textarea name="long_term_goal" {{ $isSigned ? 'disabled' : '' }} class="field-textarea" placeholder="Patient's overarching long-term recovery goal.">{{ old('long_term_goal', $plan->long_term_goal) }}</textarea>
                </div>
                <div>
                    <label class="field-label">Discharge criteria</label>
                    <textarea name="discharge_criteria" {{ $isSigned ? 'disabled' : '' }} class="field-textarea" placeholder="Conditions indicating readiness for case-management discharge.">{{ old('discharge_criteria', $plan->discharge_criteria) }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="field-label">Coordination strategy</label>
                    <textarea name="coordination_strategy" {{ $isSigned ? 'disabled' : '' }} class="field-textarea" placeholder="Providers involved, contact frequency, community resources, target outcomes…">{{ old('coordination_strategy', $plan->coordination_strategy) }}</textarea>
                </div>
            </div>
        </div>

        <div class="tcm-section">
            <div class="tcm-hd">
                <div class="tcm-num">3</div>
                <div class="flex-1"><div class="tcm-title">Goals &amp; objectives</div><div class="tcm-sub">Measurable, time-bound</div></div>
                @unless($isSigned)
                    <span class="flex items-center gap-2">
                        <button type="button" class="ai-btn" :disabled="aiBusy" @click="aiSuggestGoals">
                            <i data-lucide="sparkles" class="w-3 h-3"></i>
                            <span x-text="aiBusy ? 'Thinking…' : 'AI suggest goals'"></span>
                        </button>
                        <button type="button" class="px-3 py-1.5 bg-orange-50 text-orange-700 border border-orange-200 text-[10px] font-bold uppercase tracking-wider rounded-md inline-flex items-center gap-1"
                                @click="goals.push({ id:null, goal_code:'G' + (goals.length+1), description:'', problem_statement:'', start_date:planStart, target_date:planEnd, is_active:true, objectives:[] })">
                            <i data-lucide="plus" class="w-3 h-3"></i> Add goal
                        </button>
                    </span>
                @endunless
            </div>
            <div class="tcm-body">
                <template x-for="(g, gi) in goals" :key="gi">
                    <div class="goal-card">
                        <input type="hidden" :name="`goals[${gi}][id]`" :value="g.id ?? ''">
                        <div class="flex justify-between items-center mb-3">
                            <span class="goal-pill">Goal <span x-text="gi+1"></span></span>
                            <button type="button" @click="goals.splice(gi, 1)" {{ $isSigned ? 'disabled' : '' }} class="text-rose-600 hover:bg-rose-50 rounded p-1"><i data-lucide="x" class="w-4 h-4"></i></button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-[120px_1fr] gap-2 mb-2">
                            <input type="text" :name="`goals[${gi}][goal_code]`" x-model="g.goal_code" required maxlength="20" {{ $isSigned ? 'disabled' : '' }} placeholder="Code" class="input-clean font-mono font-bold">
                            <input type="text" :name="`goals[${gi}][description]`" x-model="g.description" required {{ $isSigned ? 'disabled' : '' }} placeholder="Goal description" class="input-clean">
                        </div>
                        <textarea :name="`goals[${gi}][problem_statement]`" x-model="g.problem_statement" rows="2" {{ $isSigned ? 'disabled' : '' }} placeholder="Problem statement (optional)" class="input-clean w-full"></textarea>
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <input type="date" :name="`goals[${gi}][start_date]`" x-model="g.start_date" required {{ $isSigned ? 'disabled' : '' }} class="input-clean">
                            <input type="date" :name="`goals[${gi}][target_date]`" x-model="g.target_date" required {{ $isSigned ? 'disabled' : '' }} class="input-clean">
                        </div>

                        <div class="mt-3 pt-3 border-t border-dashed border-orange-200">
                            <div class="flex justify-between items-center mb-2">
                                <span class="obj-pill">Objectives (<span x-text="g.objectives.length"></span>)</span>
                                @unless($isSigned)
                                    <button type="button" class="px-2 py-1 bg-orange-50 text-orange-700 border border-orange-200 text-[10px] font-bold uppercase rounded-md inline-flex items-center gap-1"
                                            @click="g.objectives.push({ id:null, objective_code: g.goal_code + '.' + (g.objectives.length+1), description:'', intervention_type:'', intervention_description:'', start_date: g.start_date, target_date: g.target_date, is_active:true })">
                                        <i data-lucide="plus" class="w-3 h-3"></i> Objective
                                    </button>
                                @endunless
                            </div>
                            <template x-for="(o, oi) in g.objectives" :key="oi">
                                <div class="obj-card">
                                    <input type="hidden" :name="`goals[${gi}][objectives][${oi}][id]`" :value="o.id ?? ''">
                                    <div class="grid grid-cols-[110px_1fr_28px] gap-2 mb-2 items-center">
                                        <input type="text" :name="`goals[${gi}][objectives][${oi}][objective_code]`" x-model="o.objective_code" required {{ $isSigned ? 'disabled' : '' }} placeholder="Code" class="input-clean font-mono font-bold text-[12px]">
                                        <input type="text" :name="`goals[${gi}][objectives][${oi}][description]`" x-model="o.description" required {{ $isSigned ? 'disabled' : '' }} placeholder="SMART objective" class="input-clean text-[12px]">
                                        <button type="button" @click="g.objectives.splice(oi, 1)" {{ $isSigned ? 'disabled' : '' }} class="text-rose-600 hover:bg-rose-50 rounded p-1">×</button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="text" :name="`goals[${gi}][objectives][${oi}][intervention_type]`" x-model="o.intervention_type" {{ $isSigned ? 'disabled' : '' }} placeholder="Coordination type (referral, advocacy…)" class="input-clean text-[11px]">
                                        <div class="grid grid-cols-2 gap-1">
                                            <input type="date" :name="`goals[${gi}][objectives][${oi}][start_date]`" x-model="o.start_date" required {{ $isSigned ? 'disabled' : '' }} class="input-clean text-[11px]">
                                            <input type="date" :name="`goals[${gi}][objectives][${oi}][target_date]`" x-model="o.target_date" required {{ $isSigned ? 'disabled' : '' }} class="input-clean text-[11px]">
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <template x-if="g.objectives.length === 0">
                                <p class="text-[11px] text-slate-400 italic text-center py-2">No objectives — add at least one to make this goal measurable.</p>
                            </template>
                        </div>
                    </div>
                </template>
                <template x-if="goals.length === 0">
                    <div class="border-2 border-dashed border-orange-200 rounded-xl p-6 text-center text-orange-500">
                        <i data-lucide="list-plus" class="w-8 h-8 mx-auto mb-2"></i>
                        <p class="text-sm font-bold">No goals yet — click "Add goal" or "AI suggest goals" to start.</p>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pb-6">
            <a href="{{ route('clinical.tcm.admissions.show', $admission) }}" class="px-4 py-2 border border-slate-300 text-slate-700 text-sm font-semibold rounded-lg hover:bg-slate-50">Cancel</a>
            @unless($isSigned)
                <button class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5 shadow-md shadow-orange-500/25">
                    <i data-lucide="save" class="w-4 h-4"></i> {{ $plan->exists ? 'Save changes' : 'Save plan' }}
                </button>
            @endunless
        </div>
    </form>

    @if($plan->exists && ! $isSigned)
        @can('clinical.tcm.treatment_plans.sign')
            <form method="POST" action="{{ route('clinical.tcm.treatment_plans.sign', $plan) }}" class="text-right pb-6" data-confirm="Sign this service plan?">@csrf
                <button class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5">
                    <i data-lucide="pen-tool" class="w-4 h-4"></i> Finalize &amp; sign
                </button>
            </form>
        @endcan
    @endif
</div>
@endsection
