<?php

/**
 * Checkout Midtrans Snap.
 *
 * Persyaratan:
 * - Composer SDK: midtrans/midtrans-php
 * - Server Key dan Client Key sudah diatur di config.php
 * - URL notifikasi Midtrans sudah dikonfigurasi
 */

// 1. Muat Composer autoloader.
$autoloadPath = __DIR__ . '/vendor/autoload.php';

if (!file_exists($autoloadPath)) {
    http_response_code(500);
    exit('Composer autoload tidak ditemukan. ' .
        'Jalankan composer require midtrans/midtrans-php ' .
        'di folder utama project.');
}

require_once $autoloadPath;

// 2. Muat konfigurasi aplikasi.
require_once __DIR__ . '/config.php';

// 3. Pastikan pengguna sudah login sebagai santri.
require_role('santri');

// 4. Periksa konfigurasi Midtrans.
if (
    !defined('MIDTRANS_SERVER_KEY') ||
    !defined('MIDTRANS_CLIENT_KEY') ||
    !MIDTRANS_SERVER_KEY ||
    !MIDTRANS_CLIENT_KEY ||
    str_starts_with(MIDTRANS_SERVER_KEY, 'ISI_') ||
    str_starts_with(MIDTRANS_CLIENT_KEY, 'ISI_')
) {

    flash(
        'Midtrans belum dikonfigurasi. Isi Server Key dan Client Key Sandbox di config.php terlebih dahulu.',
        'warning'
    );

    redirect('history.php');
}

// 5. Pastikan SDK Midtrans berhasil dimuat.
if (
    !class_exists('\Midtrans\Config') ||
    !class_exists('\Midtrans\Snap')
) {

    http_response_code(500);
    exit('SDK Midtrans tidak dapat dimuat. ' .
        'Periksa instalasi Composer dan vendor/autoload.php.');
}

// 6. Konfigurasi SDK Midtrans.
\Midtrans\Config::$serverKey = MIDTRANS_SERVER_KEY;
\Midtrans\Config::$isProduction = MIDTRANS_IS_PRODUCTION;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// 7. Ambil identitas santri yang sedang login.
$sid = current_student_id($pdo);

// Pastikan identitas santri tersedia.
if (!$sid) {
    http_response_code(403);
    exit('Akun santri tidak ditemukan.');
}

// 8. Ambil transaksi pending milik santri yang login.
$orderId = $_GET['order_id'] ?? '';

if (!is_string($orderId) || $orderId === '') {
    http_response_code(400);
    exit('Nomor transaksi tidak valid.');
}

$q = $pdo->prepare(
    "SELECT
        p.*,
        u.full_name
     FROM payments p
     JOIN students s ON s.id = p.student_id
     JOIN users u ON u.id = s.user_id
     WHERE p.order_id = ?
       AND p.student_id = ?
       AND p.method = 'midtrans'
       AND p.status = 'pending'
     LIMIT 1"
);

$q->execute([$orderId, $sid]);
$p = $q->fetch(PDO::FETCH_ASSOC);

// 9. Pastikan transaksi ditemukan dan masih pending.
if (!$p) {
    http_response_code(404);
    exit('Transaksi tidak ditemukan atau sudah diproses.');
}

// 10. Siapkan checkout Midtrans.
try {
    $amount = (int) $p['total_amount'];

    if ($amount <= 0) {
        throw new RuntimeException('Nominal pembayaran tidak valid.');
    }

    $params = [
        'transaction_details' => [
            'order_id' => $p['order_id'],
            'gross_amount' => $amount,
        ],

        'customer_details' => [
            'first_name' => $p['full_name'],
        ],

        // Tentukan URL kembali secara eksplisit agar tidak memakai
        // Finish Redirect URL default (misalnya example.com) dari Dashboard.
        'callbacks' => [
            'finish' => APP_URL . '/history.php',
        ],

        'item_details' => [
            [
                'id' => 'SPP-' . $p['billing_month'],
                'price' => $amount,
                'quantity' => 1,
                'name' => 'SPP ' . month_label($p['billing_month']),
            ],
        ],
    ];

    // Gunakan token yang tersimpan agar halaman refresh tidak membuat order ulang.
    $snapToken = trim((string)($p['snap_token'] ?? ''));
    if ($snapToken === '') {
        $snapToken = \Midtrans\Snap::getSnapToken($params);
        $saveToken = $pdo->prepare('UPDATE payments SET snap_token = ? WHERE id = ? AND snap_token IS NULL');
        $saveToken->execute([$snapToken, $p['id']]);
        if ($saveToken->rowCount() === 0) {
            $reload = $pdo->prepare('SELECT snap_token FROM payments WHERE id = ?');
            $reload->execute([$p['id']]);
            $storedToken = trim((string)$reload->fetchColumn());
            if ($storedToken !== '') {
                $snapToken = $storedToken;
            }
        }
    }

    // Tentukan URL JavaScript sesuai mode sandbox/production.
    $snapJs = MIDTRANS_IS_PRODUCTION
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js';
} catch (Throwable $e) {
    // Catat detail error di log server, bukan di halaman pengguna.
    error_log('Midtrans checkout error: ' . $e->getMessage());

    http_response_code(500);
    exit('Gagal membuat checkout. ' .
        'Periksa konfigurasi Midtrans dan log server.');
}

// 11. Siapkan URL riwayat pembayaran.
$historyUrl = APP_BASE . '/history.php';

?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Checkout Midtrans</title>

    <link
        rel="stylesheet"
        href="<?= e(APP_BASE) ?>/assets/style.css">

    <script
        src="<?= e($snapJs) ?>"
        data-client-key="<?= e(MIDTRANS_CLIENT_KEY) ?>"></script>
</head>

<body>
    <div style="max-width:620px;margin:8vh auto;padding:20px">

        <div class="card">

            <div class="brand-login">
                <span class="brand-mark">MH</span>
                Mambaul Huda
            </div>

            <div class="eyebrow" style="margin-top:25px">
                CHECKOUT PEMBAYARAN
            </div>

            <h2 style="font:800 24px Manrope;margin:0 0 8px">
                Konfirmasi pembayaran SPP
            </h2>

            <p class="help">
                Selesaikan pembayaran melalui kanal yang tersedia
                di Midtrans. Status pembayaran akan diperbarui
                setelah notifikasi server divalidasi.
            </p>

            <div class="summary-row">
                <span>Nomor transaksi</span>
                <strong><?= e($p['order_id']) ?></strong>
            </div>

            <div class="summary-row">
                <span>Periode</span>
                <strong>
                    <?= e(month_label($p['billing_month'])) ?>
                </strong>
            </div>

            <div class="summary-row">
                <span>Total pembayaran</span>
                <strong style="color:var(--green);font-size:18px">
                    <?= rupiah($p['total_amount']) ?>
                </strong>
            </div>

            <button
                type="button"
                id="pay-button"
                class="btn"
                style="width:100%;margin-top:22px">
                Bayar melalui Midtrans →
            </button>

            <a
                class="btn btn-light"
                style="width:100%;margin-top:8px"
                href="<?= e($historyUrl) ?>">
                Kembali ke riwayat
            </a>

            <p class="help" style="margin-top:15px">
                Jangan menutup halaman sebelum jendela pembayaran
                muncul. Setelah pembayaran, periksa statusnya
                kembali di riwayat pembayaran.
            </p>

        </div>
    </div>

    <script>
        (function() {
            const payButton = document.getElementById('pay-button');

            const snapToken = <?= json_encode(
                                    $snapToken,
                                    JSON_HEX_TAG |
                                        JSON_HEX_APOS |
                                        JSON_HEX_QUOT |
                                        JSON_HEX_AMP
                                ) ?>;

            const historyUrl = <?= json_encode(
                                    $historyUrl,
                                    JSON_HEX_TAG |
                                        JSON_HEX_APOS |
                                        JSON_HEX_QUOT |
                                        JSON_HEX_AMP
                                ) ?>;

            payButton.addEventListener('click', function() {
                if (typeof window.snap === 'undefined') {
                    alert(
                        'Layanan pembayaran Midtrans belum termuat. ' +
                        'Periksa koneksi internet dan konfigurasi Client Key.'
                    );
                    return;
                }

                payButton.disabled = true;

                window.snap.pay(snapToken, {
                    onSuccess: function() {
                        window.location.href = historyUrl;
                    },

                    onPending: function() {
                        window.location.href = historyUrl;
                    },

                    onError: function() {
                        payButton.disabled = false;

                        alert(
                            'Pembayaran belum berhasil. ' +
                            'Silakan coba lagi atau periksa riwayat pembayaran.'
                        );
                    },

                    onClose: function() {
                        payButton.disabled = false;
                    }
                });
            });
        })();
    </script>

</body>

</html>