<?php
/**
 * Halaman Login Administrator
 * Smart Weighing Bridge
 */

declare(strict_types=1);

session_start();

// Jika sudah login, arahkan ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

use Config\Database;

$error = '';

// Generate CSRF token hanya pada permintaan awal (GET), jangan timpa saat POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $csrfToken;
} else {
    $csrfToken = $_SESSION['csrf_token'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF
    $postToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $postToken)) {
        $error = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Username dan password harus diisi.';
        } else {
            $db = new Database();
            $pdo = $db->getConnection();

            $stmt = $pdo->prepare(
                "SELECT id, username, password_hash, full_name, role, is_active 
                 FROM users 
                 WHERE username = :username 
                 LIMIT 1"
            );
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if (!$user['is_active']) {
                    $error = 'Akun ini telah dinonaktifkan. Hubungi administrator.';
                } else {
                    // Regenerasi session ID untuk proteksi session fixation
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['logged_in_at'] = time();

                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                $error = 'Username atau password salah.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Weighing Bridge</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 20px;
        }
        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            padding: 40px 32px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-header h1 {
            color: #0f172a;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .login-header p {
            color: #64748b;
            margin: 8px 0 0;
            font-size: 14px;
        }
        .brand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            background: #3b82f6;
            border-radius: 12px;
            margin: 0 auto 16px;
            font-size: 24px;
            color: #ffffff;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-weight: 600;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            color: #0f172a;
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: #0f172a;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-login:hover {
            background: #1e293b;
        }
        .btn-login:active {
            transform: translateY(1px);
        }
        .error-msg {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }
        .footer-hint {
            margin-top: 28px;
            text-align: center;
            color: #64748b;
            font-size: 13px;
        }
        .footer-hint a {
            color: #3b82f6;
            text-decoration: none;
        }
        .footer-hint a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="brand-icon">
                <i class="fas fa-weight-scale"></i>
            </div>
            <div class="login-header">
                <h1>Smart Weighing Bridge</h1>
                <p>Sistem Monitoring Pencatatan Berat Kendaraan</p>
            </div>

            <?php if ($error): ?>
                <div class="error-msg" id="errorMsg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" autocomplete="username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Masuk
                </button>
            </form>

            <div class="footer-hint">
                <p>Belum punya akun administrator? <br>
                Buat akun pertama melalui phpMyAdmin pada tabel <code>users</code> atau gunakan akun default:<br>
                <strong>Username:</strong> admin | <strong>Password:</strong> password<br>
                <em>Segera ganti password default setelah login pertama kali!</em></p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const errorMsg = document.getElementById('errorMsg');
            if (errorMsg && errorMsg.textContent.trim() !== '') {
                errorMsg.style.display = 'block';
            }
        });
    </script>
</body>
</html>