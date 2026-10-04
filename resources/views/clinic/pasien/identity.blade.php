@extends('clinic.layouts.app')

@section('title', 'Verifikasi Identitas Pasien')

@section('content')
<div class="mx-auto flex max-w-4xl flex-col gap-6 pb-12">
    <div>
        <a href="{{ route('pasien.index') }}" class="text-xs font-bold text-primary hover:underline">← Kembali ke direktori pasien</a>
        <h1 class="mt-3 font-heading text-2xl font-bold text-slate-900">Verifikasi dan Koreksi Identitas</h1>
        <p class="mt-1 text-sm text-slate-500">Layar administratif pendaftaran. Isi klinis dan riwayat pelayanan tidak ditampilkan di sini.</p>
    </div>

    @if (session('status'))
        <div role="status" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-bold">Periksa kembali perubahan identitas.</p>
            <ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="rounded-[24px] border border-slate-100 bg-white p-6 shadow-card">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-5">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Nomor Rekam Medis</p>
                <p class="mt-1 font-mono text-lg font-bold text-primary">{{ $medicalRecordNumber }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-800">{{ $patientName }}</p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $identityStatus === 'verified' ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800' }}">
                {{ $identityStatus === 'verified' ? 'Identitas terverifikasi' : ($identityStatus === 'unidentified' ? 'Belum teridentifikasi' : 'Identitas sementara') }}
            </span>
        </div>

        <form action="{{ route('pasien.identity.update', $patientId) }}" method="POST" class="space-y-5">
            @csrf
            @method('PATCH')

            <div>
                <label for="nama_lengkap" class="mb-1.5 block text-xs font-bold text-slate-700">Nama lengkap <span class="text-red-600">*</span></label>
                <input id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $identityStatus === 'unidentified' ? '' : $patientName) }}" required maxlength="150" autocomplete="name" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20" />
                @error('nama_lengkap')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="tanggal_lahir" class="mb-1.5 block text-xs font-bold text-slate-700">Tanggal lahir</label>
                    <input id="tanggal_lahir" type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $dateOfBirth?->format('Y-m-d')) }}" max="{{ now()->toDateString() }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20" />
                    @error('tanggal_lahir')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="jenis_kelamin" class="mb-1.5 block text-xs font-bold text-slate-700">Jenis kelamin</label>
                    <select id="jenis_kelamin" name="jenis_kelamin" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20">
                        <option value="">Belum diketahui</option>
                        <option value="L" @selected(old('jenis_kelamin', $sex) === 'L')>Laki-laki</option>
                        <option value="P" @selected(old('jenis_kelamin', $sex) === 'P')>Perempuan</option>
                    </select>
                    @error('jenis_kelamin')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="nik" class="mb-1.5 block text-xs font-bold text-slate-700">NIK</label>
                    <input id="nik" name="nik" inputmode="numeric" autocomplete="off" maxlength="24" placeholder="{{ $hasNik ? 'NIK sudah tersimpan • kosongkan untuk mempertahankan' : 'Masukkan 16 angka jika tersedia' }}" value="{{ old('nik') }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20" />
                    <p class="mt-1 text-[11px] text-slate-500">Nilai NIK tersimpan tidak ditampilkan. Kosongkan untuk mempertahankan.</p>
                    @error('nik')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="nip" class="mb-1.5 block text-xs font-bold text-slate-700">NIP / nomor kepegawaian</label>
                    <input id="nip" name="nip" autocomplete="off" maxlength="30" placeholder="{{ $hasNip ? 'Nomor sudah tersimpan • kosongkan untuk mempertahankan' : 'Isi bila pasien memiliki nomor kepegawaian' }}" value="{{ old('nip') }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20" />
                    @error('nip')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <fieldset class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <legend class="px-1 text-xs font-bold text-slate-800">Pencatatan verifikasi</legend>
                <input type="hidden" name="identity_verified" value="0" />
                <label class="flex items-start gap-3 text-sm text-slate-700">
                    <input type="checkbox" name="identity_verified" value="1" @checked((bool) old('identity_verified', false)) class="mt-0.5 rounded border-slate-300 text-primary focus:ring-primary" />
                    <span>Saya telah melihat dokumen identitas yang dipilih dan mencocokkannya dengan data pasien. Ini mencatat pemeriksaan oleh petugas, bukan verifikasi ke Dukcapil.</span>
                </label>
                <label for="identity_verification_method" class="mb-1.5 mt-4 block text-xs font-bold text-slate-700">Dokumen yang dilihat</label>
                <select id="identity_verification_method" name="identity_verification_method" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    <option value="">Pilih jika menandai identitas terverifikasi</option>
                    @foreach ($verificationMethods as $value => $label)
                        <option value="{{ $value }}" @selected(old('identity_verification_method', $verificationMethod) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('identity_verification_method')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
            </fieldset>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-5">
                <a href="{{ route('pasien.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Batal</a>
                <button type="submit" class="rounded-xl bg-primary px-5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-primary-hover">Simpan koreksi identitas</button>
            </div>
        </form>
    </section>
</div>
@endsection
