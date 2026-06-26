<?php

/**
 * Role-based access helper for PrimaZentra.
 * All functions read the 'role' key from the active CI4 session.
 */

if (!function_exists('pz_role')) {
    function pz_role(): string {
        return (string)(session()->get('role') ?? '');
    }
}

if (!function_exists('pz_is')) {
    /** Check if the current user has any of the given roles. */
    function pz_is(string ...$roles): bool {
        return in_array(pz_role(), $roles, true);
    }
}

if (!function_exists('pz_can')) {
    /**
     * Returns true if the logged-in user is allowed to perform $action.
     *
     * Roles: Administrator | Operations Manager | Project Manager | Site Engineer
     */
    function pz_can(string $action): bool {
        $r = pz_role();
        $admin  = 'Administrator';
        $ops    = 'Operations Manager';
        $pm     = 'Project Manager';

        $se = 'Site Engineer';

        return match ($action) {
            // ── Navigation / summary metrics ─────────────────────────────
            'view_summary_metrics',
            'view_projects',
            'view_resources',
            'view_requests'           => in_array($r, [$admin, $ops, $pm, $se], true),
            'view_staff'              => in_array($r, [$admin, $ops], true),

            // ── Projects ──────────────────────────────────────────────────
            'create_project'          => in_array($r, [$admin, $ops], true),
            'edit_project'            => in_array($r, [$admin, $ops], true),
            'delete_project'          => in_array($r, [$admin, $ops], true),
            'archive_project'         => in_array($r, [$admin, $ops], true),
            'manage_milestones'       => in_array($r, [$admin, $ops, $pm], true),

            // ── Reports ───────────────────────────────────────────────────
            'submit_daily_report'     => in_array($r, [$admin, $se], true),
            'view_daily_reports'      => true,
            'create_emergency_report'  => in_array($r, [$admin, $pm], true),
            'view_emergency_reports'   => true,
            'resolve_emergency_report' => in_array($r, [$admin, $ops, $pm], true),

            // ── Staff ─────────────────────────────────────────────────────
            'create_staff'            => $r === $admin,
            'edit_staff'              => in_array($r, [$admin, $ops], true),
            'archive_staff'           => in_array($r, [$admin, $ops], true),
            'delete_staff'            => in_array($r, [$admin, $ops], true),

            // ── Resources ─────────────────────────────────────────────────
            'add_edit_resources'      => in_array($r, [$admin, $ops], true),
            'import_resources'        => in_array($r, [$admin, $ops], true),
            'export_resources'        => true,
            'submit_resource_request' => true,
            'transfer_resource'       => true,

            // ── Resource requests ─────────────────────────────────────────
            'approve_request'         => in_array($r, [$admin, $ops, $pm], true),
            'reject_request'          => in_array($r, [$admin, $ops, $pm], true),
            'bypass_secondary'        => $r === $admin,
            'archive_request'         => in_array($r, [$admin, $ops], true),
            'delete_request'          => in_array($r, [$admin, $ops], true),

            // ── Legacy alias ──────────────────────────────────────────────
            'edit_progress_report'    => in_array($r, [$admin, $se], true),

            default => false,
        };
    }
}
