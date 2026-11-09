<?php
require __DIR__ . '/config.php';
require_role('admin');
$from=$_GET['from']??date('Y-m-01'); $to=$_GET['to']??date('Y-m-d'); $status=$_GET['status']??'all';
$valid=static function($v){$d=DateTime::createFromFormat('!Y-m-d',(string)$v);return $d&&$d->format('Y-m-d')===$v;};
if(!$valid($from)||!$valid($to)||$from>$to){$from=date('Y-m-01');$to=date('Y-m-d');}
if(!in_array($status,['all','pending','paid','rejected','failed','expire'],true))$status='all';
$sql="SELECT p.*,u.full_name,s.student_no,s.class_level,fc.name category FROM payments p JOIN students s ON s.id=p.student_id JOIN users u ON u.id=s.user_id JOIN fee_categories fc ON fc.id=p.fee_category_id WHERE DATE(p.created_at) BETWEEN ? AND ?";
$params=[$from,$to]; if($status!=='all'){$sql.=' AND p.status=?';$params[]=$status;}$sql.=' ORDER BY p.created_at DESC';
$q=$pdo->prepare($sql);$q->execute($params);$rows=$q->fetchAll();
function x($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="laporan-transaksi-pembayaran-santri-'.date('Ymd-His').'.xls"');
echo "\xEF\xBB\xBF";
?>
<html><head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;color:#20352b}table{border-collapse:collapse;width:100%}th,td{border:1px solid #b8c9bf;padding:8px 10px;mso-number-format:"\@";}th{background:#116b49;color:#fff;font-weight:bold;text-align:center} .title{font-size:16pt;font-weight:bold;background:#e7f5ec;color:#115b3e;text-align:center;padding:14px;border:1px solid #9bb9a7}.meta{background:#f4f8f5;font-weight:bold}.money{mso-number-format:"\#\,\#\#0"} </style></head><body>
<table><tr><td class="title" colspan="10">Laporan Transaksi Pembayaran Santri</td></tr><tr><td class="meta" colspan="10"><?=x(app_school_name())?> | Periode <?=x(date('d/m/Y',strtotime($from)))?> - <?=x(date('d/m/Y',strtotime($to)))?> | Status: <?=x($status)?></td></tr>
<tr><?php foreach(['No.','Tanggal Transaksi','Nama Santri','Nomor Induk','Jenjang','Periode Tagihan','Jenis Pembayaran','Metode','Status','Nominal'] as $h):?><th><?=x($h)?></th><?php endforeach;?></tr>
<?php foreach($rows as $i=>$r):?><tr><td><?= $i+1 ?></td><td><?=x(date('d/m/Y H:i',strtotime($r['created_at'])))?></td><td><?=x($r['full_name'])?></td><td><?=x($r['student_no'])?></td><td><?=x($r['class_level'])?></td><td><?=x(month_label($r['billing_month']))?></td><td><?=x(['spp'=>'SPP Bulanan','jenjang'=>'Biaya Jenjang','combined'=>'SPP + Jenjang'][$r['payment_type']]??'Pembayaran')?></td><td><?=x(['cash'=>'Tunai','bank_transfer'=>'Transfer Bank'][$r['method']]??$r['method'])?></td><td><?=x($r['status']==='paid'?'Success':ucfirst($r['status']))?></td><td class="money"><?=x($r['total_amount'])?></td></tr><?php endforeach;?>
<?php if(!$rows):?><tr><td colspan="10">Tidak ada transaksi pada periode yang dipilih.</td></tr><?php endif;?></table></body></html>