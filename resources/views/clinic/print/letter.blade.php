<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <title>{{ $letter->jenis_label }} — {{ $letter->nomor_surat }}</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; margin: 0; padding: 40px; background: #fff; color: #000; line-height: 1.6; }
        .sheet { max-width: 750px; margin: 0 auto; }
        .kop { display: flex; align-items: center; border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 20px; }
        .kop-logo { width: 80px; height: auto; margin-right: 20px; }
        .kop-text { text-align: center; flex: 1; }
        .kop-text h2 { margin: 0; font-size: 16px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        .kop-text h1 { margin: 2px 0; font-size: 18px; font-weight: bold; text-transform: uppercase; }
        .kop-text p { margin: 0; font-size: 11px; font-family: Arial, sans-serif; }
        .title-box { text-align: center; margin: 24px 0 18px 0; }
        .title-box h3 { margin: 0; font-size: 16px; text-decoration: underline; text-transform: uppercase; font-weight: bold; }
        .title-box p { margin: 2px 0 0 0; font-size: 13px; font-family: Arial, sans-serif; }
        .content { font-size: 13px; text-align: justify; }
        .table-data { width: 100%; margin: 12px 0; border-collapse: collapse; }
        .table-data td { padding: 4px 6px; font-size: 13px; vertical-align: top; }
        .table-data td.lbl { width: 200px; }
        .table-data td.sep { width: 15px; text-align: center; }
        .qr-box { border: 1px solid #ccc; padding: 8px; width: 100px; height: 100px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .signature-section { margin-top: 40px; display: flex; justify-content: space-between; align-items: flex-end; }
        .btn-print { padding: 8px 20px; background: #005c55; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-family: sans-serif; font-size: 13px; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 25px;">
        <button class="btn-print" onclick="window.print()">
            🖨️ Cetak / Simpan Dokumen (PDF)
        </button>
    </div>

    <div class="sheet">
        <!-- KOP SURAT RESMI -->
        <div class="kop">
                <img src="/assets/images/logo_klinik_pratama.png" class="kop-logo" alt="Logo Klinik Pratama"/>
            <div class="kop-text">
                <h2>Klinik Pratama</h2>
                <p>Surat istirahat sakit untuk catatan pelayanan klinik.</p>
            </div>
        </div>

        <!-- JUDUL SURAT -->
        <div class="title-box">
            <h3>{{ strtoupper($letter->jenis_label) }}</h3>
            <p>Nomor: {{ $letter->nomor_surat }}</p>
            <p style="margin-top: 8px; color: #b91c1c; font-weight: bold;">DRAF — dokumen internal, belum ditandatangani, dan belum berlaku sebagai surat resmi.</p>
        </div>

        <!-- ISI SURAT -->
        <div class="content">
            <p>Catatan klinik yang disiapkan oleh dokter pemeriksa:</p>

            <table class="table-data">
                <tr>
                    <td class="lbl">Nama Lengkap</td>
                    <td class="sep">:</td>
                    <td><strong>{{ $letter->patient->nama_lengkap }}</strong></td>
                </tr>
                <tr>
                    <td class="lbl">Nomor Induk Pegawai (NIP)</td>
                    <td class="sep">:</td>
                    <td>{{ $letter->patient->nip ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Nomor Rekam Medis (No. RM)</td>
                    <td class="sep">:</td>
                    <td>{{ $letter->patient->no_rm }}</td>
                </tr>
                <tr>
                    <td class="lbl">Umur / Jenis Kelamin</td>
                    <td class="sep">:</td>
                    <td>{{ $letter->patient->usia !== null ? $letter->patient->usia.' Tahun' : 'Usia belum dicatat' }} / {{ $letter->patient->jenis_kelamin === 'L' ? 'Laki-laki' : ($letter->patient->jenis_kelamin === 'P' ? 'Perempuan' : 'Belum diketahui') }}</td>
                </tr>
                <tr>
                    <td class="lbl">Unit Kerja / Biro</td>
                    <td class="sep">:</td>
                    <td>{{ $letter->patient->unit_kerja ?? 'Tidak dicatat' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Diagnosis Medis (ICD-10)</td>
                    <td class="sep">:</td>
                    <td>{{ $letter->medicalRecord?->icd10_primary_code ?? 'Tidak dicatat' }} — {{ $letter->medicalRecord?->icd10_primary_name ?? 'Tidak dicatat' }}</td>
                </tr>
            </table>

            @if ($letter->jenis_surat === 'surat_sakit')
                <p>
                    Berdasarkan hasil pemeriksaan klinis yang telah dilakukan, yang bersangkutan dalam kondisi sakit dan 
                    <strong>memerlukan istirahat rawat jalan selama {{ $letter->lama_hari }} ({{ $letter->lama_hari == 2 ? 'dua' : ($letter->lama_hari == 1 ? 'satu' : ($letter->lama_hari == 3 ? 'tiga' : $letter->lama_hari)) }}) hari kalender</strong>, 
                    terhitung mulai tanggal <strong>{{ $letter->tanggal_mulai ? $letter->tanggal_mulai->translatedFormat('d F Y') : date('d F Y') }}</strong> 
                    sampai dengan tanggal <strong>{{ $letter->tanggal_selesai ? $letter->tanggal_selesai->translatedFormat('d F Y') : date('d F Y') }}</strong>.
                </p>
                <p>
                    Demikian surat keterangan ini diberikan dengan sebenar-benarnya untuk dapat dipergunakan dan diketahui oleh atasan langsung pada unit kerja yang bersangkutan.
                </p>
            @elseif ($letter->jenis_surat === 'surat_sehat')
                <p>
                    Berdasarkan hasil pemeriksaan fisik menyeluruh, tanda-tanda vital, dan antropometri yang telah dilakukan pada hari ini, yang bersangkutan dinyatakan dalam keadaan:
                </p>
                <div style="text-align: center; margin: 15px 0; font-size: 15px; font-weight: bold; letter-spacing: 1px;">
                    "{{ strtoupper($letter->status_kesehatan ?? 'TIDAK DICATAT') }}"
                </div>
                <p>
                    Surat keterangan ini diberikan untuk keperluan: <strong>{{ $letter->keperluan ?? 'Persyaratan Administrasi Kedinasan' }}</strong>.
                </p>
            @else
                <p>
                    Dengan ini merujuk pasien tersebut ke <strong>{{ $letter->tujuan_faskes_rujukan ?? 'Tidak dicatat' }}</strong> 
                    pada <strong>{{ $letter->poli_tujuan_rujukan ?? 'Spesialis Konsultan' }}</strong> untuk mendapatkan pemeriksaan dan penanganan lebih lanjut sehubungan dengan diagnosis: 
                    <strong>{{ $letter->medicalRecord?->icd10_primary_code }} - {{ $letter->medicalRecord?->icd10_primary_name }}</strong>.
                </p>
                <p>Alasan Rujukan: {{ $letter->alasan_rujukan ?? 'Pemeriksaan penunjang lanjutan dan tatalaksana komprehensif.' }}</p>
            @endif
        </div>

        <!-- Internal clinic document record -->
        <div class="signature-section">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="font-size: 10px; font-family: Arial, sans-serif; color: #444; max-width: 200px; line-height: 1.3;">
                    <strong>Draf internal klinik</strong><br/>
                    Belum ditandatangani dan belum berlaku sebagai dokumen resmi.
                </div>
            </div>

            <div style="text-align: center; width: 250px;">
                <div style="font-size: 12px; margin-bottom: 6px;">{{ $letter->tanggal_surat->translatedFormat('d F Y') }}</div>
                <div style="font-size: 12px; font-weight: bold;">Dokter Pemeriksa,</div>
                <div style="height: 65px; display: flex; align-items: center; justify-content: center;">
                    <div style="color: #64748b; font-size: 10px;">Belum ditandatangani secara elektronik</div>
                </div>
                <div style="font-size: 13px; font-weight: bold; text-decoration: underline;">
                    {{ $letter->doctor->name ?? 'Dokter tidak tersedia' }}
                </div>
                <div style="font-size: 11px; color: #333;">
                    SIP: {{ $letter->doctor?->sip ?: 'Belum dicatat' }}
                </div>
                <div style="font-size: 11px; color: #333;">
                    NIP: {{ $letter->doctor?->nip ?: 'Belum dicatat' }}
                </div>
            </div>
        </div>

    </div>
</body>
</html>
