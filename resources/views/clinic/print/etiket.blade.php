<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <title>Etiket Obat Farmasi — {{ $prescription->nomor_resep }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: #fff; color: #000; }
        .grid { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; }
        .etiket { width: 320px; border: 2px solid #005c55; border-radius: 8px; padding: 12px; box-sizing: border-box; background: #fff; page-break-inside: avoid; }
        .header { text-align: center; border-bottom: 2px solid #005c55; padding-bottom: 6px; margin-bottom: 8px; }
        .header h3 { margin: 0; font-size: 11px; text-transform: uppercase; color: #005c55; font-weight: 800; letter-spacing: 0.5px; }
        .header p { margin: 2px 0 0 0; font-size: 9px; color: #555; }
        .meta-row { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 4px; }
        .patient-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 6px 8px; margin: 6px 0; }
        .patient-name { font-size: 13px; font-weight: bold; color: #000; }
        .drug-box { margin: 8px 0; text-align: center; }
        .drug-name { font-size: 14px; font-weight: 900; color: #005c55; }
        .aturan { font-size: 15px; font-weight: 900; margin: 8px 0; padding: 6px; background: #e6f4f2; border-radius: 6px; color: #004741; }
        .warning { font-size: 9px; text-align: center; border-top: 1px dashed #ccc; padding-top: 6px; color: #777; text-transform: uppercase; font-weight: bold; }
        .btn-print { padding: 8px 18px; background: #005c55; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 12px; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button class="btn-print" onclick="window.print()">
            🖨️ Cetak Seluruh Etiket Obat (Print Label)
        </button>
    </div>

    <div class="grid">
        @foreach ($items as $it)
            <div class="etiket">
                <div class="header">
                    <h3>Instalasi Farmasi Klinik Pratama</h3>
                    <p>Label internal farmasi klinik</p>
                </div>

                <div class="meta-row">
                    <span>No: <strong>{{ $prescription->nomor_resep }}</strong></span>
                    <span>Tgl: {{ $prescription->created_at->format('d/m/Y') }}</span>
                </div>

                <div class="patient-box">
                    <div class="patient-name">{{ $prescription->patient->nama_lengkap }}</div>
                    <div style="font-size: 10px; color: #555;">No. RM: {{ $prescription->patient->no_rm }}</div>
                </div>

                <div class="drug-box">
                    <div class="drug-name">{{ $it->medicine->nama_obat ?? 'Obat' }}</div>
                    <div style="font-size: 9px; color: #555;">Batch: {{ $it->medicine->nomor_batch }} • Kedaluwarsa: {{ $it->medicine->expired_date?->format('d/m/Y') }}</div>
                    <div style="font-size: 11px; color: #444; font-weight: bold;">Jumlah: {{ $it->jumlah }} {{ $it->medicine->satuan ?? 'Tab' }}</div>
                </div>

                <div class="aturan">
                    {{ $it->aturan_pakai }}
                </div>

                @if ($it->catatan_khusus)
                    <div style="font-size: 10px; color: #b91c1c; font-weight: bold; text-align: center; margin-bottom: 6px;">
                        * {{ $it->catatan_khusus }}
                    </div>
                @endif

                <div class="warning">
                    Semoga Lekas Sembuh • Jauhkan dari Anak-Anak
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
