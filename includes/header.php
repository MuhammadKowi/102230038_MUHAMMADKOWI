<?php
$pageTitle = $pageTitle ?? 'Dashboard';
$user = $_SESSION['user'] ?? null;
$schoolName = app_school_name();
$schoolLogoUrl = app_school_logo_url();
// Gunakan path lokal aplikasi untuk favicon agar tetap berfungsi pada hosting,
// termasuk jika domain berbeda dari APP_BASE atau aplikasi berada di subfolder.
$appPublicPath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$schoolLogoFile = $schoolLogoUrl ? basename(parse_url($schoolLogoUrl, PHP_URL_PATH)) : '';
$localFaviconUrl = $schoolLogoFile !== '' ? $appPublicPath . '/uploads/' . rawurlencode($schoolLogoFile) : $appPublicPath . '/favicon.svg';
$localFaviconVersion = $schoolLogoFile !== '' ? (int) @filemtime(__DIR__ . '/../uploads/' . $schoolLogoFile) : (int) @filemtime(__DIR__ . '/../favicon.svg');
$schoolMark = app_school_mark();
$flashData = $_SESSION['flash'] ?? null;
$pendingNotifications = 0;
if (($user['role'] ?? '') === 'admin' && isset($pdo)) { try { $pendingNotifications = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status='pending'")->fetchColumn(); } catch (Throwable $e) { $pendingNotifications = 0; } }
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e($schoolName) ?></title>
<link rel="icon" href="<?= e($localFaviconUrl) ?>?v=<?= $localFaviconVersion ?>">
<link rel="apple-touch-icon" href="<?= e($localFaviconUrl) ?>?v=<?= $localFaviconVersion ?>" type="image/png">
<link rel="stylesheet" href="<?= APP_BASE ?>/assets/style.css">
</head>
<body>
<div class="school-loading-screen is-hidden" id="schoolLoadingScreen" role="status" aria-live="polite" aria-label="Memuat aplikasi">
  <div class="school-loading-content">
    <div class="school-loader-orbit" aria-hidden="true">
      <span class="school-loader-dot school-loader-dot-one"></span>
      <span class="school-loader-dot school-loader-dot-two"></span>
      <span class="school-loader-dot school-loader-dot-three"></span>
      <div class="school-loader-logo"><?php if ($schoolLogoUrl): ?><img src="<?= e($schoolLogoUrl) ?>" alt="Logo <?= e($schoolName) ?>"><?php else: ?><span><?= e($schoolMark) ?></span><?php endif; ?></div>
    </div>
    <div class="school-loader-name"><?= e($schoolName) ?></div>
    <div class="school-loader-caption">Portal Keuangan Santri</div>
    <div class="school-loader-progress" aria-hidden="true"><span></span></div>
    <div class="school-loader-status">Sedang memuat, mohon tunggu...</div>
  </div>
</div>
<script>
// Tampilkan loader sedini mungkin saat refresh, sebelum halaman selesai dirender.
(function () {
  try {
    var nav = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
    var isReload = (nav && nav.type === 'reload') ||
      (performance.navigation && performance.navigation.type === 1);
    if (isReload) {
      var loader = document.getElementById('schoolLoadingScreen');
      if (loader) loader.classList.remove('is-hidden');
    }
  } catch (e) {}
})();
</script>
<div class="app-shell">
<div class="sidebar-overlay" data-sidebar-overlay aria-hidden="true"></div>
<aside class="sidebar" id="appSidebar" aria-label="Menu navigasi">
  <button class="sidebar-close" type="button" data-sidebar-close aria-label="Tutup menu"><span></span><span></span></button>
  <a class="brand" href="<?= APP_BASE ?>/<?= ($user['role'] ?? '') === 'admin' ? 'admin.php' : 'student.php' ?>">
    <span class="brand-mark"><?php if ($schoolLogoUrl): ?><img src="<?= e($schoolLogoUrl) ?>" alt="Logo <?= e($schoolName) ?>"><?php else: ?><?= e($schoolMark) ?><?php endif; ?></span><span><strong><?= e($schoolName) ?></strong><small>Portal Keuangan Santri</small></span>
  </a>
  <div class="side-label">MENU UTAMA</div>
  <?php if (($user['role'] ?? '') === 'admin'): ?>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='admin.php'?'active':'' ?>" href="<?= APP_BASE ?>/admin.php"><span>▦</span> Dashboard</a>
    <a class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']),['students.php','santri_tagihan.php'],true)?'active':'' ?>" href="<?= APP_BASE ?>/students.php"><span>♙</span> Data & Cari Santri</a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='nominal_jenjang.php'?'active':'' ?>" href="<?= APP_BASE ?>/nominal_jenjang.php"><span>◈</span> Nominal Jenjang</a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='nominal_spp.php'?'active':'' ?>" href="<?= APP_BASE ?>/nominal_spp.php"><span>▤</span> Nominal SPP</a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='users.php'?'active':'' ?>" href="<?= APP_BASE ?>/users.php"><span>♙</span> Kelola User</a>
    <a class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']),['payments.php','reports.php'],true)?'active':'' ?>" href="<?= APP_BASE ?>/payments.php"><span>▤</span> Pembayaran & Laporan</a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='settings.php'?'active':'' ?>" href="<?= APP_BASE ?>/settings.php"><span>⚙</span> Pengaturan Pesantren</a>
  <?php else: ?>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='student.php'?'active':'' ?>" href="<?= APP_BASE ?>/student.php"><span>▦</span> Dashboard Saya</a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='pay.php'?'active':'' ?>" href="<?= APP_BASE ?>/pay.php"><span>＋</span> Bayar SPP</a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='history.php'?'active':'' ?>" href="<?= APP_BASE ?>/history.php"><span>◷</span> Riwayat Pembayaran</a>
  <?php endif; ?>
  <div class="sidebar-bottom">
    <div class="profile-mini"><div class="avatar"><?= e(strtoupper(substr($user['full_name'] ?? 'U',0,1))) ?></div><div><strong><?= e($user['full_name'] ?? 'Pengguna') ?></strong><small><?= e(ucfirst($user['role'] ?? '')) ?></small></div></div>
    <a class="nav-link logout" href="<?= APP_BASE ?>/logout.php"><span>↪</span> Keluar</a>
  </div>
</aside>
<main class="main-area">
  <header class="topbar"><div class="topbar-title"><div class="eyebrow">PONDOK PESANTREN</div><h1><?= e($pageTitle) ?></h1></div><div class="topbar-actions"><?php if (($user['role'] ?? '') === 'admin'): ?><button class="notification-icon-btn <?= $pendingNotifications > 0 ? 'has-notifications' : '' ?>" type="button" id="notificationOpen" aria-label="Buka notifikasi" aria-haspopup="dialog" aria-controls="notificationModal"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><?php if ($pendingNotifications > 0): ?><span class="notification-count"><?= $pendingNotifications > 99 ? '99+' : $pendingNotifications ?></span><?php endif; ?></button><?php endif; ?><div class="top-user"><span class="online-dot"></span><?= e($user['full_name'] ?? '') ?></div><button class="hamburger-btn" type="button" data-sidebar-open aria-controls="appSidebar" aria-expanded="false" aria-label="Buka menu navigasi"><span></span><span></span><span></span></button></div></header>
<?php if (($user['role'] ?? '') === 'admin'): ?>
<div class="premium-modal-backdrop notification-backdrop" id="notificationModal" aria-hidden="true" role="presentation">
  <section class="premium-modal notification-modal" role="dialog" aria-modal="true" aria-labelledby="notificationTitle">
    <button class="premium-modal-close" type="button" data-notification-close aria-label="Tutup">×</button>
    <div class="premium-modal-icon notification-modal-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg></div>
    <div class="premium-modal-kicker">PUSAT PEMBERITAHUAN</div><h2 id="notificationTitle">Notifikasi</h2><p class="notification-intro">Pilih notifikasi untuk membuka halaman sumbernya.</p>
    <div class="notification-list">
      <?php if ($pendingNotifications > 0): ?>
      <a class="notification-item" href="<?= APP_BASE ?>/payments.php?status=pending"><span class="notification-item-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v4l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg></span><span class="notification-item-copy"><strong>Pembayaran perlu diverifikasi</strong><small><?= number_format($pendingNotifications) ?> transaksi menunggu pemeriksaan admin. Klik untuk membuka daftar verifikasi.</small></span><span class="notification-item-arrow" aria-hidden="true">›</span></a>
      <?php else: ?><div class="notification-empty"><span>✓</span><strong>Tidak ada notifikasi baru</strong><small>Semua pembayaran sudah ditangani atau belum ada pengajuan baru.</small></div><?php endif; ?>
    </div>
    <button class="btn premium-modal-primary" type="button" data-notification-close>Tutup</button>
  </section>
</div>
<?php endif; ?>
  <div class="content">
  <?php if ($flashData): ?>
  <div class="premium-modal-backdrop is-open" id="premiumFlashModal" role="presentation">
    <section class="premium-modal" role="dialog" aria-modal="true" aria-labelledby="premiumFlashTitle" aria-describedby="premiumFlashMessage">
      <button class="premium-modal-close" type="button" data-popup-close aria-label="Tutup">×</button>
      <div class="premium-modal-icon <?= e($flashData['type']) ?>" aria-hidden="true"><?= ($flashData['type'] ?? '') === 'error' ? '!' : (($flashData['type'] ?? '') === 'warning' ? '!' : '✓') ?></div>
      <div class="premium-modal-kicker"><?= e(strtoupper($schoolName)) ?> · NOTIFIKASI</div>
      <h2 id="premiumFlashTitle"><?= ($flashData['type'] ?? '') === 'error' ? 'Terjadi kendala' : (($flashData['type'] ?? '') === 'warning' ? 'Perhatian' : 'Berhasil diproses') ?></h2>
      <p id="premiumFlashMessage"><?= e($flashData['message']) ?></p>
      <button class="btn premium-modal-primary" type="button" data-popup-close>Baik, mengerti</button>
    </section>
  </div>
  <?php endif; ?>
