<?php

/**
 * Endpoint notifikasi Midtrans. Daftarkan URL HTTPS ini di dashboard Midtrans.
 * Untuk produksi, uji verifikasi signature dan status sesuai dokumentasi resmi terbaru.
 */
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || empty($data['order_id']) || empty($data['status_code']) || !isset($data['gross_amount']) || empty($data['signature_key'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid notification']);
    exit;
}
$expected = hash('sha512', (string)$data['order_id'] . (string)$data['status_code'] . (string)$data['gross_amount'] . MIDTRANS_SERVER_KEY);
if (!MIDTRANS_SERVER_KEY || !hash_equals($expected, (string)$data['signature_key'])) {
    http_response_code(403);
    echo json_encode(['message' => 'Invalid signature']);
    exit;
}
$orderId = (string)$data['order_id'];
$transactionStatus = (string)($data['transaction_status'] ?? '');
$fraudStatus = (string)($data['fraud_status'] ?? '');
$status = 'pending';
if (in_array($transactionStatus, ['capture', 'settlement'], true) && ($fraudStatus === '' || $fraudStatus === 'accept')) $status = 'paid';
elseif ($transactionStatus === 'expire') $status = 'expire';
elseif (in_array($transactionStatus, ['cancel', 'deny'], true)) $status = 'failed';
try {
    $pdo->beginTransaction();
    $q = $pdo->prepare("SELECT * FROM payments WHERE order_id=? AND method='midtrans' FOR UPDATE");
    $q->execute([$orderId]);
    $p = $q->fetch();
    if (!$p) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['message' => 'Order not found']);
        exit;
    }
    if (abs((float)$p['total_amount'] - (float)$data['gross_amount']) > 0.01) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['message' => 'Amount mismatch']);
        exit;
    }
    // Avoid overwriting a paid state with later non-paid notifications.
    if ($p['status'] !== 'paid') {
        if ($status === 'paid') {
            $dup = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE student_id=? AND billing_month=? AND status='paid' AND id<>?");
            $dup->execute([$p['student_id'], $p['billing_month'], $p['id']]);
            if ((int)$dup->fetchColumn() > 0) {
                $pdo->prepare("UPDATE payments SET gateway_status=?,gateway_transaction_id=? WHERE id=?")->execute([$transactionStatus, $data['transaction_id'] ?? null, $p['id']]);
                $pdo->commit();
                echo json_encode(['message' => 'Payment notified; duplicate month requires manual review']);
                exit;
            }
        }
        $q = $pdo->prepare('UPDATE payments SET status=?,gateway_status=?,gateway_transaction_id=? WHERE id=?');
        $q->execute([$status, $transactionStatus, $data['transaction_id'] ?? null, $p['id']]);
    }
    $pdo->commit();
    echo json_encode(['message' => 'OK']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['message' => 'Server error']);
}
