<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrimaZentra | Dashboard</title>
    <link rel="stylesheet" href="<?= base_url('public/css/styles.css') ?>">
    <style>
        body {
            display: flex;
            background: #f8f8f8;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 2rem;
            min-height: 100vh;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #f0f0f0;
        }

        .header h1 {
            margin: 0;
            color: #212529;
        }

        .user-info {
            text-align: right;
        }

        .user-info strong {
            display: block;
            color: #212529;
            font-size: 1rem;
        }

        .user-info small {
            color: #D99600;
            font-weight: 600;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .summary-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #f0f0f0;
            text-align: center;
            transition: all 0.3s;
        }

        .summary-card:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-color: #D99600;
        }

        .summary-card p {
            margin: 0 0 1rem 0;
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-card h2 {
            margin: 0;
            color: #D99600;
            font-size: 2.5rem;
        }

        .section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #f0f0f0;
            margin-bottom: 2rem;
        }

        .section h3 {
            margin-top: 0;
            color: #212529;
            border-bottom: 2px solid #D99600;
            padding-bottom: 1rem;
            font-size: 1.25rem;
        }

        .blocker-item {
            padding: 1rem;
            margin-bottom: 1rem;
            background: #fff5f0;
            border-left: 4px solid #e74c3c;
            border-radius: 4px;
        }

        .blocker-item strong {
            color: #212529;
            display: block;
            margin-bottom: 0.5rem;
        }

        .blocker-item p {
            margin: 0.5rem 0;
            color: #666;
            font-size: 0.9rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        table th {
            background: #f5f5f5;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #212529;
            border-bottom: 1px solid #e0e0e0;
        }

        table td {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
            color: #666;
        }

        table tr:hover {
            background: #f9f9f9;
        }

        .empty-message {
            text-align: center;
            padding: 2rem;
            color: #999;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .summary-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?= view('sidebar') ?>

<!-- MAIN CONTENT -->
<div class="main-content">
    <!-- HEADER -->
    <div class="header">
        <h1>Dashboard Summary</h1>
        <div class="user-info">
            <strong><?= esc($userName ?? 'User') ?></strong>
            <small><?= strtoupper($role ?? 'USER') ?></small>
        </div>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="summary-cards">
        <div class="summary-card">
            <p>Active Projects</p>
            <h2><?= $projectCount ?? 0 ?></h2>
        </div>
        <div class="summary-card">
            <p>Available Resources</p>
            <h2><?= count($availableResources ?? []) ?></h2>
        </div>
        <div class="summary-card">
            <p>Deployed Resources</p>
            <h2><?= count($deployedResources ?? []) ?></h2>
        </div>
        <div class="summary-card">
            <p>Total Resources</p>
            <h2><?= $resourceCount ?? 0 ?></h2>
        </div>
    </div>

    <!-- BLOCKERS SECTION -->
    <div class="section">
        <h3>Project Blockers</h3>
        <?php if (!empty($blockers)): ?>
            <?php foreach ($blockers as $blocker): ?>
                <div class="blocker-item">
                    <strong><?= esc((string)$blocker['project_name'] ?? 'N/A') ?></strong>
                    <p><strong>Status:</strong> <?= esc((string)$blocker['status'] ?? 'N/A') ?></p>
                    <p><strong>Completion:</strong> <?= ($blocker['completion_percent'] ?? 0) ?>%</p>
                    <p><strong>Project Type:</strong> <?= esc((string)$blocker['project_type'] ?? 'N/A') ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-message">
                <p>No blockers at the moment. All projects are on track! ✓</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- AVAILABLE TOOLS SECTION -->
    <div class="section">
        <h3>Available Equipment & Tools</h3>
        <?php if (!empty($tools)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Condition</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tools as $tool): ?>
                        <tr>
                            <td><?= esc((string)$tool['name'] ?? 'N/A') ?></td>
                            <td><?= esc((string)$tool['category'] ?? 'Equipment') ?></td>
                            <td><?= esc((string)$tool['condition'] ?? 'Functional') ?></td>
                            <td><span class="status-badge"><?= esc((string)$tool['status'] ?? 'Available') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-message">
                <p>No equipment or tools available.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- MANPOWER SECTION -->
    <div class="section">
        <h3>Available Manpower</h3>
        <?php if (!empty($availableManpower)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Specialization</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($availableManpower as $worker): ?>
                        <tr>
                            <td><?= esc((string) ($worker['name'] ?? 'N/A')) ?></td>
                            <td><?= esc((string) ($worker['role'] ?? 'Field Worker')) ?></td>
                            <td><?= esc((string) ($worker['specialization'] ?? 'N/A')) ?></td>
                            <td><span class="status-badge"><?= esc((string) ($worker['status'] ?? 'Available')) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-message">
                <p>No manpower available at the moment.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
