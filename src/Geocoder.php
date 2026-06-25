<?php
declare(strict_types=1);

/**
 * Geocodes a text address to lat/lon using Nominatim (OpenStreetMap).
 * Results are persisted in a JSON cache file to avoid redundant API calls.
 *
 * @return array{lat: float|null, lon: float|null}
 */
function geocode(string $address, string $cachePath): array
{
    // iCal uses \n to separate venue name from street address, and \, for literal commas.
    // Split on \n first so we can build fallback candidates (without the venue name line).
    $lines = preg_split('/\\\\[nN]/', $address);
    $lines = array_values(array_filter(
        array_map(fn($l) => trim(str_replace(['\\,', '\\\\'], [',', '\\'], $l)), $lines)
    ));

    if (!$lines) {
        return ['lat' => null, 'lon' => null];
    }

    // Candidates: full address, then address without the first line (venue name)
    $candidates = [implode(', ', $lines)];
    if (count($lines) > 1) {
        $candidates[] = implode(', ', array_slice($lines, 1));
    }

    // Load cache (keyed by original raw address)
    $cache = [];
    if (file_exists($cachePath)) {
        $cache = json_decode(file_get_contents($cachePath), true) ?: [];
    }

    if (isset($cache[$address])) {
        return $cache[$address];
    }

    $opts   = ['http' => ['header' => "User-Agent: SPS-pointage\r\n", 'timeout' => 5]];
    $result = ['lat' => null, 'lon' => null];

    foreach ($candidates as $query) {
        $url  = 'https://nominatim.openstreetmap.org/search?'
              . http_build_query(['q' => $query, 'format' => 'json', 'limit' => 1]);
        $json = @file_get_contents($url, false, stream_context_create($opts));
        if ($json !== false) {
            $data = json_decode($json, true);
            if (!empty($data[0])) {
                $result = ['lat' => (float) $data[0]['lat'], 'lon' => (float) $data[0]['lon']];
                break;
            }
        }
    }

    $cache[$address] = $result;
    @file_put_contents($cachePath, json_encode($cache, JSON_PRETTY_PRINT));

    return $result;
}
