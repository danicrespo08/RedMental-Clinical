@extends('layouts.app')
@section('title', 'PSR — Discharges')

@section('content')
@php
    use App\Models\Psr\DischargeSummary;
    $statusBadge = [
        'draft'     => ['bg-amber-50 text-amber-700 border-amber-200', 'clock', 'Draft'],
        'signed'    => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'check-circle', 'Signed'],
        'co_signed' => ['bg-blue-50 text-blue-700 border-blue-200', 'shield-check', 'Co-signed'],
    ];
    $prognosisColor = [
        'good'    => 'text-emerald-600 bg-emerald-50 border-emerald-200',
        'fair'    => 'text-blue-600 bg-blue-50 border-blue-200',
        'guarded' => 'text-amber-600 bg-amber-50 border-amber-200',
        'poor'    => 'text-rose-600 bg-rose-50 border-rose-200',
    ];
@endphp

<div class="max-w-7xl mx-auto">
    {{-- HEADER --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-5 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="p-2.5 bg-gradient-to-br from-rose-500 to-rose-700 text-white rounded-xl shadow-md shadow-rose-500/25">
                <i data-lucide="log-out" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-xs font-bold uppercase tracking-widest text-rose-500">PSR · Discharge summaries</div>
                <h1 class="text-xl font-black text-slate-800">Closed admissions</h1>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">End-of-episode clinical wrap-up · FARS comparison · aftercare plan</p>
            </div>
        </div>
    </div>

    {{-- LIST --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Patient</th>
                    <th class="px-4 py-3 text-left">Discharge date</th>
                    <th class="px-4 py-3 text-left">Type / reason</th>
                    <th class="px-4 py-3 text-center">Sessions</th>
                    <th class="px-4 py-3 text-center">Days</th>
                    <th class="px-4 py-3 text-center">Prognosis</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($discharges as $d)
                    @php
                        [$bClass, $bIcon, $bLabel] = $statusBadge[$d->status] ?? $statusBadge['draft'];
                        $progClass = $prognosisColor[$d->prognosis] ?? 'text-slate-400 bg-slate-50 border-slate-200';
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-rose-400 to-rose-600 text-white flex items-center justify-center font-black text-xs">
                                    {{ strtoupper(mb_substr($d->patient?->first_name ?? '?', 0, 1)) }}{{ strtoupper(mb_substr($d->patient?->last_name ?? '?', 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-800 text-[13px] truncate">{{ $d->patient?->full_name ?? '—' }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $d->patient?->mrn ?? '---' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-700 text-[12px]">{{ $d->discharge_date->format('M j, Y') }}</div>
                            <div class="text-[10px] text-slate-400">{{ $d->discharge_date->diffForHumans() }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-[12px] font-bold text-slate-700">{{ DischargeSummary::DISCHARGE_TYPES[$d->discharge_type] ?? $d->discharge_type }}</div>
                            <div class="text-[10px] text-slate-400">{{ DischargeSummary::DISCHARGE_REASONS[$d->discharge_reason] ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="text-[12px] font-bold font-mono">{{ $d->total_sessions_attended }}</div>
                            <div class="text-[10px] text-slate-400">{{ $d->total_sessions_absent }} absent</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-mono font-bold text-[12px] text-slate-700">{{ $d->days_in_program }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($d->prognosis)
                                <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold uppercase border {{ $progClass }}">{{ $d->prognosis }}</span>
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
                                'showRoute'   => route('clinical.psr.discharges.show', $d),
                                'editRoute'   => $d->is_signed ? null : (auth()->user()->can('clinical.psr.discharges.edit') ? route('clinical.psr.discharges.edit', $d) : null),
                                'deleteRoute' => $d->is_signed ? null : (auth()->user()->can('clinical.psr.discharges.delete') ? route('clinical.psr.discharges.destroy', $d) : null),
                                'deleteLabel' => 'this discharge summary',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-16 text-center text-slate-400 text-sm">
                        <i data-lucide="archive" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                        No discharges yet — discharges are created from an admission's profile when treatment ends.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
        @if($discharges->hasPages())<div class="px-5 py-3 border-t bg-slate-50/50">{{ $discharges->links() }}</div>@endif
    </div>
</div>
@endsection
