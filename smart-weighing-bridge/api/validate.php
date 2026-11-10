<?php
/**
 * API: Validasi RFID
 * POST /api/validate
 * Request: { "uid": "A1B2C3D4" }
 * Smart Weighing Bridge
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

use Config\Database;

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['uid']) || trim($input['uid']) === '') {
    http_response_code(400);
    echo json_encode(['valid' => false, 'message' => 'UID RFID diperlukan.']);
    exit;
}

$uid = strtoupper(trim($input['uid']));

$db = new Database();
$pdo = $db->getConnection();

try {
    $stmt = $pdo->prepare("
        SELECT id, vehicle_id, plate, vehicle_type, active
        FROM vehicles
        WHERE uid = :uid
        LIMIT 1
    ");
    $stmt->execute([':uid' => $uid]);
    $vehicle = $stmt->fetch();

    if ($vehicle && !$vehicle['active']) {
        http_response_code(200);
        echo json_encode([
            'valid' => false,
            'message' => 'Kendaraan tidak aktif.'
        ]);
        exit;
    }

    if ($vehicle) {
        http_response_code(200);
        echo json_encode([
            'valid' => true,
            'vehicle' => [
                'id'          => (int)$vehicle['id'],
                'vehicle_id'  => $vehicle['vehicle_id'],
                'plate'       => $vehicle['plate'],
                'vehicle_type'=> $vehicle['vehicle_type'],
            ]
        ]);
    } else {
        http_response_code(200);
        echo json_encode(['valid' => false, 'message' => 'UID RFID tidak terdaftar.']);
    }
} catch (Throwable $e) {
    error_log("Validate API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['valid' => false, 'message' => 'Terjadi kesalahan server.']);
}