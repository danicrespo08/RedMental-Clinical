@extends('layouts.app')
@section('title', 'PSR — Bio-Psychosocial Assessment')

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

    .section-title {
        font-weight: 800; text-transform: uppercase;
        border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;
        margin: 28px 0 14px; font-size: .95rem; color: #0f766e; letter-spacing: .05em;
    }
    .form-section { margin-bottom: 22px; }
    .form-section textarea {
        width: 100%; min-height: 130px; padding: 14px 18px;
        border: 1.5px solid #e2e8f0; border-radius: .75rem;
        font-size: .88rem; line-height: 1.7; resize: vertical;
        background: #f8fafc; color: #1e293b; transition: all .25s;
        text-transform: uppercase;
    }
    .form-section textarea:focus { outline: none; border-color: #0f766e; box-shadow: 0 0 0 4px rgba(15,118,110,.06); background: #fff; }
    .form-section textarea::placeholder { color: #94a3b8; font-style: italic; text-transform: none; }

    .two-column { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media (max-width: 768px) { .two-column { grid-template-columns: 1fr; } .paper-doc { padding: 22px; } }

    .risk-section {
        background: linear-gradient(135deg, #fef2f2 0%, #fff1f2 100%);
        border: 1.5px solid #fecaca; border-radius: 1rem; padding: 22px; margin: 24px 0;
    }
    .risk-section .section-title { color: #dc2626; border-color: #fecaca; }
    .risk-section textarea { background: #fff; border-color: #fecaca; }
    .risk-section textarea:focus { border-color: #dc2626; box-shadow: 0 0 0 4px rgba(220,38,38,.06); }

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
    $isSigned  = $assessment->is_signed;
    $clientObj = auth()->user()->client;
@endphp

<div class="max-w-5xl mx-auto">

    <div class="flex items-center gap-4 mb-6 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <a href="{{ route('clinical.psr.admissions.show', $admission) }}" class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors border border-slate-200 flex-shrink-0">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div class="p-2.5 bg-gradient-to-br from-teal-500 to-emerald-600 text-white rounded-xl flex-shrink-0 shadow-lg shadow-teal-500/25"><i data-lucide="brain" class="w-6 h-6"></i></div>
        <div class="flex-1 min-w-0">
            <h1 class="text-lg font-black text-slate-800 tracking-tight uppercase truncate">Bio-Psychosocial Assessment</h1>
            <p class="text-xs text-slate-500 font-medium mt-0.5 truncate">
                {{ $admission->patient?->full_name }} — {{ $admission->patient?->mrn ?? '—' }} — {{ $admission->clinic?->name }}
            </p>
        </div>
        @if($isSigned)
            <span class="bg-emerald-100 text-emerald-700 border border-emerald-300 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider whitespace-nowrap inline-flex items-center gap-1">
                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Signed {{ optional($assessment->signed_at)->format('m/d/Y') }}
            </span>
        @endif
    </div>

    <form action="{{ $assessment->exists ? route('clinical.psr.assessments.update', $assessment) : route('clinical.psr.assessments.store') }}"
          method="POST" class="paper-doc">
        @csrf
        @if($assessment->exists) @method('PUT') @endif
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
            <h2>Bio-Psychosocial Assessment</h2>
        </div>

        <div class="legal-block">
            <div class="paper-row">
                <label style="width:75px;">Recipient:</label>
                <input type="text" value="{{ $admission->patient?->full_name }}" class="paper-input" readonly>
                <label style="width:40px;">Age:</label>
                <input type="text" value="{{ $admission->patient?->age ?? '—' }}" class="paper-input" style="max-width:60px;" readonly>
                <label style="width:55px;">Gender:</label>
                <input type="text" value="{{ $admission->patient?->gender ?? '—' }}" class="paper-input" style="max-width:90px;" readonly>
            </div>
            <div class="paper-row">
                <label style="width:75px;">MRN:</label>
                <input type="text" value="{{ $admission->patient?->mrn ?? '—' }}" class="paper-input" style="max-width:160px;" readonly>
                <label style="width:40px;">DOB:</label>
                <input type="text" value="{{ optional($admission->patient?->date_of_birth)->format('m/d/Y') ?? '—' }}" class="paper-input" style="max-width:140px;" readonly>
                <label style="width:75px;">Language:</label>
                <input type="text" value="{{ $admission->patient?->preferred_language ?? '—' }}" class="paper-input" style="max-width:140px;" readonly>
            </div>
            <div class="paper-row">
                <label style="width:75px;">Adm. date:</label>
                <input type="text" value="{{ optional($admission->admission_date)->format('m/d/Y') ?? '—' }}" class="paper-input" style="max-width:140px;" readonly>
                <label style="width:50px;">Clinic:</label>
                <input type="text" value="{{ $admission->clinic?->name ?? '—' }}" class="paper-input" readonly>
            </div>
            <div class="paper-row">
                <label style="width:75px;">Address:</label>
                <input type="text" value="{{ trim(($admission->patient?->address ?? '') . ($admission->patient?->city ? ', '.$admission->patient->city : '') . ($admission->patient?->state ? ' '.$admission->patient->state : '') . ' ' . ($admission->patient?->zip ?? '')) }}" class="paper-input" readonly>
            </div>
        </div>

        <div class="form-section">
            <div class="section-title">1. Presenting problem / Reason for referral</div>
            <textarea name="presenting_problem" {{ $isSigned ? 'disabled' : '' }} placeholder="Describe the patient's presenting problem, reason for referral, and chief complaint. Include onset, duration, and severity of symptoms…">{{ old('presenting_problem', $assessment->presenting_problem) }}</textarea>
        </div>

        <div class="form-section">
            <div class="section-title">2. History of present illness</div>
            <textarea name="history_illness" {{ $isSigned ? 'disabled' : '' }} placeholder="Chronological narrative of the development of symptoms. Include previous episodes, hospitalizations, and treatment history…">{{ old('history_illness', $assessment->history_illness) }}</textarea>
        </div>

        <div class="form-section">
            <div class="section-title">3. Family &amp; social history</div>
            <textarea name="family_history" {{ $isSigned ? 'disabled' : '' }} placeholder="Family psychiatric history, social support system, living situation, relationships, education, employment history, legal issues…">{{ old('family_history', $assessment->family_history) }}</textarea>
        </div>

        <div class="two-column">
            <div class="form-section">
                <div class="section-title">4. Medical history</div>
                <textarea name="medical_history" style="min-height:150px;" {{ $isSigned ? 'disabled' : '' }} placeholder="Current medical conditions, past surgeries, allergies, chronic illnesses…">{{ old('medical_history', $assessment->medical_history) }}</textarea>
            </div>
            <div class="form-section">
                <div class="section-title">5. Current medications</div>
                <textarea name="medications" style="min-height:150px;" {{ $isSigned ? 'disabled' : '' }} placeholder="List all current medications including dosage and frequency. Include psychiatric and medical medications…">{{ old('medications', $assessment->medications) }}</textarea>
            </div>
        </div>

        <div class="risk-section">
            <div class="section-title">6. Risk assessment</div>
            <textarea name="risk_assessment" {{ $isSigned ? 'disabled' : '' }} placeholder="Evaluate for suicidal ideation, homicidal ideation, self-harm behaviors, history of violence, substance abuse, and current risk level. Include protective factors…">{{ old('risk_assessment', $assessment->risk_assessment) }}</textarea>
        </div>

        <div class="form-section">
            <div class="section-title">7. Clinical impression &amp; recommendations</div>
            <textarea name="clinical_impression" {{ $isSigned ? 'disabled' : '' }} placeholder="Summary of clinical findings, diagnostic impression (DSM-5 / ICD-10 codes), treatment recommendations, and level of care needed…">{{ old('clinical_impression', $assessment->clinical_impression) }}</textarea>
        </div>

        <div class="signature-box {{ $isSigned ? 'locked' : '' }}">
            @if(! $isSigned)
                <p style="font-family:sans-serif;font-size:.95rem;line-height:1.5;color:#475569;text-transform:none;margin:0 0 4px;">
                    I, <strong>{{ auth()->user()->name }}</strong>, attest that this assessment is accurate and complete based on a face-to-face interview and interaction with the patient.
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
                        <div>Signed by {{ $assessment->signedByEmployee?->full_name ?? $assessment->signedByUser?->name ?? auth()->user()->name }}</div>
                        <div style="font-size:.85rem;font-weight:400;color:#64748b;">{{ optional($assessment->signed_at)->format('F j, Y \a\t g:i A') }}</div>
                    </div>
                </div>
            @endif
        </div>
    </form>

    @if($assessment->exists && ! $isSigned)
        @can('clinical.psr.assessments.sign')
            <form method="POST" action="{{ route('clinical.psr.assessments.sign', $assessment) }}" class="max-w-5xl mx-auto mt-4 text-right">@csrf
                <button class="btn btn-success">
                    <i data-lucide="pen-tool" class="w-4 h-4"></i> Finalize &amp; sign assessment
                </button>
            </form>
        @endcan
    @endif

    <div class="mt-8 max-w-5xl mx-auto">
        <div class="flex items-center justify-between mb-3 px-1">
            <h2 class="text-base font-black text-slate-700 uppercase tracking-wider flex items-center gap-2">
                <i data-lucide="gauge" class="w-4 h-4 text-sky-600"></i> FARS evaluations
            </h2>
            @can('clinical.psr.assessments.create')
                <a href="{{ route('clinical.psr.assessments.fars.create', $admission) }}"
                   class="px-4 py-2 bg-gradient-to-br from-sky-500 to-blue-600 hover:from-sky-600 hover:to-blue-700 text-white text-xs font-bold uppercase tracking-wider rounded-lg shadow-sm shadow-sky-500/30 inline-flex items-center gap-2">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> New FARS
                </a>
            @endcan
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase">
                    <tr><th class="px-4 py-3 text-left">Type</th><th class="px-4 py-3 text-left">Date</th><th class="px-4 py-3 text-right">Total</th><th class="px-4 py-3 text-right">MGAF</th><th class="px-4 py-3 text-center">Signed</th><th class="px-4 py-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($fars as $f)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 uppercase text-xs font-bold">{{ $f->evaluation_type }}</td>
                            <td class="px-4 py-3 text-xs">{{ $f->evaluation_date->format('M j, Y') }}</td>
                            <td class="px-4 py-3 text-right font-mono font-bold">{{ $f->total_score }}</td>
                            <td class="px-4 py-3 text-right font-mono">{{ $f->mgaf_score ?: '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($f->is_signed)<i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 inline"></i>@endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('clinical.psr.assessments.fars.edit', $f) }}" class="text-xs text-sky-600 font-bold hover:underline">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400 text-sm">No FARS recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
</script>
@endsection
