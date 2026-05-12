@extends('layouts.app')
@section('title', 'IT — Sessions')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- HEADER --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-gradient-to-br from-violet-500 to-purple-700 text-white rounded-xl shadow-md shadow-violet-500/25">
                    <i data-lucide="calendar-check" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest text-violet-500">IT · Sessions</div>
                    <h1 class="text-xl font-black text-slate-800">All therapy sessions</h1>
                    <p class="text-[11px] text-slate-400 font-semibold mt-0.5">Cross-patient session log</p>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTERS --}}
    <form method="GET" class="bg-white border border-slate-200 rounded-2xl p-4 mb-4 flex items-end gap-3 flex-wrap shadow-sm">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Patient</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="Name or MRN…"
                   class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-sm">
        </div>
        <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Month</label>
            <input type="month" name="month" value="{{ $month }}" class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm">
        </div>
        <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">CPT</label>
            <select name="cpt" class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm min-w-[100px]">
                <option value="">All</option>
                @foreach($cptOptions as $code)<option value="{{ $code }}" @selected($cpt === $code)>{{ $code }}</option>@endforeach
            </select>
        </div>
        <button class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5">
            <i data-lucide="filter" class="w-3.5 h-3.5"></i> Filter
        </button>
        @if($q || $month || $cpt)
            <a href="{{ route('clinical.it.sessions.index') }}" class="px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-600 text-[11px] font-bold uppercase rounded-lg border border-slate-200">Clear</a>
        @endif
    </form>

    {{-- LIST --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left">Patient</th>
                    <th class="px-4 py-3 text-left">Therapist</th>
                    <th class="px-4 py-3 text-center">CPT</th>
                    <th class="px-4 py-3 text-center">Units</th>
                    <th class="px-4 py-3 text-center">Min</th>
                    <th class="px-4 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($sessions as $session)
                    @php $patient = $session->admission?->patient; @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-700 text-[12px]">{{ $session->session_date->format('M j, Y') }}</div>
                            <div class="text-[10px] text-slate-400">{{ $session->start_time }}@if($session->end_time) – {{ $session->end_time }}@endif</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($patient)
                                <a href="{{ route('clinical.it.sessions.show', [$session->admission, $session]) }}" class="flex items-center gap-2.5 hover:text-violet-700">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-violet-400 to-purple-600 text-white flex items-center justify-center font-black text-xs">
                                        {{ strtoupper(mb_substr($patient->first_name ?? '?', 0, 1)) }}{{ strtoupper(mb_substr($patient->last_name ?? '?', 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-800 text-[13px] truncate">{{ $patient->full_name }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $patient->mrn ?? '---' }}</div>
                                    </div>
                                </a>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-[12px] text-slate-600">{{ $session->therapist?->full_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-mono text-[10px] font-bold bg-violet-50 text-violet-700 border border-violet-200 px-1.5 py-0.5 rounded">{{ $session->cpt_code }}{{ $session->modifier ? ' '.$session->modifier : '' }}</span>
                        </td>
                        <td class="px-4 py-3 text-center font-mono text-[12px] font-bold text-blue-600">{{ $session->units }}</td>
                        <td class="px-4 py-3 text-center font-mono text-[11px] text-slate-500">{{ $session->duration_minutes ?: '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('clinical.it.sessions.show', [$session->admission, $session]) }}" class="p-1.5 text-slate-500 hover:text-violet-600 hover:bg-violet-50 rounded"><i data-lucide="eye" class="w-4 h-4"></i></a>
                                @can('clinical.it.edit')<a href="{{ route('clinical.it.sessions.edit', [$session->admission, $session]) }}" class="p-1.5 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded"><i data-lucide="pencil" class="w-4 h-4"></i></a>@endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-16 text-center text-slate-400 text-sm">
                        <i data-lucide="calendar-x" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                        No sessions match your filters.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
        @if($sessions->hasPages())<div class="px-5 py-3 border-t bg-slate-50/50">{{ $sessions->links() }}</div>@endif
    </div>
</div>
@endsection
