<?php
require __DIR__ . '/../inc/bootstrap.php';
$u = api_user();
$id = (int)($_POST['id'] ?? 0);
$pdo = db(); $pdo->beginTransaction();
$s = $pdo->prepare('SELECT id FROM scans WHERE business_id = ? AND user_id = ?'); $s->execute([$id, $u['id']]);
foreach ($s->fetchAll(PDO::FETCH_COLUMN) as $sid) $pdo->prepare('DELETE FROM scan_points WHERE scan_id = ?')->execute([$sid]);
$pdo->prepare('DELETE FROM scans WHERE business_id = ? AND user_id = ?')->execute([$id, $u['id']]);
$pdo->prepare('DELETE FROM businesses WHERE id = ? AND user_id = ?')->execute([$id, $u['id']]);
$pdo->commit();
json_out(['ok' => true]);
