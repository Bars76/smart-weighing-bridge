<?php
/**
 * Proses autentikasi: validasi username/password terhadap tabel users.
 */
declare(strict_types=1);
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';

require_post();
csrf_verify();

if (login_locked()) {
    flash('error', 'Terlalu banyak percobaan gagal. Coba lagi dalam beberapa saat.');
    header('Location: index.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND status = "aktif" LIMIT 1');
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    session_regenerate_id(true);
    login_success_reset();
    $_SESSION['user'] = [
        'id'    => (int) $user['id_user'],
        'username' => $user['username'],
        'nama_lengkap' => $user['nama_lengkap'],
        'role'  => $user['role'],
        'must_change' => (int) ($user['must_change_password'] ?? 0) === 1,
    ];

    if (!empty($_SESSION['user']['must_change'])) {
        header('Location: ganti_password.php');
        exit;
    }

    // Arahkan kembali ke halaman yang diminta sebelumnya (lokal saja).
    $target = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
    unset($_SESSION['redirect_after_login']);
    if (!is_string($target) || !preg_match('#^/(iot_cihuy/)?[A-Za-z0-9_\-/.\?=]*$#', $target)) {
        $target = 'dashboard.php';
    }
    header('Location: ' . $target);
    exit;
}

login_failed();
flash('error', 'Username atau password salah.' . (login_locked()
    ? ' Akun dikunci sementara karena terlalu banyak percobaan.'
    : ' Sisa percobaan: ' . remaining_login_attempts() . '.'));
header('Location: index.php');
exit;
