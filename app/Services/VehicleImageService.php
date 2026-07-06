<?php

namespace App\Services;

use App\Models\VehicleModel;
use Config\Services;

/**
 * Fetches a clean "ideal condition" reference image of a vehicle model from
 * Wikipedia/Wikimedia (free, no API key). The result is cached on the vehicle
 * row (stock_image_url) so we only hit the network once per vehicle.
 *
 * These are stock photos of the model — NOT the inspected car. They illustrate
 * how the vehicle should look in perfect condition alongside the real captured
 * inspection photos.
 */
class VehicleImageService
{
    /** Admin toggle on the Settings page. */
    public static function enabled(): bool
    {
        return (bool) setting('Ai.showVehicleImage');
    }

    /**
     * Reference image URL for a vehicle row (cached-or-fetched), or null.
     * Persists the lookup on the vehicle so later views/PDF reuse it.
     *
     * @param array<string, mixed> $vehicle
     */
    public function imageFor(array $vehicle): ?string
    {
        if (! self::enabled()) {
            return null;
        }
        if (! empty($vehicle['stock_image_url'])) {
            return $vehicle['stock_image_url'];
        }

        $make  = trim((string) ($vehicle['make'] ?? ''));
        $model = trim((string) ($vehicle['model'] ?? ''));
        $year  = trim((string) ($vehicle['year'] ?? ''));
        if ($make === '' && $model === '') {
            return null;
        }

        // Try most-specific query first, then fall back to make+model.
        $url = $this->lookup(trim("{$make} {$model} {$year}"))
            ?? $this->lookup(trim("{$make} {$model}"));

        if ($url !== null && ! empty($vehicle['id'])) {
            model(VehicleModel::class)->update($vehicle['id'], ['stock_image_url' => $url]);
        }

        return $url;
    }

    /** Query Wikipedia for the lead image of the best-matching article. */
    public function lookup(string $query): ?string
    {
        if ($query === '') {
            return null;
        }

        try {
            $res = Services::curlrequest([
                'timeout'     => 15,
                'http_errors' => false,
                'headers'     => ['User-Agent' => 'CheckaInspections/1.0 (vehicle reference images)'],
            ])->get('https://en.wikipedia.org/w/api.php', [
                'query' => [
                    'action'      => 'query',
                    'format'      => 'json',
                    'prop'        => 'pageimages',
                    'piprop'      => 'thumbnail',
                    'pithumbsize' => 1000,
                    'generator'   => 'search',
                    'gsrsearch'   => $query,
                    'gsrnamespace' => 0,
                    'gsrlimit'    => 1,
                ],
            ]);

            $data  = json_decode((string) $res->getBody(), true);
            $pages = $data['query']['pages'] ?? [];
            foreach ($pages as $p) {
                if (! empty($p['thumbnail']['source'])) {
                    return $p['thumbnail']['source'];
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'VehicleImageService lookup failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Fetch the image and return a base64 data URI for safe mPDF embedding
     * (avoids relying on allow_url_fopen on shared hosting). Null on failure.
     */
    public function dataUri(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        try {
            $res  = Services::curlrequest([
                'timeout'     => 15,
                'http_errors' => false,
                'headers'     => ['User-Agent' => 'CheckaInspections/1.0 (vehicle reference images)'],
            ])->get($url);
            $body = (string) $res->getBody();
            if ($body === '') {
                return null;
            }
            $mime = $res->getHeaderLine('Content-Type') ?: 'image/jpeg';
            $mime = explode(';', $mime)[0];

            return 'data:' . $mime . ';base64,' . base64_encode($body);
        } catch (\Throwable $e) {
            log_message('error', 'VehicleImageService dataUri failed: ' . $e->getMessage());
        }

        return null;
    }
}
