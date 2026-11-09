<?php

declare(strict_types=1);
session_start();

const DB_HOST = '127.0.0.1';
const DB_NAME = 'santri_go';
const DB_USER = 'root';
const DB_PASS = '';

const APP_NAME = 'Mambaul Huda';
const APP_BASE = '/santri-go';
const APP_URL = '';


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


function app_setting(string $key, string $default = ''): string
{
    static $settings = null;
    global $pdo;
    if ($settings === null) {
        $settings = [];
        try {
            $rows = $pdo->query('SELECT setting_key, setting_value FROM app_settings')->fetchAll();
            foreach ($rows as $row) $settings[$row['setting_key']] = (string)$row['setting_value'];
        } catch (Throwable $e) {
            // Settings table is created when an administrator opens Pengaturan Pesantren.
        }
    }
    return (string)($settings[$key] ?? $default);
}
function app_school_name(): string
{
    return app_setting('school_name', APP_NAME) ?: APP_NAME;
}
function app_school_logo_url(): string
{
    $logo = app_setting('school_logo', '');
    if ($logo === '' || basename($logo) !== $logo) return '';
    $path = __DIR__ . '/uploads/' . $logo;
    return is_file($path) ? APP_BASE . '/uploads/' . rawurlencode($logo) : '';
}
function app_school_mark(): string
{
    $name = trim(app_school_name());
    $words = preg_split('/\s+/', $name) ?: [];
    $mark = '';
    foreach (array_slice($words, 0, 2) as $word) $mark .= strtoupper(substr($word, 0, 1));
    return $mark !== '' ? $mark : 'PP';
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
