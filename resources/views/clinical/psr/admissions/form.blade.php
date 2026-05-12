@extends('layouts.app')
@section('title', $admission->exists ? 'Edit PSR admission' : 'New PSR admission')

@section('content')
    <a href="{{ route('clinical.psr.admissions.index') }}" class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center gap-1 mb-3">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to admissions
    </a>
    <div class="text-xs font-bold uppercase tracking-widest text-emerald-500 mb-1">PSR</div>
    <h1 class="text-2xl font-bold text-slate-900 mb-6">{{ $admission->exists ? 'Edit admission' : 'New admission' }}</h1>

    <form method="POST" action="{{ $admission->exists ? route('clinical.psr.admissions.update', $admission) : route('clinical.psr.admissions.store') }}"
          class="bg-white rounded-xl border border-slate-200 p-6 space-y-5 max-w-4xl">
        @csrf
        @if($admission->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Patient *</label>
                <select name="patient_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">—</option>
                    @foreach($patients as $p)
                        <option value="{{ $p->id }}" @selected(old('patient_id', $admission->patient_id) == $p->id)>{{ $p->full_name }}@if($p->mrn) — {{ $p->mrn }}@endif</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Clinic *</label>
                <select name="clinic_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">—</option>
                    @foreach($clinics as $c)
                        <option value="{{ $c->id }}" @selected(old('clinic_id', $admission->clinic_id) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Status *</label>
                <select name="status" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach($statuses as $k => $v)<option value="{{ $k }}" @selected(old('status', $admission->status) === $k)>{{ $v }}</option>@endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Admission date *</label>
                <input type="date" name="admission_date" required value="{{ old('admission_date', optional($admission->admission_date)->format('Y-m-d')) }}"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Discharge date</label>
                <input type="date" name="discharge_date" value="{{ old('discharge_date', optional($admission->discharge_date)->format('Y-m-d')) }}"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Referral date</label>
                <input type="date" name="referral_date" value="{{ old('referral_date', optional($admission->referral_date)->format('Y-m-d')) }}"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Assigned therapist</label>
                <select name="assigned_therapist_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">— Unassigned —</option>
                    @foreach($therapists as $t)
                        <option value="{{ $t->id }}" @selected(old('assigned_therapist_id', $admission->assigned_therapist_id) == $t->id)>{{ $t->full_name }}@if($t->position) — {{ $t->position }}@endif</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Referring provider</label>
                <select name="referring_provider_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">—</option>
                    @foreach($therapists as $t)
                        <option value="{{ $t->id }}" @selected(old('referring_provider_id', $admission->referring_provider_id) == $t->id)>{{ $t->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Default place of service</label>
                <input type="text" name="default_shift_pos" maxlength="10" value="{{ old('default_shift_pos', $admission->default_shift_pos) }}"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <div class="border-t border-slate-100 pt-5">
            <h3 class="font-semibold text-slate-900 mb-3">Diagnoses</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Primary Dx code (ICD-10)</label>
                    <input type="text" name="primary_dx_code" maxlength="20" value="{{ old('primary_dx_code', $admission->primary_dx_code) }}"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Primary Dx description</label>
                    <input type="text" name="primary_dx_description" maxlength="255" value="{{ old('primary_dx_description', $admission->primary_dx_description) }}"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Secondary Dx code</label>
                    <input type="text" name="secondary_dx_code" maxlength="20" value="{{ old('secondary_dx_code', $admission->secondary_dx_code) }}"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Secondary Dx description</label>
                    <input type="text" name="secondary_dx_description" maxlength="255" value="{{ old('secondary_dx_description', $admission->secondary_dx_description) }}"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-5">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Risk score (computed)</label>
                <input type="number" name="risk_score" min="0" max="200" value="{{ old('risk_score', $admission->risk_score) }}"
                       class="w-full md:w-32 px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
            <a href="{{ route('clinical.psr.admissions.index') }}" class="px-4 py-2 border border-slate-300 text-slate-700 text-sm font-semibold rounded-lg hover:bg-slate-50">Cancel</a>
            <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg">{{ $admission->exists ? 'Save changes' : 'Create admission' }}</button>
        </div>
    </form>
@endsection
