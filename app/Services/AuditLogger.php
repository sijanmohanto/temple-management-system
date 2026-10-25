<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Record an audit event.
     */
    public static function log(string $action, string $description, ?Model $model = null, array $properties = []): AuditLog
    {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->getKey(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'properties' => ! empty($properties) ? $properties : null,
        ]);
    }
}
