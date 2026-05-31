<?php

namespace App\Controllers;

use Config\GoogleMaps;

/**
 * Server-side proxy for Google Maps APIs.
 *
 * Keeps the server-side API key out of the browser by routing
 * Geocoding and Distance Matrix calls through CI4.
 *
 * Routes:
 *   GET  maps/geocode?address=...          → lat/lng + formatted address
 *   GET  maps/reverse?lat=...&lng=...      → formatted address
 *   GET  maps/distance?origins=...&destinations=...   → distance + duration matrix
 */
class Maps extends BaseController
{
    private string $apiKey;
    private const GEOCODE_URL        = 'https://maps.googleapis.com/maps/api/geocode/json';
    private const DISTANCE_URL       = 'https://maps.googleapis.com/maps/api/distancematrix/json';

    public function __construct()
    {
        $this->apiKey = (new GoogleMaps())->serverKey;
    }

    // ── Geocoding: address → lat/lng ─────────────────────────────────

    public function geocode(): \CodeIgniter\HTTP\ResponseInterface
    {
        $address = trim((string)($this->request->getGet('address') ?? ''));

        if ($address === '') {
            return $this->jsonError('Address is required', 400);
        }

        $data = $this->callGoogle(self::GEOCODE_URL, ['address' => $address]);

        if (!$data || $data['status'] !== 'OK' || empty($data['results'])) {
            return $this->jsonError('Geocoding failed: ' . ($data['status'] ?? 'NO_RESPONSE'), 422);
        }

        $result   = $data['results'][0];
        $location = $result['geometry']['location'];

        return $this->response->setJSON([
            'status'            => 'OK',
            'formatted_address' => $result['formatted_address'],
            'lat'               => $location['lat'],
            'lng'               => $location['lng'],
            'place_id'          => $result['place_id'] ?? null,
        ]);
    }

    // ── Reverse geocoding: lat/lng → address ─────────────────────────

    public function reverse(): \CodeIgniter\HTTP\ResponseInterface
    {
        $lat = $this->request->getGet('lat');
        $lng = $this->request->getGet('lng');

        if ($lat === null || $lng === null) {
            return $this->jsonError('lat and lng are required', 400);
        }

        $data = $this->callGoogle(self::GEOCODE_URL, [
            'latlng' => "{$lat},{$lng}",
        ]);

        if (!$data || $data['status'] !== 'OK' || empty($data['results'])) {
            return $this->jsonError('Reverse geocoding failed: ' . ($data['status'] ?? 'NO_RESPONSE'), 422);
        }

        $result   = $data['results'][0];
        $location = $result['geometry']['location'];

        return $this->response->setJSON([
            'status'            => 'OK',
            'formatted_address' => $result['formatted_address'],
            'lat'               => $location['lat'],
            'lng'               => $location['lng'],
            'place_id'          => $result['place_id'] ?? null,
        ]);
    }

    // ── Distance Matrix ───────────────────────────────────────────────

    /**
     * GET maps/distance
     *   ?origins=lat,lng|lat,lng          (pipe-separated, or single pair)
     *   &destinations=lat,lng|lat,lng
     *   &mode=driving                     (optional; driving|walking|transit)
     *
     * Returns the full Distance Matrix API response so the caller can
     * inspect the rows/elements matrix for ML / MCDA use.
     */
    public function distance(): \CodeIgniter\HTTP\ResponseInterface
    {
        $origins      = trim((string)($this->request->getGet('origins')      ?? ''));
        $destinations = trim((string)($this->request->getGet('destinations') ?? ''));

        if ($origins === '' || $destinations === '') {
            return $this->jsonError('origins and destinations are required', 400);
        }

        $mode = $this->request->getGet('mode') ?? 'driving';
        $allowed = ['driving', 'walking', 'transit', 'bicycling'];
        if (!in_array($mode, $allowed, true)) {
            $mode = 'driving';
        }

        $data = $this->callGoogle(self::DISTANCE_URL, [
            'origins'      => $origins,
            'destinations' => $destinations,
            'units'        => 'metric',
            'mode'         => $mode,
        ]);

        if (!$data) {
            return $this->jsonError('Distance Matrix request failed', 502);
        }

        // Flatten into a convenient summary alongside the raw response
        $summary = [];
        foreach ($data['rows'] ?? [] as $rIdx => $row) {
            foreach ($row['elements'] ?? [] as $cIdx => $element) {
                if ($element['status'] === 'OK') {
                    $summary[] = [
                        'origin_index'      => $rIdx,
                        'destination_index' => $cIdx,
                        'distance_text'     => $element['distance']['text'],
                        'distance_meters'   => $element['distance']['value'],
                        'duration_text'     => $element['duration']['text'],
                        'duration_seconds'  => $element['duration']['value'],
                    ];
                }
            }
        }

        return $this->response->setJSON([
            'status'           => $data['status'] ?? 'UNKNOWN',
            'origin_addresses' => $data['origin_addresses']      ?? [],
            'dest_addresses'   => $data['destination_addresses'] ?? [],
            'summary'          => $summary,
            'raw'              => $data,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────

    private function callGoogle(string $url, array $params): ?array
    {
        try {
            $client   = \Config\Services::curlrequest();
            $response = $client->get($url, [
                'query'   => array_merge($params, ['key' => $this->apiKey]),
                'timeout' => 10,
            ]);
            return json_decode($response->getBody(), true);
        } catch (\Throwable $e) {
            log_message('error', 'Maps::callGoogle - ' . $e->getMessage());
            return null;
        }
    }

    private function jsonError(string $message, int $status = 400): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->response
            ->setStatusCode($status)
            ->setJSON(['status' => 'ERROR', 'message' => $message]);
    }
}
