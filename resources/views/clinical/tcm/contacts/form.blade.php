@extends('layouts.app')
@section('title', $contact->exists ? 'TCM — Edit contact' : 'TCM — New contact')

@section('content')
@php
    $isEdit     = $contact->exists;
    $standalone = ! isset($admission) || ! $admission;
    $patient    = $standalone ? null : $admission->patient;
    $backUrl    = $standalone ? route('clinical.tcm.contacts.index') : route('clinical.tcm.admissions.show', $admission);
    $formAction = $standalone
        ? route('clinical.tcm.contacts.store_any')
        : ($isEdit ? route('clinical.tcm.contacts.update', [$admission, $contact]) : route('clinical.tcm.contacts.store', $admission));

    // CPT catalog: 'unit_minutes' = minutes one billable unit represents.
    $cptCatalog = [
        'T1017' => ['label' => 'T1017 — Targeted case management, per 15 min', 'unit_minutes' => 15],
        'T1016' => ['label' => 'T1016 — Case management, per 15 min',          'unit_minutes' => 15],
        'H0006' => ['label' => 'H0006 — SA case management, per 15 min',        'unit_minutes' => 15],
        'G9012' => ['label' => 'G9012 — Other case management, per 15 min',     'unit_minutes' => 15],
    ];
    $initialAdmissionId = $standalone ? (string) old('tcm_admission_id', '') : (string) $admission->id;
    $initialSelected = collect(explode('; ', (string) old('goals_addressed', $contact->goals_addressed)))
        ->map(fn ($x) => trim($x))->filter()->values();
    $goalsByAdmission = $goalsByAdmission ?? [];
@endphp

<style>
    .tcm-section { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.02); margin-bottom:1rem; }
    .tcm-hd { padding:.75rem 1.25rem; display:flex; align-items:center; gap:.6rem; border-bottom:1px solid #e2e8f0; background:linear-gradient(180deg,#fff,#fafbff); }
    .tcm-num { width:26px; height:26px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:800; color:#fff; flex-shrink:0; background:linear-gradient(135deg,#ea580c,#f97316); }
    .tcm-title { font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#1e293b; }
    .tcm-sub { font-size:.6rem; color:#94a3b8; font-weight:600; margin-top:1px; }
    .tcm-body { padding:1.1rem 1.25rem; }
    .field-label { display:block; font-size:.65rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.3rem; }
    .field-input, .field-select, .field-textarea {
        width:100%; padding:.55rem .75rem; border:1px solid #e2e8f0; border-radius:.55rem;
        font-size:.85rem; color:#1e293b; background:#fff; transition:all .15s;
    }
    .field-input:focus, .field-select:focus, .field-textarea:focus {
        outline:none; border-color:#ea580c; box-shadow:0 0 0 3px rgba(234,88,12,.08);
    }
    .field-textarea { min-height:96px; resize:vertical; line-height:1.55; }
    .field-mono { font-family:'JetBrains Mono', ui-monospace, monospace; }

    .ctype-pill {
        padding:7px 14px; border-radius:999px;
        font-size:.7rem; font-weight:800; letter-spacing:.04em;
        text-transform:uppercase; cursor:pointer;
        border:1.5px solid #e2e8f0; background:#fff; color:#64748b;
        transition:all .15s; font-family:inherit;
        display:inline-flex; align-items:center; gap:.4rem;
    }
    .ctype-pill input { display:none; }
    .ctype-pill:has(input:checked), .ctype-pill.active {
        border-color:#ea580c; background:linear-gradient(135deg,#fff7ed,#ffedd5); color:#c2410c;
    }
</style>

<div class="max-w-5xl mx-auto" x-data="{
        contactType: @js((string) (old('contact_type', $contact->contact_type) ?? 'in_person')),
        admissionId: @js($initialAdmissionId),
        duration: @js((string) (old('duration_minutes', $contact->duration_minutes) ?? '')),
        units: @js((string) (old('units', $contact->units) ?? '1')),
        cpt: @js((string) (old('cpt_code', $contact->cpt_code) ?? '')),
        selected: @js($initialSelected),
        goalsByAdmission: @js($goalsByAdmission),
        catalog: @js($cptCatalog),
        get goals() { return this.goalsByAdmission[this.admissionId] || []; },
        get goalsText() { return this.selected.join('; '); },
        toggle(label) { const i = this.selected.indexOf(label); i === -1 ? this.selected.push(label) : this.selected.splice(i, 1); },
        onAdmissionChange() { this.selected = []; },
        recomputeUnits() { const c = this.catalog[this.cpt]; if (c && this.duration > 0) { this.units = Math.max(1, Math.round(this.duration / c.unit_minutes)); } },
    }">
    {{-- HEADER --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="{{ $backUrl }}" class="w-9 h-9 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-orange-600 transition-colors border border-slate-200 flex-shrink-0">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div class="p-2.5 bg-gradient-to-br from-orange-500 to-orange-700 text-white rounded-xl shadow-md shadow-orange-500/25 flex-shrink-0">
                <i data-lucide="phone" class="w-5 h-5"></i>
            </div>
            <div class="min-w-0">
                <div class="text-xs font-bold uppercase tracking-widest text-orange-500">TCM · Care contact</div>
                <h1 class="text-xl font-black text-slate-800 truncate">{{ $isEdit ? 'Edit contact' : 'Record contact' }}</h1>
                <p class="text-[11px] text-slate-500 font-semibold mt-0.5">{{ $standalone ? 'Select the patient below' : $patient->full_name . ' — MRN ' . ($patient->mrn ?? '---') }}</p>
            </div>
        </div>
    </div>

    @include('hhrr._shared._flash')

    <form method="POST" action="{{ $formAction }}">
        @csrf
        @if($isEdit && ! $standalone) @method('PUT') @endif

        @if($standalone)
        <div class="tcm-section">
            <div class="tcm-hd"><div class="tcm-num"><i data-lucide="user" class="w-3.5 h-3.5"></i></div><div><div class="tcm-title">Patient</div><div class="tcm-sub">Admission with a signed service plan</div></div></div>
            <div class="tcm-body">
                <label class="field-label">Patient / TCM admission *</label>
                <select name="tcm_admission_id" x-model="admissionId" @change="onAdmissionChange()" required class="field-select">
                    <option value="">— Select patient —</option>
                    @foreach($admissions as $a)
                        <option value="{{ $a->id }}" @selected(old('tcm_admission_id') == $a->id)>
                            {{ $a->patient?->full_name }} — MRN {{ $a->patient?->mrn ?? '---' }} (adm {{ optional($a->admission_date)->format('M j, Y') }})
                        </option>
                    @endforeach
                </select>
                @if($admissions->isEmpty())
                    <p class="text-[11px] text-amber-600 font-semibold mt-2">No eligible admissions — a patient needs an active TCM admission with a signed service plan first.</p>
                @endif
            </div>
        </div>
        @endif

        {{-- 1. Contact type --}}
        <div class="tcm-section">
            <div class="tcm-hd"><div class="tcm-num">1</div><div><div class="tcm-title">Contact type</div><div class="tcm-sub">How was this care touch delivered?</div></div></div>
            <div class="tcm-body flex flex-wrap gap-2">
                @php
                    $typeIcons = [
                        'in_person'  => 'user-check',
                        'phone'      => 'phone',
                        'video'      => 'video',
                        'email'      => 'mail',
                        'collateral' => 'users',
                        'home_visit' => 'home',
                    ];
                @endphp
                @foreach($types as $k => $v)
                    <label class="ctype-pill" :class="contactType === '{{ $k }}' && 'active'">
                        <input type="radio" name="contact_type" value="{{ $k }}" required x-model="contactType" @checked(old('contact_type', $contact->contact_type) === $k)>
                        <i data-lucide="{{ $typeIcons[$k] ?? 'circle' }}" class="w-3.5 h-3.5"></i> {{ $v }}
                    </label>
                @endforeach
            </div>
        </div>

        {{-- 2. When + duration --}}
        <div class="tcm-section">
            <div class="tcm-hd"><div class="tcm-num">2</div><div><div class="tcm-title">When &amp; with whom</div></div></div>
            <div class="tcm-body grid grid-cols-2 md:grid-cols-3 gap-3">
                <div class="md:col-span-2">
                    <label class="field-label">Date &amp; time *</label>
                    <input type="datetime-local" name="contact_at" required
                           value="{{ old('contact_at', optional($contact->contact_at)->format('Y-m-d\TH:i') ?? $contact->contact_at) }}"
                           class="field-input">
                </div>
                <div>
                    <label class="field-label">Duration (min)</label>
                    <input type="number" name="duration_minutes" min="0" x-model="duration" @input="recomputeUnits()"
                           class="field-input field-mono">
                </div>
                <div>
                    <label class="field-label">Case manager</label>
                    <select name="case_manager_id" class="field-select">
                        <option value="">—</option>
                        @foreach($caseManagers as $cm)<option value="{{ $cm->id }}" @selected(old('case_manager_id', $contact->case_manager_id) == $cm->id)>{{ $cm->full_name }}</option>@endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="field-label">With whom</label>
                    <input type="text" name="with_whom" maxlength="200"
                           value="{{ old('with_whom', $contact->with_whom) }}"
                           class="field-input" placeholder="Patient, family member, primary-care provider, …">
                </div>
            </div>
        </div>

        {{-- 3. Billing --}}
        <div class="tcm-section">
            <div class="tcm-hd"><div class="tcm-num">3</div><div><div class="tcm-title">Billing</div></div></div>
            <div class="tcm-body grid grid-cols-3 gap-3">
                <div>
                    <label class="field-label">CPT / service *</label>
                    <select name="cpt_code" x-model="cpt" @change="recomputeUnits()" required class="field-select field-mono">
                        <option value="">— Select service —</option>
                        @foreach($cptCatalog as $code => $info)<option value="{{ $code }}">{{ $info['label'] }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Units * <span class="text-orange-500" x-show="catalog[cpt]" x-text="catalog[cpt] ? '· ' + catalog[cpt].unit_minutes + ' min/unit' : ''"></span></label>
                    <input type="number" name="units" min="1" required x-model="units"
                           class="field-input field-mono">
                </div>
                <div>
                    <label class="field-label">Place of service *</label>
                    <input type="text" name="place_of_service" required maxlength="4"
                           value="{{ old('place_of_service', $contact->place_of_service) }}"
                           class="field-input field-mono" placeholder="12">
                </div>
            </div>
        </div>

        {{-- 4. Narrative --}}
        <div class="tcm-section">
            <div class="tcm-hd"><div class="tcm-num">4</div><div><div class="tcm-title">Narrative</div><div class="tcm-sub">Goals worked, summary of the contact, follow-up actions</div></div></div>
            <div class="tcm-body grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="field-label">Goals addressed <span class="text-slate-400 normal-case font-semibold">— from the service plan</span></label>
                    <div class="space-y-1.5">
                        <template x-for="g in goals" :key="g.code">
                            <label class="flex items-start gap-2.5 border border-slate-200 rounded-lg px-3 py-2 cursor-pointer hover:border-orange-300"
                                   :class="selected.includes(g.label) ? 'bg-orange-50 border-orange-300' : 'bg-white'">
                                <input type="checkbox" class="mt-0.5" style="accent-color:#ea580c;"
                                       :value="g.label" :checked="selected.includes(g.label)" @change="toggle(g.label)">
                                <span class="text-[12px] font-semibold text-slate-700" x-text="g.label"></span>
                            </label>
                        </template>
                        <p x-show="goals.length === 0" class="text-[11px] text-amber-600 font-semibold py-1">
                            No service-plan goals available{{ $standalone ? ' — select a patient first' : '' }}.
                        </p>
                    </div>
                    <input type="hidden" name="goals_addressed" :value="goalsText">
                </div>
                <div>
                    <label class="field-label">Summary</label>
                    <textarea name="summary" class="field-textarea" placeholder="What was discussed, what was coordinated, observations…">{{ old('summary', $contact->summary) }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="field-label">Next actions</label>
                    <textarea name="next_actions" rows="2" class="field-textarea" style="min-height:60px;" placeholder="Concrete follow-ups: calls to make, referrals to send, appointments to confirm…">{{ old('next_actions', $contact->next_actions) }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pb-6">
            <a href="{{ $backUrl }}" class="px-4 py-2 border border-slate-300 text-slate-700 text-sm font-semibold rounded-lg hover:bg-slate-50">Cancel</a>
            <button class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5 shadow-md shadow-orange-500/25">
                <i data-lucide="save" class="w-4 h-4"></i> {{ $isEdit ? 'Save changes' : 'Record contact' }}
            </button>
        </div>
    </form>
</div>
@endsection
