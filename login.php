<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/icons.php';
$error = '';
if (isset($_GET['error']) && $_GET['error'] === 'forbidden') {
  $error = 'Akun tersebut bukan aktor Admin Sistem.';
}
if (is_logged_in() && ($_SESSION['user']['role_key'] ?? '') === 'admin_website') {
  redirect('sysadmin/dashboard.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  if (attempt_login($email, $password)) {
    redirect('sysadmin/dashboard.php');
  }
  $error = 'Email atau password salah. Gunakan sysadmin@test.com / password.';
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Admin Sistem - NextIntern</title>
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>

<body>
  <main class="login-page">
    <section class="login-card">
      <div class="login-logo"><?= icon_svg('award', 'icon') ?></div>
      <div class="login-title">
        <h1>Selamat Datang di NextIntern</h1>
        <p>Sistem Informasi Magang Mahasiswa Terpadu</p>
      </div>
      <?php if ($error): ?>
        <div class="alert error"><?= e($error) ?></div><?php endif; ?>
      <form method="post" autocomplete="off">
        <div class="field">
          <label for="email">Email</label>
          <input class="input" id="email" name="email" type="email" placeholder="sysadmin@test.com"
            value="<?= e($_POST['email'] ?? 'sysadmin@test.com') ?>" required>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input class="input" id="password" name="password" type="password" placeholder="••••••••" required>
        </div>
        <button class="btn" type="submit">Masuk</button>
      </form>
      <div class="demo-box">
        <strong>Demo Admin Sistem</strong><br>
        Email: sysadmin@test.com<br>
        Password: password
      </div>
    </section>
  </main>
</body>

</html>