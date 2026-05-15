<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
    /* Ensure tab visibility is always correct */
    #equipment.tab-content,
    #tools.tab-content,
    #manpower.tab-content {
        display: none !important;
    }
    
    #equipment.tab-content-active,
    #tools.tab-content-active,
    #manpower.tab-content-active {
        display: block !important;
    }
</style>

<header class="resource-header">
    <h1>Resources</h1>
    <div class="user-profile">
        <strong><?= esc($userName ?? 'User') ?></strong><br>
        <small><?= strtoupper($role ?? 'USER') ?></small>
    </div>
</header>

<!-- Tabs Navigation -->
<div class="resource-tabs">
    <button id="tab-equipment" class="tab-btn tab-btn-active" onclick="showResourceTab('equipment')">
        EQUIPMENT
    </button>
    <button id="tab-tools" class="tab-btn" onclick="showResourceTab('tools')">
        TOOLS
    </button>
    <button id="tab-manpower" class="tab-btn" onclick="showResourceTab('manpower')">
        MANPOWER
    </button>
</div>

<!-- EQUIPMENT TAB -->
<div id="equipment" class="tab-content tab-content-active">
    <!-- Search and Filter Section -->
    <div class="resource-controls">
        <input type="text" id="searchEquipment" placeholder="🔍 Search equipment..." class="search-input">
        
        <button class="btn btn-filter">
            ▼ FILTER
        </button>
        
        <a href="<?= base_url('resources/create') ?>" class="btn btn-success">
            + ADD
        </a>
        
        <button class="btn btn-import">
            ⬇ IMPORT
        </button>
        
        <button class="btn btn-export">
            ⬆ EXPORT
        </button>
    </div>

    <!-- Equipment Table -->
    <div class="table-container">
        <table class="resource-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>EQUIPMENT NAME</th>
                    <th>STATUS</th>
                    <th>CONDITION</th>
                    <th>NEXT AVAILABLE</th>
                    <th>SERIAL NUMBER</th>
                    <th>TAGS</th>
                    <th class="text-center">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($equipment)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            No equipment found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($equipment as $index => $item): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><strong><?= esc((string)($item['name'] ?? 'N/A')) ?></strong></td>
                            <td>
                                <span class="status-badge status-<?= strtolower(str_replace('-', '', $item['status'] ?? 'available')) ?>">
                                    <?= esc((string)($item['status'] ?? 'N/A')) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (isset($item['asset_details']['condition_summary']) && is_array($item['asset_details']['condition_summary'])): ?>
                                    <span class="condition-badge">
                                        <?= esc((string)(array_key_first($item['asset_details']['condition_summary']) ?? 'N/A')) ?>
                                    </span>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?= isset($item['next_availability']) ? esc((string)$item['next_availability']) : 'N/A' ?></td>
                            <td><?= isset($item['asset_details']['model_number']) ? esc((string)$item['asset_details']['model_number']) : 'N/A' ?></td>
                            <td>
                                <?php if (isset($item['asset_details']['condition_summary']) && is_array($item['asset_details']['condition_summary'])): ?>
                                    <?php foreach ($item['asset_details']['condition_summary'] as $condition => $count): ?>
                                        <span class="tag-badge tag-<?= strtolower($condition) ?>"><?= esc((string)$condition) ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('resources/viewItems/' . $item['id']) ?>" class="action-btn" title="View">👁️</a>
                                <a href="<?= base_url('resources/edit/' . $item['id']) ?>" class="action-btn" title="Edit">✏️</a>
                                <a href="<?= base_url('resources/delete/' . $item['id']) ?>" class="action-btn" title="Delete" onclick="return confirm('Are you sure?');">🗑️</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <button class="pagination-btn">< </button>
        <button class="pagination-btn pagination-btn-active">1</button>
        <button class="pagination-btn">2</button>
        <button class="pagination-btn">3</button>
        <button class="pagination-btn">4</button>
        <button class="pagination-btn">5</button>
        <button class="pagination-btn"> ></button>
    </div>
</div>

<!-- TOOLS TAB -->
<div id="tools" class="tab-content">
    <!-- Search and Filter Section -->
    <div class="resource-controls">
        <input type="text" id="searchTools" placeholder="🔍 Search tools..." class="search-input">
        
        <button class="btn btn-filter">
            ▼ FILTER
        </button>
        
        <a href="<?= base_url('resources/create') ?>" class="btn btn-success">
            + ADD
        </a>
        
        <button class="btn btn-import">
            ⬇ IMPORT
        </button>
        
        <button class="btn btn-export">
            ⬆ EXPORT
        </button>
    </div>

    <!-- Tools Table -->
    <div class="table-container">
        <table class="resource-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>TOOL NAME</th>
                    <th>STATUS</th>
                    <th>CONDITION</th>
                    <th>NEXT AVAILABLE</th>
                    <th>SERIAL NUMBER</th>
                    <th>TAGS</th>
                    <th class="text-center">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    $tools = isset($equipment) ? array_filter($equipment, function($item) {
                        return isset($item['category']) && $item['category'] === 'Tools';
                    }) : [];
                ?>
                <?php if (empty($tools)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            No tools found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tools as $index => $item): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><strong><?= esc((string)($item['name'] ?? 'N/A')) ?></strong></td>
                            <td>
                                <span class="status-badge status-<?= strtolower(str_replace('-', '', $item['status'] ?? 'available')) ?>">
                                    <?= esc((string)($item['status'] ?? 'N/A')) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (isset($item['asset_details']['condition_summary']) && is_array($item['asset_details']['condition_summary'])): ?>
                                    <span class="condition-badge">
                                        <?= esc((string)(array_key_first($item['asset_details']['condition_summary']) ?? 'N/A')) ?>
                                    </span>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?= isset($item['next_availability']) ? esc((string)$item['next_availability']) : 'N/A' ?></td>
                            <td><?= isset($item['asset_details']['model_number']) ? esc((string)$item['asset_details']['model_number']) : 'N/A' ?></td>
                            <td>
                                <?php if (isset($item['asset_details']['condition_summary']) && is_array($item['asset_details']['condition_summary'])): ?>
                                    <?php foreach ($item['asset_details']['condition_summary'] as $condition => $count): ?>
                                        <span class="tag-badge tag-<?= strtolower($condition) ?>"><?= esc((string)$condition) ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('resources/viewItems/' . $item['id']) ?>" class="action-btn" title="View">👁️</a>
                                <a href="<?= base_url('resources/edit/' . $item['id']) ?>" class="action-btn" title="Edit">✏️</a>
                                <a href="<?= base_url('resources/delete/' . $item['id']) ?>" class="action-btn" title="Delete" onclick="return confirm('Are you sure?');">🗑️</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <button class="pagination-btn">< </button>
        <button class="pagination-btn pagination-btn-active">1</button>
        <button class="pagination-btn">2</button>
        <button class="pagination-btn">3</button>
        <button class="pagination-btn">4</button>
        <button class="pagination-btn">5</button>
        <button class="pagination-btn"> ></button>
    </div>
</div>

<!-- MANPOWER TAB -->
<div id="manpower" class="tab-content">
    <!-- Search and Filter Section -->
    <div class="resource-controls">
        <input type="text" id="searchManpower" placeholder="🔍 Search manpower..." class="search-input">
        
        <button class="btn btn-filter">
            ▼ FILTER
        </button>
        
        <a href="<?= base_url('resources/create') ?>" class="btn btn-success">
            + ADD
        </a>
        
        <button class="btn btn-import">
            ⬇ IMPORT
        </button>
        
        <button class="btn btn-export">
            ⬆ EXPORT
        </button>
    </div>

    <!-- Manpower Table -->
    <div class="table-container">
        <table class="resource-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>LAST NAME</th>
                    <th>FIRST NAME</th>
                    <th>NEXT AVAILABLE</th>
                    <th>STATUS</th>
                    <th>CURRENT SITE</th>
                    <th>SPECIALIZATION</th>
                    <th>ROLE</th>
                    <th class="text-center">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($manpower)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">
                            No manpower records found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($manpower as $index => $person): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= isset($person['name']) ? esc((string)$person['name']) : 'N/A' ?></td>
                            <td><?= isset($person['manpower_details']['role']) ? esc((string)$person['manpower_details']['role']) : 'N/A' ?></td>
                            <td><?= isset($person['next_availability']) ? esc((string)$person['next_availability']) : 'N/A' ?></td>
                            <td>
                                <span class="status-badge status-<?= strtolower(str_replace('-', '', $person['status'] ?? 'available')) ?>">
                                    <?= esc((string)($person['status'] ?? 'N/A')) ?>
                                </span>
                            </td>
                            <td><?= isset($person['location']['address']) ? esc((string)$person['location']['address']) : 'N/A' ?></td>
                            <td><?= isset($person['manpower_details']['specialization']) ? esc((string)$person['manpower_details']['specialization']) : 'N/A' ?></td>
                            <td>
                                <span class="role-badge role-<?= isset($person['manpower_details']['role']) ? strtolower(str_replace(' ', '', $person['manpower_details']['role'])) : 'n/a' ?>">
                                    <?= isset($person['manpower_details']['role']) ? esc((string)$person['manpower_details']['role']) : 'N/A' ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('resources/edit/' . $person['id']) ?>" class="action-btn" title="View">👁️</a>
                                <a href="<?= base_url('resources/edit/' . $person['id']) ?>" class="action-btn" title="Edit">✏️</a>
                                <a href="<?= base_url('resources/delete/' . $person['id']) ?>" class="action-btn" title="Delete" onclick="return confirm('Are you sure?');">🗑️</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <button class="pagination-btn">< </button>
        <button class="pagination-btn pagination-btn-active">1</button>
        <button class="pagination-btn">2</button>
        <button class="pagination-btn">3</button>
        <button class="pagination-btn">4</button>
        <button class="pagination-btn">5</button>
        <button class="pagination-btn"> ></button>
    </div>
</div>

<script>
    function showResourceTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(el => {
            el.classList.remove('tab-content-active');
        });
        document.querySelectorAll('.tab-btn').forEach(el => {
            el.classList.remove('tab-btn-active');
        });
        
        // Show selected tab
        const selectedTab = document.getElementById(tabName);
        const selectedBtn = document.getElementById('tab-' + tabName);
        
        if (selectedTab) selectedTab.classList.add('tab-content-active');
        if (selectedBtn) selectedBtn.classList.add('tab-btn-active');
    }
</script>

<?= $this->endSection() ?>
