@props(['route' => 'clinical.psr.admissions.index', 'label' => 'Back'])
<a href="{{ route($route) }}" class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center gap-1 mb-3">
    <i data-lucide="arrow-left" class="w-4 h-4"></i> {{ $label }}
</a>
