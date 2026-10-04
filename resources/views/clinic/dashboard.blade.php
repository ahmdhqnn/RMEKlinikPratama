@extends('clinic.layouts.app')

@section('title', $currentUser->hasRole('pendaftaran') ? 'Dashboard Pendaftaran' : ($currentUser->hasRole('perawat') ? 'Dashboard Triase' : ($currentUser->hasRole('dokter_gigi') ? 'Dashboard Poli Gigi' : 'Dashboard Poli Umum')))

@section('content')
<div class="flex flex-col gap-8 pb-12">
    
    <!-- Top Overview Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold font-heading text-slate-900 tracking-tight">
                @if ($currentUser->hasRole('pendaftaran'))
                    Pendaftaran Pasien
                @elseif ($currentUser->hasRole('perawat'))
                    Meja Kerja Triase
                @elseif ($currentUser->hasRole('dokter_gigi'))
                    Meja Kerja Poli Gigi
                @else
                    Meja Kerja Poli Umum
                @endif
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                @if ($currentUser->hasRole('pendaftaran'))
                    Registrasikan kedatangan, kelola identitas pasien, dan pantau antrean layanan.
                @elseif ($currentUser->hasRole('perawat'))
                    Tinjau pasien yang menunggu triase dan lanjutkan pencatatan tanda vital.
                @elseif ($currentUser->hasRole('dokter_gigi'))
                    Fokus pada antrean poli gigi dan pemeriksaan pasien yang menjadi lingkup layanan Anda.
                @else
                    Fokus pada antrean poli umum dan pemeriksaan pasien yang menjadi lingkup layanan Anda.
                @endif
            </p>
        </div>
        <div class="flex items-center gap-3">
            @if ($currentUser->hasRole('pendaftaran'))
            <button onclick="document.getElementById('formRegistrasiPasien').scrollIntoView({behavior: 'smooth'})" class="flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-primary hover:bg-primary-hover text-white text-xs font-bold font-heading shadow-md transition-all">
                <span class="material-symbols-outlined text-[18px]">person_add</span>
                <span>+ Registrasi Pasien Baru</span>
            </button>
            @endif
        </div>
    </div>

    @if ($currentUser->hasRole('pendaftaran'))
    <!-- Registration overview metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        
        <!-- Card 1: Total Pasien Hari Ini -->
        <div class="bg-white p-5 rounded-[24px] border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-300 flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block font-heading">Total Pasien Hari Ini</span>
                    <div class="flex items-baseline gap-2 mt-1.5">
                        <span class="text-3xl font-extrabold font-heading text-slate-900">{{ $stats['total_pasien'] }}</span>
                        <span class="text-xs font-bold text-primary font-heading">Pasien</span>
                    </div>
                    <span class="text-xs text-slate-400 mt-0.5 block">Antrean terdaftar hari ini</span>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">groups</span>
                </div>
            </div>
            <div class="mt-6">
                <!-- Vertical Bar Indicator (Reconciled Green vs Pending Grey) -->
                <div class="flex items-center gap-[2.5px] h-6 w-full overflow-hidden">
                    @for ($i = 0; $i < 20; $i++)
                        <span class="w-[3px] rounded-[1px] bg-primary" style="height: {{ [18, 24, 16, 22, 24, 15, 20, 24, 18, 22][$i % 10] }}px"></span>
                    @endfor
                    @for ($i = 0; $i < 6; $i++)
                        <span class="w-[3px] rounded-[1px] bg-slate-200" style="height: {{ [16, 22, 14, 20, 18, 24][$i % 6] }}px"></span>
                    @endfor
                </div>
                <div class="flex items-center justify-between mt-2 text-xs font-semibold">
                    <span class="text-primary">{{ $stats['selesai_pasien'] }} Terselesaikan</span>
                    <span class="text-slate-400">{{ $stats['pending_pasien'] }} Pending</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Antrean Poli Umum -->
        <div class="bg-white p-5 rounded-[24px] border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-300 flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block font-heading">Antrean Poli Umum</span>
                    <div class="flex items-baseline gap-2 mt-1.5">
                        <span class="text-3xl font-extrabold font-heading text-slate-900">{{ $stats['poli_umum_total'] }}</span>
                        <span class="text-xs font-bold text-amber-700 font-heading">Pasien</span>
                    </div>
                    <span class="text-xs text-slate-400 mt-0.5 block">Poli Umum</span>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">stethoscope</span>
                </div>
            </div>
            <div class="mt-6">
                <!-- Vertical Amber Bar Indicator -->
                <div class="flex items-center gap-[2.5px] h-6 w-full overflow-hidden">
                    @for ($i = 0; $i < 15; $i++)
                        <span class="w-[3px] rounded-[1px] bg-amber-600" style="height: {{ [20, 24, 15, 22, 18, 24, 16, 20][$i % 8] }}px"></span>
                    @endfor
                    @for ($i = 0; $i < 7; $i++)
                        <span class="w-[3px] rounded-[1px] bg-slate-200" style="height: {{ [18, 24, 15, 20][$i % 4] }}px"></span>
                    @endfor
                </div>
                <div class="flex items-center justify-between mt-2 text-xs font-semibold">
                    <span class="text-amber-700">{{ $stats['poli_umum_selesai'] }} Terlayani</span>
                    <span class="text-slate-400">{{ $stats['poli_umum_tunggu'] }} Tunggu</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Antrean Poli Gigi -->
        <div class="bg-white p-5 rounded-[24px] border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-300 flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block font-heading">Antrean Poli Gigi</span>
                    <div class="flex items-baseline gap-2 mt-1.5">
                        <span class="text-3xl font-extrabold font-heading text-slate-900">{{ $stats['poli_gigi_total'] }}</span>
                        <span class="text-xs font-bold text-purple-700 font-heading">Pasien</span>
                    </div>
                    <span class="text-xs text-slate-400 mt-0.5 block">Poli Gigi</span>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">dentistry</span>
                </div>
            </div>
            <div class="mt-6">
                <!-- Vertical Purple/Warm Bar Indicator -->
                <div class="flex items-center gap-[2.5px] h-6 w-full overflow-hidden">
                    @for ($i = 0; $i < 13; $i++)
                        <span class="w-[3px] rounded-[1px] bg-purple-600" style="height: {{ [22, 16, 24, 18, 20, 24][$i % 6] }}px"></span>
                    @endfor
                    @for ($i = 0; $i < 8; $i++)
                        <span class="w-[3px] rounded-[1px] bg-slate-200" style="height: {{ [16, 22, 18, 20][$i % 4] }}px"></span>
                    @endfor
                </div>
                <div class="flex items-center justify-between mt-2 text-xs font-semibold">
                    <span class="text-purple-700">{{ $stats['poli_gigi_selesai'] }} Terlayani</span>
                    <span class="text-slate-400">{{ $stats['poli_gigi_tunggu'] }} Tunggu</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Resep Selesai Farmasi -->
        <div class="bg-white p-5 rounded-[24px] border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-300 flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block font-heading">Resep Selesai Farmasi</span>
                    <div class="flex items-baseline gap-2 mt-1.5">
                        <span class="text-3xl font-extrabold font-heading text-slate-900">{{ $stats['resep_total'] }}</span>
                        <span class="text-xs font-bold text-teal-700 font-heading">Resep</span>
                    </div>
                    <span class="text-xs text-emerald-600 font-semibold mt-0.5 block">{{ $stats['resep_selesai'] }} diserahkan</span>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">vaccines</span>
                </div>
            </div>
            <div class="mt-6">
                <!-- Vertical Teal Bar Indicator -->
                <div class="flex items-center gap-[2.5px] h-6 w-full overflow-hidden">
                    @for ($i = 0; $i < 18; $i++)
                        <span class="w-[3px] rounded-[1px] bg-teal-600" style="height: {{ [20, 24, 18, 22, 24, 16, 22][$i % 7] }}px"></span>
                    @endfor
                    @for ($i = 0; $i < 5; $i++)
                        <span class="w-[3px] rounded-[1px] bg-slate-200" style="height: {{ [18, 22, 16][$i % 3] }}px"></span>
                    @endfor
                </div>
                <div class="flex items-center justify-between mt-2 text-xs font-semibold">
                    <span class="text-teal-700">{{ $stats['resep_selesai'] }} Terambil</span>
                    <span class="text-slate-400">{{ $stats['resep_antre'] }} Antre Dispensing</span>
                </div>
            </div>
        </div>

    </div>

    <!-- /Registration overview metrics -->
    @endif

    <!-- DOCTOR CARDS SECTION (DOCUVERSE REFERENCE STYLE) -->
    @if ($currentUser->hasRole('pendaftaran'))
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold font-heading text-slate-900">Tenaga Medis & Jadwal Praktek Aktif</h2>
                <p class="text-xs text-slate-500">Dokter aktif berdasarkan akun dan peran pada sistem klinik.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-full bg-slate-100 text-xs font-bold text-slate-700">Semua ({{ $doctors->count() }})</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            @foreach ($doctors as $doc)
                <div class="bg-white p-4 rounded-[22px] border border-slate-100 shadow-card hover:shadow-card-hover transition-all flex flex-col justify-between group">
                    <div>
                        <div class="flex items-start justify-between">
                            <div class="min-w-0 pr-1">
                                <h3 class="font-heading font-bold text-xs text-slate-900 truncate" title="{{ $doc->name }}">{{ $doc->name }}</h3>
                                <p class="text-[11px] text-slate-400 truncate">{{ $doc->specialty }}</p>
                            </div>
                            <span class="material-symbols-outlined text-slate-300 text-[18px]">more_vert</span>
                        </div>

                        <!-- Portrait Image cutout -->
                        <div class="my-3 flex justify-center">
                            <div class="relative w-20 h-20 rounded-2xl overflow-hidden bg-slate-100">
                                <img src="{{ $doc->avatar_url ?? '/assets/images/166de6af4e856812b08f19dc83cc9152.webp' }}" class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300" alt="{{ $doc->name }}"/>
                                <span class="absolute bottom-1 right-1 w-3 h-3 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom stats columns -->
                    <div class="border-t border-slate-50 pt-2 grid grid-cols-2 text-center text-xs">
                        <div class="border-r border-slate-50 pr-1">
                            <span class="font-bold text-slate-800 block">Poli</span>
                            <span class="text-[10px] text-slate-500 font-semibold">{{ $doc->role === 'dokter_gigi' ? 'Gigi' : 'Umum' }}</span>
                        </div>
                        <div class="pl-1">
                            <span class="font-bold text-slate-800 block">Poli</span>
                            <span class="text-[10px] text-slate-400">Siap Periksa</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @endif

    <!-- MAIN TWO-COLUMN SPLIT: REGISTRASI PASIEN & LIVE QUEUE BOARD -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT COLUMN: Registrasi Pasien Baru / Lama (Front Office) (5 cols) -->
        @if ($currentUser->hasRole('pendaftaran'))
        <div id="formRegistrasiPasien" class="lg:col-span-5 bg-white p-6 rounded-[28px] border border-slate-100 shadow-card flex flex-col gap-5">
            <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-lg font-bold font-heading text-slate-900">Registrasi Pasien & Antrean</h2>
                    <p class="text-xs text-slate-500">Pendaftaran dan antrean tersimpan di sistem klinik.</p>
                </div>
                <span class="px-2.5 py-1 rounded-full bg-slate-50 text-[11px] font-bold text-slate-700 flex items-center gap-1 border border-slate-200">
                    Sistem Klinik
                </span>
            </div>

            <form action="{{ route('antrean.store') }}" method="POST" class="flex flex-col gap-4" x-data="{ unknownIdentity: @js((bool) old('identitas_tidak_diketahui', false)) }">
                @csrf
                <input type="hidden" name="identitas_tidak_diketahui" value="0"/>

                <label class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-950">
                    <input type="checkbox" name="identitas_tidak_diketahui" value="1" x-model="unknownIdentity" class="mt-0.5 rounded border-amber-400"/>
                    <span><strong>Pasien darurat dengan identitas belum diketahui</strong><br/>Gunakan hanya saat identitas pasien memang belum dapat dipastikan. Sistem akan memberi nomor rekam medis sementara dan menandai antrean untuk triase segera.</span>
                </label>

                <!-- Input NIP / NIK -->
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between text-xs font-semibold text-slate-700">
                        <label for="input_nip">Identitas pasien</label>
                        <span class="text-slate-500 text-[11px]">NIK/NIP opsional untuk pasien umum</span>
                    </div>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined absolute left-3 text-slate-400 text-[18px]">badge</span>
                        <input type="text" name="nip" id="input_nip" value="{{ old('nip') }}" :disabled="unknownIdentity" class="w-full bg-slate-50 focus:bg-white text-slate-900 text-xs font-mono pl-10 pr-3 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all disabled:opacity-50" placeholder="NIP pegawai (opsional)"/>
                    </div>
                    <input type="text" name="nik" inputmode="numeric" maxlength="16" value="{{ old('nik') }}" :disabled="unknownIdentity" class="w-full bg-slate-50 focus:bg-white text-slate-900 text-xs font-mono px-3 py-2.5 rounded-xl border border-slate-200 disabled:opacity-50" placeholder="NIK 16 digit (opsional)"/>
                </div>

                <label class="text-xs font-semibold text-slate-700">Jenis pasien
                    <select name="jenis_pasien" class="mt-1 w-full rounded-xl border-slate-200 bg-slate-50 p-2.5">
                        @foreach (['Umum', 'BPJS', 'Asuransi', 'Pegawai', 'Lainnya'] as $jenisPasien)
                            <option value="{{ $jenisPasien }}" @selected(old('jenis_pasien', 'Umum') === $jenisPasien)>{{ $jenisPasien }}</option>
                        @endforeach
                    </select>
                </label>

                <!-- Nama Pasien -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-slate-700" for="input_nama">Nama Lengkap Pasien & Gelar</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined absolute left-3 text-slate-400 text-[18px]">person</span>
                        <input type="text" name="nama_lengkap" id="input_nama" value="{{ old('nama_lengkap') }}" :required="!unknownIdentity" class="w-full bg-slate-50 focus:bg-white text-slate-900 text-xs pl-10 pr-3 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" placeholder="Nama lengkap sesuai identitas"/>
                    </div>
                </div>

                <details class="rounded-xl border border-slate-200 p-3">
                    <summary class="cursor-pointer text-xs font-bold text-slate-700">Data kontak, penjamin, dan alergi (bila tersedia)</summary>
                    <div class="mt-3 grid grid-cols-1 gap-3 text-xs sm:grid-cols-2">
                        <label class="font-semibold">Tempat lahir<input name="tempat_lahir" value="{{ old('tempat_lahir') }}" maxlength="100" class="mt-1 w-full rounded-xl border-slate-200 p-2"></label>
                        <label class="font-semibold">Golongan darah<input name="golongan_darah" value="{{ old('golongan_darah') }}" maxlength="10" class="mt-1 w-full rounded-xl border-slate-200 p-2"></label>
                        <label class="font-semibold">Nomor BPJS<input name="no_bpjs" value="{{ old('no_bpjs') }}" maxlength="50" class="mt-1 w-full rounded-xl border-slate-200 p-2"></label>
                        <label class="font-semibold">Nomor telepon<input name="no_hp" value="{{ old('no_hp') }}" maxlength="25" class="mt-1 w-full rounded-xl border-slate-200 p-2"></label>
                        <label class="font-semibold sm:col-span-2">Alamat<textarea name="alamat" maxlength="2000" rows="2" class="mt-1 w-full rounded-xl border-slate-200 p-2">{{ old('alamat') }}</textarea></label>
                        <label class="font-semibold sm:col-span-2">Riwayat alergi yang disampaikan pasien<textarea name="riwayat_alergi" maxlength="2000" rows="2" class="mt-1 w-full rounded-xl border-slate-200 p-2">{{ old('riwayat_alergi') }}</textarea></label>
                        <label class="font-semibold sm:col-span-2">Keterangan alergi<textarea name="alergi_keterangan" maxlength="2000" rows="2" class="mt-1 w-full rounded-xl border-slate-200 p-2">{{ old('alergi_keterangan') }}</textarea></label>
                    </div>
                </details>

                <!-- Jenis Kelamin & Tanggal Lahir (2 Cols) -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-700">Jenis Kelamin</label>
                        <div class="grid grid-cols-2 gap-2" x-data="{ gender: '{{ old('jenis_kelamin', '') }}' }">
                            <label :class="gender === 'L' ? 'bg-primary text-white font-bold' : 'bg-slate-100 text-slate-600'" class="flex items-center justify-center gap-1 py-2 px-2 rounded-xl text-xs cursor-pointer transition-all">
                                <input type="radio" name="jenis_kelamin" value="L" x-model="gender" class="hidden" :required="!unknownIdentity"/>
                                <span>Laki-laki</span>
                            </label>
                            <label :class="gender === 'P' ? 'bg-primary text-white font-bold' : 'bg-slate-100 text-slate-600'" class="flex items-center justify-center gap-1 py-2 px-2 rounded-xl text-xs cursor-pointer transition-all">
                                <input type="radio" name="jenis_kelamin" value="P" x-model="gender" class="hidden" :required="!unknownIdentity"/>
                                <span>Perempuan</span>
                            </label>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-700" for="input_tgl_lahir">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" id="input_tgl_lahir" value="{{ old('tanggal_lahir') }}" :required="!unknownIdentity" max="{{ today()->toDateString() }}" class="w-full bg-slate-50 focus:bg-white text-slate-900 text-xs font-mono px-3 py-2 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-primary/20"/>
                    </div>
                </div>

                <!-- Unit Kerja / Biro -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-slate-700" for="input_unit">Unit kerja (opsional)</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined absolute left-3 text-slate-400 text-[18px]">apartment</span>
                        <input type="text" name="unit_kerja" id="input_unit" value="{{ old('unit_kerja') }}" class="w-full bg-slate-50 focus:bg-white text-slate-900 text-xs pl-10 pr-3 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-primary/20"/>
                    </div>
                </div>

                <!-- Pilihan Poli Tujuan (Radio Cards) -->
                <div class="flex flex-col gap-1.5" x-data="{ selectedPoli: '{{ old('poli', '') }}' }">
                    <span class="text-xs font-semibold text-slate-700">Pilihan Poli Tujuan</span>
                    <div class="grid grid-cols-2 gap-3">
                        
                        <!-- Poli Umum -->
                        <label :class="selectedPoli === 'umum' ? 'ring-2 ring-primary bg-emerald-50/50' : 'bg-slate-50'" class="cursor-pointer p-3.5 rounded-2xl border border-slate-200/80 transition-all flex flex-col justify-between gap-2 shadow-xs">
                            <input type="radio" name="poli" value="umum" x-model="selectedPoli" class="hidden" required/>
                            <div class="flex items-center justify-between">
                                <div class="w-8 h-8 rounded-xl bg-primary text-white flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[18px]">medical_services</span>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">{{ $stats['poli_umum_tunggu'] }} menunggu</span>
                            </div>
                            <div>
                                <span class="font-heading font-bold text-xs text-slate-900 block">Poli Umum</span>
                                <span class="text-[11px] text-slate-500">Antrean layanan umum</span>
                            </div>
                        </label>

                        <!-- Poli Gigi -->
                        <label :class="selectedPoli === 'gigi' ? 'ring-2 ring-primary bg-emerald-50/50' : 'bg-slate-50'" class="cursor-pointer p-3.5 rounded-2xl border border-slate-200/80 transition-all flex flex-col justify-between gap-2 shadow-xs">
                            <input type="radio" name="poli" value="gigi" x-model="selectedPoli" class="hidden"/>
                            <div class="flex items-center justify-between">
                                <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[18px]">dentistry</span>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-100 text-purple-800">{{ $stats['poli_gigi_tunggu'] }} menunggu</span>
                            </div>
                            <div>
                                <span class="font-heading font-bold text-xs text-slate-900 block">Poli Gigi</span>
                                <span class="text-[11px] text-slate-500">Antrean layanan gigi</span>
                            </div>
                        </label>

                    </div>
                </div>

                <!-- Keluhan Singkat -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-slate-700" for="input_keluhan">Keluhan Singkat / Alasan Kunjungan</label>
                    <textarea name="keluhan_singkat" id="input_keluhan" rows="2" maxlength="2000" class="w-full bg-slate-50 focus:bg-white text-slate-900 text-xs px-3 py-2 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-primary/20 resize-none" placeholder="Catat keluhan sebagaimana disampaikan pasien.">{{ old('keluhan_singkat') }}</textarea>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-3 rounded-xl bg-primary hover:bg-primary-hover text-white font-heading font-bold text-xs shadow-md flex items-center justify-center gap-2 transition-all transform active:scale-95">
                    <span class="material-symbols-outlined text-[18px]">local_printshop</span>
                    <span>+ Daftarkan & Cetak Nomor Antrean</span>
                </button>
            </form>
        </div>
        @endif

        <!-- RIGHT COLUMN: Papan Antrean Pasien Berjalan (7 cols) -->
                <div class="{{ $currentUser->hasRole('pendaftaran') ? 'lg:col-span-7' : 'lg:col-span-12' }} flex flex-col gap-4">
            
            <!-- Queue Filter Tabs -->
            <div class="bg-white p-3 rounded-2xl border border-slate-100 shadow-card flex items-center justify-between gap-2 overflow-x-auto font-heading text-xs font-bold">
                <div class="flex items-center gap-1.5">
                    @if ($currentUser->hasRole('pendaftaran'))
                    <a href="{{ route('dashboard', ['poli' => 'all']) }}" class="px-3.5 py-1.5 rounded-xl transition-all whitespace-nowrap {{ $filterPoli === 'all' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                        Semua Antrean ({{ $queues->count() }})
                    </a>
                    <a href="{{ route('dashboard', ['poli' => 'umum']) }}" class="px-3.5 py-1.5 rounded-xl transition-all whitespace-nowrap {{ $filterPoli === 'umum' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                        Poli Umum
                    </a>
                    <a href="{{ route('dashboard', ['poli' => 'gigi']) }}" class="px-3.5 py-1.5 rounded-xl transition-all whitespace-nowrap {{ $filterPoli === 'gigi' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                        Poli Gigi
                    </a>
                    <a href="{{ route('dashboard', ['poli' => 'selesai']) }}" class="px-3.5 py-1.5 rounded-xl transition-all whitespace-nowrap {{ $filterPoli === 'selesai' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                        Selesai
                    </a>
                    @else
                    <span class="px-3.5 py-1.5 rounded-xl bg-primary text-white shadow-xs whitespace-nowrap">
                        {{ $currentUser->hasRole('perawat') ? 'Menunggu Triase' : ($currentUser->hasRole('dokter_gigi') ? 'Antrean Poli Gigi Anda' : 'Antrean Poli Umum Anda') }}
                    </span>
                    @endif
                </div>
            </div>

            <!-- Queue Cards List -->
            <div class="flex flex-col gap-3">
                @forelse ($queues as $q)
                    @php
                        $badge = $q->status_badge;
                    @endphp
                    <div class="bg-white p-4 rounded-2xl border {{ $q->status === 'sedang_diperiksa' ? 'border-primary ring-2 ring-primary/20 bg-emerald-50/20' : 'border-slate-100' }} shadow-card hover:shadow-card-hover transition-all flex flex-col md:flex-row md:items-center justify-between gap-4">
                        
                        <!-- Left Info: Queue number + patient -->
                        <div class="flex items-start gap-3.5 min-w-0">
                            <div class="w-12 h-12 rounded-2xl {{ $q->poli === 'umum' ? 'bg-emerald-100 text-emerald-800' : 'bg-purple-100 text-purple-800' }} flex flex-col items-center justify-center flex-shrink-0 font-heading">
                                <span class="text-xs font-bold uppercase">{{ $q->poli }}</span>
                                <span class="text-sm font-extrabold">{{ $q->nomor_antrean }}</span>
                            </div>

                            <div class="flex flex-col min-w-0">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-heading font-bold text-sm text-slate-900 truncate">{{ $q->patient->nama_lengkap }}</h4>
                                    @if ($q->is_emergency)
                                        <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-extrabold text-red-800">DARURAT</span>
                                    @elseif ($q->patient->identity_status === 'provisional')
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">IDENTITAS SEMENTARA</span>
                                    @endif
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $badge['bg'] }} border">
                                        {{ $badge['label'] }}
                                    </span>
                                </div>
                                <div class="text-xs text-slate-500 font-mono mt-0.5 truncate">
                                    No. RM: {{ $q->patient->no_rm }} • {{ $q->patient->nip ? 'NIP: ' . $q->patient->nip : 'Masyarakat' }}
                                </div>
                                @if ($currentUser->hasRole(['perawat', 'dokter', 'dokter_gigi']))
                                <div class="text-xs text-slate-600 mt-1 line-clamp-1 italic">
                                    "{{ $q->keluhan_singkat }}"
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Right Actions -->
                        <div class="flex items-center justify-between md:justify-end gap-2 flex-shrink-0 border-t md:border-t-0 pt-2 md:pt-0 border-slate-100">
                            
                            <!-- Call patient button -->
                            @if ($q->status === 'siap_periksa' && (($q->poli === 'umum' && $currentUser->hasRole('dokter')) || ($q->poli === 'gigi' && $currentUser->hasRole('dokter_gigi'))))
                                <form action="{{ route('antrean.call', $q->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold font-heading shadow-xs transition-all">
                                        <span class="material-symbols-outlined text-[16px]">stethoscope</span>
                                        <span>Panggil Dokter</span>
                                    </button>
                                </form>
                            @elseif ($q->status === 'menunggu_triage' && $currentUser->hasRole('perawat'))
                                <a href="{{ route('triage.index') }}" class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold font-heading shadow-xs transition-all">
                                    <span class="material-symbols-outlined text-[16px]">monitor_heart</span>
                                    <span>Input TTV (Perawat)</span>
                                </a>
                            @elseif ($q->status === 'sedang_diperiksa' && $currentUser->hasRole($q->poli === 'gigi' ? 'dokter_gigi' : 'dokter'))
                                <a href="{{ route('periksa.show', $q->id) }}" class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold font-heading shadow-xs transition-all animate-pulse">
                                    <span class="material-symbols-outlined text-[16px]">edit_document</span>
                                    <span>Lanjut SOAP</span>
                                </a>
                            @elseif (($q->status === 'menunggu_obat' || $q->status === 'sedang_dispensing') && $currentUser->hasRole('farmasi'))
                                <a href="{{ route('farmasi.index') }}" class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold font-heading shadow-xs transition-all">
                                    <span class="material-symbols-outlined text-[16px]">medication</span>
                                    <span>Farmasi</span>
                                </a>
                            @endif

                            <!-- Print Ticket -->
                            @if ($currentUser->hasRole('pendaftaran'))
                            <a href="{{ route('antrean.print', $q->id) }}" target="_blank" class="p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors" title="Cetak Stiker Nomor Antrean">
                                <span class="material-symbols-outlined text-[18px]">print</span>
                            </a>
                            @endif
                        </div>

                    </div>
                @empty
                    <div class="bg-white p-8 rounded-2xl border border-slate-100 text-center text-slate-400">
                        <span class="material-symbols-outlined text-4xl block mb-2 text-slate-300">inbox</span>
                        <p class="text-sm font-semibold">Tidak ada antrean dalam kategori ini hari ini.</p>
                    </div>
                @endforelse
            </div>

        </div>

    </div>

</div>
@endsection
