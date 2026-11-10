<?php
/**
 * Halaman Logout
 * Smart Weighing Bridge
 */

declare(strict_types=1);

session_start();

// Hapus semua data session
$_SESSION = [];

// Hapus cookie session jika ada
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

session_destroy();

header('Location: login.php');
exit;