<?php
require __DIR__ . '/config.php';
require_role('admin');
$pageTitle = 'Dashboard Keuangan';
$students = (int)$pdo->query('SELECT COUNT(*) FROM students WHERE active=1')->fetchColumn();
$paid = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM payments WHERE status='paid' AND DATE_FORMAT(billing_month,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')")->fetchColumn();
$pending = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status='pending'")->fetchColumn();
$monthlyRows = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, SUM(CASE WHEN status='paid' THEN total_amount ELSE 0 END) AS total_paid, COUNT(CASE WHEN status='paid' THEN 1 END) AS transactions FROM payments WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY ym")->fetchAll(PDO::FETCH_ASSOC);
$monthlyMap = []; foreach ($monthlyRows as $mr) { $monthlyMap[$mr['ym']] = (float)$mr['total_paid']; }
$chartLabels = []; $chartValues = []; for ($i=11; $i>=0; $i--) { $ym = date('Y-m', strtotime(date('Y-m-01') . " -$i months")); $chartLabels[] = date('M y', strtotime($ym . '-01')); $chartValues[] = $monthlyMap[$ym] ?? 0; }
$overdue = (int)$pdo->query("SELECT COUNT(*) FROM students s WHERE s.active=1 AND EXISTS (SELECT 1 FROM payments p WHERE p.student_id=s.id AND p.status='paid' AND p.billing_month=DATE_FORMAT(CURDATE(),'%Y-%m-01'))=0")->fetchColumn();
$recent = $pdo->query("SELECT p.*,u.full_name,fc.name category FROM payments p JOIN students s ON s.id=p.student_id JOIN users u ON u.id=s.user_id JOIN fee_categories fc ON fc.id=p.fee_category_id ORDER BY p.created_at DESC LIMIT 7")->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<?php if ($pending > 0): ?><div class="alert admin-pending-notice" role="status"><strong>Perlu verifikasi:</strong> ada <?= number_format($pending) ?> pembayaran yang menunggu pemeriksaan. <a href="<?= APP_BASE ?>/payments.php?status=pending">Periksa sekarang →</a></div><?php endif; ?>
<div class="grid stats-grid">
    <div class="card stat-card">
        <div class="stat-icon">♙</div>
        <div class="stat-label">Santri aktif</div>
        <div class="stat-value"><?= number_format($students) ?></div>
        <div class="stat-note">Terdaftar dalam sistem</div>
    </div>
    <div class="card stat-card">
        <div class="stat-icon">↗</div>
        <div class="stat-label">Pembayaran bulan ini</div>
        <div class="stat-value"><?= rupiah($paid) ?></div>
        <div class="stat-note">Transaksi berstatus lunas</div>
    </div>
    <div class="card stat-card">
        <div class="stat-icon">◷</div>
        <div class="stat-label">Menunggu verifikasi</div>
        <div class="stat-value"><?= number_format($pending) ?></div>
        <div class="stat-note">Tunai dan transfer bank</div>
    </div>
    <div class="card stat-card">
        <div class="stat-icon">!</div>
        <div class="stat-label">Belum tercatat lunas bulan ini</div>
        <div class="stat-value"><?= number_format($overdue) ?></div>
        <div class="stat-note">Perlu ditinjau petugas</div>
    </div>
</div>
<section class="card monthly-chart-card">
    <div class="section-head"><div><h2>Grafik pembayaran bulanan</h2><p>Total pembayaran berstatus lunas selama 12 bulan terakhir.</p></div><span class="pill">12 bulan terakhir</span></div>
    <div class="monthly-chart" role="img" aria-label="Grafik total pembayaran lunas setiap bulan">
      <?php $maxChart = max(array_merge([1], $chartValues)); foreach ($chartValues as $idx => $value): $height = max(3, (int)round(($value / $maxChart) * 100)); ?>
      <div class="chart-column"><div class="chart-value"><?= e(rupiah($value)) ?></div><div class="chart-track"><div class="chart-bar" style="height:<?= $height ?>%"></div></div><div class="chart-label"><?= e($chartLabels[$idx]) ?></div></div>
      <?php endforeach; ?>
    </div>
</section>
<div class="grid two-col">
    <section class="card">
        <div class="section-head">
            <div>
                <h2>Transaksi terbaru</h2>
                <p>Pantau aktivitas pembayaran santri.</p>
            </div><a class="btn btn-light" href="<?= APP_BASE ?>/payments.php">Lihat semua</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Santri</th>
                        <th>Periode</th>
                        <th>Nominal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $r): ?><tr>
                            <td>
                                <div class="person">
                                    <div class="person-avatar"><?= e(strtoupper(substr($r['full_name'], 0, 1))) ?></div>
                                    <div><strong><?= e($r['full_name']) ?></strong><small><?= e($r['category']) ?></small></div>
                                </div>
                            </td>
                            <td><?= e(month_label($r['billing_month'])) ?></td>
                            <td><?= rupiah($r['total_amount']) ?></td>
                            <td><span class="badge <?= e($r['status'] === 'paid' ? 'paid' : ($r['status'] === 'pending' ? 'pending' : $r['status'])) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
                        </tr><?php endforeach; ?>
                    <?php if (!$recent): ?><tr>
                            <td colspan="4" class="empty">Belum ada transaksi.</td>
                        </tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <section class="card">
        <div class="section-head">
            <div>
                <h2>Akses cepat</h2>
                <p>Kelola administrasi dalam satu tempat.</p>
            </div>
        </div>
        <div class="grid" style="gap:10px"><a class="card" style="box-shadow:none;background:#f9fcfa" href="<?= APP_BASE ?>/students.php"><strong>♙ &nbsp; Kelola data santri</strong>
                <p class="help">Tambah akun dan data santri baru.</p>
            </a><a class="card" style="box-shadow:none;background:#f9fcfa" href="<?= APP_BASE ?>/nominal_jenjang.php"><strong>◈ &nbsp; Nominal Jenjang</strong>
                <p class="help">Atur biaya jenjang secara terpisah dari SPP bulanan.</p>
            </a><a class="card" style="box-shadow:none;background:#f9fcfa" href="<?= APP_BASE ?>/nominal_spp.php"><strong>▤ &nbsp; Nominal SPP</strong><p class="help">Atur tarif SPP bulanan setiap jenjang.</p></a><a class="card" style="box-shadow:none;background:#f9fcfa" href="<?= APP_BASE ?>/users.php"><strong>♙ &nbsp; Kelola User</strong><p class="help">Kelola akun admin dan perbarui akun pengguna.</p></a><a class="card" style="box-shadow:none;background:#f9fcfa" href="<?= APP_BASE ?>/santri_tagihan.php"><strong>⌕ &nbsp; Cari Santri & Tagihan</strong><p class="help">Cari akun santri dan periksa riwayat tagihan.</p></a><a class="card" style="box-shadow:none;background:#f9fcfa" href="<?= APP_BASE ?>/payments.php"><strong>▤ &nbsp; Verifikasi pembayaran</strong>
                <p class="help">Periksa bukti tunai atau transfer.</p>
            </a></div>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>