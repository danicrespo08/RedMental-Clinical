@extends('layouts.app')
@section('title', $auth->exists ? 'Edit authorization' : 'New PSR authorization')

@section('content')

@php
    $isEdit = $auth->exists;
    $patient = $admission?->patient ?? ($auth->patient_id ? \App\Models\Hhrr\Patient::find($auth->patient_id) : null);
    $clinic  = $admission?->clinic  ?? ($auth->clinic_id  ? \App\Models\Hhrr\Clinic::find($auth->clinic_id)  : null);
@endphp

<style>
    body { background: #f1f5f9; }

    /* paper document container */
    .paper-doc {
        max-width: 900px; margin: 0 auto;
        background: #fff; border-radius: 1.25rem;
        box-shadow: 0 12px 40px -10px rgba(15, 23, 42, .12),
                    0 4px 12px -4px rgba(15, 23, 42, .04);
        overflow: hidden; position: relative;
    }
    .paper-doc::before {
        content: ''; position: absolute; left: 0; right: 0; top: 0; height: 6px;
        background: linear-gradient(90deg, #3b82f6 0%, #06b6d4 50%, #14b8a6 100%);
    }

    .paper-header {
        padding: 1.75rem 2.25rem 1.25rem;
        border-bottom: 2px solid #f1f5f9;
        background:
            radial-gradient(circle at 100% 0, rgba(59,130,246,.07), transparent 60%),
            radial-gradient(circle at 0 100%, rgba(6,182,212,.05), transparent 60%),
            #fff;
    }
    .paper-logo {
        width: 48px; height: 48px; border-radius: 12px;
        background: linear-gradient(135deg, #3b82f6, #06b6d4);
        color: #fff; display: inline-flex; align-items: center; justify-content: center;
        box-shadow: 0 6px 14px -4px rgba(59,130,246,.4);
    }
    .paper-title-eyebrow {
        font-size: .65rem; font-weight: 800; color: #2563eb;
        text-transform: uppercase; letter-spacing: .14em;
    }
    .paper-title {
        font-size: 1.5rem; font-weight: 900; color: #0f172a;
        letter-spacing: -.01em; margin-top: .15rem;
    }

    .legal-block {
        margin: 1.25rem 2.25rem 0;
        padding: .85rem 1rem;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: 1px dashed #bae6fd;
        border-radius: .75rem;
        font-size: .72rem; color: #0c4a6e;
        display: flex; align-items: center; gap: .65rem;
    }

    .paper-body { padding: 1.5rem 2.25rem 2.25rem; }

    .section-row {
        padding: 1.25rem 0;
        border-top: 1px dashed #e2e8f0;
    }
    .section-row:first-child { border-top: 0; padding-top: 0; }

    .section-num {
        display: inline-flex; align-items: center; justify-content: center;
        width: 24px; height: 24px; border-radius: 8px;
        background: linear-gradient(135deg, #3b82f6, #06b6d4);
        color: #fff; font-size: .72rem; font-weight: 900;
        box-shadow: 0 4px 10px -3px rgba(59,130,246,.5);
    }
    .section-title {
        font-size: .68rem; font-weight: 900; color: #1e3a8a;
        text-transform: uppercase; letter-spacing: .08em;
    }

    .field-label {
        display: block; font-size: .58rem; font-weight: 800;
        color: #64748b; text-transform: uppercase;
        letter-spacing: .06em; margin-bottom: .3rem;
    }
    .field-label .req { color: #f43f5e; margin-left: .15rem; }

    .field-input,
    .field-select,
    .field-textarea {
        width: 100%; padding: .55rem .75rem;
        border: 1px solid #e2e8f0; border-radius: .55rem;
        font-size: .82rem; color: #1e293b; background: #fff;
        transition: all .2s ease; outline: none;
    }
    .field-input:focus,
    .field-select:focus,
    .field-textarea:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,.12);
    }
    .field-input.font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
    .field-textarea { resize: vertical; min-height: 90px; }

    .grid-3 { display: grid; grid-template-columns: repeat(1, 1fr); gap: .85rem; }
    @media (min-width: 640px) { .grid-3 { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 900px) { .grid-3 { grid-template-columns: repeat(3, 1fr); } }

    .grid-4 { display: grid; grid-template-columns: repeat(2, 1fr); gap: .85rem; }
    @media (min-width: 640px) { .grid-4 { grid-template-columns: repeat(4, 1fr); } }

    .grid-2 { display: grid; grid-template-columns: repeat(1, 1fr); gap: .85rem; }
    @media (min-width: 640px) { .grid-2 { grid-template-columns: repeat(2, 1fr); } }

    .footer-actions {
        margin-top: 1.5rem; padding-top: 1.5rem;
        border-top: 2px solid #f1f5f9;
        display: flex; justify-content: space-between; align-items: center;
        flex-wrap: wrap; gap: .75rem;
    }
    .btn-primary {
        background: linear-gradient(135deg, #3b82f6, #06b6d4);
        color: #fff; padding: .65rem 1.4rem; border-radius: .65rem;
        font-size: .8rem; font-weight: 800;
        box-shadow: 0 6px 16px -4px rgba(59,130,246,.45);
        transition: all .2s ease; border: 0; cursor: pointer;
        display: inline-flex; align-items: center; gap: .45rem;
    }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 20px -4px rgba(59,130,246,.55); }
    .btn-secondary {
        background: #fff; color: #475569;
        padding: .65rem 1.2rem; border-radius: .65rem;
        font-size: .8rem; font-weight: 700;
        border: 1px solid #e2e8f0; transition: all .15s;
        text-decoration: none;
    }
    .btn-secondary:hover { background: #f8fafc; color: #1e293b; }

    .helper-text {
        font-size: .68rem; color: #94a3b8; margin-top: .25rem;
    }

    .error-summary {
        background: #fef2f2; border: 1px solid #fecaca;
        border-radius: .75rem; padding: 1rem 1.25rem;
        margin: 0 2.25rem 1rem; color: #991b1b; font-size: .8rem;
    }
    .error-summary strong { color: #7f1d1d; }
</style>

<div class="px-4 py-6">

    <a href="{{ route('clinical.psr.authorizations.index') }}"
       class="inline-flex items-center gap-1 text-xs font-bold text-slate-500 hover:text-blue-600 uppercase tracking-wider mb-4 max-w-[900px] mx-auto block">
        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Authorizations
    </a>

    <div class="paper-doc">

        <div class="paper-header">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3.5">
                    <div class="paper-logo">
                        <i data-lucide="shield-check" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <div class="paper-title-eyebrow">PSR · Prior Authorization</div>
                        <div class="paper-title">{{ $isEdit ? 'Edit Authorization' : 'New Authorization' }}</div>
                        @if($isEdit)
                        <div class="text-[11px] text-slate-500 mt-1 font-mono">{{ $auth->auth_number ?: '#'.$auth->id }}</div>
                        @endif
                    </div>
                </div>
                @if($admission && $patient)
                <div class="text-right">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">For Admission</div>
                    <div class="text-sm font-bold text-slate-800 mt-0.5">{{ $patient->full_name }}</div>
                    <div class="text-[11px] text-slate-500 font-mono">MRN {{ $patient->mrn ?? '—' }} · {{ optional($admission->admission_date)->format('m/d/Y') }}</div>
                </div>
                @endif
            </div>
        </div>

        @if($admission && $patient)
        <div class="legal-block">
            <i data-lucide="info" class="w-4 h-4 text-sky-600 flex-shrink-0"></i>
            <div>
                Authorization is linked to admission <strong>#{{ $admission->id }}</strong> for
                <strong>{{ $patient->full_name }}</strong>{{ $clinic ? ' at '.$clinic->name : '' }}.
                Patient and clinic fields are pre-filled and cannot be changed here.
            </div>
        </div>
        @endif

        @if($errors->any())
        <div class="error-summary mt-4">
            <strong>Please fix the following:</strong>
            <ul class="list-disc list-inside mt-1.5 space-y-0.5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST"
              action="{{ $isEdit ? route('clinical.psr.authorizations.update', $auth) : route('clinical.psr.authorizations.store') }}"
              class="paper-body">
            @csrf
            @if($isEdit) @method('PUT') @endif

            {{-- 1. Identification --}}
            <div class="section-row">
                <div class="flex items-center gap-2 mb-3">
                    <span class="section-num">1</span>
                    <span class="section-title">Identification</span>
                </div>
                <div class="grid-3">
                    <div>
                        <label class="field-label">Admission <span class="req">*</span></label>
                        <select name="psr_admission_id" required class="field-select">
                            <option value="">— Select admission —</option>
                            @foreach($admissions as $a)
                                <option value="{{ $a->id }}" @selected(old('psr_admission_id', $auth->psr_admission_id) == $a->id)>
                                    {{ $a->patient?->full_name }} · {{ optional($a->admission_date)->format('m/d/Y') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Patient <span class="req">*</span></label>
                        <select name="patient_id" required class="field-select">
                            <option value="">— Select patient —</option>
                            @foreach($patients as $p)
                                <option value="{{ $p->id }}" @selected(old('patient_id', $auth->patient_id) == $p->id)>
                                    {{ $p->full_name }}{{ $p->mrn ? ' · MRN '.$p->mrn : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Auth Number</label>
                        <input type="text" name="auth_number" maxlength="100"
                               value="{{ old('auth_number', $auth->auth_number) }}"
                               placeholder="e.g. AUTH-2026-001"
                               class="field-input font-mono">
                    </div>
                    <div>
                        <label class="field-label">Auth Type <span class="req">*</span></label>
                        <select name="auth_type" required class="field-select">
                            @foreach($authTypes as $k => $v)
                                <option value="{{ $k }}" @selected(old('auth_type', $auth->auth_type) === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Status <span class="req">*</span></label>
                        <select name="status" required class="field-select">
                            @foreach($statuses as $k => $v)
                                <option value="{{ $k }}" @selected(old('status', $auth->status) === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Reference #</label>
                        <input type="text" name="reference_number" maxlength="100"
                               value="{{ old('reference_number', $auth->reference_number) }}"
                               class="field-input font-mono">
                    </div>
                    <div>
                        <label class="field-label">Payer</label>
                        <select name="payer_id" class="field-select">
                            <option value="">—</option>
                            @foreach($payers as $p)
                                <option value="{{ $p->id }}" @selected(old('payer_id', $auth->payer_id) == $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Clinic</label>
                        <select name="clinic_id" class="field-select">
                            <option value="">—</option>
                            @foreach($clinics as $c)
                                <option value="{{ $c->id }}" @selected(old('clinic_id', $auth->clinic_id) == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Plan Type</label>
                        <input type="text" name="plan_type" maxlength="50"
                               value="{{ old('plan_type', $auth->plan_type) }}"
                               placeholder="HMO, PPO, Medicaid…"
                               class="field-input">
                    </div>
                </div>
            </div>

            {{-- 2. Insurance IDs --}}
            <div class="section-row">
                <div class="flex items-center gap-2 mb-3">
                    <span class="section-num">2</span>
                    <span class="section-title">Insurance Identifiers</span>
                </div>
                <div class="grid-3">
                    <div>
                        <label class="field-label">Member ID</label>
                        <input type="text" name="member_id" value="{{ old('member_id', $auth->member_id) }}" class="field-input font-mono">
                    </div>
                    <div>
                        <label class="field-label">Medicaid ID</label>
                        <input type="text" name="medicaid_id" value="{{ old('medicaid_id', $auth->medicaid_id) }}" class="field-input font-mono">
                    </div>
                    <div>
                        <label class="field-label">Payer External ID</label>
                        <input type="text" name="payer_external_id" maxlength="50"
                               value="{{ old('payer_external_id', $auth->payer_external_id) }}"
                               class="field-input font-mono">
                    </div>
                </div>
            </div>

            {{-- 3. Service codes --}}
            <div class="section-row">
                <div class="flex items-center gap-2 mb-3">
                    <span class="section-num">3</span>
                    <span class="section-title">Service / Billing Codes</span>
                </div>
                <div class="grid-4">
                    <div class="sm:col-span-1">
                        <label class="field-label">Service Code (CPT/HCPCS)</label>
                        <input type="text" name="service_code" maxlength="20"
                               value="{{ old('service_code', $auth->service_code) }}"
                               class="field-input font-mono">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="field-label">Service Description</label>
                        <input type="text" name="service_description" maxlength="255"
                               value="{{ old('service_description', $auth->service_description) }}"
                               placeholder="e.g. Psychosocial Rehabilitation, per 15 min"
                               class="field-input">
                    </div>
                    @foreach(['modifier_1'=>'Modifier 1','modifier_2'=>'Modifier 2','modifier_3'=>'Modifier 3','modifier_4'=>'Modifier 4'] as $f => $label)
                        <div>
                            <label class="field-label">{{ $label }}</label>
                            <input type="text" name="{{ $f }}" maxlength="10"
                                   value="{{ old($f, $auth->{$f}) }}"
                                   class="field-input font-mono">
                        </div>
                    @endforeach
                    <div>
                        <label class="field-label">Place of Service</label>
                        <input type="text" name="place_of_service" maxlength="10"
                               value="{{ old('place_of_service', $auth->place_of_service) }}"
                               placeholder="e.g. 53"
                               class="field-input font-mono">
                    </div>
                    <div>
                        <label class="field-label">Revenue Code</label>
                        <input type="text" name="revenue_code" maxlength="10"
                               value="{{ old('revenue_code', $auth->revenue_code) }}"
                               class="field-input font-mono">
                    </div>
                </div>
            </div>

            {{-- 4. Units & Frequency --}}
            <div class="section-row">
                <div class="flex items-center gap-2 mb-3">
                    <span class="section-num">4</span>
                    <span class="section-title">Units &amp; Frequency</span>
                </div>
                <div class="grid-4">
                    <div>
                        <label class="field-label">Units Requested</label>
                        <input type="number" name="units_requested" min="0"
                               value="{{ old('units_requested', $auth->units_requested) }}"
                               class="field-input font-mono">
                    </div>
                    <div>
                        <label class="field-label">Units Approved</label>
                        <input type="number" name="units_approved" min="0"
                               value="{{ old('units_approved', $auth->units_approved) }}"
                               class="field-input font-mono">
                    </div>
                    <div>
                        <label class="field-label">Units Used</label>
                        <input type="number" name="units_used" min="0"
                               value="{{ old('units_used', $auth->units_used) }}"
                               class="field-input font-mono">
                    </div>
                    <div>
                        <label class="field-label">Unit Type <span class="req">*</span></label>
                        <select name="unit_type" required class="field-select">
                            @foreach($unitTypes as $k => $v)
                                <option value="{{ $k }}" @selected(old('unit_type', $auth->unit_type) === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="field-label">Frequency</label>
                        <input type="text" name="frequency" maxlength="100"
                               value="{{ old('frequency', $auth->frequency) }}"
                               placeholder="e.g. 5 days/week, 4 hr/day"
                               class="field-input">
                    </div>
                    <div>
                        <label class="field-label">Units Alert %</label>
                        <input type="number" name="units_alert_threshold" min="1" max="100"
                               value="{{ old('units_alert_threshold', $auth->units_alert_threshold ?? 80) }}"
                               class="field-input font-mono">
                        <div class="helper-text">% used that triggers a low-units alert</div>
                    </div>
                    <div>
                        <label class="field-label">Expiry Alert (days)</label>
                        <input type="number" name="expiry_alert_days" min="1" max="365"
                               value="{{ old('expiry_alert_days', $auth->expiry_alert_days ?? 14) }}"
                               class="field-input font-mono">
                        <div class="helper-text">Days before expiry to start warning</div>
                    </div>
                </div>
            </div>

            {{-- 5. Date Ranges --}}
            <div class="section-row">
                <div class="flex items-center gap-2 mb-3">
                    <span class="section-num">5</span>
                    <span class="section-title">Date Ranges</span>
                </div>
                <div class="grid-4">
                    @foreach([
                        'requested_start_date'=>'Requested Start',
                        'requested_end_date'=>'Requested End',
                        'approved_start_date'=>'Approved Start',
                        'approved_end_date'=>'Approved End',
                        'submission_date'=>'Submission Date',
                        'decision_date'=>'Decision Date',
                    ] as $f => $label)
                        <div>
                            <label class="field-label">{{ $label }}</label>
                            <input type="date" name="{{ $f }}"
                                   value="{{ old($f, optional($auth->{$f})->format('Y-m-d')) }}"
                                   class="field-input">
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 6. Provider info --}}
            <div class="section-row">
                <div class="flex items-center gap-2 mb-3">
                    <span class="section-num">6</span>
                    <span class="section-title">Provider Information</span>
                </div>
                <div class="grid-3">
                    <div>
                        <label class="field-label">Rendering Provider</label>
                        <select name="rendering_provider_employee_id" class="field-select">
                            <option value="">—</option>
                            @foreach($providers as $p)
                                <option value="{{ $p->id }}" @selected(old('rendering_provider_employee_id', $auth->rendering_provider_employee_id) == $p->id)>
                                    {{ $p->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Supervising Provider</label>
                        <select name="supervising_provider_id" class="field-select">
                            <option value="">—</option>
                            @foreach($providers as $p)
                                <option value="{{ $p->id }}" @selected(old('supervising_provider_id', $auth->supervising_provider_id) == $p->id)>
                                    {{ $p->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Group NPI</label>
                        <input type="text" name="group_npi" maxlength="20"
                               value="{{ old('group_npi', $auth->group_npi) }}"
                               class="field-input font-mono">
                    </div>
                    <div>
                        <label class="field-label">Rendering NPI</label>
                        <input type="text" name="rendering_npi" maxlength="20"
                               value="{{ old('rendering_npi', $auth->rendering_npi) }}"
                               class="field-input font-mono">
                    </div>
                    <div>
                        <label class="field-label">Taxonomy Code</label>
                        <input type="text" name="taxonomy_code" maxlength="20"
                               value="{{ old('taxonomy_code', $auth->taxonomy_code) }}"
                               class="field-input font-mono">
                    </div>
                </div>
            </div>

            {{-- 7. Diagnoses --}}
            <div class="section-row">
                <div class="flex items-center gap-2 mb-3">
                    <span class="section-num">7</span>
                    <span class="section-title">Diagnoses (ICD-10)</span>
                </div>
                <div class="grid-2">
                    <div>
                        <label class="field-label">Primary Code</label>
                        <input type="text" name="primary_dx_code" maxlength="20"
                               value="{{ old('primary_dx_code', $auth->primary_dx_code) }}"
                               placeholder="e.g. F33.1"
                               class="field-input font-mono">
                    </div>
                    <div>
                        <label class="field-label">Primary Description</label>
                        <input type="text" name="primary_dx_description" maxlength="255"
                               value="{{ old('primary_dx_description', $auth->primary_dx_description) }}"
                               class="field-input">
                    </div>
                    <div>
                        <label class="field-label">Secondary Code</label>
                        <input type="text" name="secondary_dx_code" maxlength="20"
                               value="{{ old('secondary_dx_code', $auth->secondary_dx_code) }}"
                               class="field-input font-mono">
                    </div>
                    <div>
                        <label class="field-label">Secondary Description</label>
                        <input type="text" name="secondary_dx_description" maxlength="255"
                               value="{{ old('secondary_dx_description', $auth->secondary_dx_description) }}"
                               class="field-input">
                    </div>
                </div>
            </div>

            {{-- 8. Justification --}}
            <div class="section-row">
                <div class="flex items-center gap-2 mb-3">
                    <span class="section-num">8</span>
                    <span class="section-title">Clinical Justification</span>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="field-label">Clinical Justification</label>
                        <textarea name="clinical_justification" rows="3"
                                  class="field-textarea">{{ old('clinical_justification', $auth->clinical_justification) }}</textarea>
                    </div>
                    <div>
                        <label class="field-label">Medical Necessity Statement</label>
                        <textarea name="medical_necessity_statement" rows="3"
                                  class="field-textarea">{{ old('medical_necessity_statement', $auth->medical_necessity_statement) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- 9. Decision tracking --}}
            <div class="section-row">
                <div class="flex items-center gap-2 mb-3">
                    <span class="section-num">9</span>
                    <span class="section-title">Decision &amp; Tracking</span>
                </div>
                <div class="grid-2 mb-3">
                    <div>
                        <label class="field-label">Insurance Contact Name</label>
                        <input type="text" name="contact_name" maxlength="150"
                               value="{{ old('contact_name', $auth->contact_name) }}"
                               class="field-input">
                    </div>
                    <div>
                        <label class="field-label">Insurance Contact Phone</label>
                        <input type="text" name="contact_phone" maxlength="30"
                               value="{{ old('contact_phone', $auth->contact_phone) }}"
                               class="field-input font-mono">
                    </div>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="field-label">Denial Reason</label>
                        <textarea name="denial_reason" rows="2"
                                  class="field-textarea">{{ old('denial_reason', $auth->denial_reason) }}</textarea>
                    </div>
                    <div>
                        <label class="field-label">Appeal Notes</label>
                        <textarea name="appeal_notes" rows="2"
                                  class="field-textarea">{{ old('appeal_notes', $auth->appeal_notes) }}</textarea>
                    </div>
                    <div>
                        <label class="field-label">Internal Notes</label>
                        <textarea name="notes" rows="2"
                                  class="field-textarea">{{ old('notes', $auth->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="footer-actions">
                <a href="{{ route('clinical.psr.authorizations.index') }}" class="btn-secondary">
                    <i data-lucide="x" class="w-3.5 h-3.5 inline-block mr-1"></i> Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    {{ $isEdit ? 'Save Changes' : 'Create Authorization' }}
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>
@endpush
@endsection
