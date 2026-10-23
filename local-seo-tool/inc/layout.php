<?php
function head_html(string $title, bool $map = false): void { ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> · Local SEO Grid Tool</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tinos:wght@400;700&display=swap">
<?php if ($map): ?><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"><?php endif; ?>
<link rel="stylesheet" href="assets/app.css">
</head>
<body>
<nav class="top"><div class="wrap">
  <a class="logo" href="<?= e(cfg('main_site')) ?>">Zahir <span>Islam</span></a>
  <div class="links">
    <?php if (current_user()): ?>
      <a href="index.php">Dashboard</a><a href="logout.php">Log out</a>
    <?php else: ?>
      <a href="login.php">Log in</a><a class="btn btn-gold" href="register.php">Sign up free</a>
    <?php endif; ?>
  </div>
</div></nav>
<?php if (cfg('dfs_mock')): ?><div class="demo">Demo mode: rankings below are sample data, not real Google results.</div><?php endif; ?>
<main class="wrap">
<?php }
function foot_html(): void { ?>
</main>
<footer class="wrap foot">© <?= date('Y') ?> Zahir Islam · <a href="<?= e(cfg('main_site')) ?>">Back to website</a> · <a href="https://wa.me/<?= e(cfg('whatsapp')) ?>" target="_blank" rel="noopener">WhatsApp</a></footer>
</body>
</html>
<?php }
