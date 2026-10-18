<?php

declare(strict_types=1);
session_start();

const DB_HOST = '127.0.0.1';
const DB_NAME = 'pesantren_spp';
const DB_USER = 'root';
const DB_PASS = '';

const APP_NAME = 'Mambaul Huda';
const APP_BASE = '/digitalisasi-spp-pesantren';
// URL absolut untuk tujuan kembali setelah pembayaran Midtrans.
// Sesuaikan jika aplikasi diakses melalui host/path yang berbeda.
const APP_URL = 'https://checks-yet-aside-screens.trycloudflare.com/digitalisasi-spp-pesantren';

const MIDTRANS_SERVER_KEY = ''; // ganti dengan Server Key Sandbox yang baru
const MIDTRANS_CLIENT_KEY = ''; // ganti dengan Client Key Sandbox yang baru
const MIDTRANS_IS_PRODUCTION = false;

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Koneksi database gagal. Periksa pengaturan config.php dan pastikan database sudah diimpor.');
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function rupiah($amount): string
{
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}
function redirect(string $path): never
{
    header('Location: ' . APP_BASE . '/' . ltrim($path, '/'));
    exit;
}
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_check(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Silakan kembali dan coba lagi.');
    }
}
function require_login(): void
{
    if (empty($_SESSION['user'])) redirect('login.php');
}
function require_role(string $role): void
{
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('Akses tidak diizinkan.');
    }
}
function current_student_id(PDO $pdo): int
{
    $q = $pdo->prepare('SELECT id FROM students WHERE user_id = ? AND active = 1');
    $q->execute([$_SESSION['user']['id']]);
    $id = $q->fetchColumn();
    if (!$id) {
        http_response_code(403);
        exit('Data santri belum terhubung ke akun.');
    }
    return (int)$id;
}
function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}
function month_label(string $date): string
{
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $ts = strtotime($date);
    return $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}
