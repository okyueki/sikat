<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        @page {
            size: A4;
            margin: 0 1.1cm 1.6cm 1.1cm;
        }
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 0.9;
        }
        /* ===================================================== */
        /* FOOTER ALAMAT — muncul otomatis di SETIAP halaman.     */
        /* dompdf mendukung position: fixed untuk elemen yang     */
        /* diulang di tiap halaman (beda dari CSS Paged Media     */
        /* standar @page margin box yang tidak didukung dompdf).  */
        /* ===================================================== */
        .page-footer {
            position: fixed;
            bottom: -1.3cm;
            left: 0;
            right: 0;
            width: 100%;
            text-align: center;
            font-size: 9pt;
            font-family: 'Times New Roman', serif;
            color: #333333;
            border-top: 1px solid #888888;
            padding-top: 0.2cm;
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
            line-height: 1.5;
        }
        .table-borderless {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
            border-collapse: collapse;
        }

        /* Spasi antar kolom */
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
        /* PENTING untuk dompdf: th harus diulang di setiap halaman baru,
           dan baris (tr) tidak boleh terpotong di tengah antar halaman */
        .table-bordered thead {
            display: table-header-group;
        }
        .table-bordered tr {
            page-break-inside: avoid;
        }
        .indent {
            text-indent: 1cm;
        }
        .signature-section {
            margin-top: 1.5rem;
        }
        /* Pemisah halaman lampiran: elemen kosong terpisah,
           BUKAN membungkus judul+tabel jadi satu blok */
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="page-footer">
        Jl. Raya Kenongo No.14, RT.01/RW.01, Kenongo, Kec. Tulangan, Kabupaten Sidoarjo, Jawa Timur 61273
    </div>
    <div class="a4">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <img width="200" src="{{ $kop_surat }}" alt="Logo" style="display: block;">
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <table class="table-borderless">
                        <tr>
                            <td style="width: 50px;">Nomor</td>
                            <td style="width: 10px;">:</td>
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
            
            <div class="row">
                <div class="col-12 text-right">
                    <p>Sidoarjo, {{ $tanggal_dibuat }}</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    @foreach(preg_split('/\r\n|\r|\n/', $kepada_undangan) as $index => $line)
                        @if(trim($line) !== '')
                            <p @if($index > 0) class="indent" @endif>{{ $line }}</p>
                        @endif
                    @endforeach
                </div>
            </div>
            
            <div class="row mt-1">
                <div class="col-12">
                    <p>assalamualaikum warahmatullahi wabarakatuh,</p>
                </div>
            </div>
            
            <div class="row mt-1">
                <div class="col-12">
                    <p class="indent">
                        Sehubungan dengan kegiatan yang akan dilaksanakan
                        @if($agenda->deskripsi), {{ $agenda->deskripsi }}@endif, dengan ini kami mengundang Bapak/Ibu untuk hadir dalam acara:
                    </p>
                </div>
            </div>
            
            <div class="row mt-1">
                <div class="col-12">
                    <table class="table-borderless">
                        <tr>
                            <td style="width: 150px;">Acara / Event</td>
                            <td style="width: 20px;">:</td>
                            <td><strong>{{ $agenda->judul }}</strong></td>
                        </tr>
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
                        
                    </table>
                </div>
            </div>
            
            <div class="row mt-1">
                <div class="col-12">
                    <p class="indent">Demikian surat undangan ini kami sampaikan, atas perhatian dan kehadiran Bapak/Ibu, kami ucapkan terima kasih.</p>
                </div>
            </div>
            
            <div class="row mt-1">
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
                                <br>
                                @if(!empty($barcode_pimpinan))
                                    <div style="margin-bottom: 4px; text-align: center;">
                                        <img src="{{ $barcode_pimpinan }}" alt="Tanda Tangan Digital" style="width: 100px; height: 100px; display: block; margin: 0 auto 2px auto;">
                                    </div>
                                @else
                                    <br>
                                @endif
                                <p><strong>{{ $agenda->pimpinan->nama ?? '-' }}</strong></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- ============================================ -->
            <!-- Lampiran: Daftar Yang Terundang               -->
            <!-- PERBAIKAN: page-break ditaruh di div kosong   -->
            <!-- terpisah, bukan membungkus judul + tabel jadi -->
            <!-- satu blok besar. Ini mencegah dompdf "menarik"-->
            <!-- seluruh blok (termasuk tabel panjang) turun   -->
            <!-- ke halaman berikutnya.                        -->
            <!-- ============================================ -->
            <div class="page-break"></div>

            <div class="row mt-3">
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
                    <!-- ============================================ -->
                    <!-- PERBAIKAN: selalu 1 kolom, 1 tabel utuh.      -->
                    <!-- dompdf TIDAK BISA memecah halaman di dalam   -->
                    <!-- <td> (nested table 2 kolom), tapi BISA       -->
                    <!-- memecah antar <tr> pada tabel biasa.         -->
                    <!-- Maka daftar nama, seberapa panjang pun,      -->
                    <!-- akan mengalir rapi ke halaman selanjutnya    -->
                    <!-- dengan header tabel ikut terulang otomatis.  -->
                    <!-- ============================================ -->
                    <table class="table-bordered" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 8%; text-align: center;">No</th>
                                <th style="width: 50%;">Nama</th>
                                <th style="width: 42%;">Unit Kerja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($list_terundang as $index => $item)
                            <tr>
                                <td style="text-align: center;">{{ $index + 1 }}</td>
                                <td>{{ $item['nama'] }}</td>
                                <td>{{ $item['unit'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</body>
</html>