<?php
// Seed: write milestone templates to milestone_templates/{type}/milestones/{id}
require __DIR__ . '/../vendor/autoload.php';

use App\Libraries\FirestoreService;
use Google\Cloud\Core\Timestamp;

if (!defined('WRITEPATH')) {
    define('WRITEPATH', realpath(__DIR__ . '/../writable') . DIRECTORY_SEPARATOR);
}

$dryRun = in_array('--dry-run', $argv, true);
echo "Seeding milestone templates (dry-run=" . ($dryRun ? 'true' : 'false') . ")\n";

$svc = FirestoreService::getInstance();
$db  = $svc->getDb();

$templates = [
    'CONVERSION' => [
        ['milestone_name' => 'SKOM',                      'weight_percent' => 5],
        ['milestone_name' => 'Site Lay-out',              'weight_percent' => 5],
        ['milestone_name' => 'Excavation',                'weight_percent' => 8],
        ['milestone_name' => 'Grounding',                 'weight_percent' => 7],
        ['milestone_name' => 'Lean Concrete',             'weight_percent' => 7],
        ['milestone_name' => 'Rebars Installation',       'weight_percent' => 8],
        ['milestone_name' => 'ABST Installation',         'weight_percent' => 8],
        ['milestone_name' => 'Formworks Installation',    'weight_percent' => 7],
        ['milestone_name' => 'Tower Foundation Casting',  'weight_percent' => 8],
        ['milestone_name' => 'Backfilling',               'weight_percent' => 6],
        ['milestone_name' => 'Tower Erection',            'weight_percent' => 9],
        ['milestone_name' => 'Electrical Works',          'weight_percent' => 8],
        ['milestone_name' => 'Cable Ladder Installation', 'weight_percent' => 7],
        ['milestone_name' => 'Site Development',          'weight_percent' => 7],
    ],
    'COLOCATION' => [
        ['milestone_name' => 'Site Layout',                              'weight_percent' => 4.8],
        ['milestone_name' => 'Trenching, Excavation',                    'weight_percent' => 4.8],
        ['milestone_name' => 'Order of Materials and Delivery',          'weight_percent' => 4.8],
        ['milestone_name' => 'Lean concrete',                            'weight_percent' => 4.8],
        ['milestone_name' => 'Rebars Fabrication and Formworks Preparation', 'weight_percent' => 4.8],
        ['milestone_name' => 'Formworks Inst',                           'weight_percent' => 4.8],
        ['milestone_name' => 'Rebar Inst',                               'weight_percent' => 4.8],
        ['milestone_name' => 'Casting(Bot) + Curing',                    'weight_percent' => 4.8],
        ['milestone_name' => 'CHB Laying',                               'weight_percent' => 4.8],
        ['milestone_name' => 'Backfill (1st and 2nd Layer)',              'weight_percent' => 4.8],
        ['milestone_name' => 'Casting(Top) + Curing',                    'weight_percent' => 4.8],
        ['milestone_name' => 'Plastering',                               'weight_percent' => 4.8],
        ['milestone_name' => 'Trenching (Electrical +FOC)',               'weight_percent' => 4.8],
        ['milestone_name' => 'Cable Tray Inst',                          'weight_percent' => 4.8],
        ['milestone_name' => 'Panel Board Inst',                         'weight_percent' => 4.8],
        ['milestone_name' => 'CEF',                                      'weight_percent' => 4.8],
        ['milestone_name' => 'Electrical Works',                         'weight_percent' => 4.8],
        ['milestone_name' => 'Grounding',                                'weight_percent' => 4.8],
        ['milestone_name' => 'Wire Termination',                         'weight_percent' => 4.8],
        ['milestone_name' => 'Site Clearing',                            'weight_percent' => 4.8],
        ['milestone_name' => 'Site Development',                         'weight_percent' => 4.0],
    ],
];

$now = new Timestamp(new DateTime());

foreach ($templates as $type => $milestones) {
    echo "  Type: {$type} (" . count($milestones) . " milestones)\n";

    $milestonesArray = [];
    $total = 0.0;

    foreach ($milestones as $order => $m) {
        $milestonesArray[] = [
            'milestone_name'         => $m['milestone_name'],
            'order'                  => $order,
            'default_weight_percent' => $m['weight_percent'],
            'default_target_quantity'=> 1,
            'default_unit'           => 'pcs',
        ];

        echo "    [" . str_pad((string)$order, 2) . "] {$m['milestone_name']} — {$m['weight_percent']}%\n";
        $total += $m['weight_percent'];
    }

    echo "    Total weight: " . round($total, 1) . "%\n";

    if (!$dryRun) {
        $db->collection('milestone_templates')->document($type)->set([
            'milestones'   => $milestonesArray,
            'project_type' => $type,
            'updated_at'   => $now,
        ]);
    }
}

echo $dryRun ? "\nDry run complete. Re-run without --dry-run to write.\n" : "\nDone.\n";
