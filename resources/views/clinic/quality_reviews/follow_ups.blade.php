@extends('clinic.layouts.app')

@section('content')
<div class="mx-auto max-w-6xl space-y-6 p-5 lg:p-8">
    <header>
        <p class="text-sm font-semibold uppercase tracking-wide text-teal-700">Meja kerja mutu</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-900">Tindak lanjut temuan</h1>
        <p class="mt-2 max-w-3xl text-sm text-slate-600">Dokter penulis catatan menindaklanjuti setiap temuan. Penutupan “Selesai setelah koreksi” memerlukan versi koreksi/addendum baru; “Tidak perlu koreksi” memerlukan alasan.</p>
    </header>

    @if (session('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('quality-review-follow-ups.index') }}" class="flex flex-col gap-3 sm:flex-row">
            <label class="sr-only" for="filter">Status tindak lanjut</label>
            <select id="filter" name="filter" class="w-full rounded-xl border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600 sm:max-w-xs">
                <option value="open" @selected($filter === 'open')>Belum dikerjakan</option>
                <option value="in_progress" @selected($filter === 'in_progress')>Sedang dikerjakan</option>
                <option value="resolved" @selected($filter === 'resolved')>Selesai setelah koreksi</option>
                <option value="not_applicable" @selected($filter === 'not_applicable')>Tidak perlu koreksi</option>
                <option value="all" @selected($filter === 'all')>Semua status</option>
            </select>
            <button class="rounded-xl bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-800">Terapkan</button>
        </form>
    </section>

    <div class="space-y-4">
        @forelse ($tasks as $task)
            @php
                $record = $task->qualityReview->medicalRecord;
                $latestVersion = (int) $record->revisions->max('version_number');
                $status = $task->status;
                $isOpen = in_array($status, [\App\Enums\MedicalRecordQualityReviewFollowUpStatus::Open, \App\Enums\MedicalRecordQualityReviewFollowUpStatus::InProgress], true);
                $isOverdue = $isOpen && $task->due_at->lt(today());
            @endphp
            <article class="rounded-2xl border {{ $isOverdue ? 'border-amber-300' : 'border-slate-200' }} bg-white p-5 shadow-sm">
                <div class="flex flex-col justify-between gap-4 md:flex-row">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $status === \App\Enums\MedicalRecordQualityReviewFollowUpStatus::Resolved ? 'bg-emerald-100 text-emerald-800' : ($status === \App\Enums\MedicalRecordQualityReviewFollowUpStatus::NotApplicable ? 'bg-slate-100 text-slate-700' : ($status === \App\Enums\MedicalRecordQualityReviewFollowUpStatus::InProgress ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800')) }}">{{ $status->label() }}</span>
                            @if ($isOverdue)<span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-800">Lewat tenggat</span>@endif
                            <span class="text-xs text-slate-500">Tenggat {{ $task->due_at->format('d M Y') }}</span>
                        </div>
                        <h2 class="mt-3 text-lg font-bold text-slate-900">{{ $record->patient?->nama_lengkap ?? 'Identitas belum tersedia' }} · {{ $record->queue?->poli === 'gigi' ? 'Poli Gigi' : 'Poli Umum' }}</h2>
                        <p class="mt-1 text-xs text-slate-500">RM {{ $record->patient?->no_rm ?? '—' }} · Kunjungan {{ $record->queue?->tanggal_antrean?->format('d M Y') ?? 'tanggal tidak tersedia' }} · Catatan v{{ $task->qualityReview->reviewed_record_version }}</p>
                        <p class="mt-4 text-sm font-semibold text-slate-800">{{ $task->item_label }}</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $task->finding }}</p>
                        <p class="mt-3 text-xs text-slate-500">Ditugaskan kepada {{ $task->assignee?->name ?? 'pengguna tidak tersedia' }} · Reviewer {{ $task->qualityReview->reviewed_by_name }}</p>

                        @if ($currentUser->hasRole('perekam_medis'))
                            <a href="{{ route('quality-reviews.show', $record) }}" class="mt-3 inline-flex text-sm font-semibold text-teal-800 hover:underline">Lihat riwayat review</a>
                        @else
                            <a href="{{ route('pasien.show', $record->patient) }}" class="mt-3 inline-flex text-sm font-semibold text-teal-800 hover:underline">Buka rekam medis untuk koreksi</a>
                        @endif
                    </div>

                    @if ($currentUser->id === $task->assigned_to_user_id && $isOpen)
                        <form method="POST" action="{{ route('quality-review-follow-ups.update', $task) }}" class="w-full space-y-3 rounded-xl bg-slate-50 p-4 md:max-w-sm">
                            @csrf
                            <label for="status_{{ $task->id }}" class="block text-xs font-semibold text-slate-700">Perbarui status</label>
                            <select id="status_{{ $task->id }}" name="status" required class="w-full rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                                @if ($status === \App\Enums\MedicalRecordQualityReviewFollowUpStatus::Open)
                                    <option value="in_progress">Sedang dikerjakan</option>
                                @endif
                                @if ($latestVersion > $task->qualityReview->reviewed_record_version)
                                    <option value="resolved">Selesai setelah koreksi</option>
                                @endif
                                <option value="not_applicable">Tidak perlu koreksi</option>
                            </select>
                            @if ($latestVersion <= $task->qualityReview->reviewed_record_version)
                                <p class="text-xs text-slate-500">Untuk menutup sebagai selesai, simpan koreksi/addendum klinis melalui halaman rekam medis terlebih dahulu.</p>
                            @endif
                            <label for="note_{{ $task->id }}" class="block text-xs font-semibold text-slate-700">Catatan perubahan / alasan</label>
                            <textarea id="note_{{ $task->id }}" name="note" rows="3" maxlength="5000" required class="w-full rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600" placeholder="Ringkas tindakan koreksi atau alasan tidak perlu koreksi.">{{ old('note') }}</textarea>
                            <button class="w-full rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-800">Simpan status dan jejak</button>
                        </form>
                    @endif
                </div>

                <details class="mt-5 border-t border-slate-100 pt-4">
                    <summary class="cursor-pointer text-sm font-semibold text-slate-700">Riwayat tindak lanjut ({{ ($events[$task->id] ?? collect())->count() }})</summary>
                    <ol class="mt-3 space-y-3">
                        @foreach (($events[$task->id] ?? collect()) as $event)
                            <li class="border-l-2 border-teal-200 pl-3">
                                <p class="text-xs font-semibold text-slate-700">{{ $event->event_type === 'created' ? 'Tugas dibuat' : 'Status: '.(\App\Enums\MedicalRecordQualityReviewFollowUpStatus::tryFrom($event->to_status)?->label() ?? $event->to_status) }}</p>
                                <p class="mt-1 whitespace-pre-line text-xs text-slate-600">{{ $event->event_note }}</p>
                                <p class="mt-1 text-[11px] text-slate-500">{{ $event->actor_name }} · {{ $event->recorded_at }}</p>
                            </li>
                        @endforeach
                    </ol>
                </details>
                @if ($status->isResolved())
                    <div class="mt-4 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-900">
                        {{ $status->label() }} oleh {{ $task->resolvedBy?->name ?? 'dokter' }}: {{ $task->resolution_note }}
                        @if ($task->resolved_record_version)<span class="block mt-1 text-xs">Ditautkan ke catatan v{{ $task->resolved_record_version }}.</span>@endif
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-14 text-center text-sm text-slate-500">Tidak ada tindak lanjut pada filter ini.</div>
        @endforelse
    </div>
    <div>{{ $tasks->links() }}</div>
</div>
@endsection
