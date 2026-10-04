<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <title>Kartu Berobat — {{ $patient->nama_lengkap }} ({{ $patient->no_rm }})</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 30px; background: #f8fafc; color: #1e293b; }
        .card-container { display: flex; flex-wrap: wrap; gap: 30px; justify-content: center; }
        .card { width: 420px; height: 260px; background: linear-gradient(135deg, #005c55 0%, #0f766e 60%, #003834 100%); border-radius: 16px; padding: 20px; box-sizing: border-box; color: white; position: relative; overflow: hidden; box-shadow: 0 10px 25px rgba(0, 92, 85, 0.25); }
        .card::before { content: ""; position: absolute; top: -50px; right: -50px; width: 180px; height: 180px; border-radius: 50%; background: rgba(255, 255, 255, 0.08); pointer-events: none; }
        .card-header { display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255, 255, 255, 0.2); padding-bottom: 10px; }
        .card-logo { height: 38px; width: auto; }
        .card-header h2 { margin: 0; font-size: 13px; text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; }
        .card-header p { margin: 1px 0 0 0; font-size: 10px; opacity: 0.85; }
        .card-body { display: flex; gap: 15px; margin-top: 14px; }
        .card-photo { width: 75px; height: 95px; border-radius: 8px; object-cover; background: #fff; border: 2px solid rgba(255, 255, 255, 0.4); flex-shrink: 0; }
        .card-info { flex: 1; min-width: 0; }
        .card-name { font-size: 15px; font-weight: 800; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .card-rm { font-family: monospace; font-size: 16px; font-weight: 900; color: #9cf2e8; letter-spacing: 1px; margin-bottom: 6px; }
        .card-meta { font-size: 10px; opacity: 0.9; margin-bottom: 2px; }
        .card-footer { position: absolute; bottom: 14px; left: 20px; right: 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255, 255, 255, 0.15); padding-top: 8px; font-size: 9px; opacity: 0.8; }
        
        .card-back { background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1; }
        .card-back .back-title { font-size: 12px; font-weight: bold; color: #005c55; border-bottom: 2px solid #005c55; padding-bottom: 4px; margin-bottom: 10px; text-transform: uppercase; }
        .rules { font-size: 10px; line-height: 1.5; color: #334155; padding-left: 16px; margin: 0; }
        .rules li { margin-bottom: 4px; }
        .barcode-box { text-align: center; margin-top: 12px; padding: 6px; background: #f8fafc; border-radius: 6px; border: 1px dashed #cbd5e1; }
        .barcode-text { font-family: monospace; font-size: 14px; font-weight: bold; letter-spacing: 3px; color: #005c55; }
        
        .btn-print { padding: 10px 24px; background: #005c55; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 13px; margin-bottom: 20px; }
        @media print {
            body { padding: 0; background: #fff; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center;">
        <button class="btn-print" onclick="window.print()">
            🖨️ Cetak Kartu Berobat (Print Card)
        </button>
    </div>

    <div class="card-container">
        
        <!-- DEPAN KARTU -->
        <div class="card">
            <div class="card-header">
                <img src="/assets/images/logo_klinik_pratama.png" class="card-logo" alt="Logo"/>
                <div>
                    <h2>Klinik Pratama</h2>
                    <p>Kartu identitas pasien internal klinik</p>
                </div>
            </div>

            <div class="card-body">
                <img src="{{ $patient->avatar_url ?? '/assets/images/166de6af4e856812b08f19dc83cc9152.webp' }}" class="card-photo" alt="Foto Pasien"/>
                <div class="card-info">
                    <div class="card-rm">{{ $patient->no_rm }}</div>
                    <div class="card-name">{{ $patient->nama_lengkap }}</div>
                    <div class="card-meta">{{ $patient->nip ? 'NIP: ' . $patient->nip : 'Masyarakat Umum' }}</div>
                    @if ($patient->unit_kerja)
                        <div class="card-meta">{{ $patient->unit_kerja }}</div>
                    @endif
                    <div class="card-meta">{{ $patient->jenis_pasien ?? 'Umum' }}{{ $patient->golongan_darah ? ' • Gol. Darah: '.$patient->golongan_darah : '' }}</div>
                </div>
            </div>

            <div class="card-footer">
                <span>Terdaftar: {{ $patient->created_at->format('d/m/Y') }}</span>
                <span>IDENTITAS INTERNAL KLINIK</span>
            </div>
        </div>

        <!-- BELAKANG KARTU -->
        <div class="card card-back">
            <div class="back-title">Ketentuan Pelayanan Klinik Pratama</div>
            
            <ol class="rules">
                <li>Harap membawa kartu ini setiap kali berobat ke Klinik Pratama.</li>
                <li>Pendaftaran antrean dapat dilakukan di loket mandiri atau website internal.</li>
                <li>Wajib menyampaikan kepada dokter/perawat apabila memiliki riwayat alergi obat.</li>
                <li>Kartu ini tidak boleh dipindahtangankan kepada orang lain.</li>
                <li>Apabila kartu hilang, segera melapor ke loket rekam medis gedung A.</li>
            </ol>

            <div class="barcode-box">
                <div class="barcode-text">*{{ str_replace('-', '', $patient->no_rm) }}*</div>
                <div style="font-size: 8px; color: #64748b; margin-top: 2px;">NOMOR REKAM MEDIS</div>
            </div>
        </div>

    </div>
</body>
</html>
