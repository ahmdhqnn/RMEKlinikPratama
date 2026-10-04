@extends('clinic.layouts.app')

@section('content')
<div class="mx-auto max-w-6xl space-y-6 p-5 lg:p-8">
    <a href="{{ route('quality-reviews.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-teal-800 hover:underline"><span aria-hidden="true">←</span> Kembali ke antrean review</a>

    @if (session('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">Periksa kembali checklist dan hasil review. {{ $errors->first() }}</div>
    @endif

    <header class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:p-7">
        <div class="flex flex-col justify-between gap-4 sm:flex-row">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-teal-700">Review mutu · versi v{{ $currentVersion }}</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $record->patient?->nama_lengkap ?? 'Identitas belum tersedia' }}</h1>
                <p class="mt-2 text-sm text-slate-600">RM {{ $record->patient?->no_rm ?? '—' }} · {{ $record->queue?->poli === 'gigi' ? 'Poli Gigi' : 'Poli Umum' }} · {{ $record->queue?->tanggal_antrean?->format('d M Y') ?? 'Tanggal kunjungan tidak tersedia' }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
                <div><span class="text-slate-500">Dokter:</span> {{ $record->doctor?->name ?? 'Tidak tercatat' }}</div>
                <div class="mt-1"><span class="text-slate-500">Finalisasi:</span> {{ $record->signed_by_name ?? 'Tidak tercatat' }} · {{ $record->signed_at?->format('d M Y H:i') ?? 'waktu tidak tercatat' }}</div>
                <div class="mt-1"><span class="text-slate-500">Identitas:</span> {{ $record->patient?->identity_status ?? 'belum ditentukan' }}</div>
            </div>
        </div>
        <p class="mt-5 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">Tugas ini menilai keberadaan dan keterbacaan dokumentasi. Jangan mengubah atau menyimpulkan isi klinis. Catat temuan agar dapat ditindaklanjuti oleh tenaga kesehatan yang berwenang.</p>
    </header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(300px,0.8fr)]">
        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Dokumentasi klinis</h2>
                <dl class="mt-4 space-y-4 text-sm">
                    @foreach ([['Subjektif', $record->subjective], ['Objektif', $record->objective], ['Asesmen', $record->assessment], ['Rencana', $record->plan]] as [$label, $value])
                        <div class="border-b border-slate-100 pb-3 last:border-0">
                            <dt class="font-semibold text-slate-700">{{ $label }}</dt>
                            <dd class="mt-1 whitespace-pre-line text-slate-600">{{ filled($value) ? $value : 'Belum diisi' }}</dd>
                        </div>
                    @endforeach
                    <div class="border-b border-slate-100 pb-3">
                        <dt class="font-semibold text-slate-700">Diagnosis utama</dt>
                        <dd class="mt-1 text-slate-600">{{ $record->icd10_primary_code ?: 'Kode belum diisi' }} · {{ $record->icd10_primary_name ?: 'Nama diagnosis belum diisi' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-700">Tindakan dan disposisi</dt>
                        <dd class="mt-1 whitespace-pre-line text-slate-600">{{ $record->tindakan_medis ?: 'Tindakan belum dicatat' }} · {{ $record->disposition ?: 'Disposisi belum dicatat' }}</dd>
                        @if ($record->follow_up_at || $record->follow_up_instructions)
                            <dd class="mt-1 text-slate-600">Tindak lanjut: {{ $record->follow_up_at?->format('d M Y') ?? 'tanggal tidak tercatat' }} {{ $record->follow_up_instructions }}</dd>
                        @endif
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Triage dan odontogram</h2>
                @php($vitals = $record->queue?->vitalSign)
                @if ($vitals)
                    <p class="mt-3 text-sm text-slate-700">Tanda vital dicatat oleh {{ $vitals->nurse?->name ?? 'petugas tidak tercatat' }}: TD {{ $vitals->sistolik ?? '—' }}/{{ $vitals->diastolik ?? '—' }} mmHg · Nadi {{ $vitals->nadi ?? '—' }} · Suhu {{ $vitals->suhu ?? '—' }} °C · SpO₂ {{ $vitals->spo2 ?? '—' }}% · BB {{ $vitals->berat_badan ?? '—' }} kg · TB {{ $vitals->tinggi_badan ?? '—' }} cm</p>
                    @if ($vitals->catatan_keperawatan)<p class="mt-2 whitespace-pre-line text-sm text-slate-600">Catatan: {{ $vitals->catatan_keperawatan }}</p>@endif
                @else
                    <p class="mt-3 text-sm text-slate-500">Tidak ada tanda vital terhubung pada kunjungan ini.</p>
                @endif
                @if ($record->queue?->poli === 'gigi')
                    @if ($record->odontogram)
                        <div class="mt-4 border-t border-slate-100 pt-4 text-sm text-slate-700">
                            <p class="font-semibold">Odontogram tercatat</p>
                            <p class="mt-1">Data elemen gigi: {{ is_array($record->odontogram->teeth_data_json) ? count($record->odontogram->teeth_data_json) : 0 }} · Pemeriksa: {{ $record->odontogram->dentist?->name ?? 'tidak tercatat' }}</p>
                            @if ($record->odontogram->catatan_khusus)<p class="mt-2 whitespace-pre-line text-slate-600">{{ $record->odontogram->catatan_khusus }}</p>@endif
                        </div>
                    @else
                        <p class="mt-4 border-t border-slate-100 pt-4 text-sm text-amber-800">Odontogram tidak ditemukan. Nilai checklist sesuai dokumentasi dan konteks kunjungan; jangan menganggap tindakan tidak dilakukan.</p>
                    @endif
                @endif
                <div class="mt-4 border-t border-slate-100 pt-4 text-sm text-slate-700">
                    <p class="font-semibold">Dokumen terkait</p>
                    @forelse ($record->letters as $letter)
                        <p class="mt-1">{{ $letter->jenis_label }}{{ $letter->jenis_surat === 'surat_rujukan' && $letter->tujuan_faskes_rujukan ? ' · '.$letter->tujuan_faskes_rujukan : '' }}</p>
                    @empty
                        <p class="mt-1 text-slate-500">Tidak ada surat terkait yang tercatat.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Checklist kelengkapan</h2>
                <p class="mt-1 text-xs text-slate-500">Checklist baseline untuk prototipe. Pilih satu hasil per item.</p>
                <form method="POST" action="{{ route('quality-reviews.store', $record) }}" class="mt-4 space-y-4">
                    @csrf
                    @foreach ($checklistItems as $key => $label)
                        <fieldset data-checklist-item="{{ $key }}" class="rounded-xl border border-slate-200 p-3">
                            <legend class="px-1 text-sm font-semibold text-slate-800">{{ $label }}</legend>
                            <div class="mt-2 flex gap-4 text-sm">
                                <label class="inline-flex items-center gap-2"><input type="radio" name="checklist[{{ $key }}]" value="pass" @checked(old('checklist.'.$key) === 'pass') class="text-teal-700 focus:ring-teal-600"> Lengkap</label>
                                <label class="inline-flex items-center gap-2"><input type="radio" name="checklist[{{ $key }}]" value="finding" @checked(old('checklist.'.$key) === 'finding') class="text-amber-600 focus:ring-amber-600"> Ada temuan</label>
                            </div>
                            <div data-finding-note="{{ $key }}" class="mt-3 {{ old('checklist.'.$key) === 'finding' ? '' : 'hidden' }}">
                                <label for="finding_{{ $key }}" class="mb-1 block text-xs font-semibold text-slate-700">Uraikan bagian yang harus ditindaklanjuti</label>
                                <textarea id="finding_{{ $key }}" name="finding_notes[{{ $key }}]" rows="3" maxlength="3000" @disabled(old('checklist.'.$key) !== 'finding') class="w-full rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600" placeholder="Catat temuan dokumentasi secara spesifik, tanpa menambahkan interpretasi klinis.">{{ old('finding_notes.'.$key) }}</textarea>
                            </div>
                        </fieldset>
                    @endforeach
                    <div>
                        <label for="review_result" class="mb-1 block text-sm font-semibold text-slate-800">Kesimpulan review</label>
                        <select id="review_result" name="review_result" required class="w-full rounded-xl border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                            <option value="">Pilih kesimpulan</option>
                            <option value="complete" @selected(old('review_result') === 'complete')>Lengkap</option>
                            <option value="needs_follow_up" @selected(old('review_result') === 'needs_follow_up')>Perlu tindak lanjut</option>
                        </select>
                    </div>
                    <div>
                        <label for="follow_up_due_at" class="mb-1 block text-sm font-semibold text-slate-800">Tenggat tindak lanjut</label>
                        <input id="follow_up_due_at" name="follow_up_due_at" type="date" min="{{ today()->toDateString() }}" value="{{ old('follow_up_due_at', $followUpDueDefault) }}" @disabled(old('review_result') !== 'needs_follow_up') class="w-full rounded-xl border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                        <p class="mt-1 text-xs text-slate-500">Temuan ditugaskan kepada {{ $record->doctor?->name ?? 'dokter penulis catatan' }} sesuai kewenangan koreksi rekam medis.</p>
                    </div>
                    <button class="w-full rounded-xl bg-teal-700 px-4 py-3 text-sm font-semibold text-white hover:bg-teal-800">Simpan review baru</button>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Riwayat review</h2>
                <ol class="mt-4 space-y-4">
                    @forelse ($record->qualityReviews->sortByDesc('review_round') as $review)
                        <li class="border-l-2 border-teal-200 pl-4">
                            <p class="text-sm font-semibold text-slate-800">Putaran {{ $review->review_round }} · {{ $review->review_result === 'complete' ? 'Lengkap' : 'Perlu tindak lanjut' }} · v{{ $review->reviewed_record_version }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $review->reviewed_by_name }} · {{ $review->reviewed_at?->format('d M Y H:i') }}</p>
                            @if ($review->findings)<p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $review->findings }}</p>@endif
                            @foreach ($review->followUps as $followUp)
                                <p class="mt-2 text-xs text-slate-600">{{ $followUp->item_label }} · {{ $followUp->status->label() }} · ditugaskan kepada {{ $followUp->assignee?->name ?? '—' }} · tenggat {{ $followUp->due_at->format('d M Y') }}</p>
                            @endforeach
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Belum ada review sebelumnya.</li>
                    @endforelse
                </ol>
            </section>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const resultField = document.getElementById('review_result');
        const dueDateField = document.getElementById('follow_up_due_at');
        const updateFindingFields = () => {
            document.querySelectorAll('[data-checklist-item]').forEach((item) => {
                const key = item.dataset.checklistItem;
                const findingChoice = item.querySelector(`input[name="checklist[${key}]"][value="finding"]`);
                const noteContainer = item.querySelector(`[data-finding-note="${key}"]`);
                const noteField = noteContainer.querySelector('textarea');
                const isFinding = findingChoice.checked;
                noteContainer.classList.toggle('hidden', !isFinding);
                noteField.disabled = !isFinding;
            });

            dueDateField.disabled = resultField.value !== 'needs_follow_up';
        };

        document.querySelectorAll('[data-checklist-item] input[type="radio"]').forEach((field) => {
            field.addEventListener('change', updateFindingFields);
        });
        resultField.addEventListener('change', updateFindingFields);
        updateFindingFields();
    });
</script>
@endsection
