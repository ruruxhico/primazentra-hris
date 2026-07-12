<?php

namespace App\Services;

use App\Libraries\FirestoreService;
use Google\Cloud\Core\Timestamp;
use Google\Cloud\Firestore\FirestoreClient;

class AuditLogService
{
    private FirestoreClient $db;

    public function __construct()
    {
        $this->db = (FirestoreService::getInstance())->getDb();
    }

    /**
     * Record one audit entry. Actor identity is pulled from the active session
     * unless explicitly overridden (needed for auth.login_failed, where the
     * session role/name aren't set yet).
     */
    public function log(
        string $action,
        string $module,
        ?string $targetId = null,
        ?string $targetName = null,
        array $details = [],
        string $status = 'success',
        ?array $actorOverride = null
    ): string {
        $actorId   = $actorOverride['id']   ?? session()->get('userId')   ?? null;
        $actorName = $actorOverride['name'] ?? session()->get('userName') ?? 'System';
        $actorRole = $actorOverride['role'] ?? session()->get('role')    ?? 'System';

        $ref = $this->db->collection('audit_logs')->newDocument();
        $ref->set([
            'action'      => $action,
            'module'      => $module,
            'target_id'   => $targetId,
            'target_name' => $targetName,
            'details'     => (object) $details,
            'status'      => $status,
            'actor_id'    => $actorId,
            'actor_name'  => $actorName,
            'actor_role'  => $actorRole,
            'ip_address'  => service('request')->getIPAddress(),
            'created_at'  => new Timestamp(new \DateTime()),
        ]);
        return $ref->id();
    }

    /**
     * Fetch recent entries and filter/paginate in PHP rather than composing
     * Firestore where()+orderBy() combos, which require composite indexes
     * that can't be provisioned from here. Matches TrashService/ArchiveService.
     *
     * Role-scoping filters (enforced server-side by AuditLogs::index(), not
     * user-selectable): 'modules' (whitelist, e.g. a Project Manager's trail
     * restricted to project/report entries), 'exclude_modules' (blacklist,
     * e.g. hiding staff entries from Operations Manager), and 'target_ids'
     * (restrict to entries about specific records, e.g. a PM's own projects).
     */
    public function query(array $filters = [], int $scanLimit = 1000): array
    {
        $docs = $this->db->collection('audit_logs')
            ->orderBy('created_at', 'DESC')
            ->limit($scanLimit)
            ->documents();

        $items = [];
        foreach ($docs as $doc) {
            if (!$doc->exists()) continue;
            $d = $doc->data();

            if (!empty($filters['module']) && ($d['module'] ?? '') !== $filters['module']) continue;
            if (!empty($filters['modules']) && !in_array($d['module'] ?? '', $filters['modules'], true)) continue;
            if (!empty($filters['exclude_modules']) && in_array($d['module'] ?? '', $filters['exclude_modules'], true)) continue;
            if (!empty($filters['target_ids']) && !in_array((string)($d['target_id'] ?? ''), $filters['target_ids'], true)) continue;
            if (!empty($filters['action']) && ($d['action'] ?? '') !== $filters['action']) continue;
            if (!empty($filters['actor_role']) && ($d['actor_role'] ?? '') !== $filters['actor_role']) continue;
            if (!empty($filters['status']) && ($d['status'] ?? '') !== $filters['status']) continue;

            $ts = $this->toUnix($d['created_at'] ?? null);
            if (!empty($filters['date_from']) && $ts < $filters['date_from']) continue;
            if (!empty($filters['date_to'])   && $ts > $filters['date_to'])   continue;

            if (!empty($filters['q'])) {
                $hay = strtolower(
                    ($d['actor_name'] ?? '') . ' ' .
                    ($d['target_name'] ?? '') . ' ' .
                    ($d['action'] ?? '') . ' ' .
                    ($d['module'] ?? '')
                );
                if (!str_contains($hay, strtolower((string)$filters['q']))) continue;
            }

            $items[] = ['id' => $doc->id()] + $d;
        }

        return $items;
    }

    private function toUnix(mixed $value): int
    {
        if ($value instanceof Timestamp) {
            return $value->get()->getTimestamp();
        }
        return (int) $value;
    }

    /**
     * Turn a raw log row into a one-line human sentence for display
     * (e.g. "Approved request 'Cement: Warehouse → Site A'"), so the
     * audit log reads like a trail instead of a dump of raw field values.
     */
    public static function describe(array $log): string
    {
        $action  = (string) ($log['action'] ?? '');
        $module  = (string) ($log['module'] ?? '');
        $name    = trim((string) ($log['target_name'] ?? ''));
        $details = (array) ($log['details'] ?? []);
        $quoted  = $name !== '' ? "\"{$name}\"" : 'a record';

        $moduleLabel = [
            'project'  => 'project',
            'resource' => 'resource',
            'manpower' => 'manpower record',
            'staff'    => 'staff member',
            'request'  => 'request',
            'report'   => 'report',
        ][$module] ?? $module;

        switch ($action) {
            case 'auth.login':
                return !empty($details['forced_password_change'])
                    ? 'Logged in (temporary password, must change)'
                    : 'Logged in';
            case 'auth.login_failed':
                return 'Failed login attempt' . ($name !== '' ? " ({$name})" : '');
            case 'auth.logout':
                return 'Logged out';

            case 'manpower.create': return "Created manpower record {$quoted}";
            case 'manpower.update': return "Updated manpower record {$quoted}";
            case 'manpower.delete': return "Deleted manpower record {$quoted}";

            case 'staff.create':
                $role = $details['role'] ?? null;
                return "Created staff member {$quoted}" . ($role ? " as {$role}" : '');
            case 'staff.update':
                $role = $details['role'] ?? null;
                return "Updated staff member {$quoted}" . ($role ? " ({$role})" : '');
            case 'staff.resend_credentials':
                return "Resent login credentials to {$quoted}";

            case 'resource.create': return "Created resource {$quoted}";
            case 'resource.update': return "Updated resource {$quoted}";
            case 'resource.item_add':
                $qty = $details['quantity'] ?? null;
                return 'Added ' . ($qty ? "{$qty} unit(s)" : 'a unit')
                     . ($name !== '' ? " (serial {$quoted})" : '') . ' to resource';
            case 'resource.item_update':
                return "Updated unit {$quoted}";
            case 'resource.warehouse_location_save':
                return "Saved warehouse location {$quoted}";
            case 'resource.warehouse_location_delete':
                return "Removed warehouse location {$quoted}";
            case 'resource.warehouse_default_save':
                return "Set default warehouse location to {$quoted}";

            case 'project.create':
                $type = $details['project_type'] ?? null;
                return "Created project {$quoted}" . ($type ? " ({$type})" : '');
            case 'project.update':
                return "Updated project {$quoted}";
            case 'project.schedule_update':
                return "Updated schedule/milestones for project {$quoted}";
            case 'project.assign_resource':
                $rname = $details['name'] ?? 'a resource';
                $cat   = $details['category'] ?? ($details['type'] ?? '');
                return "Assigned {$rname}" . ($cat ? " ({$cat})" : '') . " to project {$quoted}";
            case 'project.unassign_resource':
                $type = $details['type'] ?? 'resource';
                return "Unassigned {$type} from project {$quoted}";
            case 'project.bulk_assign_manpower':
                return 'Bulk-assigned ' . (int) ($details['count'] ?? 0) . " worker(s) to project {$quoted}";
            case 'project.bulk_assign_equipment':
                return 'Bulk-assigned ' . (int) ($details['count'] ?? 0) . " equipment unit(s) to project {$quoted}";

            case 'request.create':
                return "Created request {$quoted}";
            case 'request.approve':
                return "Approved request {$quoted}" . (!empty($details['bypass_secondary']) ? ' (bypassed secondary approval)' : '');
            case 'request.reject':
                return "Rejected request {$quoted}";
            case 'request.cancel':
                return "Cancelled request {$quoted}";
            case 'request.approve_secondary':
                return "Gave secondary approval for request {$quoted}";
            case 'request.accept_delivery':
                return "Accepted delivery for request {$quoted}";

            case 'report.emergency_create':
                return "Filed emergency report for {$quoted}";
            case 'report.emergency_resolve':
                return "Resolved emergency report for {$quoted}";
            case 'report.daily_create':
                return "Filed daily report for {$quoted}";
        }

        // Centralized trash/archive actions all follow "{module}.{verb}" —
        // handle them (and any future unmapped action) with a generic phrase
        // built from the verb instead of hard-coding every module x verb pair.
        $verb = str_contains($action, '.') ? substr($action, strpos($action, '.') + 1) : $action;
        $verbPhrase = [
            'delete'           => 'Deleted',
            'archive'          => 'Archived',
            'restore'          => 'Restored',
            'permanent_delete' => 'Permanently deleted',
        ][$verb] ?? ucfirst(str_replace('_', ' ', $verb));

        return "{$verbPhrase} {$moduleLabel} {$quoted}";
    }
}
