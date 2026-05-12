@extends('layouts.app')
@section('title', 'IT — Authorizations')

@section('content')
@php
    use App\Models\It\Authorization;
    $statusBadge = [
        'pending'   => ['bg-amber-50 text-amber-700 border-amber-200', 'clock', 'Pending'],
        'submitted' => ['bg-blue-50 text-blue-700 border-blue-200', 'send', 'Submitted'],
        'approved'  => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'check-circle', 'Approved'],
        'denied'    => ['bg-rose-50 text-rose-700 border-rose-200', 'x-circle', 'Denied'],
        'expired'   => ['bg-slate-50 text-slate-500 border-slate-200', 'calendar-x', 'Expired'],
    ];
@endphp

<div class="max-w-7xl mx-auto">
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-gradient-to-br from-violet-500 to-purple-700 text-white rounded-xl shadow-md shadow-violet-500/25">
                    <i data-lucide="key-round" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest text-violet-500">IT · Authorizations</div>
                    <h1 class="text-xl font-black text-slate-800">Insurance authorizations</h1>
                    <p class="text-[11px] text-slate-400 font-semibold mt-0.5">Pre-auth tracking · approved units · expiration windows</p>
                </div>
            </div>
            @can('clinical.it.authorizations.create')
                <a href="{{ route('clinical.it.authorizations.create') }}" class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-2 shadow-md shadow-violet-500/25">
                    <i data-lucide="plus" class="w-4 h-4"></i> New
                </a>
            @endcan
        </div>
    </div>

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
                    <th class="px-4 py-3 text-left">Patient</th>
                    <th class="px-4 py-3 text-left">Auth #</th>
                    <th class="px-4 py-3 text-left">Payer</th>
                    <th class="px-4 py-3 text-left">Period</th>
                    <th class="px-4 py-3 text-center">Units (used / total)</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($auths as $auth)
                    @php [$bClass, $bIcon, $bLabel] = $statusBadge[$auth->status] ?? $statusBadge['pending']; @endphp
                    @php $patient = $auth->admission?->patient; @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">
                            <a href="{{ route('clinical.it.authorizations.show', $auth) }}" class="flex items-center gap-2.5 hover:text-violet-700">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-violet-400 to-purple-600 text-white flex items-center justify-center font-black text-xs">
                                    {{ strtoupper(mb_substr($patient?->first_name ?? '?', 0, 1)) }}{{ strtoupper(mb_substr($patient?->last_name ?? '?', 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-800 text-[13px] truncate">{{ $patient?->full_name ?? '—' }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $patient?->mrn ?? '---' }}</div>
                                </div>
                            </a>
                        </td>
                        <td class="px-4 py-3 font-mono text-[12px] font-bold">{{ $auth->auth_number }}</td>
                        <td class="px-4 py-3 text-[12px] text-slate-600">{{ $auth->payer?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-[12px] text-slate-600">
                            {{ optional($auth->approved_start_date)->format('M j') ?? '—' }}
                            – {{ optional($auth->approved_end_date)->format('M j, Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-mono text-[12px] font-bold text-violet-600">{{ $auth->used_units }} / {{ $auth->approved_units }}</span>
                            @if($auth->approved_units > 0)
                                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden mt-1 max-w-[80px] mx-auto">
                                    <div class="h-full bg-gradient-to-r from-violet-400 to-purple-600" style="width: {{ min(100, ($auth->used_units / $auth->approved_units) * 100) }}%;"></div>
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border {{ $bClass }}">
                                <i data-lucide="{{ $bIcon }}" class="w-3 h-3"></i> {{ $bLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @include('hhrr._shared._action_buttons', [
                                'showRoute'   => route('clinical.it.authorizations.show', $auth),
                                'editRoute'   => auth()->user()->can('clinical.it.authorizations.edit') ? route('clinical.it.authorizations.edit', $auth) : null,
                                'deleteRoute' => auth()->user()->can('clinical.it.authorizations.delete') ? route('clinical.it.authorizations.destroy', $auth) : null,
                                'deleteLabel' => 'this authorization',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-16 text-center text-slate-400 text-sm">
                        <i data-lucide="key-round" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                        No authorizations yet.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
        @if($auths->hasPages())<div class="px-5 py-3 border-t bg-slate-50/50">{{ $auths->links() }}</div>@endif
    </div>
</div>
@endsection
