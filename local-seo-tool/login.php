<?php
require __DIR__ . '/inc/bootstrap.php'; require __DIR__ . '/inc/layout.php';
if (current_user()) { header('Location: index.php'); exit; }
$err = ''; $email = strtolower(trim($_POST['email'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $s = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?'); $s->execute([$email]);
    $u = $s->fetch();
    if ($u && password_verify((string)($_POST['password'] ?? ''), $u['password_hash'])) {
        session_regenerate_id(true); $_SESSION['uid'] = (int)$u['id'];
        header('Location: index.php'); exit;
    }
    usleep(400000); $err = 'Wrong email or password.';
}
head_html('Log in'); ?>
<section class="auth">
  <h1>Log in</h1>
  <form method="post" class="card form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <?php if ($err): ?><p class="err"><?= e($err) ?></p><?php endif; ?>
    <label>Email<input name="email" type="email" required value="<?= e($email) ?>" autocomplete="email"></label>
    <label>Password<input name="password" type="password" required autocomplete="current-password"></label>
    <button class="btn btn-gold">Log in</button>
    <p class="note">No account yet? <a href="register.php">Sign up free</a></p>
  </form>
</section>
<?php foot_html();
