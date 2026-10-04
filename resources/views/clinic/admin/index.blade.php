@extends('clinic.layouts.app')

@section('title', 'Pengaturan Sistem & IT Administrator')

@section('content')
<div class="flex flex-col gap-6 pb-12" x-data="{ currentTab: '{{ $tab }}' }">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold font-heading text-slate-900">Pengaturan Sistem & IT Console</h1>
            <p class="text-sm text-slate-500 mt-1">Kelola akun dan konfigurasi teknis. Catatan audit yang ditampilkan di sini dibatasi pada aktivitas sistem tanpa tautan pasien.</p>
        </div>
    </div>

    <!-- Admin Tabs -->
    <div class="flex items-center gap-2 p-1.5 bg-white rounded-2xl border border-slate-100 shadow-card font-heading text-xs font-bold overflow-x-auto">
        <button type="button" @click="currentTab = 'users'" :class="currentTab === 'users' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50'" class="px-4 py-2 rounded-xl transition-all whitespace-nowrap flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[18px]">manage_accounts</span>
            <span>Akun & Peran ({{ $users->total() }})</span>
        </button>
        <button type="button" @click="currentTab = 'medicines'" :class="currentTab === 'medicines' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50'" class="px-4 py-2 rounded-xl transition-all whitespace-nowrap flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[18px]">medication</span>
            <span>Master Data Obat</span>
        </button>
        <button type="button" @click="currentTab = 'icd'" :class="currentTab === 'icd' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50'" class="px-4 py-2 rounded-xl transition-all whitespace-nowrap flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[18px]">menu_book</span>
            <span>Master ICD-10</span>
        </button>
        <button type="button" @click="currentTab = 'integrations'" :class="currentTab === 'integrations' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50'" class="px-4 py-2 rounded-xl transition-all whitespace-nowrap flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[18px]">hub</span>
            <span>Integrasi SATUSEHAT & P-Care</span>
        </button>
        <button type="button" @click="currentTab = 'audit'" :class="currentTab === 'audit' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50'" class="px-4 py-2 rounded-xl transition-all whitespace-nowrap flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[18px]">security</span>
            <span>Log Administrasi & Sistem</span>
        </button>
    </div>

    <!-- TAB 1: USERS & ROLES -->
    <div x-show="currentTab === 'users'" class="flex flex-col gap-6">
        <form action="{{ route('admin.user.store') }}" method="POST" class="bg-white p-6 rounded-[28px] border border-slate-100 shadow-card flex flex-col gap-4">
            @csrf
            <h3 class="font-heading font-extrabold text-base text-slate-900">Buat akun pengguna</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 text-xs">
                <label class="font-semibold">Nama<input name="name" required maxlength="255" value="{{ old('name') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5"></label>
                <label class="font-semibold">Email<input name="email" type="email" required value="{{ old('email') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5"></label>
                <label class="font-semibold">Peran<select name="role" required class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5"><option value="">Pilih peran</option>@foreach (\App\Enums\UserRole::cases() as $role)<option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>@endforeach</select></label>
                <label class="font-semibold">Spesialisasi<input name="specialty" maxlength="255" value="{{ old('specialty') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5"></label>
                <label class="font-semibold">NIP<input name="nip" maxlength="30" value="{{ old('nip') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5"></label>
                <label class="font-semibold">SIP<input name="sip" maxlength="100" value="{{ old('sip') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5"></label>
                <label class="font-semibold">Telepon<input name="phone" maxlength="25" value="{{ old('phone') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5"></label>
                <label class="font-semibold">Kata sandi awal<input name="password" type="password" minlength="12" autocomplete="new-password" required class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5"><span class="mt-1 block text-[10px] text-slate-500">Minimal 12 karakter, huruf besar/kecil dan angka.</span></label>
            </div>
            <div class="flex justify-end"><button class="rounded-xl bg-primary px-5 py-2.5 text-xs font-bold text-white">Buat akun</button></div>
        </form>

        <div class="bg-white p-6 rounded-[28px] border border-slate-100 shadow-card flex flex-col gap-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-heading font-extrabold text-base text-slate-900">Daftar Pengguna Sistem & Hak Akses</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-body">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                        <tr>
                            <th class="py-3 px-4">Nama Pengguna</th>
                            <th class="py-3 px-4">Role / Peran</th>
                            <th class="py-3 px-4">NIP / SIP</th>
                            <th class="py-3 px-4">Email Login</th>
                            <th class="py-3 px-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @foreach ($users as $u)
                            <tr class="hover:bg-slate-50">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ $u->name }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $u->specialty }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-800 text-[10px] font-bold uppercase font-heading">
                                        {{ \App\Enums\UserRole::tryFrom($u->role)?->label() ?? $u->role }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600">
                                    <div>{{ $u->nip ? 'NIP: ' . $u->nip : '-' }}</div>
                                    <div class="text-slate-400">{{ $u->sip ? 'SIP: ' . $u->sip : '' }}</div>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-600">{{ $u->email }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded-full {{ $u->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }} text-[10px] font-bold">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </td>
                            </tr>
                            <tr class="border-b border-slate-100">
                                <td colspan="5" class="px-4 pb-4">
                                    <details>
                                        <summary class="cursor-pointer text-[11px] font-bold text-primary">Ubah akun {{ $u->name }}</summary>
                                        <form method="POST" action="{{ route('admin.user.update', $u) }}" class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-2 text-xs">
                                            @csrf
                                            @method('PATCH')
                                            <input name="name" value="{{ $u->name }}" required class="rounded-lg border border-slate-200 p-2" aria-label="Nama">
                                            <input name="email" type="email" value="{{ $u->email }}" required class="rounded-lg border border-slate-200 p-2" aria-label="Email">
                                            <select name="role" required class="rounded-lg border border-slate-200 p-2" aria-label="Peran">@foreach (\App\Enums\UserRole::cases() as $role)<option value="{{ $role->value }}" @selected($u->role === $role->value)>{{ $role->label() }}</option>@endforeach</select>
                                            <input name="specialty" value="{{ $u->specialty }}" class="rounded-lg border border-slate-200 p-2" aria-label="Spesialisasi">
                                            <input name="nip" value="{{ $u->nip }}" class="rounded-lg border border-slate-200 p-2" aria-label="NIP">
                                            <input name="sip" value="{{ $u->sip }}" class="rounded-lg border border-slate-200 p-2" aria-label="SIP">
                                            <input name="phone" value="{{ $u->phone }}" class="rounded-lg border border-slate-200 p-2" aria-label="Telepon">
                                            <input type="hidden" name="is_active" value="0">
                                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-2"><input type="checkbox" name="is_active" value="1" @checked($u->is_active)> Akun aktif</label>
                                            <div class="md:col-span-4 flex justify-end"><button class="rounded-lg bg-primary px-4 py-2 font-bold text-white">Simpan perubahan</button></div>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $users->links() }}
        </div>
    </div>

    <!-- TAB 2: MEDICINES -->
    <div x-show="currentTab === 'medicines'" class="flex flex-col gap-6">
        <form action="{{ route('admin.medicine.store') }}" method="POST" class="bg-white p-6 rounded-[28px] border border-slate-100 shadow-card flex flex-col gap-4">
            @csrf
            <h3 class="font-heading font-extrabold text-base text-slate-900">Tambah obat dan stok awal</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-4 gap-3 text-xs">
                <label class="font-semibold">Kode<input name="kode_obat" required maxlength="50" class="mt-1 w-full rounded-xl border border-slate-200 p-2.5"></label>
                <label class="font-semibold">Nama obat<input name="nama_obat" required maxlength="255" class="mt-1 w-full rounded-xl border border-slate-200 p-2.5"></label>
                <label class="font-semibold">Kategori<input name="kategori" required maxlength="100" class="mt-1 w-full rounded-xl border border-slate-200 p-2.5"></label>
                <label class="font-semibold">Bentuk sediaan<input name="bentuk_sediaan" required maxlength="100" class="mt-1 w-full rounded-xl border border-slate-200 p-2.5"></label>
                <label class="font-semibold">Satuan<input name="satuan" required maxlength="20" class="mt-1 w-full rounded-xl border border-slate-200 p-2.5"></label>
                <label class="font-semibold">Stok awal<input name="stok" type="number" min="0" max="2147483647" required value="0" class="mt-1 w-full rounded-xl border border-slate-200 p-2.5"></label>
                <label class="font-semibold">Batas stok minimum<input name="stok_minimum" type="number" min="0" max="2147483647" required value="0" class="mt-1 w-full rounded-xl border border-slate-200 p-2.5"></label>
                <label class="font-semibold">Nomor batch<input name="nomor_batch" maxlength="100" class="mt-1 w-full rounded-xl border border-slate-200 p-2.5"></label>
                <label class="font-semibold">Kedaluwarsa<input name="expired_date" type="date" required class="mt-1 w-full rounded-xl border border-slate-200 p-2.5"></label>
                <label class="font-semibold">Harga satuan<input name="harga_satuan" type="number" min="0" step="0.01" required value="0" class="mt-1 w-full rounded-xl border border-slate-200 p-2.5"></label>
                <label class="font-semibold">Aturan pakai default<input name="aturan_pakai_default" maxlength="255" class="mt-1 w-full rounded-xl border border-slate-200 p-2.5"></label>
                <label class="font-semibold">Kontraindikasi/alergi<input name="kontraindikasi_alergi" maxlength="500" class="mt-1 w-full rounded-xl border border-slate-200 p-2.5"></label>
            </div>
            <div class="flex justify-end"><button class="rounded-xl bg-primary px-5 py-2.5 text-xs font-bold text-white">Tambah obat</button></div>
        </form>

        <div class="bg-white p-6 rounded-[28px] border border-slate-100 shadow-card flex flex-col gap-4">
            <h3 class="font-heading font-extrabold text-base text-slate-900 border-b border-slate-100 pb-3">Master Obat & Inventori Farmasi</h3>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-body">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                        <tr>
                            <th class="py-3 px-4">Kode & Nama Obat</th>
                            <th class="py-3 px-4">Kategori & Bentuk</th>
                            <th class="py-3 px-4">Stok Saat Ini</th>
                            <th class="py-3 px-4">No. Batch & Expired</th>
                            <th class="py-3 px-4">Peringatan Alergi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @foreach ($medicines as $m)
                            <tr class="hover:bg-slate-50">
                                <td class="py-3 px-4">
                                    <span class="font-mono text-primary font-bold text-[11px] block">{{ $m->kode_obat }}</span>
                                    <span class="font-bold text-slate-900">{{ $m->nama_obat }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="text-slate-700 block">{{ $m->kategori }}</span>
                                    <span class="text-[11px] text-slate-400">{{ $m->bentuk_sediaan }} ({{ $m->satuan }})</span>
                                </td>
                                <td class="py-3 px-4 font-mono font-bold text-sm text-slate-900">
                                    {{ $m->stok }} {{ $m->satuan }}
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-500">
                                    <div>Batch: {{ $m->nomor_batch ?? '—' }}</div>
                                    <div>Exp: {{ $m->expired_date ? $m->expired_date->format('d/m/Y') : '—' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    @if ($m->kontraindikasi_alergi)
                                        <span class="px-2 py-0.5 rounded bg-red-100 text-red-800 text-[10px] font-bold">
                                            {{ $m->kontraindikasi_alergi }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-xs">Standard</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $medicines->links() }}
        </div>
    </div>

    <!-- TAB 3: MASTER ICD-10 -->
    <div x-show="currentTab === 'icd'" class="flex flex-col gap-6">
        <form action="{{ route('admin.icd10.store') }}" method="POST" class="bg-white p-6 rounded-[28px] border border-slate-100 shadow-card flex flex-col gap-4">
            @csrf
            <div><h3 class="font-heading font-extrabold text-base text-slate-900">Tambah master ICD-10 klinik</h3><p class="mt-1 text-xs text-slate-500">Masukkan kode berdasarkan sumber resmi yang telah diverifikasi.</p></div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
                <label class="font-semibold">Kode<input name="code" required maxlength="20" class="mt-1 w-full rounded-xl border border-slate-200 p-2.5"></label>
                <label class="font-semibold">Diagnosis (Indonesia)<input name="name_id" required maxlength="255" class="mt-1 w-full rounded-xl border border-slate-200 p-2.5"></label>
                <label class="font-semibold">Diagnosis (English)<input name="name_en" maxlength="255" class="mt-1 w-full rounded-xl border border-slate-200 p-2.5"></label>
                <label class="font-semibold">Kategori<input name="category" maxlength="100" class="mt-1 w-full rounded-xl border border-slate-200 p-2.5"></label>
            </div>
            <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="is_common" value="1"> Tampilkan sebagai diagnosis umum</label>
            <div class="flex justify-end"><button class="rounded-xl bg-primary px-5 py-2.5 text-xs font-bold text-white">Tambah kode</button></div>
        </form>

        <div class="bg-white p-6 rounded-[28px] border border-slate-100 shadow-card flex flex-col gap-4">
            <h3 class="font-heading font-extrabold text-base text-slate-900 border-b border-slate-100 pb-3">Master ICD-10 Klinik</h3>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-body">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                        <tr>
                            <th class="py-3 px-4">Kode ICD-10</th>
                            <th class="py-3 px-4">Nama Diagnosa (Indonesia)</th>
                            <th class="py-3 px-4">English Description</th>
                            <th class="py-3 px-4">Kategori</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @foreach ($icdCodes as $c)
                            <tr class="hover:bg-slate-50">
                                <td class="py-3 px-4 font-mono font-bold text-primary text-xs">{{ $c->code }}</td>
                                <td class="py-3 px-4 font-bold text-slate-900">{{ $c->name_id }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ $c->name_en }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px]">{{ $c->category }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $icdCodes->links() }}
        </div>
    </div>

    <!-- TAB 4: EXTERNAL INTEGRATIONS -->
    <div x-show="currentTab === 'integrations'" class="flex flex-col gap-6">
        <form action="{{ route('admin.integration.store') }}" method="POST" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card">
            @csrf
            <h3 class="font-heading text-sm font-extrabold">Siapkan konektor untuk tahap integrasi</h3>
            <p class="mt-1 text-xs text-slate-600">Konfigurasi disimpan terenkripsi dan nonaktif. Aplikasi belum menghubungi sistem eksternal.</p>
            <div class="mt-4 grid grid-cols-1 gap-3 text-xs md:grid-cols-3">
                <label class="font-semibold">Kode sistem<input name="system_code" maxlength="50" required class="mt-1 w-full rounded-xl border-slate-300 p-2.5" placeholder="SATUSEHAT"></label>
                <label class="font-semibold">Nama sistem<input name="system_name" maxlength="255" required class="mt-1 w-full rounded-xl border-slate-300 p-2.5"></label>
                <label class="font-semibold">Lingkungan<select name="environment" required class="mt-1 w-full rounded-xl border-slate-300 p-2.5"><option value="sandbox">Sandbox</option><option value="production">Production</option></select></label>
                <label class="font-semibold">Organization ID<input name="organization_id" class="mt-1 w-full rounded-xl border-slate-300 p-2.5"></label>
                <label class="font-semibold">Client ID<input name="client_id" class="mt-1 w-full rounded-xl border-slate-300 p-2.5"></label>
                <label class="font-semibold">Client Secret<input name="client_secret" type="password" autocomplete="new-password" class="mt-1 w-full rounded-xl border-slate-300 p-2.5"></label>
                <label class="font-semibold">Base URL<input name="base_url" type="url" class="mt-1 w-full rounded-xl border-slate-300 p-2.5"></label>
                <label class="font-semibold">Auth URL<input name="auth_url" type="url" class="mt-1 w-full rounded-xl border-slate-300 p-2.5"></label>
            </div>
            <div class="mt-4 flex justify-end"><button class="rounded-xl bg-primary px-4 py-2 text-xs font-bold text-white">Simpan sebagai nonaktif</button></div>
        </form>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach ($integrations as $integ)
                <div class="bg-white p-6 rounded-[28px] border border-slate-100 shadow-card flex flex-col justify-between gap-4">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-[24px]">hub</span>
                                <h3 class="font-heading font-extrabold text-sm text-slate-900">{{ $integ->system_name }}</h3>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-800 text-[10px] font-bold border border-amber-200">
                                Belum terhubung
                            </span>
                        </div>

                        <form action="{{ route('admin.integration.update', $integ->id) }}" method="POST" class="flex flex-col gap-3 mt-4 text-xs font-body">
                            @csrf
                            <div>
                                <label class="font-bold text-slate-700 block mb-1">Organization / Faskes ID</label>
                                <input type="text" name="organization_id" value="{{ $integ->organization_id }}" class="w-full bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs font-mono"/>
                            </div>
                            <div>
                                <label class="font-bold text-slate-700 block mb-1">Client ID</label>
                                <input type="text" name="client_id" value="{{ $integ->client_id }}" class="w-full bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs font-mono"/>
                            </div>
                            <div>
                                <label class="font-bold text-slate-700 block mb-1">Client Secret</label>
                                <input type="password" name="client_secret" autocomplete="new-password" placeholder="Kosongkan jika tidak mengubah secret" class="w-full bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs font-mono"/>
                            </div>
                            <div>
                                <label class="font-bold text-slate-700 block mb-1">Base URL</label>
                                <input type="url" name="base_url" value="{{ $integ->base_url }}" class="w-full bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs font-mono"/>
                            </div>
                            <div>
                                <label class="font-bold text-slate-700 block mb-1">Auth URL</label>
                                <input type="url" name="auth_url" value="{{ $integ->auth_url }}" class="w-full bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs font-mono"/>
                            </div>
                            <div>
                                <label class="font-bold text-slate-700 block mb-1">Environment</label>
                                <select name="environment" class="w-full bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs">
                                    <option value="sandbox" {{ $integ->environment === 'sandbox' ? 'selected' : '' }}>Sandbox (Pengujian)</option>
                                    <option value="production" {{ $integ->environment === 'production' ? 'selected' : '' }}>Production</option>
                                </select>
                            </div>
                            <div class="flex items-center justify-between pt-2">
                                <span class="text-[11px] text-slate-400">Sinkronisasi belum tersedia</span>
                                <button type="submit" class="px-4 py-2 rounded-xl bg-primary text-white font-bold text-xs shadow-xs hover:bg-primary-hover">
                                    Simpan Konfigurasi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- TAB 5: IMMUTABLE AUDIT TRAIL -->
    <div x-show="currentTab === 'audit'" class="flex flex-col gap-6">
        <div class="bg-white p-6 rounded-[28px] border border-slate-100 shadow-card flex flex-col gap-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="font-heading font-extrabold text-base text-slate-900">Aktivitas Administrasi dan Sistem</h3>
                    <p class="text-xs text-slate-500">Hanya aktivitas tanpa tautan pasien yang ditampilkan pada konsol teknis. Audit akses dan aktivitas klinis ditinjau melalui kewenangan terpisah.</p>
                </div>
                <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 font-mono text-[10px] font-bold">
                    Total {{ $auditLogs->total() }} Log
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-body">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                        <tr>
                            <th class="py-3 px-4">Waktu (WIB)</th>
                            <th class="py-3 px-4">Pengguna</th>
                            <th class="py-3 px-4">Aksi</th>
                            <th class="py-3 px-4">Deskripsi Aktivitas</th>
                            <th class="py-3 px-4">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @foreach ($auditLogs as $log)
                            <tr class="hover:bg-slate-50">
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ $log->user_name ?? 'System' }}</div>
                                    <div class="text-[10px] text-slate-400 uppercase">{{ $log->user_role }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 font-mono text-[10px] font-bold">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-700 max-w-md">
                                    {{ $log->description }}
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-400">
                                    {{ $log->ip_address }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $auditLogs->links() }}
        </div>
    </div>

</div>
@endsection
