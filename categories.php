<?php
require __DIR__ . '/config.php';
require_role('admin');
$pageTitle = 'Kategori & Nominal';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $spp = (float)($_POST['spp_amount'] ?? 0);
        $desc = trim($_POST['description'] ?? '');
        if (!$name || $amount < 0 || $spp < 0) {
            flash('Nama kategori dan nominal harus valid.', 'error');
            redirect('categories.php');
        }
        try {
            if ($id) {
                $q = $pdo->prepare('UPDATE fee_categories SET name=?,amount=?,spp_amount=?,description=? WHERE id=?');
                $q->execute([$name, $amount, $spp, $desc, $id]);
            } else {
                $q = $pdo->prepare('INSERT INTO fee_categories(name,amount,spp_amount,description) VALUES(?,?,?,?)');
                $q->execute([$name, $amount, $spp, $desc]);
            }
            flash('Kategori dan nominal berhasil disimpan.');
        } catch (Throwable $e) {
            flash('Gagal menyimpan. Nama kategori mungkin sudah digunakan.', 'error');
        }
        redirect('categories.php');
    }
    if ($action === 'toggle') {
        $q = $pdo->prepare('UPDATE fee_categories SET active=1-active WHERE id=?');
        $q->execute([(int)$_POST['id']]);
        flash('Status kategori diperbarui.');
        redirect('categories.php');
    }
}
$edit = null;
if (isset($_GET['edit'])) {
    $q = $pdo->prepare('SELECT * FROM fee_categories WHERE id=?');
    $q->execute([(int)$_GET['edit']]);
    $edit = $q->fetch();
}
$rows = $pdo->query('SELECT * FROM fee_categories ORDER BY name')->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="grid two-col">
    <section class="card">
        <div class="section-head">
            <div>
                <h2><?= $edit ? 'Ubah kategori' : 'Tambah kategori jenjang' ?></h2>
                <p>Tetapkan nominal kategori jenjang dan SPP bulanan.</p>
            </div>
        </div>
        <form method="post" class="form-grid"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
            <div class="form-group full"><label>Nama kategori / jenjang</label><input class="input" name="name" value="<?= e($edit['name'] ?? '') ?>" required placeholder="Contoh: Wustha"></div>
            <div class="form-group"><label>Nominal kategori (Rp)</label><input class="input" type="number" name="amount" min="0" step="1000" value="<?= e((string)($edit['amount'] ?? 0)) ?>" required></div>
            <div class="form-group"><label>Nominal SPP per bulan (Rp)</label><input class="input" type="number" name="spp_amount" min="0" step="1000" value="<?= e((string)($edit['spp_amount'] ?? 0)) ?>" required></div>
            <div class="form-group full"><label>Keterangan</label><input class="input" name="description" value="<?= e($edit['description'] ?? '') ?>" placeholder="Opsional"></div>
            <div class="form-group full"><button class="btn" type="submit">Simpan kategori</button><?php if ($edit): ?><a class="btn btn-light" href="<?= APP_BASE ?>/categories.php">Batal</a><?php endif; ?></div>
        </form>
        <p class="help">Nominal kategori dan nominal SPP disimpan terpisah. Saat membuat tagihan, keduanya dijumlahkan sesuai aturan biaya pesantren. Jika kategori tidak dikenai biaya terpisah, isi nominal kategori dengan 0.</p>
    </section>
    <section class="card">
        <h2>Ringkasan nominal</h2>
        <p class="help" style="line-height:1.8">Santri memilih kategori jenjang saat mengajukan pembayaran. Sistem menyimpan salinan nominal kategori dan SPP pada transaksi agar riwayat lama tidak berubah ketika tarif diperbarui.</p>
        <div class="pill"><?= count($rows) ?> kategori terdaftar</div>
    </section>
</div>
<section class="card" style="margin-top:20px">
    <div class="section-head">
        <div>
            <h2>Daftar kategori</h2>
            <p>Nominal yang akan digunakan pada pembayaran baru.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nama kategori</th>
                    <th>Nominal kategori</th>
                    <th>SPP / bulan</th>
                    <th>Total / bulan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?><tr>
                        <td><strong><?= e($r['name']) ?></strong>
                            <div class="help"><?= e($r['description'] ?? '') ?></div>
                        </td>
                        <td><?= rupiah($r['amount']) ?></td>
                        <td><?= rupiah($r['spp_amount']) ?></td>
                        <td><strong><?= rupiah((float)$r['amount'] + (float)$r['spp_amount']) ?></strong></td>
                        <td><span class="badge <?= $r['active'] ? 'paid' : 'neutral' ?>"><?= $r['active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td>
                            <div style="display:flex;gap:6px"><a class="btn btn-light btn-small" href="<?= APP_BASE ?>/categories.php?edit=<?= (int)$r['id'] ?>">Ubah</a>
                                <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-light btn-small"><?= $r['active'] ? 'Nonaktif' : 'Aktifkan' ?></button></form>
                            </div>
                        </td>
                    </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>