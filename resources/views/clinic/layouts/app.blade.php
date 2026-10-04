<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>@yield('title', 'Klinik Pratama') — Rekam Medis Klinik</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..24,400..600,0..1,0" rel="stylesheet"/>
    
    <!-- Tailwind CSS CDN with custom config matching Stitch & DocuVerse -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'primary': '#005c55',
                        'primary-container': '#0f766e',
                        'primary-hover': '#004741',
                        'primary-fixed': '#9cf2e8',
                        'on-primary': '#ffffff',
                        'secondary': '#64748b',
                        'surface-container-lowest': '#ffffff',
                        'surface-container-low': '#f8fafc',
                        'surface-container': '#f1f5f9',
                        'surface-container-high': '#e2e8f0',
                        'background': '#eef2f6',
                        'on-surface': '#0f172a',
                        'on-surface-variant': '#475569',
                        'tertiary': '#d97706',
                        'tertiary-container': '#b45309',
                        'error': '#dc2626',
                        'error-container': '#fee2e2',
                        'on-error-container': '#991b1b',
                    },
                    fontFamily: {
                        sans: ['Manrope', 'sans-serif'],
                        body: ['"Source Sans 3"', 'sans-serif'],
                    },
                    boxShadow: {
                        'docu': '0 10px 40px -10px rgba(0, 92, 85, 0.08), 0 1px 3px 0 rgba(0, 0, 0, 0.02)',
                        'card': '0 4px 20px 0 rgba(15, 118, 110, 0.04), 0 1px 3px 0 rgba(0, 0, 0, 0.02)',
                        'card-hover': '0 12px 28px -4px rgba(15, 118, 110, 0.12), 0 4px 8px -2px rgba(0, 0, 0, 0.04)',
                    }
                }
            }
        };
    </script>

    <!-- Alpine.js for interactive state -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    <style>
        body { font-family: 'Source Sans 3', sans-serif; }
        h1, h2, h3, h4, h5, h6, .font-heading { font-family: 'Manrope', sans-serif; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="bg-background min-h-screen text-on-surface antialiased p-3 md:p-6 lg:p-8 flex justify-center">

    <!-- Outer Frame matching DocuVerse & Stitch Architecture -->
    <div class="w-full max-w-[1720px] bg-surface-container-lowest rounded-[32px] shadow-docu flex min-h-[92vh] overflow-hidden border border-slate-200/80 relative">
        
        <!-- SIDEBAR (DocuVerse & Stitch) -->
        <aside class="w-72 bg-white flex flex-col justify-between py-7 px-5 border-r border-slate-100 flex-shrink-0 z-20">
            <div class="flex flex-col gap-6">
                <!-- Clinic Brand Header -->
                <div class="flex items-center gap-3 px-1">
                    <img alt="Logo Klinik Pratama" class="h-9 w-auto object-contain" src="/assets/images/logo_klinik_pratama.png"/>
                    <div class="flex flex-col">
                        <span class="font-heading font-bold text-primary text-base leading-tight">Klinik Pratama</span>
                        <span class="text-xs font-semibold text-secondary leading-tight">Rekam Medis Klinik</span>
                    </div>
                </div>

                <!-- Integration status -->
                <div class="flex items-center justify-between px-3 py-2 bg-amber-50/80 border border-amber-100 rounded-full">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span class="text-xs font-medium text-amber-800">Integrasi eksternal</span>
                    </div>
                    <span class="text-[11px] font-bold text-amber-700 bg-amber-100/80 px-2 py-0.5 rounded-full">Belum dikonfigurasi</span>
                </div>

                <!-- Navigation Links -->
                <nav class="flex flex-col gap-1.5 font-heading">
                    @if ($currentUser->hasRole(['pendaftaran', 'perawat', 'dokter', 'dokter_gigi']))
                    <a href="{{ route('dashboard') }}" 
                       class="flex items-center gap-3 px-4 py-2.5 rounded-2xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('dashboard*') && !request()->routeIs('dashboard.home') ? 'bg-primary-container text-white shadow-[0_4px_16px_rgba(15,118,110,0.25)]' : (request()->is('/') ? 'bg-primary-container text-white shadow-[0_4px_16px_rgba(15,118,110,0.25)]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900') }}">
                        <span class="material-symbols-outlined text-[20px]">grid_view</span>
                        <span>
                            @if ($currentUser->hasRole('pendaftaran'))
                                Pendaftaran
                            @elseif ($currentUser->hasRole('perawat'))
                                Meja Kerja Triase
                            @elseif ($currentUser->hasRole('dokter_gigi'))
                                Meja Kerja Poli Gigi
                            @else
                                Meja Kerja Poli Umum
                            @endif
                        </span>
                    </a>
                    @endif

                    @if ($currentUser->hasRole(['dokter', 'dokter_gigi']))
                    <a href="{{ route('periksa.show') }}" 
                       class="flex items-center gap-3 px-4 py-2.5 rounded-2xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('periksa*') ? 'bg-primary-container text-white shadow-[0_4px_16px_rgba(15,118,110,0.25)]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span class="material-symbols-outlined text-[20px]">stethoscope</span>
                        <span>Ruang Periksa (Dokter)</span>
                    </a>
                    @endif

                    @if ($currentUser->hasRole('farmasi'))
                    <a href="{{ route('farmasi.index') }}" 
                       class="flex items-center gap-3 px-4 py-2.5 rounded-2xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('farmasi*') ? 'bg-primary-container text-white shadow-[0_4px_16px_rgba(15,118,110,0.25)]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span class="material-symbols-outlined text-[20px]">medication</span>
                        <span>Farmasi & Dispensing</span>
                    </a>
                    @endif

                    @if ($currentUser->hasRole('perawat'))
                    <a href="{{ route('triage.index') }}" 
                       class="flex items-center gap-3 px-4 py-2.5 rounded-2xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('triage*') ? 'bg-primary-container text-white shadow-[0_4px_16px_rgba(15,118,110,0.25)]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span class="material-symbols-outlined text-[20px]">monitor_heart</span>
                        <span>Triage Keperawatan</span>
                    </a>
                    @endif

                    @if ($currentUser->hasRole(['pendaftaran', 'perawat', 'dokter', 'dokter_gigi']))
                    <a href="{{ route('pasien.index') }}" 
                       class="flex items-center gap-3 px-4 py-2.5 rounded-2xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('pasien*') ? 'bg-primary-container text-white shadow-[0_4px_16px_rgba(15,118,110,0.25)]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span class="material-symbols-outlined text-[20px]">clinical_notes</span>
                        <span>Rekam Medis Pasien</span>
                    </a>
                    @endif

                    @if ($currentUser->hasRole('kepala_klinik'))
                    <a href="{{ route('manajemen.index') }}" 
                       class="flex items-center gap-3 px-4 py-2.5 rounded-2xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('manajemen*') ? 'bg-primary-container text-white shadow-[0_4px_16px_rgba(15,118,110,0.25)]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span class="material-symbols-outlined text-[20px]">analytics</span>
                        <span>Manajemen & Laporan</span>
                    </a>
                    @endif

                    @if ($currentUser->hasRole('perekam_medis'))
                    <a href="{{ route('downtime-reconciliations.index') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-2xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('downtime-reconciliations.*') ? 'bg-primary-container text-white shadow-[0_4px_16px_rgba(15,118,110,0.25)]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span class="material-symbols-outlined text-[20px]">history</span>
                        <span>Rekonsiliasi Downtime</span>
                    </a>
                    <a href="{{ route('quality-reviews.index') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-2xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('quality-reviews.*') ? 'bg-primary-container text-white shadow-[0_4px_16px_rgba(15,118,110,0.25)]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span class="material-symbols-outlined text-[20px]">fact_check</span>
                        <span>Review Mutu RME</span>
                    </a>
                    <a href="{{ route('quality-review-follow-ups.index') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-2xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('quality-review-follow-ups.*') ? 'bg-primary-container text-white shadow-[0_4px_16px_rgba(15,118,110,0.25)]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span class="material-symbols-outlined text-[20px]">assignment_turned_in</span>
                        <span>Tindak Lanjut Mutu</span>
                    </a>
                    @endif

                    @if ($currentUser->hasRole(['dokter', 'dokter_gigi']))
                    <a href="{{ route('quality-review-follow-ups.index') }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-2xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('quality-review-follow-ups.*') ? 'bg-primary-container text-white shadow-[0_4px_16px_rgba(15,118,110,0.25)]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span class="material-symbols-outlined text-[20px]">assignment_turned_in</span>
                        <span>Tindak Lanjut Mutu</span>
                    </a>
                    @endif

                    @if ($currentUser->hasRole('admin'))
                    <a href="{{ route('admin.index') }}" 
                       class="flex items-center gap-3 px-4 py-2.5 rounded-2xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('admin*') ? 'bg-primary-container text-white shadow-[0_4px_16px_rgba(15,118,110,0.25)]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span class="material-symbols-outlined text-[20px]">settings</span>
                        <span>Pengaturan & IT</span>
                    </a>
                    @endif
                </nav>
            </div>

            <!-- Bottom Section: current account -->
            <div class="flex flex-col gap-4">
                <!-- Current User Profile Card -->
                <div class="flex items-center gap-3 p-2.5 rounded-2xl bg-slate-50 border border-slate-100">
                    <div class="flex flex-col min-w-0 flex-1">
                        <span class="font-heading text-xs font-bold text-slate-900 truncate">{{ $currentUser->name }}</span>
                        <span class="text-[11px] text-slate-500 truncate">{{ \App\Enums\UserRole::tryFrom($currentUser->role)?->label() ?? $currentUser->role }}</span>
                        <span class="text-[10px] font-mono text-primary font-semibold truncate">{{ $currentUser->nip ?: 'NIP belum diisi' }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <a class="text-[10px] font-semibold text-primary" href="{{ route('password.edit') }}">Kata sandi</a>
                        <form action="{{ route('logout') }}" method="POST">@csrf<button class="text-[10px] font-semibold text-red-700" type="submit">Keluar</button></form>
                    </div>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50/60 overflow-y-auto">
            
            <!-- TOP BAR (DocuVerse & Stitch) -->
            <header class="h-20 bg-white/95 backdrop-blur-md border-b border-slate-100 px-6 lg:px-8 flex items-center justify-between sticky top-0 z-30 gap-4">
                <!-- Search Bar with Live Suggestions -->
                @if ($currentUser->hasRole('pendaftaran', 'dokter', 'dokter_gigi'))
                <div class="flex-1 max-w-xl relative" x-data="{ query: '', results: [], open: false }">
                    <form action="{{ route('pasien.index') }}" method="GET">
                        <div class="relative flex items-center">
                            <span class="material-symbols-outlined absolute left-4 text-slate-400 text-[20px]">search</span>
                            <input 
                                type="text" 
                                name="q"
                                x-model="query"
                                @input.debounce.300ms="if(query.length >= 2) { fetch('/api/search/patients?q=' + encodeURIComponent(query)).then(r => r.json()).then(d => { results = d; open = true; }); } else { open = false; }"
                                @click.away="open = false"
                                placeholder="Cari pasien cepat (nama, NIP, nomor rekam medis)..." 
                                class="w-full bg-slate-50/90 hover:bg-slate-100/70 focus:bg-white text-slate-900 text-sm font-medium pl-11 pr-4 py-2.5 rounded-full border border-slate-200/80 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-slate-400"
                            />
                        </div>
                    </form>

                    <!-- Autocomplete Dropdown -->
                    <div x-show="open && results.length > 0" x-cloak class="absolute left-0 right-0 top-12 mt-1 bg-white rounded-2xl shadow-xl border border-slate-100 p-2 z-50">
                        <template x-for="p in results" :key="p.id">
                            <a :href="'/pasien/' + p.id" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-xs" x-text="p.jenis_kelamin || '—'"></div>
                                    <div>
                                        <div class="text-xs font-bold text-slate-900" x-text="p.nama_lengkap"></div>
                                        <div class="text-[11px] text-slate-500 font-mono" x-text="p.no_rm + ' • ' + (p.nip ? 'NIP: ' + p.nip : 'Masyarakat')"></div>
                                    </div>
                                </div>
                                <span class="material-symbols-outlined text-slate-400 text-base">chevron_right</span>
                            </a>
                        </template>
                    </div>
                </div>
                @else
                <div class="flex-1"></div>
                @endif

                <!-- Right Utility Bar -->
                <div class="flex items-center gap-3.5 flex-shrink-0">
                    
                    <!-- Date Pill -->
                    <div class="hidden md:flex items-center gap-2 px-3.5 py-1.5 bg-slate-100 border border-slate-200/70 rounded-full text-xs font-semibold text-slate-700">
                        <span class="material-symbols-outlined text-primary text-[18px]">calendar_today</span>
                        <span>{{ Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
                    </div>

                    <div class="hidden sm:block text-xs font-semibold text-slate-600">{{ \App\Enums\UserRole::tryFrom($currentUser->role)?->label() ?? $currentUser->role }}</div>

                    <!-- Notification Button -->
                    <button type="button" class="relative p-2 rounded-full border border-slate-200 text-slate-600 hover:bg-slate-100 transition-colors">
                        <span class="material-symbols-outlined text-[20px]">notifications</span>
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500"></span>
                    </button>

                    <!-- Export Report Action Button (DocuVerse Emerald Style) -->
                    @if ($currentUser->hasRole('kepala_klinik'))
                    <a href="{{ route('export.report') }}" class="flex items-center gap-2 px-4 py-2 rounded-full bg-primary hover:bg-primary-hover text-white text-xs font-bold shadow-[0_4px_16px_rgba(0,92,85,0.24)] transition-all transform hover:-translate-y-0.5">
                        <span class="material-symbols-outlined text-[18px]">ios_share</span>
                        <span>Export Laporan</span>
                    </a>
                    @endif
                </div>
            </header>

            <!-- FLASH ALERTS -->
            @if (session('success'))
                <div class="mx-6 lg:mx-8 mt-4 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-between text-emerald-900 shadow-xs" x-data="{ show: true }" x-show="show">
                    <div class="flex items-center gap-2 text-sm font-semibold">
                        <span class="material-symbols-outlined text-emerald-600">check_circle</span>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button @click="show = false" class="text-emerald-700 hover:text-emerald-900">
                        <span class="material-symbols-outlined text-base">close</span>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div class="mx-6 lg:mx-8 mt-4 p-4 rounded-2xl bg-red-50 border border-red-200 flex items-center justify-between text-red-900 shadow-xs" x-data="{ show: true }" x-show="show">
                    <div class="flex items-center gap-2 text-sm font-semibold">
                        <span class="material-symbols-outlined text-red-600">error</span>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button @click="show = false" class="text-red-700 hover:text-red-900">
                        <span class="material-symbols-outlined text-base">close</span>
                    </button>
                </div>
            @endif

            @if ($errors->any())
                <div class="mx-6 lg:mx-8 mt-4 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-900 shadow-xs" role="alert">
                    <p class="text-sm font-bold">Periksa kembali isian berikut:</p>
                    <ul class="mt-2 list-disc pl-5 text-sm">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <!-- MAIN BODY INJECTION -->
            <main class="p-6 lg:p-8 flex-1">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
