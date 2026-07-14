<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$req              = $request ?? [];
$projects         = $projects ?? [];
$role             = $role ?? '';
$recommendedSites    = $recommended_sites ?? [];
$mcdaExcludedSites   = $mcda_excluded_sites ?? [];
$mcdaMlAvailable     = $mcda_ml_available   ?? false;
$releasedByProject   = $released_by_project ?? [];

$status    = $req['status'] ?? 'Pending';
$category  = $req['resource_category'] ?? '';
$resName   = $req['resource_name'] ?? '';
$sourceName= $req['source_site_name'] ?? 'To be assigned';
$destName  = $req['destination_site_name'] ?? '—';
$qty       = $req['requested_quantity'] ?? $req['quantity'] ?? 1;
$model     = $req['requested_model_name'] ?? $req['model'] ?? '';
$reqBy     = $req['requested_by_name'] ?? '—';
$reqRole   = $req['requested_by_role'] ?? '—';
$createdAt = $req['created_at_fmt'] ?? '—';
$reserved  = is_array($req['reserved_resources'] ?? null) ? $req['reserved_resources'] : [];

$badgeMap = [
    'pending'                    => 'pz-badge-pending',
    'pending secondary approval' => 'pz-badge-pending-secondary',
    'in-transit'                 => 'pz-badge-in-transit',
    'rejected'                   => 'pz-badge-rejected',
    'cancelled'                  => 'pz-badge-rejected',
    'completed'                  => 'pz-badge-completed',
];
$badgeCls = $badgeMap[strtolower($status)] ?? 'pz-badge-pending';

$currentUid          = (string)(session()->get('userId') ?? '');
$requesterUid        = (string)($req['requested_by_uid'] ?? '');
$isOwnRequest        = ($currentUid !== '' && $currentUid === $requesterUid);
$canApprove          = in_array($role, ['Administrator', 'Operations Manager', 'Project Manager'])
                       && (!$isOwnRequest || $role === 'Administrator');
$canAcceptDelivery   = ($canAcceptDelivery ?? false);
$isPending           = strtolower($status) === 'pending';
$isPendingSecondary  = strtolower($status) === 'pending secondary approval';
$isInTransit         = strtolower($status) === 'in-transit';
$canCancel           = $isOwnRequest && ($isPending || $isPendingSecondary);
$sourceKnown         = !empty($req['source_project_id']);
$primaryApproval     = is_array($req['primary_approval'] ?? null) ? $req['primary_approval'] : [];
$primaryApproverRole = $primaryApproval['role'] ?? $req['approved_by_role'] ?? '';
$sameRoleBlocked     = $isPendingSecondary && $primaryApproverRole && $role === $primaryApproverRole;
?>

<a href="<?= base_url('requests') ?>" class="pz-back-link">
    <span class="material-symbols-outlined">arrow_back</span> Requests
</a>

<?php if (session()->getFlashdata('success')): ?>
<div class="pz-alert pz-alert-success">
    <span class="material-symbols-outlined" style="font-size:1rem">check_circle</span>
    <?= session()->getFlashdata('success') ?>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="pz-alert pz-alert-error">
    <span class="material-symbols-outlined" style="font-size:1rem">error</span>
    <?= session()->getFlashdata('error') ?>
</div>
<?php endif; ?>

<!-- Header card -->
<div class="pz-card" style="border-left:4px solid #8a6420;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:.85rem;">
            <div style="width:44px;height:44px;border-radius:10px;background:#f5ecd8;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <span class="material-symbols-outlined" style="font-size:1.5rem;color:#8a6420;">
                    <?= strtolower($category) === 'manpower' ? 'groups' : (strtolower($category) === 'equipment' ? 'construction' : 'build') ?>
                </span>
            </div>
            <div>
                <div style="font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#a07830;margin-bottom:.1rem;"><?= esc($category) ?></div>
                <div style="font-size:1.15rem;font-weight:700;color:#2c1a00;line-height:1.2;"><?= esc($resName ?: $model ?: 'Resource Request') ?></div>
                <?php $unitSerial = $req['_unit_serial'] ?? ''; ?>
                <?php if ($unitSerial !== ''): ?>
                <div style="font-size:.78rem;color:#9a7b3a;margin-top:.15rem;">Serial #: <?= esc($unitSerial) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <span id="pzReqStatusBadge" class="pz-badge <?= $badgeCls ?>" style="font-size:.78rem;padding:.35rem .9rem;"><?= esc(strtoupper($status)) ?></span>
    </div>
</div>

<!-- Request Details card -->
<?php
$primaryName = $primaryApproval['name'] ?? $req['approved_by_name'] ?? '';
$primaryRole = $primaryApproval['role'] ?? $req['approved_by_role'] ?? '';
$primaryAt   = $primaryApproval['approved_at'] ?? $req['approved_at_fmt'] ?? '';
$finalApproverName = $req['approved_by_name'] ?? '';
$finalName = (!empty($primaryApproval) && $finalApproverName !== '' && $finalApproverName !== ($primaryApproval['name'] ?? ''))
    ? $finalApproverName : '';
$finalRole = $finalName !== '' ? ($req['approved_by_role'] ?? '') : '';
?>
<div class="pz-card">
    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem;padding-bottom:.65rem;border-bottom:2px solid #f0e8d0;">
        <span class="material-symbols-outlined" style="font-size:1.1rem;color:#8a6420;">receipt_long</span>
        <span style="font-size:.78rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#5a4020;">Request Details</span>
    </div>

    <!-- Requested by -->
    <div style="margin-bottom:1rem;">
        <div style="font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#a07830;margin-bottom:.45rem;">Submitted By</div>
        <div style="display:flex;align-items:center;gap:.6rem;">
            <span class="material-symbols-outlined" style="font-size:1rem;color:#c9a84c;">person</span>
            <span style="font-weight:600;color:#2c1a00;font-size:.95rem;"><?= esc($reqBy) ?></span>
            <span style="font-size:.72rem;color:#fff;background:#8a6420;border-radius:4px;padding:.1rem .45rem;font-weight:600;"><?= esc($reqRole) ?></span>
        </div>
    </div>

    <!-- Sites -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1rem;">
        <div style="background:#fdf7e6;border:1px solid #e8d89a;border-radius:8px;padding:.65rem .85rem;">
            <div style="font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#a07830;margin-bottom:.3rem;">
                <span class="material-symbols-outlined" style="font-size:.85rem;vertical-align:middle;">location_on</span> Destination Site
            </div>
            <div style="font-weight:700;color:#2c1a00;font-size:.9rem;"><?= esc($destName) ?></div>
        </div>
        <div style="background:#fdf7e6;border:1px solid #e8d89a;border-radius:8px;padding:.65rem .85rem;">
            <div style="font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#a07830;margin-bottom:.3rem;">
                <span class="material-symbols-outlined" style="font-size:.85rem;vertical-align:middle;">where_to_vote</span> Source Site
            </div>
            <div style="font-weight:700;color:<?= $sourceName === 'To be assigned' ? '#a07830' : '#2c1a00' ?>;font-size:.9rem;font-style:<?= $sourceName === 'To be assigned' ? 'italic' : 'normal' ?>;">
                <?= esc($sourceName) ?>
            </div>
        </div>
    </div>

    <!-- Request specifics -->
    <div style="display:flex;gap:1.5rem;flex-wrap:wrap;margin-bottom:<?= $primaryName !== '' ? '1rem' : '0' ?>;">
        <div>
            <div style="font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#a07830;margin-bottom:.2rem;">Category</div>
            <div style="font-weight:600;color:#2c1a00;font-size:.92rem;"><?= esc($category) ?></div>
        </div>
        <div>
            <div style="font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#a07830;margin-bottom:.2rem;">Quantity</div>
            <div style="font-weight:600;color:#2c1a00;font-size:.92rem;"><?= esc($qty) ?></div>
        </div>
        <div>
            <div style="font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#a07830;margin-bottom:.2rem;">Created</div>
            <div style="font-weight:600;color:#2c1a00;font-size:.92rem;"><?= esc($createdAt) ?></div>
        </div>
    </div>

    <!-- Approval history -->
    <?php if ($primaryName !== ''): ?>
    <div style="border-top:1px solid #f0e8d0;padding-top:.85rem;margin-top:.15rem;">
        <div style="font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#a07830;margin-bottom:.5rem;">Approval History</div>
        <div style="display:flex;flex-direction:column;gap:.4rem;">
            <div style="display:flex;align-items:center;gap:.5rem;font-size:.88rem;color:#2c1a00;">
                <span style="width:20px;height:20px;border-radius:50%;background:#8a6420;color:#fff;font-size:.72rem;font-weight:700;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">1</span>
                <span style="font-weight:600;"><?= esc($primaryName) ?></span>
                <?php if ($primaryRole): ?><span style="font-size:.7rem;color:#fff;background:#8a6420;border-radius:4px;padding:.1rem .4rem;"><?= esc($primaryRole) ?></span><?php endif; ?>
                <?php if ($primaryAt): ?><span style="opacity:.55;font-size:.8rem;">· <?= esc($primaryAt) ?></span><?php endif; ?>
            </div>
            <?php if ($finalName !== ''): ?>
            <div style="display:flex;align-items:center;gap:.5rem;font-size:.88rem;color:#2c1a00;">
                <span style="width:20px;height:20px;border-radius:50%;background:#2e7d32;color:#fff;font-size:.72rem;font-weight:700;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">2</span>
                <span style="font-weight:600;"><?= esc($finalName) ?></span>
                <?php if ($finalRole): ?><span style="font-size:.7rem;color:#fff;background:#2e7d32;border-radius:4px;padding:.1rem .4rem;"><?= esc($finalRole) ?></span><?php endif; ?>
                <?php if (!empty($req['approved_at_fmt'])): ?><span style="opacity:.55;font-size:.8rem;">· <?= esc($req['approved_at_fmt']) ?></span><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Reserved resources -->
<div class="pz-card">
    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem;padding-bottom:.65rem;border-bottom:2px solid #f0e8d0;">
        <span class="material-symbols-outlined" style="font-size:1.1rem;color:#8a6420;">inventory</span>
        <span style="font-size:.78rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#5a4020;">Reserved Resources</span>
    </div>
    <?php if (empty($reserved)): ?>
        <p style="font-size:1rem; color:#7a6040; margin:0">No resources reserved yet. Approval will lock source items before transfer.</p>
    <?php else: ?>
        <?php foreach ($reserved as $res): ?>
        <?php $scanned = (bool)($res['scanned'] ?? false); ?>
        <div style="display:flex; align-items:center; justify-content:space-between; padding:.5rem 0; border-bottom:1px solid #f0e8d0;">
            <div>
                <div style="font-weight:700; font-size:1rem; color:#2c1a00"><?= esc(is_array($res) ? ($res['display_name'] ?? $res['name'] ?? $res['id'] ?? '') : $res) ?></div>
                <?php if (is_array($res) && !empty($res['equipment_type'])): ?>
                <div style="font-size:0.82rem; color:#9a7b3a"><?= esc($res['equipment_type']) ?></div>
                <?php endif; ?>
                <?php if ($scanned && !empty($res['scanned_by'])): ?>
                <div style="font-size:0.8rem; color:#16a34a">Scanned by <?= esc($res['scanned_by']) ?></div>
                <?php endif; ?>
            </div>
            <?php if ($scanned): ?>
                <span class="pz-badge pz-badge-completed">Scanned</span>
            <?php else: ?>
                <span class="pz-badge pz-badge-pending">Pending Scan</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- In-transit info banner -->
<?php if ($isInTransit): ?>
<div class="pz-card" style="border-left:4px solid #64748b;">
    <p class="pz-card-section">In-Transit</p>
    <div style="display:flex;align-items:flex-start;gap:.75rem;font-size:0.92rem;color:#374151;">
        <span class="material-symbols-outlined" style="font-size:1.4rem;color:#64748b;flex-shrink:0;margin-top:.05rem">local_shipping</span>
        <div>
            <p style="margin:0 0 .35rem;font-weight:600;color:#1e293b">Resource is on its way to <strong><?= esc($destName) ?></strong></p>
            <p style="margin:0;color:#6b7280;font-size:0.87rem">The resource has been approved and is now in transit. It will appear grayed-out in the destination project's monitoring card until the Site Engineer confirms receipt.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Accept Delivery section (Site Engineer only, when In-Transit) -->
<?php if ($canAcceptDelivery): ?>
<?php $isManpower = strtolower($category) === 'manpower'; ?>
<div class="pz-card" style="border-left:4px solid #16a34a;">
    <p class="pz-card-section">Accept Delivery</p>
    <div style="display:flex;align-items:flex-start;gap:.75rem;margin-bottom:1.25rem;">
        <span class="material-symbols-outlined" style="font-size:1.4rem;color:#16a34a;flex-shrink:0;margin-top:.05rem">inventory</span>
        <div style="font-size:0.92rem;color:#374151;">
            <p style="margin:0 0 .25rem;font-weight:600;color:#1e293b">Confirm receipt of <strong><?= esc($resName) ?></strong></p>
            <p style="margin:0;color:#6b7280;font-size:0.87rem">
                <?php if ($isManpower): ?>
                    Type the worker's full name exactly to confirm their arrival at <strong><?= esc($destName) ?></strong>.
                <?php else: ?>
                    Scan the QR/barcode or manually enter the serial number to confirm the item has arrived at <strong><?= esc($destName) ?></strong>.
                <?php endif; ?>
            </p>
        </div>
    </div>
    <form method="POST" action="<?= base_url('requests/accept-delivery/' . $req['id']) ?>">
        <?= csrf_field() ?>
        <?php if ($isManpower): ?>
        <div style="margin-bottom:1rem;">
            <label style="display:block;font-size:0.85rem;font-weight:600;color:#374151;margin-bottom:.4rem;">
                <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle">badge</span>
                Worker's Full Name
            </label>
            <input type="text" name="confirm_name" required autocomplete="off"
                   placeholder="e.g. Juan Dela Cruz"
                   style="width:100%;padding:.55rem .75rem;border:1px solid #d1d5db;border-radius:.5rem;font-size:0.93rem;box-sizing:border-box;">
            <p style="margin:.35rem 0 0;font-size:0.78rem;color:#6b7280;">Enter exactly as it appears on the worker's profile (case-insensitive).</p>
        </div>
        <?php else: ?>
        <div style="margin-bottom:1rem;">
            <label style="display:block;font-size:0.85rem;font-weight:600;color:#374151;margin-bottom:.4rem;">
                <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle">qr_code_scanner</span>
                Serial Number / QR Code
            </label>
            <input type="text" name="serial_number" required autocomplete="off"
                   placeholder="Scan or type the serial number"
                   style="width:100%;padding:.55rem .75rem;border:1px solid #d1d5db;border-radius:.5rem;font-size:0.93rem;box-sizing:border-box;font-family:monospace;">
            <p style="margin:.35rem 0 0;font-size:0.78rem;color:#6b7280;">Scan the item's QR/barcode or type the serial number manually (case-insensitive).</p>
        </div>
        <?php endif; ?>
        <button type="submit" class="pz-btn pz-btn-success">
            <span class="material-symbols-outlined"><?= $isManpower ? 'how_to_reg' : 'qr_code_scanner' ?></span>
            <?= $isManpower ? 'Confirm Arrival' : 'Confirm Receipt' ?>
        </button>
    </form>
</div>
<?php endif; ?>

<!-- Own-request notice (blocks self-approval for non-Admin) -->
<?php if ($isOwnRequest && $role !== 'Administrator' && in_array($role, ['Operations Manager', 'Project Manager']) && ($isPending || $isPendingSecondary)): ?>
<div class="pz-card" style="border-left:4px solid #f59e0b;">
    <div style="display:flex;align-items:center;gap:.6rem;font-size:0.92rem;color:#78350f;">
        <span class="material-symbols-outlined" style="font-size:1.3rem;color:#f59e0b;flex-shrink:0;">info</span>
        <div>
            <strong>You submitted this request.</strong>
            Another <?= esc($role) ?>, Operations Manager, or Administrator must approve it.
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Cancel section (original requester only, while pending) -->
<?php if ($canCancel): ?>
<div id="pzCancelSection" class="pz-card" style="border-left:4px solid #dc2626;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:.6rem;">
            <span class="material-symbols-outlined" style="font-size:1.3rem;color:#dc2626;flex-shrink:0;">undo</span>
            <div style="font-size:.92rem;color:#7f1d1d;">
                <div style="font-weight:700;margin-bottom:.1rem;">Cancel This Request</div>
                <div style="font-size:.82rem;">This will withdraw the request from the approval queue.</div>
            </div>
        </div>
        <form method="POST" action="<?= base_url('requests/cancel/' . esc($req['id'])) ?>" id="cancelReqForm">
            <?= csrf_field() ?>
            <button type="button" class="pz-btn pz-btn-danger-outline" id="cancelReqBtn">
                <span class="material-symbols-outlined">cancel</span> Cancel Request
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Approval section -->
<?php if ($canApprove && ($isPending || $isPendingSecondary)): ?>
<div id="pzApprovalSection" class="pz-card">
    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem;padding-bottom:.65rem;border-bottom:2px solid #f0e8d0;">
        <span class="material-symbols-outlined" style="font-size:1.1rem;color:#8a6420;">approval</span>
        <span style="font-size:.78rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#5a4020;">Approval</span>
    </div>

    <?php if ($isPendingSecondary): ?>
        <!-- Primary approval info -->
        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:.7rem 1rem;margin-bottom:.85rem;font-size:0.92rem;color:#0c4a6e;">
            <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;margin-right:.3rem;">verified</span>
            <strong>1st approval:</strong> <?= esc($primaryApproval['name'] ?? $req['approved_by_name'] ?? '—') ?>
            <?php if ($primaryApproverRole): ?><span style="opacity:.7"> · <?= esc($primaryApproverRole) ?></span><?php endif; ?>
            <?php $pa1At = $primaryApproval['approved_at'] ?? $req['approved_at_fmt'] ?? ''; ?>
            <?php if ($pa1At !== ''): ?><span style="opacity:.7"> · <?= esc($pa1At) ?></span><?php endif; ?>
            <br><span style="opacity:.8">Approved source:</span> <strong><?= esc($req['source_site_name'] ?? '—') ?></strong>
        </div>

        <?php if ($sameRoleBlocked): ?>
            <!-- Same role — cannot approve secondary -->
            <div style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:.7rem 1rem;margin-bottom:.85rem;font-size:0.92rem;color:#78350f;display:flex;align-items:center;gap:.5rem;">
                <span class="material-symbols-outlined" style="font-size:1.1rem;">block</span>
                A <strong><?= esc($role) ?></strong> already provided the first approval. The secondary approval must come from a different role.
            </div>
            <!-- Still allow rejection -->
            <form method="POST" action="<?= base_url('requests/reject/' . $req['id']) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="pz-btn pz-btn-danger-outline" class="reject-form-btn">
                    <span class="material-symbols-outlined">cancel</span> Reject
                </button>
            </form>
        <?php else: ?>
            <p style="font-size:0.92rem; color:#5a4020; margin-bottom:.85rem">
                Awaiting 2nd approval from a different role.
            </p>
            <div style="display:flex; gap:.55rem; flex-wrap:wrap">
                <form method="POST" action="<?= base_url('requests/approve-secondary/' . $req['id']) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="pz-btn pz-btn-success">
                        <span class="material-symbols-outlined">check_circle</span> Approve Final
                    </button>
                </form>
                <form method="POST" action="<?= base_url('requests/reject/' . $req['id']) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="pz-btn pz-btn-danger-outline" class="reject-form-btn">
                        <span class="material-symbols-outlined">cancel</span> Reject
                    </button>
                </form>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <form method="POST" action="<?= base_url('requests/approve/' . $req['id']) ?>">
            <?= csrf_field() ?>

            <?php if (!$sourceKnown): ?>

            <!-- MCDA section (always shown) -->
            <div style="margin-bottom:1.25rem;padding:1rem;background:#fdf7e6;border:1px solid #e8d89a;border-radius:10px;">

                <div style="display:flex;align-items:center;gap:.45rem;margin-bottom:.85rem;">
                    <span class="material-symbols-outlined" style="font-size:1rem;color:#8a6420;">auto_awesome</span>
                    <span style="font-size:.75rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#5a4020;">MCDA Recommendations</span>
                </div>

                <div style="margin-bottom:.85rem;">
                    <div style="font-size:.72rem;font-weight:700;color:#8a6420;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.4rem;">Resource/s Requested</div>
                    <table class="pz-table" style="font-size:.84rem;margin-bottom:0;background:#fff;">
                        <thead><tr><th>Resource</th><th><?= strtolower($category) === 'manpower' ? 'Skill/Type Requested' : 'Model' ?></th><th>Quantity</th></tr></thead>
                        <tbody>
                            <tr>
                                <td style="font-weight:600;"><?= esc(strtoupper($category)) ?></td>
                                <td><?= esc(strtolower($category) === 'manpower' ? (strtoupper((string)($req['manpower_type'] ?? '')) ?: '—') : ($resName ?: '—')) ?></td>
                                <td><?= esc($qty) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php
                    // ML filter summary badges — split by the actual exclusion reason so
                    // "no stock at that site" is never mislabeled as an ML risk call.
                    $nExcludedMl    = count(array_filter($mcdaExcludedSites, fn($s) => ($s['exclude_reason'] ?? '') === 'ML predicted delay/blocker'));
                    $nExcludedUnits = count(array_filter($mcdaExcludedSites, fn($s) => ($s['exclude_reason'] ?? '') === 'No allocatable units'));
                ?>

                <?php if ($mcdaMlAvailable): ?>
                <div style="display:flex;align-items:center;gap:.55rem;margin-bottom:.75rem;flex-wrap:wrap;">
                    <span style="display:inline-flex;align-items:center;gap:.28rem;background:#eff6ff;color:#1e40af;font-size:.72rem;font-weight:700;padding:.22rem .55rem;border-radius:6px;border:1px solid #bfdbfe;">
                        <span class="material-symbols-outlined" style="font-size:.88rem;">psychology</span>
                        ML Risk Filter Active
                    </span>
                    <?php if ($nExcludedMl > 0): ?>
                    <span style="display:inline-flex;align-items:center;gap:.28rem;background:#fef3c7;color:#92400e;font-size:.72rem;font-weight:700;padding:.22rem .55rem;border-radius:6px;border:1px solid #fde68a;">
                        <span class="material-symbols-outlined" style="font-size:.88rem;">warning</span>
                        <?= $nExcludedMl ?> project<?= $nExcludedMl > 1 ? 's' : '' ?> excluded (predicted delay / blocker)
                    </span>
                    <?php endif; ?>
                    <?php if ($nExcludedUnits > 0): ?>
                    <span style="display:inline-flex;align-items:center;gap:.28rem;background:#f3f4f6;color:#4b5563;font-size:.72rem;font-weight:700;padding:.22rem .55rem;border-radius:6px;border:1px solid #e5e7eb;">
                        <span class="material-symbols-outlined" style="font-size:.88rem;">inventory_2</span>
                        <?= $nExcludedUnits ?> site<?= $nExcludedUnits > 1 ? 's' : '' ?> with no stock of this resource
                    </span>
                    <?php endif; ?>
                    <?php if ($nExcludedMl === 0 && $nExcludedUnits === 0): ?>
                    <span style="display:inline-flex;align-items:center;gap:.28rem;background:#dcfce7;color:#166534;font-size:.72rem;font-weight:700;padding:.22rem .55rem;border-radius:6px;border:1px solid #bbf7d0;">
                        <span class="material-symbols-outlined" style="font-size:.88rem;">check_circle</span>
                        All candidates on track
                    </span>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div style="display:inline-flex;align-items:center;gap:.28rem;background:#f3f4f6;color:#6b7280;font-size:.72rem;font-weight:600;padding:.22rem .55rem;border-radius:6px;border:1px solid #e5e7eb;margin-bottom:.75rem;">
                    <span class="material-symbols-outlined" style="font-size:.88rem;">cloud_off</span>
                    ML service offline — heuristic filter applied
                </div>
                <?php endif; ?>

                <?php if (!empty($recommendedSites)): ?>
                <div style="font-size:.82rem;color:#2c1a00;font-weight:600;margin-bottom:.5rem;">
                    Project Site Rankings — Eligible Sources
                </div>
                <div style="overflow-x:auto;margin-bottom:.5rem;">
                <table style="width:100%;border-collapse:separate;border-spacing:0;font-size:.8rem;background:#fff;border-radius:8px;border:1px solid #e8d89a;overflow:hidden;">
                    <thead>
                        <tr style="background:#f4ead1;">
                            <th style="padding:7px 8px;text-align:center;width:44px;border-bottom:2px solid #e8d89a;font-size:.7rem;letter-spacing:.05em;color:#5a4020;">#</th>
                            <th style="padding:7px 10px;text-align:left;border-bottom:2px solid #e8d89a;font-size:.7rem;letter-spacing:.05em;color:#5a4020;">PROJECT SITE</th>
                            <th style="padding:7px 8px;text-align:center;width:76px;border-bottom:2px solid #e8d89a;font-size:.7rem;letter-spacing:.05em;color:#5a4020;">DISTANCE</th>
                            <th style="padding:7px 8px;text-align:center;width:62px;border-bottom:2px solid #e8d89a;font-size:.7rem;letter-spacing:.05em;color:#5a4020;">ETA</th>
                            <th style="padding:7px 8px;text-align:center;width:82px;border-bottom:2px solid #e8d89a;font-size:.7rem;letter-spacing:.05em;color:#5a4020;">TRAFFIC</th>
                            <th style="padding:7px 8px;text-align:center;width:80px;border-bottom:2px solid #e8d89a;font-size:.7rem;letter-spacing:.05em;color:#5a4020;">NEXT AVAIL.</th>
                            <th style="padding:7px 8px;text-align:center;width:82px;border-bottom:2px solid #e8d89a;font-size:.7rem;letter-spacing:.05em;color:#5a4020;">ALLOCATABLE</th>
                            <th style="padding:7px 8px;text-align:center;width:72px;border-bottom:2px solid #e8d89a;font-size:.7rem;letter-spacing:.05em;color:#5a4020;">ML STATUS</th>
                            <th style="padding:7px 8px;text-align:center;width:74px;border-bottom:2px solid #e8d89a;font-size:.7rem;letter-spacing:.05em;color:#5a4020;">WSM SCORE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recommendedSites as $rank => $site): ?>
                        <?php
                            $isTop        = $rank === 0;
                            $unitsAvail   = (int)($site['units_available'] ?? 0);
                            $unitsRel     = (int)($site['units_released']  ?? 0);
                            $trafficLabel = $site['traffic_label'] ?? 'Light';
                            $wsmScore     = (float)($site['wsm_score']     ?? 0);
                            $mlDelay      = (int)($site['ml_delay_days']   ?? 0);
                            $mlBlocker    = $site['ml_blocker'] ?? null;

                            if ($trafficLabel === 'Heavy') {
                                $tBg='#fee2e2';$tFg='#991b1b';$tBd='#fca5a5';
                            } elseif ($trafficLabel === 'Moderate') {
                                $tBg='#fef9c3';$tFg='#854d0e';$tBd='#fde68a';
                            } else {
                                $tBg='#dcfce7';$tFg='#166534';$tBd='#bbf7d0';
                            }

                            $nextMin  = (int)($site['next_available_min'] ?? 0);
                            $nextText = $nextMin <= 0 ? 'Now' : ($nextMin < 60 ? "{$nextMin}m" : round($nextMin/60,1).'h');

                            $wsmPct   = min(100, (int)round($wsmScore * 100));
                            $wsmColor = $wsmPct >= 70 ? '#16a34a' : ($wsmPct >= 45 ? '#b45309' : '#dc2626');

                            $mlStatusBg = '#dcfce7'; $mlStatusFg = '#166534'; $mlStatusText = 'On Track';
                            if (!$mcdaMlAvailable) { $mlStatusBg = '#f3f4f6'; $mlStatusFg = '#6b7280'; $mlStatusText = 'N/A'; }
                        ?>
                        <tr id="mcda-row-<?= esc($site['project_id']) ?>"
                            style="cursor:pointer;border-bottom:1px solid #f0e6cd;transition:background .12s;<?= $isTop ? 'background:#fffbeb;' : '' ?>"
                            onmouseover="this.style.background='#fef3d6'"
                            onmouseout="this.style.background='<?= $isTop ? '#fffbeb' : '' ?>'"
                            onclick="selectMcdaSite('<?= esc($site['project_id']) ?>', this)">
                            <td style="padding:8px;text-align:center;">
                                <span style="display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:50%;background:<?= $isTop ? '#c07000' : '#9ca3af' ?>;color:#fff;font-weight:700;font-size:.78rem;"><?= $rank + 1 ?></span>
                            </td>
                            <td style="padding:8px 10px;">
                                <div style="font-weight:<?= $isTop ? '700' : '600' ?>;color:#1c1a17;font-size:.82rem;"><?= esc($site['project_name']) ?></div>
                                <?php if ($isTop): ?>
                                <div style="font-size:.68rem;color:#c07000;font-weight:700;margin-top:.15rem;letter-spacing:.04em;">★ BEST MATCH</div>
                                <?php endif; ?>
                                <?php
                                    $siteUnits = array_values(array_filter((array)($site['unit_list'] ?? []), fn($u) => trim((string)($u['serial'] ?? $u['name'] ?? '')) !== ''));
                                    $unitsShown = array_slice($siteUnits, 0, 8);
                                    $unitsExtra = count($siteUnits) - count($unitsShown);
                                ?>
                                <?php if (!empty($siteUnits)): ?>
                                <div style="display:flex;flex-wrap:wrap;gap:.22rem;margin-top:.4rem;max-width:230px;">
                                    <?php foreach ($unitsShown as $u): ?>
                                    <span style="display:inline-flex;align-items:center;gap:.15rem;background:#eff6ff;color:#1d4ed8;font-size:.62rem;font-weight:600;padding:.08rem .32rem;border-radius:99px;border:1px solid #bfdbfe;">
                                        <span class="material-symbols-outlined" style="font-size:.68rem;">inventory_2</span><?= esc($u['serial'] ?? $u['name'] ?? '') ?>
                                    </span>
                                    <?php endforeach; ?>
                                    <?php if ($unitsExtra > 0): ?>
                                    <span style="font-size:.62rem;color:#9ca3af;font-weight:600;padding:.08rem .32rem;">+<?= $unitsExtra ?> more</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding:8px;text-align:center;color:#374151;font-size:.8rem;"><?= esc($site['distance_km']) ?> km</td>
                            <td style="padding:8px;text-align:center;color:#374151;font-size:.8rem;"><?= esc($site['eta_min'] ?? $site['travel_time_min'] ?? '—') ?> min</td>
                            <td style="padding:8px;text-align:center;">
                                <span style="display:inline-block;background:<?= $tBg ?>;color:<?= $tFg ?>;font-size:.7rem;font-weight:700;padding:.15rem .4rem;border-radius:99px;border:1px solid <?= $tBd ?>;"><?= esc($trafficLabel) ?></span>
                            </td>
                            <td style="padding:8px;text-align:center;color:<?= $nextMin<=0?'#166534':'#374151' ?>;font-weight:<?= $nextMin<=0?'700':'400' ?>;font-size:.8rem;"><?= esc($nextText) ?></td>
                            <td style="padding:8px;text-align:center;">
                                <?php if ($unitsAvail > 0): ?>
                                <span style="display:inline-flex;align-items:center;gap:.2rem;background:#eff6ff;color:#1d4ed8;font-size:.7rem;font-weight:700;padding:.15rem .4rem;border-radius:99px;border:1px solid #bfdbfe;">
                                    <span class="material-symbols-outlined" style="font-size:.78rem;">inventory_2</span><?= $unitsAvail ?>u
                                </span>
                                <?php if ($unitsRel > 0): ?><div style="font-size:.65rem;color:#166534;margin-top:.1rem;"><?= $unitsRel ?> SE-released</div><?php endif; ?>
                                <?php else: ?><span style="font-size:.7rem;color:#9ca3af;font-style:italic;">None</span><?php endif; ?>
                            </td>
                            <td style="padding:8px;text-align:center;">
                                <span style="display:inline-block;background:<?= $mlStatusBg ?>;color:<?= $mlStatusFg ?>;font-size:.68rem;font-weight:700;padding:.15rem .4rem;border-radius:4px;"><?= esc($mlStatusText) ?></span>
                            </td>
                            <td style="padding:8px;text-align:center;">
                                <div style="font-size:.82rem;font-weight:700;color:<?= $wsmColor ?>;"><?= number_format($wsmScore, 4) ?></div>
                                <div style="height:4px;background:#f0e6cd;border-radius:99px;margin-top:.25rem;overflow:hidden;">
                                    <div style="height:100%;width:<?= $wsmPct ?>%;background:<?= $wsmColor ?>;border-radius:99px;"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>

                <?php if (!empty($mcdaExcludedSites)): ?>
                <details style="margin-top:.4rem;">
                    <summary style="font-size:.72rem;color:#92400e;font-weight:700;cursor:pointer;padding:.3rem 0;user-select:none;">
                        <span class="material-symbols-outlined" style="font-size:.85rem;vertical-align:middle;">expand_more</span>
                        <?= count($mcdaExcludedSites) ?> site<?= count($mcdaExcludedSites)>1?'s':'' ?> excluded
                    </summary>
                    <div style="margin-top:.35rem;overflow-x:auto;">
                    <table style="width:100%;border-collapse:separate;border-spacing:0;font-size:.78rem;background:#fffbf0;border-radius:6px;border:1px solid #fde68a;opacity:.8;">
                        <thead><tr style="background:#fef3c7;">
                            <th style="padding:5px 8px;text-align:left;border-bottom:1px solid #fde68a;font-size:.68rem;color:#92400e;">PROJECT SITE</th>
                            <th style="padding:5px 8px;text-align:center;width:150px;border-bottom:1px solid #fde68a;font-size:.68rem;color:#92400e;">REASON</th>
                            <th style="padding:5px 8px;text-align:center;width:100px;border-bottom:1px solid #fde68a;font-size:.68rem;color:#92400e;">ML DELAY PRED.</th>
                            <th style="padding:5px 8px;text-align:center;width:120px;border-bottom:1px solid #fde68a;font-size:.68rem;color:#92400e;">PREDICTED BLOCKER</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($mcdaExcludedSites as $ex): ?>
                        <?php $exReason = $ex['exclude_reason'] ?? 'ML predicted delay/blocker'; ?>
                        <tr style="border-bottom:1px solid #fef3c7;">
                            <td style="padding:5px 8px;color:#78350f;font-weight:600;"><?= esc($ex['project_name'] ?? $ex['project_id']) ?></td>
                            <td style="padding:5px 8px;text-align:center;">
                                <?php if ($exReason === 'No allocatable units'): ?>
                                <span style="background:#f3f4f6;color:#4b5563;font-size:.68rem;font-weight:600;padding:.12rem .35rem;border-radius:4px;">No stock here</span>
                                <?php else: ?>
                                <span style="background:#fef3c7;color:#92400e;font-size:.68rem;font-weight:600;padding:.12rem .35rem;border-radius:4px;">ML risk</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:5px 8px;text-align:center;">
                                <?php $exDelay = (int)($ex['ml_delay_days'] ?? 0); ?>
                                <?php if ($exDelay > 0): ?>
                                <span style="background:#fee2e2;color:#991b1b;font-size:.7rem;font-weight:700;padding:.12rem .35rem;border-radius:4px;"><?= $exDelay ?>d delay</span>
                                <?php else: ?><span style="color:#9ca3af;font-size:.7rem;">—</span><?php endif; ?>
                            </td>
                            <td style="padding:5px 8px;text-align:center;">
                                <?php $exBlocker = $ex['ml_blocker'] ?? null; ?>
                                <?php if ($exBlocker): ?>
                                <span style="background:#fef3c7;color:#92400e;font-size:.7rem;font-weight:600;padding:.12rem .35rem;border-radius:4px;"><?= esc($exBlocker) ?></span>
                                <?php else: ?><span style="color:#9ca3af;font-size:.7rem;">—</span><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </details>
                <?php endif; ?>

                <p style="margin:.5rem 0 0;font-size:.72rem;color:#9a7b3a;">
                    <span class="material-symbols-outlined" style="font-size:.78rem;vertical-align:middle;">touch_app</span>
                    Click a row to select that site as the transfer source. <strong>WSM Score</strong> combines 5 criteria: distance (25%) · ETA (25%) · traffic (15%) · allocatable units (20%) · next availability (15%).
                    Excluded sites either had no stock of this resource, or were flagged by ML as an active-project risk.
                </p>

                <?php else: ?>
                <div style="display:flex;align-items:flex-start;gap:.5rem;font-size:.83rem;color:#8a6420;padding:.5rem 0;">
                    <span class="material-symbols-outlined" style="font-size:1rem;flex-shrink:0;margin-top:.05rem;">info</span>
                    <span>
                        <?php if ($nExcludedUnits > 0 && $nExcludedMl === 0): ?>
                        No eligible source sites found — none of the candidate <strong>On-going</strong> project sites currently have stock of this resource. Please select a source site manually.
                        <?php else: ?>
                        No eligible source sites found. MCDA only considers <strong>On-going</strong> projects with no active emergency and a registered location<?= $mcdaMlAvailable ? ', and passes ML delay/blocker prediction' : '' ?>. Please select a source site manually.
                        <?php endif; ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <div style="margin-bottom:.85rem;">
                <label style="font-size:.75rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#5a4020;display:block;margin-bottom:.4rem;">Select Source Site</label>
                <?php $mcdaBestId = $recommended_best['project_id'] ?? ''; ?>
                <select name="approved_source_project_id" id="source_site_input" required
                        onchange="window.pzRenderUnitChecklist(this.value)"
                        style="width:100%;max-width:420px;padding:.52rem .7rem;border:1px solid #d5c68a;border-radius:7px;font-size:.93rem;background:#fdf9f0;color:#2c1a00;appearance:auto;">
                    <option value="">Choose a source site</option>
                    <?php foreach ($projects as $proj): ?>
                    <option value="<?= esc($proj['id']) ?>" <?= $proj['id'] === $mcdaBestId ? 'selected' : '' ?>>
                        <?= esc($proj['label']) ?><?= $proj['id'] === $mcdaBestId ? ' ★' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php
                // Per-site unit data for the JS checklist below — only sites that appeared
                // in the MCDA ranking carry known unit-level detail.
                $mcdaUnitsById = [];
                foreach ($recommendedSites as $s) {
                    $mcdaUnitsById[$s['project_id']] = array_values(array_filter(
                        (array)($s['unit_list'] ?? []),
                        fn($u) => trim((string)($u['id'] ?? '')) !== ''
                    ));
                }
            ?>
            <div id="pzAvailableUnitsPanel" style="margin-bottom:.85rem;display:none;padding:.85rem;background:#fdf7e6;border:1px solid #e8d89a;border-radius:10px;">
                <div style="font-size:.75rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#5a4020;margin-bottom:.3rem;">Available Units</div>
                <div style="font-size:.78rem;color:#7a6040;margin-bottom:.55rem;">
                    Select exactly <strong><?= (int)$qty ?></strong> unit<?= (int)$qty > 1 ? 's' : '' ?> to transfer —
                    <span id="pzUnitSelectedCount">0</span> of <?= (int)$qty ?> selected.
                </div>
                <table style="width:100%;border-collapse:separate;border-spacing:0;font-size:.82rem;background:#fff;border-radius:8px;border:1px solid #e8d89a;overflow:hidden;">
                    <thead>
                        <tr style="background:#f4ead1;">
                            <th style="padding:6px 8px;width:32px;border-bottom:2px solid #e8d89a;"></th>
                            <th style="padding:6px 8px;text-align:left;border-bottom:2px solid #e8d89a;font-size:.7rem;letter-spacing:.05em;color:#5a4020;">NAME</th>
                            <th style="padding:6px 8px;text-align:left;border-bottom:2px solid #e8d89a;font-size:.7rem;letter-spacing:.05em;color:#5a4020;">SERIAL NO.</th>
                            <?php if (strtolower($category) === 'manpower'): ?>
                            <th style="padding:6px 8px;text-align:left;border-bottom:2px solid #e8d89a;font-size:.7rem;letter-spacing:.05em;color:#5a4020;">SKILLS</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="pzAvailableUnitsBody"></tbody>
                </table>
                <div id="pzUnitSelectError" style="display:none;color:#991b1b;font-size:.75rem;font-weight:600;margin-top:.5rem;">
                    <span class="material-symbols-outlined" style="font-size:.85rem;vertical-align:middle;">error</span>
                    Please select exactly <?= (int)$qty ?> unit<?= (int)$qty > 1 ? 's' : '' ?> before approving.
                </div>
            </div>
            <script>
                window.pzMcdaUnits    = <?= json_encode($mcdaUnitsById) ?>;
                window.pzRequiredQty  = <?= (int)$qty ?>;
                window.pzShowSkills   = <?= strtolower($category) === 'manpower' ? 'true' : 'false' ?>;
            </script>

            <?php else: ?>
            <input type="hidden" name="approved_source_project_id" value="<?= esc($req['source_project_id']) ?>">
            <div style="margin-bottom:.85rem;">
                <div style="font-size:.75rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#5a4020;margin-bottom:.3rem;">Approved Source</div>
                <div style="font-weight:700;font-size:.95rem;color:#2c1a00;"><?= esc($sourceName) ?></div>
            </div>
            <?php endif; ?>

            <?php if ($role === 'Administrator'): ?>
            <label style="display:flex; align-items:center; gap:.5rem; font-size:0.92rem; color:#7a6040; margin-bottom:.85rem; cursor:pointer; background:#fef3c7; border:1px solid #f59e0b; border-radius:7px; padding:.5rem .75rem;">
                <input type="checkbox" name="bypass_secondary" value="1">
                <span><strong>Admin bypass</strong> — approve immediately without requiring a second approval</span>
            </label>
            <?php endif; ?>

            <div style="display:flex; gap:.55rem; flex-wrap:wrap">
                <button type="submit" class="pz-btn pz-btn-success">
                    <span class="material-symbols-outlined">check_circle</span> Approve
                </button>
                <button type="button" class="pz-btn pz-btn-danger-outline"
                        onclick="rejectRequest('<?= esc($req['id']) ?>')">
                    <span class="material-symbols-outlined">cancel</span> Reject
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<div id="reqConfirmDialog" class="confirm-dialog" role="dialog" aria-modal="true">
    <div class="confirm-backdrop"></div>
    <div class="confirm-box">
        <div class="confirm-hdr">
            <span class="material-symbols-outlined confirm-hdr-icon" style="color:#c0392b">cancel</span>
            <p class="confirm-hdr-title">Reject</p>
        </div>
        <div class="confirm-content"><p id="reqConfirmMessage"></p></div>
        <div class="confirm-actions">
            <button id="reqConfirmOk"     class="confirm-btn confirm-btn-action">Reject</button>
            <button id="reqConfirmCancel" class="confirm-btn confirm-btn-cancel">Back</button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
var _rqCr = null;
var rqCd  = document.getElementById('reqConfirmDialog');
var rqCm  = document.getElementById('reqConfirmMessage');
function openReqConfirm(message, okLabel, cancelLabel) {
    if (_rqCr) return Promise.resolve(false);
    document.getElementById('reqConfirmOk').textContent     = okLabel     || 'Reject';
    document.getElementById('reqConfirmCancel').textContent = cancelLabel || 'Back';
    return new Promise(function (resolve) {
        _rqCr = resolve;
        rqCm.innerText = message;
        rqCd.classList.add('open');
    });
}
document.getElementById('reqConfirmOk')?.addEventListener('click', function () {
    if (!_rqCr) return; var r = _rqCr; _rqCr = null; rqCd.classList.remove('open'); r(true);
});
document.getElementById('reqConfirmCancel')?.addEventListener('click', function () {
    if (!_rqCr) return; var r = _rqCr; _rqCr = null; rqCd.classList.remove('open'); r(false);
});

document.getElementById('cancelReqBtn')?.addEventListener('click', async function () {
    if (!await openReqConfirm('Cancel this request? It will be withdrawn from the approval queue.', 'Yes, Cancel Request', 'Back')) return;
    document.getElementById('cancelReqForm').submit();
});

async function rejectRequest(id) {
    if (!await openReqConfirm('Reject this transfer request?')) return;
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= base_url('requests/reject/') ?>' + id;
    var csrf = document.createElement('input');
    csrf.type  = 'hidden';
    csrf.name  = '<?= csrf_token() ?>';
    csrf.value = '<?= csrf_hash() ?>';
    form.appendChild(csrf);
    document.body.appendChild(form);
    form.submit();
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.reject-form-btn').forEach(function (btn) {
        btn.addEventListener('click', async function (e) {
            e.preventDefault();
            if (!await openReqConfirm('Reject this request?')) return;
            this.closest('form').submit();
        });
    });

    // MCDA row selection
    window.selectMcdaSite = function (projectId, row) {
        var sel = document.getElementById('source_site_input');
        if (sel) sel.value = projectId;
        document.querySelectorAll('[id^="mcda-row-"]').forEach(function (r) {
            r.style.outline = '';
        });
        if (row) row.style.outline = '2px solid #c07000';
        // sync dropdown label
        if (sel) {
            for (var i = 0; i < sel.options.length; i++) {
                if (sel.options[i].value === projectId) {
                    sel.selectedIndex = i;
                    break;
                }
            }
        }
        window.pzRenderUnitChecklist(projectId);
    };

    // Available-units checklist — rebuilt whenever the source site changes.
    // Requires exactly the requested quantity to be checked before Approve submits.
    window.pzRenderUnitChecklist = function (projectId) {
        var panel = document.getElementById('pzAvailableUnitsPanel');
        var body  = document.getElementById('pzAvailableUnitsBody');
        var count = document.getElementById('pzUnitSelectedCount');
        var err   = document.getElementById('pzUnitSelectError');
        if (!panel || !body) return;

        var units = (window.pzMcdaUnits || {})[projectId] || [];
        body.innerHTML = '';
        if (err) err.style.display = 'none';

        if (!units.length) {
            panel.style.display = 'none';
            return;
        }

        panel.style.display = '';
        units.forEach(function (u) {
            var tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #f0e6cd';
            tr.innerHTML =
                '<td style="padding:6px 8px;text-align:center;"><input type="checkbox" name="selected_unit_ids[]" value="' +
                String(u.id).replace(/"/g, '&quot;') + '"></td>' +
                '<td style="padding:6px 8px;color:#1c1a17;">' + (u.name || '') + '</td>' +
                '<td style="padding:6px 8px;color:#5a4020;">' + (u.serial || '') + '</td>' +
                (window.pzShowSkills ? '<td style="padding:6px 8px;color:#5a4020;">' + (u.skills || '—') + '</td>' : '');
            body.appendChild(tr);
        });

        var updateCount = function () {
            var checked = body.querySelectorAll('input[type="checkbox"]:checked').length;
            if (count) count.textContent = String(checked);
            if (err) err.style.display = 'none';
        };
        body.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
            cb.addEventListener('change', updateCount);
        });
        updateCount();
    };

    // Auto-select the top MCDA row on page load
    (function () {
        var sel = document.getElementById('source_site_input');
        if (sel && sel.value) {
            var topRow = document.getElementById('mcda-row-' + sel.value);
            if (topRow) topRow.style.outline = '2px solid #c07000';
            window.pzRenderUnitChecklist(sel.value);
        }
    })();

    // Block submit unless the required quantity is checked (only when unit-level
    // data is available for the chosen site — plain manual picks fall back server-side).
    document.querySelectorAll('form[action*="/requests/approve/"]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var panel = document.getElementById('pzAvailableUnitsPanel');
            var err   = document.getElementById('pzUnitSelectError');
            if (!panel || panel.style.display === 'none') return;
            var body  = document.getElementById('pzAvailableUnitsBody');
            var checked = body ? body.querySelectorAll('input[type="checkbox"]:checked').length : 0;
            if (checked !== window.pzRequiredQty) {
                e.preventDefault();
                if (err) err.style.display = '';
                panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    });
});
</script>
<?php $this->endSection() ?>

<?php $this->section('firebase-scripts') ?>
<script>
(function () {
  var _reqId = '<?= esc($req['id']) ?>';
  var _ready = false;

  function updateBadge(status) {
    var badge = document.getElementById('pzReqStatusBadge');
    if (!badge || typeof window.pzBadgeClass !== 'function') return;
    badge.className  = 'pz-badge ' + window.pzBadgeClass(status);
    badge.textContent = status.toUpperCase();
  }

  function hideSections() {
    ['pzCancelSection', 'pzApprovalSection'].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.style.display = 'none';
    });
  }

  function showCancelledNotice(cancelledBy) {
    var existing = document.getElementById('pzCancelledNotice');
    if (existing) return;
    var notice = document.createElement('div');
    notice.id = 'pzCancelledNotice';
    notice.className = 'pz-card';
    notice.style.cssText = 'border-left:4px solid #dc2626;';
    notice.innerHTML =
      '<div style="display:flex;align-items:center;gap:.6rem;font-size:.92rem;color:#7f1d1d;">' +
        '<span class="material-symbols-outlined" style="font-size:1.3rem;color:#dc2626;flex-shrink:0;">cancel</span>' +
        '<div><div style="font-weight:700;margin-bottom:.1rem;">This request has been cancelled.</div>' +
        (cancelledBy ? '<div style="font-size:.82rem;">Cancelled by: ' + cancelledBy + '</div>' : '') +
        '</div></div>';
    var approvalSection = document.getElementById('pzApprovalSection');
    var cancelSection   = document.getElementById('pzCancelSection');
    var anchor = approvalSection || cancelSection;
    if (anchor) {
      anchor.parentNode.insertBefore(notice, anchor);
    }
  }

  function attachDocListener(db) {
    db.collection('resource_requests').doc(_reqId).onSnapshot(
      function (snap) {
        if (!_ready) { _ready = true; return; }
        if (!snap.exists) return;
        var data      = snap.data();
        var newStatus = (data.status || '').toLowerCase();

        updateBadge(data.status || '');

        if (newStatus === 'cancelled') {
          hideSections();
          showCancelledNotice(data.cancelled_by_name || '');
          return;
        }

        // For all other status transitions, show the refresh bar
        window.pzShowLiveBar && window.pzShowLiveBar(
          'Request status changed to ' + (data.status || '').toUpperCase() + ' — refresh for full update'
        );
      },
      function (err) { console.warn('[PZ] Request doc listener:', err.message); }
    );
  }

  firebase.auth().onAuthStateChanged(function (user) {
    if (user && window.pzFirebaseDb) attachDocListener(window.pzFirebaseDb);
  });
  setTimeout(function () {
    if (window.pzFirebaseDb && !window._pzReqDocListener) {
      window._pzReqDocListener = true;
      attachDocListener(window.pzFirebaseDb);
    }
  }, 5000);
}());
</script>
<?php $this->endSection() ?>
