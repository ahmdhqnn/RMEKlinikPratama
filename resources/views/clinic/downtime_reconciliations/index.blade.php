@extends('clinic.layouts.app')

@section('content')
<div class="mx-auto flex w-full max-w-7xl flex-col gap-5 px-4 py-6 lg:px-8">
    <header class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-indigo-700">Meja kerja PMIK</p>
        <h1 class="mt-1 text-2xl font-extrabold text-slate-950">Rekonsiliasi formulir downtime</h1>
        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-600">Cocokkan metadata formulir kertas dengan catatan final yang dibuat setelah RME pulih. Peninjauan mencatat status dan alasan; proses ini tidak mengubah waktu finalisasi sistem ataupun isi klinis.</p>
    </header>

    @if (session('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-bold">Periksa kembali hasil review.</p>
            <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <nav aria-label="Filter status rekonsiliasi" class="flex flex-wrap gap-2">
        @foreach (['pending_review' => 'Menunggu review', 'discrepancy' => 'Ada perbedaan', 'reconciled' => 'Selesai', 'all' => 'Semua'] as $value => $label)
            <a href="{{ route('downtime-reconciliations.index', ['filter' => $value]) }}" @class([
                'rounded-full border px-4 py-2 text-xs font-bold transition',
                'border-indigo-700 bg-indigo-700 text-white' => $filter === $value,
                'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' => $filter !== $value,
            ])>{{ $label }}</a>
        @endforeach
    </nav>

    <section class="flex flex-col gap-4">
        @forelse ($records as $record)
            @php($timeline = $events->get($record->id, collect()))
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $record->medicalRecord->queue?->poli === 'gigi' ? 'Poli Gigi' : 'Poli Umum' }} · RM {{ $record->medicalRecord->patient?->no_rm }}</p>
                        <h2 class="mt-1 text-lg font-extrabold text-slate-950">{{ $record->medicalRecord->patient?->nama_lengkap ?? 'Pasien tidak tersedia' }}</h2>
                        <p class="mt-1 text-xs text-slate-600">Dokter penulis: {{ $record->enteredBy?->name ?? 'Tidak tersedia' }} · Formulir {{ $record->paper_reference }}</p>
                    </div>
                    @php($statusClass = match ($record->status) { 'pending_review' => 'border-amber-200 bg-amber-50 text-amber-900', 'discrepancy' => 'border-red-200 bg-red-50 text-red-900', default => 'border-emerald-200 bg-emerald-50 text-emerald-900' })
                    <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $statusClass }}">{{ match ($record->status) { 'pending_review' => 'Menunggu review', 'discrepancy' => 'Ada perbedaan', default => 'Terekonsiliasi' } }}</span>
                </div>

                <dl class="mt-4 grid grid-cols-1 gap-3 rounded-xl bg-slate-50 p-4 text-xs sm:grid-cols-2 lg:grid-cols-3">
                    <div><dt class="font-bold text-slate-500">Waktu pelayanan pada formulir</dt><dd class="mt-1 text-slate-900">{{ $record->service_occurred_at->timezone(config('app.timezone'))->format('d M Y, H:i') }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Finalisasi di aplikasi</dt><dd class="mt-1 text-slate-900">{{ $record->medicalRecord->signed_at?->timezone(config('app.timezone'))->format('d M Y, H:i') ?? 'Metadata waktu tidak tersedia' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Metadata dimasukkan</dt><dd class="mt-1 text-slate-900">{{ $record->created_at?->timezone(config('app.timezone'))->format('d M Y, H:i') }} · {{ $record->enteredBy?->name }}</dd></div>
                </dl>

                @if ($record->review_note)
                    <div class="mt-3 rounded-xl border border-slate-200 p-3 text-xs text-slate-700">
                        <p class="font-bold">Catatan review terakhir · {{ $record->reviewedBy?->name }} · {{ $record->reviewed_at?->timezone(config('app.timezone'))->format('d M Y, H:i') }}</p>
                        <p class="mt-1 whitespace-pre-line">{{ $record->review_note }}</p>
                    </div>
                @endif

                @if (in_array($record->status, ['pending_review', 'discrepancy'], true))
                    <form action="{{ route('downtime-reconciliations.update', $record) }}" method="POST" class="mt-4 grid grid-cols-1 gap-3 border-t border-slate-100 pt-4 md:grid-cols-[220px_1fr_auto] md:items-end">
                        @csrf
                        <label class="text-xs font-semibold text-slate-700">Hasil review
                            <select name="status" required class="mt-1 w-full rounded-lg border-slate-300 text-xs">
                                <option value="reconciled">Cocok / terekonsiliasi</option>
                                @if ($record->status === 'pending_review')<option value="discrepancy">Ada perbedaan</option>@endif
                            </select>
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Alasan dan hasil pencocokan wajib dicatat
                            <textarea name="note" required maxlength="2000" rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-xs" placeholder="Contoh: Nomor dan waktu formulir cocok dengan catatan final; tidak ada perubahan isi klinis."></textarea>
                        </label>
                        <button type="submit" class="rounded-xl bg-indigo-700 px-4 py-2.5 text-xs font-bold text-white hover:bg-indigo-800">Simpan review</button>
                    </form>
                @endif

                <details class="mt-4 border-t border-slate-100 pt-3">
                    <summary class="cursor-pointer text-xs font-bold text-slate-700">Riwayat perubahan status ({{ $timeline->count() }})</summary>
                    <ol class="mt-3 flex flex-col gap-2">
                        @foreach ($timeline as $event)
                            <li class="rounded-lg bg-slate-50 p-3 text-[11px] text-slate-700">
                                <p class="font-bold">{{ \Illuminate\Support\Carbon::parse($event->recorded_at)->timezone(config('app.timezone'))->format('d M Y, H:i') }} · {{ $event->actor_name }} ({{ $event->actor_role }})</p>
                                <p class="mt-1">{{ $event->event_note }}</p>
                            </li>
                        @endforeach
                    </ol>
                </details>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
                <p class="font-bold text-slate-800">Tidak ada rekonsiliasi pada filter ini</p>
                <p class="mt-1 text-sm text-slate-500">Metadata formulir yang dicatat dokter akan muncul di antrean review.</p>
            </div>
        @endforelse
    </section>

    <div>{{ $records->links() }}</div>
</div>
@endsection
