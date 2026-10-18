<?php
require __DIR__ . '/config.php';
require_role('santri');
$pageTitle = 'Riwayat Pembayaran';
$sid = current_student_id($pdo);
$q = $pdo->prepare('SELECT p.*,fc.name category FROM payments p JOIN fee_categories fc ON fc.id=p.fee_category_id WHERE p.student_id=? ORDER BY p.created_at DESC');
$q->execute([$sid]);
$rows = $q->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<section class="card">
    <div class="section-head">
        <div>
            <h2>Riwayat transaksi</h2>
            <p>Pantau status pengajuan dan bukti pembayaran Anda.</p>
        </div><a class="btn" href="<?= APP_BASE ?>/pay.php">＋ Bayar SPP</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nomor transaksi</th>
                    <th>Bulan</th>
                    <th>Kategori</th>
                    <th>Metode</th>
                    <th>Nominal</th>
                    <th>Status</th>
                    <th>Bukti</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?><tr>
                        <td><strong><?= e($r['order_id']) ?></strong>
                            <div class="help"><?= e(date('d-m-Y H:i', strtotime($r['created_at']))) ?></div>
                        </td>
                        <td><?= e(month_label($r['billing_month'])) ?></td>
                        <td><?= e($r['category']) ?></td>
                        <td><?= e(['cash' => 'Tunai akademik', 'bank_transfer' => 'Transfer bank', 'midtrans' => 'Midtrans'][$r['method']] ?? $r['method']) ?></td>
                        <td><strong><?= rupiah($r['total_amount']) ?></strong>
                            <div class="help">SPP <?= rupiah($r['spp_amount']) ?> + kategori <?= rupiah($r['category_amount']) ?></div>
                        </td>
                        <td><span class="badge <?= e($r['status'] === 'paid' ? 'paid' : ($r['status'] === 'pending' ? 'pending' : $r['status'])) ?>"><?= e(ucfirst($r['status'])) ?></span><?php if ($r['notes']): ?><div class="help"><?= e($r['notes']) ?></div><?php endif; ?></td>
                        <td><?php if ($r['proof_path']): ?><a class="btn btn-light btn-small" target="_blank" rel="noopener" href="<?= APP_BASE ?>/<?= e($r['proof_path']) ?>">Lihat ↗</a><?php elseif ($r['method'] === 'midtrans' && $r['status'] === 'pending'): ?><div class="help">Periksa status transaksi yang sudah dibuat; jangan membuat ulang order ID yang sama.</div>
                                <form method="post" action="<?= APP_BASE ?>/midtrans_sync.php" style="display:inline-block;margin-top:6px"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="order_id" value="<?= e($r['order_id']) ?>"><button class="btn btn-light btn-small" type="submit">Periksa status</button></form><?php else: ?><span class="help">—</span><?php endif; ?>
                        </td>
                    </tr><?php endforeach; ?>
                <?php if (!$rows): ?><tr>
                        <td colspan="7" class="empty">
                            <div class="empty-icon">◷</div><strong>Belum ada riwayat pembayaran</strong>
                            <p>Pengajuan SPP Anda akan tampil di halaman ini.</p>
                        </td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>