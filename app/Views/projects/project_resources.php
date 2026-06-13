<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $projectId = $projectId ?? '';
    $project   = $project   ?? [];
    $projectName = strtoupper($project['project_name'] ?? 'PROJECT');
?>

<!-- Header -->
<div class="pr-header">
    <a href="<?= base_url('projects/show/' . $projectId) ?>" class="pr-back">
        <span class="material-symbols-outlined">arrow_back</span>
    </a>
    <h1 class="pr-title"><?= esc($projectName) ?></h1>
</div>
<p class="pr-subtitle">View what is already assigned and add more from the existing resource lists.</p>

<!-- Currently Assigned -->
<div class="pr-section">
    <h2 class="pr-section-title">Currently Assigned</h2>

    <?php if (empty($assignedManpower) && empty($assignedEqGroups)): ?>
    <p class="pr-empty">No resources assigned yet.</p>
    <?php else: ?>

        <?php if (!empty($assignedEqGroups)): ?>
        <h3 class="pr-subsection-title">Equipment / Tools</h3>
        <?php foreach ($assignedEqGroups as $g): ?>
        <div class="pr-assigned-item">
            <span class="material-symbols-outlined pr-assigned-icon">build</span>
            <div>
                <div class="pr-assigned-name"><?= esc(strtoupper($g['name'])) ?></div>
                <div class="pr-assigned-meta"><?= (int)$g['count'] ?> assigned unit(s)</div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($assignedManpower)): ?>
        <h3 class="pr-subsection-title">Manpower</h3>
        <?php foreach ($assignedManpower as $a): ?>
        <div class="pr-assigned-item">
            <span class="material-symbols-outlined pr-assigned-icon">person</span>
            <div class="pr-assigned-name"><?= esc(strtoupper($a['name'])) ?></div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

    <?php endif; ?>
</div>

<!-- Tabs -->
<div class="pr-tabs" id="prTabs">
    <button class="pr-tab active" data-tab="manpower">MANPOWER</button>
    <button class="pr-tab" data-tab="equipment">EQUIPMENT</button>
    <button class="pr-tab" data-tab="tools">TOOLS</button>
</div>

<!-- MANPOWER -->
<div class="pr-tab-content active" id="prTab-manpower">
    <?php if (empty($allManpower)): ?>
    <p class="pr-empty">No manpower records found.</p>
    <?php else: ?>
    <?php foreach ($allManpower as $m):
        $mpId          = $m['id'];
        $firstName     = trim((string)($m['first_name'] ?? ''));
        $lastName      = trim((string)($m['last_name']  ?? ''));
        $mpName        = strtoupper($firstName . ($firstName && $lastName ? ' ' : '') . $lastName)
                         ?: strtoupper($m['name'] ?? 'UNNAMED');
        $mpRole        = strtoupper($m['role'] ?? $m['manpower_role'] ?? '');
        $mpStatus      = $m['status'] ?? 'Available';
        $mpSite        = $m['current_site'] ?? '';
        $isHere        = isset($assignedManpowerIds[$mpId]);
        $assignmentId  = $assignedManpowerIds[$mpId] ?? '';
    ?>
    <div class="pr-resource-card">
        <div class="pr-resource-info">
            <div class="pr-resource-name"><?= esc($mpName) ?></div>
            <?php if ($mpRole): ?><div class="pr-resource-role"><?= esc($mpRole) ?></div><?php endif; ?>
            <?php if ($isHere): ?>
                <div class="pr-resource-site pr-site-here">Assigned to this project</div>
            <?php elseif ($mpSite): ?>
                <div class="pr-resource-site">Current site: <?= esc($mpSite) ?></div>
            <?php endif; ?>
        </div>
        <?php if ($isHere): ?>
        <button class="pr-btn pr-btn-remove"
                data-action="unassign"
                data-assignment-id="<?= esc($assignmentId) ?>">REMOVE</button>
        <?php elseif ($mpStatus === 'Available'): ?>
        <button class="pr-btn pr-btn-assign"
                data-action="assign"
                data-type="manpower"
                data-ref-id="<?= esc($mpId) ?>"
                data-name="<?= esc($mpName) ?>">ASSIGN</button>
        <?php else: ?>
        <button class="pr-btn pr-btn-unavail" disabled>UNAVAILABLE</button>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- EQUIPMENT -->
<div class="pr-tab-content" id="prTab-equipment">
    <?php if (empty($allEquipment)): ?>
    <p class="pr-empty">No equipment found.</p>
    <?php else: ?>
    <?php foreach ($allEquipment as $eq):
        $eqId      = $eq['id'];
        $eqName    = strtoupper($eq['name'] ?? '');
        $eqStats   = $resourceItemStats[$eqId] ?? ['total' => 0, 'available' => 0];
    ?>
    <div class="pr-resource-card">
        <div class="pr-resource-info">
            <div class="pr-resource-name"><?= esc($eqName) ?></div>
            <div class="pr-resource-meta">Total Qty: <?= $eqStats['total'] ?> &bull; Available: <?= $eqStats['available'] ?></div>
        </div>
        <button class="pr-btn pr-btn-view"
                data-action="view-units"
                data-resource-id="<?= esc($eqId) ?>"
                data-resource-name="<?= esc($eqName) ?>"
                data-type="equipment">VIEW UNITS</button>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- TOOLS -->
<div class="pr-tab-content" id="prTab-tools">
    <?php if (empty($allTools)): ?>
    <p class="pr-empty">No tools found.</p>
    <?php else: ?>
    <?php foreach ($allTools as $tool):
        $toolId    = $tool['id'];
        $toolName  = strtoupper($tool['name'] ?? '');
        $toolStats = $resourceItemStats[$toolId] ?? ['total' => 0, 'available' => 0];
    ?>
    <div class="pr-resource-card">
        <div class="pr-resource-info">
            <div class="pr-resource-name"><?= esc($toolName) ?></div>
            <div class="pr-resource-meta">Total Qty: <?= $toolStats['total'] ?> &bull; Available: <?= $toolStats['available'] ?></div>
        </div>
        <button class="pr-btn pr-btn-view"
                data-action="view-units"
                data-resource-id="<?= esc($toolId) ?>"
                data-resource-name="<?= esc($toolName) ?>"
                data-type="tools">VIEW UNITS</button>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Units Modal -->
<div class="pr-modal-overlay" id="prUnitsModal" style="display:none" aria-modal="true">
    <div class="pr-modal">
        <div class="pr-modal-header">
            <h3 class="pr-modal-title" id="prModalTitle"></h3>
            <button class="pr-modal-close" id="prModalClose" aria-label="Close">&times;</button>
        </div>
        <p class="pr-modal-sub">Assign specific units from the resource list.</p>
        <div id="prModalUnits"><div class="pr-loading">Loading…</div></div>
    </div>
</div>

<script>
(function () {
    var projectId  = '<?= esc($projectId) ?>';
    var csrfName   = '<?= csrf_token() ?>';
    var csrfVal    = '<?= csrf_hash() ?>';

    /* ── Tabs ── */
    document.getElementById('prTabs').addEventListener('click', function (e) {
        var tab = e.target.closest('.pr-tab');
        if (!tab) return;
        document.querySelectorAll('.pr-tab').forEach(function (t) { t.classList.remove('active'); });
        document.querySelectorAll('.pr-tab-content').forEach(function (c) { c.classList.remove('active'); });
        tab.classList.add('active');
        var panel = document.getElementById('prTab-' + tab.dataset.tab);
        if (panel) panel.classList.add('active');
    });

    /* ── Delegate all button actions ── */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-action]');
        if (!btn || btn.disabled) return;
        var action = btn.dataset.action;

        if (action === 'assign')        { doAssign(btn); }
        if (action === 'unassign')      { doUnassign(btn); }
        if (action === 'view-units')    { openModal(btn.dataset.resourceId, btn.dataset.resourceName, btn.dataset.type); }
        if (action === 'assign-unit')   { doAssignUnit(btn); }
        if (action === 'unassign-unit') { doUnassignUnit(btn); }
    });

    function post(url, body) {
        body[csrfName] = csrfVal;
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(body),
        }).then(function (r) { return r.json(); });
    }

    function doAssign(btn) {
        btn.disabled = true; btn.textContent = '…';
        post('<?= base_url('projects/') ?>' + projectId + '/resources/assign', {
            type:   btn.dataset.type,
            ref_id: btn.dataset.refId,
            name:   btn.dataset.name,
        }).then(function (d) {
            if (d.success) { location.reload(); }
            else { alert(d.message || 'Failed'); btn.disabled = false; btn.textContent = 'ASSIGN'; }
        }).catch(function () { btn.disabled = false; btn.textContent = 'ASSIGN'; });
    }

    function doUnassign(btn) {
        if (!confirm('Remove this assignment?')) return;
        btn.disabled = true;
        post('<?= base_url('projects/') ?>' + projectId + '/resources/unassign', {
            assignment_id: btn.dataset.assignmentId,
        }).then(function (d) {
            if (d.success) { location.reload(); }
            else { alert(d.message || 'Failed'); btn.disabled = false; }
        });
    }

    /* ── Modal ── */
    var modal     = document.getElementById('prUnitsModal');
    var modalTitle = document.getElementById('prModalTitle');
    var modalUnits = document.getElementById('prModalUnits');
    var currentResourceId, currentType;

    document.getElementById('prModalClose').addEventListener('click', function () { modal.style.display = 'none'; });
    modal.addEventListener('click', function (e) { if (e.target === modal) modal.style.display = 'none'; });

    function openModal(resourceId, resourceName, type) {
        currentResourceId = resourceId;
        currentType       = type;
        modalTitle.textContent = resourceName;
        modalUnits.innerHTML = '<div class="pr-loading">Loading…</div>';
        modal.style.display = 'flex';

        fetch('<?= base_url('projects/') ?>' + projectId + '/resources/units/' + resourceId)
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.success || !d.items || d.items.length === 0) {
                    modalUnits.innerHTML = '<p class="pr-empty">' + (d.success ? 'No units found.' : 'Failed to load units.') + '</p>';
                    return;
                }
                var html = '';
                d.items.forEach(function (item) {
                    var serial = item.serial_number || item.unit_name || item.id;
                    var status = item.status || 'Unknown';
                    if (item.assigned_to_this_project) {
                        html += unitCard(serial, status,
                            '<button class="pr-btn pr-btn-remove" data-action="unassign-unit" data-assignment-id="' + esc(item.assignment_id) + '">REMOVE</button>');
                    } else if (status === 'Available') {
                        html += unitCard(serial, status,
                            '<button class="pr-btn pr-btn-assign" data-action="assign-unit"' +
                            ' data-resource-id="' + esc(resourceId) + '"' +
                            ' data-unit-id="' + esc(item.id) + '"' +
                            ' data-unit-name="' + esc(serial) + '"' +
                            ' data-type="' + esc(type) + '">ASSIGN</button>');
                    } else {
                        html += unitCard(serial, status, '<button class="pr-btn pr-btn-unavail" disabled>UNAVAILABLE</button>');
                    }
                });
                modalUnits.innerHTML = html;
            })
            .catch(function () { modalUnits.innerHTML = '<p class="pr-empty">Error loading units.</p>'; });
    }

    function unitCard(serial, status, btnHtml) {
        return '<div class="pr-unit-card">' +
            '<div><div class="pr-unit-serial">' + serial + '</div>' +
            '<div class="pr-unit-status">Status: ' + status + '</div></div>' +
            btnHtml + '</div>';
    }

    function doAssignUnit(btn) {
        var resourceName = modalTitle.textContent;
        btn.disabled = true; btn.textContent = '…';
        post('<?= base_url('projects/') ?>' + projectId + '/resources/assign', {
            type:      btn.dataset.type,
            ref_id:    btn.dataset.resourceId,
            name:      resourceName,
            unit_id:   btn.dataset.unitId,
            unit_name: btn.dataset.unitName,
        }).then(function (d) {
            if (d.success) { location.reload(); }
            else { alert(d.message || 'Failed'); btn.disabled = false; btn.textContent = 'ASSIGN'; }
        }).catch(function () { btn.disabled = false; btn.textContent = 'ASSIGN'; });
    }

    function doUnassignUnit(btn) {
        if (!confirm('Remove this unit?')) return;
        btn.disabled = true;
        post('<?= base_url('projects/') ?>' + projectId + '/resources/unassign', {
            assignment_id: btn.dataset.assignmentId,
        }).then(function (d) {
            if (d.success) { location.reload(); }
            else { btn.disabled = false; }
        });
    }

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }
}());
</script>

<?= $this->endSection() ?>
