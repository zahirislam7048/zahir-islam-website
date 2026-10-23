<?php
require __DIR__ . '/inc/bootstrap.php'; require __DIR__ . '/inc/layout.php';
$u = require_login();
$b = db()->prepare('SELECT * FROM businesses WHERE user_id = ? ORDER BY id'); $b->execute([$u['id']]); $biz = $b->fetchAll();
$s = db()->prepare('SELECT s.*, b.title FROM scans s JOIN businesses b ON b.id = s.business_id WHERE s.user_id = ? ORDER BY s.id DESC LIMIT 50');
$s->execute([$u['id']]); $scans = $s->fetchAll();
$used = scans_this_month((int)$u['id']); $limit = (int)cfg('free_scans_per_month');
head_html('Dashboard'); ?>
<script>window.CSRF = <?= json_encode(csrf_token()) ?>;</script>
<section class="dash-head">
  <div><span class="eyebrow">Local SEO Grid Tool</span><h1>Hi <?= e($u['name']) ?></h1></div>
  <div class="quota"><b><?= $used ?> / <?= $limit ?></b><span>free scans used this month</span></div>
</section>

<section class="card">
  <h2>1 · Your businesses</h2>
  <?php if ($biz): ?>
  <ul class="biz-list">
    <?php foreach ($biz as $x): ?>
      <li><div><b><?= e($x['title']) ?></b><span><?= e($x['address']) ?><?= $x['rating'] ? ' · ' . e($x['rating']) . '★ (' . e($x['reviews']) . ')' : '' ?></span></div>
      <button class="btn btn-ghost btn-sm" data-del="<?= (int)$x['id'] ?>">Remove</button></li>
    <?php endforeach; ?>
  </ul>
  <?php else: ?><p class="note">No business connected yet. Search for your business on Google Maps below.</p><?php endif; ?>
  <?php if (count($biz) < (int)cfg('max_businesses')): ?>
  <form id="search-form" class="form row3">
    <label>Business name and city<input id="q" required placeholder="e.g. Example Cafe Dhanmondi Dhaka"></label>
    <label>Country<select id="country"><?php foreach (cfg('countries') as $code => $name): ?><option value="<?= (int)$code ?>"><?= e($name) ?></option><?php endforeach; ?></select></label>
    <button class="btn btn-gold">Find on Google Maps</button>
  </form>
  <div id="results"></div>
  <?php else: ?><p class="note">Free accounts can connect up to <?= (int)cfg('max_businesses') ?> businesses.</p><?php endif; ?>
</section>

<?php if ($biz): ?>
<section class="card">
  <h2>2 · Run a grid scan</h2>
  <form id="scan-form" class="form row5">
    <label>Business<select id="biz"><?php foreach ($biz as $x): ?><option value="<?= (int)$x['id'] ?>"><?= e($x['title']) ?></option><?php endforeach; ?></select></label>
    <label>Keyword<input id="kw" required placeholder="e.g. restaurant near me"></label>
    <label>Grid<select id="size"><?php foreach (cfg('grid_sizes') as $g): ?><option value="<?= (int)$g ?>" <?= $g == 9 ? 'selected' : '' ?>><?= (int)$g ?> × <?= (int)$g ?></option><?php endforeach; ?></select></label>
    <label>Distance between points<select id="spacing"><?php foreach (cfg('spacings_miles') as $m): ?><option value="<?= e($m) ?>" <?= $m == 2 ? 'selected' : '' ?>><?= e($m) ?> mile<?= $m == 1 ? '' : 's' ?></option><?php endforeach; ?></select></label>
    <button class="btn btn-gold" <?= $used >= $limit ? 'disabled' : '' ?>>Start scan</button>
  </form>
  <p class="note" id="scan-note"><?= $used >= $limit ? 'You have used all free scans this month. <a href="https://wa.me/' . e(cfg('whatsapp')) . '" target="_blank" rel="noopener">Message me on WhatsApp</a> for more.' : 'A 9 × 9 grid checks your ranking at 81 points around your business.' ?></p>
</section>
<?php endif; ?>

<section class="card">
  <h2>3 · Scan history</h2>
  <?php if ($scans): ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Date</th><th>Business</th><th>Keyword</th><th>Grid</th><th>Status</th><th></th></tr></thead>
    <tbody><?php foreach ($scans as $x): ?>
      <tr><td><?= e(substr($x['created_at'], 0, 16)) ?></td><td><?= e($x['title']) ?></td><td><?= e($x['keyword']) ?></td>
      <td><?= (int)$x['grid_size'] ?>×<?= (int)$x['grid_size'] ?> · <?= e($x['spacing_mi']) ?> mi</td><td><?= e($x['status']) ?></td>
      <td><a href="scan.php?id=<?= (int)$x['id'] ?>">View &rarr;</a></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php else: ?><p class="note">Your scans will appear here.</p><?php endif; ?>
</section>
<script src="assets/app.js"></script>
<?php foot_html();
