<?php
/**
 * API: Statistik Dashboard
 * GET /api/statistics
 * Smart Weighing Bridge
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
use Config\Database;

$db = new Database();
$pdo = $db->getConnection();

try {
    $result = [];

    // Total penimbangan
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM weighings");
    $result['total_weighings'] = (int)$stmt->fetchColumn();

    // Total berat
    $stmt = $pdo->query("SELECT COALESCE(SUM(weight_kg), 0) as total_w FROM weighings WHERE status = 'weighed'");
    $result['total_weight_kg'] = (float)$stmt->fetchColumn();

    // Jumlah kendaraan
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM vehicles");
    $result['total_vehicles'] = (int)$stmt->fetchColumn();

    // Jumlah penimbangan hari ini
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM weighings WHERE DATE(weighed_at) = CURDATE()");
    $result['today_weighings'] = (int)$stmt->fetchColumn();

    // Rata-rata berat
    $stmt = $pdo->query("SELECT COALESCE(AVG(weight_kg), 0) as avg_w FROM weighings WHERE status = 'weighed'");
    $result['avg_weight_kg'] = (float)$stmt->fetchColumn();

    // Data grafik - penimbangan harian (30 hari terakhir)
    $stmt = $pdo->query("
        SELECT DATE(weighed_at) as tgl, COUNT(*) as jumlah, COALESCE(SUM(weight_kg), 0) as total_berat
        FROM weighings
        WHERE weighed_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
        GROUP BY DATE(weighed_at)
        ORDER BY tgl ASC
    ");
    $chartDaily = [];
    while ($row = $stmt->fetch()) {
        $chartDaily[] = [
            'date'         => $row['tgl'],
            'count'        => (int)$row['jumlah'],
            'total_weight' => (float)$row['total_berat'],
        ];
    }
    $result['chart_daily'] = $chartDaily;

    // Data grafik - per minggu (12 minggu terakhir)
    $stmt = $pdo->query("
        SELECT YEARWEEK(weighed_at, 1) as tahun_minggu,
               MIN(DATE(weighed_at)) as tgl_awal,
               COUNT(*) as jumlah,
               COALESCE(SUM(weight_kg), 0) as total_berat
        FROM weighings
        WHERE weighed_at >= DATE_SUB(CURDATE(), INTERVAL 11 WEEK)
        GROUP BY YEARWEEK(weighed_at, 1)
        ORDER BY tahun_minggu ASC
    ");
    $chartWeekly = [];
    while ($row = $stmt->fetch()) {
        $chartWeekly[] = [
            'week'         => $row['tahun_minggu'],
            'date'         => $row['tgl_awal'],
            'count'        => (int)$row['jumlah'],
            'total_weight' => (float)$row['total_berat'],
        ];
    }
    $result['chart_weekly'] = $chartWeekly;

    // Data grafik - per bulan (12 bulan terakhir)
    $stmt = $pdo->query("
        SELECT DATE_FORMAT(weighed_at, '%Y-%m') as bulan,
               COUNT(*) as jumlah,
               COALESCE(SUM(weight_kg), 0) as total_berat
        FROM weighings
        WHERE weighed_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
        GROUP BY DATE_FORMAT(weighed_at, '%Y-%m')
        ORDER BY bulan ASC
    ");
    $chartMonthly = [];
    while ($row = $stmt->fetch()) {
        $chartMonthly[] = [
            'month'        => $row['bulan'],
            'count'        => (int)$row['jumlah'],
            'total_weight' => (float)$row['total_berat'],
        ];
    }
    $result['chart_monthly'] = $chartMonthly;

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data'    => $result,
    ]);
} catch (Throwable $e) {
    error_log("Statistics API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal mengambil statistik.']);
}