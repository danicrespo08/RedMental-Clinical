@extends('layouts.app')
@section('title', 'PSR — Service log entry')

@section('content')
    <a href="{{ route('clinical.psr.service_log.index') }}" class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center gap-1 mb-3"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back to service log</a>
    <h1 class="text-2xl font-bold text-slate-900 mb-6">Service log entry — {{ $log->service_date->format('M j, Y') }}</h1>

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <dl class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
            <div><dt class="text-xs font-bold text-slate-500 uppercase">Patient</dt><dd>{{ $log->patient?->full_name ?? '—' }}</dd></div>
            <div><dt class="text-xs font-bold text-slate-500 uppercase">Therapist</dt><dd>{{ $log->therapist?->full_name ?? '—' }}</dd></div>
            <div><dt class="text-xs font-bold text-slate-500 uppercase">Clinic</dt><dd>{{ $log->clinic?->name ?? '—' }}</dd></div>
            <div><dt class="text-xs font-bold text-slate-500 uppercase">Time</dt><dd>{{ $log->start_time }} → {{ $log->end_time }}</dd></div>
            <div><dt class="text-xs font-bold text-slate-500 uppercase">Units</dt><dd class="font-mono font-bold">{{ $log->units }}</dd></div>
            <div><dt class="text-xs font-bold text-slate-500 uppercase">Service</dt><dd class="font-mono">{{ $log->service_code }} {{ $log->modifier }}</dd></div>
            <div><dt class="text-xs font-bold text-slate-500 uppercase">Place of service</dt><dd class="font-mono">{{ $log->place_of_service ?: '—' }}</dd></div>
            <div><dt class="text-xs font-bold text-slate-500 uppercase">Diagnosis</dt><dd>{{ $log->diagnosis_code }} {{ $log->diagnosis_description }}</dd></div>
            <div><dt class="text-xs font-bold text-slate-500 uppercase">Source</dt><dd class="uppercase">{{ str_replace('_', ' ', $log->source_type) }}</dd></div>
            <div><dt class="text-xs font-bold text-slate-500 uppercase">Authorization</dt><dd class="font-mono">{{ $log->authorization?->auth_number ?: ($log->auth_number ?: '—') }}</dd></div>
            <div><dt class="text-xs font-bold text-slate-500 uppercase">Billing status</dt><dd class="uppercase">{{ $log->billing_status }}</dd></div>
            <div><dt class="text-xs font-bold text-slate-500 uppercase">Claim #</dt><dd class="font-mono">{{ $log->claim_number ?: '—' }}</dd></div>
            <div><dt class="text-xs font-bold text-slate-500 uppercase">Paid amount</dt><dd class="font-mono">{{ $log->paid_amount ? '$'.number_format((float)$log->paid_amount, 2) : '—' }}</dd></div>
            <div><dt class="text-xs font-bold text-slate-500 uppercase">Retroactive</dt><dd>{{ $log->is_retroactive ? 'Yes' : 'No' }}</dd></div>
            <div><dt class="text-xs font-bold text-slate-500 uppercase">Has progress note</dt><dd>{{ $log->has_progress_note ? 'Yes' : 'No' }}</dd></div>
        </dl>
        @if($log->notes)<div class="mt-4 pt-4 border-t border-slate-100"><div class="text-xs font-bold text-slate-500 uppercase mb-1">Notes</div><div class="text-sm whitespace-pre-line">{{ $log->notes }}</div></div>@endif
    </div>
@endsection
