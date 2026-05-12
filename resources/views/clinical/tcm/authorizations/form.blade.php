@extends('layouts.app')
@section('title', 'TCM — Authorization')

@section('content')
@php $isEdit = $auth->exists; @endphp

<style>
    .tcm-section { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.02); margin-bottom:1rem; }
    .tcm-hd { padding:.75rem 1.25rem; display:flex; align-items:center; gap:.6rem; border-bottom:1px solid #e2e8f0; background:linear-gradient(180deg,#fff,#fafbff); }
    .tcm-num { width:26px; height:26px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:800; color:#fff; flex-shrink:0; background:linear-gradient(135deg,#ea580c,#f97316); }
    .tcm-title { font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#1e293b; }
    .tcm-body { padding:1.1rem 1.25rem; }
    .field-label { display:block; font-size:.65rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.3rem; }
    .field-input, .field-select, .field-textarea {
        width:100%; padding:.55rem .75rem; border:1px solid #e2e8f0; border-radius:.55rem;
        font-size:.85rem; color:#1e293b; background:#fff; transition:all .15s;
    }
    .field-input:focus, .field-select:focus, .field-textarea:focus { outline:none; border-color:#ea580c; box-shadow:0 0 0 3px rgba(234,88,12,.08); }
    .field-textarea { min-height:88px; resize:vertical; line-height:1.55; }
    .field-mono { font-family:'JetBrains Mono', ui-monospace, monospace; }
</style>

<div class="max-w-5xl mx-auto">
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="{{ route('clinical.tcm.authorizations.index') }}" class="w-9 h-9 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-orange-600 transition-colors border border-slate-200 flex-shrink-0">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div class="p-2.5 bg-gradient-to-br from-orange-500 to-orange-700 text-white rounded-xl shadow-md shadow-orange-500/25">
                <i data-lucide="key-round" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-xs font-bold uppercase tracking-widest text-orange-500">TCM · Authorization</div>
                <h1 class="text-xl font-black text-slate-800">{{ $isEdit ? 'Edit authorization' : 'New authorization' }}</h1>
            </div>
        </div>
    </div>

    @include('hhrr._shared._flash')

    <form method="POST" action="{{ $isEdit ? route('clinical.tcm.authorizations.update', $auth) : route('clinical.tcm.authorizations.store') }}">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="tcm-section">
            <div class="tcm-hd"><div class="tcm-num">1</div><div><div class="tcm-title">Patient &amp; payer</div></div></div>
            <div class="tcm-body grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="field-label">TCM admission *</label>
                    <select name="tcm_admission_id" required class="field-select"
                            onchange="document.getElementById('patient_id_input').value = this.options[this.selectedIndex].dataset.patient || ''">
                        <option value="">—</option>
                        @foreach($admissions as $a)
                            <option value="{{ $a->id }}" data-patient="{{ $a->patient_id }}" @selected(old('tcm_admission_id', $auth->tcm_admission_id) == $a->id)>
                                {{ $a->patient?->full_name }} — {{ optional($a->admission_date)->format('Y-m-d') }}
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="patient_id" id="patient_id_input" value="{{ old('patient_id', $auth->patient_id) }}">
                </div>
                <div>
                    <label class="field-label">Payer</label>
                    <select name="payer_id" class="field-select">
                        <option value="">—</option>
                        @foreach($payers as $p)<option value="{{ $p->id }}" @selected(old('payer_id', $auth->payer_id) == $p->id)>{{ $p->name }}</option>@endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="tcm-section">
            <div class="tcm-hd"><div class="tcm-num">2</div><div><div class="tcm-title">Authorization details</div></div></div>
            <div class="tcm-body grid grid-cols-2 md:grid-cols-3 gap-3">
                <div>
                    <label class="field-label">Auth # *</label>
                    <input type="text" name="auth_number" required maxlength="50" value="{{ old('auth_number', $auth->auth_number) }}" class="field-input field-mono">
                </div>
                <div>
                    <label class="field-label">Type *</label>
                    <select name="auth_type" required class="field-select">
                        @foreach($types as $k => $v)<option value="{{ $k }}" @selected(old('auth_type', $auth->auth_type) === $k)>{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Status *</label>
                    <select name="status" required class="field-select">
                        @foreach($statuses as $k => $v)<option value="{{ $k }}" @selected(old('status', $auth->status) === $k)>{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">CPT codes</label>
                    @php
                        $cptValue = old('cpt_codes', $auth->cpt_codes);
                        if (is_array($cptValue)) $cptValue = implode(', ', $cptValue);
                    @endphp
                    <input type="text" name="cpt_codes" class="field-input field-mono" value="{{ $cptValue }}" placeholder="T1017, T1016, T2024">
                </div>
            </div>
        </div>

        <div class="tcm-section">
            <div class="tcm-hd"><div class="tcm-num">3</div><div><div class="tcm-title">Date ranges &amp; units</div></div></div>
            <div class="tcm-body grid grid-cols-2 md:grid-cols-3 gap-3">
                <div><label class="field-label">Requested start</label><input type="date" name="requested_start_date" class="field-input" value="{{ old('requested_start_date', optional($auth->requested_start_date)->format('Y-m-d')) }}"></div>
                <div><label class="field-label">Requested end</label><input type="date" name="requested_end_date" class="field-input" value="{{ old('requested_end_date', optional($auth->requested_end_date)->format('Y-m-d')) }}"></div>
                <div></div>
                <div><label class="field-label">Approved start</label><input type="date" name="approved_start_date" class="field-input" value="{{ old('approved_start_date', optional($auth->approved_start_date)->format('Y-m-d')) }}"></div>
                <div><label class="field-label">Approved end</label><input type="date" name="approved_end_date" class="field-input" value="{{ old('approved_end_date', optional($auth->approved_end_date)->format('Y-m-d')) }}"></div>
                <div></div>
                <div><label class="field-label">Approved units</label><input type="number" name="approved_units" min="0" required value="{{ old('approved_units', $auth->approved_units ?? 0) }}" class="field-input field-mono"></div>
                <div><label class="field-label">Used units</label><input type="number" name="used_units" min="0" required value="{{ old('used_units', $auth->used_units ?? 0) }}" class="field-input field-mono"></div>
            </div>
        </div>

        <div class="tcm-section">
            <div class="tcm-hd"><div class="tcm-num">4</div><div><div class="tcm-title">Notes</div></div></div>
            <div class="tcm-body grid grid-cols-1 gap-3">
                <div><label class="field-label">Denial reason</label><textarea name="denial_reason" class="field-textarea" placeholder="If denied, explain why and any appeal plan…">{{ old('denial_reason', $auth->denial_reason) }}</textarea></div>
                <div><label class="field-label">Notes</label><textarea name="notes" class="field-textarea" placeholder="Internal notes about this authorization…">{{ old('notes', $auth->notes) }}</textarea></div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pb-6">
            <a href="{{ route('clinical.tcm.authorizations.index') }}" class="px-4 py-2 border border-slate-300 text-slate-700 text-sm font-semibold rounded-lg hover:bg-slate-50">Cancel</a>
            <button class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5 shadow-md shadow-orange-500/25">
                <i data-lucide="save" class="w-4 h-4"></i> {{ $isEdit ? 'Save changes' : 'Create authorization' }}
            </button>
        </div>
    </form>
</div>
@endsection
