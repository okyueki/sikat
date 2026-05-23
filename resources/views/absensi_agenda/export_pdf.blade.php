<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Presensi Agenda</title>
    <style>
        @page {
            size: A4;
            margin: 1.5cm;
        }
        body {
            font-family: 'Times New Roman', serif;
            font-size: 11pt;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .logo {
            max-width: 80px;
            height: auto;
        }
        .title {
            font-size: 16pt;
            font-weight: bold;
            margin: 10px 0;
        }
        .subtitle {
            font-size: 12pt;
            margin-bottom: 5px;
        }
        .info-box {
            margin: 15px 0;
            padding: 10px;
            border: 1px solid #000;
        }
        .info-row {
            margin: 5px 0;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 10pt;
        }
        table th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            font-weight: bold;
        }
        table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
        table td.center {
            text-align: center;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9pt;
            font-weight: bold;
        }
        .status-hadir {
            background-color: #d4edda;
            color: #155724;
        }
        .status-ijin {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .status-cuti {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-sakit {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-berhalangan {
            background-color: #e2e3e5;
            color: #383d41;
        }
        .status-tidak-hadir {
            background-color: #f8d7da;
            color: #721c24;
        }
        .statistik {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #000;
        }
        .statistik-row {
            margin: 5px 0;
            display: flex;
            justify-content: space-between;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10pt;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($logo)
            <img src="{{ $logo }}" alt="Logo" class="logo">
        @endif
        <div class="title">DAFTAR PRESENSI AGENDA</div>
        <div class="subtitle">Rumah Sakit Aisyiyah Siti Fatimah Tulangan</div>
    </div>

    <div class="info-box">
        <div class="info-row">
            <span class="info-label">Judul Agenda:</span>
            <span>{{ $agenda->judul }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Tanggal:</span>
            <span>{{ $tanggalAgenda }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Waktu:</span>
            <span>{{ $waktuAgenda }} WIB</span>
        </div>
        @if($agenda->tempat)
        <div class="info-row">
            <span class="info-label">Tempat:</span>
            <span>{{ $agenda->tempat }}</span>
        </div>
        @endif
        @if($agenda->pimpinan)
        <div class="info-row">
            <span class="info-label">Pimpinan Rapat:</span>
            <span>{{ $agenda->pimpinan->nama }}</span>
        </div>
        @endif
        @if($agenda->notulenPegawai)
        <div class="info-row">
            <span class="info-label">Notulen:</span>
            <span>{{ $agenda->notulenPegawai->nama }}</span>
        </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 12%;">NIK</th>
                <th style="width: 30%;">Nama</th>
                <th style="width: 20%;">Jabatan</th>
                <th style="width: 15%;">Departemen</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 18%;">Waktu Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            @php
                $no = 1;
                $currentPage = 1;
                $itemsPerPage = 25;
            @endphp
            @foreach($dataPresensi as $presensi)
                @if($no > 1 && ($no - 1) % $itemsPerPage == 0)
                    </tbody>
                    </table>
                    <div class="page-break"></div>
                    <div class="header">
                        @if($logo)
                            <img src="{{ $logo }}" alt="Logo" class="logo">
                        @endif
                        <div class="title">DAFTAR PRESENSI AGENDA (Lanjutan)</div>
                        <div class="subtitle">{{ $agenda->judul }}</div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 12%;">NIK</th>
                                <th style="width: 30%;">Nama</th>
                                <th style="width: 20%;">Jabatan</th>
                                <th style="width: 15%;">Departemen</th>
                                <th style="width: 10%;">Status</th>
                                <th style="width: 18%;">Waktu Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody>
                @endif
                <tr>
                    <td class="center">{{ $no++ }}</td>
                    <td>{{ $presensi['nik'] }}</td>
                    <td><strong>{{ $presensi['nama'] }}</strong></td>
                    <td>{{ $presensi['jabatan'] }}</td>
                    <td>{{ $presensi['departemen'] }}</td>
                    <td class="center">
                        <span class="status-badge status-{{ $presensi['status_key'] }}">
                            {{ $presensi['status'] }}
                        </span>
                    </td>
                    <td class="center">{{ $presensi['waktu_kehadiran'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="statistik">
        <div style="font-weight: bold; margin-bottom: 10px; text-align: center;">REKAPITULASI</div>
        <div class="statistik-row">
            <span>Jumlah Undangan:</span>
            <span><strong>{{ $statistik['total'] }} orang</strong></span>
        </div>
        <div class="statistik-row">
            <span>Hadir:</span>
            <span><strong>{{ $statistik['hadir'] }} orang</strong></span>
        </div>
        <div class="statistik-row">
            <span>Ijin:</span>
            <span><strong>{{ $statistik['ijin'] }} orang</strong></span>
        </div>
        <div class="statistik-row">
            <span>Cuti:</span>
            <span><strong>{{ $statistik['cuti'] }} orang</strong></span>
        </div>
        <div class="statistik-row">
            <span>Sakit:</span>
            <span><strong>{{ $statistik['sakit'] }} orang</strong></span>
        </div>
        <div class="statistik-row">
            <span>Berhalangan:</span>
            <span><strong>{{ $statistik['berhalangan'] }} orang</strong></span>
        </div>
        <div class="statistik-row">
            <span>Tidak Hadir:</span>
            <span><strong>{{ $statistik['tidak_hadir'] }} orang</strong></span>
        </div>
        @php
            $totalHadir = $statistik['hadir'] + $statistik['ijin'] + $statistik['cuti'] + $statistik['sakit'] + $statistik['berhalangan'];
            $persentase = $statistik['total'] > 0 ? round(($totalHadir / $statistik['total']) * 100, 2) : 0;
        @endphp
        <div class="statistik-row" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #000;">
            <span>Tingkat Kehadiran:</span>
            <span><strong>{{ $persentase }}%</strong></span>
        </div>
    </div>

    <div class="footer">
        <div>Dicetak pada: {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i:s') }} WIB</div>
    </div>
</body>
</html>
