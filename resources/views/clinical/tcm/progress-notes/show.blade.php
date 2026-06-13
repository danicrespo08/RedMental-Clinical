@extends('layouts.app')
@section('title', 'TCM — Progress note')

@section('content')
@php
    $patient = $note->admission?->patient;
    $sb = match($note->status){
        'signed'   => ['bg-emerald-50 text-emerald-700 border-emerald-300', 'check-circle', 'Signed'],
        'addendum' => ['bg-blue-50 text-blue-700 border-blue-300', 'file-plus', 'Addendum'],
        default    => ['bg-amber-50 text-amber-700 border-amber-300', 'pencil', 'Draft'],
    };
    $riskColor = ['none'=>'text-slate-500 bg-slate-50 border-slate-200','low'=>'text-emerald-600 bg-emerald-50 border-emerald-200','moderate'=>'text-amber-600 bg-amber-50 border-amber-200','high'=>'text-rose-600 bg-rose-50 border-rose-200'][$note->risk_level] ?? 'text-slate-500 bg-slate-50 border-slate-200';
    $sections = [
        'Summary' => $note->summary, 'Interventions' => $note->interventions,
        'Coordination' => $note->coordination, 'Progress' => $note->progress, 'Plan / next steps' => $note->plan,
    ];
@endphp

<div class="max-w-4xl mx-auto">
    <a href="{{ route('clinical.tcm.progress_notes.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-orange-600 mb-3">
        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to notes
    </a>

    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <div class="flex items-start justify-between gap-3 mb-5 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-400 to-orange-600 text-white flex items-center justify-center font-black">
                    {{ strtoupper(mb_substr($patient->first_name ?? '?', 0, 1)) }}{{ strtoupper(mb_substr($patient->last_name ?? '?', 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <h1 class="text-lg font-black text-slate-800 truncate">{{ $patient?->full_name ?? '—' }}</h1>
                    <p class="text-[11px] text-slate-500 font-semibold">{{ $note->note_date->format('F j, Y') }} · {{ $noteTypes[$note->note_type] ?? $note->note_type }} · {{ $note->caseManager?->full_name ?? '—' }}</p>
                </div>
            </div>
            <div class="flex flex-col items-end gap-1.5">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider border {{ $sb[0] }}">
                    <i data-lucide="{{ $sb[1] }}" class="w-3 h-3"></i> {{ $sb[2] }}
                </span>
                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase border {{ $riskColor }}">Risk: {{ $riskLevels[$note->risk_level] ?? $note->risk_level }}</span>
            </div>
        </div>

        <div class="space-y-4">
            @foreach($sections as $label => $value)
                @if(filled($value))
                    <div>
                        <div class="text-[11px] font-black uppercase tracking-wider text-orange-600 mb-1">{{ $label }}</div>
                        <p class="text-[13px] text-slate-700 whitespace-pre-line leading-relaxed">{{ $value }}</p>
                    </div>
                @endif
            @endforeach
            @if(filled($note->risk_notes))
                <div><div class="text-[11px] font-black uppercase tracking-wider text-rose-600 mb-1">Risk notes</div><p class="text-[13px] text-slate-700 whitespace-pre-line leading-relaxed">{{ $note->risk_notes }}</p></div>
            @endif
            @if(filled($note->goals_addressed))
                <div><div class="text-[11px] font-black uppercase tracking-wider text-slate-500 mb-1">Goals addressed</div><p class="text-[13px] text-slate-700 whitespace-pre-line leading-relaxed">{{ $note->goals_addressed }}</p></div>
            @endif
        </div>

        @if($note->is_signed)
            <div class="mt-5 pt-4 border-t border-slate-100 flex items-center gap-2 text-emerald-600 text-[13px] font-semibold">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
                Signed by {{ $note->signedByEmployee?->full_name ?? $note->signedByUser?->name ?? 'system' }} on {{ optional($note->signed_at)->format('F j, Y \a\t g:i A') }}
            </div>
        @endif

        @if(filled($note->addendum_text))
            <div class="mt-4 bg-blue-50 border border-blue-200 rounded-xl p-4">
                <div class="text-[11px] font-black uppercase tracking-wider text-blue-700 mb-1">Addendum · {{ optional($note->addendum_date)->format('M j, Y') }} · {{ $note->addendumBy?->name }}</div>
                <p class="text-[13px] text-slate-700 whitespace-pre-line leading-relaxed">{{ $note->addendum_text }}</p>
            </div>
        @endif
    </div>

    {{-- Actions --}}
    <div class="flex flex-wrap items-center justify-end gap-2 mt-4">
        @can('clinical.tcm.progress_notes.edit')
            @unless($note->is_signed)
                <a href="{{ route('clinical.tcm.progress_notes.edit', $note) }}" class="px-4 py-2 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5">
                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
                </a>
            @endunless
        @endcan
        @can('clinical.tcm.progress_notes.sign')
            @unless($note->is_signed)
                <form method="POST" action="{{ route('clinical.tcm.progress_notes.sign', $note) }}">@csrf
                    <button class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5">
                        <i data-lucide="pen-tool" class="w-3.5 h-3.5"></i> Sign
                    </button>
                </form>
            @endunless
        @endcan
    </div>

    {{-- Addendum (signed notes only) --}}
    @if($note->is_signed)
        @can('clinical.tcm.progress_notes.sign')
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm mt-4">
                <div class="text-[11px] font-black uppercase tracking-wider text-slate-600 mb-2">Add an addendum</div>
                <form method="POST" action="{{ route('clinical.tcm.progress_notes.addendum', $note) }}">@csrf
                    <textarea name="addendum_text" rows="3" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" placeholder="Additional information added after signing…"></textarea>
                    <div class="text-right mt-2">
                        <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5">
                            <i data-lucide="file-plus" class="w-3.5 h-3.5"></i> Add addendum
                        </button>
                    </div>
                </form>
            </div>
        @endcan
    @endif
</div>
@endsection
