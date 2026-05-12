@extends('layouts.app')
@section('title', 'PSR — Service log entry')

@section('content')
    <a href="{{ route('clinical.psr.service_log.index') }}" class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center gap-1 mb-3"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back</a>
    <h1 class="text-2xl font-bold text-slate-900 mb-6">{{ $log->exists ? 'Edit service log entry' : 'New service log entry' }}</h1>

    <form method="POST" action="{{ $log->exists ? route('clinical.psr.service_log.update', $log) : route('clinical.psr.service_log.store') }}"
          class="bg-white rounded-xl border border-slate-200 p-6 space-y-4 max-w-5xl">
        @csrf
        @if($log->exists) @method('PUT') @endif

        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Admission *</label>
                <select name="psr_admission_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    <option value="">—</option>
                    @foreach($admissions as $a)<option value="{{ $a->id }}" @selected(old('psr_admission_id', $log->psr_admission_id) == $a->id)>{{ $a->patient?->full_name }}</option>@endforeach
                </select>
            </div>
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Patient *</label>
                <select name="patient_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    @foreach($patients as $p)<option value="{{ $p->id }}" @selected(old('patient_id', $log->patient_id) == $p->id)>{{ $p->full_name }}</option>@endforeach
                </select>
            </div>
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Clinic *</label>
                <select name="clinic_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    @foreach($clinics as $c)<option value="{{ $c->id }}" @selected(old('clinic_id', $log->clinic_id) == $c->id)>{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Service date *</label><input type="date" name="service_date" required value="{{ old('service_date', optional($log->service_date)->format('Y-m-d')) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm"></div>
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Start time</label><input type="time" name="start_time" value="{{ old('start_time', $log->start_time) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm"></div>
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">End time</label><input type="time" name="end_time" value="{{ old('end_time', $log->end_time) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm"></div>
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Units *</label><input type="number" name="units" min="0" required value="{{ old('units', $log->units ?? 0) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono"></div>
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Service code *</label><input type="text" name="service_code" required value="{{ old('service_code', $log->service_code) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono"></div>
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Modifier</label><input type="text" name="modifier" value="{{ old('modifier', $log->modifier) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono"></div>
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Place of service</label><input type="text" name="place_of_service" value="{{ old('place_of_service', $log->place_of_service) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm"></div>
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Diagnosis code</label><input type="text" name="diagnosis_code" value="{{ old('diagnosis_code', $log->diagnosis_code) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono"></div>
            <div class="md:col-span-2"><label class="block text-xs font-semibold text-slate-600 mb-1">Diagnosis description</label><input type="text" name="diagnosis_description" value="{{ old('diagnosis_description', $log->diagnosis_description) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm"></div>
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Therapist *</label>
                <select name="therapist_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    @foreach($therapists as $t)<option value="{{ $t->id }}" @selected(old('therapist_id', $log->therapist_id) == $t->id)>{{ $t->full_name }}</option>@endforeach
                </select>
            </div>
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Source *</label>
                <select name="source_type" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">@foreach($sourceTypes as $k => $v)<option value="{{ $k }}" @selected(old('source_type', $log->source_type) === $k)>{{ $v }}</option>@endforeach</select>
            </div>
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Authorization</label>
                <select name="psr_authorization_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    <option value="">— None —</option>
                    @foreach($authorizations as $a)<option value="{{ $a->id }}" @selected(old('psr_authorization_id', $log->psr_authorization_id) == $a->id)>{{ $a->auth_number }} · {{ $a->admission?->patient?->full_name }}</option>@endforeach
                </select>
            </div>
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Billing status *</label>
                <select name="billing_status" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">@foreach($billingStatuses as $k => $v)<option value="{{ $k }}" @selected(old('billing_status', $log->billing_status) === $k)>{{ $v }}</option>@endforeach</select>
            </div>
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Auth #</label><input type="text" name="auth_number" value="{{ old('auth_number', $log->auth_number) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono"></div>
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Claim #</label><input type="text" name="claim_number" value="{{ old('claim_number', $log->claim_number) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono"></div>
            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Paid amount</label><input type="number" step="0.01" name="paid_amount" value="{{ old('paid_amount', $log->paid_amount) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono"></div>
            <div class="md:col-span-3 flex items-center gap-4 text-sm">
                <label class="flex items-center gap-1.5"><input type="checkbox" name="has_progress_note" value="1" @checked(old('has_progress_note', $log->has_progress_note)) class="rounded border-slate-300 text-indigo-600"> Has progress note</label>
                <label class="flex items-center gap-1.5"><input type="checkbox" name="is_retroactive" value="1" @checked(old('is_retroactive', $log->is_retroactive)) class="rounded border-slate-300 text-indigo-600"> Retroactive entry</label>
            </div>
            <div class="md:col-span-3"><label class="block text-xs font-semibold text-slate-600 mb-1">Notes</label><textarea name="notes" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">{{ old('notes', $log->notes) }}</textarea></div>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
            <a href="{{ route('clinical.psr.service_log.index') }}" class="px-4 py-2 border border-slate-300 text-slate-700 text-sm font-semibold rounded-lg hover:bg-slate-50">Cancel</a>
            <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg">{{ $log->exists ? 'Save changes' : 'Save entry' }}</button>
        </div>
    </form>
@endsection
