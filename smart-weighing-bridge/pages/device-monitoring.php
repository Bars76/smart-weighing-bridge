<?php
/**
 * Halaman Monitoring Perangkat IoT
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

// Ambil semua perangkat dengan status terakhir
$devices = [];
try {
    $stmt = $pdo->query("
        SELECT d.id, d.device_name, d.device_token, d.last_seen, d.connection_status,
               d.created_at, d.updated_at
        FROM devices d
        ORDER BY d.last_seen DESC, d.id DESC
    ");
    $devices = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log("Device monitoring list error: " . $e->getMessage());
}

// Ambil log komponen terbaru per perangkat
$deviceLogs = [];
try {
    $stmt = $pdo->query("
        SELECT dl.id, dl.device_id, dl.component_name, dl.component_status, dl.recorded_at,
               d.device_name
        FROM device_logs dl
        INNER JOIN devices d ON dl.device_id = d.id
        ORDER BY dl.recorded_at DESC
    ");
    $deviceLogs = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log("Device logs error: " . $e->getMessage());
}

// Grup log berdasarkan device_id
$logsByDevice = [];
foreach ($deviceLogs as $log) {
    $logsByDevice[$log['device_id']][] = $log;
}

// Hitung waktu terakhir device terlihat untuk menentukan online/offline
// Jika last_seen lebih dari 5 menit lalu, anggap offline
$offlineThreshold = 5 * 60; // 5 menit dalam detik

function isDeviceOnline(string $lastSeen, int $thresholdSeconds): bool
{
    if ($lastSeen === null || $lastSeen === '') {
        return false;
    }
    $lastSeenTs = strtotime($lastSeen);
    if ($lastSeenTs === false) {
        return false;
    }
    return (time() - $lastSeenTs) <= $thresholdSeconds;
}

function deviceStatusLabel(string $status): string
{
    return match ($status) {
        'online' => 'Online',
        'offline' => 'Offline',
        default => 'Tidak diketahui',
    };
}

function componentStatusLabel(string $status): string
{
    return match ($status) {
        'active'  => 'Aktif',
        'inactive'=> 'Tidak Aktif',
        'open'    => 'Terbuka',
        'closed'  => 'Tertutup',
        'unknown' => 'Belum Tersedia',
        default   => 'Belum Tersedia',
    };
}

function componentBadgeClass(string $status): string
{
    return match ($status) {
        'active'  => 'badge-success',
        'inactive'=> 'badge-danger',
        'open'    => 'badge-warning',
        'closed'  => 'badge-success',
        'unknown' => 'badge-secondary',
        default   => 'badge-secondary',
    };
}

$pageTitle = 'Monitoring Perangkat';
$pageSubtitle = 'Pantau status ESP32 dan seluruh komponen IoT secara real-time';
$activePage = 'devices';

include __DIR__ . '/../layouts/header.php';
?>

            <div style="padding:0 32px 24px;">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                    <div>
                        <span class="badge" style="
                            background: #f1f5f9;
                            color: #334155;
                            padding: 6px 12px;
                            font-size: 12px;
                            border: 1px solid #e2e8f0;
                        ">
                            Total Perangkat: <strong><?php echo count($devices); ?></strong>
                        </span>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn-sm" onclick="refreshDevices()">
                            <i class="fas fa-refresh me-2"></i>Segarkan
                        </button>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Status Perangkat -->
            <div style="padding:0 32px 24px;">
                <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
                    <div class="stat-card">
                        <div class="stat-icon bg-blue">
                            <i class="fas fa-microchip"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Perangkat Terdaftar</div>
                            <div class="stat-value"><?php echo count($devices); ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-green">
                            <i class="fas fa-circle-check"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Online</div>
                            <div class="stat-value" id="countOnline">0</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-danger">
                            <i class="fas fa-circle-exclamation"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Offline</div>
                            <div class="stat-value" id="countOffline">0</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-purple">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Log Komponen</div>
                            <div class="stat-value"><?php echo count($deviceLogs); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Perangkat -->
            <section class="recent-table-section" style="padding:0 32px 40px;">
                <div class="section-header">
                    <h2 class="section-title">Daftar Perangkat IoT</h2>
                </div>
                <div class="table-wrapper">
                    <table class="data-table" id="tableDevices">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Perangkat</th>
                                <th>Token Perangkat</th>
                                <th>Status Koneksi</th>
                                <th>Terakhir Dilihat</th>
                                <th>Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyDevices">
                            <?php if (empty($devices)): ?>
                            <tr><td colspan="7" class="text-center">Belum ada perangkat terdaftar. ESP32 belum mengirim heartbeat.</td></tr>
                            <?php else: $no = 1; foreach ($devices as $dev): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($dev['device_name'] ?? '-'); ?></td>
                                <td>
                                    <code style="font-size:11px;background:#f1f5f9;padding:4px 6px;border-radius:4px;color:#0f172a;">
                                        <?php echo substr(htmlspecialchars($dev['device_token'] ?? ''), 0, 20) . '...'; ?>
                                    </code>
                                </td>
                                <td>
                                    <?php
                                    $isOnline = isDeviceOnline($dev['last_seen'] ?? '', $offlineThreshold);
                                    $dbStatus = $dev['connection_status'] ?? 'unknown';
                                    $displayStatus = $isOnline ? 'online' : ($dbStatus === 'online' ? 'offline' : $dbStatus);
                                    ?>
                                    <span class="badge <?php echo $displayStatus === 'online' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $isOnline ? 'Online' : 'Offline'; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($dev['last_seen'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($dev['created_at'] ?? '-'); ?></td>
                                <td>
                                    <button class="btn-icon" onclick="viewDeviceLogs(<?php echo (int)$dev['id']; ?>, '<?php echo htmlspecialchars($dev['device_name']); ?>')" title="Lihat Log Komponen">
                                        <i class="fas fa-list"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Log Komponen per Perangkat (Modal sederhana dengan section yang ditampilkan) -->
            <section style="padding:0 32px 40px;display:none;" id="deviceLogsSection">
                <div class="section-header">
                    <h2 class="section-title" id="logsDeviceTitle">Log Komponen</h2>
                    <button class="btn-sm" onclick="closeDeviceLogs()" style="margin-left:auto;">
                        <i class="fas fa-times me-2"></i>Tutup
                    </button>
                </div>
                <div class="table-wrapper">
                    <table class="data-table" id="tableDeviceLogs">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Komponen</th>
                                <th>Status</th>
                                <th>Waktu Catat</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyDeviceLogs">
                        </tbody>
                    </table>
                </div>
            </section>

</div> <!-- .main-body -->
</main>

<style>
.stat-icon.bg-purple { background: linear-gradient(135deg, #a855f7, #9333ea); }
</style>

<script>
    // Hitung online/offline
    function updateDeviceCounts() {
        let online = 0, offline = 0;
        document.querySelectorAll('#tbodyDevices tr').forEach(tr => {
            const badge = tr.querySelector('.badge');
            if (badge) {
                if (badge.textContent.includes('Online')) online++;
                else offline++;
            }
        });
        const elOnline = document.getElementById('countOnline');
        const elOffline = document.getElementById('countOffline');
        if (elOnline) elOnline.textContent = online;
        if (elOffline) elOffline.textContent = offline;
    }

    function refreshDevices() {
        // Reload halaman untuk mengambil data terbaru dari server
        location.reload();
    }

    function viewDeviceLogs(deviceId, deviceName) {
        const section = document.getElementById('deviceLogsSection');
        const title = document.getElementById('logsDeviceTitle');
        const tbody = document.getElementById('tbodyDeviceLogs');

        title.textContent = 'Log Komponen - ' + deviceName;

        // Ambil log dari data yang sudah ada di halaman (dikirim oleh PHP)
        // Kita filter berdasarkan device_id yang disimpan di atribut data
        const allLogs = <?php echo json_encode($deviceLogs); ?>;
        const filtered = allLogs.filter(log => log.device_id == deviceId);

        tbody.innerHTML = '';
        if (filtered.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">Belum ada log komponen untuk perangkat ini.</td></tr>';
        } else {
            filtered.forEach((log, index) => {
                const statusClass = {
                    'active': 'badge-success',
                    'inactive': 'badge-danger',
                    'open': 'badge-warning',
                    'closed': 'badge-success',
                    'unknown': 'badge-secondary'
                }[log.component_status] || 'badge-secondary';

                const statusLabel = {
                    'active': 'Aktif',
                    'inactive': 'Tidak Aktif',
                    'open': 'Terbuka',
                    'closed': 'Tertutup',
                    'unknown': 'Belum Tersedia'
                }[log.component_status] || 'Belum Tersedia';

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${escapeHtml(log.component_name)}</td>
                    <td><span class="badge ${statusClass}">${statusLabel}</span></td>
                    <td>${escapeHtml(log.recorded_at)}</td>
                `;
                tbody.appendChild(row);
            });
        }

        section.style.display = 'block';
        section.scrollIntoView({ behavior: 'smooth' });
    }

    function closeDeviceLogs() {
        document.getElementById('deviceLogsSection').style.display = 'none';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateDeviceCounts();
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php';