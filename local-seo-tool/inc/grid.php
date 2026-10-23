<?php
declare(strict_types=1);
/** Grid points centred on (lat,lng): size×size, spacing in miles. Row 0 = north. */
function grid_points(float $lat, float $lng, int $size, float $spacingMi): array {
    $half = intdiv($size - 1, 2); $pts = [];
    $dLat = $spacingMi / 69.0;
    $dLng = $spacingMi / (69.172 * cos(deg2rad($lat)));
    for ($r = 0; $r < $size; $r++) for ($c = 0; $c < $size; $c++) {
        $pts[] = ['row' => $r, 'col' => $c, 'lat' => $lat + ($half - $r) * $dLat, 'lng' => $lng + ($c - $half) * $dLng];
    }
    return $pts;
}
/** Find the business in a result list. Returns rank or null (not in top N). */
function find_rank(array $items, array $biz): ?int {
    foreach ($items as $i) {
        if (($biz['place_id'] && $i['place_id'] === $biz['place_id']) || ($biz['cid'] && (string)$i['cid'] === (string)$biz['cid'])) return (int)$i['rank'];
    }
    return null;
}
function scan_stats(array $points): array {
    $done = array_filter($points, fn($p) => $p['status'] === 'done');
    $ranks = array_map(fn($p) => $p['rank'] === null ? null : (int)$p['rank'], $done);
    $found = array_filter($ranks, fn($r) => $r !== null);
    $depth = (int)cfg('search_depth');
    $n = count($done);
    return [
        'done' => $n, 'total' => count($points),
        'arp' => $found ? round(array_sum($found) / count($found), 1) : null,                       // avg rank where found
        'atrp' => $n ? round(array_sum(array_map(fn($r) => $r ?? $depth + 1, $ranks)) / $n, 1) : null, // not found counted as depth+1
        'solv' => $n ? round(count(array_filter($found, fn($r) => $r <= 3)) / $n * 100) : null,        // % of points in top 3
    ];
}
