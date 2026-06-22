<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$req      = $request ?? [];
$projects = $projects ?? [];
$role     = $role ?? '';

$status    = $req['status'] ?? 'Pending';
$category  = $req['category'] ?? '';
$resName   = $req['resource_name'] ?? '';
$sourceName= $req['source_site_name'] ?? ($req['approved_source_project_name'] ?? 'To be assigned');
$destName  = $req['destination_site_name'] ?? '—';
$qty       = $req['quantity'] ?? 1;
$model     = $req['model'] ?? '';
$reqBy     = $req['requested_by_name'] ?? '—';
$reqRole   = $req['requested_by_role'] ?? '—';
$createdAt = $req['created_at_fmt'] ?? '—';
$reserved  = is_array($req['reserved_resources'] ?? null) ? $req['reserved_resources'] : [];

$badgeMap = [
    'pending'                    => 'pz-badge-pending',
    'pending secondary'          => 'pz-badge-pending-secondary',
    'pending secondary approval' => 'pz-badge-pending-secondary',
    'approved'                   => 'pz-badge-approved',
    'rejected'                   => 'pz-badge-rejected',
    'completed'                  => 'pz-badge-completed',
];
$badgeCls = $badgeMap[strtolower($status)] ?? 'pz-badge-pending';

$canApprove          = in_array($role, ['Administrator', 'Operations Manager', 'Project Manager']);
$isPending           = strtolower($status) === 'pending';
$isPendingSecondary  = in_array(strtolower($status), ['pending secondary', 'pending secondary approval']);
$sourceKnown         = !empty($req['source_project_id']);
$primaryApproverRole = $req['approved_by_role'] ?? '';
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
        <span class="pz-badge <?= $badgeCls ?>"><?= esc(strtoupper($status)) ?></span>
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

        <?php if ($model): ?>
        <span class="pz-info-label">Model</span>
        <span class="pz-info-value"><?= esc($model) ?></span>
        <?php endif; ?>

        <span class="pz-info-label">Created at</span>
        <span class="pz-info-value"><?= esc($createdAt) ?></span>

        <?php if (!empty($req['approved_by_name'])): ?>
        <div class="pz-info-divider"></div>
        <span class="pz-info-label">1st Approval</span>
        <span class="pz-info-value">
            <?= esc($req['approved_by_name']) ?>
            <?php if (!empty($req['approved_by_role'])): ?><span class="role-badge" style="font-size:0.7rem;margin-left:.3rem;"><?= esc($req['approved_by_role']) ?></span><?php endif; ?>
            <?= !empty($req['approved_at_fmt']) ? '<span style="opacity:.6;font-size:0.85rem;margin-left:.3rem;">· ' . esc($req['approved_at_fmt']) . '</span>' : '' ?>
        </span>
        <?php endif; ?>

        <?php if (!empty($req['secondary_approved_by_name'])): ?>
        <span class="pz-info-label">2nd Approval</span>
        <span class="pz-info-value">
            <?= esc($req['secondary_approved_by_name']) ?>
            <?php if (!empty($req['secondary_approved_by_role'])): ?><span class="role-badge" style="font-size:0.7rem;margin-left:.3rem;"><?= esc($req['secondary_approved_by_role']) ?></span><?php endif; ?>
            <?= !empty($req['secondary_approved_at_fmt']) ? '<span style="opacity:.6;font-size:0.85rem;margin-left:.3rem;">· ' . esc($req['secondary_approved_at_fmt']) . '</span>' : '' ?>
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
        <div style="display:flex; align-items:center; justify-content:space-between; padding:.5rem 0; border-bottom:1px solid #f0e8d0;">
            <div>
                <div style="font-weight:700; font-size:1rem; color:#2c1a00"><?= esc(is_array($res) ? ($res['name'] ?? $res['id'] ?? '') : $res) ?></div>
                <?php if (is_array($res) && !empty($res['matched_by'])): ?>
                <div style="font-size:1rem; color:#9a7b3a">Matched by: <?= esc($res['matched_by']) ?></div>
                <?php endif; ?>
            </div>
            <span class="pz-badge pz-badge-pending">Pending</span>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Approval section -->
<?php if ($canApprove && ($isPending || $isPendingSecondary)): ?>
<div class="pz-card">
    <p class="pz-card-section">Approval</p>

    <?php if ($isPendingSecondary): ?>
        <!-- Primary approval info -->
        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:.7rem 1rem;margin-bottom:.85rem;font-size:0.92rem;color:#0c4a6e;">
            <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;margin-right:.3rem;">verified</span>
            <strong>1st approval:</strong> <?= esc($req['approved_by_name'] ?? '—') ?>
            <?php if ($primaryApproverRole): ?><span style="opacity:.7"> · <?= esc($primaryApproverRole) ?></span><?php endif; ?>
            <?php if (!empty($req['approved_at_fmt'])): ?><span style="opacity:.7"> · <?= esc($req['approved_at_fmt']) ?></span><?php endif; ?>
            <br><span style="opacity:.8">Approved source:</span> <strong><?= esc($req['approved_source_project_name'] ?? '—') ?></strong>
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
