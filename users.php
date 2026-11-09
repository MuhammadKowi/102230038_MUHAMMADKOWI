<?php
require __DIR__ . '/config.php';
require_role('admin');
$pageTitle = 'Kelola User';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        if ($name === '' || $username === '' || ($id === 0 && strlen($password) < 8) || ($password !== '' && strlen($password) < 8)) {
            flash('Nama dan username wajib diisi. Password minimal 8 karakter; saat mengubah akun boleh dikosongkan.', 'error');
            redirect('users.php' . ($id ? '?edit=' . $id : ''));
        }
        try {
            if ($id) {
                $q = $pdo->prepare('SELECT id,role FROM users WHERE id=?');
                $q->execute([$id]);
                $target = $q->fetch();
                if (!$target) throw new RuntimeException('Akun tidak ditemukan.');
                if ($password !== '') {
                    $q = $pdo->prepare('UPDATE users SET full_name=?,username=?,password_hash=? WHERE id=?');
                    $q->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT), $id]);
                } else {
                    $q = $pdo->prepare('UPDATE users SET full_name=?,username=? WHERE id=?');
                    $q->execute([$name, $username, $id]);
                }
            } else {
                $q = $pdo->prepare("INSERT INTO users(full_name,username,password_hash,role) VALUES(?,?,?,'admin')");
                $q->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT)]);
            }
            flash('Akun berhasil disimpan.');
        } catch (Throwable $e) {
            flash('Gagal menyimpan akun. Pastikan username belum digunakan.', 'error');
        }
        redirect('users.php');
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$_SESSION['user']['id']) {
            flash('Akun yang sedang digunakan tidak dapat dihapus.', 'error');
            redirect('users.php');
        }
        $q = $pdo->prepare('SELECT role FROM users WHERE id=?');
        $q->execute([$id]);
        $role = $q->fetchColumn();
        if ($role !== 'admin') {
            flash('Akun santri tidak dapat dihapus dari menu ini. Kelola akun santri melalui menu Data Santri agar data terkait tetap konsisten.', 'error');
            redirect('users.php');
        }
        $q = $pdo->prepare("DELETE FROM users WHERE id=? AND role='admin'");
        $q->execute([$id]);
        flash('Akun admin dihapus.');
        redirect('users.php');
    }
}
$edit = null;
if (isset($_GET['edit'])) {
    $q = $pdo->prepare('SELECT id,full_name,username,role FROM users WHERE id=?');
    $q->execute([(int)$_GET['edit']]);
    $edit = $q->fetch() ?: null;
}
$rows = $pdo->query("SELECT u.id,u.full_name,u.username,u.role,u.created_at,s.id AS student_id,s.student_no,s.class_level FROM users u LEFT JOIN students s ON s.user_id=u.id ORDER BY u.role,u.full_name")->fetchAll();
require __DIR__ . '/includes/header.php'; ?>
<section class="card">
  <div class="section-head"><div><h2>Kelola akun pengguna</h2><p>Tambah atau ubah akun admin. Akun santri tetap terhubung dengan data santri.</p></div><button class="btn" type="button" onclick="openUserModal()">＋ Tambah admin</button></div>
  <div class="pill"><?= count($rows) ?> akun terdaftar</div>
</section>
<div class="premium-modal-backdrop <?= $edit ? 'is-open' : '' ?>" id="userModal" role="presentation" aria-hidden="<?= $edit ? 'false' : 'true' ?>">
 <section class="premium-modal method-modal-rich" role="dialog" aria-modal="true" aria-labelledby="userModalTitle">
  <button class="premium-modal-close" type="button" onclick="closeUserModal()" aria-label="Tutup">×</button>
  <div class="premium-modal-icon" aria-hidden="true">♙</div><div class="premium-modal-kicker">MANAJEMEN AKUN</div>
  <h2 id="userModalTitle"><?= $edit ? 'Ubah akun pengguna' : 'Tambah akun admin' ?></h2><p>Lengkapi nama, username, dan password akun.</p>
  <form method="post" class="form-grid"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
   <div class="form-group full"><label>Nama lengkap</label><input class="input" name="full_name" required value="<?= e($edit['full_name'] ?? '') ?>"></div>
   <div class="form-group full"><label>Username</label><input class="input" name="username" required value="<?= e($edit['username'] ?? '') ?>"></div>
   <div class="form-group full"><label><?= $edit ? 'Password baru (opsional)' : 'Password awal admin' ?></label><input class="input" type="password" name="password" minlength="8" <?= $edit ? '' : 'required' ?>><span class="help"><?= $edit ? 'Kosongkan jika tidak ingin mengganti password.' : 'Minimal 8 karakter.' ?></span></div>
   <div class="form-group full premium-modal-actions"><button class="btn btn-light" type="button" onclick="closeUserModal()">Batal</button><button class="btn" type="submit"><?= $edit ? 'Simpan perubahan' : '＋ Tambah admin' ?></button></div>
  </form>
 </section>
</div>
<section class="card" style="margin-top:20px">
    <div class="section-head">
        <div>
            <h2>Daftar user</h2>
            <p>Akun admin dan santri dalam sistem.</p>
        </div><input class="input" style="max-width:270px" placeholder="Cari nama, username, NIS..." oninput="filterUsers(this.value)">
    </div>
    <div class="table-wrap">
        <table id="users-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Nomor induk / jenjang</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody><?php foreach ($rows as $r): ?><tr>
                        <td><strong><?= e($r['full_name']) ?></strong></td>
                        <td><?= e($r['username']) ?></td>
                        <td><span class="badge <?= $r['role'] === 'admin' ? 'paid' : 'neutral' ?>"><?= e(ucfirst($r['role'])) ?></span></td>
                        <td><?= $r['student_no'] ? e($r['student_no'] . ' · ' . $r['class_level']) : '—' ?></td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap"><a class="btn btn-light btn-small" href="<?= APP_BASE ?>/users.php?edit=<?= (int)$r['id'] ?>">Ubah akun</a><?php if ($r['role'] === 'admin' && (int)$r['id'] !== (int)$_SESSION['user']['id']): ?><form method="post" data-confirm="Hapus akun admin ini? Tindakan ini tidak dapat dibatalkan."><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-danger btn-small" type="submit">Hapus</button></form><?php endif; ?><?php if ($r['role'] === 'santri' && $r['student_id']): ?><a class="btn btn-light btn-small" href="<?= APP_BASE ?>/santri_tagihan.php?student_id=<?= (int)$r['student_id'] ?>">Lihat tagihan</a><?php endif; ?></div>
                        </td>
                    </tr><?php endforeach; ?><?php if (!$rows): ?><tr>
                        <td colspan="5" class="empty">Belum ada akun.</td>
                    </tr><?php endif; ?></tbody>
        </table>
    </div>
</section>
<script>
    function filterUsers(v) {
        document.querySelectorAll('#users-table tbody tr').forEach(r => r.style.display = r.innerText.toLowerCase().includes(v.toLowerCase()) ? '' : 'none')
    }
</script>
<script>
function openUserModal(){const m=document.getElementById('userModal');m.classList.add('is-open');m.setAttribute('aria-hidden','false');document.body.classList.add('premium-modal-open');}
function closeUserModal(){const m=document.getElementById('userModal');m.classList.remove('is-open');m.setAttribute('aria-hidden','true');document.body.classList.remove('premium-modal-open');}
document.getElementById('userModal').addEventListener('click',function(e){if(e.target===this)closeUserModal()});document.addEventListener('keydown',function(e){if(e.key==='Escape')closeUserModal()});
</script><?php require __DIR__ . '/includes/footer.php'; ?>