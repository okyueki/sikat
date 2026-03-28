<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;

class AuditTrailController extends Controller
{
    /**
     * Display a listing of the audit trails.
     */
    public function index(Request $request)
    {
        $title = 'Audit Trail / Activity Log';
        
        if ($request->ajax()) {
            $query = AuditTrail::query()->orderBy('created_at', 'desc');

            // Filter by action
            if ($request->filled('action')) {
                $query->where('action', $request->action);
            }

            // Filter by module
            if ($request->filled('module')) {
                $query->where('module', $request->module);
            }

            // Filter by user
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by date range
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('created_at', [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay()
                ]);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('action_badge', function($row) {
                    $badgeClass = match($row->action) {
                        'create' => 'bg-success',
                        'update' => 'bg-primary',
                        'delete' => 'bg-danger',
                        'view' => 'bg-info',
                        'login' => 'bg-warning',
                        'logout' => 'bg-secondary',
                        default => 'bg-secondary'
                    };
                    return '<span class="badge ' . $badgeClass . '">' . strtoupper($row->action) . '</span>';
                })
                ->addColumn('module_badge', function($row) {
                    return '<span class="badge bg-dark">' . ucfirst($row->module) . '</span>';
                })
                ->addColumn('user_info', function($row) {
                    return $row->username ?? 'System';
                })
                ->addColumn('date_formatted', function($row) {
                    return $row->created_at->format('d M Y H:i:s');
                })
                ->addColumn('changes_summary', function($row) {
                    if (!$row->old_values && !$row->new_values) {
                        return '-';
                    }

                    $html = '';
                    if ($row->old_values && $row->action === 'update') {
                        $html .= '<strong>Old:</strong> ' . $this->formatChanges($row->old_values) . '<br>';
                    }
                    if ($row->new_values) {
                        $html .= '<strong>New:</strong> ' . $this->formatChanges($row->new_values);
                    }
                    return $html;
                })
                ->rawColumns(['action_badge', 'module_badge', 'changes_summary'])
                ->make(true);
        }

        // Get unique modules for filter
        $modules = AuditTrail::distinct()->pluck('module')->filter();

        // Get unique actions for filter
        $actions = ['create', 'update', 'delete', 'view', 'login', 'logout'];

        return view('audit_trail.index', compact('title', 'modules', 'actions'));
    }

    /**
     * Display the specified audit trail.
     */
    public function show($id)
    {
        $auditTrail = AuditTrail::findOrFail($id);
        $title = 'Detail Audit Trail';

        return view('audit_trail.show', compact('auditTrail', 'title'));
    }

    /**
     * Show filter options for audit trail
     */
    public function filter()
    {
        $title = 'Filter Audit Trail';
        $modules = AuditTrail::distinct()->pluck('module')->filter();
        $users = \App\Models\User::select('id', 'name', 'username')->get();

        return view('audit_trail.filter', compact('title', 'modules', 'users'));
    }

    /**
     * Export audit trail to CSV
     */
    public function export(Request $request)
    {
        $query = AuditTrail::query()->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        }

        $records = $query->get();

        $filename = 'audit_trail_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($records) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'User', 'Action', 'Module', 'Table', 'Record ID', 'Description', 'IP Address', 'URL', 'Method', 'Created At', 'Old Values', 'New Values']);

            foreach ($records as $record) {
                fputcsv($file, [
                    $record->id,
                    $record->username,
                    $record->action,
                    $record->module,
                    $record->table_name,
                    $record->record_id,
                    $record->description,
                    $record->ip_address,
                    $record->url,
                    $record->method,
                    $record->created_at->format('Y-m-d H:i:s'),
                    json_encode($record->old_values),
                    json_encode($record->new_values),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Delete old audit trails
     */
    public function cleanup(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        $cutoffDate = Carbon::now()->subDays($request->days);
        $deletedCount = AuditTrail::where('created_at', '<', $cutoffDate)->delete();

        return redirect()->route('audit-trail.index')
            ->with('success', "{$deletedCount} audit trail records older than {$request->days} days have been deleted.");
    }

    /**
     * Format changes array to readable string
     */
    private function formatChanges($changes)
    {
        if (!$changes) {
            return '-';
        }

        $formatted = [];
        foreach ($changes as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            // Truncate long values
            $value = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
            $formatted[] = "{$key}: {$value}";
        }

        return implode(', ', $formatted);
    }
}
