<?php
require __DIR__ . '/config.php';

if (!empty($_SESSION['user'])) {
    redirect(
        ($_SESSION['user']['role'] ?? '') === 'admin'
            ? 'admin.php'
            : 'student.php'
    );
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        'SELECT id, username, password_hash, role, full_name
         FROM users
         WHERE username = ?
         LIMIT 1'
    );

    $stmt->execute([$username]);
    $row = $stmt->fetch();

    if ($row && password_verify($password, $row['password_hash'])) {
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'        => (int) $row['id'],
            'username'  => $row['username'],
            'role'      => $row['role'],
            'full_name' => $row['full_name'],
        ];

        redirect(
            $row['role'] === 'admin'
                ? 'admin.php'
                : 'student.php'
        );
    }

    $error = 'Username atau password tidak sesuai.';
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#075e49">

    <title>Masuk | Mambaul Huda</title>

    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/style.css">
</head>

<body>
    <main class="login-page">
        <section class="login-visual">
            <div class="brand-login">
                <span class="brand-mark">MH</span>
                <div class="brand-text">
                    <strong>MAMBAUL HUDA</strong>
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
                    <span class="brand-mark">MH</span>
                    <div class="brand-text">
                        <strong>MAMBAUL HUDA</strong>
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

                <form method="post" autocomplete="on">
                    <input
                        type="hidden"
                        name="csrf"
                        value="<?= e(csrf_token()) ?>">

                    <div class="login-form-group">
                        <label for="username">Username</label>

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
                                placeholder="Masukkan username"
                                value="<?= e($_POST['username'] ?? '') ?>">
                        </div>
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
                        <span>Masuk ke dashboard</span>
                        <span aria-hidden="true">&#8594;</span>
                    </button>
                </form>

                <div class="login-bottom-note">
                    Dengan masuk, Anda menyetujui penggunaan akun sesuai hak akses
                    yang diberikan.<br>
                    <strong>Mambaul Huda</strong>
                    <span aria-hidden="true">&middot;</span>
                    Portal Pembayaran Santri
                </div>
            </div>
        </section>
    </main>

    <script>
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
    </script>
</body>

</html>

<style>
    /* =========================================================
   MAMBAUL HUDA — LOGIN PREMIUM
   CSS ini khusus halaman login.
   Tidak mengganti style dashboard yang sudah ada.
========================================================= */

    .login-page {
        --login-green-950: #063b30;
        --login-green-900: #075e49;
        --login-green-800: #08775a;
        --login-green-600: #0ca678;
        --login-text: #172b3a;
        --login-muted: #718096;
        --login-border: #dce6e9;

        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(420px, 0.9fr);
        min-height: 100vh;
        margin: 0;
        color: var(--login-text);
        background: #f5f8f7;
        font-family: Inter, "Segoe UI", Arial, sans-serif;
    }

    .login-page *,
    .login-page *::before,
    .login-page *::after {
        box-sizing: border-box;
    }

    /* PANEL BRANDING */

    .login-visual {
        position: relative;
        isolation: isolate;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-width: 0;
        overflow: hidden;
        padding: clamp(32px, 6vw, 82px);
        color: #fff;
        background:
            radial-gradient(circle at 15% 85%,
                rgba(16, 185, 129, 0.28),
                transparent 36%),
            linear-gradient(145deg,
                var(--login-green-950),
                var(--login-green-900) 55%,
                #087b5b);
    }

    .login-visual::before,
    .login-visual::after {
        position: absolute;
        z-index: -1;
        content: "";
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 50%;
        pointer-events: none;
    }

    .login-visual::before {
        right: -200px;
        top: -160px;
        width: 450px;
        height: 450px;
        box-shadow:
            0 0 0 35px rgba(255, 255, 255, 0.025),
            0 0 0 75px rgba(255, 255, 255, 0.018);
    }

    .login-visual::after {
        right: -120px;
        top: -100px;
        width: 330px;
        height: 330px;
    }

    .brand-login,
    .mobile-brand {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .brand-login {
        position: relative;
        z-index: 1;
    }

    .brand-mark {
        display: grid;
        flex: 0 0 auto;
        place-items: center;
        width: 54px;
        height: 54px;
        border-radius: 17px;
        color: var(--login-green-950);
        background: linear-gradient(145deg, #fff, #d9f8e9);
        font-size: 23px;
        font-weight: 900;
        letter-spacing: -2px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    }

    .brand-text strong {
        display: block;
        color: inherit;
        font-size: 15px;
        font-weight: 800;
        letter-spacing: 1.2px;
    }

    .brand-text small {
        display: block;
        margin-top: 5px;
        color: #b8e9d6;
        font-size: 9px;
        letter-spacing: 1.5px;
    }

    .login-visual-content {
        position: relative;
        z-index: 1;
        max-width: 570px;
        margin: 60px 0;
    }

    .visual-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 13px;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 30px;
        background: rgba(255, 255, 255, 0.07);
        color: #c6f6df;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 1.1px;
    }

    .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #5ee6ad;
        box-shadow: 0 0 0 4px rgba(94, 230, 173, 0.12);
    }

    .login-visual h1 {
        max-width: 570px;
        margin: 25px 0 18px;
        color: #fff;
        font-size: clamp(35px, 4.1vw, 56px);
        font-weight: 800;
        line-height: 1.15;
        letter-spacing: -1.8px;
    }

    .login-visual h1 span {
        display: block;
        color: #6ee7b7;
    }

    .login-visual-content>p {
        max-width: 450px;
        margin: 0;
        color: #d0e8df;
        font-size: 14px;
        line-height: 1.9;
    }

    .login-features {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
        margin-top: 42px;
    }

    .login-feature {
        min-width: 0;
        padding-right: 8px;
    }

    .feature-icon {
        display: grid;
        place-items: center;
        width: 43px;
        height: 43px;
        margin-bottom: 13px;
        border-radius: 14px;
        background: rgba(110, 231, 183, 0.15);
        color: #8ff2c6;
        font-size: 21px;
    }

    .login-feature strong {
        display: block;
        color: #fff;
        font-size: 12px;
    }

    .login-feature p {
        margin: 7px 0 0;
        color: #c0ded2;
        font-size: 10px;
        line-height: 1.7;
    }

    .login-visual-footer {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding-top: 22px;
        border-top: 1px solid rgba(255, 255, 255, 0.14);
        color: #c0ded2;
        font-size: 10px;
        line-height: 1.8;
        letter-spacing: 0.6px;
    }

    .login-visual-footer strong {
        color: #fff;
        letter-spacing: 1px;
    }

    /* PANEL FORM */

    .login-form-side {
        display: grid;
        place-items: center;
        min-width: 0;
        padding: clamp(25px, 5vw, 65px);
        background:
            radial-gradient(circle at 100% 0, #e4f7ef 0, transparent 35%),
            #f7faf9;
    }

    .login-box {
        width: 100%;
        max-width: 440px;
        min-width: 0;
        padding: clamp(26px, 4vw, 43px);
        border: 1px solid rgba(226, 235, 232, 0.9);
        border-radius: 26px;
        background: rgba(255, 255, 255, 0.96);
        box-shadow:
            0 24px 70px rgba(21, 67, 51, 0.08),
            0 3px 10px rgba(21, 67, 51, 0.025);
    }

    .mobile-brand {
        display: none;
        margin-bottom: 30px;
        color: var(--login-text);
    }

    .eyebrow {
        color: var(--login-green-800);
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 2px;
    }

    .login-box h2 {
        margin: 12px 0 9px;
        color: #172b24;
        font-size: clamp(26px, 3vw, 33px);
        font-weight: 800;
        line-height: 1.25;
        letter-spacing: -0.8px;
    }

    .login-description {
        margin: 0 0 28px;
        color: var(--login-muted);
        font-size: 13px;
        line-height: 1.8;
    }

    /* INFORMASI AKUN DEMO */

    .login-demo-note {
        display: flex;
        gap: 12px;
        margin-bottom: 27px;
        padding: 15px;
        border: 1px solid #d9f2e5;
        border-radius: 15px;
        background: #f0fbf5;
    }

    .demo-icon {
        display: grid;
        flex: 0 0 auto;
        place-items: center;
        width: 37px;
        height: 37px;
        border-radius: 11px;
        background: #dcf7e9;
        color: var(--login-green-800);
        font-size: 17px;
    }

    .login-demo-note strong {
        display: block;
        margin-bottom: 5px;
        color: #17634b;
        font-size: 12px;
    }

    .login-demo-note p {
        margin: 0;
        color: #57766b;
        font-size: 11px;
        line-height: 1.8;
    }

    /* INPUT FORM */

    .login-form-group {
        margin-bottom: 20px;
    }

    .login-form-group label {
        display: block;
        margin-bottom: 9px;
        color: #273e4b;
        font-size: 12px;
        font-weight: 700;
    }

    .login-input-wrap {
        position: relative;
    }

    .login-input-icon {
        position: absolute;
        top: 50%;
        left: 15px;
        display: grid;
        place-items: center;
        transform: translateY(-50%);
        color: #82979a;
        pointer-events: none;
    }

    .login-input-icon svg {
        display: block;
        width: 19px;
        height: 19px;
    }

    .login-input {
        display: block;
        width: 100%;
        height: 51px;
        padding: 0 46px;
        border: 1px solid var(--login-border);
        border-radius: 13px;
        outline: none;
        background: #fff;
        color: var(--login-text);
        font-family: inherit;
        font-size: 13px;
        transition:
            border-color 0.2s,
            box-shadow 0.2s,
            background 0.2s;
    }

    .login-input::placeholder {
        color: #a0adb5;
    }

    .login-input:hover {
        border-color: #b8d7c9;
    }

    .login-input:focus {
        border-color: var(--login-green-600);
        box-shadow: 0 0 0 4px rgba(12, 166, 120, 0.11);
    }

    .login-password-toggle {
        position: absolute;
        top: 50%;
        right: 9px;
        display: grid;
        place-items: center;
        width: 34px;
        height: 34px;
        transform: translateY(-50%);
        border: 0;
        border-radius: 9px;
        background: transparent;
        color: #71878b;
        cursor: pointer;
    }

    .login-password-toggle svg {
        display: block;
        width: 19px;
        height: 19px;
    }

    .login-password-toggle:hover {
        background: #eef8f3;
        color: var(--login-green-800);
    }

    .login-password-toggle:focus-visible {
        outline: 2px solid var(--login-green-600);
        outline-offset: 2px;
    }

    /* PESAN ERROR */

    .login-alert {
        margin-bottom: 22px;
        padding: 13px 15px;
        border: 1px solid #fecaca;
        border-radius: 12px;
        background: #fff1f2;
        color: #b42332;
        font-size: 12px;
        line-height: 1.7;
    }

    /* TOMBOL LOGIN */

    .login-submit {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        min-height: 52px;
        margin-top: 27px;
        padding: 14px 20px;
        border: 0;
        border-radius: 13px;
        background: linear-gradient(120deg,
                var(--login-green-800),
                var(--login-green-600));
        color: #fff;
        font-family: inherit;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 8px 18px rgba(8, 119, 90, 0.18);
        transition:
            transform 0.2s,
            box-shadow 0.2s,
            filter 0.2s;
    }

    .login-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(8, 119, 90, 0.24);
        filter: brightness(1.04);
    }

    .login-submit:active {
        transform: translateY(0);
    }

    .login-submit:focus-visible {
        outline: 3px solid #a7f3d0;
        outline-offset: 3px;
    }

    .login-bottom-note {
        margin-top: 24px;
        color: #8b999e;
        font-size: 10px;
        line-height: 1.9;
        text-align: center;
    }

    .login-bottom-note strong {
        color: #547068;
    }

    /* RESPONSIVE TABLET */

    @media (max-width: 1000px) {
        .login-page {
            grid-template-columns: minmax(0, 0.85fr) minmax(400px, 1fr);
        }

        .login-visual {
            padding: 38px;
        }

        .login-visual h1 {
            font-size: 38px;
        }

        .login-form-side {
            padding: 28px;
        }

        .login-features {
            gap: 10px;
        }
    }

    /* RESPONSIVE MOBILE */

    @media (max-width: 760px) {
        .login-page {
            display: block;
            min-height: 100vh;
        }

        .login-visual {
            display: none;
        }

        .login-form-side {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 25px 18px;
        }

        .login-box {
            max-width: 460px;
            padding: 27px 23px;
            border-radius: 22px;
        }

        .mobile-brand {
            display: flex;
        }

        .mobile-brand .brand-mark {
            width: 45px;
            height: 45px;
            border: 1px solid #d9f0e4;
            background: #ecfaf3;
            color: var(--login-green-800);
            box-shadow: none;
        }

        .mobile-brand .brand-text strong {
            color: #172b24;
        }

        .mobile-brand .brand-text small {
            color: #718078;
        }

        .login-box h2 {
            font-size: 28px;
        }
    }

    @media (max-width: 380px) {
        .login-form-side {
            padding: 15px 12px;
        }

        .login-box {
            padding: 23px 17px;
        }

        .login-demo-note {
            padding: 12px;
        }
    }

    /* AKSESIBILITAS */

    @media (prefers-reduced-motion: reduce) {

        .login-page *,
        .login-page *::before,
        .login-page *::after {
            scroll-behavior: auto !important;
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
    }


    /* =========================================================
   REFINEMENT — LOGIN LEBIH MENYATU, TIDAK TERLIHAT MELAYANG
========================================================= */
    .login-page {
        grid-template-columns: minmax(0, 1.08fr) minmax(440px, 0.92fr);
        background: #f2f7f4;
    }

    .login-visual {
        padding: clamp(30px, 4.5vw, 68px);
    }

    .login-visual-content {
        margin: clamp(28px, 4vh, 48px) 0;
    }

    .login-form-side {
        padding: clamp(20px, 3.5vw, 48px);
        background:
            linear-gradient(135deg, rgba(229, 245, 237, 0.72), transparent 48%),
            #f3f7f5;
    }

    .login-box {
        max-width: 500px;
        padding: clamp(30px, 3.4vw, 46px);
        border: 1px solid #e0eae4;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 10px 32px rgba(19, 57, 42, 0.055);
    }

    .login-box h2 {
        font-size: clamp(28px, 2.8vw, 35px);
    }

    .login-description {
        margin-bottom: 24px;
    }

    .login-demo-note {
        margin-bottom: 23px;
        border-radius: 10px;
    }

    .login-input {
        border-radius: 10px;
        background: #fbfdfc;
    }

    .login-submit {
        border-radius: 10px;
        box-shadow: 0 5px 13px rgba(8, 119, 90, 0.15);
    }

    .login-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 17px rgba(8, 119, 90, 0.2);
    }

    .login-bottom-note {
        margin-top: 20px;
    }

    @media (min-width: 1500px) {
        .login-page {
            grid-template-columns: minmax(0, 1.12fr) minmax(500px, 0.88fr);
        }

        .login-box {
            max-width: 520px;
        }
    }

    @media (max-width: 1000px) and (min-width: 761px) {
        .login-page {
            grid-template-columns: minmax(0, 0.95fr) minmax(380px, 1.05fr);
        }

        .login-form-side {
            padding: 24px;
        }

        .login-box {
            padding: 30px 27px;
        }
    }

    @media (max-width: 760px) {
        .login-form-side {
            padding: 24px 18px;
            background:
                radial-gradient(circle at 100% 0, #dff4e9 0, transparent 42%),
                #f3f7f5;
        }

        .login-box {
            max-width: 480px;
            padding: 28px 24px;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(19, 57, 42, 0.06);
        }
    }
</style>