@extends('layouts.app')
@section('title', 'TCM — ' . $admission->patient->full_name)

@section('content')
    @if($admission->status === 'discharged')
        <div class="max-w-7xl mx-auto mb-5 flex items-center gap-3 px-5 py-3.5 rounded-xl
                    bg-amber-50 border border-amber-300 text-amber-800 text-sm font-semibold">
            <i data-lucide="lock" class="w-4 h-4 flex-shrink-0"></i>
            This admission is discharged — the chart is closed and its clinical records are read-only.
        </div>
    @endif
@php
    use App\Models\Tcm\Admission;
    use App\Models\Tcm\Contact;
    $patient = $admission->patient;
    $statusBadge = match($admission->status){
        'admitted'   => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'check-circle', 'Admitted'],
        'on_hold'    => ['bg-amber-50 text-amber-700 border-amber-200', 'pause-circle', 'On hold'],
        default      => ['bg-slate-50 text-slate-500 border-slate-200', 'log-out', 'Discharged'],
    };
    $totalUnits   = $admission->contacts->sum('units');
    $totalMinutes = $admission->contacts->sum('duration_minutes');
    $mtp          = $admission->treatmentPlans->sortByDesc('id')->first();
    $signedMtp    = $admission->treatmentPlans->firstWhere('is_signed', true);
    $isDischarged = $admission->status === 'discharged';
@endphp

<style>
    .tcm-section { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.02); margin-bottom:1rem; }
    .tcm-hd { padding:.75rem 1.25rem; display:flex; align-items:center; gap:.6rem; border-bottom:1px solid #e2e8f0; background:linear-gradient(180deg,#fff,#fafbff); }
    .tcm-hd .tcm-num { width:26px; height:26px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:800; color:#fff; flex-shrink:0; background:linear-gradient(135deg,#ea580c,#f97316); }
    .tcm-hd .tcm-title { font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#1e293b; }
    .tcm-hd .tcm-sub { font-size:.6rem; color:#94a3b8; font-weight:600; margin-top:1px; }
    .tcm-body { padding:1rem 1.25rem; }

    .stat-card { background:#fff; border:1px solid #e2e8f0; border-radius:.85rem; padding:.85rem 1rem; }
    .stat-label { font-size:.6rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; }
    .stat-value { font-size:1.45rem; font-weight:800; line-height:1.1; margin-top:.15rem; font-family:'JetBrains Mono', ui-monospace, monospace; }

    .ctype-badge {
        display:inline-flex; align-items:center; gap:.25rem;
        padding:.15rem .5rem; border-radius:.4rem;
        font-size:.65rem; font-weight:700; letter-spacing:.02em;
        background:#fff7ed; color:#9a3412; border:1px solid #fed7aa;
    }
</style>

<div class="max-w-7xl mx-auto">
    {{-- HEADER --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('clinical.tcm.admissions.index') }}" class="w-9 h-9 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-orange-600 transition-colors border border-slate-200 flex-shrink-0">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-orange-400 to-amber-600 text-white flex items-center justify-center font-black text-lg shadow-md shadow-orange-500/25">
                    {{ strtoupper(mb_substr($patient->first_name ?? '?', 0, 1)) }}{{ strtoupper(mb_substr($patient->last_name ?? '?', 0, 1)) }}
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest text-orange-500">TCM · Targeted case management</div>
                    <h1 class="text-xl font-black text-slate-800">{{ $patient->full_name }}</h1>
                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                        <span class="font-mono font-bold text-[10px] bg-blue-50 text-blue-600 border border-blue-200 px-2 py-0.5 rounded-md">{{ $patient->mrn ?? '---' }}</span>
                        <span class="text-slate-200">|</span>
                        <span class="text-[10px] text-slate-400 font-medium">DOB: {{ $patient->date_of_birth ?? '---' }}</span>
                        <span class="text-slate-200">|</span>
                        <span class="text-[10px] text-slate-400 font-medium">Admitted {{ $admission->admission_date->format('M j, Y') }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider border {{ $statusBadge[0] }}">
                    <i data-lucide="{{ $statusBadge[1] }}" class="w-3.5 h-3.5"></i> {{ $statusBadge[2] }}
                </span>
                @can('clinical.tcm.create')
                    @if($signedMtp && ! $isDischarged)
                        <a href="{{ route('clinical.tcm.contacts.create', $admission) }}" class="px-3 py-1.5 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i> Record contact
                        </a>
                    @elseif(! $isDischarged)
                        <span title="Sign a service plan first" class="px-3 py-1.5 bg-slate-100 text-slate-400 border border-slate-200 text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5 cursor-not-allowed">
                            <i data-lucide="lock" class="w-3.5 h-3.5"></i> Record contact
                        </span>
                    @endif
                @endcan
                @can('clinical.tcm.edit')
                    <a href="{{ route('clinical.tcm.admissions.edit', $admission) }}" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5">
                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
                    </a>
                @endcan
            </div>
        </div>
    </div>

    {{-- METRICS --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="stat-card">
            <div class="stat-label">Contacts</div>
            <div class="stat-value text-orange-600">{{ $admission->contacts->count() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total units</div>
            <div class="stat-value text-blue-600">{{ $totalUnits }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total minutes</div>
            <div class="stat-value text-emerald-600">{{ $totalMinutes }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Days in program</div>
            <div class="stat-value text-amber-600">{{ (int) $admission->admission_date->diffInDays($admission->discharge_date ?? now()) }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- LEFT --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="tcm-section">
                <div class="tcm-hd"><div class="tcm-num">i</div><div><div class="tcm-title">Admission details</div></div></div>
                <div class="tcm-body space-y-2 text-[12px]">
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Case manager</span><span class="font-semibold text-slate-700 text-right">{{ $admission->caseManager?->full_name ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Authorization</span><span class="font-mono font-semibold text-slate-700">{{ $admission->authorization_number ?: '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400 font-bold">Admission date</span><span class="font-semibold text-slate-700">{{ $admission->admission_date->format('M j, Y') }}</span></div>
                    @if($admission->discharge_date)
                        <div class="flex justify-between"><span class="text-slate-400 font-bold">Discharge date</span><span class="font-semibold text-slate-700">{{ $admission->discharge_date->format('M j, Y') }}</span></div>
                    @endif
                </div>
            </div>

            <div class="tcm-section">
                <div class="tcm-hd"><div class="tcm-num"><i data-lucide="folder-open" class="w-3.5 h-3.5"></i></div><div><div class="tcm-title">Clinical documents</div></div></div>
                <div class="tcm-body space-y-2">
                    @php
                        $mtpState = ! $mtp ? 'pending' : ($mtp->is_signed ? 'signed' : 'draft');
                        $mtpMap = [
                            'pending' => ['Not started', 'text-slate-400 bg-slate-50 border-slate-200'],
                            'draft'   => ['Draft — needs signature', 'text-amber-600 bg-amber-50 border-amber-200'],
                            'signed'  => ['Signed', 'text-emerald-600 bg-emerald-50 border-emerald-200'],
                        ];
                    @endphp
                    <div class="flex items-center justify-between gap-2 border border-slate-200 rounded-lg px-3 py-2">
                        <div class="min-w-0">
                            <div class="text-[12px] font-bold text-slate-700">Service plan</div>
                            <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase border {{ $mtpMap[$mtpState][1] }}">{{ $mtpMap[$mtpState][0] }}</span>
                        </div>
                        @if($isDischarged)
                            @if($mtp)<a href="{{ route('clinical.tcm.treatment_plans.show', $mtp) }}" class="text-[10px] font-bold uppercase text-slate-500 hover:text-orange-600">View</a>@endif
                        @elseif(! $mtp)
                            @can('clinical.tcm.treatment_plans.create')
                                <a href="{{ route('clinical.tcm.treatment_plans.create', ['admission_id' => $admission->id]) }}" class="px-2.5 py-1 bg-orange-600 hover:bg-orange-700 text-white text-[10px] font-bold uppercase tracking-wider rounded-md inline-flex items-center gap-1 whitespace-nowrap">
                                    <i data-lucide="plus" class="w-3 h-3"></i> Create
                                </a>
                            @endcan
                        @elseif(! $mtp->is_signed)
                            @can('clinical.tcm.treatment_plans.edit')
                                <a href="{{ route('clinical.tcm.treatment_plans.edit', $mtp) }}" class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-[10px] font-bold uppercase tracking-wider rounded-md whitespace-nowrap">Continue</a>
                            @endcan
                        @else
                            <a href="{{ route('clinical.tcm.treatment_plans.show', $mtp) }}" class="text-[10px] font-bold uppercase text-orange-600 hover:underline">View</a>
                        @endif
                    </div>

                    <div class="flex items-center justify-between gap-2 border border-slate-200 rounded-lg px-3 py-2">
                        <div class="min-w-0">
                            <div class="text-[12px] font-bold text-slate-700">Discharge summary</div>
                            <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase border {{ $admission->dischargeSummary ? 'text-emerald-600 bg-emerald-50 border-emerald-200' : 'text-slate-400 bg-slate-50 border-slate-200' }}">{{ $admission->dischargeSummary ? 'On file' : 'Not started' }}</span>
                        </div>
                        @if($admission->dischargeSummary)
                            <a href="{{ route('clinical.tcm.discharges.show', $admission->dischargeSummary) }}" class="text-[10px] font-bold uppercase text-orange-600 hover:underline">View</a>
                        @else
                            @can('clinical.tcm.discharges.create')
                                <a href="{{ route('clinical.tcm.discharges.create', ['admission_id' => $admission->id]) }}" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-[10px] font-bold uppercase tracking-wider rounded-md inline-flex items-center gap-1 whitespace-nowrap">
                                    <i data-lucide="log-out" class="w-3 h-3"></i> Discharge
                                </a>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>

            <div class="tcm-section">
                <div class="tcm-hd"><div class="tcm-num"><i data-lucide="heart-pulse" class="w-3.5 h-3.5"></i></div><div><div class="tcm-title">Diagnosis (ICD-10)</div></div></div>
                <div class="tcm-body">
                    @if($admission->diagnosis_code)
                        <div class="font-mono text-[11px] bg-rose-50 text-rose-700 border border-rose-200 px-2 py-0.5 rounded inline-block">{{ $admission->diagnosis_code }}</div>
                        @if($admission->diagnosis_description)
                            <div class="text-[12px] text-slate-600 mt-1.5">{{ $admission->diagnosis_description }}</div>
                        @endif
                    @else
                        <p class="text-slate-400 italic text-sm">No diagnosis assigned.</p>
                    @endif
                </div>
            </div>

            @if($admission->service_plan)
                <div class="tcm-section">
                    <div class="tcm-hd"><div class="tcm-num"><i data-lucide="clipboard-pen" class="w-3.5 h-3.5"></i></div><div><div class="tcm-title">Service plan</div></div></div>
                    <div class="tcm-body text-[13px] text-slate-700 whitespace-pre-line leading-relaxed">{{ $admission->service_plan }}</div>
                </div>
            @endif

            @if($admission->notes)
                <div class="tcm-section">
                    <div class="tcm-hd"><div class="tcm-num"><i data-lucide="sticky-note" class="w-3.5 h-3.5"></i></div><div><div class="tcm-title">Notes</div></div></div>
                    <div class="tcm-body text-[13px] text-slate-700 whitespace-pre-line leading-relaxed">{{ $admission->notes }}</div>
                </div>
            @endif
        </div>

        {{-- RIGHT --}}
        <div class="lg:col-span-2">
            <div class="tcm-section">
                <div class="tcm-hd">
                    <div class="tcm-num">{{ $admission->contacts->count() }}</div>
                    <div class="flex-1">
                        <div class="tcm-title">Care contacts</div>
                        <div class="tcm-sub">In-person, phone, video, email, collateral, home visits</div>
                    </div>
                    @can('clinical.tcm.create')
                        @if($signedMtp && ! $isDischarged)
                            <a href="{{ route('clinical.tcm.contacts.create', $admission) }}" class="px-2.5 py-1 bg-orange-50 hover:bg-orange-100 text-orange-700 border border-orange-200 text-[10px] font-bold uppercase tracking-wider rounded-md inline-flex items-center gap-1">
                                <i data-lucide="plus" class="w-3 h-3"></i> New
                            </a>
                        @endif
                    @endcan
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-2 text-left">When</th>
                            <th class="px-4 py-2 text-left">Type</th>
                            <th class="px-4 py-2 text-left">With</th>
                            <th class="px-4 py-2 text-center">CPT / Units</th>
                            <th class="px-4 py-2 text-center">Min</th>
                            <th class="px-4 py-2 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($admission->contacts as $contact)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-2.5">
                                    <a href="{{ route('clinical.tcm.contacts.show', [$admission, $contact]) }}" class="font-semibold text-slate-700 text-[12px] hover:text-orange-700">{{ $contact->contact_at->format('M j, Y') }}</a>
                                    <div class="text-[10px] text-slate-400">{{ $contact->contact_at->format('g:i A') }}</div>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="ctype-badge">{{ Contact::CONTACT_TYPES[$contact->contact_type] ?? $contact->contact_type }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-[12px] text-slate-600">{{ $contact->with_whom ?: '—' }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    <div class="font-mono text-[10px] font-bold bg-orange-50 text-orange-700 border border-orange-200 px-1.5 py-0.5 rounded inline-block">{{ $contact->cpt_code }}</div>
                                    <div class="font-mono text-[11px] font-bold text-blue-600 mt-0.5">{{ $contact->units }} u</div>
                                </td>
                                <td class="px-4 py-2.5 text-center font-mono text-[11px] text-slate-500">{{ $contact->duration_minutes ?: '—' }}</td>
                                <td class="px-4 py-2.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('clinical.tcm.contacts.show', [$admission, $contact]) }}" class="p-1 text-slate-500 hover:text-orange-600 hover:bg-orange-50 rounded"><i data-lucide="eye" class="w-3.5 h-3.5"></i></a>
                                        @can('clinical.tcm.edit')<a href="{{ route('clinical.tcm.contacts.edit', [$admission, $contact]) }}" class="p-1 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></a>@endcan
                                        @can('clinical.tcm.delete')
                                            <form method="POST" action="{{ route('clinical.tcm.contacts.destroy', [$admission, $contact]) }}" data-confirm-delete="this contact">@csrf @method('DELETE')
                                                <button class="p-1 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400 text-sm">
                                <i data-lucide="phone-off" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                                No contacts recorded — click "New" to log the first one.
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
