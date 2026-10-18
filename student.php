<?php
require __DIR__ . '/config.php';
require_role('santri');
$pageTitle = 'Dashboard Saya';
$sid = current_student_id($pdo);
$q = $pdo->prepare('SELECT s.*,u.full_name,fc.id category_id,fc.name category,fc.amount,fc.spp_amount FROM students s JOIN users u ON u.id=s.user_id LEFT JOIN fee_categories fc ON fc.name=s.class_level AND fc.active=1 WHERE s.id=?');
$q->execute([$sid]);
$student = $q->fetch();
$cat = $pdo->query('SELECT * FROM fee_categories WHERE active=1 ORDER BY name')->fetchAll();
$q = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE student_id=? AND status='paid'");
$q->execute([$sid]);
$paidCount = (int)$q->fetchColumn();
$q = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM payments WHERE student_id=? AND status='paid'");
$q->execute([$sid]);
$totalPaid = (float)$q->fetchColumn();
$pendingQ = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE student_id=? AND status='pending'");
$pendingQ->execute([$sid]);
$pending = (int)$pendingQ->fetchColumn();
$start = new DateTime(date('Y-m-01'));
$months = [];
for ($i = 0; $i < 12; $i++) {
    $d = (clone $start)->modify("-$i months")->format('Y-m-01');
    $q = $pdo->prepare("SELECT status FROM payments WHERE student_id=? AND billing_month=? AND status IN ('paid','pending') ORDER BY status='paid' DESC");
    $q->execute([$sid, $d]);
    $st = $q->fetchColumn();
    $months[] = ['date' => $d, 'status' => $st ?: 'unpaid'];
}
require __DIR__ . '/includes/header.php';
?>
<div class="grid stats-grid">
    <div class="card stat-card">
        <div class="stat-icon">✓</div>
        <div class="stat-label">Total pembayaran lunas</div>
        <div class="stat-value"><?= rupiah($totalPaid) ?></div>
        <div class="stat-note"><?= $paidCount ?> transaksi tercatat</div>
    </div>
    <div class="card stat-card">
        <div class="stat-icon">◷</div>
        <div class="stat-label">Menunggu verifikasi</div>
        <div class="stat-value"><?= $pending ?></div>
        <div class="stat-note">Transaksi dalam proses</div>
    </div>
    <div class="card stat-card">
        <div class="stat-icon">◈</div>
        <div class="stat-label">Jenjang saat ini</div>
        <div class="stat-value" style="font-size:22px"><?= e($student['class_level']) ?></div>
        <div class="stat-note">Nomor: <?= e($student['student_no']) ?></div>
    </div>
    <div class="card stat-card">
        <div class="stat-icon">＋</div>
        <div class="stat-label">Pembayaran SPP</div>
        <div class="stat-value" style="font-size:19px">Bayar online</div>
        <div class="stat-note"><a style="color:var(--green);font-weight:700" href="<?= APP_BASE ?>/pay.php">Pilih bulan →</a></div>
    </div>
</div>
<div class="grid two-col">
    <section class="card">
        <div class="section-head">
            <div>
                <h2>Status SPP bulanan</h2>
                <p>12 bulan terakhir. Pilih bulan yang belum lunas untuk mengajukan pembayaran.</p>
            </div><a class="btn" href="<?= APP_BASE ?>/pay.php">Bayar SPP</a>
        </div>
        <div class="month-grid"><?php foreach ($months as $m): ?><div class="month-option <?= $m['status'] === 'unpaid' ? 'overdue' : '' ?>"><label style="cursor:default"><strong><?= e(month_label($m['date'])) ?></strong><small><?= $m['status'] === 'paid' ? '✓ Lunas' : ($m['status'] === 'pending' ? '◷ Menunggu verifikasi' : '! Belum lunas') ?></small></label></div><?php endforeach; ?></div>
        <div class="help" style="margin-top:14px">Catatan: status lunas hanya dihitung dari transaksi yang telah diverifikasi admin atau dikonfirmasi oleh notifikasi resmi Midtrans.</div>
    </section>
    <section class="card">
        <h2>Informasi akun</h2>
        <div style="margin-top:14px">
            <div class="summary-row"><span>Nama santri</span><strong><?= e($student['full_name']) ?></strong></div>
            <div class="summary-row"><span>Nomor induk</span><strong><?= e($student['student_no']) ?></strong></div>
            <div class="summary-row"><span>Jenjang</span><strong><?= e($student['class_level']) ?></strong></div>
            <div class="summary-row"><span>Tarif jenjang</span><strong><?= $student['amount'] !== null ? rupiah($student['amount']) : 'Pilih kategori saat bayar' ?></strong></div>
            <div class="summary-row"><span>SPP per bulan</span><strong><?= $student['spp_amount'] !== null ? rupiah($student['spp_amount']) : 'Pilih kategori saat bayar' ?></strong></div>
        </div><a class="btn btn-light" style="margin-top:18px;width:100%" href="<?= APP_BASE ?>/history.php">Lihat riwayat pembayaran</a>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>