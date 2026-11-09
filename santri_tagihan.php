<?php
require __DIR__ . '/config.php';
require_role('admin');
$pageTitle = 'Cari Santri & Tagihan';
$term = trim($_GET['q'] ?? '');
$selectedId = (int)($_GET['student_id'] ?? 0);
$students = [];
if ($term !== '') {
  $like = '%' . $term . '%';
  $q = $pdo->prepare('SELECT s.id,s.student_no,s.class_level,s.active,u.full_name,u.username FROM students s JOIN users u ON u.id=s.user_id WHERE s.student_no LIKE ? OR s.class_level LIKE ? OR u.full_name LIKE ? OR u.username LIKE ? ORDER BY u.full_name LIMIT 100');
  $q->execute([$like, $like, $like, $like]);
  $students = $q->fetchAll();
} elseif ($selectedId) {
  $q = $pdo->prepare('SELECT s.id,s.student_no,s.class_level,s.active,u.full_name,u.username FROM students s JOIN users u ON u.id=s.user_id WHERE s.id=?');
  $q->execute([$selectedId]);
  $one = $q->fetch();
  if ($one) $students = [$one];
}
$student = null;
$payments = [];
$dueItems = [];
$summary = ['paid' => 0, 'pending' => 0, 'other' => 0, 'paid_total' => 0, 'due_total' => 0];
if ($selectedId) {
  foreach ($students as $s) {
    if ((int)$s['id'] === $selectedId) {
      $student = $s;
      break;
    }
  }
  if (!$student) {
    $q = $pdo->prepare('SELECT s.id,s.student_no,s.class_level,s.active,u.full_name,u.username FROM students s JOIN users u ON u.id=s.user_id WHERE s.id=?');
    $q->execute([$selectedId]);
    $student = $q->fetch() ?: null;
  }
  if ($student) {
    $q = $pdo->prepare('SELECT p.*,fc.name category FROM payments p JOIN fee_categories fc ON fc.id=p.fee_category_id WHERE p.student_id=? ORDER BY p.billing_month DESC,p.created_at DESC');
    $q->execute([$selectedId]);
    $payments = $q->fetchAll();
    foreach ($payments as $p) {
      if ($p['status'] === 'paid') {
        $summary['paid']++;
        $summary['paid_total'] += (float)$p['total_amount'];
      } elseif ($p['status'] === 'pending') {
        $summary['pending']++;
        $summary['due_total'] += (float)$p['total_amount'];
      } else {
        $summary['other']++;
      }
    }
    // Buat daftar kewajiban per bulan untuk dua jenis tagihan, termasuk bulan yang belum pernah memiliki transaksi.
    $q = $pdo->prepare('SELECT id,name,amount,spp_amount FROM fee_categories WHERE name=? LIMIT 1');
    $q->execute([$student['class_level']]);
    $fee = $q->fetch();
    if ($fee) {
      $paidRows = $pdo->prepare("SELECT billing_month,payment_type,status FROM payments WHERE student_id=? AND billing_month BETWEEN ? AND ? AND status IN ('paid','pending') ORDER BY created_at DESC");
      $from = (new DateTime('first day of this month'))->modify('-23 months')->format('Y-m-01');
      $to = date('Y-m-01');
      $paidRows->execute([$selectedId, $from, $to]);
      $monthStatus = [];
      foreach ($paidRows->fetchAll() as $pr) {
        $m = $pr['billing_month'];
        $type = $pr['payment_type'] ?? 'combined';
        if (!isset($monthStatus[$m])) $monthStatus[$m] = ['spp' => 'unpaid', 'jenjang' => 'unpaid'];
        $types = $type === 'combined' ? ['spp', 'jenjang'] : [$type];
        foreach ($types as $t) {
          if (!in_array($t, ['spp', 'jenjang'], true)) continue;
          if ($pr['status'] === 'paid' || $monthStatus[$m][$t] !== 'paid') $monthStatus[$m][$t] = $pr['status'];
        }
      }
      $cursor = new DateTime($from);
      $end = new DateTime($to);
      while ($cursor <= $end) {
        $m = $cursor->format('Y-m-01');
        $st = $monthStatus[$m] ?? ['spp' => 'unpaid', 'jenjang' => 'unpaid'];
        foreach (['spp' => 'SPP', 'jenjang' => 'Biaya Jenjang'] as $type => $label) {
          $status = $st[$type];
          $amount = $type === 'spp' ? (float)$fee['spp_amount'] : (float)$fee['amount'];
          $dueItems[] = ['month' => $m, 'type' => $type, 'label' => $label, 'amount' => $amount, 'status' => $status];
        }
        $cursor->modify('+1 month');
      }
      usort($dueItems, function ($a, $b) {
        return strcmp($b['month'], $a['month']) ?: strcmp($a['type'], $b['type']);
      });
    }
  }
}
require __DIR__ . '/includes/header.php'; ?>
<section class="card">
  <div class="section-head">
    <div>
      <h2>Pencarian akun santri</h2>
      <p>Cari berdasarkan nama, username, nomor induk, atau jenjang untuk membuka rincian tagihan.</p>
    </div>
  </div>
  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap"><input class="input" name="q" value="<?= e($term) ?>" placeholder="Nama / username / nomor induk / jenjang" style="flex:1;min-width:230px"><button class="btn" type="submit">Cari santri</button><a class="btn btn-light" href="<?= APP_BASE ?>/santri_tagihan.php">Reset</a></form>
  <?php if ($term !== ''): ?><div class="table-wrap" style="margin-top:16px">
      <table>
        <thead>
          <tr>
            <th>Nama santri</th>
            <th>Nomor induk</th>
            <th>Jenjang</th>
            <th>Username</th>
            <th>Status</th>
            <th>Tagihan</th>
          </tr>
        </thead>
        <tbody><?php foreach ($students as $s): ?><tr>
              <td><strong><?= e($s['full_name']) ?></strong></td>
              <td><?= e($s['student_no']) ?></td>
              <td><?= e($s['class_level']) ?></td>
              <td><?= e($s['username']) ?></td>
              <td><span class="badge <?= $s['active'] ? 'paid' : 'neutral' ?>"><?= $s['active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
              <td><a class="btn btn-small" href="<?= APP_BASE ?>/santri_tagihan.php?student_id=<?= (int)$s['id'] ?>">Lihat tagihan</a></td>
            </tr><?php endforeach; ?><?php if (!$students): ?><tr>
              <td colspan="6" class="empty">Santri tidak ditemukan.</td>
            </tr><?php endif; ?></tbody>
      </table>
    </div><?php endif; ?>
</section>
<?php if ($student): ?><section class="card" style="margin-top:20px">
    <div class="section-head">
      <div>
        <h2>Rincian tagihan santri</h2>
        <p><?= e($student['full_name']) ?> · <?= e($student['student_no']) ?> · Jenjang <?= e($student['class_level']) ?> · Username <?= e($student['username']) ?></p>
      </div><a class="btn btn-light btn-small" href="<?= APP_BASE ?>/santri_tagihan.php">Cari santri lain</a>
    </div>
    <div class="grid three-col" style="margin-bottom:18px">
      <div class="stat-card"><span>Total transaksi lunas </span><strong><?= (int)$summary['paid'] ?></strong><small> <?= rupiah($summary['paid_total']) ?></small></div>
      <div class="stat-card"><span>Menunggu pembayaran </span><strong><?= (int)$summary['pending'] ?></strong><small> <?= rupiah($summary['due_total']) ?></small></div>
      <div class="stat-card"><span>Transaksi status lain </span><strong><?= (int)$summary['other'] ?></strong><small> Ditolak, gagal, atau kedaluwarsa</small></div>
    </div>
    <section class="card" style="margin:0 0 20px">
      <div class="section-head">
        <div>
          <h2>Rincian tunggakan per bulan</h2>
          <p>SPP dan biaya jenjang dihitung terpisah. Daftar mencakup 24 bulan terakhir sampai bulan berjalan, termasuk bulan yang belum pernah dibuatkan transaksi.</p>
        </div>
      </div>
      <div class="month-grid payment-month-grid admin-billing-month-grid">
        <?php foreach ($dueItems as $d):
          $isSpp = $d['type'] === 'spp';
          $statusText = $d['status'] === 'paid' ? '✓ Sudah lunas' : ($d['status'] === 'pending' ? '◷ Menunggu verifikasi' : 'Tertunggak');
          $cardClass = $d['status'] === 'unpaid' ? 'is-unpaid' : 'is-disabled';
        ?>
          <div class="billing-card <?= $cardClass ?>" data-type="<?= e($d['type']) ?>" aria-label="<?= e(month_label($d['month']) . ' - ' . $d['label'] . ' - ' . $statusText) ?>">
            <span class="billing-card-icon"><?= $isSpp ? '▦' : '◇' ?></span>
            <span class="billing-card-copy">
              <strong><?= e(month_label($d['month'])) ?> · <?= e($isSpp ? 'SPP Pondok' : 'SPP (' . $student['class_level'] . ')') ?></strong>
              <small><?= rupiah($d['amount']) ?><?= $isSpp ? ' / bulan' : '' ?></small>
              <small><?= e($statusText) ?></small>
            </span>
          </div>
        <?php endforeach; ?>
        <?php if (!$dueItems): ?>
          <div class="empty" style="grid-column:1/-1">Nominal jenjang untuk santri ini belum ditemukan. Periksa kesesuaian nama jenjang pada data santri dan menu Nominal Jenjang.</div>
        <?php endif; ?>
      </div>
      <p class="help" style="margin-top:12px">Catatan: nominal menggunakan tarif yang saat ini terpasang pada menu Nominal SPP dan Nominal Jenjang. Tagihan yang sudah lunas berdasarkan transaksi tetap ditandai lunas.</p>
    </section>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Periode tagihan</th>
            <th>Jenis/jenjang</th>
            <th>Nominal jenjang</th>
            <th>SPP</th>
            <th>Total</th>
            <th>Metode</th>
            <th>Status</th>
            <th>Order ID</th>
          </tr>
        </thead>
        <tbody><?php foreach ($payments as $p): ?><tr>
              <td><?= e(month_label($p['billing_month'])) ?><div class="help"><?= e(date('d/m/Y', strtotime($p['created_at']))) ?></div>
              </td>
              <td><?= e($p['category']) ?></td>
              <td><?= rupiah($p['category_amount']) ?></td>
              <td><?= rupiah($p['spp_amount']) ?></td>
              <td><strong><?= rupiah($p['total_amount']) ?></strong></td>
              <td><?= e(['cash' => 'Tiket tunai', 'bank_transfer' => 'Transfer bank'][$p['method']] ?? $p['method']) ?></td>
              <td><span class="badge <?= e($p['status'] === 'paid' ? 'paid' : ($p['status'] === 'pending' ? 'pending' : 'neutral')) ?>"><?= e(ucfirst($p['status'])) ?></span></td>
              <td><?= e($p['order_id']) ?></td>
            </tr><?php endforeach; ?><?php if (!$payments): ?><tr>
              <td colspan="8" class="empty">Belum ada transaksi/tagihan tercatat untuk santri ini.</td>
            </tr><?php endif; ?></tbody>
      </table>
    </div>
    <p class="help" style="margin-top:12px">Riwayat transaksi di bawah ini menampilkan pembayaran yang pernah diajukan. Untuk daftar bulan tertunggak, gunakan tabel Rincian tunggakan per bulan di atas.</p>
  </section><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>