<?php
/**
 * Halaman Riwayat Penimbangan
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

// Parameter filter
$searchPlate    = trim($_GET['plate'] ?? '');
$searchUid      = trim($_GET['uid'] ?? '');
$dateFrom       = trim($_GET['date_from'] ?? '');
$dateTo         = trim($_GET['date_to'] ?? '');
$vehicleType    = trim($_GET['vehicle_type'] ?? '');
$status         = trim($_GET['status'] ?? '');
$export         = isset($_GET['export']) ? $_GET['export'] : '';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Build query
$conditions = [];
$params = [];

if ($searchPlate !== '') {
    $conditions[] = "w.plate LIKE :plate";
    $params[':plate'] = "%{$searchPlate}%";
}
if ($searchUid !== '') {
    $conditions[] = "w.uid LIKE :uid";
    $params[':uid'] = "%{$searchUid}%";
}
if ($dateFrom !== '') {
    $conditions[] = "DATE(w.weighed_at) >= :date_from";
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $conditions[] = "DATE(w.weighed_at) <= :date_to";
    $params[':date_to'] = $dateTo;
}
if ($vehicleType !== '') {
    $conditions[] = "w.vehicle_type = :vehicle_type";
    $params[':vehicle_type'] = $vehicleType;
}
if ($status !== '') {
    $conditions[] = "w.status = :status";
    $params[':status'] = $status;
}

$whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Export CSV
if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="weighing_history_' . date('Ymd_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Kode Transaksi','Tanggal & Waktu','UID RFID','Nomor Polisi','Jenis Kendaraan','Berat (kg)','Status','Pemilik'], ';');

    try {
        $stmt = $pdo->prepare("
            SELECT w.transaction_code, w.weighed_at, w.uid, w.plate, w.vehicle_type,
                   w.weight_kg, w.status, v.owner_name
            FROM weighings w
            LEFT JOIN vehicles v ON w.vehicle_id = v.id
            {$whereSql}
            ORDER BY w.weighed_at DESC, w.id DESC
        ");
        $stmt->execute($params);
        while ($row = $stmt->fetch()) {
            fputcsv($out, [
                $row['transaction_code'],
                $row['weighed_at'],
                $row['uid'],
                $row['plate'],
                $row['vehicle_type'],
                number_format($row['weight_kg'], 2),
                ucfirst($row['status']),
                $row['owner_name'] ?? '-',
            ], ';');
        }
    } catch (Throwable $e) {
        error_log("CSV export error: " . $e->getMessage());
    }
    fclose($out);
    exit;
}

// Hitung total untuk pagination
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM weighings w LEFT JOIN vehicles v ON w.vehicle_id = v.id {$whereSql}");
    $stmt->execute($params);
    $totalRows = (int)$stmt->fetchColumn();
} catch (Throwable $e) {
    error_log("History count error: " . $e->getMessage());
    $totalRows = 0;
}
$totalPages = max(1, ceil($totalRows / $perPage));

// Ambil data
$weighings = [];
try {
    $stmt = $pdo->prepare("
        SELECT w.id, w.transaction_code, w.weighed_at, w.uid, w.plate, w.vehicle_type,
               w.weight_kg, w.status, v.owner_name
        FROM weighings w
        LEFT JOIN vehicles v ON w.vehicle_id = v.id
        {$whereSql}
        ORDER BY w.weighed_at DESC, w.id DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $weighings = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log("History list error: " . $e->getMessage());
    $weighings = [];
}

// Jenis kendaraan untuk filter dropdown
$vehicleTypes = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT vehicle_type FROM vehicles ORDER BY vehicle_type");
    $vehicleTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    error_log("Vehicle types error: " . $e->getMessage());
}

$pageTitle = 'Riwayat Penimbangan';
$pageSubtitle = 'Data transaksi penimbangan dari database MySQL';
$activePage = 'history';

include __DIR__ . '/../layouts/header.php';
?>

            <div style="padding:0 32px 16px;">
                <a href="?<?php echo http_build_query(array_filter($_GET, fn($v) => $v !== 'export')); ?>&export=csv" class="btn-sm" style="float:right;">
                    <i class="fas fa-download me-2"></i>Ekspor CSV
                </a>
                <div style="clear:both;"></div>
            </div>

            <div style="padding:0 32px 24px;">
                <form method="GET" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;background:#fff;padding:20px;border-radius:14px;border:1px solid #e2e8f0;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size:12px;font-weight:600;color:#64748b;">Nomor Polisi</label>
                        <input type="text" name="plate" value="<?php echo htmlspecialchars($searchPlate); ?>" placeholder="Cari nomor polisi..." style="width:100%;padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size:12px;font-weight:600;color:#64748b;">UID RFID</label>
                        <input type="text" name="uid" value="<?php echo htmlspecialchars($searchUid); ?>" placeholder="Cari UID RFID..." style="width:100%;padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size:12px;font-weight:600;color:#64748b;">Tanggal Awal</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" style="width:100%;padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size:12px;font-weight:600;color:#64748b;">Tanggal Akhir</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" style="width:100%;padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size:12px;font-weight:600;color:#64748b;">Jenis Kendaraan</label>
                        <select name="vehicle_type" style="width:100%;padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;">
                            <option value="">Semua</option>
                            <?php foreach ($vehicleTypes as $vt): ?>
                            <option value="<?php echo htmlspecialchars($vt); ?>" <?php echo $vehicleType === $vt ? 'selected' : ''; ?>><?php echo htmlspecialchars($vt); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size:12px;font-weight:600;color:#64748b;">Status</label>
                        <select name="status" style="width:100%;padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;">
                            <option value="">Semua</option>
                            <option value="weighed" <?php echo $status === 'weighed' ? 'selected' : ''; ?>>Selesai</option>
                            <option value="incomplete" <?php echo $status === 'incomplete' ? 'selected' : ''; ?>>Belum Lengkap</option>
                            <option value="void" <?php echo $status === 'void' ? 'selected' : ''; ?>>Dibatalkan</option>
                        </select>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="submit" style="padding:10px 20px;background:#3b82f6;color:#fff;border-radius:8px;font-weight:600;">Filter</button>
                        <a href="weighing-history.php" style="padding:10px 20px;background:#f1f5f9;color:#475569;border-radius:8px;font-weight:600;border:1px solid #e2e8f0;">Reset</a>
                    </div>
                </form>
            </div>

            <div style="padding:0 32px 40px;">
                <div style="margin-bottom:12px;font-size:13px;color:#64748b;">
                    Menampilkan <strong><?php echo count($weighings); ?></strong> dari <strong><?php echo $totalRows; ?></strong> data (halaman <?php echo $page; ?> dari <?php echo $totalPages; ?>)
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode Transaksi</th>
                                <th>Tanggal & Waktu</th>
                                <th>UID RFID</th>
                                <th>Nomor Polisi</th>
                                <th>Jenis Kendaraan</th>
                                <th>Berat (kg)</th>
                                <th>Status</th>
                                <th>Pemilik</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = $offset + 1; foreach ($weighings as $w): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><code style="font-size:12px;"><?php echo htmlspecialchars($w['transaction_code']); ?></code></td>
                                <td><?php echo htmlspecialchars($w['weighed_at']); ?></td>
                                <td><code style="font-size:12px;"><?php echo htmlspecialchars($w['uid']); ?></code></td>
                                <td><?php echo htmlspecialchars($w['plate']); ?></td>
                                <td><?php echo htmlspecialchars($w['vehicle_type']); ?></td>
                                <td><?php echo number_format($w['weight_kg'], 2); ?></td>
                                <td>
                                    <span class="badge status-<?php echo htmlspecialchars($w['status']); ?>" style="font-size:11px;padding:4px 10px;">
                                        <?php echo htmlspecialchars(ucfirst($w['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($w['owner_name'] ?? '-'); ?></td>
                                <td>
                                    <a href="weighing-detail.php?id=<?php echo (int)$w['id']; ?>" class="btn-icon" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($weighings)): ?>
                            <tr><td colspan="10" class="text-center">Belum ada data penimbangan yang cocok.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                <div style="display:flex;justify-content:center;align-items:center;gap:8px;margin-top:16px;">
                    <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="btn-icon" style="padding:6px 12px;font-size:13px;">&laquo; Sebelumnya</a>
                    <?php endif; ?>
                    <span style="font-size:13px;color:#64748b;">Halaman <?php echo $page; ?> dari <?php echo $totalPages; ?></span>
                    <?php if ($page < $totalPages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="btn-icon" style="padding:6px 12px;font-size:13px;">Selanjutnya &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

</div> <!-- .main-body -->
</main>

<?php include __DIR__ . '/../layouts/footer.php';