<?php
require __DIR__ . '/config.php';
require_role('santri');
$pageTitle = 'Bayar SPP';
$sid = current_student_id($pdo);
$categories = $pdo->query('SELECT * FROM fee_categories WHERE active=1 ORDER BY name')->fetchAll();
$months = [];
$start = new DateTime(date('Y-m-01'));
for ($i = 0; $i < 24; $i++) {
    $d = (clone $start)->modify("-$i months")->format('Y-m-01');
    $q = $pdo->prepare("SELECT status FROM payments WHERE student_id=? AND billing_month=? AND status IN ('paid','pending') ORDER BY status='paid' DESC");
    $q->execute([$sid, $d]);
    $st = $q->fetchColumn();
    if ($st !== 'paid') $months[] = ['date' => $d, 'status' => $st ?: 'unpaid'];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $method = $_POST['method'] ?? '';
    $selected = array_unique(array_filter($_POST['months'] ?? [], fn($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-01$/', $d)));
    $q = $pdo->prepare('SELECT * FROM fee_categories WHERE id=? AND active=1');
    $q->execute([$categoryId]);
    $cat = $q->fetch();
    if (!$cat || !in_array($method, ['cash', 'bank_transfer', 'midtrans'], true) || !$selected) {
        flash('Pilih kategori, minimal satu bulan, dan metode pembayaran.', 'error');
        redirect('pay.php');
    }
    if (count($selected) > 12) {
        flash('Maksimal 12 bulan dalam satu pengajuan.', 'error');
        redirect('pay.php');
    }
    if ($method === 'midtrans' && count($selected) !== 1) {
        flash('Untuk pembayaran Midtrans, pilih tepat satu bulan per transaksi agar Order ID dan status pembayaran dapat dilacak dengan benar.', 'error');
        redirect('pay.php');
    }
    $proofPath = null;
    if (in_array($method, ['cash', 'bank_transfer'], true)) {
        if (empty($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
            flash('Unggah bukti pembayaran terlebih dahulu.', 'error');
            redirect('pay.php');
        }
        $f = $_FILES['proof'];
        if ($f['size'] > 3 * 1024 * 1024) {
            flash('Ukuran bukti maksimal 3 MB.', 'error');
            redirect('pay.php');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
        if (!isset($allowed[$mime])) {
            flash('Bukti harus berupa JPG, PNG, atau PDF.', 'error');
            redirect('pay.php');
        }
        $name = bin2hex(random_bytes(18)) . '.' . $allowed[$mime];
        $dir = __DIR__ . '/uploads';
        if (!is_dir($dir)) mkdir($dir, 0750, true);
        if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
            flash('Gagal menyimpan berkas bukti.', 'error');
            redirect('pay.php');
        }
        $proofPath = 'uploads/' . $name;
    }
    $pdo->beginTransaction();
    try {
        foreach ($selected as $month) {
            $dt = DateTime::createFromFormat('!Y-m-d', $month);
            if (!$dt || $dt->format('Y-m-d') !== $month || $month > date('Y-m-01')) continue;
            $q = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE student_id=? AND billing_month=? AND status IN ('paid','pending')");
            $q->execute([$sid, $month]);
            if ((int)$q->fetchColumn() > 0) continue;
            $total = (float)$cat['amount'] + (float)$cat['spp_amount'];
            $order = 'SPP-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
            $q = $pdo->prepare("INSERT INTO payments(order_id,student_id,fee_category_id,billing_month,category_amount,spp_amount,total_amount,method,status,proof_path) VALUES(?,?,?,?,?,?,?,?,?,?)");
            $q->execute([$order, $sid, $categoryId, $month, $cat['amount'], $cat['spp_amount'], $total, $method, 'pending', $proofPath]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        if ($proofPath && is_file(__DIR__ . '/' . $proofPath)) @unlink(__DIR__ . '/' . $proofPath);
        flash('Pengajuan gagal disimpan. Periksa transaksi dan coba lagi.', 'error');
        redirect('pay.php');
    }
    if ($method === 'midtrans') {
        // Lanjutkan ke checkout untuk satu order yang baru saja disimpan.
        redirect('midtrans_create.php?order_id=' . rawurlencode((string)($order ?? '')));
    }
    flash($method === 'cash' ? 'Pengajuan pembayaran tunai tersimpan. Serahkan/konfirmasikan pembayaran ke bagian akademik dan tunggu verifikasi.' : 'Bukti transfer tersimpan. Pembayaran menunggu verifikasi admin.');
    redirect('history.php');
}
require __DIR__ . '/includes/header.php';
?>
<div class="grid two-col">
    <section class="card">
        <div class="section-head">
            <div>
                <h2>Ajukan pembayaran</h2>
                <p>Pilih kategori jenjang dan bulan yang ingin dibayar.</p>
            </div>
        </div>
        <form method="post" enctype="multipart/form-data" id="pay-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <div class="form-group"><label>Kategori jenjang</label><select name="category_id" id="category" required>
                    <option value="">Pilih kategori...</option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" data-cat="<?= (float)$c['amount'] ?>" data-spp="<?= (float)$c['spp_amount'] ?>"><?= e($c['name']) ?> — total <?= rupiah((float)$c['amount'] + (float)$c['spp_amount']) ?>/bulan</option><?php endforeach; ?>
                </select></div>
            <div style="margin:19px 0 12px" class="section-head">
                <div>
                    <h2 style="font-size:14px">Bulan yang dibayar</h2>
                    <p>Centang satu atau beberapa bulan yang belum lunas.</p>
                </div><button class="btn btn-light btn-small" type="button" onclick="document.querySelectorAll('.month-check').forEach(x=>x.checked=!x.disabled)">Pilih semua</button>
            </div>
            <div class="month-grid"><?php foreach ($months as $m): ?><div class="month-option <?= $m['status'] === 'unpaid' ? 'overdue' : '' ?>"><input class="month-check" type="checkbox" name="months[]" id="m<?= e(str_replace('-', '', $m['date'])) ?>" value="<?= e($m['date']) ?>" <?= $m['status'] === 'pending' ? 'disabled' : '' ?>><label for="m<?= e(str_replace('-', '', $m['date'])) ?>"><strong><?= e(month_label($m['date'])) ?></strong><small><?= $m['status'] === 'pending' ? 'Menunggu verifikasi' : 'Belum lunas' ?></small></label></div><?php endforeach; ?></div>
            <?php if (!$months): ?><div class="alert success" style="margin-top:15px">Tidak ada bulan tertunggak yang dapat diajukan. Jika ada transaksi yang ditolak, periksa riwayat pembayaran.</div><?php endif; ?>
            <div style="margin:22px 0 12px">
                <h2 style="font:800 14px Manrope;margin:0 0 5px">Metode pembayaran</h2>
                <p class="help">Tunai dan transfer memerlukan bukti. Midtrans membutuhkan konfigurasi gateway aktif.</p>
            </div>
            <div class="payment-methods">
                <div class="method-option"><input type="radio" name="method" id="cash" value="cash" checked><label for="cash"><span>🏢</span><strong>Tunai akademik</strong><small>Bayar di bagian akademik, lalu unggah bukti/nota.</small></label></div>
                <div class="method-option"><input type="radio" name="method" id="bank" value="bank_transfer"><label for="bank"><span>🏦</span><strong>Transfer bank</strong><small>Transfer ke rekening resmi dan unggah bukti.</small></label></div>
                <div class="method-option"><input type="radio" name="method" id="midtrans" value="midtrans"><label for="midtrans"><span>🔒</span><strong>Midtrans</strong><small>Konfirmasi otomatis setelah notifikasi gateway tervalidasi.</small></label></div>
            </div>
            <div class="form-group" id="proof-field" style="margin-top:16px"><label>Bukti pembayaran (JPG, PNG, PDF; maks. 3 MB)</label><input class="input" type="file" name="proof" id="proof" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"><span class="help">Untuk tunai, lampirkan nota/bukti dari bagian akademik. Untuk transfer, lampirkan bukti transfer yang jelas.</span></div>
            <div class="form-group" style="margin-top:20px"><button class="btn" type="submit" <?= !$months ? 'disabled' : '' ?>>Kirim pengajuan pembayaran →</button></div>
        </form>
    </section>
    <section class="card">
        <h2>Ringkasan pembayaran</h2>
        <p class="help" style="line-height:1.7">Nominal dihitung berdasarkan kategori yang dipilih dikalikan jumlah bulan. Pastikan jenjang dan periode benar sebelum mengirim pengajuan.</p>
        <div class="summary-row"><span>Nominal kategori</span><strong id="cat-amount">Rp 0</strong></div>
        <div class="summary-row"><span>SPP per bulan</span><strong id="spp-amount">Rp 0</strong></div>
        <div class="summary-row"><span>Jumlah bulan dipilih</span><strong id="month-count">0</strong></div>
        <div class="summary-row"><span>Total pengajuan</span><strong id="grand-total" style="color:var(--green);font-size:16px">Rp 0</strong></div>
        <div class="alert warning" style="margin-top:20px">Pembayaran tunai dan transfer belum dianggap lunas sampai admin menyetujui bukti. Jangan mengunggah bukti yang berisi data sensitif yang tidak diperlukan.</div>
    </section>
</div>
<script>
    const category = document.getElementById('category');
    const checks = [...document.querySelectorAll('.month-check')];
    const money = n => 'Rp ' + Number(n).toLocaleString('id-ID', {
        maximumFractionDigits: 0
    });

    function calc() {
        const opt = category.options[category.selectedIndex];
        const c = Number(opt?.dataset.cat || 0),
            s = Number(opt?.dataset.spp || 0),
            n = checks.filter(x => x.checked).length;
        document.getElementById('cat-amount').textContent = money(c);
        document.getElementById('spp-amount').textContent = money(s);
        document.getElementById('month-count').textContent = n;
        document.getElementById('grand-total').textContent = money((c + s) * n)
    }
    category.addEventListener('change', calc);
    checks.forEach(x => x.addEventListener('change', calc));
    document.querySelectorAll('input[name="method"]').forEach(r => r.addEventListener('change', () => {
        const isMid = document.getElementById('midtrans').checked;
        document.getElementById('proof-field').style.display = isMid ? 'none' : 'flex';
        document.getElementById('proof').required = !isMid;
    }));
    calc();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>