@extends('layouts.app')
@section('title', 'PSR — Progress notes')

@section('content')
@php
    $statusBadge = [
        'draft'    => ['bg-amber-50 text-amber-700 border-amber-200', 'clock', 'Draft'],
        'signed'   => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'check-circle', 'Signed'],
        'addendum' => ['bg-blue-50 text-blue-700 border-blue-200', 'file-plus', 'Addendum'],
    ];
    $riskBadge = [
        'high'     => 'bg-rose-50 text-rose-700 border-rose-200',
        'moderate' => 'bg-amber-50 text-amber-700 border-amber-200',
        'low'      => 'bg-blue-50 text-blue-700 border-blue-200',
        'none'     => 'bg-slate-50 text-slate-500 border-slate-200',
    ];
@endphp

<div class="max-w-7xl mx-auto">
    {{-- HEADER --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-gradient-to-br from-indigo-500 to-violet-700 text-white rounded-xl shadow-md shadow-indigo-500/25">
                    <i data-lucide="file-text" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest text-indigo-500">PSR · Progress notes</div>
                    <h1 class="text-xl font-black text-slate-800">Clinical documentation</h1>
                    <p class="text-[11px] text-slate-400 font-semibold mt-0.5">SOAP / DAP / BIRP / GIRP — sign to lock, addenda for post-hoc edits</p>
                </div>
            </div>
            @can('clinical.psr.progress_notes.create')
                <a href="{{ route('clinical.psr.progress_notes.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-2 shadow-md shadow-indigo-500/25">
                    <i data-lucide="plus" class="w-4 h-4"></i> New note
                </a>
            @endcan
        </div>
    </div>

    {{-- FILTERS --}}
    <form method="GET" class="bg-white border border-slate-200 rounded-2xl p-3 mb-4 flex items-end gap-3 shadow-sm">
        <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Status</label>
            <select name="status" onchange="this.form.submit()" class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm min-w-[160px]">
                <option value="">All statuses</option>
                @foreach($statuses as $k => $v)<option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>@endforeach
            </select>
        </div>
    </form>

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left">Patient</th>
                    <th class="px-4 py-3 text-left">Therapist</th>
                    <th class="px-4 py-3 text-center">Format</th>
                    <th class="px-4 py-3 text-center">Risk</th>
                    <th class="px-4 py-3 text-center">Rating</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($notes as $n)
                    @php
                        [$bClass, $bIcon, $bLabel] = $statusBadge[$n->status] ?? $statusBadge['draft'];
                        $rClass = $riskBadge[$n->risk_level] ?? 'bg-slate-50 text-slate-500 border-slate-200';
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-700 text-[12px]">{{ $n->note_date->format('M j, Y') }}</div>
                            <div class="text-[10px] text-slate-400">{{ $n->start_time }}@if($n->end_time) – {{ $n->end_time }}@endif</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-400 to-violet-600 text-white flex items-center justify-center font-black text-xs">
                                    {{ strtoupper(mb_substr($n->patient?->first_name ?? '?', 0, 1)) }}{{ strtoupper(mb_substr($n->patient?->last_name ?? '?', 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-800 text-[13px] truncate">{{ $n->patient?->full_name ?? '—' }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $n->patient?->mrn ?? '---' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-[12px] text-slate-600">{{ $n->therapist?->full_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-mono text-[10px] font-bold bg-violet-50 text-violet-700 border border-violet-200 px-2 py-0.5 rounded uppercase">
                                {{ $n->template?->slug ?? 'soap' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold uppercase border {{ $rClass }}">{{ $n->risk_level }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($n->progress_rating)
                                <span class="font-mono font-bold text-[12px] text-violet-600">{{ $n->progress_rating }}/5</span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border {{ $bClass }}">
                                <i data-lucide="{{ $bIcon }}" class="w-3 h-3"></i> {{ $bLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @include('hhrr._shared._action_buttons', [
                                'showRoute'   => route('clinical.psr.progress_notes.show', $n),
                                'editRoute'   => $n->is_signed ? null : (auth()->user()->can('clinical.psr.progress_notes.edit') ? route('clinical.psr.progress_notes.edit', $n) : null),
                                'deleteRoute' => $n->is_signed ? null : (auth()->user()->can('clinical.psr.progress_notes.delete') ? route('clinical.psr.progress_notes.destroy', $n) : null),
                                'deleteLabel' => 'this progress note',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-16 text-center text-slate-400 text-sm">
                        <i data-lucide="file-plus" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                        No progress notes yet.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
        @if($notes->hasPages())<div class="px-5 py-3 border-t bg-slate-50/50">{{ $notes->links() }}</div>@endif
    </div>
</div>
@endsection
