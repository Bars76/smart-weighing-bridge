<?php
/**
 * Halaman Pengaturan
 * Smart Weighing Bridge
 */

declare(strict_types=1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

use Config\Database;

$db = new Database();
$pdo = $db->getConnection();

$message = '';
$messageType = '';
$csrfToken = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

// Ambil data user yang sedang login
$userId = (int)($_SESSION['user_id'] ?? 0);
$userData = null;
try {
    $stmt = $pdo->prepare("SELECT id, username, full_name, role, is_active, created_at FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $userData = $stmt->fetch();
} catch (Throwable $e) {
    error_log("Settings user fetch error: " . $e->getMessage());
}

// Ambil daftar device token
$deviceTokens = [];
try {
    $stmt = $pdo->query("SELECT id, device_name, device_token, last_seen, connection_status, created_at FROM devices ORDER BY created_at DESC");
    $deviceTokens = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log("Settings device tokens error: " . $e->getMessage());
}

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $postToken)) {
        $message = 'Token keamanan tidak valid.';
        $messageType = 'danger';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'change_password') {
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                    $message = 'Semua field password harus diisi.';
                    $messageType = 'warning';
                } elseif ($newPassword !== $confirmPassword) {
                    $message = 'Password baru dan konfirmasi tidak cocok.';
                    $messageType = 'warning';
                } elseif (strlen($newPassword) < 6) {
                    $message = 'Password baru minimal 6 karakter.';
                    $messageType = 'warning';
                } else {
                    // Verifikasi password lama
                    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id LIMIT 1");
                    $stmt->execute([':id' => $userId]);
                    $user = $stmt->fetch();

                    if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                        $message = 'Password saat ini salah.';
                        $messageType = 'danger';
                    } else {
                        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id");
                        $stmt->execute([':hash' => $newHash, ':id' => $userId]);
                        $message = 'Password berhasil diubah.';
                        $messageType = 'success';
                    }
                }
            } elseif ($action === 'add_device_token') {
                $deviceName = trim($_POST['device_name'] ?? '');
                $deviceToken = trim($_POST['device_token'] ?? '');

                if ($deviceName === '' || $deviceToken === '') {
                    $message = 'Nama perangkat dan token harus diisi.';
                    $messageType = 'warning';
                } else {
                    // Cek duplikasi token
                    $chk = $pdo->prepare("SELECT id FROM devices WHERE device_token = :token LIMIT 1");
                    $chk->execute([':token' => $deviceToken]);
                    if ($chk->fetch()) {
                        $message = 'Token perangkat sudah terdaftar.';
                        $messageType = 'warning';
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO devices (device_name, device_token, last_seen, connection_status)
                            VALUES (:name, :token, NULL, 'unknown')
                        ");
                        $stmt->execute([
                            ':name'  => $deviceName,
                            ':token' => $deviceToken,
                        ]);
                        $message = 'Token perangkat berhasil ditambahkan.';
                        $messageType = 'success';
                        // Refresh data
                        $stmt = $pdo->query("SELECT id, device_name, device_token, last_seen, connection_status, created_at FROM devices ORDER BY created_at DESC");
                        $deviceTokens = $stmt->fetchAll();
                    }
                }
            } elseif ($action === 'delete_device_token') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    $message = 'ID perangkat tidak valid.';
                    $messageType = 'danger';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM devices WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    $message = 'Token perangkat berhasil dihapus.';
                    $messageType = 'success';
                    $stmt = $pdo->query("SELECT id, device_name, device_token, last_seen, connection_status, created_at FROM devices ORDER BY created_at DESC");
                    $deviceTokens = $stmt->fetchAll();
                }
            }
        } catch (Throwable $e) {
            error_log("Settings action error: " . $e->getMessage());
            $message = 'Terjadi kesalahan pada server.';
            $messageType = 'danger';
        }

        // Regenerate CSRF token setelah aksi berhasil
        if ($messageType === 'success') {
            $csrfToken = bin2hex(random_bytes(32));
            $_SESSION['csrf_token'] = $csrfToken;
        }
    }
}

$pageTitle = 'Pengaturan';
$pageSubtitle = 'Pengaturan akun dan perangkat IoT';
$activePage = 'settings';

include __DIR__ . '/../layouts/header.php';
?>

            <div style="padding:0 32px 40px;">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px;">
                    
                    <!-- Form Ganti Password -->
                    <div class="card">
                        <h3 style="font-size:16px;font-weight:700;margin-bottom:16px;color:#0f172a;">
                            <i class="fas fa-key me-2"></i>Ubah Password
                        </h3>
                        <?php if ($message && strpos($message, 'password') !== false): ?>
                        <div class="alert" style="<?php echo $messageType === 'success' ? 'background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;' : 'background:#fef2f2;border:1px solid #fecaca;color:#991b1b;'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                        <?php endif; ?>
                        <form method="POST" id="passwordForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="change_password">

                            <div class="form-group">
                                <label>Password Saat Ini <span style="color:#ef4444;">*</span></label>
                                <input type="password" name="current_password" required autocomplete="current-password">
                            </div>
                            <div class="form-group">
                                <label>Password Baru <span style="color:#ef4444;">*</span></label>
                                <input type="password" name="new_password" required autocomplete="new-password" minlength="6">
                            </div>
                            <div class="form-group">
                                <label>Konfirmasi Password Baru <span style="color:#ef4444;">*</span></label>
                                <input type="password" name="confirm_password" required autocomplete="new-password">
                            </div>
                            <button type="submit" class="btn-save" style="width:100%;">
                                <i class="fas fa-save me-2"></i>Ubah Password
                            </button>
                        </form>
                    </div>

                    <!-- Manajemen Device Token -->
                    <div class="card">
                        <h3 style="font-size:16px;font-weight:700;margin-bottom:16px;color:#0f172a;">
                            <i class="fas fa-microchip me-2"></i>Token Perangkat IoT
                        </h3>
                        <p style="font-size:13px;color:#64748b;margin-bottom:16px;">
                            Token digunakan oleh ESP32 untuk mengautentikasi setiap request ke API.
                        </p>

                        <form method="POST" id="deviceTokenForm" style="margin-bottom:20px;">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="add_device_token">

                            <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:10px;align-items:end;">
                                <div class="form-group" style="margin-bottom:0;">
                                    <label>Nama Perangkat</label>
                                    <input type="text" name="device_name" required placeholder="ESP32-Weighbridge-01">
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label>Token</label>
                                    <input type="text" name="device_token" required placeholder="contoh: WB_TOKEN_2026">
                                </div>
                                <button type="submit" class="btn-save" style="width:100%;">
                                    <i class="fas fa-plus me-2"></i>Tambah
                                </button>
                            </div>
                        </form>

                        <div class="table-wrapper" style="margin:0;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Perangkat</th>
                                        <th>Token</th>
                                        <th>Terakhir Dilihat</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($deviceTokens)): ?>
                                    <tr><td colspan="6" class="text-center">Belum ada token perangkat terdaftar.</td></tr>
                                    <?php else: $no = 1; foreach ($deviceTokens as $dt): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($dt['device_name'] ?? '-'); ?></td>
                                        <td><code style="font-size:11px;"><?php echo substr(htmlspecialchars($dt['device_token'] ?? ''), 0, 25) . '...'; ?></code></td>
                                        <td><?php echo htmlspecialchars($dt['last_seen'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge <?php echo ($dt['connection_status'] ?? 'unknown') === 'online' ? 'badge-success' : 'badge-secondary'; ?>">
                                                <?php echo ucfirst($dt['connection_status'] ?? 'unknown'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus token ini?')">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="action" value="delete_device_token">
                                                <input type="hidden" name="id" value="<?php echo (int)$dt['id']; ?>">
                                                <button type="submit" class="btn-icon btn-delete" style="padding:4px;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- Informasi Sistem -->
                <div style="margin-top:24px;">
                    <div class="card">
                        <h3 style="font-size:16px;font-weight:700;margin-bottom:16px;color:#0f172a;">
                            <i class="fas fa-info-circle me-2"></i>Informasi Sistem
                        </h3>
                        <table class="data-table" style="font-size:13px;">
                            <tbody>
                                <tr>
                                    <td style="width:30%;font-weight:600;color:#64748b;">Nama Aplikasi</td>
                                    <td>Smart Weighing Bridge</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:600;color:#64748b;">Versi</td>
                                    <td>1.0.0</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:600;color:#64748b;">Backend</td>
                                    <td>PHP Native (PDO + MySQL)</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:600;color:#64748b;">Database</td>
                                    <td>smart_weighing_bridge</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:600;color:#64748b;">Pengguna Saat Ini</td>
                                    <td><?php echo htmlspecialchars($userData['full_name'] ?? '-') . ' (' . htmlspecialchars($userData['username'] ?? '-') . ')'; ?></td>
                                </tr>
                                <tr>
                                    <td style="font-weight:600;color:#64748b;">Role</td>
                                    <td><?php echo htmlspecialchars(ucfirst($userData['role'] ?? 'operator')); ?></td>
                                </tr>
                                <tr>
                                    <td style="font-weight:600;color:#64748b;">Daftar pada</td>
                                    <td><?php echo htmlspecialchars($userData['created_at'] ?? '-'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<style>
.card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:24px;}
.form-group{margin-bottom:16px;}
.form-group label{display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:#334155;}
.form-group input{width:100%;padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;color:#0f172a;background:#fff;}
.btn-save{padding:10px 20px;background:#3b82f6;color:#fff;border-radius:8px;font-weight:600;font-size:13px;display:inline-flex;align-items:center;gap:6px;transition:background .2s;}
.btn-save:hover{background:#2563eb;}
.btn-icon{width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;color:#64748b;background:#f1f5f9;font-size:14px;margin:0 2px;transition:all .2s;}
.btn-icon.btn-delete:hover{background:#fee2e2;color:#991b1b;}
.me-2{margin-right:6px;}
</style>

</div> <!-- .main-body -->
</main>

<?php include __DIR__ . '/../layouts/footer.php';