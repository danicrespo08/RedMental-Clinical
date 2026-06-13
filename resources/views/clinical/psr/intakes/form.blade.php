@extends('layouts.app')
@section('title', 'PSR — Intake Form')

@section('content')

<style>
    /* ── Paper document look — uppercase clinical form aesthetic ─────── */
    .paper-doc {
        background: #fff; border: 1px solid #e2e8f0;
        padding: 44px 52px;
        font-family: 'DM Sans', 'Segoe UI', sans-serif;
        color: #1e293b;
        box-shadow: 0 8px 30px -8px rgba(0,0,0,.06);
        position: relative;
        max-width: 940px; margin: 0 auto;
        text-transform: uppercase;
        border-radius: 1rem;
    }
    .paper-doc::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
        background: linear-gradient(90deg, #0f766e, #14b8a6, #0f766e);
        border-radius: 1rem 1rem 0 0;
    }

    .paper-header { text-align: center; margin-bottom: 26px; padding-bottom: 18px; border-bottom: 2px solid #e2e8f0; }
    .paper-header .logo-fallback {
        width: 60px; height: 60px; border-radius: 16px;
        background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%); color: #fff;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.8rem; font-weight: 800; font-family: sans-serif; margin-bottom: 12px;
        box-shadow: 0 4px 12px rgba(15,118,110,.25);
    }
    .paper-header h1 { font-size: 1.15rem; font-weight: 800; margin: 0 0 4px; letter-spacing: .05em; color: #0f172a; }
    .paper-header p  { font-size: .8rem; margin: 2px 0; color: #64748b; text-transform: uppercase; font-weight: 600; }
    .paper-header h2 { font-size: 1.05rem; font-weight: 800; margin-top: 14px; padding-top: 12px; border-top: 1.5px solid #e2e8f0; letter-spacing: .06em; color: #0f172a; }

    .legal-block {
        border: 1px solid #e2e8f0; padding: 22px; margin-bottom: 22px;
        background: #f8fafc; border-radius: .75rem;
    }
    .paper-row { display: flex; gap: 12px; margin-bottom: 10px; align-items: baseline; flex-wrap: wrap; }
    .paper-row label { font-weight: 700; white-space: nowrap; font-size: .82rem; color: #475569; letter-spacing: .02em; }
    .paper-input {
        border: none; border-bottom: 1.5px solid #cbd5e1; background: transparent;
        font-weight: 600; font-size: .88rem; padding: 4px 6px; flex: 1; min-width: 80px;
        color: #1e293b; text-transform: uppercase;
    }
    .paper-input[readonly] { color: #334155; border-bottom-style: dashed; border-bottom-color: #e2e8f0; }
    .paper-input:focus { outline: none; border-bottom-color: #0f766e; }
    .paper-input:disabled { color: #334155; background: transparent; }

    .section-title {
        font-weight: 800; text-transform: uppercase;
        border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;
        margin: 28px 0 14px; font-size: .95rem; color: #0f766e; letter-spacing: .05em;
    }
    .form-section { margin-bottom: 22px; }
    .form-section textarea {
        width: 100%; min-height: 110px; padding: 14px 18px;
        border: 1.5px solid #e2e8f0; border-radius: .75rem;
        font-size: .88rem; line-height: 1.7; resize: vertical;
        background: #f8fafc; color: #1e293b; transition: all .25s;
        text-transform: uppercase;
    }
    .form-section textarea:focus { outline: none; border-color: #0f766e; box-shadow: 0 0 0 4px rgba(15,118,110,.06); background: #fff; }
    .form-section textarea::placeholder { color: #94a3b8; font-style: italic; text-transform: none; }

    .two-column { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media (max-width: 768px) { .two-column { grid-template-columns: 1fr; } .paper-doc { padding: 22px; } }

    .consent-list { display: grid; gap: 10px; }
    .consent-item {
        display: flex; align-items: flex-start; gap: 12px;
        border: 1.5px solid #e2e8f0; border-radius: .75rem;
        padding: 12px 16px; background: #f8fafc; cursor: pointer; transition: all .2s;
    }
    .consent-item:hover { border-color: #99f6e4; }
    .consent-item input[type=checkbox] { width: 18px; height: 18px; margin-top: 1px; accent-color: #0f766e; flex-shrink: 0; }
    .consent-item .consent-text { font-size: .82rem; font-weight: 700; color: #334155; letter-spacing: .02em; }
    .consent-item .consent-sub  { font-size: .72rem; font-weight: 500; color: #94a3b8; text-transform: none; }
    .consent-item:has(input:checked) { background: #f0fdfa; border-color: #5eead4; }

    .risk-section {
        background: linear-gradient(135deg, #fef2f2 0%, #fff1f2 100%);
        border: 1.5px solid #fecaca; border-radius: 1rem; padding: 22px; margin: 24px 0;
    }
    .risk-section .section-title { color: #dc2626; border-color: #fecaca; margin-top: 0; }
    .risk-section textarea { background: #fff; border-color: #fecaca; }
    .risk-section textarea:focus { border-color: #dc2626; box-shadow: 0 0 0 4px rgba(220,38,38,.06); }
    .risk-section .consent-item { background: #fff; border-color: #fecaca; }
    .risk-section .consent-item:has(input:checked) { background: #fef2f2; border-color: #f87171; }
    .risk-section .consent-item input[type=checkbox] { accent-color: #dc2626; }

    .signature-box {
        margin-top: 30px; padding: 24px; border-top: 2px dashed #cbd5e1;
        background: linear-gradient(180deg, #fff 0%, #f8fafc 100%); border-radius: .75rem;
    }
    .signature-box.locked { background: linear-gradient(180deg, #ecfdf5 0%, #d1fae5 100%); border-top: 2px solid #34d399; }
    .actions-bar { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
    .btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 22px; border-radius: .65rem; font-weight: 700; font-size: .85rem;
        cursor: pointer; transition: all .2s; text-transform: uppercase; letter-spacing: .03em;
        border: 1px solid transparent; text-decoration: none;
    }
    .btn-secondary { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
    .btn-secondary:hover { background: #e2e8f0; }
    .btn-success {
        background: linear-gradient(135deg, #059669, #10b981); color: #fff;
        box-shadow: 0 4px 12px rgba(5,150,105,.25);
    }
    .btn-success:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(5,150,105,.32); }

    .stamp {
        position: absolute; top: 60px; right: 40px;
        border: 4px solid #16a34a; color: #16a34a;
        font-weight: 800; text-transform: uppercase;
        padding: 8px 20px; font-size: 1rem;
        transform: rotate(-15deg); opacity: .55;
        font-family: sans-serif;
    }
</style>

@php
    $isSigned  = $intake->is_signed;
    $clientObj = auth()->user()->client;
    $consents = [
        ['name' => 'consent_treatment',   'label' => 'Consent for treatment',
         'sub' => 'The patient consents to receive psychosocial rehabilitation services.'],
        ['name' => 'consent_release_info','label' => 'Consent to release information',
         'sub' => 'Authorization to share clinical information with payers and care team.'],
        ['name' => 'receipt_hipaa',       'label' => 'Receipt of HIPAA privacy notice',
         'sub' => 'The patient received the Notice of Privacy Practices.'],
        ['name' => 'receipt_rights',      'label' => 'Receipt of patient rights',
         'sub' => 'The patient received and understands the statement of rights.'],
        ['name' => 'consent_telehealth',  'label' => 'Telehealth consent',
         'sub' => 'Consent to receive services via telehealth when applicable.'],
        ['name' => 'emergency_plan_ack',  'label' => 'Emergency plan acknowledgement',
         'sub' => 'Emergency and after-hours procedures were explained.'],
    ];
@endphp

<div class="max-w-5xl mx-auto">

    <div class="flex items-center gap-4 mb-6 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <a href="{{ route('clinical.psr.admissions.show', $admission) }}" class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors border border-slate-200 flex-shrink-0">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div class="p-2.5 bg-gradient-to-br from-teal-500 to-emerald-600 text-white rounded-xl flex-shrink-0 shadow-lg shadow-teal-500/25"><i data-lucide="clipboard-list" class="w-6 h-6"></i></div>
        <div class="flex-1 min-w-0">
            <h1 class="text-lg font-black text-slate-800 tracking-tight uppercase truncate">Intake Form</h1>
            <p class="text-xs text-slate-500 font-medium mt-0.5 truncate">
                {{ $admission->patient?->full_name }} — {{ $admission->patient?->mrn ?? '—' }} — {{ $admission->clinic?->name }}
            </p>
        </div>
        @if($isSigned)
            <span class="bg-emerald-100 text-emerald-700 border border-emerald-300 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider whitespace-nowrap inline-flex items-center gap-1">
                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Signed {{ optional($intake->signed_at)->format('m/d/Y') }}
            </span>
        @endif
    </div>

    <form action="{{ $intake->exists ? route('clinical.psr.intakes.update', $intake) : route('clinical.psr.intakes.store') }}"
          method="POST" class="paper-doc">
        @csrf
        @if($intake->exists) @method('PUT') @endif
        <input type="hidden" name="psr_admission_id" value="{{ $admission->id }}">

        @if($isSigned) <div class="stamp">Signed &amp; Locked</div> @endif

        <div class="paper-header">
            <div class="logo-fallback">{{ mb_substr($clientObj?->name ?? 'R', 0, 1) }}</div>
            <h1>{{ $clientObj?->name ?? 'RedMental' }}</h1>
            @if($clientObj?->address)<p>{{ $clientObj->address }}</p>@endif
            <p>
                @if($clientObj?->phone) Phone: {{ $clientObj->phone }} @endif
                @if($clientObj?->email)  | Email: {{ $clientObj->email }} @endif
            </p>
            <h2>Psychosocial Rehabilitation — Intake Form</h2>
        </div>

        <div class="legal-block">
            <div class="paper-row">
                <label style="width:75px;">Recipient:</label>
                <input type="text" value="{{ $admission->patient?->full_name }}" class="paper-input" readonly>
                <label style="width:40px;">DOB:</label>
                <input type="text" value="{{ optional($admission->patient?->date_of_birth)->format('m/d/Y') ?? '—' }}" class="paper-input" style="max-width:140px;" readonly>
            </div>
            <div class="paper-row">
                <label style="width:75px;">MRN:</label>
                <input type="text" value="{{ $admission->patient?->mrn ?? '—' }}" class="paper-input" style="max-width:160px;" readonly>
                <label style="width:75px;">Adm. date:</label>
                <input type="text" value="{{ optional($admission->admission_date)->format('m/d/Y') ?? '—' }}" class="paper-input" style="max-width:140px;" readonly>
                <label style="width:50px;">Clinic:</label>
                <input type="text" value="{{ $admission->clinic?->name ?? '—' }}" class="paper-input" readonly>
            </div>
        </div>

        <div class="form-section">
            <div class="section-title">1. Demographics</div>
            <div class="paper-row">
                <label style="width:75px;">Race:</label>
                <input type="text" name="race" value="{{ old('race', $intake->race) }}" class="paper-input" maxlength="60" {{ $isSigned ? 'disabled' : '' }}>
                <label style="width:75px;">Ethnicity:</label>
                <input type="text" name="ethnicity" value="{{ old('ethnicity', $intake->ethnicity) }}" class="paper-input" maxlength="60" {{ $isSigned ? 'disabled' : '' }}>
            </div>
            <div class="paper-row">
                <label style="width:140px;">Preferred language:</label>
                <input type="text" name="preferred_language" value="{{ old('preferred_language', $intake->preferred_language ?? 'English') }}" class="paper-input" style="max-width:220px;" maxlength="60" {{ $isSigned ? 'disabled' : '' }}>
            </div>
            <label class="consent-item" style="margin-top:8px;">
                <input type="checkbox" name="interpreter_needed" value="1" {{ old('interpreter_needed', $intake->interpreter_needed) ? 'checked' : '' }} {{ $isSigned ? 'disabled' : '' }}>
                <span>
                    <span class="consent-text">Interpreter needed</span><br>
                    <span class="consent-sub">The patient requires a language interpreter during services.</span>
                </span>
            </label>
        </div>

        <div class="form-section">
            <div class="section-title">2. Legal guardian (if applicable)</div>
            <div class="paper-row">
                <label style="width:75px;">Name:</label>
                <input type="text" name="legal_guardian_name" value="{{ old('legal_guardian_name', $intake->legal_guardian_name) }}" class="paper-input" {{ $isSigned ? 'disabled' : '' }}>
                <label style="width:95px;">Relationship:</label>
                <input type="text" name="legal_guardian_relationship" value="{{ old('legal_guardian_relationship', $intake->legal_guardian_relationship) }}" class="paper-input" style="max-width:180px;" maxlength="60" {{ $isSigned ? 'disabled' : '' }}>
                <label style="width:55px;">Phone:</label>
                <input type="text" name="legal_guardian_phone" value="{{ old('legal_guardian_phone', $intake->legal_guardian_phone) }}" class="paper-input" style="max-width:160px;" {{ $isSigned ? 'disabled' : '' }}>
            </div>
        </div>

        <div class="form-section">
            <div class="section-title">3. Consents &amp; acknowledgements</div>
            <div class="consent-list">
                @foreach($consents as $c)
                    <label class="consent-item">
                        <input type="checkbox" name="{{ $c['name'] }}" value="1" {{ old($c['name'], $intake->{$c['name']}) ? 'checked' : '' }} {{ $isSigned ? 'disabled' : '' }}>
                        <span>
                            <span class="consent-text">{{ $c['label'] }}</span><br>
                            <span class="consent-sub">{{ $c['sub'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="form-section">
            <div class="section-title">4. Medical history</div>
            <textarea name="medical_history_checklist" {{ $isSigned ? 'disabled' : '' }} placeholder="Relevant medical conditions: diabetes, hypertension, seizures, head injuries, chronic pain, etc…">{{ old('medical_history_checklist', $intake->medical_history_checklist) }}</textarea>
        </div>

        <div class="two-column">
            <div class="form-section">
                <div class="section-title">5. Allergies</div>
                <textarea name="allergies" {{ $isSigned ? 'disabled' : '' }} placeholder="Medication, food and environmental allergies. Write NKDA if none…">{{ old('allergies', $intake->allergies) }}</textarea>
            </div>
            <div class="form-section">
                <div class="section-title">6. Current medications</div>
                <textarea name="current_medications" {{ $isSigned ? 'disabled' : '' }} placeholder="List all current medications including dosage and frequency…">{{ old('current_medications', $intake->current_medications) }}</textarea>
            </div>
        </div>

        <div class="form-section">
            <div class="section-title">7. Care team</div>
            <div class="paper-row">
                <label style="width:140px;">Primary care physician:</label>
                <input type="text" name="pcp_name" value="{{ old('pcp_name', $intake->pcp_name) }}" class="paper-input" {{ $isSigned ? 'disabled' : '' }}>
                <label style="width:55px;">Phone:</label>
                <input type="text" name="pcp_phone" value="{{ old('pcp_phone', $intake->pcp_phone) }}" class="paper-input" style="max-width:160px;" {{ $isSigned ? 'disabled' : '' }}>
            </div>
            <div class="paper-row">
                <label style="width:140px;">Psychiatrist:</label>
                <input type="text" name="psychiatrist_name" value="{{ old('psychiatrist_name', $intake->psychiatrist_name) }}" class="paper-input" {{ $isSigned ? 'disabled' : '' }}>
                <label style="width:55px;">Phone:</label>
                <input type="text" name="psychiatrist_phone" value="{{ old('psychiatrist_phone', $intake->psychiatrist_phone) }}" class="paper-input" style="max-width:160px;" {{ $isSigned ? 'disabled' : '' }}>
            </div>
        </div>

        <div class="risk-section">
            <div class="section-title">8. Safety plan</div>
            <label class="consent-item" style="margin-bottom:12px;">
                <input type="checkbox" name="safety_contract_agreed" value="1" {{ old('safety_contract_agreed', $intake->safety_contract_agreed) ? 'checked' : '' }} {{ $isSigned ? 'disabled' : '' }}>
                <span>
                    <span class="consent-text">Safety contract agreed</span><br>
                    <span class="consent-sub">The patient agrees to the safety contract and crisis procedures.</span>
                </span>
            </label>
            <textarea name="safety_plan_details" {{ $isSigned ? 'disabled' : '' }} placeholder="Warning signs, coping strategies, support contacts, crisis lines and steps to follow in an emergency…">{{ old('safety_plan_details', $intake->safety_plan_details) }}</textarea>
        </div>

        <div class="form-section">
            <div class="section-title">9. Staff comments</div>
            <textarea name="staff_comments" {{ $isSigned ? 'disabled' : '' }} placeholder="Observations during the intake interview, pending documents, follow-up items…">{{ old('staff_comments', $intake->staff_comments) }}</textarea>
        </div>

        <div class="signature-box {{ $isSigned ? 'locked' : '' }}">
            @if(! $isSigned)
                <p style="font-family:sans-serif;font-size:.95rem;line-height:1.5;color:#475569;text-transform:none;margin:0 0 4px;">
                    I, <strong>{{ auth()->user()->name }}</strong>, attest that the information above was reviewed with the patient and that all marked consents were explained and agreed to.
                </p>
                <div class="actions-bar">
                    <button type="submit" class="btn btn-secondary">
                        <i data-lucide="save" class="w-4 h-4"></i> Save draft
                    </button>
                </div>
            @else
                <div style="display:flex;align-items:center;gap:10px;color:#16a34a;font-weight:600;font-family:sans-serif;text-transform:none;">
                    <i data-lucide="check-circle" class="w-6 h-6"></i>
                    <div>
                        <div>Signed by {{ $intake->completedBy?->name ?? auth()->user()->name }}</div>
                        <div style="font-size:.85rem;font-weight:400;color:#64748b;">{{ optional($intake->signed_at)->format('F j, Y \a\t g:i A') }}</div>
                    </div>
                </div>
            @endif
        </div>
    </form>

    @if($intake->exists && ! $isSigned)
        @can('clinical.psr.admissions.edit')
            <form method="POST" action="{{ route('clinical.psr.intakes.sign', $intake) }}" class="max-w-5xl mx-auto mt-4 text-right">@csrf
                <button class="btn btn-success">
                    <i data-lucide="pen-tool" class="w-4 h-4"></i> Finalize &amp; sign intake
                </button>
            </form>
        @endcan
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', () => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
</script>
@endsection
