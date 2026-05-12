@extends('layouts.app')
@section('title', 'IT — Discharge summary')

@section('content')
@php
    $isEdit = $discharge->exists;
    $patient = $admission->patient;
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
        font-size:.85rem; color:#1e293b; background:#fff;
    }
    .field-input:focus, .field-select:focus, .field-textarea:focus { outline:none; border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.08); }
    .field-textarea { min-height:88px; resize:vertical; line-height:1.55; }
    .field-mono { font-family:'JetBrains Mono', ui-monospace, monospace; }
</style>

<div class="max-w-5xl mx-auto">
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="{{ route('clinical.it.admissions.show', $admission) }}" class="w-9 h-9 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-violet-600 transition-colors border border-slate-200 flex-shrink-0">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div class="p-2.5 bg-gradient-to-br from-violet-500 to-purple-700 text-white rounded-xl shadow-md shadow-violet-500/25 flex-shrink-0">
                <i data-lucide="log-out" class="w-5 h-5"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-xs font-bold uppercase tracking-widest text-violet-500">IT · Discharge summary</div>
                <h1 class="text-xl font-black text-slate-800 truncate">{{ $isEdit ? 'Edit discharge' : 'New discharge summary' }}</h1>
                <p class="text-[11px] text-slate-500 font-semibold mt-0.5">{{ $patient?->full_name }} — admitted {{ optional($admission->admission_date)->format('M j, Y') }}</p>
            </div>
        </div>
    </div>

    @include('hhrr._shared._flash')

    <form method="POST" action="{{ $isEdit ? route('clinical.it.discharges.update', $discharge) : route('clinical.it.discharges.store') }}">
        @csrf
        @if($isEdit) @method('PUT') @endif
        <input type="hidden" name="it_admission_id" value="{{ $admission->id }}">
        <input type="hidden" name="patient_id"      value="{{ $admission->patient_id }}">
        <input type="hidden" name="admission_date"  value="{{ optional($admission->admission_date)->format('Y-m-d') }}">

        <div class="it-section">
            <div class="it-hd"><div class="it-num">1</div><div><div class="it-title">Discharge details</div></div></div>
            <div class="it-body grid grid-cols-2 md:grid-cols-3 gap-3">
                <div>
                    <label class="field-label">Discharge date *</label>
                    <input type="date" name="discharge_date" required value="{{ old('discharge_date', optional($discharge->discharge_date)->format('Y-m-d') ?? now()->toDateString()) }}" class="field-input">
                </div>
                <div>
                    <label class="field-label">Type *</label>
                    <select name="discharge_type" required class="field-select">
                        @foreach($dischargeTypes as $k => $v)<option value="{{ $k }}" @selected(old('discharge_type', $discharge->discharge_type) === $k)>{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Reason</label>
                    <select name="discharge_reason" class="field-select">
                        <option value="">—</option>
                        @foreach($reasons as $k => $v)<option value="{{ $k }}" @selected(old('discharge_reason', $discharge->discharge_reason) === $k)>{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Prognosis</label>
                    <select name="prognosis" class="field-select">
                        <option value="">—</option>
                        @foreach($prognoses as $k => $v)<option value="{{ $k }}" @selected(old('prognosis', $discharge->prognosis) === $k)>{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Therapist</label>
                    <select name="therapist_id" class="field-select">
                        <option value="">—</option>
                        @foreach($therapists as $t)<option value="{{ $t->id }}" @selected(old('therapist_id', $discharge->therapist_id) == $t->id)>{{ $t->full_name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Status *</label>
                    <select name="status" required class="field-select">
                        @foreach($statuses as $k => $v)<option value="{{ $k }}" @selected(old('status', $discharge->status) === $k)>{{ $v }}</option>@endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="it-section">
            <div class="it-hd"><div class="it-num">2</div><div><div class="it-title">Diagnoses (ICD-10)</div></div></div>
            <div class="it-body grid grid-cols-1 md:grid-cols-2 gap-3">
                <div><label class="field-label">Primary code</label><input type="text" name="primary_dx_code" class="field-input field-mono" value="{{ old('primary_dx_code', $discharge->primary_dx_code) }}"></div>
                <div><label class="field-label">Primary description</label><input type="text" name="primary_dx_description" class="field-input" value="{{ old('primary_dx_description', $discharge->primary_dx_description) }}"></div>
                <div><label class="field-label">Code at discharge</label><input type="text" name="dx_at_discharge_code" class="field-input field-mono" value="{{ old('dx_at_discharge_code', $discharge->dx_at_discharge_code) }}"></div>
                <div><label class="field-label">Description at discharge</label><input type="text" name="dx_at_discharge_description" class="field-input" value="{{ old('dx_at_discharge_description', $discharge->dx_at_discharge_description) }}"></div>
            </div>
        </div>

        <div class="it-section">
            <div class="it-hd"><div class="it-num">3</div><div><div class="it-title">Clinical course</div><div class="it-sub">Treatment summary, response, medications, risk</div></div></div>
            <div class="it-body grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach([
                    'presenting_problems'          => 'Presenting problems',
                    'treatment_summary'            => 'Treatment summary',
                    'clinical_course'              => 'Clinical course',
                    'response_to_treatment'        => 'Response to treatment',
                    'medications_at_discharge'     => 'Medications at discharge',
                    'risk_assessment_at_discharge' => 'Risk assessment',
                ] as $f => $label)
                    <div>
                        <label class="field-label">{{ $label }}</label>
                        <textarea name="{{ $f }}" class="field-textarea">{{ old($f, $discharge->{$f}) }}</textarea>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="it-section">
            <div class="it-hd"><div class="it-num">4</div><div><div class="it-title">Aftercare plan</div></div></div>
            <div class="it-body grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="field-label">Aftercare level</label>
                    <input type="text" name="aftercare_level" class="field-input" maxlength="60" value="{{ old('aftercare_level', $discharge->aftercare_level) }}" placeholder="e.g. Outpatient — bi-weekly therapy">
                </div>
                @foreach([
                    'aftercare_plan'           => 'Aftercare plan',
                    'aftercare_referrals'      => 'Aftercare referrals',
                    'follow_up_appointments'   => 'Follow-up appointments',
                    'crisis_plan'              => 'Crisis plan',
                    'patient_instructions'     => 'Patient instructions',
                    'therapist_recommendation' => 'Therapist recommendation',
                ] as $f => $label)
                    <div class="md:col-span-2">
                        <label class="field-label">{{ $label }}</label>
                        <textarea name="{{ $f }}" class="field-textarea">{{ old($f, $discharge->{$f}) }}</textarea>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="it-section">
            <div class="it-hd"><div class="it-num">5</div><div><div class="it-title">Episode metrics</div></div></div>
            <div class="it-body grid grid-cols-2 md:grid-cols-4 gap-3">
                <div><label class="field-label">Sessions attended</label><input type="number" name="total_sessions_attended" min="0" value="{{ old('total_sessions_attended', $discharge->total_sessions_attended ?? 0) }}" class="field-input field-mono"></div>
                <div><label class="field-label">Sessions absent</label><input type="number" name="total_sessions_absent" min="0" value="{{ old('total_sessions_absent', $discharge->total_sessions_absent ?? 0) }}" class="field-input field-mono"></div>
                <div><label class="field-label">Units billed</label><input type="number" name="total_units_billed" min="0" value="{{ old('total_units_billed', $discharge->total_units_billed ?? 0) }}" class="field-input field-mono"></div>
                <div><label class="field-label">Days in program</label><input type="number" name="days_in_program" min="0" value="{{ old('days_in_program', $discharge->days_in_program ?? 0) }}" class="field-input field-mono"></div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 pt-2 pb-6">
            <p class="text-[11px] text-slate-400">
                <i data-lucide="info" class="w-3 h-3 inline-block"></i>
                Signing the discharge moves the admission to <strong>discharged</strong>.
            </p>
            <div class="flex items-center gap-2">
                <a href="{{ route('clinical.it.admissions.show', $admission) }}" class="px-4 py-2 border border-slate-300 text-slate-700 text-sm font-semibold rounded-lg hover:bg-slate-50">Cancel</a>
                <button class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5"><i data-lucide="save" class="w-4 h-4"></i> {{ $isEdit ? 'Save changes' : 'Save draft' }}</button>
            </div>
        </div>
    </form>
</div>
@endsection
