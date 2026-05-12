@extends('layouts.app')
@section('title', 'IT — Service log')

@section('content')
@php
    $statusBadge = [
        'unbilled'  => ['bg-amber-50 text-amber-700 border-amber-200',  'circle-dashed', 'Unbilled'],
        'submitted' => ['bg-blue-50 text-blue-700 border-blue-200',     'send', 'Submitted'],
        'paid'      => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'check-circle-2', 'Paid'],
        'denied'    => ['bg-rose-50 text-rose-700 border-rose-200',     'x-circle', 'Denied'],
        'void'      => ['bg-slate-50 text-slate-500 border-slate-200',  'ban', 'Void'],
    ];
@endphp

<div class="max-w-7xl mx-auto">
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-gradient-to-br from-violet-500 to-purple-700 text-white rounded-xl shadow-md shadow-violet-500/25">
                    <i data-lucide="list" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest text-violet-500">IT · Service log</div>
                    <h1 class="text-xl font-black text-slate-800">Billable encounters</h1>
                    <p class="text-[11px] text-slate-400 font-semibold mt-0.5">CPT-level entries fed into the weekly superbill</p>
                </div>
            </div>
            @can('clinical.it.service_log.create')
                <a href="{{ route('clinical.it.service_log.create') }}" class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-2 shadow-md shadow-violet-500/25">
                    <i data-lucide="plus" class="w-4 h-4"></i> New entry
                </a>
            @endcan
        </div>
    </div>

    <form method="GET" class="bg-white border border-slate-200 rounded-2xl p-3 mb-4 flex items-end gap-3 flex-wrap shadow-sm">
        <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Status</label>
            <select name="status" class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm min-w-[140px]">
                <option value="">All</option>
                @foreach($statuses as $k => $v)<option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Month</label>
            <input type="month" name="month" value="{{ $month }}" class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm">
        </div>
        <button class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5">
            <i data-lucide="filter" class="w-3.5 h-3.5"></i> Filter
        </button>
        @if($status || $month)
            <a href="{{ route('clinical.it.service_log.index') }}" class="px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-600 text-[11px] font-bold uppercase rounded-lg border border-slate-200">Clear</a>
        @endif
    </form>

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left">Patient</th>
                    <th class="px-4 py-3 text-left">Therapist</th>
                    <th class="px-4 py-3 text-center">CPT</th>
                    <th class="px-4 py-3 text-center">Units</th>
                    <th class="px-4 py-3 text-right">Paid</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($logs as $log)
                    @php [$bClass, $bIcon, $bLabel] = $statusBadge[$log->billing_status] ?? $statusBadge['unbilled']; @endphp
                    @php $patient = $log->admission?->patient; @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-700 text-[12px]">{{ $log->service_date->format('M j, Y') }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($patient)
                                <a href="{{ route('clinical.it.service_log.show', $log) }}" class="flex items-center gap-2.5 hover:text-violet-700">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-400 to-purple-600 text-white flex items-center justify-center font-black text-[10px]">
                                        {{ strtoupper(mb_substr($patient->first_name ?? '?', 0, 1)) }}{{ strtoupper(mb_substr($patient->last_name ?? '?', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800 text-[12px] truncate">{{ $patient->full_name }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $patient->mrn ?? '---' }}</div>
                                    </div>
                                </a>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-[12px] text-slate-600">{{ $log->therapist?->full_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-center"><span class="font-mono text-[10px] font-bold bg-violet-50 text-violet-700 border border-violet-200 px-1.5 py-0.5 rounded">{{ $log->cpt_code }}{{ $log->modifier ? ' '.$log->modifier : '' }}</span></td>
                        <td class="px-4 py-3 text-center font-mono text-[12px] font-bold text-blue-600">{{ $log->units }}</td>
                        <td class="px-4 py-3 text-right font-mono text-[11px] {{ $log->paid_amount ? 'text-emerald-600 font-bold' : 'text-slate-300' }}">{{ $log->paid_amount ? '$'.number_format((float) $log->paid_amount, 2) : '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border {{ $bClass }}">
                                <i data-lucide="{{ $bIcon }}" class="w-3 h-3"></i> {{ $bLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @include('hhrr._shared._action_buttons', [
                                'showRoute'   => route('clinical.it.service_log.show', $log),
                                'editRoute'   => auth()->user()->can('clinical.it.service_log.edit') ? route('clinical.it.service_log.edit', $log) : null,
                                'deleteRoute' => auth()->user()->can('clinical.it.service_log.delete') ? route('clinical.it.service_log.destroy', $log) : null,
                                'deleteLabel' => 'this entry',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-16 text-center text-slate-400 text-sm">
                        <i data-lucide="receipt" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                        No service-log entries.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
        @if($logs->hasPages())<div class="px-5 py-3 border-t bg-slate-50/50">{{ $logs->links() }}</div>@endif
    </div>
</div>
@endsection
