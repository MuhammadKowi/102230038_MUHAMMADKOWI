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
        $username = trim($_POST['full_name'] ?? '');
        $password = (string)($_POST['password'] ?? '1234567890');
        $studentNo = trim($_POST['student_no'] ?? '');
        $level = trim($_POST['class_level'] ?? '');
        $levelCheck = $pdo->prepare('SELECT COUNT(*) FROM fee_categories WHERE name=? AND active=1');
        $levelCheck->execute([$level]);
        $validLevel = (int)$levelCheck->fetchColumn() > 0;

        if ($name === '' || $username === '' || $studentNo === '' || $level === '' || !$validLevel || ($action === 'update' && $password !== '' && strlen($password) < 8)) {
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

$levels = $pdo->query('SELECT name FROM fee_categories WHERE active=1 ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
$search = trim($_GET['q'] ?? '');
$levelFilter = trim($_GET['level'] ?? '');
if ($search !== '' || $levelFilter !== '') {
    $like = '%' . $search . '%';
    $sql = 'SELECT s.*, u.full_name, u.username FROM students s JOIN users u ON u.id=s.user_id WHERE 1=1'; $params = [];
    if ($search !== '') { $like = '%' . $search . '%'; $sql .= ' AND (u.full_name LIKE ? OR s.student_no LIKE ? OR s.class_level LIKE ? OR u.username LIKE ?)'; array_push($params, $like, $like, $like, $like); }
    if ($levelFilter !== '' && in_array($levelFilter, $levels, true)) { $sql .= ' AND s.class_level = ?'; $params[] = $levelFilter; }
    $q = $pdo->prepare($sql . ' ORDER BY s.created_at DESC'); $q->execute($params);
    $rows = $q->fetchAll();
} else {
    $rows = $pdo->query('SELECT s.*, u.full_name, u.username FROM students s JOIN users u ON u.id = s.user_id ORDER BY s.created_at DESC')->fetchAll();
}
$editId = (int)($_GET['edit'] ?? 0);
$editStudent = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT s.*, u.full_name, u.username FROM students s JOIN users u ON u.id = s.user_id WHERE s.id = ?');
    $stmt->execute([$editId]);
    $editStudent = $stmt->fetch() ?: null;
}
require __DIR__ . '/includes/header.php';
?>
<section class="card">
    <div class="section-head">
        <div>
            <h2>Data & pencarian santri</h2>
            <p>Kelola identitas, akun login, jenjang, dan status santri dalam satu halaman.</p>
        </div><div style="display:flex;gap:8px;flex-wrap:wrap"><a class="btn btn-light" href="<?= APP_BASE ?>/student_pdf.php" target="_blank" rel="noopener">⬇ Cetak PDF</a><button class="btn" type="button" onclick="openStudentModal()">＋ Tambah santri</button></div>
    </div>
    <form method="get" class="student-filter-row" style="max-width:900px;display:flex;gap:10px;flex-wrap:wrap;margin:10px 0 18px"><div style="flex:2;min-width:220px"><label for="studentSearch">Cari santri</label><input class="input" id="studentSearch" name="q" value="<?= e($search) ?>" placeholder="Nama, nomor induk, username"></div><div style="flex:1;min-width:180px"><label for="levelFilter">Filter jenjang</label><select name="level" id="levelFilter"><option value="">Semua jenjang</option><?php foreach ($levels as $level): ?><option value="<?= e($level) ?>" <?= $levelFilter === $level ? "selected" : "" ?>><?= e($level) ?></option><?php endforeach; ?></select></div></form></section>
<div class="premium-modal-backdrop <?= $editStudent ? 'is-open' : '' ?>" id="studentModal" role="presentation" aria-hidden="<?= $editStudent ? 'false' : 'true' ?>">
    <section class="premium-modal" role="dialog" aria-modal="true" aria-labelledby="studentModalTitle"><button class="premium-modal-close" type="button" onclick="closeStudentModal()" aria-label="Tutup">×</button>
        <div class="premium-modal-kicker">ADMINISTRASI SANTRI</div>
        <h2 id="studentModalTitle"><?= $editStudent ? 'Ubah data santri' : 'Tambah santri baru' ?></h2>
        <p>Lengkapi identitas santri dan akun aksesnya.</p>
        <form method="post" class="form-grid">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="<?= $editStudent ? 'update' : 'add' ?>">
            <?php if ($editStudent): ?><input type="hidden" name="id" value="<?= (int)$editStudent['id'] ?>"><?php endif; ?>
            <div class="form-group full"><label>Nama lengkap</label><input class="input" name="full_name" required value="<?= e($editStudent['full_name'] ?? '') ?>"></div>
            <div class="form-group"><label>Nomor induk santri</label><input class="input" name="student_no" required value="<?= e($editStudent['student_no'] ?? '') ?>"></div>
            <div class="form-group"><label>Jenjang</label><select name="class_level" required>
                    <option value="">Pilih jenjang...</option><?php foreach ($levels as $level): ?><option value="<?= e($level) ?>" <?= ($editStudent['class_level'] ?? '') === $level ? 'selected' : '' ?>><?= e($level) ?></option><?php endforeach; ?>
                </select>
                <!-- <span class="help">Pilihan mengikuti jenjang aktif pada menu Nominal Jenjang.</span> -->
            </div>
            <div class="form-group"><label>Username</label><input class="input" name="username" readonly required value="<?= e($editStudent['username'] ?? '') ?>"></div>
            <div class="form-group"><label><?= $editStudent ? 'Password baru (opsional)' : 'Password default' ?></label><input class="input" name="password" type="password" minlength="8" value="<?= $editStudent ? '' : '1234567890' ?>" <?= $editStudent ? '' : 'readonly' ?>></div>
            <div class="form-group full"><button class="btn" type="submit"><?= $editStudent ? 'Simpan perubahan' : 'Simpan data santri' ?></button></div>
        </form>
    </section>
</div>
<section class="card" style="margin-top:20px">
    <div class="section-head">
        <div>
            <h2>Daftar santri</h2>
            <p><?= count($rows) ?> data ditemukan.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table id="student-table">
            <thead>
                <tr>
                    <th>Santri</th>
                    <th>Nomor induk</th>
                    <th>Jenjang</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?><tr>
                        <td>
                            <div class="person">
                                <div class="person-avatar"><?= e(strtoupper(substr($row['full_name'], 0, 1))) ?></div><strong><?= e($row['full_name']) ?></strong>
                            </div>
                        </td>
                        <td><?= e($row['student_no']) ?></td>
                        <td><?= e($row['class_level']) ?></td>
                        <td><?= e($row['username']) ?></td>
                        <td><span class="badge <?= (int)$row['active'] === 1 ? 'paid' : 'neutral' ?>"><?= (int)$row['active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td>
                            <div style="display:flex;gap:6px;align-items:center"><a class="btn btn-light btn-small" href="<?= APP_BASE ?>/students.php?edit=<?= (int)$row['id'] ?>">✎ Ubah</a><a class="btn btn-light btn-small" href="<?= APP_BASE ?>/santri_tagihan.php?student_id=<?= (int)$row['id'] ?>">Tagihan</a><a class="btn btn-small" style="background:#245da8;color:#fff;border:1px solid #245da8" href="<?= APP_BASE ?>/student_pdf.php?id=<?= (int)$row['id'] ?>" target="_blank" rel="noopener">PDF Data</a>
                                <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn btn-small <?= (int)$row['active'] === 1 ? 'btn-danger' : 'btn-light' ?>" type="submit"><?= (int)$row['active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
                            </div>
                        </td>
                    </tr><?php endforeach; ?>
                <?php if (!$rows): ?><tr>
                        <td colspan="6" class="empty">Belum ada data santri.</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<script>
    // Filter otomatis: pencarian mengirim form setelah jeda singkat, jenjang langsung mengirim form.
    const studentFilterForm = document.querySelector('.student-filter-row');
    const studentSearch = document.getElementById('studentSearch');
    const levelFilter = document.getElementById('levelFilter');
    let studentSearchTimer;
    if (studentFilterForm && studentSearch) {
        studentSearch.addEventListener('input', function () {
            clearTimeout(studentSearchTimer);
            studentSearchTimer = setTimeout(() => studentFilterForm.requestSubmit(), 350);
        });
    }
    if (studentFilterForm && levelFilter) {
        levelFilter.addEventListener('change', () => studentFilterForm.requestSubmit());
    }

    function openStudentModal() {
        document.getElementById('studentModal').classList.add('is-open');
        document.body.classList.add('premium-modal-open')
    }

    function closeStudentModal() {
        document.getElementById('studentModal').classList.remove('is-open');
        document.body.classList.remove('premium-modal-open')
    }
    document.getElementById('studentModal').addEventListener('click', function(e) {
        if (e.target === this) closeStudentModal()
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeStudentModal()
    });
    const nameInput = document.querySelector('input[name="full_name"]');
    const usernameInput = document.querySelector('input[name="username"]');
    if (nameInput && usernameInput && !usernameInput.value) {
        nameInput.addEventListener('input', () => usernameInput.value = nameInput.value.trim());
    }

    function filterRows(value, id) {
        document.querySelectorAll('#' + id + ' tbody tr').forEach(row => row.style.display = row.innerText.toLowerCase().includes(value.toLowerCase()) ? '' : 'none');
    }
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>