<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AuditTrail;

class AuditTrailMiddleware
{
    /**
     * Modules yang perlu di-audit
     */
    protected $auditedModules = [
        'ijin' => ['index', 'create', 'store', 'edit', 'update', 'destroy'],
        'cuti' => ['index', 'create', 'store', 'edit', 'update', 'destroy'],
        'pengajuan_libur' => ['index', 'show', 'create', 'store', 'edit', 'update', 'destroy'],
        'absensi' => ['index', 'create', 'store', 'edit', 'update', 'destroy'],
        'api/absensi' => ['jadwal-hari-ini', 'status-hari-ini', 'submit'],
        'api/login' => ['login'],
        'api/logout' => ['logout'],
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Get route info
        $route = $request->route();
        if (!$route) {
            return $response;
        }

        $routeName = $route->getName();
        $routeAction = $route->getActionMethod();
        $module = $this->getModuleFromRoute($routeName);

        // Check if this route should be audited
        if ($this->shouldAudit($module, $routeAction, $request)) {
            $this->logAudit($request, $response, $module, $routeAction);
        }

        return $response;
    }

    /**
     * Get module name from route
     */
    protected function getModuleFromRoute($routeName)
    {
        if (!$routeName) {
            return null;
        }

        // Extract module from route name (e.g., ijin.index -> ijin)
        $parts = explode('.', $routeName);
        $module = $parts[0] ?? null;

        // Check for API routes
        if (str_starts_with($routeName, 'api.')) {
            return 'api/' . $parts[1] ?? $module;
        }

        return $module;
    }

    /**
     * Check if this request should be audited
     */
    protected function shouldAudit($module, $action, Request $request)
    {
        if (!$module || !array_key_exists($module, $this->auditedModules)) {
            return false;
        }

        // Only audit specific HTTP methods
        $method = $request->method();
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            // Also audit GET for view actions
            if ($method === 'GET' && in_array($action, ['show', 'edit', 'index'])) {
                return true;
            }
            return false;
        }

        return true;
    }

    /**
     * Log the audit trail
     */
    protected function logAudit(Request $request, $response, $module, $action)
    {
        $user = auth()->user();
        $method = $request->method();

        // Determine action type
        $actionType = $this->getActionType($method, $action);

        // Get request data (filtered for security)
        $inputData = $this->filterSensitiveData($request->all());

        // Get old values if it's an update
        $oldValues = null;
        if (in_array($action, ['update', 'destroy']) && $request->route()) {
            $recordId = $request->route()->parameter('id');
            if ($recordId) {
                $oldValues = $this->getOldValues($module, $recordId);
            }
        }

        // Create description
        $description = $this->createDescription($actionType, $module, $request);

        AuditTrail::log(
            $actionType,
            $module,
            $description,
            $request->route()?->parameter('id'),
            $oldValues,
            $inputData,
            $this->getTableName($module)
        );
    }

    /**
     * Get action type from HTTP method and route action
     */
    protected function getActionType($method, $action)
    {
        return match (true) {
            in_array($action, ['store', 'create']) || $method === 'POST' => 'create',
            in_array($action, ['update', 'edit']) || in_array($method, ['PUT', 'PATCH']) => 'update',
            in_array($action, ['destroy', 'delete']) || $method === 'DELETE' => 'delete',
            in_array($action, ['index', 'show']) || $method === 'GET' => 'view',
            default => 'other'
        };
    }

    /**
     * Filter sensitive data from request
     */
    protected function filterSensitiveData(array $data)
    {
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'secret', 'credit_card', 'cvv'];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $data[$key] = '***REDACTED***';
            }
        }

        return $data;
    }

    /**
     * Get old values for update/delete
     */
    protected function getOldValues($module, $recordId)
    {
        $tableName = $this->getTableName($module);
        if (!$tableName) {
            return null;
        }

        try {
            $record = \DB::table($tableName)->where('id', $recordId)->first();
            return $record ? (array) $record : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get table name from module
     */
    protected function getTableName($module)
    {
        $mapping = [
            'ijin' => 'pengajuan_libur',
            'cuti' => 'pengajuan_libur',
            'pengajuan_libur' => 'pengajuan_libur',
            'absensi' => 'rekap_presensi',
            'api/absensi' => 'temporary_presensi',
        ];

        return $mapping[$module] ?? null;
    }

    /**
     * Create description for the audit log
     */
    protected function createDescription($actionType, $module, Request $request)
    {
        $user = auth()->user()?->name ?? 'Unknown';
        $recordId = $request->route()?->parameter('id');

        return match ($actionType) {
            'create' => "{$user} membuat data {$module} baru",
            'update' => "{$user} mengubah data {$module} ID: {$recordId}",
            'delete' => "{$user} menghapus data {$module} ID: {$recordId}",
            'view' => "{$user} melihat data {$module}",
            default => "{$user} melakukan aksi pada {$module}",
        };
    }
}
