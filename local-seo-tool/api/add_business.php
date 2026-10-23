<?php
require __DIR__ . '/../inc/bootstrap.php';
$u = api_user();
$i = (int)($_POST['i'] ?? -1); $p = $_SESSION['found'][$i] ?? null;
if (!$p) json_out(['error' => 'Please search again.'], 400);
$c = db()->prepare('SELECT COUNT(*) FROM businesses WHERE user_id = ?'); $c->execute([$u['id']]);
if ((int)$c->fetchColumn() >= (int)cfg('max_businesses')) json_out(['error' => 'Business limit reached.'], 403);
db()->prepare('INSERT INTO businesses (user_id, title, address, place_id, cid, lat, lng, rating, reviews, category, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
  ->execute([$u['id'], $p['title'], $p['address'], $p['place_id'], (string)$p['cid'], $p['lat'], $p['lng'], $p['rating'], $p['reviews'], $p['category'], now()]);
json_out(['ok' => true]);
