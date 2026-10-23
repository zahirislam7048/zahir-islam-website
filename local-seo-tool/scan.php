<?php
require __DIR__ . '/inc/bootstrap.php'; require __DIR__ . '/inc/layout.php';
$u = require_login();
$s = db()->prepare('SELECT s.*, b.title, b.address, b.lat AS blat, b.lng AS blng FROM scans s JOIN businesses b ON b.id = s.business_id WHERE s.id = ? AND s.user_id = ?');
$s->execute([(int)($_GET['id'] ?? 0), $u['id']]); $scan = $s->fetch();
if (!$scan) { http_response_code(404); exit('Scan not found.'); }
head_html('Scan: ' . $scan['keyword'], true); ?>
<script>window.CSRF = <?= json_encode(csrf_token()) ?>; window.SCAN = <?= json_encode(['id' => (int)$scan['id'], 'lat' => (float)$scan['blat'], 'lng' => (float)$scan['blng'], 'title' => $scan['title'], 'size' => (int)$scan['grid_size']]) ?>;</script>
<section class="dash-head">
  <div><span class="eyebrow"><a href="index.php">&larr; Dashboard</a></span>
  <h1>"<?= e($scan['keyword']) ?>"</h1>
  <p class="note"><?= e($scan['title']) ?> · <?= e($scan['address']) ?> · <?= (int)$scan['grid_size'] ?>×<?= (int)$scan['grid_size'] ?> grid, <?= e($scan['spacing_mi']) ?> mi apart · <?= e(substr($scan['created_at'], 0, 16)) ?></p></div>
</section>
<div class="kpis">
  <div><span>Average rank (where found)</span><b id="k-arp">–</b></div>
  <div><span>Average rank (all points)</span><b id="k-atrp">–</b></div>
  <div><span>Points in top 3</span><b id="k-solv">–</b></div>
  <div><span>Progress</span><b id="k-prog">0%</b></div>
</div>
<p class="note" id="msg">Starting scan…</p>
<div class="map-wrap"><div id="map"></div></div>
<div class="legend"><span class="c1">1–3</span><span class="c2">4–10</span><span class="c3">11–20</span><span class="c4">Not found</span><span class="biz">★ Your business</span></div>
<section class="card"><h2>Grid view</h2><div class="table-wrap"><div id="grid" class="grid"></div></div></section>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="assets/scan.js"></script>
<?php foot_html();
