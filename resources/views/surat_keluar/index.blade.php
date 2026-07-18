@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle . $title :  $title)

@section('content')

        <!-- end page title -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        @unless(config('surat.disable_keluar_create'))
                        <a href="{{ route('surat_keluar.create') }}" class="btn btn-success me-2">
                            Create Surat Keluar
                        </a>
                        @endunless
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Template Surat
                            </button>
                            <ul class="dropdown-menu">
                                @foreach($templateSurat as $ts)
                                <li><a class="dropdown-item" href="{{ asset('storage/' . $ts->file_template) }}">{{ $ts->nama_template }}</a></li>
                                @endforeach        
                            </ul>
                        </div>
                    </div>
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
                    </div>
                </div>
            </div>
        </div>

<!-- End Page-content -->
@include('surat_keluar.partials.index-script')
@endsection