<?php
require __DIR__ . '/config.php';
require_role('santri');
$pageTitle = 'Pembayaran SPP & Jenjang';
$sid = current_student_id($pdo);
$studentQ = $pdo->prepare('SELECT s.class_level, fc.id, fc.name, fc.amount, fc.spp_amount FROM students s LEFT JOIN fee_categories fc ON fc.name=s.class_level AND fc.active=1 WHERE s.id=?');
$studentQ->execute([$sid]);
$studentFee = $studentQ->fetch();
$categories = $studentFee && $studentFee['id'] ? [$studentFee] : [];
$initialPaymentType = in_array(($_GET['type'] ?? 'spp'), ['spp', 'jenjang'], true) ? (string)($_GET['type'] ?? 'spp') : 'spp';
$months = [];
$start = new DateTime(date('Y-m-01'));
for ($i = 0; $i < 12; $i++) {
    $d = (clone $start)->modify("-$i months")->format('Y-m-01');
    $q = $pdo->prepare("SELECT payment_type,status FROM payments WHERE student_id=? AND billing_month=? AND status IN ('paid','pending')");
    $q->execute([$sid, $d]);
    $statuses = ['spp' => 'unpaid', 'jenjang' => 'unpaid'];
    foreach ($q->fetchAll() as $row) {
        $type = $row['payment_type'] ?? 'combined';
        if (in_array($type, ['spp', 'jenjang'], true)) {
            if ($row['status'] === 'paid' || $statuses[$type] !== 'paid') $statuses[$type] = $row['status'];
        } elseif ($type === 'combined') {
            // Pembayaran gabungan mencakup kedua komponen.
            foreach (['spp', 'jenjang'] as $t) if ($row['status'] === 'paid' || $statuses[$t] !== 'paid') $statuses[$t] = $row['status'];
        }
    }
    // Tampilkan hanya komponen tagihan yang benar-benar belum dibayar. Tagihan paid/pending tidak ditawarkan lagi.
    if ($statuses['spp'] === 'unpaid' || $statuses['jenjang'] === 'unpaid') {
        $months[] = ['date' => $d, 'spp_status' => $statuses['spp'], 'jenjang_status' => $statuses['jenjang']];
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $method = (string)($_POST['method'] ?? '');
    $rawSelected = $_POST['bills'] ?? [];
    $selected = [];
    if (is_array($rawSelected)) {
        foreach ($rawSelected as $item) {
            if (!is_string($item) || !preg_match('/^(\d{4}-\d{2}-01)\|(spp|jenjang)$/', $item, $match)) continue;
            $month = $match[1];
            $type = $match[2];
            $dt = DateTime::createFromFormat('!Y-m-d', $month);
            if (!$dt || $dt->format('Y-m-d') !== $month || $month > date('Y-m-01')) continue;
            $selected[$month . '|' . $type] = ['month' => $month, 'type' => $type];
        }
    }
    $q = $pdo->prepare('SELECT * FROM fee_categories WHERE id=? AND active=1');
    $q->execute([$categoryId]);
    $cat = $q->fetch();
    if (!$cat || (int)$cat['id'] !== (int)($studentFee['id'] ?? 0) || !in_array($method, ['cash', 'bank_transfer'], true) || !$selected) {
        flash('Pilih minimal satu kartu tagihan dan metode pembayaran.', 'error');
        redirect('pay.php');
    }
    $bankDestination = $method === 'bank_transfer' ? (string)($_POST['bank_destination'] ?? '') : null;
    if ($method === 'bank_transfer' && !in_array($bankDestination, ['BRI', 'BCA'], true)) {
        flash('Pilih bank tujuan transfer.', 'error');
        redirect('pay.php');
    }
    $proofPath = null;
    if ($method === 'bank_transfer') {
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
        foreach ($selected as $bill) {
            $month = $bill['month'];
            $paymentType = $bill['type'];
            $check = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE student_id=? AND billing_month=? AND status IN ('paid','pending') AND (payment_type=? OR payment_type='combined')");
            $check->execute([$sid, $month, $paymentType]);
            if ((int)$check->fetchColumn() > 0) throw new RuntimeException('Salah satu tagihan sudah lunas atau sedang menunggu verifikasi. Muat ulang halaman.');
            $categoryAmount = $paymentType === 'jenjang' ? (float)$cat['amount'] : 0.0;
            $sppAmount = $paymentType === 'spp' ? (float)$cat['spp_amount'] : 0.0;
            $total = $categoryAmount + $sppAmount;
            if ($total <= 0) throw new RuntimeException('Nominal salah satu tagihan belum diatur admin.');
            $order = 'SPP-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
            $q = $pdo->prepare("INSERT INTO payments(order_id,student_id,fee_category_id,billing_month,payment_type,category_amount,spp_amount,total_amount,method,status,proof_path,bank_destination) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)");
            $q->execute([$order, $sid, $categoryId, $month, $paymentType, $categoryAmount, $sppAmount, $total, $method, 'pending', $proofPath, $bankDestination]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        if ($proofPath && is_file(__DIR__ . '/' . $proofPath)) @unlink(__DIR__ . '/' . $proofPath);
        flash($e instanceof RuntimeException ? $e->getMessage() : 'Pengajuan gagal disimpan. Periksa transaksi dan coba lagi.', 'error');
        redirect('pay.php');
    }
    flash($method === 'cash' ? 'Pengajuan pembayaran tunai berhasil dibuat. Silakan bayar kepada petugas dan tunggu verifikasi admin.' : 'Pengajuan transfer dan bukti transaksi berhasil dikirim. Pembayaran menunggu verifikasi admin.');
    redirect('history.php');
}
require __DIR__ . '/includes/header.php';
?>
<div class="grid two-col">
    <section class="card">
        <div class="section-head">
            <div>
                <h2>Pilih tagihan</h2>
                <p>Pilih satu, beberapa, atau seluruh kartu tagihan yang tersedia.</p>
            </div>
        </div>
        <form method="post" enctype="multipart/form-data" id="pay-form">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="category_id" value="<?= (int)($studentFee['id'] ?? 0) ?>">
            <div class="student-fee-summary"><span>Jenjang aktif</span><strong><?= e($studentFee['name'] ?? 'Belum diatur') ?></strong><span class="fee-summary-detail">SPP <?= rupiah($studentFee['spp_amount'] ?? 0) ?> / bulan · Biaya jenjang <?= rupiah($studentFee['amount'] ?? 0) ?></span></div>
            <div style="margin:19px 0 12px" class="section-head">
                <div>
                    <h2 style="font-size:14px">Tagihan per bulan</h2>
                    <p>Hanya tagihan yang masih tertunggak ditampilkan.</p>
                </div><button class="btn btn-light btn-small" type="button" id="select-all">Pilih semua</button>
            </div>
            <div class="month-grid payment-month-grid"><?php foreach ($months as $m): ?>
                    <?php foreach (['jenjang' => 'SPP (' . ($studentFee['name'] ?? 'Jenjang') . ')', 'spp' => 'SPP Pondok'] as $typeKey => $typeLabel): $status = $m[$typeKey === 'spp' ? 'spp_status' : 'jenjang_status'];
                                                            if ($status !== 'unpaid') continue;
                                                            $amount = (float)($typeKey === 'spp' ? ($studentFee['spp_amount'] ?? 0) : ($studentFee['amount'] ?? 0)); ?>
                        <label class="billing-card <?= $status === 'unpaid' ? 'is-unpaid' : '' ?> <?= $status !== 'unpaid' ? 'is-disabled' : '' ?>" data-type="<?= $typeKey ?>">
                            <input class="month-check" type="checkbox" name="bills[]" value="<?= e($m['date'] . '|' . $typeKey) ?>" data-month="<?= e($m['date']) ?>" data-type="<?= $typeKey ?>" data-amount="<?= $amount ?>" data-status="<?= e($status) ?>" <?= $status !== 'unpaid' ? 'disabled' : '' ?>>
                            <span class="billing-card-icon"><?= $typeKey === 'spp' ? '▦' : '◇' ?></span><span class="billing-card-copy"><strong><?= e(month_label($m['date'])) ?> · <?= e($typeLabel) ?></strong><small><?= rupiah($amount) ?><?= $typeKey === 'spp' ? ' / bulan' : '' ?></small><small><?= $status === 'paid' ? '✓ Sudah lunas' : ($status === 'pending' ? '◷ Menunggu verifikasi' : 'Pilih tagihan ini') ?></small></span><span class="billing-card-check">✓</span>
                        </label>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
            <?php if (!$months): ?><div class="alert success" style="margin-top:15px">Tidak ada bulan tertunggak yang dapat diajukan. Jika ada transaksi yang ditolak, periksa riwayat pembayaran.</div><?php endif; ?>
            <div style="margin:22px 0 12px">
                <h2 style="font:800 14px Manrope;margin:0 0 5px">Metode pembayaran</h2>
                <p class="help">Pilih metode untuk membuka formulir pembayaran yang sesuai.</p>
            </div>
            <input type="hidden" name="method" id="payment-method" value="">
            <div class="payment-methods method-launchers">
                <button class="method-launch" type="button" onclick="openMethodModal('cash')"><span>💵</span><strong>Tunai</strong><small>Bayar langsung kepada petugas pondok</small><b id="cash-selected" style="display:none">✓ Metode dipilih</b></button>
                <button class="method-launch" type="button" onclick="openMethodModal('bank_transfer')"><span>🏦</span><strong>Transfer bank</strong><small>Pilih bank dan unggah bukti transaksi</small><b id="bank-selected" style="display:none">✓ Metode dipilih</b></button>
            </div>
            <div class="selected-method-note" id="selected-method-note">Pilih metode pembayaran untuk melanjutkan.</div>
            <div class="form-group" style="margin-top:20px"><button class="btn" id="submit-payment" type="submit" disabled <?= !$months ? 'disabled' : '' ?>>Kirim pengajuan pembayaran →</button></div>
            <div class="premium-modal-backdrop" id="method-modal" aria-hidden="true">
                <div class="premium-modal method-modal-box method-modal-rich" role="dialog" aria-modal="true" aria-labelledby="method-modal-title">
                    <button class="premium-modal-close" type="button" onclick="closeMethodModal()">×</button>
                    <div class="premium-modal-icon" id="method-modal-icon">💵</div>
                    <div class="premium-modal-kicker" id="method-modal-kicker">PEMBAYARAN TUNAI</div>
                    <h2 id="method-modal-title">Pembayaran tunai</h2>
                    <div id="cash-modal-content" class="method-modal-content">
                        <p>Serahkan pembayaran langsung ke petugas/bagian administrasi pondok. Simpan bukti atau kuitansi yang diberikan petugas. Pengajuan akan tercatat dan menunggu verifikasi admin.</p>
                        <div class="cash-steps">
                            <div><span>1</span><strong>Konfirmasi tagihan</strong><small>Pastikan daftar tagihan dan total sudah benar.</small></div>
                            <div><span>2</span><strong>Bayar ke petugas</strong><small>Tunjukkan nama santri dan periode tagihan.</small></div>
                            <div><span>3</span><strong>Tunggu verifikasi</strong><small>Status berubah setelah admin memproses pembayaran.</small></div>
                        </div>
                        <div class="modal-total"><span>Total tagihan dipilih</span><strong id="cash-modal-total">Rp 0</strong></div>
                    </div>
                    <div id="bank-modal-content" class="method-modal-content" style="display:none">
                        <p>Pilih rekening tujuan, transfer sesuai total tagihan, lalu unggah bukti transaksi yang jelas.</p>
                        <div class="bank-choice-grid">
                            <label class="bank-choice" for="bank-bri"><input type="radio" name="bank_destination" id="bank-bri" value="BRI"><span class="bank-mark bri">BRI</span><span class="bank-account-copy"><strong>Bank BRI</strong><small class="bank-account-number"><?= e(app_setting('bank_bri_account', 'Nomor rekening belum diatur admin')) ?></small><small><?= e(app_setting('bank_bri_name', '')) ?></small><button class="btn btn-light btn-small copy-account" type="button" data-account="<?= e(app_setting('bank_bri_account', '')) ?>">Salin rekening</button></span></label>
                            <label class="bank-choice" for="bank-bca"><input type="radio" name="bank_destination" id="bank-bca" value="BCA"><span class="bank-mark bca">BCA</span><span class="bank-account-copy"><strong>Bank BCA</strong><small class="bank-account-number"><?= e(app_setting('bank_bca_account', 'Nomor rekening belum diatur admin')) ?></small><small><?= e(app_setting('bank_bca_name', '')) ?></small><button class="btn btn-light btn-small copy-account" type="button" data-account="<?= e(app_setting('bank_bca_account', '')) ?>">Salin rekening</button></span></label>
                        </div>
                        <div class="form-group proof-upload"><label for="proof">Bukti transaksi <span class="required-mark">*</span></label><input class="input" type="file" name="proof" id="proof" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"><small class="help">Format JPG, PNG, atau PDF · Maksimal 3 MB.</small></div>
                        <div class="modal-total"><span>Total tagihan dipilih</span><strong id="bank-modal-total">Rp 0</strong></div>
                    </div>
                    <div class="premium-modal-actions"><button class="btn btn-light" type="button" onclick="closeMethodModal()">Batal</button><button class="btn premium-modal-primary" type="button" id="confirm-method-btn" onclick="confirmMethod()">Gunakan metode tunai</button></div>
                </div>
            </div>
        </form>
    </section>
    <section class="card payment-summary-card">
        <div class="summary-heading-icon">✓</div>
        <h2>Ringkasan pembayaran</h2>
        <p class="help" style="line-height:1.7">Semua kartu yang dipilih akan dihitung satu per satu, termasuk SPP Pondok dan biaya jenjang pada bulan yang sama.</p>
        <div class="summary-row"><span>Tagihan dipilih</span><strong id="bill-count">0</strong></div>
        <div class="summary-row"><span>Periode unik</span><strong id="period-count">0 bulan</strong></div>
        <div class="summary-row"><span>Metode pembayaran</span><strong id="summary-method">Belum dipilih</strong></div>
        <div class="summary-row summary-total"><span>Total pengajuan</span><strong id="grand-total">Rp 0</strong></div>
        <div class="alert warning" style="margin-top:20px">Pembayaran tunai dan transfer belum dianggap lunas sampai admin menyetujui transaksi.</div>
    </section>
</div>
<!-- <section class="card payment-summary-card">
    <div class="summary-heading-icon">✓</div>
    <h2>Ringkasan pembayaran</h2>
    <p class="help" style="line-height:1.7">Semua kartu yang dipilih akan dihitung satu per satu, termasuk SPP Pondok dan biaya jenjang pada bulan yang sama.</p>
    <div class="summary-row"><span>Tagihan dipilih</span><strong id="bill-count">0</strong></div>
    <div class="summary-row"><span>Periode unik</span><strong id="period-count">0 bulan</strong></div>
    <div class="summary-row"><span>Metode pembayaran</span><strong id="summary-method">Belum dipilih</strong></div>
    <div class="summary-row summary-total"><span>Total pengajuan</span><strong id="grand-total">Rp 0</strong></div>
    <div class="alert warning" style="margin-top:20px">Pembayaran tunai dan transfer belum dianggap lunas sampai admin menyetujui transaksi.</div>
</section> -->
</div>
<script>
    const checks = [...document.querySelectorAll('.month-check')];
    const money = n => 'Rp ' + Number(n).toLocaleString('id-ID', {
        maximumFractionDigits: 0
    });
    let activeMethod = '';

    function selectedBills() {
        return checks.filter(x => x.checked && !x.disabled)
    }

    function calc() {
        const selected = selectedBills();
        const total = selected.reduce((sum, x) => sum + Number(x.dataset.amount || 0), 0);
        const periods = new Set(selected.map(x => x.dataset.month));
        checks.forEach(x => x.closest('.billing-card')?.classList.toggle('is-selected', x.checked));
        document.getElementById('bill-count').textContent = selected.length;
        document.getElementById('period-count').textContent = periods.size + ' bulan';
        document.getElementById('grand-total').textContent = money(total);
        document.getElementById('cash-modal-total').textContent = money(total);
        document.getElementById('bank-modal-total').textContent = money(total);
        document.getElementById('submit-payment').disabled = selected.length === 0 || !activeMethod;
        const selectAll = document.getElementById('select-all');
        if (selectAll) selectAll.textContent = checks.filter(x => !x.disabled).length && checks.filter(x => !x.disabled).every(x => x.checked) ? 'Batalkan semua' : 'Pilih semua';
    }
    checks.forEach(x => x.addEventListener('change', calc));
    document.getElementById('select-all').addEventListener('click', () => {
        const available = checks.filter(x => !x.disabled);
        const all = available.length && available.every(x => x.checked);
        available.forEach(x => x.checked = !all);
        calc()
    });

    function openMethodModal(method) {
        activeMethod = method;
        const bank = method === 'bank_transfer';
        document.getElementById('method-modal-title').textContent = bank ? 'Transfer bank' : 'Pembayaran tunai';
        document.getElementById('method-modal-kicker').textContent = bank ? 'PEMBAYARAN TRANSFER BANK' : 'PEMBAYARAN TUNAI';
        document.getElementById('method-modal-icon').textContent = bank ? '🏦' : '💵';
        document.getElementById('cash-modal-content').style.display = bank ? 'none' : 'block';
        document.getElementById('bank-modal-content').style.display = bank ? 'block' : 'none';
        document.getElementById('confirm-method-btn').textContent = bank ? 'Gunakan transfer bank' : 'Gunakan metode tunai';
        document.getElementById('method-modal').classList.add('is-open');
        document.getElementById('method-modal').setAttribute('aria-hidden', 'false');
        document.body.classList.add('premium-modal-open');
        calc();
    }

    function closeMethodModal() {
        document.getElementById('method-modal').classList.remove('is-open');
        document.getElementById('method-modal').setAttribute('aria-hidden', 'true');
        document.body.classList.remove('premium-modal-open')
    }

    function confirmMethod() {
        if (!selectedBills().length) {
            alert('Pilih minimal satu kartu tagihan terlebih dahulu.');
            closeMethodModal();
            return
        }
        if (activeMethod === 'bank_transfer') {
            const bank = document.querySelector('input[name="bank_destination"]:checked');
            const proof = document.getElementById('proof');
            if (!bank) {
                alert('Pilih bank tujuan transfer terlebih dahulu.');
                return
            }
            if (!proof.files || !proof.files.length) {
                alert('Unggah bukti transaksi terlebih dahulu.');
                return
            }
        }
        document.getElementById('payment-method').value = activeMethod;
        document.getElementById('cash-selected').style.display = activeMethod === 'cash' ? 'inline-block' : 'none';
        document.getElementById('bank-selected').style.display = activeMethod === 'bank_transfer' ? 'inline-block' : 'none';
        document.getElementById('summary-method').textContent = activeMethod === 'cash' ? 'Tunai' : 'Transfer bank';
        document.getElementById('selected-method-note').textContent = activeMethod === 'cash' ? 'Metode dipilih: Tunai. Bayar langsung kepada petugas pondok.' : 'Metode dipilih: Transfer bank. Pastikan bank dan bukti transaksi sudah benar.';
        document.getElementById('proof').required = activeMethod === 'bank_transfer';
        document.querySelectorAll('input[name="bank_destination"]').forEach(x => x.required = activeMethod === 'bank_transfer');
        closeMethodModal();
        calc();
    }
    document.getElementById('method-modal').addEventListener('click', e => {
        if (e.target.id === 'method-modal') closeMethodModal()
    });
    document.getElementById('pay-form').addEventListener('submit', e => {
        if (!selectedBills().length) {
            e.preventDefault();
            alert('Pilih minimal satu tagihan.');
            return
        }
        if (!document.getElementById('payment-method').value) {
            e.preventDefault();
            alert('Pilih metode pembayaran terlebih dahulu.');
            return
        }
        if (document.getElementById('payment-method').value === 'bank_transfer' && (!document.querySelector('input[name="bank_destination"]:checked') || !document.getElementById('proof').files.length)) {
            e.preventDefault();
            openMethodModal('bank_transfer');
        }
    });
    document.querySelectorAll('.copy-account').forEach(button => {
        button.addEventListener('click', async event => {
            event.preventDefault(); event.stopPropagation();
            const account = button.dataset.account || '';
            if (!account || account === 'Nomor rekening belum diatur admin') { alert('Nomor rekening belum diatur admin.'); return; }
            try {
                await navigator.clipboard.writeText(account);
                const original = button.textContent; button.textContent = '✓ Tersalin';
                setTimeout(() => button.textContent = original, 1800);
            } catch (error) {
                const field = document.createElement('textarea'); field.value = account; field.style.position = 'fixed'; field.style.opacity = '0'; document.body.appendChild(field); field.select();
                const copied = document.execCommand('copy'); field.remove(); alert(copied ? 'Nomor rekening berhasil disalin.' : 'Gagal menyalin. Silakan salin nomor rekening secara manual.');
            }
        });
    });
    calc();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>