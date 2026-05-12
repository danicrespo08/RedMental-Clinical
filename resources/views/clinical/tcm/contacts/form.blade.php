@extends('layouts.app')
@section('title', $contact->exists ? 'TCM — Edit contact' : 'TCM — New contact')

@section('content')
@php
    $isEdit = $contact->exists;
    $patient = $admission->patient;
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

<div class="max-w-5xl mx-auto" x-data="{ contactType: '{{ old('contact_type', $contact->contact_type) }}' }">
    {{-- HEADER --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="{{ route('clinical.tcm.admissions.show', $admission) }}" class="w-9 h-9 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-orange-600 transition-colors border border-slate-200 flex-shrink-0">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div class="p-2.5 bg-gradient-to-br from-orange-500 to-orange-700 text-white rounded-xl shadow-md shadow-orange-500/25 flex-shrink-0">
                <i data-lucide="phone" class="w-5 h-5"></i>
            </div>
            <div class="min-w-0">
                <div class="text-xs font-bold uppercase tracking-widest text-orange-500">TCM · Care contact</div>
                <h1 class="text-xl font-black text-slate-800 truncate">{{ $isEdit ? 'Edit contact' : 'Record contact' }}</h1>
                <p class="text-[11px] text-slate-500 font-semibold mt-0.5">{{ $patient->full_name }} — MRN {{ $patient->mrn ?? '---' }}</p>
            </div>
        </div>
    </div>

    @include('hhrr._shared._flash')

    <form method="POST" action="{{ $isEdit ? route('clinical.tcm.contacts.update', [$admission, $contact]) : route('clinical.tcm.contacts.store', $admission) }}">
        @csrf
        @if($isEdit) @method('PUT') @endif

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
                    <input type="number" name="duration_minutes" min="0"
                           value="{{ old('duration_minutes', $contact->duration_minutes) }}"
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
                    <label class="field-label">CPT *</label>
                    <input type="text" name="cpt_code" required maxlength="10"
                           value="{{ old('cpt_code', $contact->cpt_code) }}"
                           class="field-input field-mono" placeholder="T1017">
                </div>
                <div>
                    <label class="field-label">Units *</label>
                    <input type="number" name="units" min="1" required
                           value="{{ old('units', $contact->units) }}"
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
                    <label class="field-label">Goals addressed</label>
                    <textarea name="goals_addressed" class="field-textarea" placeholder="Reference service-plan goals worked during this contact.">{{ old('goals_addressed', $contact->goals_addressed) }}</textarea>
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
            <a href="{{ route('clinical.tcm.admissions.show', $admission) }}" class="px-4 py-2 border border-slate-300 text-slate-700 text-sm font-semibold rounded-lg hover:bg-slate-50">Cancel</a>
            <button class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5 shadow-md shadow-orange-500/25">
                <i data-lucide="save" class="w-4 h-4"></i> {{ $isEdit ? 'Save changes' : 'Record contact' }}
            </button>
        </div>
    </form>
</div>
@endsection
