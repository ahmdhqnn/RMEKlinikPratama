@extends('clinic.layouts.app')

@section('title', 'Apotek & Dispensing Farmasi')

@section('content')
<div class="flex flex-col gap-6 pb-12">
    
    <!-- LIVE PRESCRIPTION BROADCAST BANNER (Matching Stitch Apotek) -->
    @if ($latestPrescription)
        <div class="relative overflow-hidden rounded-[24px] bg-white border border-slate-100 shadow-card p-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="absolute -left-1 top-0 bottom-0 w-2 bg-primary"></div>
            <div class="flex items-center gap-3.5">
                <div class="relative flex items-center justify-center w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-800 flex-shrink-0">
                    <span class="material-symbols-outlined text-[20px]">notification_important</span>
                    <span class="absolute top-0 right-0 w-2.5 h-2.5 bg-red-500 rounded-full animate-ping"></span>
                    <span class="absolute top-0 right-0 w-2.5 h-2.5 bg-red-500 rounded-full"></span>
                </div>
                <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold text-primary uppercase tracking-wider font-heading">Resep Masuk Baru</span>
                        <span class="text-xs text-slate-400">• {{ $latestPrescription->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-xs font-semibold text-slate-800 mt-0.5">
                        {{ $latestPrescription->doctor->name ?? 'Dokter belum dicatat' }} 
                        <span class="text-slate-400 font-normal">({{ $latestPrescription->queue?->poli === 'gigi' ? 'Poli Gigi' : 'Poli Umum' }})</span> → 
                        Pasien: <span class="font-bold text-primary">{{ $latestPrescription->patient->nama_lengkap }}</span> 
                        (No. RM: {{ $latestPrescription->patient->no_rm }})
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 self-end md:self-auto">
                    <span class="px-3 py-1 rounded-full bg-amber-50 text-[11px] font-semibold text-amber-800 flex items-center gap-1.5">
                    Integrasi eksternal belum dikonfigurasi
                </span>
            </div>
        </div>
    @endif

    <!-- OPERATIONAL HEADER & TICKER BAR -->
    <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4 bg-white p-4 rounded-[24px] border border-slate-100 shadow-card">
        <div class="flex flex-wrap items-center gap-2 font-heading">
            <div class="flex items-center gap-2 px-3.5 py-2 bg-slate-50 border border-slate-100 rounded-xl text-xs">
                <span class="text-slate-400">Total Resep:</span>
                <span class="font-black text-slate-900">{{ $totalToday }}</span>
            </div>
            <div class="flex items-center gap-2 px-3.5 py-2 bg-amber-50/70 border border-amber-100 rounded-xl text-xs">
                <span class="text-amber-800 font-bold">Resep Masuk:</span>
                <span class="font-black text-amber-900">{{ $pendingCount }}</span>
            </div>
            <div class="flex items-center gap-2 px-3.5 py-2 bg-blue-50/70 border border-blue-100 rounded-xl text-xs">
                <span class="text-blue-800 font-bold">Sedang Disiapkan:</span>
                <span class="font-black text-blue-900">{{ $processingCount }}</span>
            </div>
            <div class="flex items-center gap-2 px-3.5 py-2 bg-emerald-50/70 border border-emerald-100 rounded-xl text-xs">
                <span class="text-emerald-800 font-bold">Siap Diambil:</span>
                <span class="font-black text-emerald-900">{{ $readyCount }}</span>
            </div>
            <div class="flex items-center gap-2 px-3.5 py-2 bg-slate-50 border border-slate-100 rounded-xl text-xs">
                <span class="text-slate-500 font-medium">Selesai:</span>
                <span class="font-bold text-slate-600">{{ $dispensedCount }}</span>
            </div>
        </div>

        <!-- Filter Search in Pharmacy -->
        <form action="{{ route('farmasi.index') }}" method="GET" class="flex items-center gap-2 min-w-[280px]">
            <div class="relative w-full">
                <span class="material-symbols-outlined absolute left-3 text-slate-400 text-[18px]">filter_alt</span>
                <input type="text" name="q" value="{{ $search }}" placeholder="Filter resep atau nama pasien..." class="w-full bg-slate-50 focus:bg-white text-xs text-slate-900 pl-9 pr-3 py-2 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-primary/20"/>
            </div>
        </form>
    </div>

    @if ($lowStockMedicines->isNotEmpty())
        <section class="rounded-[24px] border border-amber-200 bg-amber-50 p-5">
            <h2 class="font-heading text-sm font-extrabold text-amber-950">Stok menipis — penerimaan per batch</h2>
            <p class="mt-1 text-xs text-amber-900">Tambahkan jumlah pada batch dan tanggal kedaluwarsa yang sama. Batch baru harus dibuat sebagai master obat terpisah agar stok tidak tercampur.</p>
            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                @foreach ($lowStockMedicines as $medicine)
                    <form action="{{ route('farmasi.stock.receive', $medicine) }}" method="POST" class="rounded-2xl border border-amber-200 bg-white p-4">
                        @csrf
                        <div class="mb-3 flex items-center justify-between gap-3 text-xs">
                            <strong class="text-slate-900">{{ $medicine->nama_obat }}</strong>
                            <span class="font-mono text-slate-500">Stok {{ $medicine->stok }} / minimum {{ $medicine->stok_minimum }}</span>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <label class="text-[11px] font-semibold">Batch<input name="nomor_batch" value="{{ $medicine->nomor_batch }}" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 p-2"></label>
                            <label class="text-[11px] font-semibold">Kedaluwarsa<input name="expired_date" type="date" value="{{ $medicine->expired_date?->format('Y-m-d') }}" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 p-2"></label>
                            <label class="text-[11px] font-semibold">Jumlah masuk<input name="quantity" type="number" min="1" max="100000" required class="mt-1 w-full rounded-lg border-slate-200 p-2"></label>
                            <label class="text-[11px] font-semibold">Referensi penerimaan<input name="reference" maxlength="100" placeholder="Nomor faktur / dokumen" class="mt-1 w-full rounded-lg border-slate-200 p-2"></label>
                            <label class="text-[11px] font-semibold sm:col-span-2">Keterangan<input name="reason" maxlength="1000" required placeholder="Contoh: penerimaan dari pemasok" class="mt-1 w-full rounded-lg border-slate-200 p-2"></label>
                        </div>
                        <div class="mt-3 flex justify-end"><button class="rounded-lg bg-primary px-4 py-2 text-xs font-bold text-white">Catat penerimaan stok</button></div>
                    </form>
                @endforeach
            </div>
        </section>
    @endif

    <!-- TRI-COLUMN CLINICAL KANBAN GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        
        <!-- COLUMN 1: RESEP MASUK (PENDING VERIFIKASI) -->
        <div class="flex flex-col gap-4 bg-slate-100/60 p-4 rounded-[28px] border border-slate-200/60">
            <div class="flex items-center justify-between pb-1">
                <div class="flex items-center gap-2 font-heading font-bold text-xs uppercase tracking-wider text-amber-900">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    <span>Resep Masuk (Pending)</span>
                </div>
                <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold">
                    {{ $pendingCount }} Baru
                </span>
            </div>

            <!-- Pending Cards List -->
            @forelse ($pendingPrescriptions as $rx)
                @php $pat = $rx->patient; @endphp
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-card flex flex-col gap-3 relative overflow-hidden">
                    <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-amber-500"></div>

                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-heading font-black text-sm text-primary">{{ $rx->nomor_resep }}</span>
                            <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-bold uppercase">{{ $rx->queue?->nomor_antrean ?? 'Antrean tidak tersedia' }}</span>
                            </div>
                            <h4 class="font-heading font-bold text-xs text-slate-900 mt-1">{{ $pat->nama_lengkap }} ({{ $pat->usia }} Thn)</h4>
                            <span class="text-[11px] text-slate-400 font-mono">No. RM: {{ $pat->no_rm }}</span>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-800 text-[10px] font-bold">{{ $rx->created_at->format('H:i') }} WIB</span>
                    </div>

                    <!-- Prescribing Doctor Info -->
                    <div class="p-2 rounded-xl bg-slate-50 text-[11px] text-slate-600 flex flex-col gap-0.5">
                        <div class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px] text-primary">stethoscope</span>
                            <span>{{ $rx->doctor->name ?? 'Dokter belum dicatat' }}</span>
                        </div>
                        <div class="text-slate-500 italic truncate">
                            Catatan: "{{ $rx->catatan_dokter_farmasi }}"
                        </div>
                    </div>

                    <!-- Allergy Warning Pill if patient has allergy -->
                    @if ($pat->riwayat_alergi && !str_contains(strtolower($pat->riwayat_alergi), 'tidak ada'))
                        <div class="p-2 rounded-xl bg-red-50 border border-red-100 flex items-center gap-2 text-xs text-red-900">
                            <span class="material-symbols-outlined text-red-600 text-[16px]">warning</span>
                            <span class="font-bold text-[11px]">Alergi: {{ $pat->riwayat_alergi }}</span>
                        </div>
                    @endif

                    <!-- Medicine List Summary -->
                    <div class="flex flex-col gap-1 text-xs">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Daftar Obat:</span>
                        @foreach ($rx->items as $it)
                            <div class="flex items-center justify-between p-1.5 rounded-lg bg-slate-50 text-slate-800">
                                <span class="truncate font-semibold">{{ $it->medicine->nama_obat ?? 'Obat' }}</span>
                                <span class="font-mono font-bold text-primary flex-shrink-0">{{ $it->jumlah }} Unit</span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Action: Verify -->
                    <form action="{{ route('farmasi.verify', $rx->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold font-heading shadow-xs flex items-center justify-center gap-1.5 transition-all">
                            <span class="material-symbols-outlined text-[16px]">verified</span>
                            <span>Verifikasi & Siapkan Obat</span>
                        </button>
                    </form>
                </div>
            @empty
                <div class="p-6 rounded-2xl bg-white border border-slate-100 text-center text-xs text-slate-400">
                    Tidak ada resep baru yang menunggu verifikasi.
                </div>
            @endforelse
        </div>

        <!-- COLUMN 2: SEDANG DISIAPKAN (PROCESSING / DISPENSING) -->
        <div class="flex flex-col gap-4 bg-slate-100/60 p-4 rounded-[28px] border border-slate-200/60">
            <div class="flex items-center justify-between pb-1">
                <div class="flex items-center gap-2 font-heading font-bold text-xs uppercase tracking-wider text-blue-900">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    <span>Sedang Disiapkan</span>
                </div>
                <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 text-[10px] font-bold">
                    {{ $processingCount }} Proses
                </span>
            </div>

            <!-- Processing Cards List -->
            @forelse ($processingPrescriptions as $rx)
                @php $pat = $rx->patient; @endphp
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-card flex flex-col gap-3 relative overflow-hidden">
                    <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-blue-500"></div>

                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-heading font-black text-sm text-blue-900">{{ $rx->nomor_resep }}</span>
                                <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 text-[10px] font-bold">{{ $rx->queue?->nomor_antrean ?? 'Antrean tidak tersedia' }}</span>
                            </div>
                            <h4 class="font-heading font-bold text-xs text-slate-900 mt-1">{{ $pat->nama_lengkap }}</h4>
                            <span class="text-[11px] text-slate-400 font-mono">No. RM: {{ $pat->no_rm }}</span>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-[10px] font-bold">Peracikan</span>
                    </div>

                    <!-- Medicine List Details -->
                    <div class="flex flex-col gap-1.5 text-xs">
                        @foreach ($rx->items as $it)
                            <div class="p-2 rounded-xl bg-slate-50 border border-slate-100 flex flex-col gap-0.5">
                                <div class="flex items-center justify-between font-bold text-slate-800">
                                    <span>{{ $it->medicine->nama_obat ?? 'Obat' }}</span>
                                    <span class="text-primary font-mono">{{ $it->jumlah }} {{ $it->medicine->satuan ?? 'Tab' }}</span>
                                </div>
                                <span class="text-[11px] text-slate-500 font-medium">Aturan: {{ $it->aturan_pakai }}</span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Actions: Print Etiket & Mark Ready -->
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <a href="{{ route('farmasi.etiket', $rx->id) }}" target="_blank" class="py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold text-center flex items-center justify-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">print</span>
                            <span>Cetak Etiket</span>
                        </a>
                        <form action="{{ route('farmasi.ready', $rx->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center justify-center gap-1 shadow-xs">
                                <span class="material-symbols-outlined text-[16px]">check_circle</span>
                                <span>Siap Diambil</span>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-6 rounded-2xl bg-white border border-slate-100 text-center text-xs text-slate-400">
                    Tidak ada resep yang sedang dalam proses penyiapan.
                </div>
            @endforelse
        </div>

        <!-- COLUMN 3: SIAP DISERAHKAN (READY) & SELESAI -->
        <div class="flex flex-col gap-4 bg-slate-100/60 p-4 rounded-[28px] border border-slate-200/60">
            <div class="flex items-center justify-between pb-1">
                <div class="flex items-center gap-2 font-heading font-bold text-xs uppercase tracking-wider text-emerald-900">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <span>Siap Diserahkan ke Pasien</span>
                </div>
                <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">
                    {{ $readyCount }} Siap
                </span>
            </div>

            <!-- Ready Cards List -->
            @forelse ($readyPrescriptions as $rx)
                @php $pat = $rx->patient; @endphp
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-card flex flex-col gap-3 relative overflow-hidden ring-2 ring-emerald-500/20">
                    <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-emerald-500"></div>

                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-heading font-black text-sm text-emerald-800">{{ $rx->nomor_resep }}</span>
                                <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-bold">{{ $rx->queue?->nomor_antrean ?? 'Antrean tidak tersedia' }}</span>
                            </div>
                            <h4 class="font-heading font-bold text-xs text-slate-900 mt-1">{{ $pat->nama_lengkap }}</h4>
                            <span class="text-[11px] text-slate-400 font-mono">No. RM: {{ $pat->no_rm }}</span>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-bold">Siap</span>
                    </div>

                    <div class="p-2.5 rounded-xl bg-emerald-50/60 border border-emerald-100 text-xs text-emerald-950 flex flex-col gap-1">
                        <span class="font-bold">Obat Siap Diserahkan:</span>
                        <div class="text-[11px] text-slate-600">
                            @foreach ($rx->items as $item)
                                <div>{{ $item->medicine->nama_obat }} — {{ $item->jumlah }} {{ $item->medicine->satuan }} • Batch {{ $item->medicine->nomor_batch }} • Exp {{ $item->medicine->expired_date?->format('d/m/Y') }}</div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Print Etiket & Dispense Confirmed -->
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <a href="{{ route('farmasi.etiket', $rx->id) }}" target="_blank" class="py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold text-center flex items-center justify-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">print</span>
                            <span>Etiket</span>
                        </a>
                        <form action="{{ route('farmasi.dispense', $rx->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold flex items-center justify-center gap-1 shadow-xs">
                                <span class="material-symbols-outlined text-[16px]">handshake</span>
                                <span>Serahkan</span>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-6 rounded-2xl bg-white border border-slate-100 text-center text-xs text-slate-400">
                    Tidak ada obat yang menunggu penyerahan saat ini.
                </div>
            @endforelse

            <!-- Selesai Diserahkan Accordion / Summary -->
            <div class="mt-4 border-t border-slate-200 pt-3 flex items-center justify-between text-xs text-slate-500 font-semibold">
                <span>Total Obat Diserahkan Hari Ini:</span>
                <span class="font-bold text-slate-800">{{ $dispensedCount }} Resep</span>
            </div>
        </div>

    </div>

</div>
@endsection
