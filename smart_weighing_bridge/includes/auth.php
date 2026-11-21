<?php
/**
 * Autentikasi & otorisasi sederhana berbasis session PHP.
 * Role: admin (kelola data & pengaturan), operator (proses penimbangan).
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/security.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

/** Escape output. */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** User yang sedang login (array atau null). */
function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function user_role(): ?string
{
    $u = current_user();
    return $u['role'] ?? null;
}

function is_admin(): bool
{
    return user_role() === 'admin';
}

/** Set flash message (satu kali tampil). */
function flash(string $jenis, string $pesan): void
{
    $_SESSION['flash'] = ['jenis' => $jenis, 'pesan' => $pesan];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/* ----------------------------- CSRF ----------------------------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Cetak field tersembunyi CSRF di dalam form. */
function csrf_field(): void
{
    echo '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}

/** Hentikan request jika token CSRF tidak valid. */
function csrf_verify(): void
{
    $token = $_POST['_csrf'] ?? $_GET['_csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        echo 'Permintaan ditolak (token CSRF tidak valid).';
        exit;
    }
}

function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php');
        exit;
    }
}

/* ------------------------- Rate limit login ---------------------- */
function login_locked(): bool
{
    $until = (int) ($_SESSION['login_lock_until'] ?? 0);
    if ($until > time()) return true;
    if ($until > 0 && $until <= time()) {
        unset($_SESSION['login_attempts'], $_SESSION['login_lock_until']);
    }
    return false;
}

function remaining_login_attempts(): int
{
    return max(0, MAX_LOGIN_ATTEMPTS - (int) ($_SESSION['login_attempts'] ?? 0));
}

function login_failed(): void
{
    $_SESSION['login_attempts'] = (int) ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $_SESSION['login_lock_until'] = time() + LOGIN_LOCKOUT_SECONDS;
        $_SESSION['login_attempts'] = 0;
    }
}

function login_success_reset(): void
{
    unset($_SESSION['login_attempts'], $_SESSION['login_lock_until']);
}

/** Paksa login; jika belum, simpan tujuan lalu arahkan ke halaman login. */
function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/iot_cihuy/index.php';
        header('Location: index.php');
        exit;
    }

    // Akun bawaan wajib mengganti password sebelum memakai aplikasi.
    $u = current_user();
    if (!empty($u['must_change']) && basename($_SERVER['PHP_SELF']) !== 'ganti_password.php') {
        header('Location: ganti_password.php');
        exit;
    }
}

/** Paksa role tertentu (admin), jika tidak sesuai tampilkan 403. */
function require_role(string ...$roles): void
{
    require_login();
    if (!in_array(user_role(), $roles, true)) {
        http_response_code(403);
        require __DIR__ . '/header.php';
        echo '<section class="empty-state"><h2>Akses ditolak</h2>'
            . '<p>Anda tidak memiliki hak untuk membuka halaman ini.</p>'
            . '<a class="btn btn-primary" href="dashboard.php">Kembali ke dashboard</a></section>';
        require __DIR__ . '/footer.php';
        exit;
    }
}

/** Alihkan ke dashboard sesuai role setelah login. */
function redirect_by_role(): void
{
    header('Location: dashboard.php');
    exit;
}
