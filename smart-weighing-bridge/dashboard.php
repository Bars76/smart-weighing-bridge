<?php
/**
 * Halaman Dashboard Utama
 * Smart Weighing Bridge
 */

declare(strict_types=1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

use Config\Database;

$db = new Database();
$pdo = $db->getConnection();

// Ambil statistik untuk dashboard
$statistics = [
    'total_vehicles'   => 0,
    'total_weighings'  => 0,
    'total_weight_kg'  => 0,
    'today_weighings'  => 0,
    'avg_weight_kg'    => 0,
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM vehicles");
    $statistics['total_vehicles'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM weighings");
    $statistics['total_weighings'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COALESCE(SUM(weight_kg), 0) as total_w FROM weighings WHERE status = 'weighed'");
    $statistics['total_weight_kg'] = (float) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM weighings WHERE DATE(weighed_at) = CURDATE()");
    $statistics['today_weighings'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COALESCE(AVG(weight_kg), 0) as avg_w FROM weighings WHERE status = 'weighed'");
    $statistics['avg_weight_kg'] = (float) $stmt->fetchColumn();
} catch (Throwable $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
}

// Ambil transaksi terbaru
$recentWeighings = [];
try {
    $stmt = $pdo->prepare("
        SELECT w.id, w.transaction_code, w.uid, w.plate, w.vehicle_type, 
               w.weight_kg, w.weighed_at, w.status, v.owner_name
        FROM weighings w
        LEFT JOIN vehicles v ON w.vehicle_id = v.id
        ORDER BY w.weighed_at DESC, w.id DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recentWeighings = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log("Dashboard recent weighings error: " . $e->getMessage());
}

// Ambil status perangkat
$deviceStatus = [
    'device_name' => '-',
    'connection_status' => 'unknown',
    'last_seen' => null,
    'components' => [],
];

try {
    $stmt = $pdo->prepare("
        SELECT d.device_name, d.connection_status, d.last_seen
        FROM devices d
        ORDER BY d.last_seen DESC, d.id DESC
        LIMIT 1
    ");
    $stmt->execute();
    $device = $stmt->fetch();

    if ($device) {
        $deviceStatus['device_name'] = htmlspecialchars($device['device_name']);
        $deviceStatus['connection_status'] = $device['connection_status'];
        $deviceStatus['last_seen'] = $device['last_seen'];

        $stmt2 = $pdo->prepare("
            SELECT dl.component_name, dl.component_status, dl.recorded_at
            FROM device_logs dl
            INNER JOIN (
                SELECT component_name, MAX(recorded_at) as max_recorded
                FROM device_logs
                GROUP BY component_name
            ) latest ON dl.component_name = latest.component_name 
                       AND dl.recorded_at = latest.max_recorded
            WHERE dl.device_id = :device_id
        ");
        $stmt2->execute([':device_id' => $device['id']]);
        $deviceStatus['components'] = $stmt2->fetchAll();
    }
} catch (Throwable $e) {
    error_log("Dashboard device status error: " . $e->getMessage());
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

$pageTitle = 'Dashboard';
$pageSubtitle = 'Monitoring dan pencatatan berat kendaraan berbasis IoT';
$activePage = 'dashboard';

include __DIR__ . '/layouts/header.php';
?>

            <!-- Kartu Statistik -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-blue">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Total Kendaraan Ditimbang</div>
                        <div class="stat-value" id="statTotalWeighings"><?php echo number_format($statistics['total_weighings']); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-orange">
                        <i class="fas fa-weight-hanging"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Total Berat Kendaraan</div>
                        <div class="stat-value" id="statTotalWeight"><?php echo number_format($statistics['total_weight_kg'], 2); ?> kg</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-green">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Kendaraan Hari Ini</div>
                        <div class="stat-value" id="statTodayWeighings"><?php echo number_format($statistics['today_weighings']); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-purple">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Rata-rata Berat</div>
                        <div class="stat-value" id="statAvgWeight"><?php echo number_format($statistics['avg_weight_kg'], 2); ?> kg</div>
                    </div>
                </div>
            </section>

            <!-- Monitoring Penimbangan Terkini -->
            <section class="monitoring-section">
                <div class="section-header">
                    <h2 class="section-title">Monitoring Penimbangan Terkini</h2>
                    <a href="pages/weighing-history.php" class="btn-sm">Lihat Semua</a>
                </div>
                <div class="monitoring-grid">
                    <div class="mon-card mon-weight">
                        <div class="mon-label">Berat Terkini</div>
                        <div class="mon-value" id="monCurrentWeight">- kg</div>
                        <div class="mon-unit">kg</div>
                    </div>
                    <div class="mon-card mon-rfid">
                        <div class="mon-label">UID RFID</div>
                        <div class="mon-value" id="monRfidUid">-</div>
                    </div>
                    <div class="mon-card mon-plate">
                        <div class="mon-label">Nomor Polisi</div>
                        <div class="mon-value" id="monPlate">-</div>
                    </div>
                    <div class="mon-card mon-type">
                        <div class="mon-label">Jenis Kendaraan</div>
                        <div class="mon-value" id="monVehicleType">-</div>
                    </div>
                    <div class="mon-card mon-time">
                        <div class="mon-label">Waktu Penimbangan</div>
                        <div class="mon-value" id="monWeighedAt">-</div>
                    </div>
                    <div class="mon-card mon-status">
                        <div class="mon-label">Status Transaksi</div>
                        <div class="mon-value status-badge" id="monStatus">-</div>
                    </div>
                </div>
            </section>

            <!-- Status Perangkat IoT -->
            <section class="device-section">
                <div class="section-header">
                    <h2 class="section-title">Status Perangkat IoT</h2>
                    <span class="device-connection" id="deviceConnBadge">
                        <?php echo deviceStatusLabel($deviceStatus['connection_status']); ?>
                    </span>
                </div>
                <div class="device-grid">
                    <div class="device-item">
                        <div class="device-icon"><i class="fas fa-microchip"></i></div>
                        <div class="device-info">
                            <div class="device-name">ESP32</div>
                            <div class="device-val" id="devEsp32"><?php echo deviceStatusLabel($deviceStatus['connection_status']); ?></div>
                        </div>
                    </div>
                    <div class="device-item">
                        <div class="device-icon"><i class="fas fa-id-card"></i></div>
                        <div class="device-info">
                            <div class="device-name">RFID RC522</div>
                            <div class="device-val" id="devRfid"><?php 
                                $rfid = array_filter($deviceStatus['components'], fn($c) => $c['component_name'] === 'RFID');
                                echo componentStatusLabel($rfid ? reset($rfid)['component_status'] : 'unknown'); 
                            ?></div>
                        </div>
                    </div>
                    <div class="device-item">
                        <div class="device-icon"><i class="fas fa-signal"></i></div>
                        <div class="device-info">
                            <div class="device-name">Sensor IR Masuk</div>
                            <div class="device-val" id="devIrIn"><?php 
                                $ir = array_filter($deviceStatus['components'], fn($c) => $c['component_name'] === 'IR_IN');
                                echo componentStatusLabel($ir ? reset($ir)['component_status'] : 'unknown'); 
                            ?></div>
                        </div>
                    </div>
                    <div class="device-item">
                        <div class="device-icon"><i class="fas fa-signal"></i></div>
                        <div class="device-info">
                            <div class="device-name">Sensor IR Keluar</div>
                            <div class="device-val" id="devIrOut"><?php 
                                $ir = array_filter($deviceStatus['components'], fn($c) => $c['component_name'] === 'IR_OUT');
                                echo componentStatusLabel($ir ? reset($ir)['component_status'] : 'unknown'); 
                            ?></div>
                        </div>
                    </div>
                    <div class="device-item">
                        <div class="device-icon"><i class="fas fa-weight-hanging"></i></div>
                        <div class="device-info">
                            <div class="device-name">HX711 Load Cell</div>
                            <div class="device-val" id="devHx711"><?php 
                                $hx = array_filter($deviceStatus['components'], fn($c) => $c['component_name'] === 'HX711');
                                echo componentStatusLabel($hx ? reset($hx)['component_status'] : 'unknown'); 
                            ?></div>
                        </div>
                    </div>
                    <div class="device-item">
                        <div class="device-icon"><i class="fas fa-cog"></i></div>
                        <div class="device-info">
                            <div class="device-name">Servo</div>
                            <div class="device-val" id="devServo"><?php 
                                $sv = array_filter($deviceStatus['components'], fn($c) => $c['component_name'] === 'Servo');
                                echo componentStatusLabel($sv ? reset($sv)['component_status'] : 'unknown'); 
                            ?></div>
                        </div>
                    </div>
                    <div class="device-item">
                        <div class="device-icon"><i class="fas fa-wifi"></i></div>
                        <div class="device-info">
                            <div class="device-name">Wi-Fi</div>
                            <div class="device-val" id="devWifi"><?php echo deviceStatusLabel($deviceStatus['connection_status']); ?></div>
                        </div>
                    </div>
                </div>
                <?php if ($deviceStatus['last_seen']): ?>
                <div class="device-last-seen">
                    <small>Terakhir dilihat: <?php echo htmlspecialchars($deviceStatus['last_seen']); ?></small>
                </div>
                <?php endif; ?>
            </section>

            <!-- Grafik -->
            <section class="charts-section">
                <div class="section-header">
                    <h2 class="section-title">Analitik Penimbangan</h2>
                    <div class="chart-filter">
                        <button class="filter-btn active" data-period="day">Hari</button>
                        <button class="filter-btn" data-period="week">Minggu</button>
                        <button class="filter-btn" data-period="month">Bulan</button>
                    </div>
                </div>
                <div class="chart-grid">
                    <div class="chart-card">
                        <canvas id="chartDaily"></canvas>
                    </div>
                    <div class="chart-card">
                        <canvas id="chartWeight"></canvas>
                    </div>
                </div>
            </section>

            <!-- Tabel Penimbangan Terbaru -->
            <section class="recent-table-section">
                <div class="section-header">
                    <h2 class="section-title">Penimbangan Terbaru</h2>
                    <div class="table-controls">
                        <input type="text" id="searchRecent" placeholder="Cari nomor polisi atau UID RFID...">
                        <select id="filterStatusRecent">
                            <option value="">Semua Status</option>
                            <option value="weighed">Selesai</option>
                            <option value="incomplete">Belum Lengkap</option>
                            <option value="void">Batalkan</option>
                        </select>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table class="data-table" id="tableRecent">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal & Waktu</th>
                                <th>UID RFID</th>
                                <th>Nomor Polisi</th>
                                <th>Jenis Kendaraan</th>
                                <th>Berat (kg)</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyRecent">
                            <?php $no = 1; foreach ($recentWeighings as $row): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($row['weighed_at']); ?></td>
                                <td><?php echo htmlspecialchars($row['uid']); ?></td>
                                <td><?php echo htmlspecialchars($row['plate']); ?></td>
                                <td><?php echo htmlspecialchars($row['vehicle_type']); ?></td>
                                <td><?php echo number_format($row['weight_kg'], 2); ?></td>
                                <td>
                                    <span class="badge status-<?php echo htmlspecialchars($row['status']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($row['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="pages/weighing-detail.php?id=<?php echo (int)$row['id']; ?>" class="btn-icon" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentWeighings)): ?>
                            <tr>
                                <td colspan="8" class="text-center">Belum ada data penimbangan.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-info">
                    Menampilkan <?php echo count($recentWeighings); ?> data terbaru
                </div>
            </section>

<script src="assets/js/dashboard.js"></script>
<script src="assets/js/charts.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const initialStats = {
            total_weighings: <?php echo $statistics['total_weighings']; ?>,
            total_weight_kg: <?php echo $statistics['total_weight_kg']; ?>,
            today_weighings: <?php echo $statistics['today_weighings']; ?>,
            avg_weight_kg: <?php echo $statistics['avg_weight_kg']; ?>,
        };
        const initialDevice = {
            connection_status: '<?php echo $deviceStatus['connection_status']; ?>',
            last_seen: '<?php echo $deviceStatus['last_seen'] ?? ''; ?>',
            components: <?php echo json_encode($deviceStatus['components']); ?>,
        };
        if (typeof initDashboard === 'function') {
            initDashboard(initialStats, initialDevice);
        }
        if (typeof initCharts === 'function') {
            initCharts();
        }
    });
</script>

</div> <!-- .main-body -->
</main>

<?php include __DIR__ . '/layouts/footer.php';