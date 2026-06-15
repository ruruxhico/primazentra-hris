<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="customToast" class="custom-toast" aria-live="polite" aria-atomic="true"></div>

<meta name="csrf-name" content="<?= csrf_token() ?>">
<meta name="csrf-hash" content="<?= csrf_hash() ?>">

<div id="confirmDialog" class="confirm-dialog" role="dialog" aria-modal="true">
    <div class="confirm-backdrop"></div>
    <div class="confirm-box">
        <div class="confirm-content">
            <p id="confirmMessage">Are you sure?</p>
        </div>
        <div class="confirm-actions">
            <button id="confirmCancel" class="btn-toolbar">Cancel</button>
            <button id="confirmOk" class="btn-toolbar btn-add">Delete</button>
        </div>
    </div>
</div>

<?php
    $activeEquipFilters    = $activeEquipFilters    ?? [];
    $activeToolFilters     = $activeToolFilters     ?? [];
    $activeManpowerFilters = $activeManpowerFilters ?? [];
    $activeTab             = $activeTab             ?? 'equipment';
    $eqActiveCount         = count(array_filter($activeEquipFilters));
    $toolsActiveCount      = count(array_filter($activeToolFilters));
    $mpActiveCount         = count(array_filter($activeManpowerFilters));

    $eqPage          = $eqPage          ?? 1;
    $toolsPage       = $toolsPage       ?? 1;
    $mpPage          = $mpPage          ?? 1;
    $eqTotalPages    = $eqTotalPages    ?? 1;
    $toolsTotalPages = $toolsTotalPages ?? 1;
    $mpTotalPages    = $mpTotalPages    ?? 1;
    $eqOffset        = $eqOffset        ?? 0;
    $toolsOffset     = $toolsOffset     ?? 0;
    $mpOffset        = $mpOffset        ?? 0;

    function res_pager(int $current, int $totalPages, string $pageParam, array $extra = []): string {
        if ($totalPages <= 1) return '';
        $out = '<nav class="pager-wrapper"><ul class="pager-list">';
        if ($current > 1) {
            $out .= '<li class="pager-item"><a class="pager-link" href="?' . http_build_query(array_merge($extra, [$pageParam => 1])) . '">&laquo;</a></li>';
            $out .= '<li class="pager-item"><a class="pager-link" href="?' . http_build_query(array_merge($extra, [$pageParam => $current - 1])) . '">&lsaquo;</a></li>';
        }
        $start = max(1, $current - 2);
        $end   = min($totalPages, $current + 2);
        for ($p = $start; $p <= $end; $p++) {
            $cls = $p === $current ? 'pager-active' : 'pager-link';
            $out .= '<li class="pager-item"><a class="' . $cls . '" href="?' . http_build_query(array_merge($extra, [$pageParam => $p])) . '">' . $p . '</a></li>';
        }
        if ($current < $totalPages) {
            $out .= '<li class="pager-item"><a class="pager-link" href="?' . http_build_query(array_merge($extra, [$pageParam => $current + 1])) . '">&rsaquo;</a></li>';
            $out .= '<li class="pager-item"><a class="pager-link" href="?' . http_build_query(array_merge($extra, [$pageParam => $totalPages])) . '">&raquo;</a></li>';
        }
        $out .= '</ul></nav>';
        return $out;
    }
?>

<div class="pz-page-header">
    <h1>Resources</h1>
</div>

<!-- Tabs -->
<div class="pz-tabs">
    <button id="tab-equipment" class="pz-tab <?= $activeTab === 'equipment' ? 'active' : '' ?>" onclick="showResourceTab('equipment')">Equipment</button>
    <button id="tab-tools"     class="pz-tab <?= $activeTab === 'tools'     ? 'active' : '' ?>" onclick="showResourceTab('tools')">Tools</button>
    <button id="tab-manpower"  class="pz-tab <?= $activeTab === 'manpower'  ? 'active' : '' ?>" onclick="showResourceTab('manpower')">Manpower</button>
</div>

<!-- ═══════════════════ EQUIPMENT TAB ═══════════════════ -->
<div id="equipment" class="tab-content <?= $activeTab === 'equipment' ? 'tab-content-active' : '' ?>">

    <div class="pz-toolbar">
        <div class="pz-search-wrap">
            <span class="material-symbols-outlined">search</span>
            <input type="text" id="searchEquipment" placeholder="Search equipment…" class="pz-search">
        </div>
        <div class="pz-toolbar-right">
            <button type="button" class="pz-btn pz-btn-outline" id="eqFilterToggleBtn">
                <span class="material-symbols-outlined">filter_list</span>
                Filter<?php if ($eqActiveCount > 0): ?><span class="pz-filter-badge"><?= $eqActiveCount ?></span><?php endif; ?>
            </button>
            <a href="<?= base_url('resources/create?category=Equipment') ?>" class="pz-btn pz-btn-primary">
                <span class="material-symbols-outlined">add</span> Add
            </a>
            <button class="pz-btn pz-btn-ghost"><span class="material-symbols-outlined">upload</span> Import</button>
            <button class="pz-btn pz-btn-ghost"><span class="material-symbols-outlined">download</span> Export</button>
        </div>
    </div>

    <div id="eqFilterPanel" class="pz-filter-panel">
        <form id="eqFilterForm" method="get" action="<?= base_url('resources') ?>">
            <input type="hidden" name="tab" value="equipment">
            <input type="hidden" id="eqSortInput" name="eq_sort" value="<?= esc($activeEquipFilters['eq_sort'] ?? '') ?>">
            <div style="margin-bottom:.5rem"><p class="pz-filter-group"><label>Sort By</label></p>
            <div class="pz-chip-row">
                <button type="button" class="pz-chip <?= ($activeEquipFilters['eq_sort'] ?? '') === 'total_desc' ? 'active' : '' ?>" data-val="total_desc">Most Total</button>
                <button type="button" class="pz-chip <?= ($activeEquipFilters['eq_sort'] ?? '') === 'avail_desc' ? 'active' : '' ?>" data-val="avail_desc">Most Available</button>
            </div></div>
            <div class="pz-filter-footer">
                <a href="<?= base_url('resources?tab=equipment') ?>" class="pz-btn pz-btn-ghost pz-btn-sm"><span class="material-symbols-outlined">restart_alt</span> Reset</a>
            </div>
        </form>
    </div>

    <div class="pz-table-wrap">
        <table class="pz-table">
            <thead><tr>
                <th>#</th>
                <th>Equipment Name</th>
                <th>Total Qty</th>
                <th>Available</th>
                <th class="center">Actions</th>
            </tr></thead>
            <tbody>
                <?php if (empty($equipment)): ?>
                    <tr><td colspan="5" class="pz-table-empty">No equipment found. <a href="<?= base_url('resources/create?category=Equipment') ?>">Add now</a></td></tr>
                <?php else: ?>
                    <?php foreach ($equipment as $index => $item): ?>
                    <tr>
                        <td class="pz-row-num"><?= $eqOffset + $index + 1 ?></td>
                        <td class="pz-row-name"><?= esc((string)($item['name'] ?? 'N/A')) ?></td>
                        <td><?= esc((string)($item['total_quantity_display'] ?? $item['asset_details']['total_quantity'] ?? '0')) ?></td>
                        <td><?= esc((string)($item['available_units_display'] ?? $item['asset_details']['available_quantity'] ?? '0')) ?></td>
                        <td class="actions">
                            <a href="<?= base_url('resources/show/' . $item['id']) ?>" class="pz-btn-icon" title="View"><span class="material-symbols-outlined">visibility</span></a>
                            <a href="<?= base_url('resources/edit/' . $item['id']) ?>" class="pz-btn-icon" title="Edit"><span class="material-symbols-outlined">edit</span></a>
                            <a href="#" data-id="<?= esc((string)$item['id']) ?>" data-name="<?= esc((string)($item['name'] ?? 'this resource')) ?>" class="pz-btn-icon danger action-delete" title="Delete"><span class="material-symbols-outlined">delete</span></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= res_pager($eqPage, $eqTotalPages, 'eq_page', ['tab' => 'equipment', 'eq_sort' => $activeEquipFilters['eq_sort'] ?? '']) ?>
</div>

<!-- ═══════════════════ TOOLS TAB ═══════════════════ -->
<div id="tools" class="tab-content <?= $activeTab === 'tools' ? 'tab-content-active' : '' ?>">

    <div class="pz-toolbar">
        <div class="pz-search-wrap">
            <span class="material-symbols-outlined">search</span>
            <input type="text" id="searchTools" placeholder="Search tools…" class="pz-search">
        </div>
        <div class="pz-toolbar-right">
            <button type="button" class="pz-btn pz-btn-outline" id="toolsFilterToggleBtn">
                <span class="material-symbols-outlined">filter_list</span>
                Filter<?php if ($toolsActiveCount > 0): ?><span class="pz-filter-badge"><?= $toolsActiveCount ?></span><?php endif; ?>
            </button>
            <a href="<?= base_url('resources/create?category=Tools') ?>" class="pz-btn pz-btn-primary">
                <span class="material-symbols-outlined">add</span> Add
            </a>
            <button class="pz-btn pz-btn-ghost"><span class="material-symbols-outlined">upload</span> Import</button>
            <button class="pz-btn pz-btn-ghost"><span class="material-symbols-outlined">download</span> Export</button>
        </div>
    </div>

    <div id="toolsFilterPanel" class="pz-filter-panel">
        <form id="toolsFilterForm" method="get" action="<?= base_url('resources') ?>">
            <input type="hidden" name="tab" value="tools">
            <input type="hidden" id="toolsSortInput" name="tools_sort" value="<?= esc($activeToolFilters['tools_sort'] ?? '') ?>">
            <div style="margin-bottom:.5rem"><p class="pz-filter-group"><label>Sort By</label></p>
            <div class="pz-chip-row">
                <button type="button" class="pz-chip <?= ($activeToolFilters['tools_sort'] ?? '') === 'total_desc' ? 'active' : '' ?>" data-val="total_desc">Most Total</button>
                <button type="button" class="pz-chip <?= ($activeToolFilters['tools_sort'] ?? '') === 'avail_desc' ? 'active' : '' ?>" data-val="avail_desc">Most Available</button>
            </div></div>
            <div class="pz-filter-footer">
                <a href="<?= base_url('resources?tab=tools') ?>" class="pz-btn pz-btn-ghost pz-btn-sm"><span class="material-symbols-outlined">restart_alt</span> Reset</a>
            </div>
        </form>
    </div>

    <div class="pz-table-wrap">
        <table class="pz-table">
            <thead><tr>
                <th>#</th>
                <th>Tool Name</th>
                <th>Total Qty</th>
                <th>Available</th>
                <th class="center">Actions</th>
            </tr></thead>
            <tbody>
                <?php $tools = $tools ?? []; ?>
                <?php if (empty($tools)): ?>
                    <tr><td colspan="5" class="pz-table-empty">No tools found. <a href="<?= base_url('resources/create?category=Tools') ?>">Add now</a></td></tr>
                <?php else: ?>
                    <?php foreach ($tools as $index => $item): ?>
                    <tr>
                        <td class="pz-row-num"><?= $toolsOffset + $index + 1 ?></td>
                        <td class="pz-row-name"><?= esc((string)($item['name'] ?? 'N/A')) ?></td>
                        <td><?= esc((string)($item['total_quantity_display'] ?? $item['asset_details']['total_quantity'] ?? '0')) ?></td>
                        <td><?= esc((string)($item['available_units_display'] ?? $item['asset_details']['available_quantity'] ?? '0')) ?></td>
                        <td class="actions">
                            <a href="<?= base_url('resources/show/' . $item['id']) ?>" class="pz-btn-icon" title="View"><span class="material-symbols-outlined">visibility</span></a>
                            <a href="<?= base_url('resources/edit/' . $item['id']) ?>" class="pz-btn-icon" title="Edit"><span class="material-symbols-outlined">edit</span></a>
                            <a href="#" data-id="<?= esc((string)$item['id']) ?>" data-name="<?= esc((string)($item['name'] ?? 'this resource')) ?>" class="pz-btn-icon danger action-delete" title="Delete"><span class="material-symbols-outlined">delete</span></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= res_pager($toolsPage, $toolsTotalPages, 'tools_page', ['tab' => 'tools', 'tools_sort' => $activeToolFilters['tools_sort'] ?? '']) ?>
</div>

<!-- ═══════════════════ MANPOWER TAB ═══════════════════ -->
<div id="manpower" class="tab-content <?= $activeTab === 'manpower' ? 'tab-content-active' : '' ?>">

    <div class="pz-toolbar">
        <div class="pz-search-wrap">
            <span class="material-symbols-outlined">search</span>
            <input type="text" id="searchManpower" placeholder="Search manpower…" class="pz-search">
        </div>
        <div class="pz-toolbar-right">
            <button type="button" class="pz-btn pz-btn-outline" id="mpFilterToggleBtn">
                <span class="material-symbols-outlined">filter_list</span>
                Filter<?php if ($mpActiveCount > 0): ?><span class="pz-filter-badge"><?= $mpActiveCount ?></span><?php endif; ?>
            </button>
            <a href="<?= base_url('resources/create?category=Manpower') ?>" class="pz-btn pz-btn-primary">
                <span class="material-symbols-outlined">add</span> Add
            </a>
            <button class="pz-btn pz-btn-ghost"><span class="material-symbols-outlined">upload</span> Import</button>
            <button class="pz-btn pz-btn-ghost"><span class="material-symbols-outlined">download</span> Export</button>
        </div>
    </div>

    <div id="mpFilterPanel" class="pz-filter-panel">
        <form id="mpFilterForm" method="get" action="<?= base_url('resources') ?>">
            <input type="hidden" name="tab" value="manpower">
            <input type="hidden" id="mpRoleInput"   name="mp_role"   value="<?= esc($activeManpowerFilters['mp_role']   ?? '') ?>">
            <input type="hidden" id="mpSpecInput"   name="mp_spec"   value="<?= esc($activeManpowerFilters['mp_spec']   ?? '') ?>">
            <input type="hidden" id="mpStatusInput" name="mp_status" value="<?= esc($activeManpowerFilters['mp_status'] ?? '') ?>">

            <div style="display:flex;flex-direction:column;gap:.65rem">
                <div>
                    <p class="pz-filter-group" style="margin-bottom:.3rem"><label>Role</label></p>
                    <div class="pz-chip-row">
                        <?php foreach (['Field Worker','Foreman'] as $opt): ?>
                        <button type="button" class="pz-chip mp-role-chip <?= ($activeManpowerFilters['mp_role'] ?? '') === $opt ? 'active' : '' ?>" data-val="<?= esc($opt) ?>"><?= esc($opt) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <p class="pz-filter-group" style="margin-bottom:.3rem"><label>Specialization</label></p>
                    <div class="pz-chip-row">
                        <?php foreach (['Residential','Towers'] as $opt): ?>
                        <button type="button" class="pz-chip mp-spec-chip <?= ($activeManpowerFilters['mp_spec'] ?? '') === $opt ? 'active' : '' ?>" data-val="<?= esc($opt) ?>"><?= esc($opt) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <p class="pz-filter-group" style="margin-bottom:.3rem"><label>Status</label></p>
                    <div class="pz-chip-row">
                        <?php foreach (['Available','Deployed','On-Leave'] as $opt): ?>
                        <button type="button" class="pz-chip mp-status-chip <?= ($activeManpowerFilters['mp_status'] ?? '') === $opt ? 'active' : '' ?>" data-val="<?= esc($opt) ?>"><?= esc($opt) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="pz-filter-footer">
                <a href="<?= base_url('resources?tab=manpower') ?>" class="pz-btn pz-btn-ghost pz-btn-sm"><span class="material-symbols-outlined">restart_alt</span> Reset</a>
            </div>
        </form>
    </div>

    <div class="pz-table-wrap">
        <table class="pz-table">
            <thead><tr>
                <th>#</th>
                <th>Name</th>
                <th>Role</th>
                <th>Next Available</th>
                <th>Status</th>
                <th>Current Site</th>
                <th>Specialization</th>
                <th class="center">Actions</th>
            </tr></thead>
            <tbody>
                <?php if (empty($manpower)): ?>
                    <tr><td colspan="8" class="pz-table-empty">No manpower records found. <a href="<?= base_url('resources/create?category=Manpower') ?>">Add now</a></td></tr>
                <?php else: ?>
                    <?php foreach ($manpower as $index => $person): ?>
                    <tr>
                        <td class="pz-row-num"><?= $mpOffset + $index + 1 ?></td>
                        <td class="pz-row-name"><?= esc((string)($person['name'] ?? 'N/A')) ?></td>
                        <td><?= esc((string)($person['manpower_details']['role'] ?? 'N/A')) ?></td>
                        <td class="pz-row-muted"><?= esc((string)($person['next_availability'] ?? 'N/A')) ?></td>
                        <td>
                            <?php
                                $st  = strtolower($person['status'] ?? 'available');
                                $sbc = str_contains($st,'avail') ? 'pz-badge-available' : (str_contains($st,'deploy') ? 'pz-badge-deployed' : (str_contains($st,'leave') ? 'pz-badge-maintenance' : 'pz-badge-unavailable'));
                            ?>
                            <span class="pz-badge <?= $sbc ?>"><?= esc((string)($person['status'] ?? 'N/A')) ?></span>
                        </td>
                        <td class="pz-row-muted"><?= esc((string)($person['manpower_details']['current_site_deployment'] ?? $person['location']['address'] ?? 'N/A')) ?></td>
                        <td><?= esc((string)($person['manpower_details']['specialization'] ?? 'N/A')) ?></td>
                        <td class="actions">
                            <a href="<?= base_url('resources/show/' . $person['id']) ?>" class="pz-btn-icon" title="View"><span class="material-symbols-outlined">visibility</span></a>
                            <a href="<?= base_url('resources/edit/' . $person['id']) ?>" class="pz-btn-icon" title="Edit"><span class="material-symbols-outlined">edit</span></a>
                            <a href="#" data-id="<?= esc((string)$person['id']) ?>" data-name="<?= esc((string)($person['name'] ?? 'this resource')) ?>" class="pz-btn-icon danger action-delete" title="Delete"><span class="material-symbols-outlined">delete</span></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= res_pager($mpPage, $mpTotalPages, 'mp_page', array_merge(['tab' => 'manpower'], array_filter($activeManpowerFilters))) ?>
</div>

<script>
    function showResourceTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('tab-content-active'));
        document.querySelectorAll('.pz-tab').forEach(el => el.classList.remove('active'));
        const sel = document.getElementById(tabName);
        const btn = document.getElementById('tab-' + tabName);
        if (sel) sel.classList.add('tab-content-active');
        if (btn) btn.classList.add('active');
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Filter panels
        function wireFilterPanel(toggleId, panelId, activeCount) {
            const toggle = document.getElementById(toggleId);
            const panel  = document.getElementById(panelId);
            if (!toggle || !panel) return;
            if (activeCount > 0) panel.classList.add('open');
            toggle.addEventListener('click', () => panel.classList.toggle('open'));
        }
        wireFilterPanel('eqFilterToggleBtn',    'eqFilterPanel',    <?= $eqActiveCount ?>);
        wireFilterPanel('toolsFilterToggleBtn', 'toolsFilterPanel', <?= $toolsActiveCount ?>);
        wireFilterPanel('mpFilterToggleBtn',    'mpFilterPanel',    <?= $mpActiveCount ?>);

        // Sort chips (equipment + tools)
        function wireSortChips(formId, hiddenId) {
            const form = document.getElementById(formId);
            const inp  = document.getElementById(hiddenId);
            if (!form || !inp) return;
            form.querySelectorAll('.pz-chip[data-val]').forEach(chip => {
                chip.addEventListener('click', function () {
                    inp.value = inp.value === this.dataset.val ? '' : this.dataset.val;
                    form.submit();
                });
            });
        }
        wireSortChips('eqFilterForm',    'eqSortInput');
        wireSortChips('toolsFilterForm', 'toolsSortInput');

        // Manpower filter chips
        const mpForm = document.getElementById('mpFilterForm');
        if (mpForm) {
            function wireMpChips(selector, hiddenId) {
                const inp = document.getElementById(hiddenId);
                mpForm.querySelectorAll(selector).forEach(chip => {
                    chip.addEventListener('click', function () {
                        inp.value = inp.value === this.dataset.val ? '' : this.dataset.val;
                        mpForm.submit();
                    });
                });
            }
            wireMpChips('.mp-role-chip',   'mpRoleInput');
            wireMpChips('.mp-spec-chip',   'mpSpecInput');
            wireMpChips('.mp-status-chip', 'mpStatusInput');
        }

        // Search
        function attachSearch(inputId, tableSelector) {
            const search = document.getElementById(inputId);
            const table  = document.querySelector(tableSelector);
            if (!search || !table) return;
            search.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                table.querySelectorAll('tbody tr').forEach(row => {
                    row.style.display = q === '' || row.innerText.toLowerCase().includes(q) ? '' : 'none';
                });
            });
        }
        attachSearch('searchEquipment', '#equipment .pz-table');
        attachSearch('searchTools',     '#tools .pz-table');
        attachSearch('searchManpower',  '#manpower .pz-table');

        // Delete
        const csrfName = document.querySelector('meta[name="csrf-name"]')?.getAttribute('content');
        const csrfHash = document.querySelector('meta[name="csrf-hash"]')?.getAttribute('content');
        const confirmDialog  = document.getElementById('confirmDialog');
        const confirmMessage = document.getElementById('confirmMessage');
        const confirmOk      = document.getElementById('confirmOk');
        const confirmCancel  = document.getElementById('confirmCancel');
        const toast          = document.getElementById('customToast');

        function showToast(msg, type) {
            if (!toast) return;
            toast.className = 'custom-toast show ' + (type === 'error' ? 'error' : 'success');
            toast.innerText = msg;
            setTimeout(() => { toast.className = 'custom-toast'; }, 3000);
        }

        function animateRowOut(row) {
            const h = row.offsetHeight;
            row.style.height = h + 'px';
            row.style.overflow = 'hidden';
            requestAnimationFrame(() => {
                row.style.transition = 'height 260ms ease, opacity 260ms ease, transform 260ms ease';
                row.style.height = '0';
                row.style.opacity = '0';
                row.style.transform = 'translateX(-8px)';
            });
            setTimeout(() => row.remove(), 280);
        }

        let pendingDeleteId = null;
        let pendingRow = null;

        document.querySelectorAll('.action-delete').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                pendingDeleteId = this.dataset.id;
                pendingRow = this.closest('tr');
                confirmMessage.innerText = 'Delete "' + (this.dataset.name || 'this resource') + '"?';
                confirmDialog.classList.add('open');
            });
        });

        confirmCancel?.addEventListener('click', () => {
            pendingDeleteId = null; pendingRow = null;
            confirmDialog.classList.remove('open');
        });

        confirmOk?.addEventListener('click', () => {
            if (!pendingDeleteId) return;
            const body = new URLSearchParams();
            if (csrfName && csrfHash) body.append(csrfName, csrfHash);
            body.append('id', pendingDeleteId);

            fetch('<?= base_url('resources/delete') ?>', {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString()
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    showToast('Resource deleted', 'success');
                    if (pendingRow) animateRowOut(pendingRow);
                } else {
                    showToast(data.message || 'Delete failed', 'error');
                }
            })
            .catch(() => showToast('Error deleting resource', 'error'))
            .finally(() => {
                pendingDeleteId = null; pendingRow = null;
                confirmDialog.classList.remove('open');
            });
        });
    });
</script>

<?= $this->endSection() ?>
