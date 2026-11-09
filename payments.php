<?php
require __DIR__ . '/config.php';
require_role('admin');
$pageTitle = 'Pembayaran SPP';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'cash_add') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $month = (string)($_POST['billing_month'] ?? '');
        if (preg_match('/^\d{4}-\d{2}$/', $month)) $month .= '-01';
        $type = (string)($_POST['payment_type'] ?? 'spp');
        $q = $pdo->prepare('SELECT s.id,s.class_level,fc.id category_id,fc.amount,fc.spp_amount FROM students s JOIN fee_categories fc ON fc.name=s.class_level AND fc.active=1 WHERE s.id=? AND s.active=1');
        $q->execute([$studentId]);
        $st = $q->fetch();
        $dt = DateTime::createFromFormat('!Y-m-d', $month);
        if (!$st || !$dt || $dt->format('Y-m-d') !== $month || $month > date('Y-m-01') || !in_array($type, ['spp', 'jenjang', 'combined'], true)) {
            flash('Data pembayaran tunai tidak valid.', 'error');
            redirect('payments.php');
        }
        $amtSpp = in_array($type, ['spp', 'combined'], true) ? (float)$st['spp_amount'] : 0;
        $amtJenjang = in_array($type, ['jenjang', 'combined'], true) ? (float)$st['amount'] : 0;
        $total = $amtSpp + $amtJenjang;
        $check = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE student_id=? AND billing_month=? AND status IN ('paid','pending') AND (payment_type=? OR payment_type='combined' OR ?='combined')");
        $check->execute([$studentId, $month, $type, $type]);
        if ($total <= 0 || (int)$check->fetchColumn() > 0) {
            flash('Nominal belum diatur atau tagihan periode tersebut sudah diproses.', 'error');
            redirect('payments.php');
        }
        $order = 'CASH-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));
        $q = $pdo->prepare("INSERT INTO payments(order_id,student_id,fee_category_id,billing_month,payment_type,category_amount,spp_amount,total_amount,method,status,notes,verified_by,verified_at) VALUES(?,?,?,?,?,?,?,?, 'cash','paid',?,?,NOW())");
        $q->execute([$order, $studentId, $st['category_id'], $month, $type, $amtJenjang, $amtSpp, $total, trim($_POST['notes'] ?? 'Pembayaran tunai diterima admin'), $_SESSION['user']['id']]);
        flash('Pembayaran tunai berhasil dicatat dan langsung berstatus lunas.');
        redirect('payments.php');
    }
    if (in_array($action, ['approve', 'reject'], true)) {
        $rejectReason = trim((string)($_POST['notes'] ?? ''));
        if ($action === 'reject' && $rejectReason === '') { flash('Isi alasan penolakan sebelum menolak pembayaran.', 'error'); redirect('payments.php'); }
        $status = $action === 'approve' ? 'paid' : 'rejected';
        $q = $pdo->prepare("SELECT * FROM payments WHERE id=? AND method IN ('cash','bank_transfer') AND status='pending'");
        $q->execute([$id]);
        $p = $q->fetch();
        if (!$p) {
            flash('Transaksi tidak ditemukan atau sudah diproses.', 'error');
            redirect('payments.php');
        }
        if ($status === 'paid') {
            // Tolak hanya jika tagihan yang sama sudah lunas. SPP Pondok dan biaya jenjang
            // pada bulan yang sama adalah dua tagihan berbeda dan boleh diverifikasi terpisah.
            $q = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE student_id=? AND billing_month=? AND status='paid' AND (payment_type=? OR payment_type='combined' OR ?='combined')");
            $q->execute([$p['student_id'], $p['billing_month'], $p['payment_type'], $p['payment_type']]);
            if ((int)$q->fetchColumn() > 0) {
                flash('Jenis tagihan ini sudah lunas melalui transaksi lain. Periksa riwayat transaksi sebelum menyetujui.', 'error');
                redirect('payments.php');
            }
        }
        $q = $pdo->prepare('UPDATE payments SET status=?,verified_by=?,verified_at=NOW(),notes=? WHERE id=? AND status=\'pending\'');
        $verifierId = (int)($_SESSION['user']['id'] ?? 0);
        if ($verifierId <= 0) { flash('Sesi pengguna tidak valid. Silakan masuk kembali.', 'error'); redirect('login.php'); }
        $q->execute([$status, $verifierId, trim($_POST['notes'] ?? ''), $id]);
        flash($status === 'paid' ? 'Pembayaran berhasil diverifikasi lunas.' : 'Pembayaran ditolak.', 'success');
    }
    redirect('payments.php');
}
$statusFilter = $_GET['status'] ?? 'all';
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$where = '';
$params = [];
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) || $from > $to) {
    $from = date('Y-m-01');
    $to = date('Y-m-d');
}
if ($statusFilter === 'pending' && !isset($_GET['from']) && !isset($_GET['to'])) {
    $where = 'WHERE p.status=?';
    $params = ['pending'];
} else {
    $where = 'WHERE DATE(p.created_at) BETWEEN ? AND ?';
    $params = [$from, $to];
    if (in_array($statusFilter, ['pending', 'paid', 'rejected', 'failed', 'expire'], true)) {
        $where .= ' AND p.status=?';
        $params[] = $statusFilter;
    }
}
$sql = "SELECT p.*,u.full_name,s.student_no,fc.name category,vu.full_name AS verifier_name FROM payments p JOIN students s ON s.id=p.student_id JOIN users u ON u.id=s.user_id JOIN fee_categories fc ON fc.id=p.fee_category_id LEFT JOIN users vu ON vu.id=p.verified_by $where ORDER BY p.created_at DESC LIMIT 300";
$q = $pdo->prepare($sql);
$q->execute($params);
$rows = $q->fetchAll();
$cashStudents = $pdo->query("SELECT s.id,s.student_no,s.class_level,u.full_name FROM students s JOIN users u ON u.id=s.user_id WHERE s.active=1 ORDER BY u.full_name")->fetchAll();
$cashLevels = $pdo->query('SELECT id,name,amount,spp_amount FROM fee_categories WHERE active=1 ORDER BY name')->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<section class="card">
    <div class="section-head">
        <div>
            <h2>Pembayaran & laporan transaksi</h2>
            <p>Catat pembayaran tunai, verifikasi transfer, dan pantau transaksi dari satu menu.</p>
        </div><div class="payment-head-actions"><a class="btn btn-light" href="<?= APP_BASE ?>/export_excel.php?<?= e(http_build_query(['from'=>$from,'to'=>$to,'status'=>$statusFilter])) ?>">▦ Unduh Excel</a><a class="btn btn-light" target="_blank" rel="noopener" href="<?= APP_BASE ?>/export_pdf.php?<?= e(http_build_query(['from'=>$from,'to'=>$to,'status'=>$statusFilter])) ?>">↓ Cetak PDF</a><button type="button" class="btn" onclick="openCashModal()">＋ Input pembayaran</button></div>
    </div>
    <form method="get" id="paymentFilter" class="report-filter" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;margin-bottom:16px">
        <div class="form-group"><label>Dari tanggal</label><input class="input" type="date" name="from" value="<?= e($from) ?>"></div>
        <div class="form-group"><label>Sampai tanggal</label><input class="input" type="date" name="to" value="<?= e($to) ?>"></div>
        <div class="form-group"><label>Status</label><select name="status" id="paymentStatus">
                <option value="all">Semua status</option><?php foreach (['pending' => 'Menunggu verifikasi', 'paid' => 'Lunas', 'rejected' => 'Ditolak', 'failed' => 'Gagal', 'expire' => 'Kedaluwarsa'] as $k => $v): ?><option value="<?= e($k) ?>" <?= $statusFilter === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
            </select></div>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Santri / Order</th>
                    <th>Periode</th>
                    <th>Metode</th>
                    <th>Total</th>
                    <th>Bukti</th>
                    <th>Status</th>
                    <th>Verifikasi</th>
                    <th>Kwitansi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?><tr>
                        <td><strong><?= e($r['full_name']) ?></strong>
                            <div class="help"><?= e($r['student_no']) ?> · <?= e($r['order_id']) ?></div>
                        </td>
                        <td><?= e(month_label($r['billing_month'])) ?><div class="help"><?= e($r['category']) ?></div><?php if (!empty($r['bank_destination'])): ?><div class="help">Rekening: <?= e($r['bank_destination']) ?></div><?php endif; ?></td>
                        <td><?= e(['cash' => 'Tiket tunai', 'bank_transfer' => 'Transfer bank'][$r['method']] ?? $r['method']) ?></td>
                        <td><strong><?= rupiah($r['total_amount']) ?></strong></td>
                        <td><?php if (!empty($r['proof_path'])): $proofUrl = preg_match('~^https?://~i', (string)$r['proof_path']) ? (string)$r['proof_path'] : APP_BASE . '/' . ltrim((string)$r['proof_path'], '/'); ?><button class="btn btn-light btn-small js-proof-open" type="button" data-proof-url="<?= e($proofUrl) ?>">Lihat bukti</button><?php else: ?><span class="help">Tidak ada</span><?php endif; ?></td>
                        <td><span class="badge <?= e($r['status'] === 'paid' ? 'paid' : ($r['status'] === 'pending' ? 'pending' : $r['status'])) ?>"><?= e(['paid' => 'Success', 'rejected' => 'Ditolak', 'pending' => 'Menunggu verifikasi', 'failed' => 'Gagal', 'expire' => 'Kedaluwarsa'][$r['status']] ?? ucfirst($r['status'])) ?></span><?php if ($r['status'] === 'rejected' && !empty($r['notes'])): ?><br/><div class="payment-rejection-note"><?= e($r['notes']) ?></div><?php endif; ?></td>
                        <td><?php if ($r['status'] === 'pending'): ?><form method="post" style="display:flex;gap:5px;align-items:center"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input class="input" name="notes" placeholder="Alasan jika ditolak" aria-label="Catatan atau alasan penolakan" style="width:150px;padding:7px"><button class="btn btn-small" name="action" value="approve">Verifikasi</button><button class="btn btn-danger btn-small" name="action" value="reject">Tolak</button></form><div class="help">Menunggu verifikasi</div><?php else: ?><strong><?= e(($r['verifier_name'] ?? '') ?: ((int)($r['verified_by'] ?? 0) > 0 ? 'Admin #' . (int)$r['verified_by'] : ($r['status'] === 'paid' ? 'Terverifikasi' : 'Diproses'))) ?></strong><?php if (!empty($r['verified_at'])): ?><div class="help"><?= e(date('d/m/Y H:i', strtotime($r['verified_at']))) ?></div><?php endif; ?><?php endif; ?></td>
                        <td><a class="btn btn-light btn-small" target="_blank" href="<?= APP_BASE ?>/receipt.php?id=<?= (int)$r['id'] ?>">Cetak kwitansi</a></td>
                    </tr><?php endforeach; ?>
                <?php if (!$rows): ?><tr>
                        <td colspan="8" class="empty">Belum ada transaksi pada filter ini.</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<div class="proof-modal-backdrop" id="adminProofModal" aria-hidden="true">
  <section class="proof-modal" role="dialog" aria-modal="true" aria-labelledby="adminProofTitle">
    <div class="proof-modal-head"><h2 id="adminProofTitle">Bukti pembayaran</h2><button type="button" class="proof-close" id="adminProofClose" aria-label="Tutup">×</button></div>
    <div class="proof-preview" id="adminProofPreview"><p>Pilih bukti untuk melihat pratinjau.</p></div>
    <div class="proof-modal-actions"><a class="btn" id="adminProofDownload" href="#" download>Unduh bukti</a><button class="btn btn-light" type="button" id="adminProofCloseBottom">Tutup</button></div>
  </section>
</div>
<script>
(function(){
 const modal=document.getElementById('adminProofModal');
 const preview=document.getElementById('adminProofPreview');
 const download=document.getElementById('adminProofDownload');
 function close(){modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true');document.body.classList.remove('modal-open');preview.replaceChildren();}
 function open(url){
   if(!url){preview.textContent='Alamat bukti pembayaran tidak tersedia.';modal.classList.add('is-open');modal.setAttribute('aria-hidden','false');return;}
   preview.replaceChildren();
   const clean=url.split('?')[0].split('#')[0];
   const ext=(clean.split('.').pop()||'').toLowerCase();
   if(['jpg','jpeg','png','webp','gif','bmp'].includes(ext)){
     const img=document.createElement('img');img.src=url;img.alt='Bukti pembayaran';img.onerror=()=>{preview.replaceChildren();const msg=document.createElement('p');msg.textContent='Bukti tidak dapat dimuat. Periksa apakah file masih tersedia dan alamat file benar.';const a=document.createElement('a');a.href=url;a.target='_blank';a.rel='noopener';a.textContent='Coba buka bukti di tab baru';preview.append(msg,a);};preview.appendChild(img);
   }else if(ext==='pdf'){
     const frame=document.createElement('iframe');frame.src=url;frame.title='Bukti pembayaran PDF';frame.setAttribute('loading','eager');preview.appendChild(frame);
   }else{
     const msg=document.createElement('p');msg.textContent='Pratinjau format ini tidak tersedia.';const a=document.createElement('a');a.href=url;a.target='_blank';a.rel='noopener';a.textContent='Buka bukti di tab baru';preview.append(msg,a);
   }
   download.href=url;download.setAttribute('download',clean.split('/').pop()||'bukti-pembayaran');
   modal.classList.add('is-open');modal.setAttribute('aria-hidden','false');document.body.classList.add('modal-open');
 }
 document.addEventListener('click',function(e){const btn=e.target.closest('.js-proof-open');if(btn){e.preventDefault();open(btn.getAttribute('data-proof-url'));}});
 document.getElementById('adminProofClose').addEventListener('click',close);document.getElementById('adminProofCloseBottom').addEventListener('click',close);
 modal.addEventListener('click',e=>{if(e.target===modal)close();});document.addEventListener('keydown',e=>{if(e.key==='Escape')close();});
})();
</script>
<div class="premium-modal-backdrop" id="cashModal" role="presentation" aria-hidden="true">
    <section class="premium-modal" role="dialog" aria-modal="true" aria-labelledby="cashTitle"><button class="premium-modal-close" type="button" onclick="closeCashModal()">×</button>
        <div class="premium-modal-kicker">KASIR ADMIN</div>
        <h2 id="cashTitle">Input pembayaran tunai</h2>
        <p>Transaksi yang dicatat di sini langsung tersimpan sebagai lunas.</p>
        <form method="post" class="form-grid"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="cash_add">
            <div class="form-group full admin-student-search"><label for="cashStudentSearch">Nama santri</label><input class="input" id="cashStudentSearch" type="text" autocomplete="off" placeholder="Ketik nama atau nomor induk santri..." aria-autocomplete="list" aria-controls="cashStudentSuggestions" aria-expanded="false" required><input type="hidden" name="student_id" id="cashStudentId" required><div id="cashStudentSuggestions" class="admin-student-suggestions" role="listbox" hidden></div><small class="help" id="cashStudentHint">Pilih nama santri dari daftar pencarian.</small></div>
            <div class="form-group"><label>Bulan tagihan</label><input class="input" type="month" name="billing_month" value="<?= date('Y-m') ?>" max="<?= date('Y-m') ?>" required></div>
            <div class="form-group"><label>Jenis pembayaran</label><select name="payment_type" required>
                    <option value="spp">SPP Pondok</option>
                    <option value="jenjang">SPP Jenjang</option>
                    <option value="combined">SPP + Jenjang</option>
                </select></div>
            <div class="form-group full"><label>Catatan (opsional)</label><input class="input" name="notes" placeholder="Contoh: diterima oleh bendahara"></div>
            <div class="form-group full"><button class="btn" type="submit">Simpan pembayaran tunai</button></div>
        </form>
    </section>
</div>
<style>.payment-head-actions{display:flex;gap:9px;align-items:center;flex-wrap:wrap}.btn-pdf{background:linear-gradient(135deg,#2f6fc4,#204b8b)!important;color:#fff!important;border:1px solid #245da8!important;font-weight:800;box-shadow:0 6px 16px rgba(36,93,168,.18)}.btn-pdf:hover{background:linear-gradient(135deg,#285fa8,#183d73)!important;color:#fff!important}.admin-student-search{position:relative}.admin-student-search .input{width:100%;min-height:44px;border-radius:10px}.admin-student-suggestions{position:absolute;z-index:100;top:calc(100% - 22px);left:0;right:0;max-height:240px;overflow:auto;background:#fff;border:1px solid #dce6f5;border-radius:12px;box-shadow:0 14px 35px rgba(31,62,105,.16);padding:5px}.admin-student-option{display:flex;width:100%;flex-direction:column;gap:4px;text-align:left;border:0;background:#fff;border-radius:8px;padding:10px 12px;cursor:pointer;color:#203a5f}.admin-student-option:hover{background:#edf4ff}.admin-student-option strong{font-size:13px}.admin-student-option small{font-size:11px;color:#6b7f9d}
</style>
<script>
    (()=>{const f=document.getElementById("paymentFilter"),s=document.getElementById("paymentStatus");if(f){if(s)s.addEventListener("change",()=>f.requestSubmit());f.querySelectorAll('input[type="date"]').forEach(el=>el.addEventListener("change",()=>{if(f.checkValidity())f.requestSubmit()}));}const input=document.getElementById('cashStudentSearch'),hidden=document.getElementById('cashStudentId'),list=document.getElementById('cashStudentSuggestions'),hint=document.getElementById('cashStudentHint');if(!input)return;let timer;input.addEventListener('input',()=>{hidden.value='';hint.textContent='Pilih nama santri dari daftar pencarian.';const q=input.value.trim();clearTimeout(timer);if(q.length<2){list.hidden=true;input.setCustomValidity('Pilih nama santri dari daftar pencarian.');return}timer=setTimeout(async()=>{try{const r=await fetch('<?= APP_BASE ?>/login.php?lookup_santri='+encodeURIComponent(q),{headers:{Accept:'application/json'}});const items=await r.json();list.replaceChildren();if(!items.length){const empty=document.createElement('div');empty.className='admin-student-option';empty.textContent='Nama santri tidak ditemukan';list.append(empty)}else items.forEach(item=>{const b=document.createElement('button');b.type='button';b.className='admin-student-option';const strong=document.createElement('strong');strong.textContent=item.full_name;const small=document.createElement('small');small.textContent='No. induk: '+item.student_no;b.append(strong,small);b.addEventListener('click',()=>{input.value=item.full_name;hidden.value=item.id;input.setCustomValidity('');hint.textContent='Santri dipilih: '+item.full_name+' · '+item.student_no;list.hidden=true});list.append(b)});list.hidden=false;input.setAttribute('aria-expanded','true')}catch(e){list.hidden=true}},180)});input.addEventListener('change',()=>{if(!hidden.value)input.setCustomValidity('Pilih nama santri dari daftar pencarian.')});document.addEventListener('click',e=>{if(!e.target.closest('.admin-student-search'))list.hidden=true});const modal=document.getElementById('cashModal');modal.querySelector('form').addEventListener('submit',e=>{if(!hidden.value){e.preventDefault();input.setCustomValidity('Pilih nama santri dari daftar pencarian.');input.reportValidity()}})})();
    function openCashModal() {
        let m = document.getElementById('cashModal');
        m.classList.add('is-open');
        m.setAttribute('aria-hidden', 'false');
        document.body.classList.add('premium-modal-open')
    }

    function closeCashModal() {
        let m = document.getElementById('cashModal');
        m.classList.remove('is-open');
        m.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('premium-modal-open')
    }
    document.querySelectorAll('form').forEach(form=>{const reject=form.querySelector('button[name="action"][value="reject"]');if(reject){reject.addEventListener('click',e=>{const notes=form.querySelector('[name="notes"]');if(notes&&!notes.value.trim()){e.preventDefault();notes.required=true;notes.setCustomValidity('Isi alasan penolakan terlebih dahulu.');notes.reportValidity();notes.addEventListener('input',()=>notes.setCustomValidity(''),{once:true});}});}});
    document.getElementById('cashModal').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeCashModal()
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeCashModal()
    });
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
