<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class GoogleMaps extends BaseConfig
{
    /**
     * Server-side API key — used by CI4 backend for Geocoding and Distance Matrix.
     * Restrict this key to your server's IP in Google Cloud Console.
     */
    public string $serverKey = '';

    /**
     * Browser API key — injected into the Maps JavaScript API <script> tag.
     * Restrict this key to your domain via HTTP referrers in Google Cloud Console.
     */
    public string $browserKey = '';

    public function __construct()
    {
        parent::__construct();
        $this->serverKey  = env('GOOGLE_MAPS_KEY', '');
        $this->browserKey = env('GOOGLE_MAPS_BROWSER_KEY', '') ?: $this->serverKey;
    }
}
