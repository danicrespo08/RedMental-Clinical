@extends('layouts.app')
@section('title', $note->exists ? 'TCM — Edit note' : 'TCM — New note')

@section('content')
@php
    $isEdit  = $note->exists;
    $backUrl = route('clinical.tcm.progress_notes.index');
    $formAction = $isEdit ? route('clinical.tcm.progress_notes.update', $note) : route('clinical.tcm.progress_notes.store');
    $initialAdmissionId = (string) old('tcm_admission_id', $note->tcm_admission_id);
    $initialSelected = collect(explode('; ', (string) old('goals_addressed', $note->goals_addressed)))
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
    .field-input:focus, .field-select:focus, .field-textarea:focus { outline:none; border-color:#ea580c; box-shadow:0 0 0 3px rgba(234,88,12,.08); }
    .field-textarea { min-height:90px; resize:vertical; line-height:1.55; }
    .btn { display:inline-flex; align-items:center; gap:8px; padding:10px 22px; border-radius:.65rem; font-weight:700; font-size:.85rem; cursor:pointer; transition:all .2s; text-transform:uppercase; letter-spacing:.03em; border:1px solid transparent; text-decoration:none; }
    .btn-success { background:linear-gradient(135deg,#059669,#10b981); color:#fff; box-shadow:0 4px 12px rgba(5,150,105,.25); }
    .btn-success:hover { transform:translateY(-1px); }
</style>

<div class="max-w-5xl mx-auto" x-data="{
        admissionId: @js($initialAdmissionId),
        selected: @js($initialSelected),
        goalsByAdmission: @js($goalsByAdmission),
        get goals() { return this.goalsByAdmission[this.admissionId] || []; },
        get goalsText() { return this.selected.join('; '); },
        toggle(label) { const i = this.selected.indexOf(label); i === -1 ? this.selected.push(label) : this.selected.splice(i, 1); },
        onAdmissionChange() { this.selected = []; },
    }">
    {{-- HEADER --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="{{ $backUrl }}" class="w-9 h-9 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-orange-600 transition-colors border border-slate-200 flex-shrink-0">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div class="p-2.5 bg-gradient-to-br from-orange-500 to-orange-700 text-white rounded-xl shadow-md shadow-orange-500/25 flex-shrink-0">
                <i data-lucide="notebook-pen" class="w-5 h-5"></i>
            </div>
            <div class="min-w-0">
                <div class="text-xs font-bold uppercase tracking-widest text-orange-500">TCM · Progress note</div>
                <h1 class="text-xl font-black text-slate-800 truncate">{{ $isEdit ? 'Edit note' : 'New note' }}</h1>
            </div>
        </div>
    </div>

    @include('hhrr._shared._flash')

    <form method="POST" action="{{ $formAction }}">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- 1. Heading --}}
        <div class="tcm-section">
            <div class="tcm-hd"><div class="tcm-num">1</div><div><div class="tcm-title">Note details</div></div></div>
            <div class="tcm-body grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="md:col-span-2">
                    <label class="field-label">Patient / TCM admission *</label>
                    <select name="tcm_admission_id" x-model="admissionId" @change="onAdmissionChange()" required class="field-select">
                        <option value="">— Select patient —</option>
                        @foreach($admissions as $a)
                            <option value="{{ $a->id }}" @selected(old('tcm_admission_id', $note->tcm_admission_id) == $a->id)>
                                {{ $a->patient?->full_name }} — MRN {{ $a->patient?->mrn ?? '---' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Date *</label>
                    <input type="date" name="note_date" required value="{{ old('note_date', optional($note->note_date)->format('Y-m-d') ?? $note->note_date) }}" class="field-input">
                </div>
                <div>
                    <label class="field-label">Case manager</label>
                    <select name="case_manager_id" class="field-select">
                        <option value="">—</option>
                        @foreach($caseManagers as $cm)<option value="{{ $cm->id }}" @selected(old('case_manager_id', $note->case_manager_id) == $cm->id)>{{ $cm->full_name }}</option>@endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="field-label">Note type *</label>
                    <select name="note_type" required class="field-select">
                        @foreach($noteTypes as $k => $v)<option value="{{ $k }}" @selected(old('note_type', $note->note_type) === $k)>{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="field-label">Risk level *</label>
                    <select name="risk_level" required class="field-select">
                        @foreach($riskLevels as $k => $v)<option value="{{ $k }}" @selected(old('risk_level', $note->risk_level) === $k)>{{ $v }}</option>@endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- 2. Narrative --}}
        <div class="tcm-section">
            <div class="tcm-hd"><div class="tcm-num">2</div><div><div class="tcm-title">Narrative</div><div class="tcm-sub">Summary · interventions · coordination · progress · plan</div></div></div>
            <div class="tcm-body grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="field-label">Summary</label>
                    <textarea name="summary" class="field-textarea" placeholder="What happened, situation, observations…">{{ old('summary', $note->summary) }}</textarea>
                </div>
                <div>
                    <label class="field-label">Interventions</label>
                    <textarea name="interventions" class="field-textarea" placeholder="Case-management interventions provided this period…">{{ old('interventions', $note->interventions) }}</textarea>
                </div>
                <div>
                    <label class="field-label">Coordination</label>
                    <textarea name="coordination" class="field-textarea" placeholder="Providers, agencies, family coordinated with…">{{ old('coordination', $note->coordination) }}</textarea>
                </div>
                <div>
                    <label class="field-label">Progress</label>
                    <textarea name="progress" class="field-textarea" placeholder="Progress toward goals, barriers, response…">{{ old('progress', $note->progress) }}</textarea>
                </div>
                <div>
                    <label class="field-label">Plan / next steps</label>
                    <textarea name="plan" class="field-textarea" placeholder="Next steps, referrals, follow-up…">{{ old('plan', $note->plan) }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="field-label">Risk notes</label>
                    <textarea name="risk_notes" rows="2" class="field-textarea" style="min-height:60px;" placeholder="If elevated risk, describe the safety plan / actions taken.">{{ old('risk_notes', $note->risk_notes) }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="field-label">Goals addressed <span class="text-slate-400 normal-case font-semibold">— from the service plan</span></label>
                    <div class="space-y-1.5">
                        <template x-for="g in goals" :key="g.code">
                            <label class="flex items-start gap-2.5 border border-slate-200 rounded-lg px-3 py-2 cursor-pointer hover:border-orange-300"
                                   :class="selected.includes(g.label) ? 'bg-orange-50 border-orange-300' : 'bg-white'">
                                <input type="checkbox" class="mt-0.5" style="accent-color:#ea580c;" :value="g.label" :checked="selected.includes(g.label)" @change="toggle(g.label)">
                                <span class="text-[12px] font-semibold text-slate-700" x-text="g.label"></span>
                            </label>
                        </template>
                        <p x-show="goals.length === 0" class="text-[11px] text-amber-600 font-semibold py-1">No service-plan goals available — select a patient first.</p>
                    </div>
                    <input type="hidden" name="goals_addressed" :value="goalsText">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pb-6">
            <a href="{{ $backUrl }}" class="px-4 py-2 border border-slate-300 text-slate-700 text-sm font-semibold rounded-lg hover:bg-slate-50">Cancel</a>
            <button class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5 shadow-md shadow-orange-500/25">
                <i data-lucide="save" class="w-4 h-4"></i> {{ $isEdit ? 'Save changes' : 'Save note' }}
            </button>
        </div>
    </form>

    @if($isEdit && ! $note->is_signed)
        @can('clinical.tcm.progress_notes.sign')
            <form method="POST" action="{{ route('clinical.tcm.progress_notes.sign', $note) }}" class="text-right pb-8 -mt-2">@csrf
                <button class="btn btn-success"><i data-lucide="pen-tool" class="w-4 h-4"></i> Finalize &amp; sign note</button>
            </form>
        @endcan
    @endif
</div>
@endsection
