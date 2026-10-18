<?php
$pageTitle = $pageTitle ?? 'Dashboard';
$user = $_SESSION['user'] ?? null;
$flashData = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= APP_BASE ?>/assets/style.css">
</head>
<body>
<div class="app-shell">
<div class="sidebar-overlay" data-sidebar-overlay aria-hidden="true"></div>
<aside class="sidebar" id="appSidebar" aria-label="Menu navigasi">
  <button class="sidebar-close" type="button" data-sidebar-close aria-label="Tutup menu"><span></span><span></span></button>
  <a class="brand" href="<?= APP_BASE ?>/<?= ($user['role'] ?? '') === 'admin' ? 'admin.php' : 'student.php' ?>">
    <span class="brand-mark">MH</span><span><strong>Mambaul Huda</strong><small>Portal Keuangan Santri</small></span>
  </a>
  <div class="side-label">MENU UTAMA</div>
  <?php if (($user['role'] ?? '') === 'admin'): ?>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='admin.php'?'active':'' ?>" href="<?= APP_BASE ?>/admin.php"><span>▦</span> Dashboard</a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='students.php'?'active':'' ?>" href="<?= APP_BASE ?>/students.php"><span>♙</span> Data Santri</a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='categories.php'?'active':'' ?>" href="<?= APP_BASE ?>/categories.php"><span>◈</span> Kategori & Nominal</a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='payments.php'?'active':'' ?>" href="<?= APP_BASE ?>/payments.php"><span>▤</span> Pembayaran SPP</a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='reports.php'?'active':'' ?>" href="<?= APP_BASE ?>/reports.php"><span>▥</span> Laporan Transaksi</a>
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
  <header class="topbar"><div class="topbar-title"><div class="eyebrow">PONDOK PESANTREN</div><h1><?= e($pageTitle) ?></h1></div><div class="topbar-actions"><div class="top-user"><span class="online-dot"></span><?= e($user['full_name'] ?? '') ?></div><button class="hamburger-btn" type="button" data-sidebar-open aria-controls="appSidebar" aria-expanded="false" aria-label="Buka menu navigasi"><span></span><span></span><span></span></button></div></header>
  <div class="content">
  <?php if ($flashData): ?><div class="alert <?= e($flashData['type']) ?>"><?= e($flashData['message']) ?></div><?php endif; ?>
