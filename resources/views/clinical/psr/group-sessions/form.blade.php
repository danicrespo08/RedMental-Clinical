@extends('layouts.app')
@section('title', $session->exists ? 'PSR — Edit Group Session' : 'PSR — New Group Session')

@section('content')

<style>
    .paper-doc {
        background: #fff; border: 1px solid #e2e8f0;
        padding: 38px 46px;
        font-family: 'DM Sans', 'Segoe UI', sans-serif;
        color: #1e293b;
        box-shadow: 0 8px 30px -8px rgba(0,0,0,.06);
        margin: 0 auto 20px; max-width: 1100px;
        border-radius: 1rem; position: relative;
    }
    .paper-doc::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
        background: linear-gradient(90deg, #4338ca, #7c3aed, #4338ca);
        border-radius: 1rem 1rem 0 0;
    }

    .section-title {
        display: flex; align-items: center; gap: 10px;
        font-size: .92rem; font-weight: 800; color: #4338ca;
        text-transform: uppercase; letter-spacing: .05em;
        border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;
        margin: 24px 0 16px;
    }
    .section-title .num {
        width: 28px; height: 28px; border-radius: 50%;
        background: linear-gradient(135deg, #4338ca, #7c3aed); color: #fff;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .82rem; font-weight: 800;
    }

    .field-label {
        display: block; font-size: .65rem; font-weight: 800;
        color: #475569; text-transform: uppercase; letter-spacing: .04em;
        margin-bottom: .35rem;
    }
    .field-input {
        width: 100%; border: 1.5px solid #e2e8f0; border-radius: .65rem;
        padding: .55rem .8rem; font-size: .85rem; font-weight: 600;
        background: #f8fafc; color: #1e293b; transition: all .2s;
        font-family: inherit;
    }
    .field-input:focus { outline: none; border-color: #4338ca; box-shadow: 0 0 0 3px rgba(67,56,202,.08); background: #fff; }

    .att-row {
        display: grid; grid-template-columns: 32px 32px 1fr 130px 80px 88px 88px 1fr;
        gap: 10px; align-items: center;
        padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: .65rem;
        background: #fff; margin-bottom: 6px; transition: all .15s;
    }
    .att-row:hover { background: #fafbff; border-color: #c7d2fe; }
    .att-row.included { background: linear-gradient(180deg, #fefce8, #fff); border-color: #fde68a; }
    @media (max-width: 1024px) { .att-row { grid-template-columns: 1fr; gap: 6px; } }

    .pat-avatar {
        width: 32px; height: 32px; border-radius: .55rem;
        display: flex; align-items: center; justify-content: center;
        font-size: .68rem; font-weight: 900; flex-shrink: 0; letter-spacing: .02em;
    }

    .btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 22px; border-radius: .65rem; font-weight: 700; font-size: .85rem;
        cursor: pointer; transition: all .2s; text-transform: uppercase; letter-spacing: .03em;
        border: 1px solid transparent; text-decoration: none;
    }
    .btn-secondary { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
    .btn-secondary:hover { background: #e2e8f0; }
    .btn-primary { background: linear-gradient(135deg, #4338ca, #7c3aed); color: #fff; box-shadow: 0 4px 12px rgba(67,56,202,.25); }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(67,56,202,.32); }

    @media (max-width: 768px) { .paper-doc { padding: 22px; } }
</style>

@php
    $clientObj = auth()->user()->client;
    $avatarColors = ['bg-indigo-100 text-indigo-600', 'bg-emerald-100 text-emerald-600', 'bg-amber-100 text-amber-600', 'bg-rose-100 text-rose-600', 'bg-sky-100 text-sky-600', 'bg-violet-100 text-violet-600', 'bg-teal-100 text-teal-600', 'bg-pink-100 text-pink-600'];
@endphp

<div class="max-w-6xl mx-auto">

    <div class="flex items-center gap-4 mb-6 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <a href="{{ route('clinical.psr.group_sessions.index') }}" class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors border border-slate-200 flex-shrink-0">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div class="p-2.5 bg-gradient-to-br from-indigo-500 to-violet-600 text-white rounded-xl flex-shrink-0 shadow-lg shadow-indigo-500/30"><i data-lucide="users" class="w-6 h-6"></i></div>
        <div class="flex-1 min-w-0">
            <h1 class="text-lg font-black text-slate-800 tracking-tight uppercase">{{ $session->exists ? 'Edit group session' : 'New group session' }}</h1>
            <p class="text-xs text-slate-500 font-medium mt-0.5">Schedule, roster, attendance &amp; activities</p>
        </div>
    </div>

    <form method="POST" action="{{ $session->exists ? route('clinical.psr.group_sessions.update', $session) : route('clinical.psr.group_sessions.store') }}"
          class="paper-doc"
          x-data="{ activities: @js($session->activities ?? []) }">
        @csrf
        @if($session->exists) @method('PUT') @endif

        <div class="section-title"><span class="num">1</span> When &amp; where</div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="field-label">Title *</label>
                <input type="text" name="title" required value="{{ old('title', $session->title) }}"
                       placeholder="E.g. Morning PSR Group — Cognitive reframing"
                       class="field-input">
            </div>
            <div>
                <label class="field-label">Status *</label>
                <select name="status" required class="field-input">
                    @foreach($statuses as $k => $v)<option value="{{ $k }}" @selected(old('status', $session->status) === $k)>{{ $v }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="field-label">Clinic *</label>
                <select name="clinic_id" required class="field-input">
                    <option value="">— Select clinic —</option>
                    @foreach($clinics as $c)<option value="{{ $c->id }}" @selected(old('clinic_id', $session->clinic_id) == $c->id)>{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="field-label">Session date *</label>
                <input type="date" name="session_date" required value="{{ old('session_date', optional($session->session_date)->format('Y-m-d')) }}" class="field-input">
            </div>
            <div>
                <label class="field-label">Capacity *</label>
                <input type="number" name="max_capacity" min="1" max="50" required value="{{ old('max_capacity', $session->max_capacity ?? 10) }}" class="field-input">
            </div>
            <div>
                <label class="field-label">Start time *</label>
                <input type="time" name="start_time" required value="{{ old('start_time', $session->start_time) }}" class="field-input">
            </div>
            <div>
                <label class="field-label">End time *</label>
                <input type="time" name="end_time" required value="{{ old('end_time', $session->end_time) }}" class="field-input">
            </div>
            <div>
                <label class="field-label">Session type *</label>
                <input type="text" name="session_type" required value="{{ old('session_type', $session->session_type ?? 'group_therapy') }}"
                       placeholder="e.g. group_therapy" class="field-input">
            </div>
        </div>

        <div class="section-title"><span class="num">2</span> Staff</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="field-label">Lead therapist *</label>
                <select name="lead_therapist_id" required class="field-input">
                    <option value="">—</option>
                    @foreach($therapists as $t)<option value="{{ $t->id }}" @selected(old('lead_therapist_id', $session->lead_therapist_id) == $t->id)>{{ $t->full_name }}@if($t->position) — {{ $t->position }}@endif</option>@endforeach
                </select>
            </div>
            <div>
                <label class="field-label">Co-therapist (optional)</label>
                <select name="co_therapist_id" class="field-input">
                    <option value="">—</option>
                    @foreach($therapists as $t)<option value="{{ $t->id }}" @selected(old('co_therapist_id', $session->co_therapist_id) == $t->id)>{{ $t->full_name }}</option>@endforeach
                </select>
            </div>
        </div>

        <div class="section-title"><span class="num">3</span> Billing codes</div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="field-label">Service code (CPT/HCPCS) *</label>
                <input type="text" name="service_code" required value="{{ old('service_code', $session->service_code ?? 'H2017') }}"
                       class="field-input" style="font-family:ui-monospace,monospace;">
            </div>
            <div>
                <label class="field-label">Modifier</label>
                <input type="text" name="modifier" maxlength="20" value="{{ old('modifier', $session->modifier) }}"
                       class="field-input" style="font-family:ui-monospace,monospace;">
            </div>
            <div>
                <label class="field-label">Place of service *</label>
                <input type="text" name="place_of_service" maxlength="10" required value="{{ old('place_of_service', $session->place_of_service ?? '11') }}"
                       class="field-input" style="font-family:ui-monospace,monospace;">
            </div>
        </div>

        <div class="section-title"><span class="num">4</span> Break (optional)</div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="field-label">Break start</label>
                <input type="time" name="break_start_time" value="{{ old('break_start_time', $session->break_start_time) }}" class="field-input">
            </div>
            <div>
                <label class="field-label">Break end</label>
                <input type="time" name="break_end_time" value="{{ old('break_end_time', $session->break_end_time) }}" class="field-input">
            </div>
            <div>
                <label class="field-label">Break minutes</label>
                <input type="number" name="break_minutes" min="0" max="240" value="{{ old('break_minutes', $session->break_minutes ?? 0) }}" class="field-input">
            </div>
        </div>

        <div class="section-title" style="justify-content:space-between;">
            <span style="display:flex;align-items:center;gap:10px;"><span class="num">5</span> Session activities</span>
            <button type="button" @click="activities.push({ minute: activities.length ? (activities[activities.length-1].minute + activities[activities.length-1].duration) : 0, duration: 30, activity: '' })"
                    class="btn btn-secondary" style="padding:6px 14px;font-size:.65rem;">
                <i data-lucide="plus" class="w-3 h-3"></i> Add activity
            </button>
        </div>
        <p class="text-xs text-slate-500 mb-3" style="font-family:sans-serif;text-transform:none;">Curriculum-style breakdown of the session. Each row = one activity with start minute and duration.</p>
        <template x-for="(act, ai) in activities" :key="ai">
            <div style="display:grid;grid-template-columns:90px 90px 1fr 32px;gap:10px;margin-bottom:6px;align-items:end;">
                <div>
                    <label class="field-label">Start (min)</label>
                    <input type="number" :name="`activities[${ai}][minute]`" x-model.number="act.minute" min="0" class="field-input" style="font-family:ui-monospace,monospace;font-weight:700;">
                </div>
                <div>
                    <label class="field-label">Duration</label>
                    <input type="number" :name="`activities[${ai}][duration]`" x-model.number="act.duration" min="1" class="field-input" style="font-family:ui-monospace,monospace;font-weight:700;">
                </div>
                <div>
                    <label class="field-label">Activity</label>
                    <input type="text" :name="`activities[${ai}][activity]`" x-model="act.activity" placeholder="E.g. Group exercise — cognitive reframing" class="field-input">
                </div>
                <button type="button" @click="activities.splice(ai, 1)" class="btn btn-secondary" style="padding:8px 10px;font-size:1rem;line-height:1;align-self:end;">×</button>
            </div>
        </template>
        <template x-if="activities.length === 0">
            <p class="text-xs text-slate-400 italic" style="text-transform:none;">No activities yet — click "Add activity" to start.</p>
        </template>

        <div class="section-title"><span class="num">6</span> Summary &amp; notes</div>
        <div>
            <label class="field-label">Session summary</label>
            <textarea name="session_summary" rows="3" placeholder="Clinical summary of what happened during the session — themes covered, group dynamics, key observations…"
                      class="field-input" style="resize:vertical;line-height:1.55;">{{ old('session_summary', $session->session_summary) }}</textarea>
        </div>
        <div class="mt-3">
            <label class="field-label">Internal notes</label>
            <textarea name="notes" rows="2" placeholder="Operational notes (room change, supplies needed, etc.)"
                      class="field-input" style="resize:vertical;line-height:1.55;">{{ old('notes', $session->notes) }}</textarea>
        </div>

        <div class="section-title"><span class="num">7</span> Attendees roster</div>
        <p class="text-xs text-slate-500 mb-3" style="font-family:sans-serif;text-transform:none;">
            Tick "include" to mark attendance. Patients shown are admitted PSR patients. Units default to 16 (4 hrs × 4 units/hr).
        </p>

        <div class="space-y-1">
            @foreach($admissions as $idx => $adm)
                @php
                    $existing = $session->exists ? $session->attendees->firstWhere('psr_admission_id', $adm->id) : null;
                    $color = $avatarColors[$idx % count($avatarColors)];
                    $initials = strtoupper(mb_substr($adm->patient?->first_name ?? '?', 0, 1) . mb_substr($adm->patient?->last_name ?? '?', 0, 1));
                @endphp
                <label class="att-row {{ $existing ? 'included' : '' }}" data-row>
                    <input type="hidden" name="attendees[{{ $idx }}][psr_admission_id]" value="{{ $adm->id }}">
                    @if($existing)<input type="hidden" name="attendees[{{ $idx }}][id]" value="{{ $existing->id }}">@endif
                    <input type="checkbox" name="attendees[{{ $idx }}][include]" value="1" @checked($existing) class="rounded">
                    <div class="pat-avatar {{ $color }}">{{ $initials }}</div>
                    <div class="min-w-0">
                        <div class="font-bold text-slate-800 text-sm truncate">{{ $adm->patient?->full_name ?? '—' }}</div>
                        <div class="text-[10px] text-slate-400 font-mono">{{ $adm->patient?->mrn ?: '—' }}</div>
                    </div>
                    <select name="attendees[{{ $idx }}][attendance_status]" class="field-input" style="padding:4px 6px;font-size:.7rem;">
                        @foreach($attendance as $k => $v)<option value="{{ $k }}" @selected($existing?->attendance_status === $k)>{{ $v }}</option>@endforeach
                    </select>
                    <input type="number" name="attendees[{{ $idx }}][units]" min="0" value="{{ $existing->units ?? 16 }}"
                           class="field-input" style="padding:4px 6px;font-size:.75rem;font-family:ui-monospace,monospace;font-weight:700;text-align:center;">
                    <input type="time" name="attendees[{{ $idx }}][check_in_time]" value="{{ $existing?->check_in_time }}"
                           class="field-input" style="padding:4px 6px;font-size:.7rem;">
                    <input type="time" name="attendees[{{ $idx }}][check_out_time]" value="{{ $existing?->check_out_time }}"
                           class="field-input" style="padding:4px 6px;font-size:.7rem;">
                    <input type="text" name="attendees[{{ $idx }}][individual_notes]" value="{{ $existing?->individual_notes }}"
                           placeholder="Individual note…" class="field-input" style="padding:4px 8px;font-size:.7rem;">
                </label>
            @endforeach
        </div>

        <div class="flex justify-end gap-2 pt-5 mt-6 border-t border-slate-100">
            <a href="{{ route('clinical.psr.group_sessions.index') }}" class="btn btn-secondary">
                <i data-lucide="x" class="w-4 h-4"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save" class="w-4 h-4"></i> {{ $session->exists ? 'Save changes' : 'Create session' }}
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // Toggle "included" highlight when checkbox flips
    document.querySelectorAll('[data-row]').forEach(row => {
        const cb = row.querySelector('input[type=checkbox]');
        if (! cb) return;
        cb.addEventListener('change', () => row.classList.toggle('included', cb.checked));
    });
});
</script>
@endsection
