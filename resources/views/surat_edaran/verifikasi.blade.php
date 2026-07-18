<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link href="{{ asset('backend/assets/libs/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4 py-md-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5">
                        <h4 class="mb-3">{{ $title }}</h4>

                        @if($surat_edaran->tanggal_ditandatangani)
                            <div class="alert alert-success">
                                <strong>Dokumen SAH.</strong>
                                Ditandatangani pada {{ $surat_edaran->tanggal_ditandatangani->format('d-m-Y H:i:s') }}.
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <strong>Dokumen belum sah.</strong>
                                Dokumen ini masih berstatus draft dan belum ditandatangani.
                            </div>
                        @endif

                        <div class="row mb-2">
                            <div class="col-md-4 text-muted">Judul Surat</div>
                            <div class="col-md-8 fw-semibold">{{ $surat_edaran->judul_surat }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 text-muted">Nomor Surat</div>
                            <div class="col-md-8">{{ $surat_edaran->nomor_surat ?? '-' }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 text-muted">Tanggal Dokumen</div>
                            <div class="col-md-8">{{ $surat_edaran->tanggal ? $surat_edaran->tanggal->format('d-m-Y') : '-' }}</div>
                        </div>
                        @if(!empty($hasMasaBerlaku) && $surat_edaran->tanggal_mulai_berlaku && $surat_edaran->tanggal_berakhir_berlaku)
                        <div class="row mb-2">
                            <div class="col-md-4 text-muted">Masa Berlaku</div>
                            <div class="col-md-8">
                                {{ $surat_edaran->tanggal_mulai_berlaku->format('d-m-Y') }} – {{ $surat_edaran->tanggal_berakhir_berlaku->format('d-m-Y') }}
                                @php($masaStatus = method_exists($surat_edaran, 'masaBerlakuStatus') ? $surat_edaran->masaBerlakuStatus() : 'unknown')
                                @if($masaStatus === 'aktif')
                                    <span class="badge bg-success ms-1">Aktif</span>
                                @elseif($masaStatus === 'belum')
                                    <span class="badge bg-info text-dark ms-1">Belum berlaku</span>
                                @elseif($masaStatus === 'berakhir')
                                    <span class="badge bg-secondary ms-1">Berakhir</span>
                                @endif
                            </div>
                        </div>
                        @endif
                        <div class="row mb-2">
                            <div class="col-md-4 text-muted">Penandatangan</div>
                            <div class="col-md-8">
                                {{ $surat_edaran->penandatangan?->nama ?? '-' }}
                                @if($surat_edaran->penandatangan?->nik)
                                    ({{ $surat_edaran->penandatangan->nik }})
                                @endif
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 text-muted">Dibuat Oleh</div>
                            <div class="col-md-8">
                                {{ ($createdLog && !empty($createdLog->username)) ? ($userDisplayByUsername[$createdLog->username] ?? $createdLog->username) : '-' }}
                                @if($createdLog?->created_at)
                                    <span class="text-muted">({{ $createdLog->created_at->format('d-m-Y H:i:s') }})</span>
                                @endif
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-4 text-muted">Upload/Revisi PDF Terakhir</div>
                            <div class="col-md-8">
                                {{ ($uploadLog && !empty($uploadLog->username)) ? ($userDisplayByUsername[$uploadLog->username] ?? $uploadLog->username) : '-' }}
                                @if($uploadLog?->created_at)
                                    <span class="text-muted">({{ $uploadLog->created_at->format('d-m-Y H:i:s') }})</span>
                                @endif
                            </div>
                        </div>

                        <h6 class="mb-3">Audit Trail Dokumen</h6>
                        @if($auditTrails->isEmpty())
                            <div class="text-muted">Belum ada data audit trail untuk dokumen ini.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th style="width: 60px;">No</th>
                                            <th style="width: 160px;">Waktu</th>
                                            <th style="width: 110px;">Aksi</th>
                                            <th style="width: 160px;">Pengguna</th>
                                            <th>Deskripsi</th>
                                            <th style="width: 90px;">Method</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($auditTrails as $idx => $log)
                                            <tr>
                                                <td>{{ $idx + 1 }}</td>
                                                <td>{{ optional($log->created_at)->format('d-m-Y H:i:s') }}</td>
                                                <td><span class="badge bg-secondary">{{ strtoupper($log->action) }}</span></td>
                                                <td>{{ !empty($log->username) ? ($userDisplayByUsername[$log->username] ?? $log->username) : '-' }}</td>
                                                <td>{{ $log->description ?? '-' }}</td>
                                                <td>{{ $log->method ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

