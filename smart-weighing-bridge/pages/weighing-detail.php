<?php
/**
 * Halaman Detail Penimbangan
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

$id = (int)($_GET['id'] ?? 0);
$weighing = null;

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT w.*, v.owner_name
            FROM weighings w
            LEFT JOIN vehicles v ON w.vehicle_id = v.id
            WHERE w.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $weighing = $stmt->fetch();
    } catch (Throwable $e) {
        error_log("Weighing detail error: " . $e->getMessage());
    }
}

if (!$weighing) {
    header('Location: weighing-history.php?msg=notfound');
    exit;
}

$pageTitle = 'Detail Penimbangan';
$pageSubtitle = 'Informasi lengkap transaksi penimbangan';
$activePage = 'history';

include __DIR__ . '/../layouts/header.php';
?>

            <div style="padding:0 32px 40px;">
                <div class="card" style="max-width:700px;margin:0 auto;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;">
                        <div>
                            <h2 style="font-size:18px;font-weight:700;color:#0f172a;margin-bottom:4px;">Transaksi #<?php echo htmlspecialchars($weighing['transaction_code']); ?></h2>
                            <div class="badge status-<?php echo htmlspecialchars($weighing['status']); ?>" style="font-size:11px;padding:6px 12px;">
                                <?php echo htmlspecialchars(ucfirst($weighing['status'])); ?>
                            </div>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px;">
                        <div class="detail-row">
                            <div class="detail-label">Tanggal & Waktu</div>
                            <div class="detail-value"><?php echo htmlspecialchars($weighing['weighed_at']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">UID RFID</div>
                            <div class="detail-value"><code><?php echo htmlspecialchars($weighing['uid']); ?></code></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Nomor Polisi</div>
                            <div class="detail-value"><?php echo htmlspecialchars($weighing['plate']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Jenis Kendaraan</div>
                            <div class="detail-value"><?php echo htmlspecialchars($weighing['vehicle_type']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Berat</div>
                            <div class="detail-value" style="font-size:24px;font-weight:700;color:#2563eb;"><?php echo number_format($weighing['weight_kg'], 2); ?> kg</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Pemilik Kendaraan</div>
                            <div class="detail-value"><?php echo htmlspecialchars($weighing['owner_name'] ?? '-'); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Status Transaksi</div>
                            <div class="detail-value"><?php echo htmlspecialchars(ucfirst($weighing['status'])); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">ID Kendaraan Terkait</div>
                            <div class="detail-value"><?php echo htmlspecialchars($weighing['vehicle_id'] ?? '-'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

<style>
.card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:24px;}
.detail-row{display:flex;flex-direction:column;gap:6px;}
.detail-label{font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.3px;}
.detail-value{font-size:14px;color:#334155;}
.detail-value code{background:#f1f5f9;padding:2px 6px;border-radius:4px;color:#0f172a;}
</style>

</div> <!-- .main-body -->
</main>

<?php include __DIR__ . '/../layouts/footer.php';