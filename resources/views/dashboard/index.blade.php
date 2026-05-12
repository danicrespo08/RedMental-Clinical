@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">Welcome back, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
        <p class="text-slate-500 text-sm mt-1">{{ $client?->name }}</p>
    </div>

    <h2 class="text-xs font-bold text-emerald-500 uppercase tracking-widest mb-3">Clinical</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3 bg-emerald-50 border-b border-emerald-100">
                <h3 class="font-bold text-emerald-800 text-sm">PSR</h3>
                <p class="text-xs text-emerald-600">Psychosocial Rehabilitation</p>
            </div>
            <div class="p-5 grid grid-cols-2 gap-3">
                <div>
                    <div class="text-2xl font-bold text-slate-900">{{ $stats['psr_admitted'] }}</div>
                    <div class="text-xs text-slate-500">admitted</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-slate-900">{{ $stats['psr_sessions_this_month'] }}</div>
                    <div class="text-xs text-slate-500">sessions this month</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3 bg-violet-50 border-b border-violet-100">
                <h3 class="font-bold text-violet-800 text-sm">IT</h3>
                <p class="text-xs text-violet-600">Intensive Therapy</p>
            </div>
            <div class="p-5 grid grid-cols-2 gap-3">
                <div>
                    <div class="text-2xl font-bold text-slate-900">{{ $stats['it_admitted'] }}</div>
                    <div class="text-xs text-slate-500">admitted</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-slate-900">{{ $stats['it_sessions_this_month'] }}</div>
                    <div class="text-xs text-slate-500">sessions this month</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3 bg-orange-50 border-b border-orange-100">
                <h3 class="font-bold text-orange-800 text-sm">TCM</h3>
                <p class="text-xs text-orange-600">Targeted Case Management</p>
            </div>
            <div class="p-5 grid grid-cols-2 gap-3">
                <div>
                    <div class="text-2xl font-bold text-slate-900">{{ $stats['tcm_admitted'] }}</div>
                    <div class="text-xs text-slate-500">admitted</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-slate-900">{{ $stats['tcm_contacts_this_month'] }}</div>
                    <div class="text-xs text-slate-500">contacts this month</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Patients per clinic --}}
    @if($patientsByClinic->isNotEmpty())
        <h2 class="text-xs font-bold text-rose-500 uppercase tracking-widest mb-3">Patients per clinic</h2>
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-8">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr class="text-left">
                        <th class="px-5 py-3 text-[10px] font-bold text-slate-500 uppercase">Clinic</th>
                        <th class="px-5 py-3 text-[10px] font-bold text-slate-500 uppercase">City</th>
                        <th class="px-5 py-3 text-[10px] font-bold text-slate-500 uppercase text-right">Patients</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($patientsByClinic as $clinic)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-semibold text-slate-800">
                                <a href="{{ route('hhrr.clinics.show', $clinic) }}" class="hover:text-indigo-600">{{ $clinic->name }}</a>
                            </td>
                            <td class="px-5 py-3 text-sm text-slate-600">{{ $clinic->city ?: '—' }}@if($clinic->state), {{ $clinic->state }}@endif</td>
                            <td class="px-5 py-3 text-right font-bold text-indigo-600">{{ $clinic->patients_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($recentPatients->count())
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="font-semibold text-slate-900">Recently added patients</h3>
            </div>
            <table class="w-full">
                <tbody class="divide-y divide-slate-100">
                    @foreach($recentPatients as $patient)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <a href="{{ route('hhrr.patients.show', $patient) }}" class="font-semibold text-slate-800 hover:text-indigo-600">{{ $patient->full_name }}</a>
                                @if($patient->mrn)<span class="text-xs font-mono text-slate-400 ml-2">{{ $patient->mrn }}</span>@endif
                            </td>
                            <td class="px-5 py-3 text-sm text-slate-500 text-right">{{ $patient->created_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
