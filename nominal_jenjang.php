<?php
require __DIR__ . '/config.php';
require_role('admin');
$pageTitle = 'Nominal Jenjang';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $amount = (float)($_POST['amount'] ?? -1);
        $desc = trim($_POST['description'] ?? '');
        if ($name === '' || $amount < 0) {
            flash('Nama jenjang dan nominal harus valid.', 'error');
            redirect('nominal_jenjang.php');
        }
        try {
            if ($id) {
                $q = $pdo->prepare('UPDATE fee_categories SET name=?,amount=?,description=? WHERE id=?');
                $q->execute([$name, $amount, $desc, $id]);
            } else {
                $q = $pdo->prepare('INSERT INTO fee_categories(name,amount,spp_amount,description) VALUES(?,?,0,?)');
                $q->execute([$name, $amount, $desc]);
            }
            flash('Nominal jenjang berhasil disimpan.');
        } catch (Throwable $e) {
            flash('Gagal menyimpan. Nama jenjang mungkin sudah digunakan.', 'error');
        }
        redirect('nominal_jenjang.php');
    }
    if ($action === 'toggle') {
        $q = $pdo->prepare('UPDATE fee_categories SET active=1-active WHERE id=?');
        $q->execute([(int)$_POST['id']]);
        flash('Status jenjang diperbarui.');
        redirect('nominal_jenjang.php');
    }
}
$edit = null;
if (isset($_GET['edit'])) {
    $q = $pdo->prepare('SELECT * FROM fee_categories WHERE id=?');
    $q->execute([(int)$_GET['edit']]);
    $edit = $q->fetch() ?: null;
}
$rows = $pdo->query('SELECT * FROM fee_categories ORDER BY name')->fetchAll();
require __DIR__ . '/includes/header.php'; ?>
<section class="card">
    <div class="section-head">
        <div>
            <h2>Pengelolaan nominal jenjang</h2>
            <p>Atur biaya jenjang tanpa mengubah tarif SPP bulanan.</p>
        </div><button class="btn" type="button" onclick="openLevelModal()">＋ Tambah jenjang</button>
    </div>
    <div class="pill"><?= count($rows) ?> jenjang terdaftar</div>
</section>
<div class="premium-modal-backdrop <?= $edit ? 'is-open' : '' ?>" id="levelModal" role="presentation" aria-hidden="<?= $edit ? 'false' : 'true' ?>">
    <section class="premium-modal method-modal-rich" role="dialog" aria-modal="true" aria-labelledby="levelModalTitle"><button class="premium-modal-close" type="button" onclick="closeLevelModal()" aria-label="Tutup">×</button>
        <div class="premium-modal-icon" aria-hidden="true">◈</div>
        <div class="premium-modal-kicker">PENGATURAN BIAYA</div>
        <h2 id="levelModalTitle"><?= $edit ? 'Ubah nominal jenjang' : 'Tambah nominal jenjang' ?></h2>
        <p>Masukkan nama jenjang dan biaya yang akan digunakan pada tagihan baru.</p>
        <form method="post" class="form-grid"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
            <div class="form-group full"><label>Nama jenjang</label><input class="input" name="name" required value="<?= e($edit['name'] ?? '') ?>" placeholder="Contoh: Wustha"></div>
            <div class="form-group full"><label>Nominal jenjang (Rp)</label><input class="input" type="number" name="amount" min="0" step="1000" required value="<?= e((string)($edit['amount'] ?? 0)) ?>"></div>
            <div class="form-group full"><label>Keterangan</label><input class="input" name="description" value="<?= e($edit['description'] ?? '') ?>" placeholder="Opsional"></div>
            <div class="form-group full premium-modal-actions"><button class="btn btn-light" type="button" onclick="closeLevelModal()">Batal</button><button class="btn" type="submit">Simpan nominal</button></div>
        </form>
    </section>
</div>
<section class="card" style="margin-top:20px">
    <div class="section-head">
        <div>
            <h2>Daftar nominal jenjang</h2>
            <p>Tarif jenjang yang digunakan pada tagihan baru.</p>
        </div><input class="input" style="max-width:240px" placeholder="Cari jenjang..." oninput="filterRows(this.value,'level-table')">
    </div>
    <div class="table-wrap">
        <table id="level-table">
            <thead>
                <tr>
                    <th>Jenjang</th>
                    <th>Nominal jenjang</th>
                    <th>SPP bulanan (informasi)</th>
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
                        <td><span class="badge <?= $r['active'] ? 'paid' : 'neutral' ?>"><?= $r['active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap"><a class="btn btn-light btn-small" href="<?= APP_BASE ?>/nominal_jenjang.php?edit=<?= (int)$r['id'] ?>">Ubah</a>
                                <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-light btn-small" type="submit"><?= $r['active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
                            </div>
                        </td>
                    </tr><?php endforeach; ?>
                <?php if (!$rows): ?><tr>
                        <td colspan="5" class="empty">Belum ada jenjang.</td>
                    </tr><?php endif; ?></tbody>
        </table>
    </div>
</section>
<script>
    function filterRows(v, id) {
        document.querySelectorAll('#' + id + ' tbody tr').forEach(r => r.style.display = r.innerText.toLowerCase().includes(v.toLowerCase()) ? '' : 'none')
    }
</script>
<script>
    function openLevelModal() {
        var m = document.getElementById('levelModal');
        m.classList.add('is-open');
        m.setAttribute('aria-hidden', 'false');
        document.body.classList.add('premium-modal-open')
    }

    function closeLevelModal() {
        var m = document.getElementById('levelModal');
        m.classList.remove('is-open');
        m.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('premium-modal-open')
    }
    document.getElementById('levelModal').addEventListener('click', function(e) {
        if (e.target === this) closeLevelModal()
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeLevelModal()
    });
</script><?php require __DIR__ . '/includes/footer.php'; ?>