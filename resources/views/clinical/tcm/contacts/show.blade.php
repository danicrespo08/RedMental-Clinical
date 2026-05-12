@extends('layouts.app')
@section('title', 'TCM — Contact ' . $contact->contact_at->format('M j, Y'))

@section('content')
@php
    use App\Models\Tcm\Contact;
    $patient = $admission->patient;
    $typeIcons = [
        'in_person' => 'user-check', 'phone' => 'phone', 'video' => 'video',
        'email' => 'mail', 'collateral' => 'users', 'home_visit' => 'home',
    ];
@endphp

<style>
    .tcm-section { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.02); margin-bottom:1rem; }
    .tcm-hd { padding:.75rem 1.25rem; display:flex; align-items:center; gap:.6rem; border-bottom:1px solid #e2e8f0; background:linear-gradient(180deg,#fff,#fafbff); }
    .tcm-num { width:26px; height:26px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:800; color:#fff; flex-shrink:0; background:linear-gradient(135deg,#ea580c,#f97316); }
    .tcm-title { font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#1e293b; }
    .tcm-body { padding:1rem 1.25rem; }
    .narr-block { padding:.85rem 1rem; border-left:3px solid #fdba74; background:#fff7ed; border-radius:0 .5rem .5rem 0; margin-bottom:.65rem; }
    .narr-label { font-size:.6rem; font-weight:800; color:#c2410c; text-transform:uppercase; letter-spacing:.05em; }
    .narr-content { font-size:.85rem; color:#334155; line-height:1.6; white-space:pre-wrap; margin-top:.25rem; }
    .stat-card { background:#fff; border:1px solid #e2e8f0; border-radius:.85rem; padding:.85rem 1rem; }
    .stat-label { font-size:.6rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; }
    .stat-value { font-size:1.1rem; font-weight:800; color:#1e293b; line-height:1.2; margin-top:.15rem; }
</style>

<div class="max-w-7xl mx-auto">
    {{-- HEADER --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('clinical.tcm.admissions.show', $admission) }}" class="w-9 h-9 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-orange-600 transition-colors border border-slate-200 flex-shrink-0">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-orange-400 to-amber-600 text-white flex items-center justify-center font-black text-lg shadow-md shadow-orange-500/25">
                    {{ strtoupper(mb_substr($patient?->first_name ?? '?', 0, 1)) }}{{ strtoupper(mb_substr($patient?->last_name ?? '?', 0, 1)) }}
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest text-orange-500">TCM · Care contact</div>
                    <h1 class="text-xl font-black text-slate-800">{{ $patient?->full_name ?? '—' }}</h1>
                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                        <span class="font-mono font-bold text-[10px] bg-blue-50 text-blue-600 border border-blue-200 px-2 py-0.5 rounded-md">{{ $patient?->mrn ?? '---' }}</span>
                        <span class="text-slate-200">|</span>
                        <span class="text-[10px] text-slate-400 font-medium">{{ $contact->contact_at->format('M j, Y g:i A') }}</span>
                        <span class="text-slate-200">|</span>
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-orange-700 bg-orange-50 border border-orange-200 px-1.5 py-0.5 rounded">
                            <i data-lucide="{{ $typeIcons[$contact->contact_type] ?? 'circle' }}" class="w-3 h-3"></i> {{ Contact::CONTACT_TYPES[$contact->contact_type] ?? $contact->contact_type }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @can('clinical.tcm.edit')
                    <a href="{{ route('clinical.tcm.contacts.edit', [$admission, $contact]) }}" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5">
                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
                    </a>
                @endcan
                @can('clinical.tcm.delete')
                    <form method="POST" action="{{ route('clinical.tcm.contacts.destroy', [$admission, $contact]) }}" class="inline" data-confirm-delete="this contact">@csrf @method('DELETE')
                        <button class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
                        </button>
                    </form>
                @endcan
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-1 space-y-4">
            <div class="tcm-section">
                <div class="tcm-hd"><div class="tcm-num">i</div><div><div class="tcm-title">Contact details</div></div></div>
                <div class="tcm-body space-y-2 text-[12px]">
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Case manager</span><span class="font-semibold text-slate-700 text-right">{{ $contact->caseManager?->full_name ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">With whom</span><span class="font-semibold text-slate-700 text-right">{{ $contact->with_whom ?: '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">CPT code</span><span class="font-mono font-semibold text-slate-700">{{ $contact->cpt_code }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Place of service</span><span class="font-mono font-semibold text-slate-700">{{ $contact->place_of_service }}</span></div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="stat-card">
                    <div class="stat-label">Units</div>
                    <div class="stat-value text-orange-600 font-mono">{{ $contact->units }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Minutes</div>
                    <div class="stat-value text-blue-600 font-mono">{{ $contact->duration_minutes ?: '—' }}</div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="tcm-section">
                <div class="tcm-hd"><div class="tcm-num">1</div><div><div class="tcm-title">Contact narrative</div></div></div>
                <div class="tcm-body">
                    @php $hasAny = false; @endphp
                    @foreach(['goals_addressed' => 'Goals addressed', 'summary' => 'Summary', 'next_actions' => 'Next actions'] as $f => $label)
                        @if($contact->{$f})
                            @php $hasAny = true; @endphp
                            <div class="narr-block">
                                <div class="narr-label">{{ $label }}</div>
                                <div class="narr-content">{{ $contact->{$f} }}</div>
                            </div>
                        @endif
                    @endforeach
                    @unless($hasAny)<p class="text-slate-400 italic text-sm text-center py-6">No narrative documented.</p>@endunless
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
