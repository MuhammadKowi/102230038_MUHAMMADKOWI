<?php
require __DIR__ . '/config.php';

$error = '';
$schoolName = app_school_name();
$schoolLogoUrl = app_school_logo_url();
// Favicon memakai URL lokal supaya tidak bergantung pada nilai APP_BASE di hosting.
$appPublicPath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$schoolLogoFile = $schoolLogoUrl ? basename(parse_url($schoolLogoUrl, PHP_URL_PATH)) : '';
$localFaviconUrl = $schoolLogoFile !== '' ? $appPublicPath . '/uploads/' . rawurlencode($schoolLogoFile) : $appPublicPath . '/favicon.svg';
$localFaviconVersion = $schoolLogoFile !== '' ? (int) @filemtime(__DIR__ . '/uploads/' . $schoolLogoFile) : (int) @filemtime(__DIR__ . '/favicon.svg');
$schoolMark = app_school_mark();

// Endpoint autocomplete nama santri; hanya mengirim ID, nama, dan nomor induk.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['lookup_santri'])) {
    header('Content-Type: application/json; charset=utf-8');
    $query = trim((string) $_GET['lookup_santri']);
    if (mb_strlen($query) < 2) {
        echo json_encode([]);
        exit;
    }
    $stmt = $pdo->prepare(
        "SELECT s.id, u.full_name, s.student_no
         FROM students s JOIN users u ON u.id = s.user_id
         WHERE s.active = 1 AND u.role = 'santri'
           AND (u.full_name LIKE ? OR s.student_no LIKE ?)
         ORDER BY u.full_name LIMIT 10"
    );
    $like = '%' . $query . '%';
    $stmt->execute([$like, $like]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!empty($_SESSION['user'])) {
    redirect(
        ($_SESSION['user']['role'] ?? '') === 'admin'
            ? 'admin.php'
            : 'student.php'
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $loginMode = ($_POST['login_mode'] ?? 'admin') === 'santri' ? 'santri' : 'admin';
    $password = $_POST['password'] ?? '';
    $row = false;

    if ($loginMode === 'santri') {
        $studentId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        if ($studentId) {
            $stmt = $pdo->prepare(
                "SELECT u.id, u.username, u.password_hash, u.role, u.full_name
                 FROM students s JOIN users u ON u.id = s.user_id
                 WHERE s.id = ? AND s.active = 1 AND u.role = 'santri' LIMIT 1"
            );
            $stmt->execute([$studentId]);
            $row = $stmt->fetch();
        }
    } else {
        $username = trim($_POST['username'] ?? '');
        $stmt = $pdo->prepare(
            "SELECT id, username, password_hash, role, full_name
             FROM users WHERE username = ? AND role = 'admin' LIMIT 1"
        );
        $stmt->execute([$username]);
        $row = $stmt->fetch();
    }

    if ($row && password_verify($password, $row['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $row['id'],
            'username' => $row['username'],
            'role' => $row['role'],
            'full_name' => $row['full_name'],
        ];
        redirect($row['role'] === 'admin' ? 'admin.php' : 'student.php');
    }

    $error = 'Nama/username atau password tidak sesuai.';
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#527bb8">

    <title>Masuk | <?= e($schoolName) ?></title>
    <link rel="icon" href="<?= e($localFaviconUrl) ?>?v=<?= $localFaviconVersion ?>">
    <link rel="apple-touch-icon" href="<?= e($localFaviconUrl) ?>?v=<?= $localFaviconVersion ?>">

    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/style.css">
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/login.css">
    <style>
        .login-mode-switch {
            position: relative;
            display: grid;
            grid-template-columns: 1fr 1fr;
            padding: 4px;
            margin: 0 0 22px;
            background: #edf3fc;
            border: 1px solid #e0e9f7;
            border-radius: 14px;
            isolation: isolate
        }

        .mode-option {
            position: relative;
            z-index: 1;
            min-height: 42px;
            border: 0;
            background: transparent;
            color: #6d7f99;
            font-weight: 800;
            font-size: 12px;
            cursor: pointer;
            border-radius: 10px;
            transition: color .2s
        }

        .mode-option.is-active {
            color: #fff
        }

        .mode-slider {
            position: absolute;
            z-index: 0;
            left: 4px;
            top: 4px;
            bottom: 4px;
            width: calc(50% - 4px);
            border-radius: 10px;
            background: linear-gradient(110deg, #315f9f, #477cbd);
            box-shadow: 0 4px 12px #315f9f30;
            transition: transform .25s ease
        }

        .login-mode-switch[data-mode="santri"] .mode-slider {
            transform: translateX(100%)
        }

        #studentNameGroup .login-input-wrap {
            display: block;
            position: relative
        }

        #studentNameGroup .login-input-icon.search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            display: grid;
            place-items: center;
            z-index: 2;
            pointer-events: none
        }

        #studentNameGroup .login-input-icon.search-icon svg {
            display: block;
            width: 18px;
            height: 18px
        }

        #studentNameGroup .login-input {
            padding-left: 43px;
            padding-right: 14px
        }

        .student-suggestions {
            position: absolute;
            z-index: 20;
            top: calc(100% + 7px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #dce6f4;
            border-radius: 12px;
            box-shadow: 0 14px 32px #23437020;
            max-height: 220px;
            overflow: auto;
            padding: 5px
        }

        .student-suggestion {
            display: flex;
            flex-direction: column;
            gap: 3px;
            width: 100%;
            padding: 10px 12px;
            text-align: left;
            border: 0;
            background: #fff;
            border-radius: 8px;
            cursor: pointer;
            color: #243d60
        }

        .student-suggestion:hover,
        .student-suggestion:focus {
            background: #edf4ff;
            outline: none
        }

        .student-suggestion strong {
            font-size: 12px
        }

        .student-suggestion small {
            font-size: 10px;
            color: #788ba7
        }

        .student-selection-hint {
            display: block;
            margin-top: 7px;
            font-size: 10px;
            color: #74849a
        }

        .student-selection-hint.selected {
            color: #26734d;
            font-weight: 700
        }
    </style>
</head>

<body>
<div class="school-loading-screen" id="schoolLoadingScreen" role="status" aria-live="polite" aria-label="Memuat aplikasi">
  <div class="school-loading-content">
    <div class="school-loader-orbit" aria-hidden="true">
      <span class="school-loader-dot school-loader-dot-one"></span>
      <span class="school-loader-dot school-loader-dot-two"></span>
      <span class="school-loader-dot school-loader-dot-three"></span>
      <div class="school-loader-logo"><?php if ($schoolLogoUrl): ?><img src="<?= e($schoolLogoUrl) ?>" alt="Logo <?= e($schoolName) ?>"><?php else: ?><span><?= e($schoolMark) ?></span><?php endif; ?></div>
    </div>
    <div class="school-loader-name"><?= e($schoolName) ?></div>
    <div class="school-loader-caption">Portal Keuangan Santri</div>
    <div class="school-loader-progress" aria-hidden="true"><span></span></div>
    <div class="school-loader-status">Sedang memuat, mohon tunggu...</div>
  </div>
</div>
    <main class="login-page">
        <section class="login-visual">
            <div class="brand-login">
                <span class="brand-mark"><?php if ($schoolLogoUrl): ?><img src="<?= e($schoolLogoUrl) ?>" alt="Logo <?= e($schoolName) ?>"><?php else: ?><?= e($schoolMark) ?><?php endif; ?></span>
                <div class="brand-text">
                    <strong><?= e(strtoupper($schoolName)) ?></strong>
                    <small>PORTAL PEMBAYARAN SANTRI</small>
                </div>
            </div>

            <div class="login-visual-content">
                <div class="visual-label">
                    <span class="status-dot"></span>
                    SISTEM ADMINISTRASI TERINTEGRASI
                </div>

                <h1>
                    Administrasi keuangan,
                    <span>lebih tertata.</span>
                </h1>

                <p>
                    Kelola pembayaran SPP santri dengan proses yang jelas,
                    riwayat yang mudah ditelusuri, dan laporan yang
                    terintegrasi dalam satu sistem.
                </p>

                <div class="login-features">
                    <article class="login-feature">
                        <span class="feature-icon" aria-hidden="true">&#10003;</span>
                        <strong>Aman</strong>
                        <p>Data terjaga dan akses terkontrol.</p>
                    </article>

                    <article class="login-feature">
                        <span class="feature-icon" aria-hidden="true">&#9889;</span>
                        <strong>Praktis</strong>
                        <p>Pengelolaan lebih mudah dan efisien.</p>
                    </article>

                    <article class="login-feature">
                        <span class="feature-icon" aria-hidden="true">&#9638;</span>
                        <strong>Terintegrasi</strong>
                        <p>Riwayat dan laporan lebih tertata.</p>
                    </article>
                </div>
            </div>

            <footer class="login-visual-footer">
                <strong>PORTAL PEMBAYARAN SANTRI</strong>
                <span>PAJOMBLANGAN KEDUNGWUNI</span>
            </footer>
        </section>

        <section class="login-form-side">
            <div class="login-box">
                <div class="mobile-brand">
                    <span class="brand-mark"><?php if ($schoolLogoUrl): ?><img src="<?= e($schoolLogoUrl) ?>" alt="Logo <?= e($schoolName) ?>"><?php else: ?><?= e($schoolMark) ?><?php endif; ?></span>
                    <div class="brand-text">
                        <strong><?= e(strtoupper($schoolName)) ?></strong>
                        <small>PORTAL PEMBAYARAN SANTRI</small>
                    </div>
                </div>

                <div class="eyebrow">SELAMAT DATANG</div>
                <h2>Masuk ke akun</h2>
                <p class="login-description">
                    Gunakan akun admin atau akun santri Anda untuk melanjutkan.
                </p>

                <!-- <div class="login-demo-note">
                    <span class="demo-icon" aria-hidden="true">&#9432;</span>
                    <div>
                        <strong>Informasi akun demo</strong>
                        <p>
                            Admin: <b>admin</b> / <b>password</b><br>
                            Santri: <b>santri001</b> / <b>password</b><br>
                            Ganti kredensial demo sebelum penggunaan nyata.
                        </p>
                    </div>
                </div> -->

                <?php if ($error): ?>
                    <div class="login-alert" role="alert">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <div class="login-mode-switch" role="tablist" aria-label="Pilih jenis login">
                    <button type="button" class="mode-option is-active" data-mode="admin" role="tab" aria-selected="true">Login Admin</button>
                    <button type="button" class="mode-option" data-mode="santri" role="tab" aria-selected="false">Login Santri</button>
                    <span class="mode-slider" aria-hidden="true"></span>
                </div>

                <form method="post" autocomplete="on" id="loginForm">
                    <input type="hidden" name="login_mode" id="loginMode" value="<?= e(($_POST['login_mode'] ?? 'admin') === 'santri' ? 'santri' : 'admin') ?>">
                    <input type="hidden" name="student_id" id="studentId" value="<?= e($_POST['student_id'] ?? '') ?>">
                    <input type="hidden" name="student_name" id="studentNamePosted" value="<?= e($_POST['student_name'] ?? '') ?>">
                    <input
                        type="hidden"
                        name="csrf"
                        value="<?= e(csrf_token()) ?>">

                    <div class="login-form-group" id="adminUsernameGroup">
                        <label for="username">Username admin</label>

                        <div class="login-input-wrap">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="8" r="4"></circle>
                                    <path d="M5 21v-2a7 7 0 0 1 14 0v2"></path>
                                </svg>
                            </span>

                            <input
                                class="login-input"
                                id="username"
                                name="username"
                                type="text"
                                required
                                maxlength="100"
                                autocomplete="username"
                                placeholder="Masukkan username admin"
                                value="<?= e($_POST['username'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="login-form-group" id="studentNameGroup" hidden>
                        <label for="studentName">Nama santri</label>
                        <div class="login-input-wrap">
                            <span class="login-input-icon search-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                    <circle cx="10.8" cy="10.8" r="6.3"></circle>
                                    <path d="m16 16 4.2 4.2"></path>
                                </svg></span>
                            <input class="login-input" id="studentName" type="text" maxlength="150" autocomplete="off" placeholder="Ketik nama santri..." aria-autocomplete="list" aria-controls="studentSuggestions" aria-expanded="false">
                            <div id="studentSuggestions" class="student-suggestions" role="listbox" hidden></div>
                        </div>
                        <small class="student-selection-hint" id="studentHint"></small>
                    </div>

                    <div class="login-form-group">
                        <label for="password">Password</label>

                        <div class="login-input-wrap">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="4" y="10" width="16" height="11" rx="2"></rect>
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                                    <path d="M12 14v3"></path>
                                </svg>
                            </span>

                            <input
                                class="login-input login-password-input"
                                id="password"
                                name="password"
                                type="password"
                                required
                                autocomplete="current-password"
                                placeholder="Masukkan password">

                            <button
                                class="login-password-toggle"
                                type="button"
                                id="togglePassword"
                                aria-label="Tampilkan password"
                                aria-pressed="false">
                                <svg id="eyeIcon" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button class="login-submit" type="submit">
                        <span id="submitLabel">Masuk sebagai admin</span>
                        <span aria-hidden="true">&#8594;</span>
                    </button>
                </form>

                <div class="login-bottom-note">
                    Dengan masuk, Anda menyetujui penggunaan akun sesuai hak akses
                    yang diberikan.<br>
                    <strong><?= e($schoolName) ?></strong>
                    <span aria-hidden="true">&middot;</span>
                    Portal Pembayaran Santri
                </div>
            </div>
        </section>
    </main>

    <script>
        // Loader tampil saat login dibuka/refresh dan saat formulir login dikirim.
        (function () {
            const loader = document.getElementById('schoolLoadingScreen');
            if (!loader) return;
            let submitted = false;
            function hideLoader() {
                window.setTimeout(() => loader.classList.add('is-hidden'), 500);
            }
            if (document.readyState === 'complete') hideLoader();
            else window.addEventListener('load', hideLoader, { once: true });
            window.addEventListener('pageshow', function () {
                if (!submitted) {
                    loader.classList.remove('is-hidden');
                    hideLoader();
                }
            });
            const form = document.getElementById('loginForm');
            if (form) form.addEventListener('submit', function (event) {
                window.setTimeout(function () {
                    if (!event.defaultPrevented) {
                        submitted = true;
                        try { sessionStorage.setItem('santriGoShowLoaderAfterLogin', '1'); } catch (e) {}
                        loader.classList.remove('is-hidden');
                    }
                }, 0);
            });
        })();

        const passwordInput = document.getElementById('password');
        const toggleButton = document.getElementById('togglePassword');
        const eyeIcon = document.getElementById('eyeIcon');

        toggleButton.addEventListener('click', function() {
            const showPassword = passwordInput.type === 'password';

            passwordInput.type = showPassword ? 'text' : 'password';

            toggleButton.setAttribute(
                'aria-label',
                showPassword ? 'Sembunyikan password' : 'Tampilkan password'
            );

            toggleButton.setAttribute(
                'aria-pressed',
                showPassword ? 'true' : 'false'
            );

            eyeIcon.innerHTML = showPassword ?
                '<path d="M3 3l18 18"></path>' +
                '<path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>' +
                '<path d="M9.9 5.2A10.9 10.9 0 0 1 12 5c6.5 0 10 7 10 7a15 15 0 0 1-4 4.7"></path>' +
                '<path d="M6.2 6.2C3.5 8 2 12 2 12s3.5 7 10 7a10 10 0 0 0 3-.5"></path>' :
                '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>' +
                '<circle cx="12" cy="12" r="3"></circle>';
        });

        // Switch between admin and santri login modes.
        const modeSwitch = document.querySelector('.login-mode-switch');
        const modeButtons = document.querySelectorAll('.mode-option');
        const loginMode = document.getElementById('loginMode');
        const adminGroup = document.getElementById('adminUsernameGroup');
        const studentGroup = document.getElementById('studentNameGroup');
        const usernameInput = document.getElementById('username');
        const studentName = document.getElementById('studentName');
        const studentId = document.getElementById('studentId');
        const studentSuggestions = document.getElementById('studentSuggestions');
        const studentHint = document.getElementById('studentHint');
        const submitLabel = document.getElementById('submitLabel');
        let lookupTimer;

        function setLoginMode(mode) {
            const isSantri = mode === 'santri';
            loginMode.value = isSantri ? 'santri' : 'admin';
            modeSwitch.dataset.mode = loginMode.value;
            modeButtons.forEach(button => {
                const active = button.dataset.mode === loginMode.value;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            adminGroup.hidden = isSantri;
            studentGroup.hidden = !isSantri;
            usernameInput.required = !isSantri;
            studentName.required = isSantri;
            document.getElementById('password').autocomplete = 'current-password';
            submitLabel.textContent = isSantri ? 'Masuk sebagai santri' : 'Masuk sebagai admin';
            if (!isSantri) {
                studentId.value = '';
                studentSuggestions.hidden = true;
                studentName.value = '';
                studentHint.textContent = '';
                studentHint.classList.remove('selected');
            }
        }
        modeButtons.forEach(button => button.addEventListener('click', () => setLoginMode(button.dataset.mode)));
        setLoginMode(loginMode.value);
        if (loginMode.value === 'santri' && studentId.value) {
            studentName.value = <?= json_encode($_POST['student_name'] ?? '') ?>;
            document.getElementById('studentNamePosted').value = studentName.value;
        }
        studentName.addEventListener('input', () => {
            studentId.value = '';
            studentHint.textContent = '';
            studentHint.classList.remove('selected');
            const q = studentName.value.trim();
            clearTimeout(lookupTimer);
            if (q.length < 2) {
                studentSuggestions.hidden = true;
                return;
            }
            lookupTimer = setTimeout(async () => {
                try {
                    const response = await fetch('<?= APP_BASE ?>/login.php?lookup_santri=' + encodeURIComponent(q), {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const results = await response.json();
                    studentSuggestions.replaceChildren();
                    if (!results.length) {
                        const empty = document.createElement('div');
                        empty.className = 'student-suggestion';
                        empty.textContent = 'Nama santri tidak ditemukan';
                        studentSuggestions.appendChild(empty);
                    } else {
                        results.forEach(item => {
                            const option = document.createElement('button');
                            option.type = 'button';
                            option.className = 'student-suggestion';
                            option.setAttribute('role', 'option');
                            const name = document.createElement('strong');
                            name.textContent = item.full_name;
                            const detail = document.createElement('small');
                            detail.textContent = 'Nomor induk: ' + item.student_no;
                            option.append(name, detail);
                            option.addEventListener('click', () => {
                                studentId.value = item.id;
                                studentName.value = item.full_name;
                                document.getElementById('studentNamePosted').value = item.full_name;
                                studentSuggestions.hidden = true;
                                studentHint.textContent = 'Santri dipilih: ' + item.full_name;
                                studentHint.classList.add('selected');
                                document.getElementById('password').focus();
                            });
                            studentSuggestions.appendChild(option);
                        });
                    }
                    studentSuggestions.hidden = false;
                } catch (error) {
                    studentSuggestions.hidden = true;
                }
            }, 180);
        });
        document.addEventListener('click', event => {
            if (!studentGroup.contains(event.target)) studentSuggestions.hidden = true;
        });
        document.getElementById('loginForm').addEventListener('submit', event => {
            if (loginMode.value === 'santri' && !studentId.value) {
                event.preventDefault();
                studentHint.textContent = 'Pilih nama santri dari daftar sebelum masuk.';
                studentHint.classList.remove('selected');
                studentName.focus();
            }
        });
    </script>
</body>

</html>