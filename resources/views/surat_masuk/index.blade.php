@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle . $title :  $title)

@section('content')

        <!-- end page title -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body">
                    <a href="{{ route('surat_masuk.create') }}" class="btn btn-success waves-effect waves-light mb-3">Create Surat Masuk</a>
                   
                        @if ($message = Session::get('success'))
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: '{{ $message }}',
                                        confirmButtonText: 'OK'
                                    });
                                });
                            </script>
                        @endif
                        <div class="table-responsive">
                        <table class="table table-bordered" id="suratTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Pengirim</th>
                                    <th>Perihal</th>
                                    <th>Tanggal Surat</th>
                                    <th width="280px">Action</th>
                                </tr>
                            </thead>
                        </table>
                        </div>
                        <div class="col-xl-12">
                        <span class="badge bg-danger">Verifikasi Belum Dibaca : {{ $jumlahVerifikasiBelumDibaca }}</span>
                        <span class="badge bg-warning">Disposisi Belum Dibaca : {{ $jumlahDisposisiBelumDibaca }}</span>
                    </div>
                    </div>
                     
                </div>
            </div>
        </div>

<!-- End Page-content -->
@include('surat_masuk.partials.index-script')
@endsection