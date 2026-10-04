@extends('clinic.layouts.app')

@section('title', 'Executive Dashboard — Manajemen & Kepala Klinik')

@section('content')
<div class="flex flex-col gap-6 pb-12">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold font-heading text-slate-900">Executive Dashboard & Pelaporan</h1>
            <p class="text-sm text-slate-500 mt-1">Ringkasan berdasarkan catatan antrean dan rekam medis yang tersimpan di klinik.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('export.report') }}" class="flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-primary hover:bg-primary-hover text-white text-xs font-bold font-heading shadow-md transition-all">
                <span class="material-symbols-outlined text-[18px]">download</span>
                <span>Download Rekap Laporan Bulanan (CSV)</span>
            </a>
        </div>
    </div>

    <!-- STATS SUMMARY CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
        <div class="bg-white p-5 rounded-[24px] border border-slate-100 shadow-card">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block font-heading">Total Kunjungan Bulan Ini</span>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-3xl font-black font-heading text-slate-900">{{ number_format($metrics['monthVisits']) }}</span>
                <span class="text-xs font-bold text-slate-600 font-heading">Kunjungan</span>
            </div>
            <span class="text-xs text-slate-500 mt-1 block">Bulan berjalan sampai hari ini</span>
        </div>

        <div class="bg-white p-5 rounded-[24px] border border-slate-100 shadow-card">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block font-heading">Waktu Tunggu Rata-Rata</span>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-3xl font-black font-heading text-primary">{{ $metrics['averageWaitMinutes'] ?? '—' }}</span>
                <span class="text-xs font-bold text-slate-600 font-heading">Menit</span>
            </div>
            <span class="text-xs text-slate-500 mt-1 block">Waktu dari pendaftaran hingga dipanggil</span>
        </div>

        <div class="bg-white p-5 rounded-[24px] border border-slate-100 shadow-card">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block font-heading">Indeks Kepuasan Pasien</span>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-3xl font-black font-heading text-emerald-600">{{ $metrics['recordCompletionPercent'] === null ? '—' : $metrics['recordCompletionPercent'].'%' }}</span>
                <span class="text-xs font-bold text-slate-600 font-heading">Finalisasi RME</span>
            </div>
            <span class="text-xs text-slate-500 mt-1 block">Kepuasan belum dikumpulkan oleh sistem</span>
        </div>

        <div class="bg-white p-5 rounded-[24px] border border-slate-100 shadow-card">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block font-heading">Integrasi eksternal</span>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-3xl font-black font-heading text-teal-700">{{ $metrics['integrationsConfigured'] }}</span>
                <span class="text-xs font-bold text-slate-600 font-heading">Dikonfigurasi</span>
            </div>
            <span class="text-xs text-slate-500 mt-1 block">Tidak berarti koneksi aktif atau tersertifikasi</span>
        </div>
    </div>

    <!-- MAIN CHARTS ROW: 10 Besar Penyakit & Pemakaian Obat -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- 10 BESAR PENYAKIT (ICD-10) (7 cols) -->
        <div class="lg:col-span-7 bg-white p-6 rounded-[28px] border border-slate-100 shadow-card flex flex-col gap-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="font-heading font-extrabold text-base text-slate-900">10 diagnosis terbanyak (ICD-10)</h3>
                    <p class="text-xs text-slate-500">Distribusi diagnosis final yang tercatat pada bulan berjalan</p>
                </div>
                <span class="text-xs font-bold text-primary font-mono">Bulan Berjalan</span>
            </div>

            <div class="flex flex-col gap-3">
                @php $maxVal = $topDiseases->max('total') ?: 100; @endphp
                @foreach ($topDiseases as $idx => $dis)
                    @php $pct = round(($dis->total / $maxVal) * 100); @endphp
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center justify-between text-xs font-medium">
                            <div class="flex items-center gap-2">
                                <span class="w-5 font-heading font-bold text-slate-400 text-center">{{ $idx + 1 }}.</span>
                                <span class="font-mono font-bold text-primary">{{ $dis->icd10_primary_code }}</span>
                                <span class="text-slate-800 font-semibold truncate max-w-xs md:max-w-md">{{ $dis->icd10_primary_name }}</span>
                            </div>
                            <span class="font-heading font-extrabold text-slate-900">{{ $dis->total }} Kasus</span>
                        </div>
                        <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-primary rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- LAPORAN PEMAKAIAN OBAT & INVENTORI (5 cols) -->
        <div class="lg:col-span-5 bg-white p-6 rounded-[28px] border border-slate-100 shadow-card flex flex-col gap-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="font-heading font-extrabold text-base text-slate-900">Pemakaian Obat Terbanyak</h3>
                    <p class="text-xs text-slate-500">Monitoring pengeluaran obat instalasi farmasi</p>
                </div>
                <span class="material-symbols-outlined text-slate-400">medication</span>
            </div>

            <div class="flex flex-col gap-3">
                @foreach ($topMedicines as $med)
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between gap-3">
                        <div class="flex flex-col min-w-0">
                            <span class="font-heading font-bold text-xs text-slate-900 truncate">{{ $med->nama_obat }}</span>
                            <span class="text-[10px] text-slate-500">{{ $med->kategori }} • {{ $med->bentuk_sediaan }}</span>
                        </div>
                        <div class="flex flex-col items-end flex-shrink-0">
                            <span class="font-heading font-extrabold text-xs text-slate-800">{{ $med->jumlah_diserahkan }} {{ $med->satuan }} diserahkan</span>
                            @if ($med->isLowStock())
                                <span class="text-[10px] text-red-600 font-bold">Stok Menipis</span>
                            @else
                                <span class="text-[10px] text-emerald-600 font-bold">Stok Cukup</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

</div>
@endsection
