<?php
/**
 * API: Status Perangkat (Heartbeat)
 * POST /api/device-status
 * Request: {
 *   "device_token": "SECRET_TOKEN",
 *   "device_name": "ESP32-Weighbridge-01",
 *   "components": [
 *     {"name": "RFID", "status": "active"},
 *     {"name": "IR_IN", "status": "active"},
 *     {"name": "IR_OUT", "status": "active"},
 *     {"name": "HX711", "status": "active"},
 *     {"name": "Servo", "status": "closed"},
 *     {"name": "WiFi", "status": "connected"}
 *   ]
 * }
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

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Data JSON tidak valid.']);
    exit;
}

$deviceToken = trim($input['device_token'] ?? '');
$deviceName  = trim($input['device_name'] ?? 'ESP32 Device');
$components  = $input['components'] ?? [];

if ($deviceToken === '') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Device token diperlukan.']);
    exit;
}

$db = new Database();
$pdo = $db->getConnection();

try {
    $pdo->beginTransaction();

    // Cari atau buat perangkat berdasarkan token
    $stmt = $pdo->prepare("SELECT id FROM devices WHERE device_token = :token LIMIT 1");
    $stmt->execute([':token' => $deviceToken]);
    $device = $stmt->fetch();

    $now = date('Y-m-d H:i:s');

    if (!$device) {
        $stmt = $pdo->prepare("
            INSERT INTO devices (device_name, device_token, last_seen, connection_status)
            VALUES (:name, :token, :now, 'online')
        ");
        $stmt->execute([
            ':name'  => $deviceName,
            ':token' => $deviceToken,
            ':now'   => $now,
        ]);
        $deviceId = (int)$pdo->lastInsertId();
    } else {
        $deviceId = (int)$device['id'];
        $stmt = $pdo->prepare("
            UPDATE devices
            SET device_name = :name, last_seen = :now, connection_status = 'online', updated_at = :now
            WHERE id = :id
        ");
        $stmt->execute([
            ':name'  => $deviceName,
            ':now'   => $now,
            ':id'    => $deviceId,
        ]);
    }

    // Simpan log komponen
    $validComponentStatus = ['active','inactive','unknown','open','closed'];
    foreach ($components as $comp) {
        $compName  = trim($comp['name'] ?? '');
        $compStatus = trim($comp['status'] ?? 'unknown');
        if ($compName === '') continue;
        if (!in_array($compStatus, $validComponentStatus, true)) {
            $compStatus = 'unknown';
        }
        $stmt = $pdo->prepare("
            INSERT INTO device_logs (device_id, component_name, component_status, recorded_at)
            VALUES (:device_id, :component_name, :component_status, :recorded_at)
        ");
        $stmt->execute([
            ':device_id'       => $deviceId,
            ':component_name'  => $compName,
            ':component_status'=> $compStatus,
            ':recorded_at'     => $now,
        ]);
    }

    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Status perangkat berhasil diperbarui',
        'device_id' => $deviceId,
        'recorded_at' => $now,
    ]);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log("Device status API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status perangkat.']);
}