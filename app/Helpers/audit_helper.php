<?php

/**
 * Centralized audit trail helper for PrimaZentra.
 * Writes to the `audit_logs` Firestore collection via AuditLogService.
 */

if (!function_exists('audit_log')) {
    /**
     * Record an audit trail entry. Never throws — a logging failure must
     * not block the business action that triggered it.
     *
     * @param string $action   e.g. 'project.create', 'auth.login_failed'
     * @param string $module   e.g. 'project', 'manpower', 'resource', 'staff', 'request', 'report', 'auth', 'profile'
     * @param array  $details  free-form context (before/after values, reason, etc.)
     */
    function audit_log(
        string $action,
        string $module,
        ?string $targetId = null,
        ?string $targetName = null,
        array $details = [],
        string $status = 'success',
        ?array $actorOverride = null
    ): void {
        try {
            (new \App\Services\AuditLogService())->log(
                $action, $module, $targetId, $targetName, $details, $status, $actorOverride
            );
        } catch (\Throwable $e) {
            log_message('error', 'audit_log failed: ' . $e->getMessage());
        }
    }
}
