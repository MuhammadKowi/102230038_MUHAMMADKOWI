<?php
require __DIR__ . '/config.php';
if (!empty($_SESSION['user'])) redirect(($_SESSION['user']['role'] ?? '') === 'admin' ? 'admin.php' : 'student.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $stmt = $pdo->prepare('SELECT id, username, password_hash, role, full_name FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([trim($_POST['username'] ?? '')]);
    $row = $stmt->fetch();
    if ($row && password_verify($_POST['password'] ?? '', $row['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$row['id'], 'username' => $row['username'], 'role' => $row['role'], 'full_name' => $row['full_name']];
        redirect($row['role'] === 'admin' ? 'admin.php' : 'student.php');
    }
    $error = 'Username atau password tidak sesuai.';
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Masuk · Mambaul Huda</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/style.css">
</head>

<body>
    <div class="login-page">
        <section class="login-visual">
            <div class="brand-login"><span class="brand-mark">MH</span> MAMBAUL HUDA</div>
            <h1>Administrasi keuangan, lebih tertata.</h1>
            <p>Kelola pembayaran SPP santri dengan proses yang jelas, riwayat yang mudah ditelusuri, dan laporan yang terintegrasi.</p>
            <div style="margin-top:35px;font-size:11px;color:#c5efda">PORTAL PEMBAYARAN SANTRI · PAJOMBLANGAN KEDUNGWUNI</div>
        </section>
        <section class="login-form-side">
            <div class="login-box">
                <div class="eyebrow">SELAMAT DATANG</div>
                <h2>Masuk ke akun</h2>
                <p>Gunakan akun admin atau akun santri Anda.</p>
                <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
                <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <div class="form-group"><label>Username</label><input class="input" name="username" required autocomplete="username" placeholder="Masukkan username"></div>
                    <div class="form-group"><label>Password</label><input class="input" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan password"></div><button class="btn" type="submit">Masuk ke dashboard →</button>
                </form>
                <div class="login-note">Akun demo: <strong>admin</strong> / <strong>password</strong> atau <strong>santri001</strong> / <strong>password</strong>. Ganti password sebelum penggunaan nyata.</div>
            </div>
        </section>
    </div>
</body>

</html>