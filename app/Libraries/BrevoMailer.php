<?php

namespace App\Libraries;

use Config\Services;

class BrevoMailer
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) env('BREVO_API_KEY', '');
    }

    public function send(string $toEmail, string $subject, string $html): void
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException('BREVO_API_KEY is not configured.');
        }

        $response = Services::curlrequest()->post('https://api.brevo.com/v3/smtp/email', [
            'json' => [
                'sender'      => ['name' => 'PrimaZentra', 'email' => 'primazentra@gmail.com'],
                'to'          => [['email' => $toEmail]],
                'subject'     => $subject,
                'htmlContent' => $html,
            ],
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
                'api-key'      => $this->apiKey,
            ],
            'timeout'         => 10,
            'connect_timeout' => 5,
            'http_errors'     => false,
        ]);

        $status = $response->getStatusCode();

        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException('Brevo send failed (' . $status . '): ' . $response->getBody());
        }
    }
}
