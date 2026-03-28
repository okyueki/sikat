<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditTrail extends Model
{
    protected $table = 'audit_trails';

    protected $fillable = [
        'user_id',
        'username',
        'action',
        'module',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
        'description',
        'ip_address',
        'user_agent',
        'url',
        'method',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Get the user that performed the action
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Log audit trail
     */
    public static function log($action, $module, $description = null, $recordId = null, $oldValues = null, $newValues = null, $tableName = null)
    {
        $user = auth()->user();
        $request = request();

        return self::create([
            'user_id' => $user?->id,
            'username' => $user?->username ?? ($user?->name ?? 'system'),
            'action' => $action,
            'module' => $module,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->header('User-Agent'),
            'url' => substr($request?->fullUrl() ?? '', 0, 255),
            'method' => $request?->method(),
        ]);
    }

    /**
     * Log create action
     */
    public static function logCreate($module, $tableName, $recordId, $newValues, $description = null)
    {
        return self::log('create', $module, $description, $recordId, null, $newValues, $tableName);
    }

    /**
     * Log update action
     */
    public static function logUpdate($module, $tableName, $recordId, $oldValues, $newValues, $description = null)
    {
        return self::log('update', $module, $description, $recordId, $oldValues, $newValues, $tableName);
    }

    /**
     * Log delete action
     */
    public static function logDelete($module, $tableName, $recordId, $oldValues, $description = null)
    {
        return self::log('delete', $module, $description, $recordId, $oldValues, null, $tableName);
    }

    /**
     * Log view action
     */
    public static function logView($module, $tableName, $recordId = null, $description = null)
    {
        return self::log('view', $module, $description, $recordId, null, null, $tableName);
    }

    /**
     * Scope for filtering by action
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for filtering by module
     */
    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope for filtering by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
