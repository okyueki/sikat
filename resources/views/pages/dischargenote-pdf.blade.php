<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Discharge Note</title>

<style>
/* ===== RESET & BASE ===== */
body {
    font-size: 12px;
    font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
    /*background: #f4f6f9;*/
    color: #333;
    margin: 0;
}

.page {
    background: #fff;
    padding: 25px;
    border-radius: 6px;
}

/* ===== LAYOUT MIRIP BOOTSTRAP ===== */
.container { width: 100%; }
.row { width: 100%; clear: both; }
.col-8 { width: 66.666%; float: left; }
.col-4 { width: 33.333%; float: left; }
.text-end { text-align: right; }
.text-center { text-align: center; }

/* ===== HEADER ===== */
.report-header {
    background: #0d6efd;
    color: #fff;
    padding: 50px 20px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.report-header h4 {
    margin: 0;
    font-weight: bold;
    font-size: 15px;
}

.report-header small {
    font-size: 11px;
    opacity: .9;
}

.badge-status {
    background: #198754;
    color: #fff;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 10px;
    display: inline-block;
}

/* ===== TITLE ===== */
.report-title {
    text-align: center;
    margin: 20px 0;
    font-weight: bold;
    font-size: 16px;
    letter-spacing: 1px;
}

/* ===== SECTION ===== */
.section {
    margin-bottom: 18px;
}

.section-title {
    font-weight: bold;
    font-size: 13px;
    color: #0d6efd;
    border-left: 4px solid #0d6efd;
    padding-left: 8px;
    margin-bottom: 8px;
}

/* ===== TABLE ===== */
table {
    width: 100%;
    border-collapse: collapse;
}

.info-table td {
    padding: 5px 6px;
    vertical-align: top;
}

.info-label {
    width: 25%;
    color: #666;
}

.table-bordered th,
.table-bordered td {
    border: 1px solid #333;
    padding: 6px;
    font-size: 11px;
}

.table-bordered th {
    background: #f1f3f5;
}

/* ===== FOOTER ===== */
.footer {
    margin-top: 40px;
}

.signature {
    text-align: right;
}

.signature .name {
    margin-top: 55px;
    font-weight: bold;
    text-decoration: underline;
}
</style>
</head>

<body>
<div class="container">
<div class="page">

@php
    $pasien  = optional(optional($asuhan)->regPeriksa)->pasien;
    $dokter  = optional($asuhan)->dokter;
    $kamar   = optional($kamarinap)->kamar;
    $bangsal = optional($kamar)->bangsal;
@endphp

{{-- HEADER --}}
<div class="report-header">
    <div class="row">
        <div class="col-8">
            <h4>RUMAH SAKIT UMUM 'AISYIYAH SITI FATIMAH</h4>
            <small>Sistem Informasi Rumah Sakit</small>
        </div>
        <div class="col-4 text-end">
            <span class="badge-status">DISCHARGED</span><br>
            <small>{{ now()->format('d M Y') }}</small>
        </div>
    </div>
</div>

{{-- TITLE --}}
<div class="report-title">
    DISCHARGE NOTE / RINGKASAN PULANG
</div>

{{-- IDENTITAS --}}
<div class="section">
    <div class="section-title">IDENTITAS PASIEN</div>
    <table class="info-table">
        <tr>
            <td class="info-label">Nama Pasien</td>
            <td>{{ $pasien->nm_pasien ?? '-' }}</td>
            <td class="info-label">No. Rawat</td>
            <td>{{ $asuhan->no_rawat ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">No. RM</td>
            <td>{{ $pasien->no_rkm_medis ?? '-' }}</td>
            <td class="info-label">Ruang / Kelas</td>
            <td>{{ $bangsal->nm_bangsal ?? '-' }} / {{ $kamar->kelas ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Jenis Kelamin</td>
            <td>{{ $pasien->jk === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
            <td class="info-label">DPJP</td>
            <td>{{ $dokter->nm_dokter ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Tanggal Masuk</td>
            <td>{{ $asuhan->tgl_masuk ?? '-' }}</td>
            <td class="info-label">Tanggal Pulang</td>
            <td>{{ $asuhan->tgl_keluar ?? '-' }}</td>
        </tr>
    </table>
</div>

{{-- DIAGNOSIS --}}
<div class="section">
    <div class="section-title">DIAGNOSIS</div>
    <p><strong>Masuk:</strong><br>{{ $asuhan->diagnosa_awal ?? '-' }}</p>
    <p><strong>Pulang:</strong><br>{{ $asuhan->diagnosa_akhir ?? '-' }}</p>
</div>

{{-- TINDAKAN --}}
<div class="section">
    <div class="section-title">TINDAKAN SELAMA PERAWATAN</div>
    <ol>
        @forelse($asuhan->tindakan ?? [] as $tdk)
            <li>{{ $tdk->tindakan ?? '-' }}</li>
        @empty
            <li>-</li>
        @endforelse
    </ol>
</div>

{{-- OBAT --}}
<div class="section">
    <div class="section-title">TERAPI / OBAT</div>
    <table class="table-bordered">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Nama Obat</th>
                <th width="15%">Dosis</th>
                <th width="20%">Cara Pakai</th>
                <th width="30%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
        @forelse($asuhan->obat ?? [] as $i => $obat)
            <tr>
                <td class="text-center">{{ $i+1 }}</td>
                <td>{{ $obat->nama_obat ?? '-' }}</td>
                <td>{{ $obat->dosis ?? '-' }}</td>
                <td>{{ $obat->cara_pakai ?? '-' }}</td>
                <td>{{ $obat->keterangan ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center">Tidak ada data obat</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- KONDISI --}}
<div class="section">
    <div class="section-title">KONDISI PASIEN SAAT PULANG</div>
    <p>
        Tidur: {{ $asuhan->total_waktu_tidur ?? '-' }} jam ({{ $asuhan->kualitas_tidur ?? '-' }})<br>
        Makan: {{ $asuhan->kalori_makan ?? '-' }}x |
        Minum: {{ $asuhan->nutrisi_minum ?? '-' }}<br>
        Tensi: {{ $asuhan->tensi ?? '-' }},
        RR: {{ $asuhan->rr ?? '-' }},
        SPO2: {{ $asuhan->spo2 ?? '-' }},
        Suhu: {{ $asuhan->temp ?? '-' }}
    </p>
</div>

{{-- TTD --}}
<div class="footer">
    <div class="signature">
        <p>Dokter Penanggung Jawab</p>
        <div class="name">{{ $dokter->nm_dokter ?? '-' }}</div>
    </div>
</div>

</div>
</div>
</body>
</html>
