@extends('layouts.app')
@section('title', 'TCM — Contacts')

@section('content')
@php
    use App\Models\Tcm\Contact;
    $typeIcons = [
        'in_person' => 'user-check', 'phone' => 'phone', 'video' => 'video',
        'email' => 'mail', 'collateral' => 'users', 'home_visit' => 'home',
    ];
@endphp

<div class="max-w-7xl mx-auto">
    {{-- HEADER --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-gradient-to-br from-orange-500 to-orange-700 text-white rounded-xl shadow-md shadow-orange-500/25">
                    <i data-lucide="phone" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest text-orange-500">TCM · Contacts</div>
                    <h1 class="text-xl font-black text-slate-800">All care contacts</h1>
                    <p class="text-[11px] text-slate-400 font-semibold mt-0.5">Cross-patient case-management touch log</p>
                </div>
            </div>
            @can('clinical.tcm.create')
                <a href="{{ route('clinical.tcm.contacts.create_any') }}" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5 self-start">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> New contact
                </a>
            @endcan
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
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Type</label>
            <select name="type" class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm min-w-[140px]">
                <option value="">All</option>
                @foreach($types as $k => $v)<option value="{{ $k }}" @selected($type === $k)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <button class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold uppercase tracking-wider rounded-lg inline-flex items-center gap-1.5">
            <i data-lucide="filter" class="w-3.5 h-3.5"></i> Filter
        </button>
        @if($q || $month || $type)
            <a href="{{ route('clinical.tcm.contacts.index') }}" class="px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-600 text-[11px] font-bold uppercase rounded-lg border border-slate-200">Clear</a>
        @endif
    </form>

    {{-- LIST --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">When</th>
                    <th class="px-4 py-3 text-left">Patient</th>
                    <th class="px-4 py-3 text-left">Case manager</th>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-center">CPT / Units</th>
                    <th class="px-4 py-3 text-center">Min</th>
                    <th class="px-4 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($contacts as $contact)
                    @php $patient = $contact->admission?->patient; @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-700 text-[12px]">{{ $contact->contact_at->format('M j, Y') }}</div>
                            <div class="text-[10px] text-slate-400">{{ $contact->contact_at->format('g:i A') }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($patient)
                                <a href="{{ route('clinical.tcm.contacts.show', [$contact->admission, $contact]) }}" class="flex items-center gap-2.5 hover:text-orange-700">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-orange-400 to-amber-600 text-white flex items-center justify-center font-black text-xs">
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
                        <td class="px-4 py-3 text-[12px] text-slate-600">{{ $contact->caseManager?->full_name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-orange-50 text-orange-700 border border-orange-200">
                                <i data-lucide="{{ $typeIcons[$contact->contact_type] ?? 'circle' }}" class="w-3 h-3"></i> {{ Contact::CONTACT_TYPES[$contact->contact_type] ?? $contact->contact_type }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="font-mono text-[10px] font-bold bg-orange-50 text-orange-700 border border-orange-200 px-1.5 py-0.5 rounded inline-block">{{ $contact->cpt_code }}</div>
                            <div class="font-mono text-[11px] font-bold text-blue-600 mt-0.5">{{ $contact->units }} u</div>
                        </td>
                        <td class="px-4 py-3 text-center font-mono text-[11px] text-slate-500">{{ $contact->duration_minutes ?: '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('clinical.tcm.contacts.show', [$contact->admission, $contact]) }}" class="p-1.5 text-slate-500 hover:text-orange-600 hover:bg-orange-50 rounded"><i data-lucide="eye" class="w-4 h-4"></i></a>
                                @can('clinical.tcm.edit')<a href="{{ route('clinical.tcm.contacts.edit', [$contact->admission, $contact]) }}" class="p-1.5 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded"><i data-lucide="pencil" class="w-4 h-4"></i></a>@endcan
                                @can('clinical.tcm.delete')
                                    <form method="POST" action="{{ route('clinical.tcm.contacts.destroy', [$contact->admission, $contact]) }}" data-confirm-delete="this contact">@csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-16 text-center text-slate-400 text-sm">
                        <i data-lucide="phone-off" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                        No contacts match your filters.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
        @if($contacts->hasPages())<div class="px-5 py-3 border-t bg-slate-50/50">{{ $contacts->links() }}</div>@endif
    </div>
</div>
@endsection
