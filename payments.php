<?php
require __DIR__ . '/config.php'; require_role('admin');
$pageTitle='Pembayaran SPP';
if($_SERVER['REQUEST_METHOD']==='POST'){
 csrf_check();$id=(int)($_POST['id']??0);$action=$_POST['action']??'';
 if(in_array($action,['approve','reject'],true)){
  $status=$action==='approve'?'paid':'rejected';
  $q=$pdo->prepare("SELECT * FROM payments WHERE id=? AND method IN ('cash','bank_transfer') AND status='pending'");$q->execute([$id]);$p=$q->fetch();
  if(!$p){flash('Transaksi tidak ditemukan atau sudah diproses.','error');redirect('payments.php');}
  if($status==='paid'){
   $q=$pdo->prepare("SELECT COUNT(*) FROM payments WHERE student_id=? AND billing_month=? AND status='paid'");$q->execute([$p['student_id'],$p['billing_month']]);
   if((int)$q->fetchColumn()>0){flash('Bulan tersebut sudah lunas melalui transaksi lain. Periksa transaksi sebelum menyetujui.','error');redirect('payments.php');}
  }
  $q=$pdo->prepare('UPDATE payments SET status=?,verified_by=?,verified_at=NOW(),notes=? WHERE id=? AND status=\'pending\'');$q->execute([$status,$_SESSION['user']['id'],trim($_POST['notes']??''),$id]);
  flash($status==='paid'?'Pembayaran berhasil diverifikasi lunas.':'Pembayaran ditolak.','success');
 }
 redirect('payments.php');
}
$statusFilter=$_GET['status']??'all';$where='';$params=[];
if(in_array($statusFilter,['pending','paid','rejected','failed','expire'],true)){$where='WHERE p.status=?';$params[]=$statusFilter;}
$sql="SELECT p.*,u.full_name,s.student_no,fc.name category FROM payments p JOIN students s ON s.id=p.student_id JOIN users u ON u.id=s.user_id JOIN fee_categories fc ON fc.id=p.fee_category_id $where ORDER BY p.created_at DESC LIMIT 300";
$q=$pdo->prepare($sql);$q->execute($params);$rows=$q->fetchAll();
require __DIR__.'/includes/header.php';
?>
<section class="card"><div class="section-head"><div><h2>Daftar transaksi</h2><p>Verifikasi pembayaran tunai dan transfer; Midtrans diperbarui melalui notifikasi gateway.</p></div><form method="get"><select name="status" onchange="this.form.submit()"><option value="all">Semua status</option><?php foreach(['pending'=>'Menunggu verifikasi','paid'=>'Lunas','rejected'=>'Ditolak','failed'=>'Gagal','expire'=>'Kedaluwarsa'] as $k=>$v): ?><option value="<?= e($k) ?>" <?= $statusFilter===$k?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></form></div>
<div class="table-wrap"><table><thead><tr><th>Santri / Order</th><th>Periode</th><th>Metode</th><th>Total</th><th>Bukti</th><th>Status</th><th>Verifikasi</th><th>Kwitansi</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><strong><?= e($r['full_name']) ?></strong><div class="help"><?= e($r['student_no']) ?> · <?= e($r['order_id']) ?></div></td><td><?= e(month_label($r['billing_month'])) ?><div class="help"><?= e($r['category']) ?></div></td><td><?= e(['cash'=>'Tunai akademik','bank_transfer'=>'Transfer bank','midtrans'=>'Midtrans'][$r['method']]??$r['method']) ?></td><td><strong><?= rupiah($r['total_amount']) ?></strong></td><td><?php if($r['proof_path']): ?><a class="btn btn-light btn-small" target="_blank" rel="noopener" href="<?= APP_BASE ?>/<?= e($r['proof_path']) ?>">Lihat bukti ↗</a><?php else: ?><span class="help">Tidak ada</span><?php endif; ?></td><td><span class="badge <?= e($r['status']==='paid'?'paid':($r['status']==='pending'?'pending':$r['status'])) ?>"><?= e(ucfirst($r['status'])) ?></span></td><td><?php if($r['status']==='pending' && $r['method']!=='midtrans'): ?><form method="post" style="display:flex;gap:5px;align-items:center"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input class="input" name="notes" placeholder="Catatan (opsional)" style="width:125px;padding:7px"><button class="btn btn-small" name="action" value="approve">Setujui</button><button class="btn btn-danger btn-small" name="action" value="reject">Tolak</button></form><?php elseif($r['method']==='midtrans' && $r['status']==='pending'): ?><div class="help"><?= e($r['gateway_status']??'Status gateway belum diperiksa') ?></div><form method="post" action="<?= APP_BASE ?>/midtrans_sync.php" style="margin-top:6px;min-width:190px"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="order_id" value="<?= e($r['order_id']) ?>"><input class="input" name="transaction_id" placeholder="Transaction ID (opsional)" style="width:100%;padding:7px;margin-bottom:5px" title="Isi Transaction ID dari detail transaksi Dashboard Midtrans jika pencarian order_id menghasilkan 404"><button class="btn btn-light btn-small" type="submit">Periksa status Midtrans</button></form><?php elseif($r['method']==='midtrans'): ?><span class="help"><?= e($r['gateway_status']??'Status gateway') ?></span><?php else: ?><span class="help">—</span><?php endif; ?></td><td><a class="btn btn-light btn-small" target="_blank" href="<?= APP_BASE ?>/receipt.php?id=<?= (int)$r['id'] ?>">Cetak kwitansi</a></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="8" class="empty">Belum ada transaksi pada filter ini.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php require __DIR__.'/includes/footer.php'; ?>
