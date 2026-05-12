@extends('layouts.app')
@section('title', 'IT — Service log entry')

@section('content')
@php $isEdit = $log->exists; @endphp

<style>
    .it-section { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.02); margin-bottom:1rem; }
    .it-hd { padding:.75rem 1.25rem; display:flex; align-items:center; gap:.6rem; border-bottom:1px solid #e2e8f0; background:linear-gradient(180deg,#fff,#fafbff); }
    .it-num { width:26px; height:26px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:800; color:#fff; flex-shrink:0; background:linear-gradient(135deg,#7c3aed,#a855f7); }
    .it-title { font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#1e293b; }
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
            <a href="{{ route('clinical.it.service_log.index') }}" class="w-9 h-9 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-violet-600 transition-colors border border-slate-200 flex-shrink-0">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div class="p-2.5 bg-gradient-to-br from-violet-500 to-purple-700 text-white rounded-xl shadow-md shadow-violet-500/25">
                <i data-lucide="list" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-xs font-bold uppercase tracking-widest text-violet-500">IT · Service log</div>
                <h1 class="text-xl font-black text-slate-800">{{ $isEdit ? 'Edit entry' : 'New entry' }}</h1>
            </div>
        </div>
    </div>

    @include('hhrr._shared._flash')

    <form method="POST" action="{{ $isEdit ? route('clinical.it.service_log.update', $log) : route('clinical.it.service_log.store') }}">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="it-section">
            <div class="it-hd"><div class="it-num">1</div><div><div class="it-title">Patient &amp; therapist</div></div></div>
            <div class="it-body grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="field-label">IT admission *</label>
                    <select name="it_admission_id" required class="field-select"
                            onchange="document.getElementById('patient_id_input').value = this.options[this.selectedIndex].dataset.patient || ''">
                        <option value="">—</option>
                        @foreach($admissions as $a)
                            <option value="{{ $a->id }}" data-patient="{{ $a->patient_id }}" @selected(old('it_admission_id', $log->it_admission_id) == $a->id)>
                                {{ $a->patient?->full_name }} — {{ optional($a->admission_date)->format('Y-m-d') }}
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="patient_id" id="patient_id_input" value="{{ old('patient_id', $log->patient_id) }}">
                </div>
                <div>
                    <label class="field-label">Therapist *</label>
                    <select name="therapist_id" required class="field-select">
                        <option value="">—</option>
                        @foreach($therapists as $t)<option value="{{ $t->id }}" @selected(old('therapist_id', $log->therapist_id) == $t->id)>{{ $t->full_name }}</option>@endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="it-section">
            <div class="it-hd"><div class="it-num">2</div><div><div class="it-title">Encounter</div></div></div>
            <div class="it-body grid grid-cols-2 md:grid-cols-3 gap-3">
                <div>
                    <label class="field-label">Service date *</label>
                    <input type="date" name="service_date" required value="{{ old('service_date', optional($log->service_date)->format('Y-m-d') ?? $log->service_date) }}" class="field-input">
                </div>
                <div>
                    <label class="field-label">Start time</label>
                    <input type="time" name="start_time" value="{{ old('start_time', $log->start_time) }}" class="field-input">
                </div>
                <div>
                    <label class="field-label">End time</label>
                    <input type="time" name="end_time" value="{{ old('end_time', $log->end_time) }}" class="field-input">
                </div>
                <div>
                    <label class="field-label">Units *</label>
                    <input type="number" name="units" min="0" required value="{{ old('units', $log->units ?? 0) }}" class="field-input field-mono">
                </div>
                <div>
                    <label class="field-label">CPT code *</label>
                    <input type="text" name="cpt_code" required maxlength="20" value="{{ old('cpt_code', $log->cpt_code) }}" class="field-input field-mono">
                </div>
                <div>
                    <label class="field-label">Modifier</label>
                    <input type="text" name="modifier" maxlength="20" value="{{ old('modifier', $log->modifier) }}" class="field-input field-mono uppercase">
                </div>
                <div>
                    <label class="field-label">Place of service</label>
                    <input type="text" name="place_of_service" maxlength="10" value="{{ old('place_of_service', $log->place_of_service) }}" class="field-input field-mono">
                </div>
                <div>
                    <label class="field-label">Diagnosis code</label>
                    <input type="text" name="diagnosis_code" maxlength="20" value="{{ old('diagnosis_code', $log->diagnosis_code) }}" class="field-input field-mono uppercase">
                </div>
                <div>
                    <label class="field-label">Diagnosis description</label>
                    <input type="text" name="diagnosis_description" maxlength="255" value="{{ old('diagnosis_description', $log->diagnosis_description) }}" class="field-input">
                </div>
            </div>
        </div>

        <div class="it-section">
            <div class="it-hd"><div class="it-num">3</div><div><div class="it-title">Authorization &amp; billing</div></div></div>
            <div class="it-body grid grid-cols-2 md:grid-cols-3 gap-3">
                <div>
                    <label class="field-label">Authorization</label>
                    <select name="it_authorization_id" class="field-select">
                        <option value="">—</option>
                        @foreach($auths as $a)<option value="{{ $a->id }}" @selected(old('it_authorization_id', $log->it_authorization_id) == $a->id)>{{ $a->auth_number }} ({{ $a->status }})</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Auth number</label>
                    <input type="text" name="auth_number" maxlength="50" value="{{ old('auth_number', $log->auth_number) }}" class="field-input field-mono">
                </div>
                <div>
                    <label class="field-label">Billing status *</label>
                    <select name="billing_status" required class="field-select">
                        @foreach($statuses as $k => $v)<option value="{{ $k }}" @selected(old('billing_status', $log->billing_status) === $k)>{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Claim #</label>
                    <input type="text" name="claim_number" maxlength="50" value="{{ old('claim_number', $log->claim_number) }}" class="field-input field-mono">
                </div>
                <div>
                    <label class="field-label">Billed date</label>
                    <input type="date" name="billed_date" value="{{ old('billed_date', optional($log->billed_date)->format('Y-m-d')) }}" class="field-input">
                </div>
                <div>
                    <label class="field-label">Paid date</label>
                    <input type="date" name="paid_date" value="{{ old('paid_date', optional($log->paid_date)->format('Y-m-d')) }}" class="field-input">
                </div>
                <div>
                    <label class="field-label">Paid amount ($)</label>
                    <input type="number" step="0.01" min="0" name="paid_amount" value="{{ old('paid_amount', $log->paid_amount) }}" class="field-input field-mono">
                </div>
                <div class="md:col-span-2">
                    <label class="field-label">Denial reason</label>
                    <input type="text" name="denial_reason" value="{{ old('denial_reason', $log->denial_reason) }}" class="field-input">
                </div>
            </div>
        </div>

        <div class="it-section">
            <div class="it-hd"><div class="it-num">4</div><div><div class="it-title">Notes &amp; flags</div></div></div>
            <div class="it-body space-y-3">
                <label class="flex items-center gap-2 text-[12px] font-semibold text-slate-700">
                    <input type="checkbox" name="has_progress_note" value="1" @checked(old('has_progress_note', $log->has_progress_note))>
                    Progress note attached
                </label>
                <textarea name="notes" class="field-textarea" placeholder="Any additional context for this billable encounter…">{{ old('notes', $log->notes) }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pb-6">
            <a href="{{ route('clinical.it.service_log.index') }}" class="px-4 py-2 border border-slate-300 text-slate-700 text-sm font-semibold rounded-lg hover:bg-slate-50">Cancel</a>
            <button class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5 shadow-md shadow-violet-500/25">
                <i data-lucide="save" class="w-4 h-4"></i> {{ $isEdit ? 'Save changes' : 'Create entry' }}
            </button>
        </div>
    </form>
</div>
@endsection
