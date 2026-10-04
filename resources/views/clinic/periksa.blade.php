@extends('clinic.layouts.app')

@section('title', 'Ruang Periksa Dokter — E-RME')

@section('content')
@if (!$queue)
    <div class="bg-white p-12 rounded-[28px] border border-slate-100 shadow-card text-center max-w-lg mx-auto my-12">
        <span class="material-symbols-outlined text-5xl text-slate-300 block mb-3">stethoscope</span>
        <h2 class="text-lg font-bold font-heading text-slate-800">Tidak Ada Antrean Aktif di Ruang Periksa</h2>
        <p class="text-xs text-slate-500 mt-1 mb-6">Silakan pilih atau panggil pasien dari daftar antrean di dashboard.</p>
        <a href="{{ route('dashboard') }}" class="px-5 py-2.5 rounded-full bg-primary hover:bg-primary-hover text-white text-xs font-bold font-heading shadow-md transition-all">
            Kembali ke Antrean
        </a>
    </div>
@else
    @php
        $patient = $queue->patient;
        $vitalSign = $queue->vitalSign;
        $medRecord = $queue->medicalRecord;
        $presc = $queue->prescription ?? $medRecord?->prescription;
        $dispositionErrors = $errors->has('disposition') || $errors->has('follow_up_at') || $errors->has('follow_up_instructions') || $errors->has('referral_facility') || $errors->has('referral_department') || $errors->has('referral_reason');
        $initialTab = $dispositionErrors ? 'surat' : ($queue->poli === 'gigi' ? 'odontogram' : 'soap');
    @endphp

    <div class="flex flex-col gap-6 pb-24" x-data="{ 
        activeTab: '{{ $initialTab }}',
        terbitkanSuratSakit: false,
        disposition: '{{ old('disposition') }}',
        lamaHari: '',
        tglMulai: '{{ Carbon\Carbon::today()->toDateString() }}',
        calcTglSelesai() {
            let d = new Date(this.tglMulai);
            d.setDate(d.getDate() + parseInt(this.lamaHari || 1) - 1);
            return d.toISOString().split('T')[0];
        },
        prescriptionItems: [],
        selectedMedicineId: '',
        addMedicine() {
            let select = document.getElementById('newMedSelect');
            if (!select || !select.value) return;
            let opt = select.options[select.selectedIndex];
            this.prescriptionItems.push({
                id: select.value,
                name: opt.getAttribute('data-name'),
                code: opt.getAttribute('data-code'),
                batch: opt.getAttribute('data-batch'),
                expired: opt.getAttribute('data-expired'),
                jumlah: '',
                aturan: '',
                stok: opt.getAttribute('data-stok'),
                kategori: opt.getAttribute('data-kategori')
            });
            select.value = '';
        },
        removeMedicine(idx) {
            this.prescriptionItems.splice(idx, 1);
        },
        // Odontogram state
        teethData: {{ json_encode($odontogram?->teeth_data_json ?? []) }},
        activeTooth: null,
        activeCondition: 'caries',
        setToothCondition(toothNum, cond) {
            this.teethData[toothNum] = { ...(this.teethData[toothNum] || {}), number: toothNum, condition: cond };
        }
    }">

        <!-- ROOM & SESSION EXECUTIVE HEADER BANNER (Matching Stitch) -->
        <div class="relative overflow-hidden rounded-[24px] bg-white p-5 border border-slate-100 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4 min-w-0">
                <div class="w-12 h-12 rounded-2xl bg-primary text-white flex items-center justify-center flex-shrink-0 shadow-sm">
                    <span class="material-symbols-outlined text-[24px]">{{ $queue->poli === 'gigi' ? 'dentistry' : 'stethoscope' }}</span>
                </div>
                <div class="flex flex-col min-w-0">
                    <div class="flex items-center gap-2">
                        <h2 class="font-heading font-extrabold text-base lg:text-lg text-slate-900">
                            Ruang Periksa {{ $queue->poli === 'gigi' ? 'Poli Gigi & Mulut' : '1 — Poli Umum' }}
                        </h2>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 text-[11px] font-bold border border-emerald-200">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                            Sesi Aktif
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 mt-0.5">
                        <span class="font-bold text-slate-800">{{ $currentUser->name }}</span>
                        <span>•</span>
                        <span>SIP: {{ $currentUser->sip ?: 'Belum dicatat' }}</span>
                        <span>•</span>
                        <span class="text-slate-500">Sinkronisasi eksternal belum dikonfigurasi</span>
                    </div>
                </div>
            </div>

            <!-- Antrean Focus Pill -->
            <div class="flex items-center gap-4 bg-slate-50 px-4 py-2 rounded-2xl border border-slate-200/80 flex-shrink-0">
                <div class="flex flex-col items-end">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sedang Diperiksa</span>
                    <span class="font-heading font-black text-2xl text-primary leading-none">{{ $queue->nomor_antrean }}</span>
                </div>
                <div class="w-px h-8 bg-slate-200"></div>
                <div class="flex items-center gap-1.5">
                    <button type="button" onclick="alert('Suara Panggilan: Antrean nomor {{ $queue->nomor_antrean }}, atas nama {{ $patient->nama_lengkap }}, silakan masuk ke Ruang Periksa!')" class="p-2 rounded-xl bg-white hover:bg-slate-100 text-primary border border-slate-200 shadow-xs transition-colors" title="Panggil Ulang Pasien">
                        <span class="material-symbols-outlined text-[18px]">volume_up</span>
                    </button>
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-1 px-3 py-1.5 rounded-xl bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold border border-slate-200 shadow-xs transition-colors">
                        <span>Antrean Berikut</span>
                        <span class="material-symbols-outlined text-[16px]">skip_next</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- VISIT WORKFLOW HISTORY: clinician-scoped, operational metadata only -->
        <section class="rounded-[24px] bg-white p-5 border border-slate-100 shadow-card" aria-labelledby="visit-history-title">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <h3 id="visit-history-title" class="font-heading font-extrabold text-sm text-slate-900">Riwayat Alur Kunjungan</h3>
                    <p class="text-xs text-slate-500 mt-1">Perubahan status antrean, pelaku, waktu, dan alasan workflow.</p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 border border-slate-200 px-2.5 py-1 text-[10px] font-bold text-slate-600">
                    <span class="material-symbols-outlined text-[14px]">history</span>
                    Metadata alur saja
                </span>
            </div>
            @if ($queue->statusHistories->isEmpty())
                <p class="rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-500">Belum ada riwayat status yang tersedia.</p>
            @else
                <ol class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    @foreach ($queue->statusHistories as $history)
                        @php
                            $statusLabels = [
                                'menunggu_triage' => 'Menunggu triase',
                                'siap_periksa' => 'Siap diperiksa',
                                'sedang_diperiksa' => 'Sedang diperiksa',
                                'menunggu_obat' => 'Menunggu farmasi',
                                'sedang_dispensing' => 'Sedang disiapkan',
                                'siap_diambil' => 'Obat siap diambil',
                                'selesai' => 'Pelayanan selesai',
                                'dibatalkan' => 'Dibatalkan',
                            ];
                            $roleLabels = [
                                'pendaftaran' => 'Pendaftaran',
                                'perawat' => 'Perawat',
                                'dokter' => 'Dokter umum',
                                'dokter_gigi' => 'Dokter gigi',
                                'farmasi' => 'Farmasi',
                                'admin' => 'Administrator',
                                'system' => 'Sistem',
                            ];
                        @endphp
                        <li class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-xs font-extrabold text-slate-800">
                                        @if ($history->from_status)
                                            {{ $statusLabels[$history->from_status] ?? $history->from_status }}
                                            <span aria-hidden="true" class="text-slate-400">→</span>
                                        @endif
                                        {{ $statusLabels[$history->to_status] ?? $history->to_status }}
                                    </p>
                                    <p class="text-[11px] text-slate-500 mt-1">
                                        {{ $history->changed_by_name ?: 'Pelaku tidak tercatat' }}
                                        <span>·</span>
                                        {{ $roleLabels[$history->changed_by_role] ?? $history->changed_by_role }}
                                    </p>
                                </div>
                                <span class="material-symbols-outlined text-slate-400 text-[18px]" aria-hidden="true">{{ $history->origin === 'migration_snapshot' ? 'inventory_2' : 'event_note' }}</span>
                            </div>
                            <p class="text-[10px] font-semibold text-slate-500 mt-3">
                                {{ $history->recorded_at?->timezone(config('app.timezone'))->format('d M Y, H:i') ?? 'Waktu tidak tersedia' }}
                                @if ($history->origin === 'migration_snapshot')
                                    <span class="block mt-1 text-amber-700">Snapshot saat migrasi; bukan rekonstruksi riwayat historis.</span>
                                @endif
                            </p>
                            @if ($history->reason)
                                <p class="text-[11px] text-slate-600 mt-2">Alasan: {{ $history->reason }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        <!-- MAIN SPLIT WORKSPACE: 38% Profile & TTV | 62% Clinical RME Form -->
        <div class="grid grid-cols-12 gap-6 items-start">
            
            <!-- LEFT PANEL: Patient Demographics, Safety Alert, TTV, Attachments (5 cols) -->
            <div class="col-span-12 lg:col-span-5 flex flex-col gap-5">
                
                <!-- Patient Demographics Soft Card -->
                <div class="rounded-[24px] bg-white p-5 border border-slate-100 shadow-card flex flex-col gap-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="relative w-14 h-14 rounded-2xl overflow-hidden bg-slate-100 flex-shrink-0 shadow-xs">
                                <img class="w-full h-full object-cover" src="{{ $patient->avatar_url ?? '/assets/images/166de6af4e856812b08f19dc83cc9152.webp' }}" alt="{{ $patient->nama_lengkap }}"/>
                                <span class="absolute bottom-1 right-1 w-3 h-3 rounded-full {{ $patient->identity_status === 'verified' ? 'bg-emerald-500' : ($patient->identity_status === 'unidentified' ? 'bg-red-500' : 'bg-amber-500') }} ring-2 ring-white" title="{{ $patient->identity_status === 'verified' ? 'Identitas terverifikasi' : ($patient->identity_status === 'unidentified' ? 'Identitas belum diketahui' : 'Identitas sementara/belum diverifikasi') }}"></span>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <h3 class="font-heading font-extrabold text-sm text-slate-900 truncate">{{ $patient->nama_lengkap }}</h3>
                                <span class="text-xs font-bold text-primary font-mono">No. RM: {{ $patient->no_rm }}</span>
                                <span class="text-[11px] text-slate-500 font-mono">{{ $patient->nip ? 'NIP: ' . $patient->nip : 'Masyarakat' }}</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap justify-end gap-2">
                            <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-[11px] font-bold font-heading whitespace-nowrap">
                                {{ $patient->jenis_pasien ?? 'Umum' }}
                            </span>
                            @if ($queue->is_emergency || $patient->identity_status === 'unidentified')
                                <span class="px-2.5 py-1 rounded-full bg-red-100 text-red-800 text-[10px] font-extrabold whitespace-nowrap">DARURAT • IDENTITAS SEMENTARA</span>
                            @endif
                        </div>
                    </div>

                    <!-- 3 Grid Info Strip -->
                    <div class="grid grid-cols-3 gap-2 p-3 rounded-2xl bg-slate-50 border border-slate-100 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Usia / JK</span>
                            <span class="font-extrabold text-slate-800">{{ $patient->usia ?? 'Usia belum dicatat' }} {{ $patient->usia ? 'Thn' : '' }} / {{ $patient->jenis_kelamin === 'L' ? 'Laki-laki' : ($patient->jenis_kelamin === 'P' ? 'Perempuan' : 'Belum diketahui') }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Gol. Darah</span>
                            <span class="font-extrabold text-slate-800">{{ $patient->golongan_darah ?? 'Belum dicatat' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Penjamin</span>
                            <span class="font-extrabold text-primary">{{ $patient->no_bpjs ? 'BPJS tercatat' : 'Belum dicatat' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 text-xs text-slate-600 bg-slate-50 px-3 py-2 rounded-xl">
                        <span class="material-symbols-outlined text-[18px] text-primary flex-shrink-0">apartment</span>
                        <span class="truncate">{{ $patient->unit_kerja ?? 'Unit kerja belum dicatat' }}</span>
                    </div>
                </div>

                <!-- CRITICAL SAFETY: Allergy Alert Box (Soft Red High Contrast) -->
                @if ($patient->riwayat_alergi && !str_contains(strtolower($patient->riwayat_alergi), 'tidak ada'))
                    <div class="rounded-2xl bg-red-50/90 border border-red-200 p-4 flex items-start gap-3 shadow-xs">
                        <div class="w-9 h-9 rounded-xl bg-red-600 text-white flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-[20px]">warning</span>
                        </div>
                        <div class="flex flex-col gap-0.5 flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold font-heading text-red-900 tracking-wide uppercase">
                                    Riwayat alergi yang tercatat
                                </span>
                                <span class="px-2 py-0.5 rounded-full bg-red-600 text-white text-[10px] font-extrabold">KONTRAINDIKASI</span>
                            </div>
                            <p class="text-xs text-red-800 font-medium leading-relaxed">
                                <strong class="underline">{{ $patient->riwayat_alergi }}</strong>. 
                                {{ $patient->alergi_keterangan }}
                            </p>
                        </div>
                    </div>
                @endif

                <!-- Verified TTV (Tanda-Tanda Vital Input Perawat) -->
                <div class="rounded-[24px] bg-white p-5 border border-slate-100 shadow-card flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[20px]">monitor_heart</span>
                            <h3 class="font-heading font-extrabold text-sm text-slate-900">Tanda-Tanda Vital (TTV)</h3>
                        </div>
                        <span class="flex items-center gap-1 text-[11px] font-bold text-primary bg-emerald-50 border border-emerald-100 px-2.5 py-0.5 rounded-full">
                            <span class="material-symbols-outlined text-[13px]">verified</span>
                            {{ $vitalSign?->nurse?->name ?? 'Data triage' }}
                        </span>
                    </div>

                    <!-- 4 Metric TTV Grid -->
                    <div class="grid grid-cols-2 gap-3">
                        
                        <!-- Blood Pressure -->
                        <div class="p-3.5 rounded-2xl bg-amber-50/60 border border-amber-100 flex flex-col justify-between">
                            <div class="flex items-center justify-between text-slate-500">
                                <span class="text-[11px] font-bold uppercase text-amber-900">Tekanan Darah</span>
                                <span class="material-symbols-outlined text-[18px] text-amber-600">speed</span>
                            </div>
                            <div class="mt-2 flex items-baseline gap-1">
                                <span class="font-heading font-black text-2xl text-slate-900">{{ $vitalSign?->sistolik !== null ? $vitalSign->sistolik.'/'.$vitalSign->diastolik : 'Belum dicatat' }}</span>
                                <span class="text-xs text-slate-500">mmHg</span>
                            </div>
                        </div>

                        <!-- Body Temperature -->
                        <div class="p-3.5 rounded-2xl bg-amber-50/60 border border-amber-100 flex flex-col justify-between">
                            <div class="flex items-center justify-between text-slate-500">
                                <span class="text-[11px] font-bold uppercase text-amber-900">Suhu Tubuh</span>
                                <span class="material-symbols-outlined text-[18px] text-amber-600">device_thermostat</span>
                            </div>
                            <div class="mt-2 flex items-baseline gap-1">
                                <span class="font-heading font-black text-2xl text-slate-900">{{ $vitalSign?->suhu ?? 'Belum dicatat' }}</span>
                                <span class="text-xs text-slate-500">°C</span>
                            </div>
                        </div>

                        <!-- Heart Rate -->
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-400">Denyut Nadi</span>
                                <div class="font-heading font-extrabold text-base text-slate-900">{{ $vitalSign?->nadi ?? 'Belum dicatat' }} <span class="text-xs font-normal text-slate-400">x/mnt</span></div>
                            </div>
                            <span class="material-symbols-outlined text-slate-400 text-[18px]">favorite</span>
                        </div>

                        <!-- Respiratory Rate -->
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-400">Laju Nafas</span>
                                <div class="font-heading font-extrabold text-base text-slate-900">{{ $vitalSign?->laju_nafas ?? 'Belum dicatat' }} <span class="text-xs font-normal text-slate-400">x/mnt</span></div>
                            </div>
                            <span class="material-symbols-outlined text-slate-400 text-[18px]">air</span>
                        </div>

                        <!-- SpO2 -->
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-400">Saturasi SpO2</span>
                                <div class="font-heading font-extrabold text-base text-slate-900">{{ $vitalSign?->spo2 ?? 'Belum dicatat' }} <span class="text-xs font-normal text-slate-400">%</span></div>
                            </div>
                            <span class="material-symbols-outlined text-emerald-600 text-[18px]">water_drop</span>
                        </div>

                        <!-- IMT Antropometri -->
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-400">BB/TB (IMT)</span>
                                <div class="font-heading font-extrabold text-xs text-slate-900">{{ $vitalSign?->berat_badan ?? '—' }} kg / {{ $vitalSign?->tinggi_badan ?? '—' }} cm (IMT {{ $vitalSign?->imt ?? '—' }})</div>
                            </div>
                            <span class="material-symbols-outlined text-slate-400 text-[18px]">scale</span>
                        </div>

                    </div>
                </div>

                <!-- EXTERNAL MEDICAL ATTACHMENTS (Berkas Medis Luar) -->
                <div class="rounded-[24px] bg-white p-5 border border-slate-100 shadow-card flex flex-col gap-3" x-data="{ showUploadModal: false }">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[20px]">attachment</span>
                            <h3 class="font-heading font-extrabold text-sm text-slate-900">Berkas Medis Eksternal</h3>
                        </div>
                        <button @click="showUploadModal = true" type="button" class="flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                            <span class="material-symbols-outlined text-[16px]">upload_file</span>
                            + Unggah Berkas
                        </button>
                    </div>

                    <p class="text-xs text-slate-500">Hasil laboratorium atau radiologi luar yang dibawa pasien (Maks 10MB, PDF/JPG/PNG).</p>

                    <!-- Attachments List -->
                    <div class="flex flex-col gap-2">
                        @forelse ($patient->attachments as $att)
                            @php $kat = $att->kategori_badge; @endphp
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between gap-3 hover:bg-slate-100/70 transition-colors">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="material-symbols-outlined text-primary text-[20px]">{{ $kat['icon'] }}</span>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs font-bold text-slate-900 truncate">{{ $att->judul_dokumen }}</span>
                                        <span class="text-[10px] text-slate-500 truncate">{{ $att->instansi_penerbit }} • {{ $att->file_size_kb }} KB</span>
                                    </div>
                                </div>
                            <a href="{{ route('pasien.attachment.download', [$patient->id, $att->id]) }}" class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-primary text-xs font-bold shadow-xs hover:bg-emerald-50">
                                    Buka
                                </a>
                            </div>
                        @empty
                            <div class="p-3 rounded-xl bg-slate-50 text-center text-xs text-slate-400">
                                Belum ada berkas eksternal untuk pasien ini.
                            </div>
                        @endforelse
                    </div>

                    <!-- Upload Modal -->
                    <div x-show="showUploadModal" x-cloak class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
                        <div @click.away="showUploadModal = false" class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-slate-100 flex flex-col gap-4 font-heading">
                            <div class="flex items-center justify-between">
                                <h3 class="font-bold text-base text-slate-900">Unggah Berkas Medis Eksternal</h3>
                                <button @click="showUploadModal = false" class="text-slate-400 hover:text-slate-600">
                                    <span class="material-symbols-outlined">close</span>
                                </button>
                            </div>
                            <form action="{{ route('patient.attachment', $patient->id) }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-3 font-body text-xs">
                                @csrf
                                <div>
                                    <label class="font-bold text-slate-700 block mb-1">Judul Dokumen</label>
                                    <input type="text" name="judul_dokumen" required placeholder="Contoh: Hasil Darah Lengkap / Foto Rontgen Thorax" class="w-full bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs"/>
                                </div>
                                <div>
                                    <label class="font-bold text-slate-700 block mb-1">Kategori Dokumen</label>
                                    <select name="kategori" class="w-full bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs">
                                        <option value="lab_luar">Hasil Laboratorium Luar</option>
                                        <option value="rontgen_radiologi">Foto Rontgen / Radiologi</option>
                                        <option value="jawaban_rujukan">Surat Jawaban Rujukan Rumah Sakit</option>
                                        <option value="resume_medis_luar">Resume Medis Eksternal</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="font-bold text-slate-700 block mb-1">Instansi Penerbit / Laboratorium</label>
                                    <input type="text" name="instansi_penerbit" placeholder="RSUP Cipto Mangunkusumo / Lab Prodia" class="w-full bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs"/>
                                </div>
                                <div>
                                    <label class="font-bold text-slate-700 block mb-1">Pilih File Berkas (PDF, JPG, PNG, DCM)</label>
                                    <input type="file" name="berkas" accept=".pdf,.jpg,.jpeg,.png" required class="w-full bg-slate-50 p-2 rounded-xl border border-slate-200 text-xs"/>
                                </div>
                                <div class="flex justify-end gap-2 mt-2">
                                    <button type="button" @click="showUploadModal = false" class="px-4 py-2 rounded-xl border border-slate-200 font-bold text-slate-600">Batal</button>
                                    <button type="submit" class="px-5 py-2 rounded-xl bg-primary text-white font-bold shadow-sm">Simpan Berkas</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT PANEL: CLINICAL RME WORKSPACE (7 cols) -->
            <div class="col-span-12 lg:col-span-7 flex flex-col gap-5">
                
                <!-- Clinical Tabs Switcher -->
                <div class="flex items-center gap-2 p-1.5 bg-white rounded-2xl border border-slate-100 shadow-card font-heading text-xs font-bold">
                    <button type="button" @click="activeTab = 'soap'" :class="activeTab === 'soap' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50'" class="flex-1 py-2 px-3 rounded-xl transition-all flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">clinical_notes</span>
                        <span>Catatan SOAP & e-Resep</span>
                    </button>
                    @if ($queue->poli === 'gigi')
                    <button type="button" @click="activeTab = 'odontogram'" :class="activeTab === 'odontogram' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50'" class="flex-1 py-2 px-3 rounded-xl transition-all flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">dentistry</span>
                        <span>Modul Odontogram (Poli Gigi)</span>
                    </button>
                    @endif
                    <button type="button" @click="activeTab = 'surat'" :class="activeTab === 'surat' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50'" class="flex-1 py-2 px-3 rounded-xl transition-all flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">assignment_turned_in</span>
                        <span>Administrasi & Surat</span>
                    </button>
                </div>

                <!-- MAIN FORM WRAPPER FOR FINAL SUBMISSION -->
                <form action="{{ route('periksa.finish', $queue->id) }}" method="POST" id="formDoctorSession" class="flex flex-col gap-5">
                    @csrf
                    <input type="hidden" name="teeth_data_json" :value="JSON.stringify(teethData)">

                    <!-- TAB 1: SOAP & e-RESEP -->
                    <div x-show="activeTab === 'soap'" class="flex flex-col gap-5">
                        
                        <!-- SECTION 1: Subjective & Objective -->
                        <div class="rounded-[24px] bg-white p-5 border border-slate-100 shadow-card flex flex-col gap-4">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-[20px]">history_edu</span>
                                    <h3 class="font-heading font-extrabold text-sm text-slate-900">Anamnesis & Pemeriksaan Fisik (SO)</h3>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button type="button" class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-xs font-semibold hover:bg-slate-200">
                                        Voice Dictation
                                    </button>
                                </div>
                            </div>

                            <!-- Subjective -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-slate-700">Subjective (Keluhan Pasien & Anamnesis):</label>
                                <textarea name="subjective" rows="3" required class="w-full bg-slate-50 focus:bg-white text-slate-900 text-xs p-3 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-primary/20 leading-relaxed">{{ old('subjective', $medRecord?->subjective) }}</textarea>
                            </div>

                            <!-- Objective -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-slate-700">Objective (Temuan Fisik Dokter):</label>
                                <textarea name="objective" rows="4" required class="w-full bg-slate-50 focus:bg-white text-slate-900 text-xs p-3 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-primary/20 leading-relaxed font-mono">{{ old('objective', $medRecord?->objective) }}</textarea>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-slate-700">Assessment (penilaian klinis):</label>
                                <textarea name="assessment" rows="3" required class="w-full bg-slate-50 focus:bg-white text-slate-900 text-xs p-3 rounded-xl border border-slate-200">{{ old('assessment', $medRecord?->assessment) }}</textarea>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-slate-700">Plan (rencana pemeriksaan dan tindak lanjut):</label>
                                <textarea name="plan" rows="3" required class="w-full bg-slate-50 focus:bg-white text-slate-900 text-xs p-3 rounded-xl border border-slate-200">{{ old('plan', $medRecord?->plan) }}</textarea>
                            </div>

                            <!-- Quick Observation Tags -->
                        </div>

                        <!-- SECTION 2: Assessment & ICD-10 Diagnosa -->
                        <div class="rounded-[24px] bg-white p-5 border border-slate-100 shadow-card flex flex-col gap-4">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-[20px]">fact_check</span>
                                    <h3 class="font-heading font-extrabold text-sm text-slate-900">Diagnosis medis (ICD-10)</h3>
                                </div>
                                <span class="text-xs text-slate-500">Pilih kode sesuai diagnosis</span>
                            </div>

                            <label class="flex flex-col gap-1.5 text-xs font-semibold text-slate-700">Diagnosis utama ICD-10
                                <select name="icd10_primary_code" class="rounded-xl border-slate-300 bg-slate-50 p-3 text-sm">
                                    <option value="">Pilih kode (opsional)</option>
                                    @foreach ($commonIcd10 as $diagnosis)
                                        <option value="{{ $diagnosis->code }}" @selected(old('icd10_primary_code', $medRecord?->icd10_primary_code) === $diagnosis->code)>{{ $diagnosis->code }} — {{ $diagnosis->name_id }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <!-- SECTION 3: E-Resep & Order Obat Elektronik (Terhubung ke Farmasi) -->
                        <div class="rounded-[24px] bg-white p-5 border border-slate-100 shadow-card flex flex-col gap-4">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-[20px]">medication</span>
                                    <div>
                                        <h3 class="font-heading font-extrabold text-sm text-slate-900">E-Resep & Dispensing Farmasi Klinik</h3>
                                        <p class="text-[11px] text-slate-500">Stok divalidasi kembali saat farmasi memproses resep.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900">Tinjau alergi pasien yang tercatat di panel kiri sebelum menyusun resep. Sistem belum menjalankan pemeriksaan interaksi atau kontraindikasi obat otomatis.</div>

                            <!-- Add Drug Row Form -->
                            <div class="flex items-center gap-2 p-3 bg-slate-50 rounded-2xl border border-slate-200">
                                <select id="newMedSelect" class="flex-1 bg-white p-2.5 rounded-xl border border-slate-200 text-xs font-medium outline-none">
                                    <option value="">-- Tambah Obat dari Master Farmasi --</option>
                                    @foreach ($medicines as $med)
                                        <option value="{{ $med->id }}" 
                                                data-name="{{ $med->nama_obat }}" 
                                                data-code="{{ $med->kode_obat }}"
                                                data-batch="{{ $med->nomor_batch }}"
                                                data-expired="{{ $med->expired_date?->format('d/m/Y') }}"
                                                data-stok="{{ $med->stok }}" 
                                                data-aturan="{{ $med->aturan_pakai_default }}" 
                                                data-kategori="{{ $med->kategori }}">
                                            {{ $med->nama_obat }} • {{ $med->kode_obat }} • Batch {{ $med->nomor_batch }} • Exp {{ $med->expired_date?->format('d/m/Y') }} (Stok: {{ $med->stok }} {{ $med->satuan }})
                                        </option>
                                    @endforeach
                                </select>
                                <button type="button" @click="addMedicine()" class="px-4 py-2.5 rounded-xl bg-primary text-white text-xs font-bold flex items-center gap-1 shadow-xs hover:bg-primary-hover">
                                    <span class="material-symbols-outlined text-[16px]">add</span> Tambah
                                </button>
                            </div>

                            <!-- Prescribed Medicines List Table -->
                            <div class="flex flex-col gap-2">
                                <template x-for="(med, idx) in prescriptionItems" :key="idx">
                                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3 hover:bg-slate-100/70 transition-colors">
                                        <div class="flex items-center gap-3 min-w-0 flex-1">
                                            <div class="w-7 h-7 rounded-lg bg-white border border-slate-200 text-primary flex items-center justify-center font-mono font-bold text-xs shadow-xs" x-text="idx + 1"></div>
                                            <div class="flex flex-col min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-heading font-bold text-xs text-slate-900" x-text="med.name"></span>
                                                    <span class="px-2 py-0.5 rounded bg-slate-200 text-slate-700 text-[10px]" x-text="med.kategori"></span>
                                                </div>
                                                <div class="mt-2 flex gap-2"><label class="text-[10px]">Jumlah<input type="number" min="1" max="1000" x-model="med.jumlah" class="mt-1 w-20 rounded-lg border-slate-300 p-1.5"></label><label class="flex-1 text-[10px]">Aturan pakai<input type="text" maxlength="255" x-model="med.aturan" class="mt-1 w-full rounded-lg border-slate-300 p-1.5" placeholder="Isi aturan pakai"></label></div>
                                                <div class="mt-1 text-[10px] text-slate-500" x-text="'Kode ' + med.code + ' • Batch ' + med.batch + ' • Exp ' + med.expired"></div>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between md:justify-end gap-4 flex-shrink-0">
                                            <div class="flex flex-col items-end text-xs">
                                                <span class="font-heading font-extrabold text-slate-800" x-text="med.jumlah + ' Unit'"></span>
                                                <span class="text-emerald-700 font-semibold text-[10px]" x-text="'Stok: ' + med.stok"></span>
                                            </div>
                                            <button type="button" @click="removeMedicine(idx)" class="p-1.5 text-slate-400 hover:text-red-600 rounded-lg">
                                                <span class="material-symbols-outlined text-[18px]">close</span>
                                            </button>
                                        </div>

                                        <!-- Hidden Inputs for Form Submission -->
                                        <input type="hidden" :name="'medicines[' + idx + '][medicine_id]'" :value="med.id"/>
                                        <input type="hidden" :name="'medicines[' + idx + '][jumlah]'" :value="med.jumlah"/>
                                        <input type="hidden" :name="'medicines[' + idx + '][aturan_pakai]'" :value="med.aturan"/>
                                    </div>
                                </template>
                            </div>

                            <!-- Pharmacist Note -->
                            <div class="flex flex-col gap-1.5 mt-1">
                                <label class="text-xs font-semibold text-slate-600">Catatan Khusus untuk Apoteker:</label>
                                <input type="text" name="catatan_resep" value="{{ old('catatan_resep') }}" class="w-full bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs"/>
                            </div>
                        </div>

                    </div>

                    <!-- TAB 2: ODONTOGRAM (POLI GIGI 52 GIGI) -->
                    <div x-show="activeTab === 'odontogram'" class="flex flex-col gap-5">
                        <div class="rounded-[24px] bg-white p-5 border border-slate-100 shadow-card flex flex-col gap-4">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div>
                                    <h3 class="font-heading font-extrabold text-sm text-slate-900">Peta odontogram gigi (FDI)</h3>
                                    <p class="text-xs text-slate-500">Pemeriksaan 32 elemen gigi permanen menurut penomoran FDI</p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs font-bold text-slate-500 mr-2">Pilih Kondisi:</span>
                                    <button type="button" @click="activeCondition = 'caries'" :class="activeCondition === 'caries' ? 'ring-2 ring-red-500' : ''" class="px-2.5 py-1 rounded-lg bg-red-100 text-red-800 text-xs font-bold">Karies (Kavitas)</button>
                                    <button type="button" @click="activeCondition = 'amalgam'" :class="activeCondition === 'amalgam' ? 'ring-2 ring-slate-800' : ''" class="px-2.5 py-1 rounded-lg bg-slate-800 text-white text-xs font-bold">Amalgam</button>
                                    <button type="button" @click="activeCondition = 'composite'" :class="activeCondition === 'composite' ? 'ring-2 ring-blue-500' : ''" class="px-2.5 py-1 rounded-lg bg-blue-100 text-blue-800 text-xs font-bold">Komposit</button>
                                    <button type="button" @click="activeCondition = 'missing'" :class="activeCondition === 'missing' ? 'ring-2 ring-amber-500' : ''" class="px-2.5 py-1 rounded-lg bg-amber-100 text-amber-800 text-xs font-bold">Gigi Hilang (X)</button>
                                    <button type="button" @click="activeCondition = 'crown'" :class="activeCondition === 'crown' ? 'ring-2 ring-yellow-500' : ''" class="px-2.5 py-1 rounded-lg bg-yellow-100 text-yellow-800 text-xs font-bold">Crown / Mahkota</button>
                                    <button type="button" @click="activeCondition = 'normal'" class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-xs font-bold">Normal</button>
                                </div>
                            </div>

                            <!-- Teeth Chart Graphical Canvas -->
                            <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 flex flex-col gap-6 items-center overflow-x-auto">
                                
                                <!-- Rahang Atas Dewasa (Quadrant 1 & 2) -->
                                <div class="flex flex-col items-center gap-1">
                                    <span class="text-[10px] font-bold uppercase text-slate-400">Rahang Atas (Dewasa)</span>
                                    <div class="flex items-center gap-1.5">
                                        <!-- Q1: 18 - 11 -->
                                        @foreach ([18, 17, 16, 15, 14, 13, 12, 11] as $tNum)
                                            <div @click="setToothCondition({{ $tNum }}, activeCondition)" class="cursor-pointer group flex flex-col items-center">
                                                <span class="text-[10px] font-mono font-bold text-slate-600 mb-0.5">{{ $tNum }}</span>
                                                <div class="w-8 h-8 rounded-lg border flex items-center justify-center font-bold text-[10px] transition-all shadow-xs"
                                                     :class="{
                                                         'bg-red-500 text-white border-red-600': teethData[{{ $tNum }}]?.condition === 'caries',
                                                         'bg-slate-800 text-white border-slate-900': teethData[{{ $tNum }}]?.condition === 'amalgam',
                                                         'bg-blue-500 text-white border-blue-600': teethData[{{ $tNum }}]?.condition === 'composite',
                                                         'bg-slate-300 text-slate-600 line-through border-slate-400': teethData[{{ $tNum }}]?.condition === 'missing',
                                                         'bg-yellow-400 text-yellow-950 border-yellow-500': teethData[{{ $tNum }}]?.condition === 'crown',
                                                         'bg-white text-slate-700 border-slate-300 hover:border-primary': !teethData[{{ $tNum }}]?.condition || teethData[{{ $tNum }}]?.condition === 'normal'
                                                     }">
                                                    <span x-text="teethData[{{ $tNum }}]?.condition === 'missing' ? 'X' : {{ $tNum }}"></span>
                                                </div>
                                            </div>
                                        @endforeach
                                        <div class="w-px h-10 bg-slate-300 mx-2"></div>
                                        <!-- Q2: 21 - 28 -->
                                        @foreach ([21, 22, 23, 24, 25, 26, 27, 28] as $tNum)
                                            <div @click="setToothCondition({{ $tNum }}, activeCondition)" class="cursor-pointer group flex flex-col items-center">
                                                <span class="text-[10px] font-mono font-bold text-slate-600 mb-0.5">{{ $tNum }}</span>
                                                <div class="w-8 h-8 rounded-lg border flex items-center justify-center font-bold text-[10px] transition-all shadow-xs"
                                                     :class="{
                                                         'bg-red-500 text-white border-red-600': teethData[{{ $tNum }}]?.condition === 'caries',
                                                         'bg-slate-800 text-white border-slate-900': teethData[{{ $tNum }}]?.condition === 'amalgam',
                                                         'bg-blue-500 text-white border-blue-600': teethData[{{ $tNum }}]?.condition === 'composite',
                                                         'bg-slate-300 text-slate-600 line-through border-slate-400': teethData[{{ $tNum }}]?.condition === 'missing',
                                                         'bg-yellow-400 text-yellow-950 border-yellow-500': teethData[{{ $tNum }}]?.condition === 'crown',
                                                         'bg-white text-slate-700 border-slate-300 hover:border-primary': !teethData[{{ $tNum }}]?.condition || teethData[{{ $tNum }}]?.condition === 'normal'
                                                     }">
                                                    <span x-text="teethData[{{ $tNum }}]?.condition === 'missing' ? 'X' : {{ $tNum }}"></span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Rahang Bawah Dewasa (Quadrant 4 & 3) -->
                                <div class="flex flex-col items-center gap-1 border-t border-slate-200 pt-3">
                                    <div class="flex items-center gap-1.5">
                                        <!-- Q4: 48 - 41 -->
                                        @foreach ([48, 47, 46, 45, 44, 43, 42, 41] as $tNum)
                                            <div @click="setToothCondition({{ $tNum }}, activeCondition)" class="cursor-pointer group flex flex-col items-center">
                                                <div class="w-8 h-8 rounded-lg border flex items-center justify-center font-bold text-[10px] transition-all shadow-xs"
                                                     :class="{
                                                         'bg-red-500 text-white border-red-600': teethData[{{ $tNum }}]?.condition === 'caries',
                                                         'bg-slate-800 text-white border-slate-900': teethData[{{ $tNum }}]?.condition === 'amalgam',
                                                         'bg-blue-500 text-white border-blue-600': teethData[{{ $tNum }}]?.condition === 'composite',
                                                         'bg-slate-300 text-slate-600 line-through border-slate-400': teethData[{{ $tNum }}]?.condition === 'missing',
                                                         'bg-yellow-400 text-yellow-950 border-yellow-500': teethData[{{ $tNum }}]?.condition === 'crown',
                                                         'bg-white text-slate-700 border-slate-300 hover:border-primary': !teethData[{{ $tNum }}]?.condition || teethData[{{ $tNum }}]?.condition === 'normal'
                                                     }">
                                                    <span x-text="teethData[{{ $tNum }}]?.condition === 'missing' ? 'X' : {{ $tNum }}"></span>
                                                </div>
                                                <span class="text-[10px] font-mono font-bold text-slate-600 mt-0.5">{{ $tNum }}</span>
                                            </div>
                                        @endforeach
                                        <div class="w-px h-10 bg-slate-300 mx-2"></div>
                                        <!-- Q3: 31 - 38 -->
                                        @foreach ([31, 32, 33, 34, 35, 36, 37, 38] as $tNum)
                                            <div @click="setToothCondition({{ $tNum }}, activeCondition)" class="cursor-pointer group flex flex-col items-center">
                                                <div class="w-8 h-8 rounded-lg border flex items-center justify-center font-bold text-[10px] transition-all shadow-xs"
                                                     :class="{
                                                         'bg-red-500 text-white border-red-600': teethData[{{ $tNum }}]?.condition === 'caries',
                                                         'bg-slate-800 text-white border-slate-900': teethData[{{ $tNum }}]?.condition === 'amalgam',
                                                         'bg-blue-500 text-white border-blue-600': teethData[{{ $tNum }}]?.condition === 'composite',
                                                         'bg-slate-300 text-slate-600 line-through border-slate-400': teethData[{{ $tNum }}]?.condition === 'missing',
                                                         'bg-yellow-400 text-yellow-950 border-yellow-500': teethData[{{ $tNum }}]?.condition === 'crown',
                                                         'bg-white text-slate-700 border-slate-300 hover:border-primary': !teethData[{{ $tNum }}]?.condition || teethData[{{ $tNum }}]?.condition === 'normal'
                                                     }">
                                                    <span x-text="teethData[{{ $tNum }}]?.condition === 'missing' ? 'X' : {{ $tNum }}"></span>
                                                </div>
                                                <span class="text-[10px] font-mono font-bold text-slate-600 mt-0.5">{{ $tNum }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                    <span class="text-[10px] font-bold uppercase text-slate-400 mt-0.5">Rahang Bawah (Dewasa)</span>
                                </div>

                            </div>

                            <!-- Occlusion & Palatum Fields -->
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="text-xs font-bold text-slate-700 block mb-1">Oklusi</label>
                            <input type="text" name="oklusi" value="{{ old('oklusi', $odontogram?->oklusi) }}" class="w-full bg-slate-50 p-2 rounded-xl border border-slate-200 text-xs"/>
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-700 block mb-1">Torus Palatinus</label>
                                    <input type="text" name="torus_palatinus" value="{{ old('torus_palatinus', $odontogram?->torus_palatinus) }}" class="w-full bg-slate-50 p-2 rounded-xl border border-slate-200 text-xs"/>
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-700 block mb-1">Palatum</label>
                                    <input type="text" name="palatum" value="{{ old('palatum', $odontogram?->palatum) }}" class="w-full bg-slate-50 p-2 rounded-xl border border-slate-200 text-xs"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: ADMINISTRASI & SURAT-MENYURAT DIGITAL -->
                    <div x-show="activeTab === 'surat'" class="flex flex-col gap-5">
                        <div class="rounded-[24px] bg-white p-5 border border-slate-100 shadow-card flex flex-col gap-4">
                            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                                <span class="material-symbols-outlined text-primary text-[20px]">clinical_notes</span>
                                <div>
                                    <h3 class="font-heading font-extrabold text-sm text-slate-900">Disposition Kunjungan</h3>
                                    <p class="text-[11px] text-slate-500 mt-1">Catat rencana pasien setelah pemeriksaan. Resep tetap diproses farmasi sebelum kunjungan ditutup.</p>
                                </div>
                            </div>
                            <label class="flex flex-col gap-1.5 text-xs font-semibold text-slate-700">Rencana akhir klinis
                                <select name="disposition" x-model="disposition" class="rounded-xl border-slate-300 bg-slate-50 p-3 text-sm">
                                    <option value="" disabled>Pilih rencana akhir klinis</option>
                                    <option value="home">Pulang dengan edukasi dan rencana SOAP</option>
                                    <option value="follow_up">Kontrol kembali ke klinik</option>
                                    <option value="referral">Dirujuk ke fasilitas/poli lain</option>
                                </select>
                            </label>
                            <div x-show="disposition === 'follow_up'" class="grid grid-cols-1 md:grid-cols-2 gap-3 rounded-2xl bg-blue-50/70 border border-blue-100 p-4">
                                <label class="flex flex-col gap-1.5 text-xs font-semibold text-slate-700">Tanggal kontrol
                                    <input type="date" name="follow_up_at" value="{{ old('follow_up_at') }}" min="{{ Carbon\Carbon::today()->toDateString() }}" class="rounded-xl border-slate-300 bg-white p-2.5 text-sm"/>
                                </label>
                                <label class="flex flex-col gap-1.5 text-xs font-semibold text-slate-700 md:col-span-2">Instruksi kontrol
                                    <textarea name="follow_up_instructions" rows="2" class="rounded-xl border-slate-300 bg-white p-2.5 text-sm" placeholder="Jadwal, kondisi yang perlu dipantau, atau instruksi tindak lanjut">{{ old('follow_up_instructions') }}</textarea>
                                </label>
                            </div>
                            <div x-show="disposition === 'referral'" class="grid grid-cols-1 md:grid-cols-2 gap-3 rounded-2xl bg-amber-50/70 border border-amber-100 p-4">
                                <label class="flex flex-col gap-1.5 text-xs font-semibold text-slate-700">Fasilitas tujuan
                                    <input type="text" name="referral_facility" value="{{ old('referral_facility') }}" maxlength="255" class="rounded-xl border-slate-300 bg-white p-2.5 text-sm" placeholder="Nama fasilitas rujukan"/>
                                </label>
                                <label class="flex flex-col gap-1.5 text-xs font-semibold text-slate-700">Poli/layanan tujuan
                                    <input type="text" name="referral_department" value="{{ old('referral_department') }}" maxlength="255" class="rounded-xl border-slate-300 bg-white p-2.5 text-sm" placeholder="Contoh: penyakit dalam"/>
                                </label>
                                <label class="flex flex-col gap-1.5 text-xs font-semibold text-slate-700 md:col-span-2">Alasan rujukan
                                    <textarea name="referral_reason" rows="3" maxlength="2000" class="rounded-xl border-slate-300 bg-white p-2.5 text-sm" placeholder="Alasan dan kebutuhan evaluasi lanjutan">{{ old('referral_reason') }}</textarea>
                                </label>
                                <p class="md:col-span-2 text-[11px] text-amber-800">Surat rujukan klinik dibuat bersama finalisasi catatan; pengiriman ke fasilitas tujuan tidak dilakukan otomatis.</p>
                            </div>
                        </div>
                        <div class="rounded-[24px] bg-white p-5 border border-slate-100 shadow-card flex flex-col gap-4">
                            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                                <span class="material-symbols-outlined text-primary text-[20px]">assignment_turned_in</span>
                                <h3 class="font-heading font-extrabold text-sm text-slate-900">Edukasi, Rencana Pemulihan & Administrasi ASN</h3>
                            </div>

                            <!-- Electronic Sick Leave (e-SKS) Box -->
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col gap-3">
                                <label class="flex items-start gap-3 cursor-pointer select-none">
                                    <input type="checkbox" name="terbitkan_surat_sakit" value="1" x-model="terbitkanSuratSakit" class="mt-1 w-5 h-5 rounded text-primary accent-primary"/>
                                    <div class="flex flex-col">
                                        <span class="font-heading font-bold text-xs text-slate-900">Terbitkan Surat Keterangan Istirahat Sakit Elektronik (e-SKS)</span>
                                        <span class="text-xs text-slate-500">Membuat catatan surat sakit internal klinik. Dokumen ini belum ditandatangani secara elektronik tersertifikasi.</span>
                                    </div>
                                </label>

                                <div x-show="terbitkanSuratSakit" class="pl-8 flex flex-col gap-3 pt-2">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="text-xs font-bold text-slate-700 block mb-1">Lama Hari Istirahat:</label>
                                            <input type="number" min="1" max="14" name="lama_hari_sakit" x-model="lamaHari" class="w-full bg-white p-2 rounded-xl border border-slate-200 text-xs font-bold text-primary"/>
                                        </div>
                                        <div>
                                            <label class="text-xs font-bold text-slate-700 block mb-1">Mulai Tanggal:</label>
                                            <input type="date" name="tgl_mulai_sakit" x-model="tglMulai" class="w-full bg-white p-2 rounded-xl border border-slate-200 text-xs font-mono"/>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs text-slate-700 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200 font-semibold">
                                        <span class="material-symbols-outlined text-[16px]">event</span>
                                        <span>Estimasi tanggal akhir: <span x-text="calcTglSelesai()"></span></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Advice Input -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-slate-700">Edukasi Khusus Kepada Pasien:</label>
                                <p class="text-xs text-slate-600">Rencana tindak lanjut diisi pada bagian Plan di tab SOAP.</p>
                            </div>

                            <!-- List of Issued Letters for this patient -->
                            @if ($patient->letters->count() > 0)
                                <div class="border-t border-slate-100 pt-3">
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Surat Medis Terbit Pasien Ini:</span>
                                    <div class="flex flex-col gap-2">
                                        @foreach ($patient->letters as $let)
                                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
                                                <div>
                                                    <span class="font-bold text-slate-800 block">{{ $let->jenis_label }}</span>
                                                    <span class="text-[11px] text-slate-500 font-mono">No: {{ $let->nomor_surat }} • {{ $let->tanggal_surat->format('d/m/Y') }}</span>
                                                </div>
                                                <a href="{{ route('letter.print', $let->id) }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-primary font-bold shadow-xs hover:bg-emerald-50 flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-[14px]">print</span>
                                                    <span>Cetak Dokumen</span>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- FIXED BOTTOM ACTION BAR (Matching Stitch Ruang Periksa Footer) -->
                    <div class="fixed bottom-0 left-0 lg:left-72 right-0 bg-white/95 backdrop-blur-xl border-t border-slate-200/90 py-3.5 px-6 lg:px-8 z-40 shadow-2xl flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-1.5 text-xs text-slate-700">
                                <span class="material-symbols-outlined text-slate-500 text-[18px]">edit_note</span>
                                <span>Perubahan tersimpan setelah Anda menekan tombol simpan.</span>
                            </div>
                            <div class="hidden md:flex items-center gap-1 text-xs text-slate-400">
                                <span>•</span>
                                <span>Integrasi eksternal belum dikonfigurasi.</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                                Kembali ke Antrean
                            </a>
                            @if ($queue->poli === 'gigi')
                            <button type="submit" formaction="{{ route('periksa.odontogram', $queue->id) }}" class="px-4 py-2.5 rounded-xl border border-slate-400 text-slate-700 text-xs font-bold">Simpan odontogram</button>
                            @endif
                            <button type="submit" formaction="{{ route('periksa.soap', $queue->id) }}" class="px-4 py-2.5 rounded-xl border border-primary text-primary text-xs font-bold">Simpan draf SOAP</button>
                            <!-- Finalize consultation -->
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold font-heading shadow-[0_4px_16px_rgba(0,92,85,0.3)] flex items-center gap-2 transition-all transform hover:-translate-y-0.5">
                                <span class="material-symbols-outlined text-[18px]">send_and_archive</span>
                                <span>Selesaikan pemeriksaan</span>
                            </button>
                        </div>
                    </div>

                </form>

            </div>

        </div>

    </div>
@endif
@endsection
