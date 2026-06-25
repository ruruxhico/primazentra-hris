<?php

namespace App\Libraries;

use Config\Services;

class MLServiceClient
{
    protected string $baseUrl;
    protected int    $timeout;

    public function __construct()
    {
        $this->baseUrl = env('ml.baseUrl', 'http://127.0.0.1:8000');
        $this->timeout = (int) env('ml.timeoutSeconds', 2);
    }

    public function predictBlockers(array $projectData): array
    {
        try {
            $response = Services::curlrequest()->post($this->baseUrl . '/predict/blockers', [
                'json'            => $projectData,
                'headers'         => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
                'timeout'         => $this->timeout,
                'connect_timeout' => 5,
            ]);
            return json_decode($response->getBody(), true) ?? [];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function predictDelay(array $projectData): array
    {
        try {
            $response = Services::curlrequest()->post($this->baseUrl . '/predict/delay', [
                'json'            => $projectData,
                'headers'         => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
                'timeout'         => $this->timeout,
                'connect_timeout' => 5,
            ]);
            return json_decode($response->getBody(), true) ?? [];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function predictToolAvailability(array $sourcingData): array
    {
        try {
            $response = Services::curlrequest()->post($this->baseUrl . '/predict/tool-availability', [
                'json'            => $sourcingData,
                'headers'         => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
                'timeout'         => $this->timeout,
                'connect_timeout' => 5,
            ]);
            return json_decode($response->getBody(), true) ?? [];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
