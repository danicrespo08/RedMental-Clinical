@extends('layouts.app')
@section('title', 'PSR — Progress note')

@section('content')
@php
    $patient = $note->patient;
    $format  = $note->template?->slug ?: 'soap';

    // Same field-label map as the form, so the show view labels match the format chosen.
    $fieldLabels = [
        'subjective'   => ['soap' => 'Subjective',  'dap' => 'Data',       'birp' => 'Behavior', 'girp' => 'Goal'],
        'objective'    => ['soap' => 'Objective',   'dap' => 'Assessment', 'birp' => null,       'girp' => null],
        'intervention' => ['soap' => 'Intervention','dap' => null,         'birp' => 'Intervention', 'girp' => 'Intervention'],
        'response'     => ['soap' => 'Response',    'dap' => null,         'birp' => 'Response', 'girp' => 'Response'],
        'progress'     => ['soap' => 'Progress',    'dap' => null,         'birp' => null,       'girp' => null],
        'plan'         => ['soap' => 'Plan',        'dap' => 'Plan',       'birp' => 'Plan',     'girp' => 'Plan'],
    ];

    $statusBadge = match($note->status){
        'signed'   => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'check-circle', 'Signed'],
        'addendum' => ['bg-blue-50 text-blue-700 border-blue-200',          'file-plus',    'Addendum'],
        default    => ['bg-amber-50 text-amber-700 border-amber-200',       'clock',        'Draft'],
    };
    $riskBadge = match($note->risk_level){
        'high'     => ['bg-rose-50 text-rose-700 border-rose-200', 'alert-triangle'],
        'moderate' => ['bg-amber-50 text-amber-700 border-amber-200', 'alert-circle'],
        'low'      => ['bg-blue-50 text-blue-700 border-blue-200', 'info'],
        default    => ['bg-slate-50 text-slate-500 border-slate-200', 'minus'],
    };
@endphp

<style>
    .pn-section { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.02); margin-bottom:1rem; }
    .pn-hd { padding:.75rem 1.25rem; display:flex; align-items:center; gap:.6rem; border-bottom:1px solid #e2e8f0; background:linear-gradient(180deg,#fff,#fafbff); }
    .pn-hd .pn-num { width:26px; height:26px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:800; color:#fff; flex-shrink:0; background:linear-gradient(135deg,#4338ca,#7c3aed); }
    .pn-hd .pn-title { font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#1e293b; }
    .pn-hd .pn-sub { font-size:.6rem; color:#94a3b8; font-weight:600; margin-top:1px; }
    .pn-body { padding:1.1rem 1.25rem; }

    .narr-block { padding:.85rem 1rem; border-left:3px solid #c7d2fe; background:#eef2ff; border-radius:0 .5rem .5rem 0; margin-bottom:.65rem; }
    .narr-label { font-size:.6rem; font-weight:800; color:#4338ca; text-transform:uppercase; letter-spacing:.05em; }
    .narr-content { font-size:.85rem; color:#334155; line-height:1.6; white-space:pre-wrap; margin-top:.25rem; }

    .stat-card { background:#fff; border:1px solid #e2e8f0; border-radius:.85rem; padding:.85rem 1rem; }
    .stat-label { font-size:.6rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; }
    .stat-value { font-size:1.1rem; font-weight:800; color:#1e293b; line-height:1.2; margin-top:.15rem; }

    .sig-box { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:.75rem; padding:14px 18px; display:flex; justify-content:space-between; align-items:center; }
    .sig-box.draft { background:#fffbeb; border-color:#fde68a; }
</style>

<div class="max-w-7xl mx-auto">
    {{-- HEADER --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('clinical.psr.progress_notes.index') }}" class="w-9 h-9 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-indigo-600 transition-colors border border-slate-200 flex-shrink-0">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-indigo-400 to-violet-600 text-white flex items-center justify-center font-black text-lg shadow-md shadow-indigo-500/25">
                    {{ strtoupper(mb_substr($patient?->first_name ?? '?', 0, 1)) }}{{ strtoupper(mb_substr($patient?->last_name ?? '?', 0, 1)) }}
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest text-indigo-500">PSR · Progress note</div>
                    <h1 class="text-xl font-black text-slate-800">{{ $patient?->full_name ?? '—' }}</h1>
                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                        <span class="font-mono font-bold text-[10px] bg-blue-50 text-blue-600 border border-blue-200 px-2 py-0.5 rounded-md">{{ $patient?->mrn ?? '---' }}</span>
                        <span class="text-slate-200">|</span>
                        <span class="text-[10px] text-slate-400 font-medium">{{ $note->note_date->format('M j, Y') }}</span>
                        @if($note->start_time)
                            <span class="text-slate-200">|</span>
                            <span class="text-[10px] text-slate-400 font-medium">{{ $note->start_time }}@if($note->end_time) – {{ $note->end_time }}@endif</span>
                        @endif
                        <span class="text-slate-200">|</span>
                        <span class="font-mono text-[10px] font-bold bg-violet-50 text-violet-700 border border-violet-200 px-1.5 py-0.5 rounded uppercase">{{ $format }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider border {{ $statusBadge[0] }}">
                    <i data-lucide="{{ $statusBadge[1] }}" class="w-3.5 h-3.5"></i> {{ $statusBadge[2] }}
                </span>
                @if(! $note->is_signed)
                    @can('clinical.psr.progress_notes.edit')
                        <a href="{{ route('clinical.psr.progress_notes.edit', $note) }}" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5">
                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
                        </a>
                    @endcan
                    @can('clinical.psr.progress_notes.sign')
                        <form method="POST" action="{{ route('clinical.psr.progress_notes.sign', $note) }}" class="inline">@csrf
                            <button class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5">
                                <i data-lucide="pen-tool" class="w-3.5 h-3.5"></i> Sign
                            </button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Sidebar --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="pn-section">
                <div class="pn-hd"><div class="pn-num">i</div><div><div class="pn-title">Encounter</div></div></div>
                <div class="pn-body space-y-2 text-[12px]">
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Therapist</span><span class="font-semibold text-slate-700">{{ $note->therapist?->full_name ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Service code</span><span class="font-mono font-semibold text-slate-700">{{ $note->service_code ?: '—' }}{{ $note->modifier ? ' '.$note->modifier : '' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Place of service</span><span class="font-mono font-semibold text-slate-700">{{ $note->place_of_service ?: '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Units</span><span class="font-mono font-semibold text-slate-700">{{ $note->units ?? 0 }}</span></div>
                    @if($note->groupSession)
                        <div class="flex justify-between"><span class="text-slate-400 font-bold">Group session</span><span class="font-semibold text-slate-700 text-right">{{ $note->groupSession->title }}</span></div>
                    @endif
                    @if($note->session_type)
                        <div class="flex justify-between"><span class="text-slate-400 font-bold">Type</span><span class="font-semibold text-slate-700">{{ str_replace('_', ' ', $note->session_type) }}</span></div>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="stat-card">
                    <div class="stat-label">Mood</div>
                    <div class="stat-value">{{ $note->mood ?: '—' }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Affect</div>
                    <div class="stat-value">{{ $note->affect ?: '—' }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Participation</div>
                    <div class="stat-value">{{ $note->participation_level ?: '—' }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Progress rating</div>
                    <div class="stat-value">
                        @if($note->progress_rating)
                            <span class="font-mono text-violet-600">{{ $note->progress_rating }}/5</span>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="pn-section">
                <div class="pn-hd">
                    <div class="pn-num"><i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i></div>
                    <div><div class="pn-title">Risk assessment</div></div>
                </div>
                <div class="pn-body">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold uppercase tracking-wider border {{ $riskBadge[0] }}">
                        <i data-lucide="{{ $riskBadge[1] }}" class="w-3.5 h-3.5"></i> {{ ucfirst($note->risk_level) }}
                    </span>
                    @if($note->risk_notes)
                        <div class="mt-3 text-[12px] text-slate-600 whitespace-pre-line leading-relaxed">{{ $note->risk_notes }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Main content --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="pn-section">
                <div class="pn-hd">
                    <div class="pn-num">1</div>
                    <div>
                        <div class="pn-title">Clinical narrative</div>
                        <div class="pn-sub">{{ strtoupper($format) }} format</div>
                    </div>
                </div>
                <div class="pn-body">
                    @php $hasAny = false; @endphp
                    @foreach($fieldLabels as $field => $labels)
                        @php $label = $labels[$format] ?? null; @endphp
                        @if($label && $note->{$field})
                            @php $hasAny = true; @endphp
                            <div class="narr-block">
                                <div class="narr-label">{{ $label }}</div>
                                <div class="narr-content">{{ $note->{$field} }}</div>
                            </div>
                        @endif
                    @endforeach
                    @unless($hasAny)
                        <p class="text-slate-400 italic text-sm text-center py-6">No clinical narrative documented.</p>
                    @endunless
                </div>
            </div>

            {{-- Signature --}}
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <div class="sig-box {{ $note->is_signed ? '' : 'draft' }}">
                    <div>
                        <div class="font-bold text-sm">{{ $note->signedByEmployee?->full_name ?? $note->signedByUser?->name ?? $note->therapist?->full_name ?? '—' }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">{{ $note->therapist?->credentials ?? '' }}</div>
                    </div>
                    <div class="text-right">
                        @if($note->is_signed)
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-bold uppercase">
                                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Signed {{ $note->signed_at?->format('m/d/Y g:i A') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-amber-100 text-amber-700 rounded-lg text-xs font-bold uppercase">
                                <i data-lucide="clock" class="w-3.5 h-3.5"></i> Draft
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Addendum --}}
            @if($note->is_signed)
                <div class="pn-section">
                    <div class="pn-hd">
                        <div class="pn-num"><i data-lucide="file-plus" class="w-3.5 h-3.5"></i></div>
                        <div><div class="pn-title">Addendum</div><div class="pn-sub">Append a follow-up note — original record stays locked</div></div>
                    </div>
                    <div class="pn-body">
                        @if($note->addendum_text)
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 text-sm text-slate-700 whitespace-pre-line leading-relaxed mb-3">{{ $note->addendum_text }}</div>
                        @endif
                        @can('clinical.psr.progress_notes.sign')
                            <form method="POST" action="{{ route('clinical.psr.progress_notes.addendum', $note) }}" class="flex gap-2">@csrf
                                <textarea name="addendum_text" rows="2" required placeholder="Add an addendum…" class="flex-1 px-3 py-2 border border-slate-300 rounded-lg text-sm"></textarea>
                                <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold uppercase tracking-wider rounded-lg self-end inline-flex items-center gap-1.5">
                                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Append
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
