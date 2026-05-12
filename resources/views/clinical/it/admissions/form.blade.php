@extends('layouts.app')
@section('title', $admission->exists ? 'IT — Edit admission' : 'IT — New admission')

@section('content')
@php
    $isEdit = $admission->exists;
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
            <a href="{{ route('clinical.it.admissions.index') }}" class="w-9 h-9 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-violet-600 transition-colors border border-slate-200 flex-shrink-0">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div class="p-2.5 bg-gradient-to-br from-violet-500 to-purple-700 text-white rounded-xl shadow-md shadow-violet-500/25 flex-shrink-0">
                <i data-lucide="user-round-search" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-xs font-bold uppercase tracking-widest text-violet-500">IT · Individual therapy</div>
                <h1 class="text-xl font-black text-slate-800">{{ $isEdit ? 'Edit admission' : 'New admission' }}</h1>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">Choose patient, therapist, dates, diagnosis</p>
            </div>
        </div>
    </div>

    @include('hhrr._shared._flash')

    <form method="POST" action="{{ $isEdit ? route('clinical.it.admissions.update', $admission) : route('clinical.it.admissions.store') }}">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- 1. Patient & therapist --}}
        <div class="it-section">
            <div class="it-hd"><div class="it-num">1</div><div><div class="it-title">Patient &amp; therapist</div><div class="it-sub">Who is being treated, and by whom</div></div></div>
            <div class="it-body grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="field-label">Patient *</label>
                    <select name="patient_id" required class="field-select">
                        <option value="">—</option>
                        @foreach($patients as $p)<option value="{{ $p->id }}" @selected(old('patient_id', $admission->patient_id) == $p->id)>{{ $p->full_name }}@if($p->mrn) ({{ $p->mrn }})@endif</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Therapist</label>
                    <select name="therapist_id" class="field-select">
                        <option value="">—</option>
                        @foreach($therapists as $t)<option value="{{ $t->id }}" @selected(old('therapist_id', $admission->therapist_id) == $t->id)>{{ $t->full_name }}</option>@endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- 2. Episode dates & status --}}
        <div class="it-section">
            <div class="it-hd"><div class="it-num">2</div><div><div class="it-title">Episode dates &amp; status</div></div></div>
            <div class="it-body grid grid-cols-2 md:grid-cols-3 gap-3">
                <div>
                    <label class="field-label">Admission date *</label>
                    <input type="date" name="admission_date" required
                           value="{{ old('admission_date', optional($admission->admission_date)->format('Y-m-d') ?? $admission->admission_date) }}"
                           class="field-input">
                </div>
                <div>
                    <label class="field-label">Discharge date</label>
                    <input type="date" name="discharge_date"
                           value="{{ old('discharge_date', optional($admission->discharge_date)->format('Y-m-d')) }}"
                           class="field-input">
                </div>
                <div>
                    <label class="field-label">Status *</label>
                    <select name="status" required class="field-select">
                        @foreach($statuses as $k => $v)<option value="{{ $k }}" @selected(old('status', $admission->status) === $k)>{{ $v }}</option>@endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- 3. Diagnosis & authorization --}}
        <div class="it-section">
            <div class="it-hd"><div class="it-num">3</div><div><div class="it-title">Diagnosis &amp; authorization</div></div></div>
            <div class="it-body grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="field-label">ICD-10 code</label>
                    <input type="text" name="diagnosis_code" maxlength="20"
                           value="{{ old('diagnosis_code', $admission->diagnosis_code) }}"
                           class="field-input field-mono uppercase" placeholder="F33.1">
                </div>
                <div class="md:col-span-2">
                    <label class="field-label">Description</label>
                    <input type="text" name="diagnosis_description" maxlength="200"
                           value="{{ old('diagnosis_description', $admission->diagnosis_description) }}"
                           class="field-input">
                </div>
                <div class="md:col-span-3">
                    <label class="field-label">Authorization #</label>
                    <input type="text" name="authorization_number" maxlength="50"
                           value="{{ old('authorization_number', $admission->authorization_number) }}"
                           class="field-input field-mono">
                </div>
            </div>
        </div>

        {{-- 4. Notes --}}
        <div class="it-section">
            <div class="it-hd"><div class="it-num">4</div><div><div class="it-title">Notes</div></div></div>
            <div class="it-body">
                <textarea name="notes" class="field-textarea" placeholder="Any additional context: presenting issues, comorbidities, scheduling preferences…">{{ old('notes', $admission->notes) }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pb-6">
            <a href="{{ route('clinical.it.admissions.index') }}" class="px-4 py-2 border border-slate-300 text-slate-700 text-sm font-semibold rounded-lg hover:bg-slate-50">Cancel</a>
            <button class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5 shadow-md shadow-violet-500/25">
                <i data-lucide="save" class="w-4 h-4"></i> {{ $isEdit ? 'Save changes' : 'Create admission' }}
            </button>
        </div>
    </form>
</div>
@endsection
