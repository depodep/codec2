<?php

namespace App\Traits;

use App\Models\AuditLog;

trait RecordAudit
{
    public function logActivity(
        string $action, 
        string $module, 
        string $status = 'Success', 
        ?string $description = null
    ): AuditLog {
        return AuditLog::record($action, $module, $status, $description);
    }
}