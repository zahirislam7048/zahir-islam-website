<?php
require __DIR__ . '/inc/bootstrap.php'; require __DIR__ . '/inc/layout.php';
if (current_user()) { header('Location: index.php'); exit; }
$err = ''; $name = trim($_POST['name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pass = (string)($_POST['password'] ?? '');
    if ($name === '' || mb_strlen($name) > 120) $err = 'Please enter your name.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Please enter a valid email address.';
    elseif (strlen($pass) < 8) $err = 'Password must be at least 8 characters.';
    else {
        $s = db()->prepare('SELECT id FROM users WHERE email = ?'); $s->execute([$email]);
        if ($s->fetch()) $err = 'An account with this email already exists. Please log in.';
        else {
            db()->prepare('INSERT INTO users (name, email, password_hash, created_at) VALUES (?,?,?,?)')
                ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), now()]);
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)db()->lastInsertId();
            if (cfg('admin_email')) @mail(cfg('admin_email'), 'New Grid Tool signup: ' . $name, "Name: $name\nEmail: $email\nTime: " . now());
            header('Location: index.php'); exit;
        }
    }
}
head_html('Sign up'); ?>
<section class="auth">
  <h1>Track your Google Maps rankings, <em>free.</em></h1>
  <p class="lede">See where your business ranks on Google Maps across your whole area, point by point, for the keywords that matter.</p>
  <form method="post" class="card form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <?php if ($err): ?><p class="err"><?= e($err) ?></p><?php endif; ?>
    <label>Your name<input name="name" required value="<?= e($name) ?>" autocomplete="name"></label>
    <label>Email<input name="email" type="email" required value="<?= e($email) ?>" autocomplete="email"></label>
    <label>Password (8+ characters)<input name="password" type="password" required minlength="8" autocomplete="new-password"></label>
    <button class="btn btn-gold">Create free account</button>
    <p class="note">Already have an account? <a href="login.php">Log in</a></p>
  </form>
</section>
<?php foot_html();
