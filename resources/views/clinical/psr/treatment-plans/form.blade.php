@extends('layouts.app')
@section('title', 'PSR — Master Treatment Plan')

@section('content')

<style>
    .paper-doc {
        background: #fff; border: 1px solid #e2e8f0;
        padding: 40px 48px;
        font-family: 'DM Sans', 'Segoe UI', sans-serif;
        color: #1e293b;
        box-shadow: 0 8px 30px -8px rgba(0,0,0,.06);
        margin: 0 auto 20px; max-width: 1100px;
        text-transform: uppercase; border-radius: 1rem;
        position: relative;
    }
    .paper-doc::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
        background: linear-gradient(90deg, #4338ca, #7c3aed, #4338ca);
        border-radius: 1rem 1rem 0 0;
    }

    .paper-header { text-align: center; margin-bottom: 26px; padding-bottom: 18px; border-bottom: 2px solid #e2e8f0; }
    .paper-header .logo-fallback {
        width: 60px; height: 60px; border-radius: 16px;
        background: linear-gradient(135deg, #4338ca 0%, #7c3aed 100%); color: #fff;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.8rem; font-weight: 800; font-family: sans-serif; margin-bottom: 12px;
        box-shadow: 0 4px 12px rgba(67,56,202,.25);
    }
    .paper-header h1 { font-size: 1.15rem; font-weight: 800; margin: 0 0 4px; letter-spacing: .05em; color: #0f172a; }
    .paper-header p  { font-size: .8rem; margin: 2px 0; color: #64748b; text-transform: uppercase; font-weight: 600; }
    .paper-header h2 { font-size: 1.05rem; font-weight: 800; margin-top: 14px; padding-top: 12px; border-top: 1.5px solid #e2e8f0; letter-spacing: .06em; color: #0f172a; }

    .legal-block {
        border: 1px solid #e2e8f0; padding: 22px; margin-bottom: 22px;
        background: #f8fafc; border-radius: .75rem;
    }
    .paper-row { display: flex; gap: 12px; margin-bottom: 10px; align-items: baseline; flex-wrap: wrap; }
    .paper-row label { font-weight: 700; white-space: nowrap; font-size: .82rem; color: #475569; letter-spacing: .02em; }
    .paper-input {
        border: none; border-bottom: 1.5px solid #cbd5e1; background: transparent;
        font-weight: 600; font-size: .88rem; padding: 4px 6px; flex: 1; min-width: 80px;
        color: #1e293b; text-transform: uppercase;
    }
    .paper-input[readonly] { color: #334155; border-bottom-style: dashed; border-bottom-color: #e2e8f0; }

    .section-title {
        font-weight: 800; text-transform: uppercase;
        border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;
        margin: 26px 0 14px; font-size: .92rem; color: #4338ca; letter-spacing: .05em;
        display: flex; align-items: center; gap: 10px;
    }
    .section-title .num {
        width: 28px; height: 28px; border-radius: 50%;
        background: linear-gradient(135deg, #4338ca, #7c3aed); color: #fff;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .82rem; font-weight: 800;
    }

    .form-section { margin-bottom: 18px; }
    .form-section textarea {
        width: 100%; min-height: 110px; padding: 14px 18px;
        border: 1.5px solid #e2e8f0; border-radius: .75rem;
        font-size: .88rem; line-height: 1.7; resize: vertical;
        background: #f8fafc; color: #1e293b; transition: all .25s;
        text-transform: uppercase;
    }
    .form-section textarea:focus { outline: none; border-color: #4338ca; box-shadow: 0 0 0 4px rgba(67,56,202,.06); background: #fff; }
    .form-section textarea::placeholder { color: #94a3b8; font-style: italic; text-transform: none; }

    .two-column { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media (max-width: 768px) { .two-column { grid-template-columns: 1fr; } .paper-doc { padding: 22px; } }

    .paper-input.date {
        border: 1.5px solid #cbd5e1; border-radius: .5rem;
        background: #fff; padding: 6px 10px; font-weight: 600; min-width: 140px;
    }

    /* Goal / Objective cards */
    .goal-card {
        background: linear-gradient(180deg, #f5f3ff 0%, #ffffff 100%);
        border: 1.5px solid #c7d2fe; border-radius: 1rem;
        padding: 18px; margin-bottom: 16px;
        position: relative; text-transform: none;
    }
    .goal-card::before {
        content: ''; position: absolute; top: 0; left: 0; bottom: 0; width: 4px;
        background: linear-gradient(180deg, #4338ca, #7c3aed);
        border-radius: 1rem 0 0 1rem;
    }
    .goal-pill {
        display: inline-block; padding: 4px 12px; border-radius: 99px;
        background: linear-gradient(135deg, #4338ca, #7c3aed); color: #fff;
        font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
    }

    .obj-card {
        background: #fff; border: 1px solid #e2e8f0;
        border-left: 3px solid #818cf8;
        border-radius: .65rem; padding: 12px 14px; margin-bottom: 8px;
        text-transform: none;
    }
    .obj-pill {
        display: inline-block; padding: 2px 8px; border-radius: 99px;
        background: #eef2ff; color: #4338ca;
        font-size: .65rem; font-weight: 800; letter-spacing: .04em;
    }

    .input-clean {
        border: 1px solid #e2e8f0; border-radius: .5rem;
        padding: 7px 10px; font-size: .82rem; font-weight: 500;
        background: #fff; transition: all .15s; text-transform: none;
        font-family: inherit;
    }
    .input-clean:focus { outline: none; border-color: #4338ca; box-shadow: 0 0 0 3px rgba(67,56,202,.08); }

    .signature-box {
        margin-top: 30px; padding: 24px; border-top: 2px dashed #cbd5e1;
        background: linear-gradient(180deg, #fff 0%, #f8fafc 100%); border-radius: .75rem;
        text-transform: none;
    }
    .signature-box.locked { background: linear-gradient(180deg, #ecfdf5 0%, #d1fae5 100%); border-top: 2px solid #34d399; }
    .actions-bar { display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; flex-wrap: wrap; }

    .btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 22px; border-radius: .65rem; font-weight: 700; font-size: .85rem;
        cursor: pointer; transition: all .2s; text-transform: uppercase; letter-spacing: .03em;
        border: 1px solid transparent; text-decoration: none;
    }
    .btn-secondary { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
    .btn-secondary:hover { background: #e2e8f0; }
    .btn-primary { background: linear-gradient(135deg, #4338ca, #7c3aed); color: #fff; box-shadow: 0 4px 12px rgba(67,56,202,.25); }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(67,56,202,.32); }
    .btn-success { background: linear-gradient(135deg, #059669, #10b981); color: #fff; box-shadow: 0 4px 12px rgba(5,150,105,.25); }
    .btn-success:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(5,150,105,.32); }
    .btn-tiny { padding: 5px 10px; font-size: .65rem; }
    .btn-soft { background: #f5f3ff; color: #5b21b6; border-color: #ddd6fe; }
    .btn-soft:hover { background: #ede9fe; }

    .stamp {
        position: absolute; top: 60px; right: 40px;
        border: 4px solid #16a34a; color: #16a34a;
        font-weight: 800; text-transform: uppercase;
        padding: 8px 20px; font-size: 1rem;
        transform: rotate(-15deg); opacity: .55;
        font-family: sans-serif;
    }

    .checkbox-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 8px 14px; text-transform: none;
    }
    .checkbox-grid label {
        display: flex; align-items: flex-start; gap: 8px;
        font-size: .82rem; cursor: pointer; color: #475569;
        font-weight: 500; padding: 4px 6px; border-radius: .4rem;
        transition: background .15s;
    }
    .checkbox-grid label:hover { background: #f5f3ff; }
    .checkbox-grid input[type="checkbox"] { margin-top: 3px; accent-color: #4338ca; }
    .other-input {
        margin-top: 10px; width: 100%; border: 1px solid #e2e8f0;
        border-radius: .5rem; padding: 8px 12px; font-size: .82rem;
        text-transform: none; background: #fff;
    }
    .ai-btn {
        background: linear-gradient(135deg, #0ea5e9, #6366f1); color: #fff;
        border: none; border-radius: .5rem; padding: 6px 12px;
        font-size: .72rem; font-weight: 700; cursor: pointer;
        text-transform: uppercase; letter-spacing: .04em;
        display: inline-flex; align-items: center; gap: 6px;
        box-shadow: 0 2px 8px rgba(14,165,233,.25); transition: all .2s;
    }
    .ai-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(14,165,233,.35); }
    .ai-btn:disabled { opacity: .55; cursor: wait; }
</style>

@php
    use App\Models\Psr\TreatmentPlan;
    $isSigned       = (bool) ($plan->is_signed ?? false);
    $clientObj      = auth()->user()->client;
    $strengthLabels = TreatmentPlan::STRENGTHS;
    $weaknessLabels = TreatmentPlan::WEAKNESSES;
    $serviceLabels  = TreatmentPlan::SERVICES;
    $savedStrengths  = old('strengths',  $plan->strengths  ?? []) ?: [];
    $savedWeaknesses = old('weaknesses', $plan->weaknesses ?? []) ?: [];
    $savedServices   = old('services',   $plan->services   ?? []) ?: [];
@endphp

<div class="max-w-6xl mx-auto">

    <div class="flex items-center gap-4 mb-6 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <a href="{{ route('clinical.psr.admissions.show', $admission) }}" class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors border border-slate-200 flex-shrink-0">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div class="p-2.5 bg-gradient-to-br from-indigo-600 to-violet-600 text-white rounded-xl flex-shrink-0 shadow-lg shadow-indigo-500/30"><i data-lucide="list-checks" class="w-6 h-6"></i></div>
        <div class="flex-1 min-w-0">
            <h1 class="text-lg font-black text-slate-800 tracking-tight uppercase truncate">Master Treatment Plan</h1>
            <p class="text-xs text-slate-500 font-medium mt-0.5 truncate">
                {{ $admission->patient?->full_name }} — {{ $admission->patient?->mrn ?? '—' }} — {{ $admission->clinic?->name }}
            </p>
        </div>
        @if($isSigned)
            <span class="bg-emerald-100 text-emerald-700 border border-emerald-300 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider whitespace-nowrap inline-flex items-center gap-1">
                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Signed {{ optional($plan->signed_at)->format('m/d/Y') }}
            </span>
        @endif
    </div>

    <form method="POST" action="{{ $plan->exists ? route('clinical.psr.treatment_plans.update', $plan) : route('clinical.psr.treatment_plans.store') }}"
          class="paper-doc"
          data-ai-url="{{ route('clinical.psr.treatment_plans.ai_suggest_goals', $admission) }}"
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
                      'intervention_type' => $o->intervention_type, 'intervention_description' => $o->intervention_description,
                      'start_date' => optional($o->start_date)->format('Y-m-d'),
                      'target_date' => optional($o->target_date)->format('Y-m-d'),
                      'is_active' => (bool) $o->is_active,
                  ])->all(),
              ])->all()),
              async aiSuggestGoals() {
                  if (this.aiBusy) return;
                  this.aiBusy = true;
                  try {
                      const url = this.$el.dataset.aiUrl;
                      const res = await fetch(url, {
                          method: 'POST',
                          headers: {
                              'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content
                                  || this.$el.querySelector('input[name=_token]').value,
                              'Accept': 'application/json',
                          },
                      });
                      if (!res.ok) throw new Error('HTTP ' + res.status);
                      const data = await res.json();
                      if (data.error) {
                          window.RM ? RM.toast('error', data.error) : alert(data.error);
                          return;
                      }
                      const ltg = this.$el.querySelector('textarea[name=long_term_goal]');
                      const dis = this.$el.querySelector('textarea[name=discharge_criteria]');
                      if (data.long_term_goal && ltg && !ltg.value.trim()) ltg.value = data.long_term_goal;
                      if (data.discharge_criteria && dis && !dis.value.trim()) dis.value = data.discharge_criteria;
                      const today = new Date().toISOString().slice(0, 10);
                      (data.goals || []).forEach((g, gi) => {
                          const code = 'G' + (this.goals.length + 1);
                          this.goals.push({
                              id: null, goal_code: code,
                              description: g.description || '',
                              problem_statement: '',
                              start_date: this.planStart || today,
                              target_date: this.planEnd || today,
                              is_active: true,
                              objectives: (g.objectives || []).map((o, oi) => ({
                                  id: null,
                                  objective_code: code + '.' + (oi + 1),
                                  description: o.description || '',
                                  intervention_type: '',
                                  intervention_description: '',
                                  start_date: this.planStart || today,
                                  target_date: this.planEnd || today,
                                  is_active: true,
                              })),
                          });
                      });
                      const note = data._source === 'mock'
                          ? 'AI suggestion ready (offline mode — review before saving).'
                          : 'AI suggestion ready — review before saving.';
                      window.RM ? RM.toast('success', note) : null;
                      this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
                  } catch (e) {
                      window.RM ? RM.toast('error', 'AI request failed: ' + e.message) : alert(e.message);
                  } finally {
                      this.aiBusy = false;
                  }
              },
          }">
        @csrf
        @if($plan->exists) @method('PUT') @endif
        <input type="hidden" name="psr_admission_id" value="{{ $admission->id }}">

        @if($isSigned) <div class="stamp">Signed &amp; Locked</div> @endif

        <div class="paper-header">
            <div class="logo-fallback">{{ mb_substr($clientObj?->name ?? 'R', 0, 1) }}</div>
            <h1>{{ $clientObj?->name ?? 'RedMental' }}</h1>
            @if($clientObj?->address)<p>{{ $clientObj->address }}</p>@endif
            <p>
                @if($clientObj?->phone) Phone: {{ $clientObj->phone }} @endif
                @if($clientObj?->email)  | Email: {{ $clientObj->email }} @endif
            </p>
            <h2>Master Treatment Plan</h2>
        </div>

        <div class="legal-block">
            <div class="paper-row">
                <label style="width:75px;">Recipient:</label>
                <input type="text" value="{{ $admission->patient?->full_name }}" class="paper-input" readonly>
                <label style="width:40px;">Age:</label>
                <input type="text" value="{{ $admission->patient?->age ?? '—' }}" class="paper-input" style="max-width:60px;" readonly>
                <label style="width:55px;">Gender:</label>
                <input type="text" value="{{ $admission->patient?->gender ?? '—' }}" class="paper-input" style="max-width:90px;" readonly>
            </div>
            <div class="paper-row">
                <label style="width:75px;">MRN:</label>
                <input type="text" value="{{ $admission->patient?->mrn ?? '—' }}" class="paper-input" style="max-width:160px;" readonly>
                <label style="width:50px;">Clinic:</label>
                <input type="text" value="{{ $admission->clinic?->name ?? '—' }}" class="paper-input" readonly>
                <label style="width:80px;">Therapist:</label>
                <input type="text" value="{{ $admission->assignedTherapist?->full_name ?? '—' }}" class="paper-input" readonly>
            </div>
            <div class="paper-row">
                <label style="width:75px;">Plan period:</label>
                <input type="date" name="start_date" required {{ $isSigned ? 'disabled' : '' }}
                       value="{{ old('start_date', optional($plan->start_date)->format('Y-m-d')) }}"
                       class="paper-input date" style="max-width:160px;">
                <span style="color:#94a3b8;font-weight:700;">→</span>
                <input type="date" name="end_date" required {{ $isSigned ? 'disabled' : '' }}
                       value="{{ old('end_date', optional($plan->end_date)->format('Y-m-d')) }}"
                       class="paper-input date" style="max-width:160px;">
            </div>
        </div>

        <div class="form-section">
            <div class="section-title"><span class="num">1</span> Strengths, Resources, Abilities &amp; Preferences</div>
            <div class="checkbox-grid">
                @foreach($strengthLabels as $key => $label)
                    <label>
                        <input type="checkbox" name="strengths[]" value="{{ $key }}"
                               @checked(in_array($key, $savedStrengths)) {{ $isSigned ? 'disabled' : '' }}>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <input type="text" name="strengths_other" maxlength="500" {{ $isSigned ? 'disabled' : '' }}
                   value="{{ old('strengths_other', $plan->strengths_other) }}"
                   placeholder="Other strength(s)…" class="other-input">
        </div>

        <div class="form-section">
            <div class="section-title"><span class="num">2</span> Weaknesses, Barriers, Challenges &amp; Limitations</div>
            <div class="checkbox-grid">
                @foreach($weaknessLabels as $key => $label)
                    <label>
                        <input type="checkbox" name="weaknesses[]" value="{{ $key }}"
                               @checked(in_array($key, $savedWeaknesses)) {{ $isSigned ? 'disabled' : '' }}>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <input type="text" name="weaknesses_other" maxlength="500" {{ $isSigned ? 'disabled' : '' }}
                   value="{{ old('weaknesses_other', $plan->weaknesses_other) }}"
                   placeholder="Other barrier(s)…" class="other-input">
        </div>

        <div class="form-section">
            <div class="section-title"><span class="num">3</span> Services to be Provided</div>
            <div class="checkbox-grid">
                @foreach($serviceLabels as $key => $label)
                    <label>
                        <input type="checkbox" name="services[]" value="{{ $key }}"
                               @checked(in_array($key, $savedServices)) {{ $isSigned ? 'disabled' : '' }}>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="two-column">
            <div class="form-section">
                <div class="section-title"><span class="num">4</span> Long-term goal</div>
                <textarea name="long_term_goal" {{ $isSigned ? 'disabled' : '' }} placeholder="Patient's overarching long-term clinical goal (the destination — what success looks like at end of treatment)…">{{ old('long_term_goal', $plan->long_term_goal) }}</textarea>
            </div>
            <div class="form-section">
                <div class="section-title"><span class="num">5</span> Discharge criteria</div>
                <textarea name="discharge_criteria" {{ $isSigned ? 'disabled' : '' }} placeholder="What must be observed before this patient can be discharged? Include measurable outcomes (e.g. PHQ-9 below 5 for 60 days, consistent attendance, demonstrated coping skills)…">{{ old('discharge_criteria', $plan->discharge_criteria) }}</textarea>
            </div>
        </div>

        <div class="form-section">
            <div class="section-title" style="justify-content:space-between;">
                <span style="display:flex;align-items:center;gap:10px;"><span class="num">6</span> Goals &amp; objectives</span>
                @unless($isSigned)
                    <span style="display:flex;align-items:center;gap:8px;">
                        <button type="button" class="ai-btn" :disabled="aiBusy" @click="aiSuggestGoals">
                            <i data-lucide="sparkles" class="w-3 h-3"></i>
                            <span x-text="aiBusy ? 'Thinking…' : 'AI suggest goals'"></span>
                        </button>
                        <button type="button" class="btn btn-soft btn-tiny" @click="goals.push({ id: null, goal_code: 'G' + (goals.length + 1), description: '', problem_statement: '', start_date: planStart, target_date: planEnd, is_active: true, objectives: [] })">
                            <i data-lucide="plus" class="w-3 h-3"></i> Add goal
                        </button>
                    </span>
                @endunless
            </div>

            <template x-for="(g, gi) in goals" :key="gi">
                <div class="goal-card">
                    <input type="hidden" :name="`goals[${gi}][id]`" :value="g.id ?? ''">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                        <span class="goal-pill">Goal <span x-text="gi + 1"></span></span>
                        <button type="button" @click="goals.splice(gi, 1)" {{ $isSigned ? 'disabled' : '' }}
                                style="color:#dc2626;background:transparent;border:none;cursor:pointer;padding:4px;border-radius:6px;"
                                onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div style="display:grid;grid-template-columns:120px 1fr;gap:12px;margin-bottom:10px;">
                        <input type="text" :name="`goals[${gi}][goal_code]`" x-model="g.goal_code" required maxlength="20"
                               {{ $isSigned ? 'disabled' : '' }} placeholder="Code (e.g. G1)" class="input-clean" style="font-family:monospace;font-weight:700;">
                        <input type="text" :name="`goals[${gi}][description]`" x-model="g.description" required
                               {{ $isSigned ? 'disabled' : '' }} placeholder="Goal description (what should change)" class="input-clean">
                    </div>
                    <textarea :name="`goals[${gi}][problem_statement]`" x-model="g.problem_statement" rows="2"
                              {{ $isSigned ? 'disabled' : '' }} placeholder="Problem statement — why is this goal needed?"
                              class="input-clean" style="width:100%;font-family:inherit;"></textarea>
                    <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:end;margin-top:12px;">
                        <div><label style="font-size:.7rem;font-weight:700;color:#475569;text-transform:uppercase;">Start</label>
                             <input type="date" :name="`goals[${gi}][start_date]`" x-model="g.start_date" required {{ $isSigned ? 'disabled' : '' }} class="input-clean" style="width:100%;"></div>
                        <div><label style="font-size:.7rem;font-weight:700;color:#475569;text-transform:uppercase;">Target</label>
                             <input type="date" :name="`goals[${gi}][target_date]`" x-model="g.target_date" required {{ $isSigned ? 'disabled' : '' }} class="input-clean" style="width:100%;"></div>
                        <label style="display:flex;gap:6px;align-items:center;font-size:.78rem;font-weight:600;color:#475569;padding-bottom:8px;">
                            <input type="checkbox" :name="`goals[${gi}][is_active]`" x-model="g.is_active" value="1" {{ $isSigned ? 'disabled' : '' }} class="rounded"> Active
                        </label>
                    </div>

                    {{-- Objectives nested under goal --}}
                    <div style="margin-top:16px;padding-top:14px;border-top:1px dashed #c7d2fe;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                            <span class="obj-pill">Objectives (<span x-text="g.objectives.length"></span>)</span>
                            @unless($isSigned)
                                <button type="button" class="btn btn-soft btn-tiny"
                                        @click="g.objectives.push({ id: null, objective_code: g.goal_code + '.' + (g.objectives.length + 1), description: '', intervention_type: '', intervention_description: '', start_date: g.start_date, target_date: g.target_date, is_active: true })">
                                    <i data-lucide="plus" class="w-3 h-3"></i> Add objective
                                </button>
                            @endunless
                        </div>
                        <template x-for="(o, oi) in g.objectives" :key="oi">
                            <div class="obj-card">
                                <input type="hidden" :name="`goals[${gi}][objectives][${oi}][id]`" :value="o.id ?? ''">
                                <div style="display:grid;grid-template-columns:110px 1fr 28px;gap:8px;margin-bottom:8px;align-items:center;">
                                    <input type="text" :name="`goals[${gi}][objectives][${oi}][objective_code]`" x-model="o.objective_code" required {{ $isSigned ? 'disabled' : '' }} placeholder="Code" class="input-clean" style="font-family:monospace;font-weight:700;font-size:.78rem;">
                                    <input type="text" :name="`goals[${gi}][objectives][${oi}][description]`" x-model="o.description" required {{ $isSigned ? 'disabled' : '' }} placeholder="Objective description (measurable, time-bound)" class="input-clean" style="font-size:.8rem;">
                                    <button type="button" @click="g.objectives.splice(oi, 1)" {{ $isSigned ? 'disabled' : '' }}
                                            style="color:#dc2626;background:transparent;border:none;cursor:pointer;border-radius:5px;padding:2px;"
                                            onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">×</button>
                                </div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                                    <input type="text" :name="`goals[${gi}][objectives][${oi}][intervention_type]`" x-model="o.intervention_type" {{ $isSigned ? 'disabled' : '' }} placeholder="Intervention type (CBT, DBT, …)" class="input-clean" style="font-size:.78rem;">
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
                                        <input type="date" :name="`goals[${gi}][objectives][${oi}][start_date]`" x-model="o.start_date" required {{ $isSigned ? 'disabled' : '' }} class="input-clean" style="font-size:.78rem;">
                                        <input type="date" :name="`goals[${gi}][objectives][${oi}][target_date]`" x-model="o.target_date" required {{ $isSigned ? 'disabled' : '' }} class="input-clean" style="font-size:.78rem;">
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="g.objectives.length === 0">
                            <p style="font-size:.78rem;color:#94a3b8;font-style:italic;text-align:center;padding:8px 0;text-transform:none;">No objectives yet — add at least one to make this goal measurable.</p>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="goals.length === 0">
                <div style="border:2px dashed #c7d2fe;border-radius:1rem;padding:30px;text-align:center;color:#6366f1;text-transform:none;">
                    <i data-lucide="list-plus" class="w-8 h-8 mx-auto mb-2"></i>
                    <p style="font-size:.9rem;font-weight:700;">No goals yet — click "Add goal" to start building this treatment plan.</p>
                </div>
            </template>
        </div>

        <div class="signature-box {{ $isSigned ? 'locked' : '' }}">
            @if($isSigned)
                <div style="display:flex;align-items:center;gap:10px;color:#16a34a;font-weight:600;">
                    <i data-lucide="check-circle" class="w-6 h-6"></i>
                    <div>
                        <div>Signed by {{ $plan->signedByEmployee?->full_name ?? $plan->signedByUser?->name ?? 'system' }}</div>
                        <div style="font-size:.85rem;font-weight:400;color:#64748b;">{{ optional($plan->signed_at)->format('F j, Y \a\t g:i A') }}</div>
                    </div>
                </div>
            @else
                <p style="font-size:.95rem;line-height:1.5;color:#475569;margin:0 0 12px;">
                    I, <strong>{{ auth()->user()->name }}</strong>, certify that this Master Treatment Plan was developed in collaboration with the patient and accurately reflects the agreed-upon clinical interventions.
                </p>
                <div class="actions-bar">
                    <a href="{{ route('clinical.psr.admissions.show', $admission) }}" class="btn btn-secondary">
                        <i data-lucide="x" class="w-4 h-4"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save" class="w-4 h-4"></i> {{ $plan->exists ? 'Save changes' : 'Save plan' }}
                    </button>
                </div>
            @endif
        </div>
    </form>

    @if($plan->exists && ! $isSigned)
        @can('clinical.psr.treatment_plans.sign')
            <form method="POST" action="{{ route('clinical.psr.treatment_plans.sign', $plan) }}" class="max-w-6xl mx-auto mt-4 text-right">@csrf
                <button class="btn btn-success">
                    <i data-lucide="pen-tool" class="w-4 h-4"></i> Finalize &amp; sign treatment plan
                </button>
            </form>
        @endcan
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', () => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
</script>
@endsection
