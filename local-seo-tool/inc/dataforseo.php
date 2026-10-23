<?php
// Google Maps rankings via DataForSEO (serp/google/maps/live/advanced).
declare(strict_types=1);

const DFS_URL = 'https://api.dataforseo.com/v3/serp/google/maps/live/advanced';

/** Search businesses by name in a country. Returns list of places. */
function dfs_search_business(string $query, int $locationCode): array {
    if (cfg('dfs_mock')) return mock_search($query);
    $res = dfs_multi([[
        'keyword' => $query, 'location_code' => $locationCode,
        'language_code' => cfg('language_code'), 'depth' => 20,
    ]]);
    return array_map('dfs_place', $res[0]);
}

/** Run many point checks in parallel. $tasks: [['keyword'=>..,'lat'=>..,'lng'=>..], ...] → list of item arrays */
function dfs_points(array $tasks): array {
    if (cfg('dfs_mock')) return array_map(fn($t) => mock_point($t), $tasks);
    $payloads = array_map(fn($t) => [
        'keyword' => $t['keyword'],
        'location_coordinate' => sprintf('%.7f,%.7f,%dz', $t['lat'], $t['lng'], (int)cfg('map_zoom')),
        'language_code' => cfg('language_code'),
        'depth' => (int)cfg('search_depth'),
    ], $tasks);
    return array_map(fn($items) => array_map('dfs_place', $items), dfs_multi($payloads));
}

function dfs_place(array $i): array {
    return [
        'rank' => $i['rank_group'] ?? null, 'title' => $i['title'] ?? '', 'address' => $i['address'] ?? '',
        'place_id' => $i['place_id'] ?? '', 'cid' => $i['cid'] ?? '',
        'lat' => $i['latitude'] ?? null, 'lng' => $i['longitude'] ?? null,
        'rating' => $i['rating']['value'] ?? null, 'reviews' => $i['rating']['votes_count'] ?? null,
        'category' => $i['category'] ?? '',
    ];
}

/** POST each payload as its own request, in parallel. Returns array of item lists (same order). */
function dfs_multi(array $payloads): array {
    $mh = curl_multi_init(); $hs = [];
    foreach ($payloads as $k => $p) {
        $h = curl_init(DFS_URL);
        curl_setopt_array($h, [
            CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 120,
            CURLOPT_USERPWD => cfg('dfs_login') . ':' . cfg('dfs_password'),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([$p]),
        ]);
        curl_multi_add_handle($mh, $h); $hs[$k] = $h;
    }
    do { $st = curl_multi_exec($mh, $running); if ($running) curl_multi_select($mh, 1.0); } while ($running && $st === CURLM_OK);
    $out = [];
    foreach ($hs as $k => $h) {
        $body = curl_multi_getcontent($h); $code = curl_getinfo($h, CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($mh, $h); curl_close($h);
        $j = json_decode((string)$body, true);
        if ($code !== 200 || !$j || ($j['status_code'] ?? 0) !== 20000) {
            throw new RuntimeException('DataForSEO error: ' . ($j['status_message'] ?? "HTTP $code"));
        }
        $task = $j['tasks'][0] ?? [];
        if (($task['status_code'] ?? 0) !== 20000) throw new RuntimeException('DataForSEO task error: ' . ($task['status_message'] ?? 'unknown'));
        $items = $task['result'][0]['items'] ?? [];
        $out[$k] = array_values(array_filter($items ?: [], fn($i) => ($i['type'] ?? '') === 'maps_search'));
    }
    curl_multi_close($mh);
    return $out;
}

// ---------- Demo mode (no API credits used) ----------
function mock_search(string $q): array {
    $base = ['lat' => 23.7465, 'lng' => 90.3760];
    $out = [];
    foreach ([['', 0, 0], [' Branch 2', .03, .02], [' Express', -.02, .04]] as $n => $v) {
        $out[] = ['rank' => $n + 1, 'title' => ucwords($q) . $v[0], 'address' => 'Demo address ' . ($n + 1) . ', Dhaka',
            'place_id' => 'demo_' . md5($q . $n), 'cid' => (string)(1000 + $n), 'lat' => $base['lat'] + $v[1], 'lng' => $base['lng'] + $v[2],
            'rating' => 4.5, 'reviews' => 120 - $n * 30, 'category' => 'Demo business'];
    }
    return $out;
}
function mock_point(array $t): array {
    // Rank gets worse with distance from the business, with some noise. Same input → same output.
    $d = sqrt((($t['lat'] - $t['blat']) * 69) ** 2 + (($t['lng'] - $t['blng']) * 64) ** 2);
    $noise = (crc32($t['keyword'] . round($t['lat'], 4) . round($t['lng'], 4)) % 5) - 2;
    $rank = (int)max(1, round(1 + $d * 0.9 + $noise));
    if ($rank > (int)cfg('search_depth')) return [];
    $items = [];
    for ($r = 1; $r <= min(20, (int)cfg('search_depth')); $r++) {
        $items[] = $r === $rank
            ? ['rank' => $r, 'title' => $t['title'], 'place_id' => $t['place_id'], 'cid' => $t['cid']]
            : ['rank' => $r, 'title' => 'Competitor ' . chr(64 + ($r % 26 ?: 26)), 'place_id' => 'c' . $r, 'cid' => 'c' . $r];
    }
    return $items;
}
