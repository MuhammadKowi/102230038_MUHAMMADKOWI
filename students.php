<?php
require __DIR__ . '/config.php';
require_role('admin');
$pageTitle = 'Data Santri';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if (in_array($action, ['add', 'update'], true)) {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $studentNo = trim($_POST['student_no'] ?? '');
        $level = trim($_POST['class_level'] ?? '');

        if ($name === '' || $username === '' || $studentNo === '' || $level === '' || ($action === 'add' && strlen($password) < 8) || ($action === 'update' && $password !== '' && strlen($password) < 8)) {
            flash('Lengkapi data wajib. Password minimal 8 karakter; saat mengubah data, password boleh dikosongkan jika tidak ingin menggantinya.', 'error');
            redirect('students.php' . ($action === 'update' && $id ? '?edit=' . $id : ''));
        }

        try {
            $pdo->beginTransaction();
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, full_name) VALUES (?, ?, 'santri', ?)");
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $name]);
                $userId = (int)$pdo->lastInsertId();
                $stmt = $pdo->prepare('INSERT INTO students (user_id, student_no, class_level) VALUES (?, ?, ?)');
                $stmt->execute([$userId, $studentNo, $level]);
                $message = 'Data santri berhasil ditambahkan.';
            } else {
                $stmt = $pdo->prepare('SELECT user_id FROM students WHERE id = ?');
                $stmt->execute([$id]);
                $userId = (int)$stmt->fetchColumn();
                if (!$userId) {
                    throw new RuntimeException('Data santri tidak ditemukan.');
                }
                if ($password !== '') {
                    $stmt = $pdo->prepare('UPDATE users SET username = ?, full_name = ?, password_hash = ? WHERE id = ?');
                    $stmt->execute([$username, $name, password_hash($password, PASSWORD_DEFAULT), $userId]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET username = ?, full_name = ? WHERE id = ?');
                    $stmt->execute([$username, $name, $userId]);
                }
                $stmt = $pdo->prepare('UPDATE students SET student_no = ?, class_level = ? WHERE id = ?');
                $stmt->execute([$studentNo, $level, $id]);
                $message = 'Data santri berhasil diperbarui.';
            }
            $pdo->commit();
            flash($message);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('Gagal menyimpan data. Pastikan username dan nomor induk belum digunakan.', 'error');
        }
        redirect('students.php');
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE students SET active = 1 - active WHERE id = ?');
        $stmt->execute([$id]);
        flash('Status santri diperbarui.');
        redirect('students.php');
    }
}

$rows = $pdo->query('SELECT s.*, u.full_name, u.username FROM students s JOIN users u ON u.id = s.user_id ORDER BY s.created_at DESC')->fetchAll();
$editId = (int)($_GET['edit'] ?? 0);
$editStudent = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT s.*, u.full_name, u.username FROM students s JOIN users u ON u.id = s.user_id WHERE s.id = ?');
    $stmt->execute([$editId]);
    $editStudent = $stmt->fetch() ?: null;
}
require __DIR__ . '/includes/header.php';
?>
<div class="grid two-col">
    <section class="card">
        <div class="section-head"><div><h2><?= $editStudent ? 'Ubah data santri' : 'Tambah santri' ?></h2><p><?= $editStudent ? 'Perbarui identitas, akun, atau jenjang santri.' : 'Akun login dibuat bersamaan dengan data santri.' ?></p></div>
            <?php if ($editStudent): ?><a class="btn btn-light btn-small" href="<?= APP_BASE ?>/students.php">Batal ubah</a><?php endif; ?>
        </div>
        <form method="post" class="form-grid">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="<?= $editStudent ? 'update' : 'add' ?>">
            <?php if ($editStudent): ?><input type="hidden" name="id" value="<?= (int)$editStudent['id'] ?>"><?php endif; ?>
            <div class="form-group full"><label>Nama lengkap</label><input class="input" name="full_name" required value="<?= e($editStudent['full_name'] ?? '') ?>"></div>
            <div class="form-group"><label>Nomor induk santri</label><input class="input" name="student_no" required value="<?= e($editStudent['student_no'] ?? '') ?>"></div>
            <div class="form-group"><label>Jenjang</label><select name="class_level" required><?php foreach (['Ula', 'Wustha', 'Ulya'] as $level): ?><option value="<?= e($level) ?>" <?= ($editStudent['class_level'] ?? 'Ula') === $level ? 'selected' : '' ?>><?= e($level) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Username</label><input class="input" name="username" required value="<?= e($editStudent['username'] ?? '') ?>"></div>
            <div class="form-group"><label><?= $editStudent ? 'Password baru (opsional)' : 'Password awal' ?></label><input class="input" name="password" type="password" minlength="8" <?= $editStudent ? '' : 'required' ?>><span class="help"><?= $editStudent ? 'Kosongkan jika password tidak ingin diubah.' : 'Minimal 8 karakter.' ?></span></div>
            <div class="form-group full"><button class="btn" type="submit"><?= $editStudent ? 'Simpan perubahan' : '＋ Tambah santri' ?></button></div>
        </form>
    </section>
    <section class="card"><h2>Pengelolaan santri</h2><p class="help" style="line-height:1.8">Perubahan nama dan akun akan diperbarui pada akun login santri. Username dan nomor induk harus unik. Password disimpan dalam bentuk hash dan tidak dapat ditampilkan kembali.</p><div class="pill">Santri aktif: <?= count(array_filter($rows, fn($row) => (int)$row['active'] === 1)) ?></div></section>
</div>
<section class="card" style="margin-top:20px">
    <div class="section-head"><div><h2>Daftar santri</h2><p>Kelola identitas, akun, dan status santri.</p></div><input class="input" style="max-width:240px" placeholder="Cari nama / nomor..." oninput="filterRows(this.value,'student-table')"></div>
    <div class="table-wrap"><table id="student-table"><thead><tr><th>Santri</th><th>Nomor induk</th><th>Jenjang</th><th>Username</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach ($rows as $row): ?><tr>
        <td><div class="person"><div class="person-avatar"><?= e(strtoupper(substr($row['full_name'], 0, 1))) ?></div><strong><?= e($row['full_name']) ?></strong></div></td>
        <td><?= e($row['student_no']) ?></td><td><?= e($row['class_level']) ?></td><td><?= e($row['username']) ?></td>
        <td><span class="badge <?= (int)$row['active'] === 1 ? 'paid' : 'neutral' ?>"><?= (int)$row['active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
        <td><div style="display:flex;gap:6px;align-items:center"><a class="btn btn-light btn-small" href="<?= APP_BASE ?>/students.php?edit=<?= (int)$row['id'] ?>">✎ Ubah</a><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn btn-small <?= (int)$row['active'] === 1 ? 'btn-danger' : 'btn-light' ?>" type="submit"><?= (int)$row['active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></div></td>
    </tr><?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6" class="empty">Belum ada data santri.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<script>function filterRows(value, id) { document.querySelectorAll('#' + id + ' tbody tr').forEach(row => row.style.display = row.innerText.toLowerCase().includes(value.toLowerCase()) ? '' : 'none'); }</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
