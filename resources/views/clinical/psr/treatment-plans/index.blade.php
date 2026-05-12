@extends('layouts.app')
@section('title', 'PSR — Treatment plans')

@section('content')
    <div class="text-xs font-bold uppercase tracking-widest text-emerald-500">PSR</div>
    <h1 class="text-2xl font-bold text-slate-900 mb-4">Master treatment plans</h1>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase">
                <tr><th class="px-4 py-3 text-left">Patient</th><th class="px-4 py-3 text-left">Period</th><th class="px-4 py-3 text-right">Goals</th><th class="px-4 py-3 text-center">Signed</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($plans as $plan)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-semibold">{{ $plan->admission?->patient?->full_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-slate-600">{{ $plan->start_date->format('M j, Y') }} → {{ $plan->end_date->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-right font-mono text-xs">{{ $plan->goals->count() }} goals · {{ $plan->goals->sum(fn ($g) => $g->objectives->count()) }} obj.</td>
                        <td class="px-4 py-3 text-center">
                            @if($plan->is_signed)<span class="inline-block px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold uppercase">Signed</span>
                            @else <span class="inline-block px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold uppercase">Draft</span>@endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @include('hhrr._shared._action_buttons', [
                                'showRoute'   => route('clinical.psr.treatment_plans.show', $plan),
                                'editRoute'   => $plan->is_signed ? null : (auth()->user()->can('clinical.psr.treatment_plans.edit') ? route('clinical.psr.treatment_plans.edit', $plan) : null),
                                'deleteRoute' => $plan->is_signed ? null : (auth()->user()->can('clinical.psr.treatment_plans.delete') ? route('clinical.psr.treatment_plans.destroy', $plan) : null),
                                'deleteLabel' => 'this treatment plan',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400 text-sm">No treatment plans yet — create one from a patient's admission page.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($plans->hasPages())<div class="px-5 py-3 border-t bg-slate-50/50">{{ $plans->links() }}</div>@endif
    </div>
@endsection
