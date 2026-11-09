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
                    <th>Jenis pembayaran</th>
                    <th>Kategori/jenjang</th>
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
                        <td><?= e(['spp' => 'SPP Bulanan', 'jenjang' => 'Biaya Jenjang', 'combined' => 'SPP + Jenjang'][$r['payment_type'] ?? 'combined'] ?? 'Pembayaran') ?></td>
                        <td><?= e($r['category']) ?></td>
                        <td><?= e(['cash' => 'Tiket pembayaran tunai', 'bank_transfer' => 'Transfer bank'][$r['method']] ?? $r['method']) ?></td>
                        <td><strong><?= rupiah($r['total_amount']) ?></strong>
                            <div class="help"><?= e(['spp' => 'SPP', 'jenjang' => 'Biaya jenjang', 'combined' => 'SPP + biaya jenjang'][$r['payment_type'] ?? 'combined'] ?? 'Pembayaran') ?></div>
                        </td>
                        <td><span class="badge <?= e($r['status'] === 'paid' ? 'paid' : ($r['status'] === 'pending' ? 'pending' : $r['status'])) ?>"><?= e(['paid' => 'Success', 'rejected' => 'Ditolak', 'pending' => 'Menunggu verifikasi', 'failed' => 'Gagal', 'expire' => 'Kedaluwarsa'][$r['status']] ?? ucfirst($r['status'])) ?></span><?php if ($r['status'] === 'rejected' && $r['notes']): ?><br/><div class="payment-rejection-note"><?= e($r['notes']) ?></div><?php elseif ($r['status'] !== 'rejected' && $r['notes']): ?><div class="help"><?= e($r['notes']) ?></div><?php endif; ?></td>
                        <td><?php if (!empty($r['proof_path'])): $proofUrl = preg_match('~^https?://~i', (string)$r['proof_path']) ? (string)$r['proof_path'] : APP_BASE . '/' . ltrim((string)$r['proof_path'], '/'); ?><button class="btn btn-light btn-small js-student-proof-open" type="button" data-proof-url="<?= e($proofUrl) ?>">Lihat bukti</button><?php else: ?><span class="help">—</span><?php endif; ?>
                        </td>
                    </tr><?php endforeach; ?>
                <?php if (!$rows): ?><tr>
                        <td colspan="8" class="empty">
                            <div class="empty-icon">◷</div><strong>Belum ada riwayat pembayaran</strong>
                            <p>Pengajuan SPP Anda akan tampil di halaman ini.</p>
                        </td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<div class="proof-modal-backdrop student-proof-backdrop" id="proofModal" aria-hidden="true"><section class="proof-modal student-proof-modal" role="dialog" aria-modal="true" aria-labelledby="proofTitle"><div class="proof-modal-head"><h2 id="proofTitle">Bukti pembayaran</h2><button type="button" class="proof-close" id="proofClose" aria-label="Tutup">×</button></div><div class="proof-preview" id="proofPreview"></div><div class="proof-modal-actions"><a class="btn" id="proofDownload" href="#" download>Unduh bukti</a><button class="btn btn-light" type="button" id="proofCloseBottom">Tutup</button></div></section></div>
<script>
(function(){const m=document.getElementById('proofModal'),p=document.getElementById('proofPreview'),d=document.getElementById('proofDownload');
function close(){m.classList.remove('is-open');m.setAttribute('aria-hidden','true');document.body.classList.remove('modal-open');p.replaceChildren();}
function open(url){p.replaceChildren();if(!url){p.textContent='Alamat bukti tidak tersedia.';}else{const clean=url.split('?')[0].split('#')[0],ext=(clean.split('.').pop()||'').toLowerCase();if(['jpg','jpeg','png','webp','gif','bmp'].includes(ext)){const img=document.createElement('img');img.src=url;img.alt='Bukti pembayaran';img.onerror=()=>{p.replaceChildren();const msg=document.createElement('p');msg.textContent='Bukti tidak dapat dimuat. Periksa file bukti pembayaran.';const a=document.createElement('a');a.href=url;a.target='_blank';a.rel='noopener';a.textContent='Buka bukti di tab baru';p.append(msg,a);};p.appendChild(img);}else if(ext==='pdf'){const frame=document.createElement('iframe');frame.src=url;frame.title='Bukti pembayaran PDF';p.appendChild(frame);}else{const msg=document.createElement('p');msg.textContent='Pratinjau tidak tersedia untuk format ini.';const a=document.createElement('a');a.href=url;a.target='_blank';a.rel='noopener';a.textContent='Buka bukti di tab baru';p.append(msg,a);}d.href=url;d.setAttribute('download',clean.split('/').pop()||'bukti-pembayaran');}m.classList.add('is-open');m.setAttribute('aria-hidden','false');document.body.classList.add('modal-open');}
document.addEventListener('click',e=>{const b=e.target.closest('.js-student-proof-open');if(b){e.preventDefault();open(b.getAttribute('data-proof-url'));}});document.getElementById('proofClose').addEventListener('click',close);document.getElementById('proofCloseBottom').addEventListener('click',close);m.addEventListener('click',e=>{if(e.target===m)close();});document.addEventListener('keydown',e=>{if(e.key==='Escape')close();});})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>