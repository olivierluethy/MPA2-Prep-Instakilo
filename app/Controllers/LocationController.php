<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;

/**
 * Location autocomplete + nearest-city lookup backed by a static dataset
 * (config/cities.php) — no external geocoding dependency.
 */
final class LocationController extends Controller
{
    public function search(Request $request): void
    {
        $q = mb_strtolower(trim((string) $request->query('q', '')));
        if ($q === '') {
            $this->ok(['locations' => []]);
        }

        $matches = [];
        foreach ($this->cities() as $c) {
            $pos = mb_strpos(mb_strtolower($c['name']), $q);
            if ($pos !== false) {
                $matches[] = ['label' => $c['name'] . ', ' . $c['country'], 'rank' => $pos];
            }
        }
        // Prefix matches first, then alphabetical.
        usort($matches, static fn ($a, $b) => [$a['rank'], $a['label']] <=> [$b['rank'], $b['label']]);

        $this->ok(['locations' => array_map(
            static fn ($m) => $m['label'],
            array_slice($matches, 0, 8)
        )]);
    }

    public function nearest(Request $request): void
    {
        $lat = (float) ($request->query('lat', '') ?? 0);
        $lng = (float) ($request->query('lng', '') ?? 0);
        if ($lat === 0.0 && $lng === 0.0) {
            $this->fail('Koordinaten fehlen.', 422);
        }

        $best = null;
        $bestDist = INF;
        foreach ($this->cities() as $c) {
            $d = $this->haversine($lat, $lng, $c['lat'], $c['lng']);
            if ($d < $bestDist) {
                $bestDist = $d;
                $best = $c;
            }
        }

        $this->ok(['location' => $best ? $best['name'] . ', ' . $best['country'] : null]);
    }

    /** @return array<int, array{name:string, country:string, lat:float, lng:float}> */
    private function cities(): array
    {
        return require BASE_PATH . '/config/cities.php';
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $r * 2 * asin(min(1, sqrt($a)));
    }
}
