<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$req      = $request ?? [];
$projects = $projects ?? [];
$role     = $role ?? '';

$status    = $req['status'] ?? 'Pending';
$category  = $req['resource_category'] ?? '';
$resName   = $req['resource_name'] ?? '';
$sourceName= $req['source_site_name'] ?? 'To be assigned';
$destName  = $req['destination_site_name'] ?? '—';
$qty       = $req['requested_quantity'] ?? $req['quantity'] ?? 1;
$model     = $req['requested_model_id'] ?? $req['model'] ?? '';
$reqBy     = $req['requested_by_name'] ?? '—';
$reqRole   = $req['requested_by_role'] ?? '—';
$createdAt = $req['created_at_fmt'] ?? '—';
$reserved  = is_array($req['reserved_resources'] ?? null) ? $req['reserved_resources'] : [];

$badgeMap = [
    'pending'                    => 'pz-badge-pending',
    'pending secondary approval' => 'pz-badge-pending-secondary',
    'in-transit'                 => 'pz-badge-in-transit',
    'rejected'                   => 'pz-badge-rejected',
    'completed'                  => 'pz-badge-completed',
];
$badgeCls = $badgeMap[strtolower($status)] ?? 'pz-badge-pending';

$canApprove          = in_array($role, ['Administrator', 'Operations Manager', 'Project Manager']);
$canAcceptDelivery   = ($canAcceptDelivery ?? false);
$isPending           = strtolower($status) === 'pending';
$isPendingSecondary  = strtolower($status) === 'pending secondary approval';
$isInTransit         = strtolower($status) === 'in-transit';
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
<div class="pz-card">
    <div class="pz-card-header">
        <div>
            <h2><?= esc($category) ?></h2>
            <p class="pz-card-subtitle"><?= esc($resName ?: $model ?: '—') ?></p>
        </div>
        <span id="pzReqStatusBadge" class="pz-badge <?= $badgeCls ?>"><?= esc(strtoupper($status)) ?></span>
    </div>
</div>

<!-- Info card -->
<div class="pz-card">
    <p class="pz-card-section">Request Details</p>
    <div class="pz-info-grid">
        <span class="pz-info-label">Requested by</span>
        <span class="pz-info-value"><?= esc($reqBy) ?></span>

        <span class="pz-info-label">Role</span>
        <span class="pz-info-value"><?= esc($reqRole) ?></span>

        <div class="pz-info-divider"></div>

        <span class="pz-info-label">Destination site</span>
        <span class="pz-info-value"><?= esc($destName) ?></span>

        <span class="pz-info-label">Source site</span>
        <span class="pz-info-value"><?= esc($sourceName) ?></span>

        <div class="pz-info-divider"></div>

        <span class="pz-info-label">Category</span>
        <span class="pz-info-value"><?= esc($category) ?></span>

        <span class="pz-info-label">Quantity</span>
        <span class="pz-info-value"><?= esc($qty) ?></span>

        <span class="pz-info-label">Created at</span>
        <span class="pz-info-value"><?= esc($createdAt) ?></span>

        <?php
        // $primaryApproval already set in header block above
        $primaryName = $primaryApproval['name'] ?? $req['approved_by_name'] ?? '';
        $primaryRole = $primaryApproval['role'] ?? $req['approved_by_role'] ?? '';
        $primaryAt   = $primaryApproval['approved_at'] ?? $req['approved_at_fmt'] ?? '';
        // 2nd approval only when final approver differs from 1st (non-bypass 2-step flow)
        $finalApproverName = $req['approved_by_name'] ?? '';
        $finalName = (!empty($primaryApproval) && $finalApproverName !== '' && $finalApproverName !== ($primaryApproval['name'] ?? ''))
            ? $finalApproverName : '';
        $finalRole = $finalName !== '' ? ($req['approved_by_role'] ?? '') : '';
        ?>
        <?php if ($primaryName !== ''): ?>
        <div class="pz-info-divider"></div>
        <span class="pz-info-label">1st Approval</span>
        <span class="pz-info-value">
            <?= esc($primaryName) ?>
            <?php if ($primaryRole !== ''): ?><span class="role-badge" style="font-size:0.7rem;margin-left:.3rem;"><?= esc($primaryRole) ?></span><?php endif; ?>
            <?= $primaryAt !== '' ? '<span style="opacity:.6;font-size:0.85rem;margin-left:.3rem;">· ' . esc($primaryAt) . '</span>' : '' ?>
        </span>
        <?php endif; ?>

        <?php if ($finalName !== ''): ?>
        <span class="pz-info-label">2nd Approval</span>
        <span class="pz-info-value">
            <?= esc($finalName) ?>
            <?php if ($finalRole !== ''): ?><span class="role-badge" style="font-size:0.7rem;margin-left:.3rem;"><?= esc($finalRole) ?></span><?php endif; ?>
            <?= !empty($req['approved_at_fmt']) ? '<span style="opacity:.6;font-size:0.85rem;margin-left:.3rem;">· ' . esc($req['approved_at_fmt']) . '</span>' : '' ?>
        </span>
        <?php endif; ?>
    </div>
</div>

<!-- Reserved resources -->
<div class="pz-card">
    <p class="pz-card-section">Reserved Resources</p>
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

<!-- Approval section -->
<?php if ($canApprove && ($isPending || $isPendingSecondary)): ?>
<div class="pz-card">
    <p class="pz-card-section">Approval</p>

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
            <?php if (isset($recommended_best) && !empty($recommended_best)): ?>
                <div class="mb-3 p-3" style="background-color:#f8f9fa;border-radius:8px;border-left:4px solid #f39c12;max-width:450px;margin-bottom:.85rem;">
                    <p style="font-size:0.85rem;font-weight:bold;color:#6c757d;margin:0 0 .5rem">
                        <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle">magic_button</span> AI RECOMMENDATIONS
                    </p>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                        <span class="pz-badge" style="background:#10b981;color:#fff;cursor:pointer;padding:.4rem .6rem;"
                              onclick="document.getElementById('source_site_input').value='<?= esc($recommended_best['project_id'] ?? '') ?>'">
                            ⭐ Best: <?= esc($recommended_best['project_name']) ?> (Score: <?= esc($recommended_best['score']) ?>)
                        </span>
                        <?php if (isset($recommended_nearest) && !empty($recommended_nearest) && ($recommended_nearest['project_id'] ?? '') !== ($recommended_best['project_id'] ?? '')): ?>
                        <span class="pz-badge" style="background:#0ea5e9;color:#fff;cursor:pointer;padding:.4rem .6rem;"
                              onclick="document.getElementById('source_site_input').value='<?= esc($recommended_nearest['project_id'] ?? '') ?>'">
                            📍 Nearest: <?= esc($recommended_nearest['project_name']) ?> (<?= esc($recommended_nearest['distance_km']) ?> km)
                        </span>
                        <?php endif; ?>
                    </div>
                    <small style="color:#9ca3af;font-size:0.75rem;">Click a badge to auto-select the source site below.</small>
                </div>
            <?php endif; ?>
            <div class="pz-form-group" style="max-width:340px; margin-bottom:.85rem">
                <label>Select source site</label>
                <select name="approved_source_project_id" id="source_site_input" required>
                    <option value="">Choose a source site</option>
                    <?php foreach ($projects as $proj): ?>
                    <option value="<?= esc($proj['id']) ?>"><?= esc($proj['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
            <input type="hidden" name="approved_source_project_id" value="<?= esc($req['source_project_id']) ?>">
            <?php if (isset($recommended_best) && !empty($recommended_best)): ?>
                <?php
                $aiAgreesWithSource = strtolower($recommended_best['project_name'] ?? '') === strtolower($sourceName ?? '');
                ?>
                <div style="background-color:#f8f9fa;border-radius:8px;border-left:4px solid <?= $aiAgreesWithSource ? '#10b981' : '#f39c12' ?>;max-width:450px;margin-bottom:.85rem;padding:.6rem .85rem;">
                    <p style="font-size:0.78rem;font-weight:700;color:#6c757d;margin:0 0 .35rem;text-transform:uppercase;letter-spacing:.04em;">
                        <span class="material-symbols-outlined" style="font-size:.95rem;vertical-align:middle">magic_button</span>
                        AI Sourcing Insight
                    </p>
                    <?php if ($aiAgreesWithSource): ?>
                        <span style="font-size:.84rem;color:#1e6b45;font-weight:600;">✓ AI agrees — <?= esc($recommended_best['project_name']) ?> is the top-scored source (Score: <?= esc($recommended_best['score']) ?>)</span>
                    <?php else: ?>
                        <span style="font-size:.84rem;color:#92400e;">AI suggests a different source: <strong><?= esc($recommended_best['project_name']) ?></strong> (Score: <?= esc($recommended_best['score']) ?>)</span>
                        <?php if (isset($recommended_nearest) && !empty($recommended_nearest)): ?>
                        <br><span style="font-size:.78rem;color:#6c757d;">Nearest: <?= esc($recommended_nearest['project_name']) ?> (<?= esc($recommended_nearest['distance_km']) ?> km)</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="pz-form-group" style="max-width:340px; margin-bottom:.85rem">
                <label>Approved source</label>
                <div style="font-weight:700; font-size:1rem; color:#2c1a00; padding:.4rem 0"><?= esc($sourceName) ?></div>
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
            <button id="reqConfirmCancel" class="confirm-btn confirm-btn-cancel">Cancel</button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
var _rqCr = null;
var rqCd  = document.getElementById('reqConfirmDialog');
var rqCm  = document.getElementById('reqConfirmMessage');
function openReqConfirm(message) {
    if (_rqCr) return Promise.resolve(false);
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
});
</script>
<?php $this->endSection() ?>

<?php $this->section('firebase-scripts') ?>
<script>
(function () {
  var _reqId  = '<?= esc($req['id']) ?>';
  var _ready  = false;

  // Status badge element — already in the DOM
  function updateBadge(status) {
    var badge = document.getElementById('pzReqStatusBadge');
    if (!badge || typeof window.pzBadgeClass !== 'function') return;
    badge.className = 'pz-badge ' + window.pzBadgeClass(status);
    badge.textContent = status.toUpperCase();
  }

  function attachDocListener(db) {
    db.collection('resource_requests').doc(_reqId).onSnapshot(
      function (snap) {
        if (!_ready) { _ready = true; return; } // skip initial fire
        if (!snap.exists) return;
        var data = snap.data();
        var newStatus = data.status || '';

        // Update badge immediately — no reload needed
        updateBadge(newStatus);

        // For section changes (approval buttons, accept delivery appearing/disappearing),
        // show the bar so the user can refresh when they're ready
        window.pzShowLiveBar && window.pzShowLiveBar(
          'Request status changed to ' + newStatus.toUpperCase() + ' — refresh for full update'
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
