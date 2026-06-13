<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RedMental System') — {{ config('app.name', 'RedMental') }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

<div class="flex h-screen overflow-hidden" x-data="{ sidebar: true }">

    <aside class="hidden md:flex w-64 bg-slate-900 text-slate-200 flex-col border-r border-slate-800">
        <div class="px-5 py-5 border-b border-slate-800">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center font-black">R</div>
                <div>
                    <div class="font-bold text-white text-sm">RedMental</div>
                    @auth
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider">
                            @if(auth()->user()->isSuperAdmin())
                                Super Administrator
                            @else
                                {{ auth()->user()->client?->name ?? '—' }}
                            @endif
                        </div>
                    @endauth
                </div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6 text-sm">
            @auth
                @if(auth()->user()->isSuperAdmin())
                    {{-- Super Admin navigation --}}
                    <div>
                        <div class="px-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">System</div>
                        <a href="{{ route('dashboard') }}"
                           class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
                        </a>
                        <a href="{{ route('super-admin.clients.index') }}"
                           class="nav-link {{ request()->routeIs('super-admin.clients.*') ? 'active' : '' }}">
                            <i data-lucide="building-2" class="w-4 h-4"></i> Clients
                        </a>
                    </div>
                @else
                    {{-- Regular user navigation --}}
                    <div>
                        <div class="px-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">General</div>
                        <a href="{{ route('dashboard') }}" class="nav-link">
                            <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
                        </a>
                    </div>

                    {{-- Clinical sidebar — PSR has dedicated submodule routes
                         . IT and TCM only show admissions until
                         their dedicated tables are built in upcoming phases. --}}
                    @php
                        $psrSubmenus = [
                            'dashboard'       => ['label' => 'Dashboard',       'route' => 'clinical.psr.dashboard'],
                            'admissions'      => ['label' => 'Admissions',      'route' => 'clinical.psr.admissions.index'],
                            'group_sessions'  => ['label' => 'Group sessions',  'route' => 'clinical.psr.group_sessions.index'],
                            'assessments'     => ['label' => 'Assessments',     'route' => 'clinical.psr.assessments.index'],
                            'authorizations'  => ['label' => 'Authorizations',  'route' => 'clinical.psr.authorizations.index'],
                            'treatment_plans' => ['label' => 'Treatment plans', 'route' => 'clinical.psr.treatment_plans.index'],
                            'progress_notes'  => ['label' => 'Progress notes',  'route' => 'clinical.psr.progress_notes.index'],
                            'service_log'     => ['label' => 'Service log',     'route' => 'clinical.psr.service_log.index'],
                            'superbill'       => ['label' => 'Superbill',       'route' => 'clinical.psr.superbill.index'],
                            'discharges'      => ['label' => 'Discharges',      'route' => 'clinical.psr.discharges.index'],
                        ];
                        $itSubmenus = [
                            'dashboard'       => ['label' => 'Dashboard',       'route' => 'clinical.it.dashboard'],
                            'admissions'      => ['label' => 'Admissions',      'route' => 'clinical.it.admissions.index'],
                            'sessions'        => ['label' => 'Sessions',        'route' => 'clinical.it.sessions.index'],
                            'treatment_plans' => ['label' => 'Treatment plans', 'route' => 'clinical.it.treatment_plans.index'],
                            'authorizations'  => ['label' => 'Authorizations',  'route' => 'clinical.it.authorizations.index'],
                            'service_log'     => ['label' => 'Service log',     'route' => 'clinical.it.service_log.index'],
                            'superbill'       => ['label' => 'Superbill',       'route' => 'clinical.it.superbill.index'],
                            'discharges'      => ['label' => 'Discharges',      'route' => 'clinical.it.discharges.index'],
                        ];
                        $tcmSubmenus = [
                            'dashboard'       => ['label' => 'Dashboard',       'route' => 'clinical.tcm.dashboard'],
                            'admissions'      => ['label' => 'Admissions',      'route' => 'clinical.tcm.admissions.index'],
                            'contacts'        => ['label' => 'Contacts',        'route' => 'clinical.tcm.contacts.index'],
                            'treatment_plans' => ['label' => 'Service plans',   'route' => 'clinical.tcm.treatment_plans.index'],
                            'authorizations'  => ['label' => 'Authorizations',  'route' => 'clinical.tcm.authorizations.index'],
                            'service_log'     => ['label' => 'Service log',     'route' => 'clinical.tcm.service_log.index'],
                            'superbill'       => ['label' => 'Superbill',       'route' => 'clinical.tcm.superbill.index'],
                            'discharges'      => ['label' => 'Discharges',      'route' => 'clinical.tcm.discharges.index'],
                        ];
                        $itTcmMeta = [
                            'it'  => ['label' => 'IT',  'icon' => 'user-round-search', 'submenus' => $itSubmenus],
                            'tcm' => ['label' => 'TCM', 'icon' => 'clipboard-list',    'submenus' => $tcmSubmenus],
                        ];
                    @endphp
                    <div>
                        <div class="px-2 text-[10px] font-bold text-emerald-400 uppercase tracking-widest mb-2">Clinical</div>

                        {{-- PSR with full submodules --}}
                        @can('clinical.psr.view')
                            <details class="group" {{ request()->routeIs('clinical.psr.*') ? 'open' : '' }}>
                                <summary class="nav-link cursor-pointer list-none flex items-center justify-between {{ request()->routeIs('clinical.psr.*') ? 'active' : '' }}">
                                    <span class="flex items-center gap-2.5"><i data-lucide="users" class="w-4 h-4"></i> PSR</span>
                                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform group-open:rotate-180"></i>
                                </summary>
                                <div class="ml-4 mt-1 space-y-0.5 border-l border-slate-700 pl-3">
                                    @foreach($psrSubmenus as $key => $item)
                                        @if(\Illuminate\Support\Facades\Route::has($item['route']))
                                            @php
                                                $isActiveSub = $key === 'dashboard'
                                                    ? request()->routeIs('clinical.psr.dashboard')
                                                    : request()->routeIs("clinical.psr.{$key}.*");
                                            @endphp
                                            <a href="{{ route($item['route']) }}"
                                               class="block px-2 py-1 text-xs text-slate-400 hover:text-white rounded {{ $isActiveSub ? 'text-white font-semibold' : '' }}">{{ $item['label'] }}</a>
                                        @endif
                                    @endforeach
                                </div>
                            </details>
                        @endcan

                        {{-- IT / TCM with submodules --}}
                        @foreach($itTcmMeta as $disc => $meta)
                            @can("clinical.{$disc}.view")
                                <details class="group" {{ request()->routeIs("clinical.{$disc}.*") ? 'open' : '' }}>
                                    <summary class="nav-link cursor-pointer list-none flex items-center justify-between {{ request()->routeIs("clinical.{$disc}.*") ? 'active' : '' }}">
                                        <span class="flex items-center gap-2.5"><i data-lucide="{{ $meta['icon'] }}" class="w-4 h-4"></i> {{ $meta['label'] }}</span>
                                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform group-open:rotate-180"></i>
                                    </summary>
                                    <div class="ml-4 mt-1 space-y-0.5 border-l border-slate-700 pl-3">
                                        @foreach($meta['submenus'] as $key => $item)
                                            @if(\Illuminate\Support\Facades\Route::has($item['route']))
                                                @php
                                                    $isActiveSub = $key === 'dashboard'
                                                        ? request()->routeIs("clinical.{$disc}.dashboard")
                                                        : request()->routeIs("clinical.{$disc}.{$key}.*");
                                                @endphp
                                                <a href="{{ route($item['route']) }}"
                                                   class="block px-2 py-1 text-xs text-slate-400 hover:text-white rounded {{ $isActiveSub ? 'text-white font-semibold' : '' }}">{{ $item['label'] }}</a>
                                            @endif
                                        @endforeach
                                    </div>
                                </details>
                            @endcan
                        @endforeach
                    </div>

                    @if(auth()->user()->isClientAdmin())
                        <div>
                            <div class="px-2 text-[10px] font-bold text-amber-400 uppercase tracking-widest mb-2">Administration</div>
                            <a href="{{ route('admin.users.index') }}"
                               class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                <i data-lucide="users" class="w-4 h-4"></i> Users
                            </a>
                            <a href="{{ route('admin.roles.index') }}"
                               class="nav-link {{ request()->routeIs('admin.roles.*') && ! request()->routeIs('admin.roles.matrix*') ? 'active' : '' }}">
                                <i data-lucide="shield" class="w-4 h-4"></i> Roles
                            </a>
                            <a href="{{ route('admin.roles.matrix') }}"
                               class="nav-link {{ request()->routeIs('admin.roles.matrix*') ? 'active' : '' }}">
                                <i data-lucide="grid-2x2-check" class="w-4 h-4"></i> Permissions matrix
                            </a>
                            @can('system.audit.view')
                                <a href="{{ route('admin.audit.index') }}"
                                   class="nav-link {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">
                                    <i data-lucide="shield-check" class="w-4 h-4"></i> Audit log
                                </a>
                            @endcan
                        </div>
                    @endif
                @endif
            @endauth
        </nav>

        @auth
            <div class="border-t border-slate-800 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-slate-700 flex items-center justify-center text-sm font-bold text-white">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-white text-xs font-semibold truncate">{{ auth()->user()->name }}</div>
                        <div class="text-slate-400 text-[10px] truncate">{{ auth()->user()->email }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-slate-400 hover:text-white transition" title="Sign out">
                            <i data-lucide="log-out" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            </div>
        @endauth
    </aside>

    <main class="flex-1 overflow-y-auto">
        <div class="p-6 md:p-8">
            @yield('content')
        </div>
    </main>
</div>

<style>
    .nav-link { display: flex; align-items: center; gap: 0.625rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; color: rgb(203 213 225); transition: all 0.15s; font-weight: 500; }
    .nav-link:hover { background-color: rgb(30 41 59); color: white; }
    .nav-link.active { background-color: rgb(79 70 229); color: white; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => lucide.createIcons());

    window.RM = window.RM || {};

    RM.toast = (icon, title) => Swal.fire({
        toast: true, position: 'top-end', timer: 3500, timerProgressBar: true,
        showConfirmButton: false, icon, title,
    });

    RM.confirmDelete = (form, label = 'this record') => {
        Swal.fire({
            title: 'Delete ' + label + '?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
        return false;
    };

    // Wire any <form data-confirm-delete="label"> inside the page so authors
    // don't have to write inline JS.
    document.addEventListener('submit', (e) => {
        const form = e.target.closest('form[data-confirm-delete]');
        if (!form || form.dataset.confirmed === '1') return;
        e.preventDefault();
        Swal.fire({
            title: 'Delete ' + (form.dataset.confirmDelete || 'this record') + '?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                form.dataset.confirmed = '1';
                form.submit();
            }
        });
    });

    // Server-side flash messages → SweetAlert2 toasts
    @if(session('status'))
        RM.toast('success', @json(session('status')));
    @endif
    @if(session('error'))
        Swal.fire({ icon: 'error', title: 'Error', text: @json(session('error')) });
    @endif
    @if($errors->any())
        Swal.fire({ icon: 'error', title: 'Please fix the form', text: @json($errors->first()) });
    @endif
</script>
@stack('scripts')
</body>
</html>
