@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle . $title : $title)

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <h5 class="card-title">Audit Trail / Activity Log</h5>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#filterModal">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <button type="button" class="btn btn-sm btn-success" onclick="exportData()">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </div>
            </div>
            <div class="card-body">
                @if ($message = Session::get('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ $message }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="audit-trail-table">
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th width="100">Date</th>
                                <th width="80">User</th>
                                <th width="80">Action</th>
                                <th width="80">Module</th>
                                <th>Description</th>
                                <th>Changes</th>
                                <th width="100">IP</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">Filter Audit Trail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="filterForm">
                    <div class="mb-3">
                        <label for="filter_action" class="form-label">Action</label>
                        <select class="form-select" id="filter_action" name="action">
                            <option value="">All Actions</option>
                            @foreach($actions as $action)
                                <option value="{{ $action }}">{{ ucfirst($action) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="filter_module" class="form-label">Module</label>
                        <select class="form-select" id="filter_module" name="module">
                            <option value="">All Modules</option>
                            @foreach($modules as $module)
                                <option value="{{ $module }}">{{ ucfirst($module) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="filter_start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="filter_start_date" name="start_date">
                    </div>
                    <div class="mb-3">
                        <label for="filter_end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="filter_end_date" name="end_date">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="applyFilter()">Apply Filter</button>
                <button type="button" class="btn btn-warning" onclick="resetFilter()">Reset</button>
            </div>
        </div>
    </div>
</div>

<script>
    let table;

    $(function () {
        table = $('#audit-trail-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("audit-trail.index") }}',
                data: function (d) {
                    d.action = $('#filter_action').val();
                    d.module = $('#filter_module').val();
                    d.start_date = $('#filter_start_date').val();
                    d.end_date = $('#filter_end_date').val();
                }
            },
            columns: [
                { data: null, searchable: false, orderable: false, render: function (data, type, row, meta) {
                    return meta.row + 1;
                }},
                { data: 'date_formatted', name: 'created_at', width: '150px' },
                { data: 'user_info', name: 'username', width: '100px' },
                { data: 'action_badge', name: 'action', orderable: true, searchable: true, width: '80px' },
                { data: 'module_badge', name: 'module', orderable: true, searchable: true, width: '80px' },
                { data: 'description', name: 'description' },
                { data: 'changes_summary', name: 'changes_summary', orderable: false, searchable: false },
                { data: 'ip_address', name: 'ip_address', width: '100px' }
            ],
            order: [[1, 'desc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]]
        });
    });

    function applyFilter() {
        table.ajax.reload();
        $('#filterModal').modal('hide');
    }

    function resetFilter() {
        $('#filterForm')[0].reset();
        table.ajax.reload();
        $('#filterModal').modal('hide');
    }

    function exportData() {
        const params = new URLSearchParams({
            action: $('#filter_action').val(),
            module: $('#filter_module').val(),
            start_date: $('#filter_start_date').val(),
            end_date: $('#filter_end_date').val()
        });
        window.location.href = '{{ route("audit-trail.export") }}?' + params.toString();
    }
</script>
@endsection
