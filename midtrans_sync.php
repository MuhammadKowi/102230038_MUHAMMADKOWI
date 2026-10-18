<?php
/**
 * Sinkronisasi status pembayaran langsung dari Midtrans Status API.
 * Dapat digunakan oleh santri untuk transaksi miliknya atau admin untuk pemeriksaan.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_login();

$role = (string)($_SESSION['user']['role'] ?? '');
if (!in_array($role, ['santri', 'admin'], true)) {
    http_response_code(403);
    exit('Akses tidak diizinkan.');
}

$returnPage = $role === 'admin' ? 'payments.php' : 'history.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}
csrf_check();

$orderId = trim((string)($_POST['order_id'] ?? ''));
$requestedTransactionId = trim((string)($_POST['transaction_id'] ?? ''));
if ($role !== 'admin') {
    // Santri tidak boleh menentukan ID transaksi gateway secara bebas.
    $requestedTransactionId = '';
}
if ($requestedTransactionId !== '' && !preg_match('/^[A-Za-z0-9-]{8,120}$/', $requestedTransactionId)) {
    flash('Transaction ID dari Dashboard Midtrans tidak valid.', 'error');
    redirect($returnPage);
}
if ($orderId === '' || strlen($orderId) > 80) {
    flash('Nomor transaksi tidak valid.', 'error');
    redirect($returnPage);
}

if ($role === 'santri') {
    $studentId = current_student_id($pdo);
    $q = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? AND student_id = ? AND method = 'midtrans' LIMIT 1");
    $q->execute([$orderId, $studentId]);
} else {
    $q = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? AND method = 'midtrans' LIMIT 1");
    $q->execute([$orderId]);
}
$payment = $q->fetch();

if (!MIDTRANS_SERVER_KEY || str_starts_with(MIDTRANS_SERVER_KEY, 'ISI_')) {
    flash('Server Key Sandbox belum diisi dengan benar pada config.php.', 'error');
    redirect($returnPage);
}

if (!$payment) {
    flash('Transaksi Midtrans tidak ditemukan.', 'error');
    redirect($returnPage);
}
if ($payment['status'] === 'paid') {
    flash('Pembayaran ini sudah tercatat lunas.', 'success');
    redirect($returnPage);
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!is_file($autoloadPath)) {
    flash('SDK Midtrans tidak ditemukan. Periksa folder vendor.', 'error');
    redirect($returnPage);
}
require_once $autoloadPath;

if (!class_exists('\Midtrans\Config') || !class_exists('\Midtrans\Transaction')) {
    flash('SDK Midtrans belum terpasang dengan benar.', 'error');
    redirect($returnPage);
}

try {
    \Midtrans\Config::$serverKey = MIDTRANS_SERVER_KEY;
    \Midtrans\Config::$isProduction = MIDTRANS_IS_PRODUCTION;

    // Utamakan Transaction ID dari detail Dashboard jika admin memasukkannya.
    // Midtrans mendukung pencarian status dengan order_id maupun transaction_id.
    $lookupId = $requestedTransactionId !== '' ? $requestedTransactionId : $orderId;
    $response = \Midtrans\Transaction::status($lookupId);

    // Jika pencarian memakai Transaction ID, pastikan respons benar-benar milik order ini.
    $responseOrderId = (string)($response->order_id ?? '');
    if ($responseOrderId !== '' && !hash_equals($orderId, $responseOrderId)) {
        throw new RuntimeException('Transaction ID tersebut tidak cocok dengan order_id aplikasi.');
    }

    $transactionStatus = (string)($response->transaction_status ?? '');
    $fraudStatus = (string)($response->fraud_status ?? '');
    $grossAmount = (string)($response->gross_amount ?? '');
    $transactionId = isset($response->transaction_id) ? (string)$response->transaction_id : null;

    if ($grossAmount === '' || abs((float)$grossAmount - (float)$payment['total_amount']) > 0.01) {
        throw new RuntimeException('Nominal transaksi dari Midtrans tidak cocok dengan database.');
    }

    $newStatus = null;
    if ($transactionStatus === 'settlement') {
        $newStatus = 'paid';
    } elseif ($transactionStatus === 'capture' && $fraudStatus === 'accept') {
        $newStatus = 'paid';
    } elseif ($transactionStatus === 'expire') {
        $newStatus = 'expire';
    } elseif (in_array($transactionStatus, ['cancel', 'deny'], true)) {
        $newStatus = 'failed';
    }

    if ($newStatus === null) {
        $pdo->prepare('UPDATE payments SET gateway_status = ?, gateway_transaction_id = ? WHERE id = ?')
            ->execute([$transactionStatus !== '' ? $transactionStatus : 'unknown', $transactionId, $payment['id']]);
        flash('Status Midtrans saat ini: ' . ($transactionStatus ?: 'belum tersedia') . '. Pembayaran belum dapat ditandai lunas.', 'warning');
        redirect($returnPage);
    }

    $pdo->beginTransaction();
    $q = $pdo->prepare('SELECT * FROM payments WHERE id = ? FOR UPDATE');
    $q->execute([$payment['id']]);
    $currentPayment = $q->fetch();

    if (!$currentPayment) {
        throw new RuntimeException('Transaksi tidak ditemukan saat pembaruan database.');
    }

    if ($currentPayment['status'] !== 'paid' && $newStatus === 'paid') {
        // Hindari dua transaksi lunas untuk santri dan bulan tagihan yang sama.
        $dup = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE student_id = ? AND billing_month = ? AND status = 'paid' AND id <> ?");
        $dup->execute([$currentPayment['student_id'], $currentPayment['billing_month'], $currentPayment['id']]);
        if ((int)$dup->fetchColumn() > 0) {
            $pdo->prepare('UPDATE payments SET gateway_status = ?, gateway_transaction_id = ? WHERE id = ?')
                ->execute([$transactionStatus, $transactionId, $currentPayment['id']]);
            $pdo->commit();
            flash('Midtrans mengonfirmasi pembayaran, tetapi bulan ini sudah memiliki transaksi lunas lain. Periksa transaksi duplikat sebelum mengubah status.', 'error');
            redirect($returnPage);
        }
    }

    // Jangan turunkan status paid jika ada pemeriksaan ulang.
    if ($currentPayment['status'] !== 'paid') {
        $q = $pdo->prepare('UPDATE payments SET status = ?, gateway_status = ?, gateway_transaction_id = ? WHERE id = ?');
        $q->execute([$newStatus, $transactionStatus, $transactionId, $currentPayment['id']]);
    }
    $pdo->commit();

    if ($newStatus === 'paid') {
        flash('Status berhasil diverifikasi langsung dari Midtrans dan MySQL diperbarui menjadi paid.', 'success');
    } else {
        flash('Status transaksi diperbarui dari Midtrans: ' . $transactionStatus . '.', 'warning');
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Midtrans status sync failed for order ' . $orderId . ': ' . $e->getMessage());
    $errorMessage = $e->getMessage();
    if (stripos($errorMessage, "Transaction doesn't exist") !== false || stripos($errorMessage, 'HTTP status code: 404') !== false) {
        flash('Midtrans tidak menemukan order_id ini melalui Status API. Admin: ambil Transaction ID pada detail transaksi di Dashboard Sandbox, masukkan pada kolom Transaction ID, lalu periksa lagi. Pastikan akun/Server Key dan mode Sandbox sama.', 'error');
    } else {
        flash('Gagal memeriksa status Midtrans. Periksa Server Key Sandbox, mode, koneksi, dan log PHP.', 'error');
    }
}

redirect($returnPage);
