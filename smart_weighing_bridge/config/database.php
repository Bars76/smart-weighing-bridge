<?php
/**
 * Koneksi database PDO untuk Smart Weighing Bridge.
 * Kredensial dapat di-set lewat environment variable SW_DB_HOST / SW_DB_NAME /
 * SW_DB_USER / SW_DB_PASS. Default hanya untuk XAMPP lokal.
 */
declare(strict_types=1);
require_once __DIR__ . '/security.php';

$env = static function (string $key, string $default): string {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : (string) $value;
};

define('DB_HOST', $env('SW_DB_HOST', '127.0.0.1'));
define('DB_NAME', $env('SW_DB_NAME', 'smart_weighing'));
define('DB_USER', $env('SW_DB_USER', 'root'));
define('DB_PASS', $env('SW_DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Detail error hanya ditulis ke log, tidak ditampilkan ke pengunjung.
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    echo '<!doctype html><meta charset="utf-8"><title>Kesalahan Server</title>';
    echo '<h1>Kesalahan server</h1><p>Silakan coba beberapa saat lagi atau hubungi administrator.</p>';
    exit;
}

/** Ambil nilai pengaturan berdasarkan nama. */
function pengaturan(string $nama, string $default = ''): string
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT nilai_pengaturan FROM pengaturan WHERE nama_pengaturan = ? LIMIT 1');
    $stmt->execute([$nama]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : (string) $value;
}

/** Simpan/perbarui nilai pengaturan. */
function set_pengaturan(string $nama, string $nilai): void
{
    global $pdo;
    $stmt = $pdo->prepare(
        'INSERT INTO pengaturan (nama_pengaturan, nilai_pengaturan) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE nilai_pengaturan = VALUES(nilai_pengaturan)'
    );
    $stmt->execute([$nama, $nilai]);
}
