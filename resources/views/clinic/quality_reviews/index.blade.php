@extends('clinic.layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 p-5 lg:p-8">
    <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-teal-700">Meja kerja PMIK</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Review kelengkapan rekam medis</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-600">Periksa kelengkapan dokumentasi pada catatan yang sudah final. Review ini tidak mengubah isi klinis dan bukan penilaian atas keputusan klinis.</p>
        </div>
    </header>

    <section aria-label="Ringkasan mutu" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ([
            ['Catatan final', $summary['final_records'], 'Seluruh rekam medis berstatus final'],
            ['Perlu review', $summary['pending_reviews'], 'Belum direview atau sudah ada versi baru'],
            ['Review lengkap', $summary['complete_reviews'], 'Checklist lulus pada versi terkini'],
            ['Perlu tindak lanjut', $summary['follow_up_reviews'], 'Review terkini mencatat temuan'],
            ['Tugas terbuka', $summary['open_follow_ups'], 'Belum selesai atau sedang dikerjakan'],
            ['Tugas terlambat', $summary['overdue_follow_ups'], 'Tenggat lewat, tugas masih terbuka'],
        ] as [$label, $value, $description])
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-semibold text-slate-600">{{ $label }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($value) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $description }}</p>
            </article>
        @endforeach
    </section>

    @php
        $maxMonthlyReviews = max(1, (int) $trends->max('total_reviews'));
    @endphp
    <section aria-labelledby="quality-trend-heading" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
            <div>
                <h2 id="quality-trend-heading" class="text-lg font-bold text-slate-900">Tren review 6 bulan</h2>
                <p class="mt-1 text-sm text-slate-600">Menghitung setiap putaran review berdasarkan bulan pencatatan. Persentase menunjukkan review yang mencatat temuan.</p>
            </div>
        </div>
        <div class="mt-5 space-y-4">
            @foreach ($trends as $trend)
                @php
                    $findingRate = $trend['total_reviews'] > 0 ? (int) round(($trend['follow_up_reviews'] / $trend['total_reviews']) * 100) : 0;
                @endphp
                <div class="grid gap-2 sm:grid-cols-[110px_1fr_150px] sm:items-center">
                    <p class="text-sm font-semibold text-slate-700">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $trend['month'])->locale('id')->translatedFormat('F Y') }}</p>
                    <div class="h-3 overflow-hidden rounded-full bg-slate-100" role="img" aria-label="{{ $trend['total_reviews'] }} review pada bulan {{ $trend['month'] }}">
                        <div class="h-full rounded-full bg-teal-600" style="width: {{ (int) round(($trend['total_reviews'] / $maxMonthlyReviews) * 100) }}%"></div>
                    </div>
                    <p class="text-xs text-slate-600">{{ $trend['total_reviews'] }} review · {{ $trend['complete_reviews'] }} lengkap · {{ $trend['follow_up_reviews'] }} temuan ({{ $findingRate }}%)</p>
                </div>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="quality-poli-trend-heading" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div>
            <h2 id="quality-poli-trend-heading" class="text-lg font-bold text-slate-900">Hasil review per poli</h2>
            <p class="mt-1 text-sm text-slate-600">Jumlah putaran review lengkap dan yang mencatat temuan. Angka nol berarti belum ada review tercatat pada periode itu.</p>
        </div>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
            @foreach ($trendsByPoli->groupBy('month') as $month => $poliTrends)
                <article class="rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                    <h3 class="text-xs font-bold text-slate-800">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->locale('id')->translatedFormat('F Y') }}</h3>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        @foreach ($poliTrends as $poliTrend)
                            <div class="rounded-lg bg-white p-3">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Poli {{ $poliTrend['poli'] === 'gigi' ? 'Gigi' : 'Umum' }}</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">{{ $poliTrend['total_reviews'] }} review</p>
                                <p class="mt-1 text-[10px] text-slate-600">{{ $poliTrend['complete_reviews'] }} lengkap · {{ $poliTrend['follow_up_reviews'] }} temuan</p>
                            </div>
                        @endforeach
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    @if (session('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-bold">Sampling tidak dapat dibuat.</p>
            <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section aria-labelledby="quality-sampling-heading" class="rounded-2xl border border-indigo-200 bg-indigo-50/40 p-5 shadow-sm">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <h2 id="quality-sampling-heading" class="text-lg font-bold text-slate-900">Cohort sampling mutu bulanan</h2>
                <p class="mt-1 max-w-3xl text-sm text-slate-600">Pilih periode kunjungan dan ukuran sampel. Sistem membagi sampel antara poli yang memiliki catatan, memprioritaskan catatan belum direview/versi baru/temuan, lalu mengisi sampel rutin. Cohort dan alasan pemilihan tersimpan sebagai jejak tetap.</p>
            </div>
            <form method="POST" action="{{ route('quality-reviews.sampling.store') }}" class="grid w-full gap-3 sm:grid-cols-[170px_140px_auto] lg:max-w-2xl">
                @csrf
                <label class="text-xs font-semibold text-slate-700">Bulan kunjungan
                    <input name="period" type="month" required max="{{ now()->format('Y-m') }}" value="{{ old('period', now()->subMonth()->format('Y-m')) }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                </label>
                <label class="text-xs font-semibold text-slate-700">Target sampel
                    <input name="sample_size" type="number" required min="1" max="100" value="{{ old('sample_size', 10) }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                </label>
                <button class="self-end rounded-xl bg-indigo-700 px-4 py-2.5 text-xs font-bold text-white hover:bg-indigo-800">Bentuk cohort</button>
            </form>
        </div>
        <p class="mt-3 text-[11px] text-slate-500">Satu cohort per bulan untuk menghindari penggantian sampel setelah hasil diketahui. Jika catatan yang tersedia lebih sedikit daripada target, seluruh catatan yang memenuhi syarat dimasukkan.</p>

        @if ($samplingRuns->isNotEmpty())
            <div class="mt-5 space-y-3">
                <h3 class="text-sm font-bold text-slate-800">Cohort tersimpan</h3>
                @foreach ($samplingRuns as $run)
                    <details class="rounded-xl border border-slate-200 bg-white p-4">
                        <summary class="cursor-pointer flex flex-wrap items-center justify-between gap-2 text-sm font-semibold text-slate-800">
                            <span>{{ $run->period_start->locale('id')->translatedFormat('F Y') }} · {{ $run->selected_sample_size }} catatan · dibuat {{ $run->created_at->timezone(config('app.timezone'))->format('d M Y H:i') }} oleh {{ $run->created_by_name }}</span>
                            <span class="text-xs text-indigo-700">Lihat cohort</span>
                        </summary>
                        <div class="mt-3 overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                                <thead class="text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="py-2 pr-3">Pasien / RM</th><th class="py-2 pr-3">Poli</th><th class="py-2 pr-3">Alasan sampling</th><th class="py-2">Tautan</th></tr></thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($run->items as $item)
                                        <tr>
                                            <td class="py-2 pr-3">{{ $item->medicalRecord->patient?->nama_lengkap ?? 'Identitas belum tersedia' }} · RM {{ $item->medicalRecord->patient?->no_rm ?? '—' }}</td>
                                            <td class="py-2 pr-3">{{ $item->poli === 'gigi' ? 'Gigi' : 'Umum' }}</td>
                                            <td class="py-2 pr-3">{{ $item->selection_reason === 'high_risk' ? 'Prioritas mutu' : 'Sampel rutin' }}</td>
                                            <td class="py-2"><a class="font-semibold text-teal-800 hover:underline" href="{{ route('quality-reviews.show', $item->medical_record_id) }}">Buka review</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                @endforeach
            </div>
        @endif
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('quality-reviews.index') }}" class="grid gap-3 md:grid-cols-[1fr_220px_auto]">
            <label class="sr-only" for="q">Cari pasien</label>
            <input id="q" name="q" value="{{ $search }}" maxlength="100" placeholder="Cari nama atau nomor rekam medis" class="rounded-xl border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">
            <label class="sr-only" for="filter">Filter hasil review</label>
            <select id="filter" name="filter" class="rounded-xl border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                <option value="pending" @selected($filter === 'pending')>Belum direview / versi baru</option>
                <option value="needs_follow_up" @selected($filter === 'needs_follow_up')>Perlu tindak lanjut</option>
                <option value="complete" @selected($filter === 'complete')>Lengkap</option>
                <option value="all" @selected($filter === 'all')>Semua catatan final</option>
            </select>
            <button class="rounded-xl bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-800">Terapkan</button>
        </form>
    </section>

    @if (session('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Pasien / kunjungan</th>
                        <th class="px-5 py-3">Penanggung jawab</th>
                        <th class="px-5 py-3">Versi</th>
                        <th class="px-5 py-3">Status review</th>
                        <th class="px-5 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $record)
                        @php
                            $reviewIsStale = $record->latest_reviewed_record_version !== null && (int) $record->latest_reviewed_record_version < (int) $record->current_record_version;
                            $reviewLabel = $record->latest_quality_review_result === 'complete' ? 'Lengkap' : ($record->latest_quality_review_result === 'needs_follow_up' ? 'Perlu tindak lanjut' : 'Belum direview');
                        @endphp
                        <tr class="align-top hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-900">{{ $record->patient?->nama_lengkap ?? 'Identitas belum tersedia' }}</div>
                                <div class="mt-1 text-xs text-slate-500">RM {{ $record->patient?->no_rm ?? '—' }} · {{ $record->queue?->poli === 'gigi' ? 'Poli Gigi' : 'Poli Umum' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $record->queue?->tanggal_antrean?->format('d M Y') ?? $record->created_at?->format('d M Y') ?? 'Tanggal tidak tersedia' }}</div>
                            </td>
                            <td class="px-5 py-4 text-slate-700">{{ $record->doctor?->name ?? $record->signed_by_name ?? 'Tidak tercatat' }}</td>
                            <td class="px-5 py-4 text-slate-700">v{{ $record->current_record_version }} @if($reviewIsStale)<span class="block text-xs text-amber-700">Review versi v{{ $record->latest_reviewed_record_version }}</span>@endif</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $record->latest_quality_review_result === 'complete' && ! $reviewIsStale ? 'bg-emerald-100 text-emerald-800' : ($record->latest_quality_review_result === 'needs_follow_up' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">{{ $reviewIsStale ? 'Versi baru perlu direview' : $reviewLabel }}</span>
                                @if ($record->latest_quality_reviewed_at)
                                    <div class="mt-1 text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($record->latest_quality_reviewed_at)->format('d M Y H:i') }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('quality-reviews.show', $record) }}" class="inline-flex items-center rounded-lg border border-teal-200 px-3 py-2 text-xs font-semibold text-teal-800 hover:bg-teal-50">Buka review</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">Tidak ada catatan yang cocok dengan filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-4">{{ $records->links() }}</div>
    </section>
</div>
@endsection
