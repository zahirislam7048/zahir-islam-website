<?php
require __DIR__ . '/../inc/bootstrap.php'; require __DIR__ . '/../inc/dataforseo.php';
$u = api_user();
$q = trim((string)($_POST['q'] ?? '')); $country = (int)($_POST['country'] ?? 0);
if ($q === '' || mb_strlen($q) > 150 || !isset(cfg('countries')[$country])) json_out(['error' => 'Enter a business name and choose a country.'], 400);
$_SESSION['searches'] = ($_SESSION['searches'] ?? 0) + 1;
if ($_SESSION['searches'] > 30) json_out(['error' => 'Too many searches. Please log out and back in later.'], 429);
try { $places = dfs_search_business($q, $country); }
catch (Throwable $e) { error_log($e->getMessage()); json_out(['error' => 'Google Maps search failed. Please try again later.'], 502); }
$places = array_values(array_filter($places, fn($p) => $p['lat'] !== null && $p['lng'] !== null));
$_SESSION['found'] = $places;
json_out(['results' => array_map(fn($p, $i) => ['i' => $i, 'title' => $p['title'], 'address' => $p['address'], 'rating' => $p['rating'], 'reviews' => $p['reviews'], 'category' => $p['category']], $places, array_keys($places))]);
