@extends('clinic.layouts.app')

@section('title', 'Berkas Rekam Medis: ' . $patient->nama_lengkap)

@section('content')
<div class="flex flex-col gap-6 pb-12" x-data="{ activeSection: 'timeline' }">
    
    <!-- Top Patient Header & Navigation -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('pasien.index') }}" class="p-2.5 rounded-2xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors shadow-xs">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </a>
            <div>
                <h1 class="text-2xl font-bold font-heading text-slate-900">{{ $patient->nama_lengkap }}</h1>
                <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mt-0.5">
                    <span class="font-bold text-primary">No. RM: {{ $patient->no_rm }}</span>
                    <span>•</span>
                    <span>{{ $patient->nip ? 'NIP: ' . $patient->nip : 'Masyarakat' }}</span>
                    <span>•</span>
                    <span>{{ $patient->usia !== null ? $patient->usia.' Tahun' : 'Usia belum dicatat' }} ({{ $patient->jenis_kelamin === 'L' ? 'Laki-laki' : ($patient->jenis_kelamin === 'P' ? 'Perempuan' : 'Belum diketahui') }})</span>
                    @if ($patient->identity_status === 'unidentified')
                        <span class="rounded-full bg-red-100 px-2 py-0.5 font-bold text-red-800">Identitas belum diketahui</span>
                    @elseif ($patient->identity_status === 'provisional')
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 font-bold text-amber-800">Identitas sementara</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @if ($currentUser->hasRole('pendaftaran'))
            <a href="{{ route('pasien.card', $patient->id) }}" target="_blank" class="flex items-center gap-1.5 px-4 py-2 rounded-2xl bg-white border border-slate-200 text-slate-700 text-xs font-bold font-heading hover:bg-slate-50 shadow-xs">
                <span class="material-symbols-outlined text-[16px]">badge</span>
                <span>Cetak Kartu Berobat</span>
            </a>
            @endif
        </div>
    </div>

    <!-- PATIENT COMPREHENSIVE SUMMARY CARD -->
    <div class="bg-white p-6 rounded-[28px] border border-slate-100 shadow-card flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="flex items-center gap-4 min-w-0">
            <div class="relative w-20 h-20 rounded-3xl overflow-hidden bg-slate-100 flex-shrink-0 shadow-sm">
                <img src="{{ $patient->avatar_url ?? '/assets/images/166de6af4e856812b08f19dc83cc9152.webp' }}" class="w-full h-full object-cover" alt="{{ $patient->nama_lengkap }}"/>
                <span class="absolute bottom-1 right-1 w-3.5 h-3.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
            </div>
            <div class="flex flex-col min-w-0 gap-1">
                <div class="flex items-center gap-2">
                    <h2 class="font-heading font-black text-lg text-slate-900 truncate">{{ $patient->nama_lengkap }}</h2>
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 text-[10px] font-bold border border-emerald-100">
                        {{ $patient->jenis_pasien ?? 'Umum' }}
                    </span>
                </div>
                <div class="text-xs text-slate-600 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px] text-primary">apartment</span>
                    <span class="truncate">{{ $patient->unit_kerja ?? 'Unit kerja belum dicatat' }}</span>
                </div>
                <div class="text-xs text-slate-500">
                            Alamat: {{ $patient->alamat ?? 'Belum dicatat' }} • Telepon: {{ $patient->no_hp ?? 'Belum dicatat' }}
                </div>
            </div>
        </div>

        <!-- Allergy Alert if present -->
        @if ($patient->riwayat_alergi && !str_contains(strtolower($patient->riwayat_alergi), 'tidak ada'))
            <div class="p-3.5 rounded-2xl bg-red-50 border border-red-200 text-xs text-red-900 max-w-sm">
                <div class="flex items-center gap-1.5 font-bold uppercase tracking-wider text-red-700 text-[11px] mb-1">
                    <span class="material-symbols-outlined text-[16px]">warning</span>
                    <span>Peringatan Alergi Pasien</span>
                </div>
                <p class="font-semibold">{{ $patient->riwayat_alergi }}</p>
                <p class="text-[11px] text-red-700 mt-0.5">{{ $patient->alergi_keterangan }}</p>
            </div>
        @endif
    </div>

    <!-- TABS NAVIGATION: Riwayat Kunjungan / Berkas Luar / Surat Medis / Odontogram -->
    <div class="flex items-center gap-2 p-1.5 bg-white rounded-2xl border border-slate-100 shadow-card font-heading text-xs font-bold overflow-x-auto">
        <button type="button" @click="activeSection = 'timeline'" :class="activeSection === 'timeline' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50'" class="px-4 py-2 rounded-xl transition-all flex items-center gap-1.5 whitespace-nowrap">
            <span class="material-symbols-outlined text-[18px]">timeline</span>
            <span>Riwayat Kunjungan & SOAP ({{ $patient->medicalRecords->count() }})</span>
        </button>
        <button type="button" @click="activeSection = 'attachments'" :class="activeSection === 'attachments' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50'" class="px-4 py-2 rounded-xl transition-all flex items-center gap-1.5 whitespace-nowrap">
            <span class="material-symbols-outlined text-[18px]">attachment</span>
            <span>Berkas Medis Eksternal ({{ $patient->attachments->count() }})</span>
        </button>
        <button type="button" @click="activeSection = 'letters'" :class="activeSection === 'letters' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50'" class="px-4 py-2 rounded-xl transition-all flex items-center gap-1.5 whitespace-nowrap">
            <span class="material-symbols-outlined text-[18px]">assignment_turned_in</span>
            <span>Surat Keterangan Medis ({{ $patient->letters->count() }})</span>
        </button>
        @if ($patient->odontograms->count() > 0)
            <button type="button" @click="activeSection = 'odontogram'" :class="activeSection === 'odontogram' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50'" class="px-4 py-2 rounded-xl transition-all flex items-center gap-1.5 whitespace-nowrap">
                <span class="material-symbols-outlined text-[18px]">dentistry</span>
                <span>Peta Odontogram Gigi</span>
            </button>
        @endif
    </div>

    <!-- SECTION 1: TIMELINE OF PAST VISITS & SOAP -->
    <div x-show="activeSection === 'timeline'" class="flex flex-col gap-4">
        @forelse ($patient->medicalRecords as $rec)
            <div class="bg-white p-6 rounded-[28px] border border-slate-100 shadow-card flex flex-col gap-4">
                <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-heading font-black text-sm text-slate-900">{{ $rec->created_at->translatedFormat('l, d F Y - H:i') }} WIB</span>
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[10px] font-bold uppercase">
                                {{ $rec->queue?->poli === 'gigi' ? 'Poli Gigi' : 'Poli Umum' }}
                            </span>
                        </div>
                        <span class="text-xs text-slate-500">Dokter Pemeriksa: <strong>{{ $rec->doctor->name ?? 'Dokter' }}</strong></span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 rounded-xl bg-primary text-white font-mono font-bold text-xs">
                            {{ $rec->icd10_primary_code }}
                        </span>
                    </div>
                </div>

                <!-- SOAP Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col gap-1">
                        <span class="font-bold text-primary uppercase text-[10px]">Subjective (S):</span>
                        <p class="text-slate-700 whitespace-pre-line">{{ $rec->subjective }}</p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col gap-1">
                        <span class="font-bold text-primary uppercase text-[10px]">Objective (O):</span>
                        <p class="text-slate-700 whitespace-pre-line">{{ $rec->objective }}</p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col gap-1">
                        <span class="font-bold text-primary uppercase text-[10px]">Assessment (A):</span>
                        <p class="text-slate-700 font-semibold">{{ $rec->assessment }}</p>
                        <span class="text-slate-500 font-mono text-[11px]">{{ $rec->icd10_primary_name }}</span>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col gap-1">
                        <span class="font-bold text-primary uppercase text-[10px]">Plan & Terapi (P):</span>
                        <p class="text-slate-700 whitespace-pre-line">{{ $rec->plan }}</p>
                    </div>
                </div>

                <!-- Prescribed Drugs -->
                @if ($rec->prescription && $rec->prescription->items->count() > 0)
                    <div class="border-t border-slate-100 pt-3">
                        <span class="text-[11px] font-bold uppercase text-slate-400 block mb-2">Resep Obat yang Diberikan:</span>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-xs">
                            @foreach ($rec->prescription->items as $item)
                                <div class="p-2.5 rounded-xl bg-emerald-50/60 border border-emerald-100 text-slate-800">
                                    <div class="font-bold text-emerald-950">{{ $item->medicine->nama_obat ?? 'Obat' }} ({{ $item->jumlah }} Unit)</div>
                                    <div class="text-[11px] text-slate-600 mt-0.5">{{ $item->aturan_pakai }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="border-t border-slate-100 pt-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Penanggung jawab finalisasi</p>
                            <p class="text-xs text-slate-700 mt-1">
                                {{ $rec->signed_by_name ?: 'Metadata finalisasi tidak tersedia pada catatan lama' }}
                                @if ($rec->signed_at)
                                    <span>·</span> {{ $rec->signed_at->timezone(config('app.timezone'))->format('d M Y, H:i') }}
                                @endif
                            </p>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Metadata pelaku dan waktu aplikasi; tidak menyatakan penggunaan tanda tangan elektronik tersertifikasi.</p>
                        @can('revise', $rec)
                            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-emerald-800">Anda penulis catatan ini</span>
                        @endcan
                    </div>

                    @if ($rec->revisions->isNotEmpty())
                        <div class="mt-3 flex flex-col gap-2">
                            <h4 class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Riwayat versi append-only</h4>
                            @foreach ($rec->revisions as $revision)
                                <details class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                                    <summary class="cursor-pointer flex flex-wrap items-center justify-between gap-2 text-xs font-semibold text-slate-700">
                                        <span>Versi {{ $revision->version_number }} · {{ match ($revision->revision_type) { 'original' => 'Catatan asli', 'correction' => 'Koreksi', 'addendum' => 'Addendum', default => $revision->revision_type } }}</span>
                                        <span class="text-[10px] text-slate-500">{{ $revision->recorded_at?->timezone(config('app.timezone'))->format('d M Y, H:i') }} · {{ $revision->recorded_by_name }} ({{ match ($revision->recorded_by_role) { 'dokter' => 'Dokter umum', 'dokter_gigi' => 'Dokter gigi', 'system' => 'Sistem', default => $revision->recorded_by_role } }})</span>
                                    </summary>
                                    <p class="text-[11px] text-slate-600 mt-2">Alasan: {{ $revision->reason }}</p>
                                    @if ($revision->origin === 'activation_snapshot')
                                        <p class="text-[10px] text-amber-700 mt-1">Snapshot saat fitur versioning diaktifkan; waktu ini bukan waktu perubahan catatan historis.</p>
                                    @endif
                                    @if ($revision->revision_type === 'addendum' && $revision->addendum_text)
                                        <div class="mt-2 rounded-lg bg-white border border-slate-200 p-3 text-xs text-slate-700 whitespace-pre-line">{{ $revision->addendum_text }}</div>
                                    @else
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2 text-[11px]">
                                            <div><span class="font-bold">S:</span> {{ $revision->snapshot_json['subjective'] ?? '—' }}</div>
                                            <div><span class="font-bold">O:</span> {{ $revision->snapshot_json['objective'] ?? '—' }}</div>
                                            <div><span class="font-bold">A:</span> {{ $revision->snapshot_json['assessment'] ?? '—' }}</div>
                                            <div><span class="font-bold">P:</span> {{ $revision->snapshot_json['plan'] ?? '—' }}</div>
                                            <div><span class="font-bold">Diagnosis:</span> {{ $revision->snapshot_json['icd10_primary_code'] ?? '—' }} {{ $revision->snapshot_json['icd10_primary_name'] ?? '' }}</div>
                                        </div>
                                    @endif
                                </details>
                            @endforeach
                        </div>
                    @endif

                    @can('revise', $rec)
                        @if ($rec->downtimeReconciliations->isEmpty())
                            <details @if ($errors->has('paper_reference') || $errors->has('service_occurred_at')) open @endif class="mt-4 rounded-2xl border border-indigo-200 bg-indigo-50/50 p-4">
                                <summary class="cursor-pointer text-xs font-extrabold text-indigo-950">Catat formulir pelayanan saat RME downtime</summary>
                                <p class="mt-2 text-[11px] leading-relaxed text-indigo-900">Gunakan untuk menghubungkan formulir kertas bernomor ke catatan final yang dibuat setelah sistem pulih. Masukkan waktu pelayanan pada formulir. Waktu finalisasi di aplikasi dan isi klinis tidak akan diubah.</p>
                                <form action="{{ route('downtime-reconciliations.store', [$patient, $rec]) }}" method="POST" class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                                    @csrf
                                    <label class="text-[11px] font-semibold text-slate-700">Nomor formulir downtime
                                        <input name="paper_reference" required maxlength="120" value="{{ old('paper_reference') }}" class="mt-1 w-full rounded-lg border-slate-300 text-xs" placeholder="Contoh: DT-2026-00042"/>
                                        @error('paper_reference')<span class="mt-1 block text-red-700">{{ $message }}</span>@enderror
                                    </label>
                                    <label class="text-[11px] font-semibold text-slate-700">Waktu pelayanan pada formulir
                                        <input type="datetime-local" name="service_occurred_at" required value="{{ old('service_occurred_at') }}" max="{{ now()->format('Y-m-d\TH:i') }}" class="mt-1 w-full rounded-lg border-slate-300 text-xs"/>
                                        @error('service_occurred_at')<span class="mt-1 block text-red-700">{{ $message }}</span>@enderror
                                    </label>
                                    <button type="submit" class="self-start rounded-xl bg-indigo-700 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-800 md:col-span-2">Simpan metadata dan kirim ke antrean PMIK</button>
                                </form>
                            </details>
                        @else
                            <div class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50/70 p-3 text-[11px] text-indigo-950">
                                <p class="font-bold">Metadata downtime tercatat</p>
                                @foreach ($rec->downtimeReconciliations as $downtimeReconciliation)
                                    <p class="mt-1">Formulir {{ $downtimeReconciliation->paper_reference }} · pelayanan {{ $downtimeReconciliation->service_occurred_at?->timezone(config('app.timezone'))->format('d M Y, H:i') }} · status {{ str($downtimeReconciliation->status)->replace('_', ' ')->title() }}</p>
                                @endforeach
                            </div>
                        @endif

                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-3 mt-4">
                            <details @if (old('revision_type') === 'correction') open @endif class="rounded-2xl border border-amber-200 bg-amber-50/50 p-4">
                                <summary class="cursor-pointer text-xs font-extrabold text-amber-900">Koreksi catatan (membuat versi pengganti)</summary>
                                <form action="{{ route('pasien.medical-record.revision.store', [$patient, $rec]) }}" method="POST" class="mt-3 flex flex-col gap-2">
                                    @csrf
                                    <input type="hidden" name="revision_type" value="correction"/>
                                    <label class="text-[11px] font-semibold text-slate-700">Alasan koreksi wajib
                                        <textarea name="reason" required maxlength="2000" rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-xs" placeholder="Jelaskan apa yang perlu dikoreksi dan alasannya">{{ old('revision_type') === 'correction' ? old('reason') : '' }}</textarea>
                                    </label>
                                    <label class="text-[11px] font-semibold text-slate-700">Subjective (S)
                                        <textarea name="subjective" required maxlength="10000" rows="3" class="mt-1 w-full rounded-lg border-slate-300 text-xs">{{ old('revision_type') === 'correction' ? old('subjective', $rec->subjective) : $rec->subjective }}</textarea>
                                    </label>
                                    <label class="text-[11px] font-semibold text-slate-700">Objective (O)
                                        <textarea name="objective" required maxlength="10000" rows="3" class="mt-1 w-full rounded-lg border-slate-300 text-xs">{{ old('revision_type') === 'correction' ? old('objective', $rec->objective) : $rec->objective }}</textarea>
                                    </label>
                                    <label class="text-[11px] font-semibold text-slate-700">Assessment (A)
                                        <textarea name="assessment" required maxlength="10000" rows="3" class="mt-1 w-full rounded-lg border-slate-300 text-xs">{{ old('revision_type') === 'correction' ? old('assessment', $rec->assessment) : $rec->assessment }}</textarea>
                                    </label>
                                    <label class="text-[11px] font-semibold text-slate-700">Plan (P)
                                        <textarea name="plan" required maxlength="10000" rows="3" class="mt-1 w-full rounded-lg border-slate-300 text-xs">{{ old('revision_type') === 'correction' ? old('plan', $rec->plan) : $rec->plan }}</textarea>
                                    </label>
                                    <label class="text-[11px] font-semibold text-slate-700">Kode diagnosis ICD-10
                                        <input name="icd10_primary_code" required maxlength="20" value="{{ old('revision_type') === 'correction' ? old('icd10_primary_code', $rec->icd10_primary_code) : $rec->icd10_primary_code }}" class="mt-1 w-full rounded-lg border-slate-300 text-xs" placeholder="Masukkan kode ICD-10 utama"/>
                                    </label>
                                    <label class="text-[11px] font-semibold text-slate-700">Tindakan medis
                                        <textarea name="tindakan_medis" maxlength="4000" rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-xs">{{ old('revision_type') === 'correction' ? old('tindakan_medis', $rec->tindakan_medis) : $rec->tindakan_medis }}</textarea>
                                    </label>
                                    <button type="submit" class="self-start rounded-xl bg-amber-700 px-4 py-2 text-xs font-bold text-white hover:bg-amber-800">Simpan koreksi sebagai versi baru</button>
                                </form>
                            </details>

                            <details @if (old('revision_type') === 'addendum') open @endif class="rounded-2xl border border-blue-200 bg-blue-50/50 p-4">
                                <summary class="cursor-pointer text-xs font-extrabold text-blue-900">Tambah addendum (tanpa mengubah SOAP)</summary>
                                <form action="{{ route('pasien.medical-record.revision.store', [$patient, $rec]) }}" method="POST" class="mt-3 flex flex-col gap-2">
                                    @csrf
                                    <input type="hidden" name="revision_type" value="addendum"/>
                                    <label class="text-[11px] font-semibold text-slate-700">Alasan addendum wajib
                                        <textarea name="reason" required maxlength="2000" rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-xs" placeholder="Mengapa informasi tambahan perlu dicatat?">{{ old('revision_type') === 'addendum' ? old('reason') : '' }}</textarea>
                                    </label>
                                    <label class="text-[11px] font-semibold text-slate-700">Informasi tambahan
                                        <textarea name="addendum_text" required maxlength="10000" rows="7" class="mt-1 w-full rounded-lg border-slate-300 text-xs" placeholder="Catatan tambahan terkait kunjungan ini">{{ old('revision_type') === 'addendum' ? old('addendum_text') : '' }}</textarea>
                                    </label>
                                    <button type="submit" class="self-start rounded-xl bg-blue-700 px-4 py-2 text-xs font-bold text-white hover:bg-blue-800">Tambahkan addendum</button>
                                </form>
                            </details>
                        </div>
                    @endcan
                </div>
            </div>
        @empty
            <div class="bg-white p-12 rounded-[28px] border border-slate-100 text-center text-slate-400">
                Belum ada rekam medis sebelumnya untuk pasien ini.
            </div>
        @endforelse
    </div>

    <!-- SECTION 2: EXTERNAL MEDICAL ATTACHMENTS -->
    <div x-show="activeSection === 'attachments'" class="flex flex-col gap-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse ($patient->attachments as $att)
                @php $kat = $att->kategori_badge; @endphp
                <div class="bg-white p-5 rounded-[24px] border border-slate-100 shadow-card flex flex-col justify-between gap-3">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $kat['color'] }} border flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">{{ $kat['icon'] }}</span>
                                {{ $kat['label'] }}
                            </span>
                            <span class="text-[11px] font-mono text-slate-400">{{ $att->created_at->format('d/m/Y') }}</span>
                        </div>
                        <h4 class="font-heading font-bold text-sm text-slate-900">{{ $att->judul_dokumen }}</h4>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $att->instansi_penerbit }}</p>
                        <p class="text-xs text-slate-600 mt-2 bg-slate-50 p-2.5 rounded-xl border border-slate-100 italic">
                            "{{ $att->catatan_interpretasi ?? 'Tidak ada catatan interpretasi.' }}"
                        </p>
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-50 pt-3 text-xs">
                        <span class="text-slate-400 font-mono">{{ strtoupper($att->file_type) }} • {{ $att->file_size_kb }} KB</span>
                        <a href="{{ route('pasien.attachment.download', [$patient->id, $att->id]) }}" class="px-4 py-1.5 rounded-xl bg-primary text-white font-bold hover:bg-primary-hover shadow-xs flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                            <span>Buka Berkas</span>
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-2 bg-white p-12 rounded-[28px] border border-slate-100 text-center text-slate-400">
                    Belum ada berkas laboratorium atau radiologi luar yang diunggah.
                </div>
            @endforelse
        </div>
    </div>

    <!-- SECTION 3: MEDICAL LETTERS (SURAT-MENYURAT) -->
    <div x-show="activeSection === 'letters'" class="flex flex-col gap-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse ($patient->letters as $let)
                <div class="bg-white p-5 rounded-[24px] border border-slate-100 shadow-card flex flex-col justify-between gap-3">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 text-[10px] font-bold border border-emerald-100">
                                {{ $let->jenis_label }}
                            </span>
                            <span class="text-[11px] font-mono text-slate-400">{{ $let->tanggal_surat->format('d M Y') }}</span>
                        </div>
                        <h4 class="font-heading font-black text-sm text-slate-900">{{ $let->nomor_surat }}</h4>
                        <p class="text-xs text-slate-600 mt-1">
                            {{ $let->catatan_tambahan ?? $let->keperluan }}
                        </p>
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-50 pt-3 text-xs">
                        <span class="text-slate-500">Dokter: {{ $let->doctor->name ?? 'Dokter' }}</span>
                        @can('print', $let)
                        <a href="{{ route('letter.print', $let->id) }}" target="_blank" class="px-4 py-1.5 rounded-xl bg-slate-900 text-white font-bold hover:bg-slate-800 shadow-xs flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">print</span>
                            <span>Cetak Surat</span>
                        </a>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="col-span-2 bg-white p-12 rounded-[28px] border border-slate-100 text-center text-slate-400">
                    Belum ada surat keterangan medis yang diterbitkan untuk pasien ini.
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
