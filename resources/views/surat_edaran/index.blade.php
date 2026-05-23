@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle . $title : $title)

@section('content')
@php($routePrefix = $routePrefix ?? 'surat_edaran')
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ $title }}</h5>
                <a href="{{ route($routePrefix . '.create') }}" class="btn btn-success btn-sm">Tambah {{ !empty($isSpo) ? 'SPO' : 'Surat Edaran' }}</a>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Judul Surat</th>
                                <th>Nomor</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Yang menyetujui</th>
                                @if(!empty($isSpo))
                                    <th>Petugas Upload</th>
                                    <th>Dept Upload</th>
                                @endif
                                <th width="220">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr>
                                    <td>{{ $items->firstItem() + $loop->index }}</td>
                                    <td>{{ $item->judul_surat }}</td>
                                    <td>{{ $item->nomor_surat ?? '-' }}</td>
                                    <td>{{ $item->tanggal ? $item->tanggal->format('d-m-Y') : '-' }}</td>
                                    <td>
                                        @if($item->tanggal_ditandatangani)
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-success me-2"><i class="fe fe-check-circle"></i> Sah</span>
                                                <small class="text-muted">{{ $item->tanggal_ditandatangani->format('d-m-Y H:i') }}</small>
                                            </div>
                                        @else
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-warning text-dark me-2"><i class="fe fe-clock"></i> Draft</span>
                                                <small class="text-muted">Belum ditandatangani</small>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $item->penandatangan ? $item->penandatangan->nama : '-' }}</td>
                                    @if(!empty($isSpo))
                                        <td>{{ $item->uploaderPegawai?->nama ?? '-' }}</td>
                                        <td>{{ ($departemenMap ?? [])[$item->departemen_upload_id ?? ''] ?? '-' }}</td>
                                    @endif
                                    <td>
                                        <a href="{{ route($routePrefix . '.show', $item) }}" class="btn btn-primary btn-sm">Detail</a>
                                        @if($item->tanggal_ditandatangani)
                                            <a href="{{ route($routePrefix . '.show', $item) }}" class="btn btn-info btn-sm">Lihat TTD</a>
                                            <form action="{{ route($routePrefix . '.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus dokumen ini?{{ $item->tanggal_ditandatangani ? " Dokumen yang sudah ditandatangani akan dihapus permanen." : "" }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                            </form>
                                        @else
                                            <a href="{{ route($routePrefix . '.tandaTangani', $item) }}" class="btn btn-info btn-sm">Tanda tangani</a>
                                            <a href="{{ route($routePrefix . '.edit', $item) }}" class="btn btn-warning btn-sm">Edit</a>
                                            <form action="{{ route($routePrefix . '.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus draft dokumen ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">Hapus Draft</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ !empty($isSpo) ? 9 : 7 }}" class="text-center text-muted">Belum ada data {{ !empty($isSpo) ? 'SPO' : 'Surat Edaran' }}.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($items->hasPages())
                    <div class="d-flex justify-content-end mt-2">
                        {{ $items->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
