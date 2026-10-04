<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <title>Stiker Antrean — {{ $queue->nomor_antrean }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: #fff; color: #111; }
        .ticket { max-width: 320px; margin: 0 auto; border: 2px dashed #999; padding: 20px; text-align: center; border-radius: 12px; }
        .logo { max-height: 48px; margin-bottom: 8px; }
        .title { font-size: 14px; font-weight: bold; margin: 0; text-transform: uppercase; color: #005c55; }
        .subtitle { font-size: 11px; color: #555; margin-bottom: 12px; }
        .queue-no { font-size: 44px; font-weight: 900; margin: 10px 0; color: #005c55; letter-spacing: 2px; }
        .patient-name { font-size: 15px; font-weight: bold; margin-bottom: 4px; }
        .meta { font-size: 11px; color: #444; margin-bottom: 2px; }
        .divider { border-top: 1px dashed #ccc; margin: 14px 0; }
        .barcode { font-family: monospace; font-size: 16px; letter-spacing: 4px; background: #f4f4f4; padding: 6px; margin: 8px 0; display: inline-block; font-weight: bold; }
        .footer { font-size: 10px; color: #777; line-height: 1.4; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="text-align: center; margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #005c55; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
            Cetak Tiket (Print)
        </button>
    </div>

    <div class="ticket">
        <img src="/assets/images/logo_klinik_pratama.png" class="logo" alt="Logo"/>
        <div class="title">Klinik Pratama</div>
        <div class="subtitle">Tiket antrean kunjungan</div>
        <div class="divider"></div>

        <div style="font-size: 12px; font-weight: bold; text-transform: uppercase; color: #666;">
            POLI TUJUAN: {{ strtoupper($queue->poli) }}
        </div>
        <div class="queue-no">{{ $queue->nomor_antrean }}</div>

        <div class="patient-name">{{ $queue->patient->nama_lengkap }}</div>
        <div class="meta">No. RM: {{ $queue->patient->no_rm }}</div>
        @if ($queue->is_emergency)
            <div class="meta" style="font-weight: bold; color: #b91c1c;">PENDAFTARAN DARURAT — IDENTITAS SEMENTARA</div>
        @endif
        <div class="meta">{{ $queue->patient->nip ? 'NIP: ' . $queue->patient->nip : 'Masyarakat Umum' }}</div>
        @if ($queue->patient->unit_kerja)
            <div class="meta">{{ $queue->patient->unit_kerja }}</div>
        @endif

        <div class="divider"></div>

        <div class="barcode">*{{ $queue->nomor_antrean }}-{{ str_replace('-', '', $queue->patient->no_rm) }}*</div>
        
        <div class="footer">
            <div>Waktu Daftar: {{ $queue->tanggal_antrean->format('d/m/Y') }} • {{ $queue->waktu_daftar ?? 'Tidak dicatat' }} WIB</div>
            <div>Silakan menunggu di ruang tunggu sesuai panggilan layar antrean.</div>
            <div style="margin-top: 6px;">@if ($queue->is_emergency) Mohon lengkapi/verifikasi identitas setelah kondisi memungkinkan. @else Mohon siapkan kartu identitas saat pendaftaran. @endif</div>
        </div>
    </div>
</body>
</html>
