<?php
require __DIR__ . '/config.php';
require_role('admin');
$pageTitle = 'Nominal SPP';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $spp = (float)($_POST['spp_amount'] ?? -1);
    if ($id < 1 || $spp < 0) {
        flash('Nominal SPP tidak valid.', 'error');
        redirect('nominal_spp.php');
    }
    $q = $pdo->prepare('UPDATE fee_categories SET spp_amount=? WHERE id=?');
    $q->execute([$spp, $id]);
    flash('Nominal SPP berhasil diperbarui.');
    redirect('nominal_spp.php');
}
$rows = $pdo->query('SELECT id,name,spp_amount,amount,active FROM fee_categories ORDER BY name')->fetchAll();
require __DIR__ . '/includes/header.php'; ?>
<section class="card">
    <div class="section-head">
        <div>
            <h2>Pengaturan SPP bulanan</h2>
            <p>Atur biaya SPP per bulan untuk setiap jenjang. Perubahan di sini tidak mengubah nominal jenjang.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Jenjang</th>
                    <th>Nominal jenjang (informasi)</th>
                    <th>SPP per bulan</th>
                    <th>Status</th>
                    <th>Simpan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?><tr>
                        <td><strong><?= e($r['name']) ?></strong></td>
                        <td><?= rupiah($r['amount']) ?></td>
                        <td>
                            <form method="post" style="display:flex;gap:8px;align-items:center;min-width:230px"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input class="input" type="number" name="spp_amount" min="0" step="1000" required value="<?= e((string)$r['spp_amount']) ?>" style="max-width:160px"><button class="btn btn-small" type="submit">Simpan</button></form>
                        </td>
                        <td><span class="badge <?= $r['active'] ? 'paid' : 'neutral' ?>"><?= $r['active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td class="help">Tarif berlaku untuk tagihan baru</td>
                    </tr><?php endforeach; ?>
                <?php if (!$rows): ?><tr>
                        <td colspan="5" class="empty">Belum ada jenjang. Tambahkan dahulu pada menu Nominal Jenjang.</td>
                    </tr><?php endif; ?></tbody>
        </table>
    </div>
    <p class="help" style="margin-top:14px">Catatan: nominal SPP yang sudah tercatat pada transaksi sebelumnya tidak ikut berubah.</p>
</section><?php require __DIR__ . '/includes/footer.php'; ?>