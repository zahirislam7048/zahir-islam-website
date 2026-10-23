<?php
require __DIR__ . '/../inc/bootstrap.php'; require __DIR__ . '/../inc/grid.php';
$u = api_user();
$bid = (int)($_POST['business_id'] ?? 0); $kw = trim((string)($_POST['keyword'] ?? ''));
$size = (int)($_POST['grid_size'] ?? 0); $sp = (float)($_POST['spacing'] ?? 0);
if ($kw === '' || mb_strlen($kw) > 120) json_out(['error' => 'Enter a keyword.'], 400);
if (!in_array($size, cfg('grid_sizes'), true) || !in_array($sp, array_map('floatval', cfg('spacings_miles')), true)) json_out(['error' => 'Invalid grid settings.'], 400);
$b = db()->prepare('SELECT * FROM businesses WHERE id = ? AND user_id = ?'); $b->execute([$bid, $u['id']]);
$biz = $b->fetch(); if (!$biz) json_out(['error' => 'Business not found.'], 404);
if (scans_this_month((int)$u['id']) >= (int)cfg('free_scans_per_month')) json_out(['error' => 'You have used all free scans this month.'], 403);
$pdo = db(); $pdo->beginTransaction();
$pdo->prepare('INSERT INTO scans (user_id, business_id, keyword, grid_size, spacing_mi, status, created_at) VALUES (?,?,?,?,?,?,?)')
    ->execute([$u['id'], $bid, $kw, $size, $sp, 'running', now()]);
$sid = (int)$pdo->lastInsertId();
$ins = $pdo->prepare('INSERT INTO scan_points (scan_id, row_i, col_i, lat, lng, status) VALUES (?,?,?,?,?,?)');
foreach (grid_points((float)$biz['lat'], (float)$biz['lng'], $size, $sp) as $p) $ins->execute([$sid, $p['row'], $p['col'], $p['lat'], $p['lng'], 'pending']);
$pdo->commit();
json_out(['scan_id' => $sid]);
