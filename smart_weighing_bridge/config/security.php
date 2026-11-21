<?php
/**
 * Konfigurasi keamanan terpusat.
 * Nilai dapat di-set lewat environment variable sebelum aplikasi dipublikasikan.
 */
declare(strict_types=1);

// Batas percobaan login (dipakai oleh includes/auth.php).
if (!defined('MAX_LOGIN_ATTEMPTS')) {
    define('MAX_LOGIN_ATTEMPTS', 5);
    define('LOGIN_LOCKOUT_SECONDS', 60);
}

// Mode debug. Aktifkan hanya saat development.
if (!defined('APP_DEBUG')) {
    $envDebug = getenv('SW_DEBUG');
    define('APP_DEBUG', $envDebug !== false
        ? filter_var($envDebug, FILTER_VALIDATE_BOOLEAN)
        : false);
}

if (!APP_DEBUG) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}
