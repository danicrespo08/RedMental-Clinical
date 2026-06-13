@extends('layouts.app')
@section('title', 'PSR — FARS Assessment')

@section('content')

<style>
    .paper-doc {
        background: #fff; border: 1px solid #e2e8f0;
        padding: 36px 44px;
        font-family: 'DM Sans', 'Segoe UI', sans-serif;
        color: #1e293b;
        box-shadow: 0 8px 30px -8px rgba(0,0,0,.06);
        margin: 0 auto 20px; max-width: 1100px;
        text-transform: uppercase; border-radius: 1rem;
        position: relative;
    }
    .paper-doc::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
        background: linear-gradient(90deg, #0c4a6e, #0284c7, #0c4a6e);
        border-radius: 1rem 1rem 0 0;
    }

    .paper-header { text-align: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #e2e8f0; }
    .paper-header .logo-fallback {
        width: 60px; height: 60px; border-radius: 16px;
        background: linear-gradient(135deg, #0c4a6e 0%, #0284c7 100%); color: #fff;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.8rem; font-weight: 800; font-family: sans-serif; margin-bottom: 12px;
        box-shadow: 0 4px 12px rgba(12,74,110,.25);
    }
    .paper-header h1 { font-size: 1.15rem; font-weight: 800; margin: 0; letter-spacing: .05em; color: #0f172a; }
    .paper-header p  { font-size: .8rem; margin: 2px 0; color: #64748b; text-transform: uppercase; font-weight: 600; }
    .paper-header h2 { font-size: 1rem; font-weight: 800; margin-top: 12px; padding-top: 10px; border-top: 1.5px solid #e2e8f0; letter-spacing: .06em; color: #0f172a; }

    .info-grid {
        display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;
        margin-bottom: 22px; font-size: .86rem;
    }
    .info-grid > div { display: flex; gap: 10px; align-items: baseline; padding: 6px 0; border-bottom: 1px dashed #e2e8f0; }
    .info-grid label { font-weight: 700; min-width: 110px; white-space: nowrap; color: #475569; font-size: .78rem; }
    .info-grid span  { font-weight: 600; color: #1e293b; }

    .severity-scale {
        display: flex; justify-content: space-between;
        margin: 18px 0; padding: 14px 16px;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: 1px solid #bae6fd; border-radius: .75rem; font-size: .72rem;
    }
    .severity-scale span { text-align: center; flex: 1; line-height: 1.3; }
    .severity-scale span:nth-child(odd) { font-weight: 700; color: #0369a1; }

    .domain-section {
        border: 1px solid #e2e8f0; margin-bottom: 14px;
        border-radius: .75rem; overflow: hidden; background: #fff;
    }
    .domain-header {
        background: linear-gradient(135deg, #0c4a6e 0%, #0284c7 100%);
        color: #fff; padding: 11px 18px;
        display: flex; justify-content: space-between; align-items: center;
    }
    .domain-title { font-weight: 700; font-size: .86rem; letter-spacing: .03em; }
    .domain-score { display: flex; align-items: center; gap: 10px; font-size: .72rem; font-weight: 700; }
    .domain-score input {
        width: 56px; padding: 6px; text-align: center;
        font-size: 1rem; font-weight: 800;
        border: 2px solid rgba(255,255,255,.4); border-radius: .4rem;
        background: rgba(255,255,255,.12); color: #fff;
    }
    .domain-score input:focus { outline: none; background: rgba(255,255,255,.25); border-color: #fff; }

    .indicators-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 6px 14px; padding: 14px 18px;
        font-size: .76rem;
    }
    .indicator-item { display: flex; align-items: center; gap: 6px; cursor: pointer; color: #475569; font-weight: 500; text-transform: none; }
    .indicator-item:hover { color: #0369a1; }
    .indicator-item input { width: 14px; height: 14px; cursor: pointer; accent-color: #0369a1; }

    .total-section {
        background: linear-gradient(135deg, #0c4a6e 0%, #0284c7 100%);
        color: #fff; padding: 22px 24px; margin-top: 22px;
        display: flex; justify-content: space-between; align-items: center;
        border-radius: 1rem; box-shadow: 0 4px 16px rgba(12,74,110,.15);
    }
    .total-score { font-size: 2rem; font-weight: 800; }
    .mgaf-section {
        display: flex; justify-content: space-between; align-items: center;
        margin-top: 16px; padding: 16px 22px;
        background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: .75rem;
        text-transform: none;
    }
    .mgaf-section input {
        width: 90px; padding: 8px; text-align: center;
        font-size: 1.1rem; font-weight: 800;
        border: 1.5px solid #cbd5e1; border-radius: .5rem;
        background: #fff;
    }

    .signature-box {
        margin-top: 26px; padding: 22px;
        border: 2px dashed #cbd5e1; border-radius: .75rem;
        background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
        text-transform: none;
    }
    .signature-box.locked { border-style: solid; border-color: #34d399; background: linear-gradient(180deg, #ecfdf5 0%, #d1fae5 100%); }

    .btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 22px; border-radius: .65rem; font-weight: 700; font-size: .85rem;
        cursor: pointer; transition: all .2s; text-transform: uppercase; letter-spacing: .03em;
        border: 1px solid transparent; text-decoration: none;
    }
    .btn-secondary { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
    .btn-secondary:hover { background: #e2e8f0; }
    .btn-primary { background: linear-gradient(135deg, #0284c7, #0369a1); color: #fff; box-shadow: 0 4px 12px rgba(2,132,199,.25); }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(2,132,199,.32); }
    .btn-success { background: linear-gradient(135deg, #059669, #10b981); color: #fff; box-shadow: 0 4px 12px rgba(5,150,105,.25); }
    .btn-success:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(5,150,105,.32); }

    @media (max-width: 768px) {
        .paper-doc { padding: 22px; }
        .info-grid { grid-template-columns: 1fr; }
    }
</style>

@php
    $isSigned  = (bool) ($fars->is_signed ?? false);
    $clientObj = auth()->user()->client;

    // 18 FARS domains with their indicator checklist 
    $farsDomains = [
        'depression'           => ['title' => 'Depression',                    'indicators' => ['Depressed mood','Anhedonic','Worthless','Sleep problems','Lonely','Sad','Anti-depression meds','Hopeless']],
        'security'             => ['title' => 'Security / Management needs',   'indicators' => ['Home w/o supervision','Suicide watch','Behavioral contract','Run/escape risk','Restraint','Protection from others','Involuntary commitment']],
        'hyperaffect'          => ['title' => 'Hyperaffect',                   'indicators' => ['Manic','Mood swings','Pressured speech','Elevated mood','Agitated','Sleep deficit','Anti-manic meds','Overactive']],
        'anxiety'              => ['title' => 'Anxiety',                       'indicators' => ['Anxious','Anti-anxiety medications','Obsessive/compulsive','Guilt','Panic','Tense','Fearful']],
        'cognitive'            => ['title' => 'Cognitive performance',         'indicators' => ['Poor memory','Short attention','Low self-awareness','Impaired judgment','Poor concentration','Slow processing','Developmental disability']],
        'thought_process'      => ['title' => 'Thought process',               'indicators' => ['Illogical','Delusional','Hallucinations','Loose associations','Paranoid','Anti-psych. meds','Ruminative']],
        'traumatic_stress'     => ['title' => 'Traumatic stress',              'indicators' => ['Acute','Repression/amnesia','Dreams/nightmares','Upsetting memories','Chronic','Avoidant','Detached']],
        'medical_physical'     => ['title' => 'Medical / Physical',            'indicators' => ['Acute illness','Seizures','Handicap/permanent disability','Stress-related illness','Poor nutrition','CNS disorder','Chronic illness','Eating disorder']],
        'interpersonal'        => ['title' => 'Interpersonal relationships',   'indicators' => ['Problems w/friends','Difficulty establish/maintain relationships','Poor social skills']],
        'family_relationships' => ['title' => 'Family relationships',          'indicators' => ['Poor parenting skills','Difficulty w/partner','Acting out','Conflict w/relative','No family','Difficulty w/parent']],
        'family_environment'   => ['title' => 'Family environment',            'indicators' => ['Family instability','Separation','Custody problem','Family legal problems','Divorce','Single parent','Death in family']],
        'substance_use'        => ['title' => 'Substance use',                 'indicators' => ['Alcohol','Cravings/urges','Interferes with duties','Drug(s)','Dependence','I.V. drugs','Abuse','Recovery','Family history']],
        'work_school'          => ['title' => 'Work or school',                'indicators' => ['Absenteeism','Seeking employment','Not employed','Poor performance','Disabled','Dropped out','Tardiness']],
        'socio_legal'          => ['title' => 'Socio-legal',                   'indicators' => ['Disregards rules','Pending charges','Offenses/property','Dishonesty','Offenses/person','Probation']],
        'danger_others'        => ['title' => 'Danger to others',              'indicators' => ['Violent temper','Homicidal threats','Physically aggressive','Threatens others','Assaultive','Hostile','Homicidal ideation']],
        'danger_self'          => ['title' => 'Danger to self',                'indicators' => ['Suicidal ideation','Self-mutilation','Current plan','Past attempt','Recent attempt','Self-injury']],
        'adl'                  => ['title' => 'ADL functioning',               'indicators' => ['Money management problems','Meal preparation difficulties','Personal hygiene problems','Transportation problems','Problems obtaining/maintaining employment','Problems obtaining/maintaining housing']],
        'self_care'            => ['title' => 'Ability to care for self',      'indicators' => ['Risk of harm','Suffers from neglect','Refuses to care for self','Unable to survive w/o help','Alternative care not available']],
    ];

    $savedIndicators = is_array($fars->indicators_json ?? null) ? $fars->indicators_json : (json_decode($fars->indicators_json ?? '[]', true) ?: []);
@endphp

<div class="max-w-6xl mx-auto">

    <div class="flex items-center gap-4 mb-6 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <a href="{{ route('clinical.psr.admissions.show', $admission) }}" class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors border border-slate-200 flex-shrink-0">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div class="p-2.5 bg-gradient-to-br from-sky-600 to-blue-700 text-white rounded-xl flex-shrink-0 shadow-lg shadow-sky-500/30"><i data-lucide="gauge" class="w-6 h-6"></i></div>
        <div class="flex-1 min-w-0">
            <h1 class="text-lg font-black text-slate-800 tracking-tight uppercase truncate">FARS — Functional Assessment Rating Scale</h1>
            <p class="text-xs text-slate-500 font-medium mt-0.5 truncate">
                {{ $admission->patient?->full_name }} — {{ $admission->patient?->mrn ?? '—' }} — {{ $admission->clinic?->name }}
            </p>
        </div>
        @if($isSigned)
            <span class="bg-emerald-100 text-emerald-700 border border-emerald-300 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider whitespace-nowrap inline-flex items-center gap-1">
                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Signed
            </span>
        @endif
    </div>

    <form method="POST" action="{{ $fars->exists ? route('clinical.psr.assessments.fars.update', $fars) : route('clinical.psr.assessments.fars.store', $admission) }}" class="paper-doc">
        @csrf
        @if($fars->exists) @method('PUT') @endif

        <div class="paper-header">
            <div class="logo-fallback">{{ mb_substr($clientObj?->name ?? 'R', 0, 1) }}</div>
            <h1>{{ $clientObj?->name ?? 'RedMental' }}</h1>
            @if($clientObj?->address)<p>{{ $clientObj->address }}</p>@endif
            <p>
                @if($clientObj?->phone) Phone: {{ $clientObj->phone }} @endif
                @if($clientObj?->email)  | Email: {{ $clientObj->email }} @endif
            </p>
            <h2>Functional Assessment Rating Scale (FARS)</h2>
        </div>

        <div class="info-grid">
            <div><label>Recipient:</label> <span>{{ $admission->patient?->full_name }}</span></div>
            <div><label>Age:</label> <span>{{ $admission->patient?->age ?? '—' }}</span></div>
            <div><label>MRN:</label> <span>{{ $admission->patient?->mrn ?? '—' }}</span></div>
            <div><label>DOB:</label> <span>{{ optional($admission->patient?->date_of_birth)->format('m/d/Y') ?? '—' }}</span></div>
            <div><label>Adm. date:</label> <span>{{ optional($admission->admission_date)->format('m/d/Y') ?? '—' }}</span></div>
            <div><label>Clinic:</label> <span>{{ $admission->clinic?->name ?? '—' }}</span></div>
            <div>
                <label>Eval. type:</label>
                <select name="evaluation_type" required {{ $isSigned ? 'disabled' : '' }}
                        style="font-weight:700;color:#0369a1;background:transparent;border:none;border-bottom:1.5px dashed #94a3b8;padding:4px;">
                    @foreach(\App\Models\Psr\Fars::EVALUATION_TYPES as $k => $v)
                        <option value="{{ $k }}" @selected(old('evaluation_type', $fars->evaluation_type) === $k)>{{ strtoupper($v) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Eval. date:</label>
                <input type="datetime-local" name="evaluation_date" required {{ $isSigned ? 'disabled' : '' }}
                       value="{{ old('evaluation_date', optional($fars->evaluation_date)->format('Y-m-d\TH:i')) }}"
                       style="font-weight:700;color:#0369a1;background:transparent;border:none;border-bottom:1.5px dashed #94a3b8;padding:4px;">
            </div>
            <div style="grid-column: span 2;">
                <label>Substance abuse history:</label>
                <span style="display:flex;gap:18px;font-weight:600;color:#475569;text-transform:none;">
                    <label style="display:flex;gap:5px;cursor:pointer;align-items:center;"><input type="radio" name="substance_abuse_history" value="1" {{ $isSigned ? 'disabled' : '' }} {{ ($fars->substance_abuse_history ?? false) ? 'checked' : '' }}> Yes</label>
                    <label style="display:flex;gap:5px;cursor:pointer;align-items:center;"><input type="radio" name="substance_abuse_history" value="0" {{ $isSigned ? 'disabled' : '' }} {{ ! ($fars->substance_abuse_history ?? false) ? 'checked' : '' }}> No</label>
                </span>
            </div>
        </div>

        <div class="severity-scale">
            <span>1<br>No problem</span>
            <span>2<br>Less than slight</span>
            <span>3<br>Slight</span>
            <span>4<br>Slight-mod</span>
            <span>5<br>Moderate</span>
            <span>6<br>Mod-severe</span>
            <span>7<br>Severe</span>
            <span>8<br>Severe-extreme</span>
            <span>9<br>Extreme</span>
        </div>

        @foreach($farsDomains as $key => $domain)
            @php
                $rating  = old($key, $fars->{$key} ?? 1);
                $checked = $savedIndicators[$key] ?? [];
            @endphp
            <div class="domain-section">
                <div class="domain-header">
                    <span class="domain-title">{{ strtoupper($domain['title']) }}</span>
                    <div class="domain-score">
                        <span>RATING:</span>
                        <input type="number" name="{{ $key }}" min="1" max="9" value="{{ $rating }}"
                               {{ $isSigned ? 'disabled' : '' }} onchange="updateFarsTotal()" class="domain-rating-input">
                    </div>
                </div>
                <div class="indicators-grid">
                    @foreach($domain['indicators'] as $indicator)
                        <label class="indicator-item">
                            <input type="checkbox" name="{{ $key }}_indicators[]" value="{{ $indicator }}"
                                   {{ $isSigned ? 'disabled' : '' }} {{ in_array($indicator, $checked) ? 'checked' : '' }}>
                            <span>{{ $indicator }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="total-section">
            <div>
                <div style="font-size:.9rem;opacity:.85;">Total FARS score</div>
                <div style="font-size:.75rem;opacity:.65;">Sum of all 18 domain ratings (range 18 – 162)</div>
            </div>
            <div class="total-score" id="farsTotalScore">{{ $fars->total_score ?? 18 }}</div>
        </div>

        <div class="mgaf-section">
            <div>
                <strong style="color:#0f172a;">MGAF revised rating</strong>
                <div style="font-size:.85rem;color:#64748b;">Modified Global Assessment of Functioning (1 – 100)</div>
            </div>
            <input type="number" name="mgaf_score" min="1" max="100" {{ $isSigned ? 'disabled' : '' }}
                   value="{{ old('mgaf_score', $fars->mgaf_score ?? 50) }}">
        </div>

        <div class="signature-box {{ $isSigned ? 'locked' : '' }}">
            @if($isSigned)
                <div style="display:flex;align-items:center;gap:10px;color:#16a34a;font-weight:600;">
                    <i data-lucide="check-circle" class="w-6 h-6"></i>
                    <div>
                        <div>Signed by {{ $fars->signedByEmployee?->full_name ?? $fars->signedByUser?->name ?? 'system' }}</div>
                        <div style="font-size:.85rem;font-weight:400;color:#64748b;">{{ optional($fars->signed_at)->format('F j, Y \a\t g:i A') }}</div>
                    </div>
                </div>
            @else
                <p style="font-size:.95rem;line-height:1.5;color:#475569;margin:0 0 16px;">
                    I, <strong>{{ auth()->user()->name }}</strong>, certify that this FARS assessment accurately reflects the patient's current functional status based on clinical observation and interview.
                </p>
                <div style="display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;">
                    <a href="{{ route('clinical.psr.admissions.show', $admission) }}" class="btn btn-secondary">
                        <i data-lucide="x" class="w-4 h-4"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save" class="w-4 h-4"></i> {{ $fars->exists ? 'Save changes' : 'Save FARS' }}
                    </button>
                </div>
            @endif
        </div>
    </form>

    @if($fars->exists && ! $isSigned)
        @can('clinical.psr.assessments.sign')
            <form method="POST" action="{{ route('clinical.psr.assessments.fars.sign', $fars) }}" class="mt-4 text-right">@csrf
                <button class="btn btn-success">
                    <i data-lucide="pen-tool" class="w-4 h-4"></i> Finalize &amp; sign FARS
                </button>
            </form>
        @endcan
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') lucide.createIcons();
    updateFarsTotal();

    // Checking indicators drives the domain rating: 0 checked = 1 (no problem),
    // each additional indicator raises severity by one, capped at 9 (extreme).
    document.querySelectorAll('.domain-section').forEach(section => {
        const rating = section.querySelector('.domain-rating-input');
        if (!rating) return;
        section.querySelectorAll('.indicators-grid input[type=checkbox]').forEach(box => {
            box.addEventListener('change', () => {
                const checked = section.querySelectorAll('.indicators-grid input[type=checkbox]:checked').length;
                rating.value = Math.min(9, checked + 1);
                updateFarsTotal();
            });
        });
    });
});

function updateFarsTotal() {
    const inputs = document.querySelectorAll('.domain-rating-input');
    let total = 0;
    inputs.forEach(i => total += (parseInt(i.value, 10) || 0));
    const out = document.getElementById('farsTotalScore');
    if (out) out.textContent = total;
}
</script>
@endsection
