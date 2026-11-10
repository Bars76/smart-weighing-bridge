<?php
/**
 * API: Data Kendaraan
 * GET /api/vehicles
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
    $stmt = $pdo->query("
        SELECT id, vehicle_id, uid, plate, vehicle_type, owner_name, active, created_at
        FROM vehicles
        ORDER BY created_at DESC
    ");
    $vehicles = $stmt->fetchAll();

    $data = [];
    foreach ($vehicles as $v) {
        $data[] = [
            'id'            => (int)$v['id'],
            'vehicle_id'    => $v['vehicle_id'],
            'uid'           => $v['uid'],
            'plate'         => $v['plate'],
            'vehicle_type'  => $v['vehicle_type'],
            'owner_name'    => $v['owner_name'],
            'active'        => (int)$v['active'],
            'created_at'    => $v['created_at'],
        ];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data'    => $data,
        'count'   => count($data),
    ]);
} catch (Throwable $e) {
    error_log("Vehicles API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal mengambil data kendaraan.']);
}