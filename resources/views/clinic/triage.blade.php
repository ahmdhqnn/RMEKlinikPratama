@extends('clinic.layouts.app')

@section('title', 'Triage & Pemeriksaan Fisik Awal Keperawatan')

@section('content')
<div class="flex flex-col gap-6 pb-12" x-data="{ 
    selectedQueue: {{ $queues->first() ? $queues->first()->id : 'null' }},
    bb: null,
    tb: null,
    calcImt() {
        if (!this.bb || !this.tb) return '—';
        let m = this.tb / 100;
        return (this.bb / (m * m)).toFixed(1);
    }
}">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold font-heading text-slate-900">Ruang Triage & Anamnesis Awal</h1>
            <p class="text-sm text-slate-500 mt-1">Pemeriksaan tanda-tanda vital (TTV), skrining alergi, dan pengkajian awal keperawatan.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3.5 py-1.5 rounded-full bg-amber-50 text-amber-800 text-xs font-bold border border-amber-200">
                {{ $queues->count() }} Pasien Menunggu Triage
            </span>
        </div>
    </div>

    <!-- MAIN TWO COLUMNS: Waiting list on left, Vitals form on right -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT: Queues waiting for Triage (5 cols) -->
        <div class="lg:col-span-5 flex flex-col gap-3">
            <span class="text-xs font-bold font-heading text-slate-400 uppercase tracking-wider">Pilih Pasien untuk Triage:</span>
            
            @forelse ($queues as $q)
                @php $p = $q->patient; @endphp
                <div @click="selectedQueue = {{ $q->id }}; bb = {{ $q->vitalSign?->berat_badan ? json_encode($q->vitalSign->berat_badan) : 'null' }}; tb = {{ $q->vitalSign?->tinggi_badan ? json_encode($q->vitalSign->tinggi_badan) : 'null' }}" 
                     :class="selectedQueue === {{ $q->id }} ? 'border-primary ring-2 ring-primary/20 bg-emerald-50/30' : 'border-slate-100 bg-white'"
                     class="p-4 rounded-2xl border shadow-card hover:shadow-card-hover transition-all cursor-pointer flex items-center justify-between gap-3">
                    
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-11 h-11 rounded-xl bg-slate-100 font-heading font-black text-xs flex flex-col items-center justify-center text-slate-800 flex-shrink-0">
                            <span>{{ $q->nomor_antrean }}</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <h4 class="font-heading font-bold text-xs text-slate-900 truncate">{{ $p->nama_lengkap }}</h4>
                            <span class="text-[11px] text-slate-400 font-mono">No. RM: {{ $p->no_rm }}</span>
                            @if ($q->is_emergency)
                                <span class="mt-1 inline-flex w-fit rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-extrabold text-red-800">DARURAT • TRIAGE PRIORITAS</span>
                            @elseif ($p->identity_status === 'provisional')
                                <span class="mt-1 inline-flex w-fit rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">IDENTITAS BELUM TERVERIFIKASI</span>
                            @endif
                            <span class="text-[11px] text-slate-600 truncate mt-0.5 italic">"{{ $q->keluhan_singkat }}"</span>
                        </div>
                    </div>

                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $q->status === 'menunggu_triage' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                        {{ $q->status_badge['label'] }}
                    </span>
                </div>
            @empty
                <div class="bg-white p-8 rounded-2xl border border-slate-100 text-center text-slate-400 text-xs">
                    Tidak ada antrean yang menunggu pemeriksaan triage saat ini.
                </div>
            @endforelse
        </div>

        <!-- RIGHT: Vital Signs Input Console (7 cols) -->
        <div class="lg:col-span-7 bg-white p-6 rounded-[28px] border border-slate-100 shadow-card flex flex-col gap-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="font-heading font-extrabold text-base text-slate-900">Form Pengkajian TTV & Anamnesis Awal</h3>
                    <p class="text-xs text-slate-500">Catat hasil pengkajian yang diperoleh dari pasien.</p>
                </div>
                <div class="flex items-center gap-1.5 text-xs text-primary font-bold">
                    <span class="material-symbols-outlined text-[16px]">health_and_safety</span>
                    <span>Isi sesuai hasil pengkajian</span>
                </div>
            </div>

            @if ($queues->count() > 0)
                @foreach ($queues as $q)
                    @php 
                        $p = $q->patient;
                        $vs = $q->vitalSign;
                    @endphp
                    <div x-show="selectedQueue === {{ $q->id }}">
                        
                        <!-- Selected Patient Summary Bar -->
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2.5">
                                <span class="font-heading font-black text-sm text-primary">{{ $q->nomor_antrean }}</span>
                                <span class="text-xs font-bold text-slate-900">{{ $p->nama_lengkap }}{{ $p->usia ? ' ('.$p->usia.' Thn)' : '' }}</span>
                            </div>
                            <span class="text-[11px] font-mono text-slate-500">Poli Tujuan: {{ strtoupper($q->poli) }}</span>
                        </div>

                        <form action="{{ route('triage.ttv', $q->id) }}" method="POST" class="flex flex-col gap-4 text-xs font-body">
                            @csrf

                            <!-- Row 1: Blood Pressure & Temp -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="font-bold text-slate-700 block mb-1">Tekanan Darah (Sistolik / Diastolik mmHg)</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="number" name="sistolik" value="{{ old('sistolik', $vs?->sistolik) }}" placeholder="Sistolik" required min="40" max="300" class="bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs font-mono font-bold"/>
                                        <input type="number" name="diastolik" value="{{ old('diastolik', $vs?->diastolik) }}" placeholder="Diastolik" required min="20" max="200" class="bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs font-mono font-bold"/>
                                    </div>
                                </div>
                                <div>
                                    <label class="font-bold text-slate-700 block mb-1">Suhu Tubuh (°C)</label>
                                    <input type="number" step="0.1" name="suhu" value="{{ old('suhu', $vs?->suhu) }}" placeholder="°C" required min="25" max="45" class="w-full bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs font-mono font-bold"/>
                                </div>
                            </div>

                            <!-- Row 2: Heart rate, Resp rate, SpO2 -->
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="font-bold text-slate-700 block mb-1">Denyut Nadi (x/mnt)</label>
                                    <input type="number" name="nadi" value="{{ old('nadi', $vs?->nadi) }}" placeholder="x/menit" min="20" max="250" class="w-full bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs font-mono"/>
                                </div>
                                <div>
                                    <label class="font-bold text-slate-700 block mb-1">Laju Nafas / RR (x/mnt)</label>
                                    <input type="number" name="laju_nafas" value="{{ old('laju_nafas', $vs?->laju_nafas) }}" placeholder="x/menit" min="4" max="80" class="w-full bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs font-mono"/>
                                </div>
                                <div>
                                    <label class="font-bold text-slate-700 block mb-1">Saturasi Oksigen SpO2 (%)</label>
                                    <input type="number" name="spo2" value="{{ old('spo2', $vs?->spo2) }}" placeholder="%" min="50" max="100" class="w-full bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs font-mono"/>
                                </div>
                            </div>

                            <!-- Row 3: Anthropometry (BB, TB, IMT) -->
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="font-bold text-slate-700 block mb-1">Berat Badan (kg)</label>
                                    <input type="number" step="0.1" name="berat_badan" x-model="bb" class="w-full bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs font-mono font-bold"/>
                                </div>
                                <div>
                                    <label class="font-bold text-slate-700 block mb-1">Tinggi Badan (cm)</label>
                                    <input type="number" step="0.1" name="tinggi_badan" x-model="tb" class="w-full bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs font-mono font-bold"/>
                                </div>
                                <div>
                                    <label class="font-bold text-slate-700 block mb-1">Kalkulasi IMT (kg/m²)</label>
                                    <div class="bg-emerald-50 text-emerald-900 font-mono font-bold p-2.5 rounded-xl border border-emerald-200 text-xs flex items-center justify-between">
                                        <span x-text="calcImt()"></span>
                                        <span class="text-[10px] text-emerald-700 uppercase">Auto</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Row 4: Kesadaran & Skala Nyeri -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="font-bold text-slate-700 block mb-1">Tingkat Kesadaran</label>
                                    <select name="kesadaran" class="w-full bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs">
                                        <option value="Compos Mentis">Compos Mentis</option>
                                        <option value="Apatis" @selected(old('kesadaran', $vs?->kesadaran) === 'Apatis')>Apatis</option>
                                        <option value="Somnolen" @selected(old('kesadaran', $vs?->kesadaran) === 'Somnolen')>Somnolen</option>
                                        <option value="Sopor" @selected(old('kesadaran', $vs?->kesadaran) === 'Sopor')>Sopor</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="font-bold text-slate-700 block mb-1">Skala Nyeri (0 - 10)</label>
                                    <input type="number" min="0" max="10" name="skala_nyeri" value="{{ old('skala_nyeri', $vs?->skala_nyeri) }}" placeholder="0–10" class="w-full bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs font-mono"/>
                                </div>
                            </div>

                            <!-- Catatan Keperawatan -->
                            <div>
                                <label class="font-bold text-slate-700 block mb-1">Catatan Pengkajian Awal Keperawatan:</label>
                                <textarea name="catatan_keperawatan" rows="2" class="w-full bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs resize-none" placeholder="Catatan kondisi awal, riwayat alergi yang dikonfirmasi ulang..."></textarea>
                            </div>

                            <!-- CTA Submit -->
                            <button type="submit" class="w-full py-3 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold font-heading shadow-md flex items-center justify-center gap-2 transition-all">
                                <span class="material-symbols-outlined text-[18px]">verified</span>
                                <span>Simpan TTV & Alihkan ke "Siap Diperiksa Dokter"</span>
                            </button>
                        </form>

                    </div>
                @endforeach
            @else
                <div class="p-12 text-center text-slate-400">
                    Tidak ada antrean aktif.
                </div>
            @endif
        </div>

    </div>

</div>
@endsection
