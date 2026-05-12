@extends('layouts.app')
@section('title', 'IT — Treatment plans')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="p-2.5 bg-gradient-to-br from-violet-500 to-purple-700 text-white rounded-xl shadow-md shadow-violet-500/25">
                <i data-lucide="list-checks" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-xs font-bold uppercase tracking-widest text-violet-500">IT · Treatment plans</div>
                <h1 class="text-xl font-black text-slate-800">Master treatment plans</h1>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">Goals · objectives · interventions for each IT episode</p>
            </div>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Patient</th>
                    <th class="px-4 py-3 text-left">Period</th>
                    <th class="px-4 py-3 text-right">Goals</th>
                    <th class="px-4 py-3 text-center">Signed</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($plans as $plan)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-semibold">{{ $plan->admission?->patient?->full_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-slate-600">{{ $plan->start_date->format('M j, Y') }} → {{ $plan->end_date->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-right font-mono text-xs">{{ $plan->goals->count() }} goals · {{ $plan->goals->sum(fn ($g) => $g->objectives->count()) }} obj.</td>
                        <td class="px-4 py-3 text-center">
                            @if($plan->is_signed)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase border bg-emerald-50 text-emerald-700 border-emerald-200">
                                    <i data-lucide="check-circle" class="w-3 h-3"></i> Signed
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase border bg-amber-50 text-amber-700 border-amber-200">
                                    <i data-lucide="clock" class="w-3 h-3"></i> Draft
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @include('hhrr._shared._action_buttons', [
                                'showRoute'   => route('clinical.it.treatment_plans.show', $plan),
                                'editRoute'   => $plan->is_signed ? null : (auth()->user()->can('clinical.it.treatment_plans.edit') ? route('clinical.it.treatment_plans.edit', $plan) : null),
                                'deleteRoute' => $plan->is_signed ? null : (auth()->user()->can('clinical.it.treatment_plans.delete') ? route('clinical.it.treatment_plans.destroy', $plan) : null),
                                'deleteLabel' => 'this treatment plan',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400 text-sm">No treatment plans yet — create one from a patient's IT admission.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($plans->hasPages())<div class="px-5 py-3 border-t bg-slate-50/50">{{ $plans->links() }}</div>@endif
    </div>
</div>
@endsection
