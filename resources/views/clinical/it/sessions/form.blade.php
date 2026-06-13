@extends('layouts.app')
@section('title', $session->exists ? 'IT — Edit session' : 'IT — New session')

@section('content')
@php
    $isEdit     = $session->exists;
    $standalone = ! isset($admission) || ! $admission;
    $patient    = $standalone ? null : $admission->patient;
    $backUrl    = $standalone ? route('clinical.it.sessions.index') : route('clinical.it.admissions.show', $admission);
    $formAction = $standalone
        ? route('clinical.it.sessions.store_any')
        : ($isEdit ? route('clinical.it.sessions.update', [$admission, $session]) : route('clinical.it.sessions.store', $admission));

    // CPT catalog: 'unit_minutes' is how many minutes ONE billable unit represents.
    // Units are derived from the actual session duration ÷ unit_minutes.
    $cptCatalog = [
        '90791' => ['label' => '90791 — Psychiatric diagnostic evaluation', 'unit_minutes' => 60],
        '90832' => ['label' => '90832 — Psychotherapy, per 30 min',         'unit_minutes' => 30],
        '90834' => ['label' => '90834 — Psychotherapy, per 45 min',         'unit_minutes' => 45],
        '90837' => ['label' => '90837 — Psychotherapy, per 60 min',         'unit_minutes' => 60],
        '90846' => ['label' => '90846 — Family therapy (without patient)',  'unit_minutes' => 50],
        '90847' => ['label' => '90847 — Family therapy (with patient)',     'unit_minutes' => 50],
        '90853' => ['label' => '90853 — Group psychotherapy',               'unit_minutes' => 60],
    ];
    // 15-minute time slots for the dropdowns (07:00 – 20:00).
    $timeSlots = [];
    for ($mins = 7 * 60; $mins <= 20 * 60; $mins += 15) {
        $timeSlots[] = sprintf('%02d:%02d', intdiv($mins, 60), $mins % 60);
    }
    $initialAdmissionId = $standalone ? (string) old('it_admission_id', '') : (string) $admission->id;
    $initialSelected = collect(explode('; ', (string) old('goals_addressed', $session->goals_addressed)))
        ->map(fn ($x) => trim($x))->filter()->values();
    $goalsByAdmission = $goalsByAdmission ?? [];
@endphp

<style>
    .it-section { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.02); margin-bottom:1rem; }
    .it-hd { padding:.75rem 1.25rem; display:flex; align-items:center; gap:.6rem; border-bottom:1px solid #e2e8f0; background:linear-gradient(180deg,#fff,#fafbff); }
    .it-num { width:26px; height:26px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:800; color:#fff; flex-shrink:0; background:linear-gradient(135deg,#7c3aed,#a855f7); }
    .it-title { font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#1e293b; }
    .it-sub { font-size:.6rem; color:#94a3b8; font-weight:600; margin-top:1px; }
    .it-body { padding:1.1rem 1.25rem; }
    .field-label { display:block; font-size:.65rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.3rem; }
    .field-input, .field-select, .field-textarea {
        width:100%; padding:.55rem .75rem; border:1px solid #e2e8f0; border-radius:.55rem;
        font-size:.85rem; color:#1e293b; background:#fff; transition:all .15s;
    }
    .field-input:focus, .field-select:focus, .field-textarea:focus {
        outline:none; border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.08);
    }
    .field-textarea { min-height:96px; resize:vertical; line-height:1.55; }
    .field-mono { font-family:'JetBrains Mono', ui-monospace, monospace; }
</style>

<div class="max-w-5xl mx-auto">
    {{-- HEADER --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="{{ $backUrl }}" class="w-9 h-9 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-violet-600 transition-colors border border-slate-200 flex-shrink-0">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div class="p-2.5 bg-gradient-to-br from-violet-500 to-purple-700 text-white rounded-xl shadow-md shadow-violet-500/25 flex-shrink-0">
                <i data-lucide="calendar-check" class="w-5 h-5"></i>
            </div>
            <div class="min-w-0">
                <div class="text-xs font-bold uppercase tracking-widest text-violet-500">IT · Therapy session</div>
                <h1 class="text-xl font-black text-slate-800 truncate">{{ $isEdit ? 'Edit session' : 'New session' }}</h1>
                <p class="text-[11px] text-slate-500 font-semibold mt-0.5">{{ $standalone ? 'Select the patient below' : $patient->full_name . ' — MRN ' . ($patient->mrn ?? '---') }}</p>
            </div>
        </div>
    </div>

    @include('hhrr._shared._flash')

    <form method="POST" action="{{ $formAction }}"
          x-data="{
              admissionId: @js($initialAdmissionId),
              start: @js((string) (old('start_time', $session->start_time) ?? '')),
              end:   @js((string) (old('end_time', $session->end_time) ?? '')),
              duration: @js((string) (old('duration_minutes', $session->duration_minutes) ?? '')),
              units: @js((string) (old('units', $session->units) ?? '1')),
              cpt: @js((string) (old('cpt_code', $session->cpt_code) ?? '')),
              selected: @js($initialSelected),
              goalsByAdmission: @js($goalsByAdmission),
              catalog: @js($cptCatalog),
              get goals() { return this.goalsByAdmission[this.admissionId] || []; },
              get goalsText() { return this.selected.join('; '); },
              toggle(label) { const i = this.selected.indexOf(label); i === -1 ? this.selected.push(label) : this.selected.splice(i, 1); },
              onAdmissionChange() { this.selected = []; },
              onCpt() { this.recomputeUnits(); },
              recomputeUnits() { const c = this.catalog[this.cpt]; if (c && this.duration > 0) { this.units = Math.max(1, Math.round(this.duration / c.unit_minutes)); } },
              recompute() { if (this.start && this.end) { const [sh, sm] = this.start.split(':').map(Number); const [eh, em] = this.end.split(':').map(Number); const mins = (eh * 60 + em) - (sh * 60 + sm); this.duration = mins > 0 ? mins : 0; this.recomputeUnits(); } },
          }">
        @csrf
        @if($isEdit && ! $standalone) @method('PUT') @endif

        @if($standalone)
        <div class="it-section">
            <div class="it-hd"><div class="it-num"><i data-lucide="user" class="w-3.5 h-3.5"></i></div><div><div class="it-title">Patient</div><div class="it-sub">Admission with a signed treatment plan</div></div></div>
            <div class="it-body">
                <label class="field-label">Patient / IT admission *</label>
                <select name="it_admission_id" x-model="admissionId" @change="onAdmissionChange()" required class="field-select">
                    <option value="">— Select patient —</option>
                    @foreach($admissions as $a)
                        <option value="{{ $a->id }}" @selected(old('it_admission_id') == $a->id)>
                            {{ $a->patient?->full_name }} — MRN {{ $a->patient?->mrn ?? '---' }} (adm {{ optional($a->admission_date)->format('M j, Y') }})
                        </option>
                    @endforeach
                </select>
                @if($admissions->isEmpty())
                    <p class="text-[11px] text-amber-600 font-semibold mt-2">No eligible admissions — a patient needs an active IT admission with a signed treatment plan first.</p>
                @endif
            </div>
        </div>
        @endif

        {{-- 1. Encounter --}}
        <div class="it-section">
            <div class="it-hd"><div class="it-num">1</div><div><div class="it-title">Encounter</div><div class="it-sub">Date / time · billing codes · therapist</div></div></div>
            <div class="it-body grid grid-cols-2 md:grid-cols-3 gap-3">
                <div>
                    <label class="field-label">Date *</label>
                    <input type="date" name="session_date" required
                           value="{{ old('session_date', optional($session->session_date)->format('Y-m-d') ?? $session->session_date) }}"
                           class="field-input">
                </div>
                <div>
                    <label class="field-label">Therapist</label>
                    <select name="therapist_id" class="field-select">
                        <option value="">—</option>
                        @foreach($therapists as $t)<option value="{{ $t->id }}" @selected(old('therapist_id', $session->therapist_id) == $t->id)>{{ $t->full_name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Start time</label>
                    <select name="start_time" x-model="start" @change="recompute()" class="field-select field-mono">
                        <option value="">—</option>
                        @foreach($timeSlots as $slot)<option value="{{ $slot }}">{{ $slot }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">End time</label>
                    <select name="end_time" x-model="end" @change="recompute()" class="field-select field-mono">
                        <option value="">—</option>
                        @foreach($timeSlots as $slot)<option value="{{ $slot }}">{{ $slot }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Duration (min) <span class="text-violet-500" x-show="start && end">· auto</span></label>
                    <input type="number" name="duration_minutes" min="0" x-model="duration" @input="recomputeUnits()"
                           class="field-input field-mono">
                </div>
                <div>
                    <label class="field-label">CPT / service *</label>
                    <select name="cpt_code" x-model="cpt" @change="onCpt()" required class="field-select field-mono">
                        <option value="">— Select service —</option>
                        @foreach($cptCatalog as $code => $info)<option value="{{ $code }}">{{ $info['label'] }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Units * <span class="text-violet-500" x-show="catalog[cpt]" x-text="catalog[cpt] ? '· ' + catalog[cpt].unit_minutes + ' min/unit' : ''"></span></label>
                    <input type="number" name="units" min="1" required x-model="units"
                           class="field-input field-mono">
                </div>
                <div>
                    <label class="field-label">Modifier</label>
                    <input type="text" name="modifier" maxlength="10"
                           value="{{ old('modifier', $session->modifier) }}"
                           class="field-input field-mono uppercase">
                </div>
                <div>
                    <label class="field-label">Place of service *</label>
                    <input type="text" name="place_of_service" required maxlength="4"
                           value="{{ old('place_of_service', $session->place_of_service) }}"
                           class="field-input field-mono" placeholder="11">
                </div>
            </div>
        </div>

        {{-- 2. SOAP note --}}
        <div class="it-section">
            <div class="it-hd"><div class="it-num">2</div><div><div class="it-title">SOAP progress note</div><div class="it-sub">Subjective · Objective · Assessment · Plan</div></div></div>
            <div class="it-body grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="field-label">S — Subjective</label>
                    <textarea name="subjective" class="field-textarea" placeholder="Patient's report of mood, symptoms, recent events…">{{ old('subjective', $session->subjective) }}</textarea>
                </div>
                <div>
                    <label class="field-label">O — Objective</label>
                    <textarea name="objective" class="field-textarea" placeholder="Mental-status observations, behavior, presentation…">{{ old('objective', $session->objective) }}</textarea>
                </div>
                <div>
                    <label class="field-label">A — Assessment</label>
                    <textarea name="assessment" class="field-textarea" placeholder="Clinician's interpretation, progress toward goals, risk…">{{ old('assessment', $session->assessment) }}</textarea>
                </div>
                <div>
                    <label class="field-label">P — Plan</label>
                    <textarea name="plan" class="field-textarea" placeholder="Next session focus, between-session homework…">{{ old('plan', $session->plan) }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="field-label">Goals addressed <span class="text-slate-400 normal-case font-semibold">— from the treatment plan</span></label>
                    <div class="space-y-1.5">
                        <template x-for="g in goals" :key="g.code">
                            <label class="flex items-start gap-2.5 border border-slate-200 rounded-lg px-3 py-2 cursor-pointer hover:border-violet-300"
                                   :class="selected.includes(g.label) ? 'bg-violet-50 border-violet-300' : 'bg-white'">
                                <input type="checkbox" class="mt-0.5" style="accent-color:#7c3aed;"
                                       :value="g.label" :checked="selected.includes(g.label)" @change="toggle(g.label)">
                                <span class="text-[12px] font-semibold text-slate-700" x-text="g.label"></span>
                            </label>
                        </template>
                        <p x-show="goals.length === 0" class="text-[11px] text-amber-600 font-semibold py-1">
                            No treatment-plan goals available{{ $standalone ? ' — select a patient first' : '' }}.
                        </p>
                    </div>
                    <input type="hidden" name="goals_addressed" :value="goalsText">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pb-6">
            <a href="{{ $backUrl }}" class="px-4 py-2 border border-slate-300 text-slate-700 text-sm font-semibold rounded-lg hover:bg-slate-50">Cancel</a>
            <button class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5 shadow-md shadow-violet-500/25">
                <i data-lucide="save" class="w-4 h-4"></i> {{ $isEdit ? 'Save changes' : 'Record session' }}
            </button>
        </div>
    </form>
</div>
@endsection
