<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        @page {
            size: A4;
            margin: 0 1.5cm;
        }
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.6;
        }
        .container-fluid {
            width: 100%;
            padding-right: var(--bs-gutter-x, 0.75rem);
            padding-left: var(--bs-gutter-x, 0.75rem);
            margin-right: auto;
            margin-left: auto;
        }
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-top: calc(-1 * var(--bs-gutter-y, 0));
            margin-right: calc(-0.5 * var(--bs-gutter-x, 0));
            margin-left: calc(-0.5 * var(--bs-gutter-x, 0));
        }
        .col-12 {
            flex: 0 0 auto;
            width: 100%;
        }
        .text-center {
            text-align: center !important;
        }
        .text-left {
            text-align: left !important;
        }
        .text-right {
            text-align: right !important;
        }
        .text-decoration-underline {
            text-decoration: underline !important;
        }
        .mt-2 {
            margin-top: 0.5rem !important;
        }
        .mt-3 {
            margin-top: 1rem !important;
        }
        .mt-4 {
            margin-top: 1.5rem !important;
        }
        .mb-2 {
            margin-bottom: 0.5rem !important;
        }
        .mb-3 {
            margin-bottom: 1rem !important;
        }
        .mb-4 {
            margin-bottom: 1.5rem !important;
        }
        p, h3, h4 {
            padding: 0;
            margin: 0;
            line-height: 1.6;
        }
        .table-borderless {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
            border-collapse: collapse;
        }
        .table-borderless th, .table-borderless td {
            border: 0px solid #dee2e6 !important;
            padding: 0.25rem;
            vertical-align: top;
        }
        .table-bordered {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
            border-collapse: collapse;
        }
        .table-bordered th, .table-bordered td {
            border: 1px solid #212529;
            padding: 0.5rem;
            vertical-align: top;
            text-align: left;
        }
        .table-bordered th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
        }
        .two-columns {
            display: flex;
            flex-wrap: wrap;
        }
        .column {
            flex: 1;
            min-width: 45%;
            padding: 0 10px;
        }
        .indent {
            text-indent: 2.5cm;
        }
        .signature-section {
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="a4">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 text-center">
                    <br>
                    <img width="225" src="{{ $kop_surat }}" alt="Logo">
                    <br><br>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <table class="table-borderless">
                        <tr>
                            <td style="width: 100px;">Nomor</td>
                            <td style="width: 20px;">:</td>
                            <td>{{ $agenda->nomor_agenda }}</td>
                        </tr>
                        <tr>
                            <td>Perihal</td>
                            <td>:</td>
                            <td><strong>Undangan {{ $agenda->judul }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12 text-right">
                    <p>Sidoarjo, {{ $tanggal_dibuat }}</p>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <p>Kepada Yth.</p>
                    <p class="indent">Yang tersebut dalam lampiran surat ini</p>
                    <p class="indent">di tempat</p>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <p>Dengan hormat,</p>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <p class="indent">Sehubungan dengan kegiatan yang akan dilaksanakan, dengan ini kami mengundang Bapak/Ibu untuk hadir dalam acara:</p>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <table class="table-borderless">
                        <tr>
                            <td style="width: 150px;">Judul Agenda</td>
                            <td style="width: 20px;">:</td>
                            <td><strong>{{ $agenda->judul }}</strong></td>
                        </tr>
                        @if($agenda->deskripsi)
                        <tr>
                            <td>Deskripsi</td>
                            <td>:</td>
                            <td>{{ $agenda->deskripsi }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td>Hari/Tanggal</td>
                            <td>:</td>
                            <td>{{ $tanggal_mulai }}</td>
                        </tr>
                        <tr>
                            <td>Waktu</td>
                            <td>:</td>
                            <td>{{ $waktu_mulai }} WIB
                                @if($tanggal_akhir && $tanggal_akhir != $tanggal_mulai)
                                    s.d. {{ $tanggal_akhir }}, {{ $waktu_akhir }} WIB
                                @elseif($waktu_akhir)
                                    s.d. {{ $waktu_akhir }} WIB
                                @endif
                            </td>
                        </tr>
                        @if($agenda->tempat)
                        <tr>
                            <td>Tempat</td>
                            <td>:</td>
                            <td>{{ $agenda->tempat }}</td>
                        </tr>
                        @endif
                        @if($agenda->pimpinan)
                        <tr>
                            <td>Pimpinan Rapat</td>
                            <td>:</td>
                            <td>{{ $agenda->pimpinan->nama }}</td>
                        </tr>
                        @endif
                        @if($agenda->notulenPegawai)
                        <tr>
                            <td>Notulen</td>
                            <td>:</td>
                            <td>{{ $agenda->notulenPegawai->nama }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
            
            @if($agenda->keterangan)
            <div class="row mt-3">
                <div class="col-12">
                    <p class="indent">{{ $agenda->keterangan }}</p>
                </div>
            </div>
            @endif
            
            <div class="row mt-4">
                <div class="col-12">
                    <p class="indent">Demikian surat undangan ini kami sampaikan, atas perhatian dan kehadiran Bapak/Ibu, kami ucapkan terima kasih.</p>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <p>Wassalamu 'alaikum Warahmatullahi Wabarakaatuh</p>
                </div>
            </div>
            
            <div class="row signature-section">
                <div class="col-12 text-center">
                    <table class="table-borderless" style="width: 100%;">
                        <tr>
                            <td style="width: 50%;"></td>
                            <td style="width: 50%; text-align: center;">
                                <p>Sidoarjo, {{ $tanggal_dibuat }}</p>
                                <p>Pimpinan Rapat</p>
                                <br><br><br>
                                <p><strong>{{ $agenda->pimpinan->nama ?? '-' }}</strong></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Lampiran: Daftar Yang Terundang -->
            <div style="page-break-before: always;">
                <div class="row">
                    <div class="col-12">
                        <h4 class="text-center text-decoration-underline">LAMPIRAN</h4>
                        <p class="text-center"><strong>Daftar Yang Terundang</strong></p>
                        <p class="text-center">Surat Undangan Nomor: {{ $agenda->nomor_agenda }}</p>
                        <br>
                    </div>
                </div>
                
                @if($is_all)
                <div class="row">
                    <div class="col-12">
                        <p><strong>Semua Pegawai Aktif ({{ $jumlah_terundang }} orang)</strong></p>
                    </div>
                </div>
                @endif
                
                @if(count($list_terundang) > 0)
                <div class="row">
                    <div class="col-12">
                        <table class="table-bordered">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">No</th>
                                    <th style="width: 15%;">NIK</th>
                                    <th style="width: 30%;">Nama</th>
                                    <th style="width: 25%;">Jabatan</th>
                                    <th style="width: 25%;">Unit Kerja</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $itemsPerColumn = ceil(count($list_terundang) / 2);
                                    $column1 = array_slice($list_terundang, 0, $itemsPerColumn);
                                    $column2 = array_slice($list_terundang, $itemsPerColumn);
                                @endphp
                                
                                @if(count($list_terundang) > 20)
                                    {{-- Tampilkan 2 kolom jika lebih dari 20 --}}
                                    <tr>
                                        <td colspan="5" style="padding: 0; border: none;">
                                            <table style="width: 100%; border-collapse: collapse;">
                                                <tr>
                                                    <td style="width: 48%; padding: 0; vertical-align: top;">
                                                        <table class="table-bordered" style="width: 100%; margin: 0;">
                                                            <thead>
                                                                <tr>
                                                                    <th style="width: 10%;">No</th>
                                                                    <th style="width: 20%;">NIK</th>
                                                                    <th style="width: 70%;">Nama</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($column1 as $index => $item)
                                                                <tr>
                                                                    <td style="text-align: center;">{{ $index + 1 }}</td>
                                                                    <td>{{ $item['nik'] }}</td>
                                                                    <td>{{ $item['nama'] }}</td>
                                                                </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                    <td style="width: 4%;"></td>
                                                    <td style="width: 48%; padding: 0; vertical-align: top;">
                                                        <table class="table-bordered" style="width: 100%; margin: 0;">
                                                            <thead>
                                                                <tr>
                                                                    <th style="width: 10%;">No</th>
                                                                    <th style="width: 20%;">NIK</th>
                                                                    <th style="width: 70%;">Nama</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($column2 as $index => $item)
                                                                <tr>
                                                                    <td style="text-align: center;">{{ $index + $itemsPerColumn + 1 }}</td>
                                                                    <td>{{ $item['nik'] }}</td>
                                                                    <td>{{ $item['nama'] }}</td>
                                                                </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @else
                                    {{-- Tampilkan 1 kolom jika kurang dari 20 --}}
                                    @foreach($list_terundang as $index => $item)
                                    <tr>
                                        <td style="text-align: center;">{{ $index + 1 }}</td>
                                        <td>{{ $item['nik'] }}</td>
                                        <td>{{ $item['nama'] }}</td>
                                        <td>{{ $item['jabatan'] }}</td>
                                        <td>{{ $item['unit'] }}</td>
                                    </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
