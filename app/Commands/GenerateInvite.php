<?php

namespace App\Commands;

use App\Libraries\FirestoreService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Google\Cloud\Core\Timestamp;

class GenerateInvite extends BaseCommand
{
    protected $group       = 'Auth';
    protected $name        = 'auth:invite';
    protected $description = 'Generate a one-time admin registration link and print it to the terminal.';
    protected $usage       = 'auth:invite [--expires <hours>]';
    protected $options     = [
        '--expires' => 'Hours until the link expires (default: 48)',
    ];

    public function run(array $params)
    {
        $hours = (int) CLI::getOption('expires') ?: 48;

        try {
            $token = bin2hex(random_bytes(32));
            $now   = new Timestamp(new \DateTime());
            $exp   = new Timestamp((new \DateTime())->modify("+{$hours} hours"));

            $db = FirestoreService::getInstance()->getDb();
            $db->collection('invite_tokens')->document($token)->set([
                'token'          => $token,
                'created_at'     => $now,
                'expires_at'     => $exp,
                'used'           => false,
                'created_by_uid' => 'cli',
            ]);

            $url = base_url('register?token=' . $token);

            CLI::newLine();
            CLI::write('  Admin Invite Link Generated', 'green');
            CLI::write('  ' . str_repeat('─', 60), 'dark_gray');
            CLI::write('  URL     : ' . $url, 'yellow');
            CLI::write('  Expires : ' . $hours . ' hours from now', 'dark_gray');
            CLI::write('  Use     : single-use only', 'dark_gray');
            CLI::write('  ' . str_repeat('─', 60), 'dark_gray');
            CLI::newLine();
        } catch (\Throwable $e) {
            CLI::error('Failed to generate invite token: ' . $e->getMessage());
        }
    }
}
