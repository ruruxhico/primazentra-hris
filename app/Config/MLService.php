<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class MLService extends BaseConfig
{
    public string $baseUrl = 'http://127.0.0.1:8000';
    public int    $timeoutSeconds = 5;
    public $mcdaDistanceWeight = 0.7;
    public $mcdaAvailabilityWeight = 0.3;

    public function __construct()
    {
        parent::__construct();
        $this->baseUrl        = env('ml.baseUrl',         $this->baseUrl);
        $this->timeoutSeconds = (int) env('ml.timeoutSeconds', $this->timeoutSeconds);
    }
}
