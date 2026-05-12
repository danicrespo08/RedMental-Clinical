@extends('layouts.app')
@section('title', 'PSR — Progress note')

@section('content')
@php
    $isLocked = $note->is_signed && $note->status !== 'addendum';
    $defaultFormat = optional($note->template)->slug ?: 'soap';
    // Map: form-field => [format-key => human label]. A null value means the
    // field is hidden for that format. The form always submits all SOAP-style
    // columns; only the labelled ones are visible for the chosen format.
    $fieldLabels = [
        'subjective'   => ['soap' => 'Subjective',  'dap' => 'Data',       'birp' => 'Behavior', 'girp' => 'Goal'],
        'objective'    => ['soap' => 'Objective',   'dap' => 'Assessment', 'birp' => null,       'girp' => null],
        'intervention' => ['soap' => 'Intervention','dap' => null,         'birp' => 'Intervention', 'girp' => 'Intervention'],
        'response'     => ['soap' => 'Response',    'dap' => null,         'birp' => 'Response', 'girp' => 'Response'],
        'progress'     => ['soap' => 'Progress',    'dap' => null,         'birp' => null,       'girp' => null],
        'plan'         => ['soap' => 'Plan',        'dap' => 'Plan',       'birp' => 'Plan',     'girp' => 'Plan'],
    ];
    $patient = $note->patient ?? optional($note->admission)->patient;
@endphp

<style>
    .pn-section { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.02); margin-bottom:1rem; }
    .pn-hd { padding:.75rem 1.25rem; display:flex; align-items:center; gap:.6rem; border-bottom:1px solid #e2e8f0; background:linear-gradient(180deg,#fff,#fafbff); }
    .pn-hd .pn-num { width:26px; height:26px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:800; color:#fff; flex-shrink:0; background:linear-gradient(135deg,#4338ca,#7c3aed); }
    .pn-hd .pn-title { font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#1e293b; }
    .pn-hd .pn-sub { font-size:.6rem; color:#94a3b8; font-weight:600; margin-top:1px; }
    .pn-body { padding:1.1rem 1.25rem; }

    .pn-label { display:block; font-size:.65rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.3rem; }
    .pn-input, .pn-select, .pn-textarea {
        width:100%; padding:.55rem .75rem; border:1px solid #e2e8f0; border-radius:.55rem;
        font-size:.85rem; color:#1e293b; background:#fff; transition:all .15s;
    }
    .pn-input:focus, .pn-select:focus, .pn-textarea:focus {
        outline:none; border-color:#4338ca; box-shadow:0 0 0 3px rgba(67,56,202,.08);
    }
    .pn-textarea { min-height:96px; resize:vertical; line-height:1.6; }
    .pn-mono { font-family:'JetBrains Mono', ui-monospace, monospace; }

    .ai-btn {
        background:linear-gradient(135deg, #0ea5e9, #6366f1); color:#fff;
        border:none; border-radius:.6rem; padding:9px 16px;
        font-size:.72rem; font-weight:800; cursor:pointer;
        text-transform:uppercase; letter-spacing:.05em;
        display:inline-flex; align-items:center; gap:6px;
        box-shadow:0 3px 10px rgba(14,165,233,.28); transition:all .2s;
    }
    .ai-btn:hover:not(:disabled) { transform:translateY(-1px); box-shadow:0 5px 14px rgba(14,165,233,.36); }
    .ai-btn:disabled { opacity:.55; cursor:wait; }

    .fmt-pill {
        padding:7px 16px; border-radius:999px;
        font-size:.7rem; font-weight:800; letter-spacing:.05em;
        text-transform:uppercase; cursor:pointer;
        border:1.5px solid #e2e8f0; background:#fff; color:#64748b;
        transition:all .15s; font-family:inherit;
    }
    .fmt-pill:hover { background:#f8fafc; }
    .fmt-pill.active { border-color:#4338ca; background:linear-gradient(135deg,#eef2ff,#ede9fe); color:#4338ca; box-shadow:0 1px 3px rgba(67,56,202,.15); }

    .risk-pill {
        display:inline-block; padding:6px 12px; border-radius:.5rem;
        font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em;
        cursor:pointer; border:1.5px solid #e2e8f0; background:#fff;
        transition:all .15s;
    }
    .risk-pill input { display:none; }
    .risk-pill.r-none.active     { border-color:#94a3b8; background:#f1f5f9; color:#475569; }
    .risk-pill.r-low.active      { border-color:#3b82f6; background:#dbeafe; color:#1e40af; }
    .risk-pill.r-moderate.active { border-color:#f59e0b; background:#fef3c7; color:#92400e; }
    .risk-pill.r-high.active     { border-color:#ef4444; background:#fee2e2; color:#991b1b; }
</style>

<div class="max-w-6xl mx-auto"
     x-data="{
        format: '{{ $defaultFormat }}',
        risk: '{{ old('risk_level', $note->risk_level ?? 'none') }}',
        aiBusy: false,
        aiUrl: '{{ route('clinical.psr.progress_notes.ai_suggest') }}',
        admissionId: '{{ $note->psr_admission_id ?? '' }}',
        async aiSuggest() {
            if (this.aiBusy) return;
            const form = this.$root.querySelector('form');
            if (!form) return;
            const fd = new FormData(form);
            const aid = fd.get('psr_admission_id') || this.admissionId;
            if (!aid) {
                window.RM ? RM.toast('error', 'Choose an admission first.') : alert('Choose an admission first.');
                return;
            }
            this.aiBusy = true;
            try {
                const res = await fetch(this.aiUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content
                            || form.querySelector('input[name=_token]').value,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        admission_id: aid,
                        format: this.format,
                        session_type: fd.get('session_type') || 'group_therapy',
                    }),
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                if (data.error) {
                    window.RM ? RM.toast('error', data.error) : alert(data.error);
                    return;
                }
                const map = {
                    soap: { subjective:'subjective', objective:'objective', intervention:'intervention', response:'response', progress:'progress', plan:'plan' },
                    dap:  { data:'subjective', assessment:'objective', plan:'plan' },
                    birp: { behavior:'subjective', intervention:'intervention', response:'response', plan:'plan' },
                    girp: { goal:'subjective', intervention:'intervention', response:'response', plan:'plan' },
                };
                const fmtMap = map[this.format] || map.soap;
                Object.entries(data).forEach(([key, val]) => {
                    const colName = fmtMap[key] || key;
                    const el = form.querySelector(`[name='${colName}']`);
                    if (el && (!el.value || !el.value.trim())) el.value = val;
                });
                const note = data._source === 'mock'
                    ? 'AI draft ready (offline mode — review before saving).'
                    : 'AI draft ready — review before saving.';
                window.RM ? RM.toast('success', note) : null;
            } catch (e) {
                window.RM ? RM.toast('error', 'AI request failed: ' + e.message) : alert(e.message);
            } finally {
                this.aiBusy = false;
            }
        },
     }">

    {{-- HEADER --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('clinical.psr.progress_notes.index') }}" class="w-9 h-9 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-indigo-600 transition-colors border border-slate-200 flex-shrink-0">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div class="p-2.5 bg-gradient-to-br from-indigo-500 to-violet-700 text-white rounded-xl shadow-md shadow-indigo-500/25 flex-shrink-0">
                    <i data-lucide="file-text" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <div class="text-xs font-bold uppercase tracking-widest text-indigo-500">PSR · Progress note</div>
                    <h1 class="text-xl font-black text-slate-800 truncate">{{ $note->exists ? 'Edit progress note' : 'New progress note' }}</h1>
                    @if($patient)
                        <p class="text-[11px] text-slate-500 font-semibold mt-0.5">{{ $patient->full_name }} — MRN {{ $patient->mrn ?? '---' }}</p>
                    @endif
                </div>
            </div>

            @unless($isLocked)
                <button type="button" class="ai-btn" :disabled="aiBusy" @click="aiSuggest">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                    <span x-text="aiBusy ? 'Drafting…' : 'AI suggest note'"></span>
                </button>
            @endunless
        </div>
    </div>

    {{-- 1. Format selector --}}
    <div class="pn-section">
        <div class="pn-hd">
            <div class="pn-num">1</div>
            <div>
                <div class="pn-title">Documentation format</div>
                <div class="pn-sub">Switch between SOAP, DAP, BIRP, GIRP — labels rotate to match</div>
            </div>
        </div>
        <div class="pn-body flex flex-wrap items-center gap-2">
            @foreach(['soap' => 'SOAP', 'dap' => 'DAP', 'birp' => 'BIRP', 'girp' => 'GIRP'] as $key => $label)
                <button type="button" class="fmt-pill" :class="format === '{{ $key }}' && 'active'"
                        @click="format = '{{ $key }}'">{{ $label }}</button>
            @endforeach
            <span class="text-[10px] text-slate-400 ml-auto">Selecting a template below auto-applies its format.</span>
        </div>
    </div>

    <form method="POST" action="{{ $note->exists ? route('clinical.psr.progress_notes.update', $note) : route('clinical.psr.progress_notes.store') }}">
        @csrf
        @if($note->exists) @method('PUT') @endif

        {{-- 2. Encounter info --}}
        <div class="pn-section">
            <div class="pn-hd">
                <div class="pn-num">2</div>
                <div>
                    <div class="pn-title">Encounter</div>
                    <div class="pn-sub">Patient · therapist · date / time · billing codes</div>
                </div>
            </div>
            <div class="pn-body grid grid-cols-2 md:grid-cols-3 gap-3">
                <div>
                    <label class="pn-label">Admission *</label>
                    <select name="psr_admission_id" required class="pn-select" x-model="admissionId">
                        <option value="">—</option>
                        @foreach($admissions as $a)<option value="{{ $a->id }}" @selected(old('psr_admission_id', $note->psr_admission_id) == $a->id)>{{ $a->patient?->full_name }} · {{ optional($a->admission_date)->format('Y-m-d') }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="pn-label">Patient *</label>
                    <select name="patient_id" required class="pn-select">
                        @foreach($admissions as $a)<option value="{{ $a->patient_id }}" @selected(old('patient_id', $note->patient_id) == $a->patient_id)>{{ $a->patient?->full_name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="pn-label">Group session</label>
                    <select name="psr_group_session_id" class="pn-select">
                        <option value="">— None —</option>
                        @foreach($sessions as $s)<option value="{{ $s->id }}" @selected(old('psr_group_session_id', $note->psr_group_session_id) == $s->id)>{{ $s->session_date->format('M j') }} · {{ $s->title }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="pn-label">Note template</label>
                    <select name="note_template_id" class="pn-select"
                            @change="(() => { const opt = $event.target.options[$event.target.selectedIndex]; if (opt.dataset.slug) format = opt.dataset.slug; })()">
                        <option value="">— None —</option>
                        @foreach($templates as $tpl)<option value="{{ $tpl->id }}" data-slug="{{ $tpl->slug }}" @selected(old('note_template_id', $note->note_template_id) == $tpl->id)>{{ $tpl->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="pn-label">Therapist *</label>
                    <select name="therapist_id" required class="pn-select">
                        <option value="">—</option>
                        @foreach($therapists as $t)<option value="{{ $t->id }}" @selected(old('therapist_id', $note->therapist_id) == $t->id)>{{ $t->full_name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="pn-label">Date *</label>
                    <input type="date" name="note_date" required value="{{ old('note_date', optional($note->note_date)->format('Y-m-d') ?? now()->toDateString()) }}" class="pn-input">
                </div>
                <div>
                    <label class="pn-label">Start time</label>
                    <input type="time" name="start_time" value="{{ old('start_time', $note->start_time) }}" class="pn-input">
                </div>
                <div>
                    <label class="pn-label">End time</label>
                    <input type="time" name="end_time" value="{{ old('end_time', $note->end_time) }}" class="pn-input">
                </div>
                <div>
                    <label class="pn-label">Units</label>
                    <input type="number" name="units" min="0" value="{{ old('units', $note->units ?? 0) }}" class="pn-input pn-mono">
                </div>
                <div>
                    <label class="pn-label">Service code</label>
                    <input type="text" name="service_code" value="{{ old('service_code', $note->service_code) }}" class="pn-input pn-mono" placeholder="H2017">
                </div>
                <div>
                    <label class="pn-label">Modifier</label>
                    <input type="text" name="modifier" value="{{ old('modifier', $note->modifier) }}" class="pn-input pn-mono">
                </div>
                <div>
                    <label class="pn-label">Place of service</label>
                    <input type="text" name="place_of_service" value="{{ old('place_of_service', $note->place_of_service) }}" class="pn-input pn-mono" placeholder="11">
                </div>
            </div>
        </div>

        {{-- 3. Note body --}}
        <div class="pn-section">
            <div class="pn-hd">
                <div class="pn-num">3</div>
                <div>
                    <div class="pn-title">Clinical narrative</div>
                    <div class="pn-sub" x-text="format.toUpperCase() + ' format — fill the visible sections only'"></div>
                </div>
            </div>
            <div class="pn-body grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($fieldLabels as $field => $labels)
                    @php
                        $visibleConditions = collect($labels)
                            ->filter(fn ($lbl) => $lbl !== null)
                            ->map(fn ($lbl, $fmt) => "format === '{$fmt}'")
                            ->values()
                            ->implode(' || ') ?: 'false';
                    @endphp
                    <div x-show="{{ $visibleConditions }}">
                        <label class="pn-label">
                            @foreach($labels as $fmt => $lbl)
                                @if($lbl)<span x-show="format === '{{ $fmt }}'">{{ $lbl }}</span>@endif
                            @endforeach
                        </label>
                        <textarea name="{{ $field }}" class="pn-textarea">{{ old($field, $note->{$field}) }}</textarea>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 4. Mental status --}}
        <div class="pn-section">
            <div class="pn-hd">
                <div class="pn-num">4</div>
                <div>
                    <div class="pn-title">Mental status &amp; engagement</div>
                    <div class="pn-sub">Mood, affect, participation, progress rating</div>
                </div>
            </div>
            <div class="pn-body grid grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                    <label class="pn-label">Mood</label>
                    <input type="text" name="mood" maxlength="50" value="{{ old('mood', $note->mood) }}" class="pn-input">
                </div>
                <div>
                    <label class="pn-label">Affect</label>
                    <input type="text" name="affect" maxlength="50" value="{{ old('affect', $note->affect) }}" class="pn-input">
                </div>
                <div>
                    <label class="pn-label">Participation</label>
                    <input type="text" name="participation_level" maxlength="30" value="{{ old('participation_level', $note->participation_level) }}" class="pn-input">
                </div>
                <div>
                    <label class="pn-label">Progress rating (1-5)</label>
                    <input type="number" name="progress_rating" min="1" max="5" value="{{ old('progress_rating', $note->progress_rating) }}" class="pn-input pn-mono">
                </div>
                <div class="col-span-2 md:col-span-2">
                    <label class="pn-label">Session type</label>
                    <input type="text" name="session_type" maxlength="50" value="{{ old('session_type', $note->session_type) }}" class="pn-input" placeholder="group_therapy / individual / family">
                </div>
                <div class="col-span-2">
                    <label class="pn-label">Status *</label>
                    <select name="status" required class="pn-select">
                        @foreach($statuses as $k => $v)<option value="{{ $k }}" @selected(old('status', $note->status) === $k)>{{ $v }}</option>@endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- 5. Risk --}}
        <div class="pn-section">
            <div class="pn-hd">
                <div class="pn-num">5</div>
                <div>
                    <div class="pn-title">Risk assessment</div>
                    <div class="pn-sub">Required — escalate to high if patient verbalizes SI/HI or showed risk during session</div>
                </div>
            </div>
            <div class="pn-body">
                <div class="flex flex-wrap gap-2 mb-3">
                    @foreach($risks as $k => $v)
                        <label class="risk-pill r-{{ $k }}" :class="risk === '{{ $k }}' && 'active'">
                            <input type="radio" name="risk_level" value="{{ $k }}" required x-model="risk" @checked(old('risk_level', $note->risk_level) === $k)>
                            {{ $v }}
                        </label>
                    @endforeach
                </div>
                <label class="pn-label">Risk notes</label>
                <textarea name="risk_notes" class="pn-textarea" placeholder="Document any risk indicators observed during the session, plan to mitigate, escalation contacts, etc.">{{ old('risk_notes', $note->risk_notes) }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 pt-2 pb-6">
            <p class="text-[11px] text-slate-400">
                <i data-lucide="info" class="w-3 h-3 inline-block"></i>
                Save as draft to continue editing — sign to lock the note.
            </p>
            <div class="flex items-center gap-2">
                <a href="{{ route('clinical.psr.progress_notes.index') }}" class="px-4 py-2 border border-slate-300 text-slate-700 text-sm font-semibold rounded-lg hover:bg-slate-50">Cancel</a>
                <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5 shadow-md shadow-indigo-500/25">
                    <i data-lucide="save" class="w-4 h-4"></i> {{ $note->exists ? 'Save changes' : 'Save as draft' }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
