@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle . $title : $title)

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <a href="{{ route('memo_internal.create') }}" class="btn btn-success me-2">
                        Create Memo Internal
                    </a>
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
@include('memo_internal.partials.index-script')
@endsection
