<?php
// Run once in the browser to create the database tables, then DELETE this file.
require __DIR__ . '/inc/bootstrap.php';
$mysql = db()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
$id = $mysql ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
$tail = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';
$sql = [
 "CREATE TABLE IF NOT EXISTS users (id $id, name VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL)$tail",
 "CREATE TABLE IF NOT EXISTS businesses (id $id, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, address VARCHAR(255), place_id VARCHAR(255), cid VARCHAR(64), lat DOUBLE NOT NULL, lng DOUBLE NOT NULL, rating DOUBLE, reviews INT, category VARCHAR(190), created_at DATETIME NOT NULL)$tail",
 "CREATE TABLE IF NOT EXISTS scans (id $id, user_id INT NOT NULL, business_id INT NOT NULL, keyword VARCHAR(190) NOT NULL, grid_size INT NOT NULL, spacing_mi DOUBLE NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL)$tail",
 "CREATE TABLE IF NOT EXISTS scan_points (id $id, scan_id INT NOT NULL, row_i INT NOT NULL, col_i INT NOT NULL, lat DOUBLE NOT NULL, lng DOUBLE NOT NULL, `rank` INT NULL, top3 TEXT NULL, status VARCHAR(20) NOT NULL)$tail",
];
foreach ($sql as $q) db()->exec($q);
foreach (['CREATE INDEX idx_biz_user ON businesses(user_id)', 'CREATE INDEX idx_scan_user ON scans(user_id, created_at)', 'CREATE INDEX idx_pt_scan ON scan_points(scan_id, row_i)'] as $q) {
    try { db()->exec($q); } catch (Throwable $e) { /* already exists */ }
}
echo 'Tables created. Now delete install.php from the server.';
