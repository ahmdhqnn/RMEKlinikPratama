@extends('clinic.layouts.app')

@section('title', 'Direktori Rekam Medis Pasien')

@section('content')
<div class="flex flex-col gap-6 pb-12">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold font-heading text-slate-900">Rekam Medis Pasien</h1>
            <p class="text-sm text-slate-500 mt-1">Direktori pasien klinik sesuai kewenangan akun Anda.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-2xl bg-white border border-slate-200 text-slate-700 text-xs font-bold font-heading hover:bg-slate-50 shadow-xs">
                + Registrasi Pasien di Front Office
            </a>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white p-4 rounded-[24px] border border-slate-100 shadow-card flex flex-col md:flex-row items-center justify-between gap-4">
        <form action="{{ route('pasien.index') }}" method="GET" class="w-full md:max-w-md">
            <div class="relative flex items-center">
                <span class="material-symbols-outlined absolute left-3.5 text-slate-400 text-[18px]">search</span>
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama pasien, No. RM, atau NIP..." class="w-full bg-slate-50 focus:bg-white text-slate-900 text-xs pl-10 pr-4 py-2.5 rounded-full border border-slate-200 outline-none focus:ring-2 focus:ring-primary/20"/>
            </div>
        </form>

        <span class="text-xs font-semibold text-slate-500">
            Menampilkan {{ $patients->count() }} dari total {{ $patients->total() }} pasien terdaftar
        </span>
    </div>

    <!-- Patient Cards Grid / Table -->
    <div class="bg-white rounded-[28px] border border-slate-100 shadow-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 border-b border-slate-100 text-slate-500 font-heading font-bold uppercase text-[11px] tracking-wider">
                    <tr>
                        <th class="py-4 px-6">Pasien / No. RM</th>
                        <th class="py-4 px-6">Identitas Kepegawaian</th>
                        <th class="py-4 px-6">Unit Kerja / Instansi</th>
                        @if (!$currentUser->hasRole('pendaftaran'))
                            <th class="py-4 px-6">Riwayat Alergi</th>
                            <th class="py-4 px-6">Kunjungan</th>
                        @endif
                        <th class="py-4 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse ($patients as $p)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl overflow-hidden bg-slate-100 flex-shrink-0">
                                        <img src="{{ $p->avatar_url ?? '/assets/images/166de6af4e856812b08f19dc83cc9152.webp' }}" class="w-full h-full object-cover" alt="{{ $p->nama_lengkap }}"/>
                                    </div>
                                    <div>
                                        @if ($currentUser->hasRole('dokter', 'dokter_gigi'))
                                            <a href="{{ route('pasien.show', $p->id) }}" class="font-heading font-bold text-sm text-slate-900 hover:text-primary transition-colors block">{{ $p->nama_lengkap }}</a>
                                        @else
                                            <span class="font-heading font-bold text-sm text-slate-900 block">{{ $p->nama_lengkap }}</span>
                                        @endif
                                        <span class="text-[11px] font-mono text-primary font-semibold">No. RM: {{ $p->no_rm }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex flex-col">
                                    <span class="font-mono text-slate-800 font-bold">{{ $p->nip ? 'NIP: ' . $p->nip : 'Masyarakat Umum' }}</span>
                                    <span class="text-[11px] text-slate-400">{{ $p->usia !== null ? $p->usia.' Thn' : 'Usia belum dicatat' }} • {{ $p->jenis_kelamin === 'L' ? 'Laki-laki' : ($p->jenis_kelamin === 'P' ? 'Perempuan' : 'Belum diketahui') }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                @if ($p->unit_kerja)
                                    <span class="text-slate-800 font-medium block">{{ $p->unit_kerja }}</span>
                                @endif
                                <span class="text-[10px] font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-full inline-block mt-0.5">
                                    {{ $p->jenis_pasien ?? 'Umum' }}
                                </span>
                                @if ($p->identity_status === 'unidentified')
                                    <span class="text-[10px] font-bold text-red-800 bg-red-50 px-2 py-0.5 rounded-full inline-block mt-1">Identitas belum diketahui</span>
                                @elseif ($p->identity_status === 'provisional')
                                    <span class="text-[10px] font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-full inline-block mt-1">Identitas sementara</span>
                                @endif
                            </td>
                            @if (!$currentUser->hasRole('pendaftaran'))
                                <td class="py-4 px-6">
                                    @if ($p->riwayat_alergi && !str_contains(strtolower($p->riwayat_alergi), 'tidak ada'))
                                        <span class="px-2.5 py-1 rounded-full bg-red-50 text-red-800 border border-red-200 text-[10px] font-bold inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[13px]">warning</span>
                                            {{ Str::limit($p->riwayat_alergi, 25) }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-xs">Alergi (-)</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 font-heading">
                                    <span class="font-bold text-slate-900">{{ $p->queues_count }}x Kunjungan</span>
                                    @if ($currentUser->hasRole('dokter', 'dokter_gigi'))
                                        <span class="text-[10px] text-slate-400 block">{{ $p->attachments_count }} Berkas Luar</span>
                                    @endif
                                </td>
                            @endif
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($currentUser->hasRole('pendaftaran'))
                                    <a href="{{ route('pasien.identity.edit', $p->id) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-[11px] font-bold text-slate-700 hover:bg-slate-100" title="Verifikasi atau koreksi identitas pasien">
                                        Identitas
                                    </a>
                                    <a href="{{ route('pasien.card', $p->id) }}" target="_blank" class="p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 transition-colors" title="Cetak Kartu Berobat">
                                        <span class="material-symbols-outlined text-[18px]">badge</span>
                                    </a>
                                    @endif
                                    @if ($currentUser->hasRole('dokter', 'dokter_gigi'))
                                    <a href="{{ route('pasien.show', $p->id) }}" class="px-3.5 py-1.5 rounded-xl bg-primary text-white font-bold text-xs hover:bg-primary-hover shadow-xs flex items-center gap-1">
                                        <span>Rekam Medis</span>
                                        <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $currentUser->hasRole('pendaftaran') ? 4 : 6 }}" class="py-12 text-center text-slate-400">
                                Tidak ada data pasien yang cocok dengan pencarian Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $patients->links() }}
        </div>
    </div>

</div>
@endsection
