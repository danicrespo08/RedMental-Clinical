@extends('layouts.app')
@section('title', 'PSR — Service log')

@section('content')
    <div class="flex items-start justify-between mb-4">
        <div>
            <div class="text-xs font-bold uppercase tracking-widest text-emerald-500">PSR</div>
            <h1 class="text-2xl font-bold text-slate-900">Service log</h1>
            <p class="text-slate-500 text-sm mt-1">One row per billable encounter — feeds the superbill module.</p>
        </div>
        @can('clinical.psr.service_log.create')
            <a href="{{ route('clinical.psr.service_log.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg inline-flex items-center gap-2"><i data-lucide="plus" class="w-4 h-4"></i> New entry</a>
        @endcan
    </div>

    <form method="GET" class="bg-white rounded-xl border border-slate-200 p-3 mb-4 flex gap-2 items-end flex-wrap">
        <div><label class="block text-[10px] font-bold text-slate-500 uppercase">From</label><input type="date" name="from" value="{{ $from }}" class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm"></div>
        <div><label class="block text-[10px] font-bold text-slate-500 uppercase">To</label><input type="date" name="to" value="{{ $to }}" class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm"></div>
        <div><label class="block text-[10px] font-bold text-slate-500 uppercase">Billing</label>
            <select name="billing_status" class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm">
                <option value="">All</option>
                @foreach($billingStatuses as $k => $v)<option value="{{ $k }}" @selected($billing === $k)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <button class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold rounded-lg">Filter</button>
    </form>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase">
                <tr><th class="px-3 py-2 text-left">Date</th><th class="px-3 py-2 text-left">Patient</th><th class="px-3 py-2 text-left">Therapist</th><th class="px-3 py-2 text-right">Units</th><th class="px-3 py-2 text-left">Code</th><th class="px-3 py-2 text-left">Source</th><th class="px-3 py-2 text-center">Note</th><th class="px-3 py-2 text-left">Billing</th><th class="px-3 py-2"></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($logs as $log)
                    @php $bc = match($log->billing_status){ 'paid'=>'bg-emerald-100 text-emerald-700', 'submitted'=>'bg-blue-100 text-blue-700', 'denied'=>'bg-rose-100 text-rose-700', 'void'=>'bg-slate-100 text-slate-600', default=>'bg-amber-100 text-amber-700' }; @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2 text-xs">{{ $log->service_date->format('Y-m-d') }}</td>
                        <td class="px-3 py-2 font-semibold">{{ $log->patient?->full_name ?? '—' }}</td>
                        <td class="px-3 py-2 text-xs">{{ $log->therapist?->full_name ?? '—' }}</td>
                        <td class="px-3 py-2 text-right font-mono">{{ $log->units }}</td>
                        <td class="px-3 py-2 font-mono text-xs">{{ $log->service_code }} {{ $log->modifier }}</td>
                        <td class="px-3 py-2 text-xs uppercase">{{ str_replace('_', ' ', $log->source_type) }}</td>
                        <td class="px-3 py-2 text-center">@if($log->has_progress_note)<i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 inline"></i>@else <span class="text-slate-300">—</span>@endif</td>
                        <td class="px-3 py-2"><span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $bc }}">{{ $log->billing_status }}</span></td>
                        <td class="px-3 py-2 text-right">
                            @include('hhrr._shared._action_buttons', [
                                'showRoute'   => route('clinical.psr.service_log.show', $log),
                                'editRoute'   => auth()->user()->can('clinical.psr.service_log.edit')   ? route('clinical.psr.service_log.edit', $log)    : null,
                                'deleteRoute' => auth()->user()->can('clinical.psr.service_log.delete') ? route('clinical.psr.service_log.destroy', $log) : null,
                                'deleteLabel' => 'this service log entry',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-5 py-12 text-center text-slate-400 text-sm">No service log entries.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($logs->hasPages())<div class="px-5 py-3 border-t bg-slate-50/50">{{ $logs->links() }}</div>@endif
    </div>
@endsection
