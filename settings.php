<?php
require __DIR__ . '/config.php';
require_role('admin');

$pdo->exec("CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(80) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

function save_app_setting(PDO $pdo, string $key, string $value): void {
    $stmt = $pdo->prepare('INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $stmt->execute([$key, $value]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $schoolName = trim((string)($_POST['school_name'] ?? ''));
    if ($schoolName === '' || mb_strlen($schoolName) > 150) {
        flash('Nama pesantren wajib diisi dan maksimal 150 karakter.', 'error');
        redirect('settings.php');
    }

    $logoName = app_setting('school_logo', '');
    if (!empty($_POST['remove_logo'])) {
        if ($logoName !== '' && basename($logoName) === $logoName) {
            $oldPath = __DIR__ . '/uploads/' . $logoName;
            if (is_file($oldPath)) @unlink($oldPath);
        }
        $logoName = '';
    }

    if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['school_logo'];
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024) {
            flash('Logo gagal diunggah. Ukuran maksimal 2 MB.', 'error');
            redirect('settings.php');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
        $imageInfo = @getimagesize($file['tmp_name']);
        if (!isset($allowed[$mime]) || !$imageInfo || !in_array($imageInfo[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP], true)) {
            flash('Format logo harus PNG, JPG/JPEG, atau WebP.', 'error');
            redirect('settings.php');
        }
        $uploadDir = __DIR__ . '/uploads';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            flash('Folder upload tidak dapat dibuat.', 'error');
            redirect('settings.php');
        }
        $newLogoName = 'school-logo-' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $newLogoName)) {
            flash('Logo gagal disimpan. Periksa izin folder uploads.', 'error');
            redirect('settings.php');
        }
        if ($logoName !== '' && basename($logoName) === $logoName) {
            $oldPath = $uploadDir . '/' . $logoName;
            if (is_file($oldPath) && str_starts_with($logoName, 'school-logo-')) @unlink($oldPath);
        }
        $logoName = $newLogoName;
    }

    save_app_setting($pdo, 'bank_bri_account', preg_replace('/[^0-9]/', '', (string)($_POST['bank_bri_account'] ?? '')));
    save_app_setting($pdo, 'bank_bca_account', preg_replace('/[^0-9]/', '', (string)($_POST['bank_bca_account'] ?? '')));
    save_app_setting($pdo, 'school_name', $schoolName);
    save_app_setting($pdo, 'school_logo', $logoName);
    flash('Identitas pesantren berhasil diperbarui dan akan ditampilkan pada layout aplikasi.', 'success');
    redirect('settings.php');
}

$pageTitle = 'Pengaturan Pesantren';
$currentName = app_setting('school_name', APP_NAME) ?: APP_NAME;
$currentLogo = app_school_logo_url();
$currentBri = app_setting('bank_bri_account', '');
$currentBca = app_setting('bank_bca_account', '');
require __DIR__ . '/includes/header.php';
?>
<section class="card settings-card">
    <div class="section-head">
        <div><h2>Identitas Pesantren</h2><p>Atur nama dan logo </p></div>
    </div>
    <form method="post" enctype="multipart/form-data" class="form-grid">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="form-group full">
            <label for="school_name">Nama pesantren</label>
            <input class="input" id="school_name" name="school_name" maxlength="150" required value="<?= e($currentName) ?>" placeholder="Contoh: Pondok Pesantren Mambaul Huda">
        </div>
        <div class="form-group full">
            <label for="school_logo">Logo pesantren</label>
            <input class="input" type="file" id="school_logo" name="school_logo" accept="image/png,image/jpeg,image/webp">
            <small class="help">Format PNG, JPG/JPEG, atau WebP. Maksimal 2 MB. Logo lama tetap digunakan jika tidak memilih file baru.</small>
        </div>
        <?php if ($currentLogo): ?>
        <div class="form-group full">
            <label>Logo saat ini</label>
            <div class="settings-logo-preview"><img src="<?= e($currentLogo) ?>" alt="Logo pesantren saat ini"><span><?= e($currentName) ?></span></div>
            <label class="settings-remove-logo"><input type="checkbox" name="remove_logo" value="1"> Hapus logo saat ini</label>
        </div>
        <?php endif; ?>
        <div class="form-group"><label>Nomor rekening BRI</label><input class="input" name="bank_bri_account" inputmode="numeric" value="<?= e($currentBri) ?>" placeholder="Masukkan nomor rekening BRI"></div><div class="form-group"><label>Nomor rekening BCA</label><input class="input" name="bank_bca_account" inputmode="numeric" value="<?= e($currentBca) ?>" placeholder="Masukkan nomor rekening BCA"></div><div class="form-group full"><button class="btn" type="submit">Simpan pengaturan</button></div>
    </form>
</section>
<style>
.settings-card{max-width:850px}.settings-logo-preview{display:flex;align-items:center;gap:15px;padding:14px;border:1px solid var(--line);border-radius:12px;background:#fbfdfb}.settings-logo-preview img{width:76px;height:76px;object-fit:contain;border-radius:10px;background:#fff}.settings-logo-preview span{font-weight:800}.settings-remove-logo{display:flex;align-items:center;gap:8px;font-weight:600}
.brand-mark img{width:100%;height:100%;object-fit:contain;border-radius:inherit;background:#fff;padding:3px}.receipt-logo img{width:100%;height:100%;object-fit:contain;border-radius:inherit;background:#fff;padding:1mm}.brand-login .brand-mark img{width:100%;height:100%;object-fit:contain;border-radius:inherit;background:#fff;padding:3px}
</style>
<?php require __DIR__ . '/includes/footer.php'; ?>
