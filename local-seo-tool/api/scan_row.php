<?php
// Processes the next pending row of a scan (one call per row keeps each request short), then returns all points.
require __DIR__ . '/../inc/bootstrap.php'; require __DIR__ . '/../inc/dataforseo.php'; require __DIR__ . '/../inc/grid.php';
$u = api_user();
set_time_limit(180);
$sid = (int)($_POST['scan_id'] ?? 0);
$s = db()->prepare('SELECT s.*, b.title, b.place_id, b.cid, b.lat AS blat, b.lng AS blng FROM scans s JOIN businesses b ON b.id = s.business_id WHERE s.id = ? AND s.user_id = ?');
$s->execute([$sid, $u['id']]); $scan = $s->fetch();
if (!$scan) json_out(['error' => 'Scan not found.'], 404);
$error = null;
if ($scan['status'] === 'running') {
    $r = db()->prepare("SELECT MIN(row_i) FROM scan_points WHERE scan_id = ? AND status = 'pending'"); $r->execute([$sid]);
    $row = $r->fetchColumn();
    if ($row !== null && $row !== false) {
        $claim = db()->prepare("UPDATE scan_points SET status = 'working' WHERE scan_id = ? AND row_i = ? AND status = 'pending'");
        $claim->execute([$sid, $row]);
        if ($claim->rowCount() > 0) {
            $p = db()->prepare("SELECT * FROM scan_points WHERE scan_id = ? AND row_i = ? AND status = 'working' ORDER BY col_i"); $p->execute([$sid, $row]);
            $pts = $p->fetchAll();
            $tasks = array_map(fn($pt) => ['keyword' => $scan['keyword'], 'lat' => (float)$pt['lat'], 'lng' => (float)$pt['lng'],
                'blat' => (float)$scan['blat'], 'blng' => (float)$scan['blng'], 'title' => $scan['title'], 'place_id' => $scan['place_id'], 'cid' => $scan['cid']], $pts);
            try {
                $results = dfs_points($tasks);
                $up = db()->prepare("UPDATE scan_points SET `rank` = ?, top3 = ?, status = 'done' WHERE id = ?");
                foreach ($pts as $k => $pt) {
                    $items = $results[$k] ?? [];
                    $top3 = array_map(fn($i) => $i['title'], array_slice($items, 0, 3));
                    $up->execute([find_rank($items, $scan), json_encode($top3), $pt['id']]);
                }
            } catch (Throwable $e) {
                error_log($e->getMessage());
                db()->prepare("UPDATE scan_points SET status = 'pending' WHERE scan_id = ? AND row_i = ? AND status = 'working'")->execute([$sid, $row]);
                $error = 'Google Maps check failed for one row. It will be retried.';
            }
        }
    } else {
        // A row left 'working' by a request that died (e.g. server timeout) is retried; otherwise the scan is finished.
        $w = db()->prepare("UPDATE scan_points SET status = 'pending' WHERE scan_id = ? AND status = 'working'"); $w->execute([$sid]);
        if ($w->rowCount() === 0) { db()->prepare("UPDATE scans SET status = 'done' WHERE id = ?")->execute([$sid]); $scan['status'] = 'done'; }
    }
}
$p = db()->prepare('SELECT row_i, col_i, lat, lng, `rank`, top3, status FROM scan_points WHERE scan_id = ? ORDER BY row_i, col_i'); $p->execute([$sid]);
$points = $p->fetchAll();
foreach ($points as &$pt) { $pt['rank'] = $pt['rank'] === null ? null : (int)$pt['rank']; $pt['top3'] = $pt['top3'] ? json_decode($pt['top3'], true) : []; }
unset($pt);
$stats = scan_stats($points);
if ($stats['done'] === $stats['total'] && $scan['status'] !== 'done') { db()->prepare("UPDATE scans SET status = 'done' WHERE id = ?")->execute([$sid]); $scan['status'] = 'done'; }
json_out(['status' => $scan['status'], 'points' => $points, 'stats' => $stats, 'depth' => (int)cfg('search_depth'), 'error' => $error]);
