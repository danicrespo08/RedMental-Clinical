@extends('layouts.app')
@section('title', 'PSR — Bio-Psychosocial Assessment')

@section('content')

<style>
    /* Read-only paper-doc — same language as the edit form */
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
    .paper-value {
        flex: 1; min-width: 80px;
        border-bottom: 1.5px dashed #cbd5e1;
        padding: 4px 6px;
        font-weight: 600; font-size: .88rem; color: #1e293b; text-transform: uppercase;
    }

    .section-title {
        font-weight: 800; text-transform: uppercase;
        border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;
        margin: 28px 0 14px; font-size: .95rem; color: #0f766e; letter-spacing: .05em;
    }

    .form-section { margin-bottom: 22px; }
    .form-section .ro-text {
        width: 100%; min-height: 100px; padding: 14px 18px;
        border: 1.5px solid #e2e8f0; border-radius: .75rem;
        font-size: .88rem; line-height: 1.7;
        background: #f8fafc; color: #1e293b;
        white-space: pre-line; text-transform: uppercase;
    }
    .form-section .ro-text.empty { color: #94a3b8; font-style: italic; text-transform: none; }

    .two-column { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media (max-width: 768px) { .two-column { grid-template-columns: 1fr; } .paper-doc { padding: 22px; } }

    .risk-section {
        background: linear-gradient(135deg, #fef2f2 0%, #fff1f2 100%);
        border: 1.5px solid #fecaca; border-radius: 1rem; padding: 22px; margin: 24px 0;
    }
    .risk-section .section-title { color: #dc2626; border-color: #fecaca; }
    .risk-section .ro-text { background: #fff; border-color: #fecaca; }

    .signature-box {
        margin-top: 30px; padding: 24px; border-top: 2px dashed #cbd5e1;
        background: linear-gradient(180deg, #fff 0%, #f8fafc 100%); border-radius: .75rem;
    }
    .signature-box.locked { background: linear-gradient(180deg, #ecfdf5 0%, #d1fae5 100%); border-top: 2px solid #34d399; }

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

    $val = function ($v) {
        return [trim($v ?? ''), trim($v ?? '') === ''];
    };

    $sections = [
        ['n' => 1, 'key' => 'presenting_problem',  'label' => 'Presenting problem / Reason for referral'],
        ['n' => 2, 'key' => 'history_illness',     'label' => 'History of present illness'],
        ['n' => 3, 'key' => 'family_history',      'label' => 'Family & social history'],
    ];
    $twoColumns = [
        ['n' => 4, 'key' => 'medical_history', 'label' => 'Medical history'],
        ['n' => 5, 'key' => 'medications',     'label' => 'Current medications'],
    ];
@endphp

<div class="max-w-5xl mx-auto">

    <div class="flex items-center gap-4 mb-6 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <a href="{{ route('clinical.psr.admissions.show', $assessment->psr_admission_id) }}"
           class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:text-teal-600 hover:bg-teal-50 transition-colors border border-slate-200 flex-shrink-0">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div class="p-2.5 bg-gradient-to-br from-teal-500 to-emerald-600 text-white rounded-xl flex-shrink-0 shadow-lg shadow-teal-500/25"><i data-lucide="brain" class="w-6 h-6"></i></div>
        <div class="flex-1 min-w-0">
            <h1 class="text-lg font-black text-slate-800 tracking-tight uppercase truncate">Bio-Psychosocial Assessment</h1>
            <p class="text-xs text-slate-500 font-medium mt-0.5 truncate">
                {{ $assessment->admission?->patient?->full_name }} — {{ $assessment->admission?->patient?->mrn ?? '—' }} — {{ $assessment->admission?->clinic?->name }}
            </p>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            @if($isSigned)
                <span class="bg-emerald-100 text-emerald-700 border border-emerald-300 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider whitespace-nowrap inline-flex items-center gap-1">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Signed {{ optional($assessment->signed_at)->format('m/d/Y') }}
                </span>
            @else
                @can('clinical.psr.assessments.edit')
                    <a href="{{ route('clinical.psr.assessments.edit', $assessment) }}"
                       class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 text-[10px] font-bold uppercase tracking-wider rounded-lg border border-amber-200 inline-flex items-center gap-1.5 transition">
                        <i data-lucide="pencil" class="w-3 h-3"></i> Edit
                    </a>
                @endcan
            @endif
        </div>
    </div>

    <div class="paper-doc">
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

        {{-- Patient block --}}
        <div class="legal-block">
            <div class="paper-row">
                <label style="width:75px;">Recipient:</label>
                <div class="paper-value">{{ $assessment->admission?->patient?->full_name }}</div>
                <label style="width:40px;">Age:</label>
                <div class="paper-value" style="max-width:60px;">{{ $assessment->admission?->patient?->age ?? '—' }}</div>
                <label style="width:55px;">Gender:</label>
                <div class="paper-value" style="max-width:90px;">{{ $assessment->admission?->patient?->gender ?? '—' }}</div>
            </div>
            <div class="paper-row">
                <label style="width:75px;">MRN:</label>
                <div class="paper-value" style="max-width:160px;">{{ $assessment->admission?->patient?->mrn ?? '—' }}</div>
                <label style="width:40px;">DOB:</label>
                <div class="paper-value" style="max-width:140px;">{{ optional($assessment->admission?->patient?->date_of_birth)->format('m/d/Y') ?? '—' }}</div>
                <label style="width:75px;">Language:</label>
                <div class="paper-value" style="max-width:140px;">{{ $assessment->admission?->patient?->preferred_language ?? '—' }}</div>
            </div>
            <div class="paper-row">
                <label style="width:75px;">Adm. date:</label>
                <div class="paper-value" style="max-width:140px;">{{ optional($assessment->admission?->admission_date)->format('m/d/Y') ?? '—' }}</div>
                <label style="width:50px;">Clinic:</label>
                <div class="paper-value">{{ $assessment->admission?->clinic?->name ?? '—' }}</div>
            </div>
        </div>

        {{-- Sections --}}
        @foreach($sections as $s)
            @php([$value, $empty] = $val($assessment->{$s['key']}))
            <div class="form-section">
                <div class="section-title">{{ $s['n'] }}. {{ $s['label'] }}</div>
                <div class="ro-text {{ $empty ? 'empty' : '' }}">{{ $empty ? '— Not documented —' : $value }}</div>
            </div>
        @endforeach

        <div class="two-column">
            @foreach($twoColumns as $s)
                @php([$value, $empty] = $val($assessment->{$s['key']}))
                <div class="form-section">
                    <div class="section-title">{{ $s['n'] }}. {{ $s['label'] }}</div>
                    <div class="ro-text {{ $empty ? 'empty' : '' }}" style="min-height:130px;">{{ $empty ? '— Not documented —' : $value }}</div>
                </div>
            @endforeach
        </div>

        @php([$riskValue, $riskEmpty] = $val($assessment->risk_assessment))
        <div class="risk-section">
            <div class="section-title">6. Risk assessment</div>
            <div class="ro-text {{ $riskEmpty ? 'empty' : '' }}">{{ $riskEmpty ? '— Not documented —' : $riskValue }}</div>
        </div>

        @php([$clinValue, $clinEmpty] = $val($assessment->clinical_impression))
        <div class="form-section">
            <div class="section-title">7. Clinical impression &amp; recommendations</div>
            <div class="ro-text {{ $clinEmpty ? 'empty' : '' }}">{{ $clinEmpty ? '— Not documented —' : $clinValue }}</div>
        </div>

        {{-- Signature --}}
        <div class="signature-box {{ $isSigned ? 'locked' : '' }}">
            @if($isSigned)
                <div style="display:flex;align-items:center;gap:10px;color:#16a34a;font-weight:600;font-family:sans-serif;text-transform:none;">
                    <i data-lucide="check-circle" class="w-6 h-6"></i>
                    <div>
                        <div>Signed by {{ $assessment->signedByEmployee?->full_name ?? $assessment->signedByUser?->name ?? 'system' }}</div>
                        <div style="font-size:.85rem;font-weight:400;color:#64748b;">{{ optional($assessment->signed_at)->format('F j, Y \a\t g:i A') }}</div>
                    </div>
                </div>
            @else
                <div style="display:flex;align-items:center;gap:10px;color:#94a3b8;font-weight:600;font-family:sans-serif;text-transform:none;">
                    <i data-lucide="circle-dashed" class="w-6 h-6"></i>
                    <div>
                        <div>Draft — not yet signed</div>
                        <div style="font-size:.85rem;font-weight:400;color:#64748b;">Open in edit mode to finalize and sign.</div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
</script>
@endsection
