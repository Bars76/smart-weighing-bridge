<?php
/**
 * API: Simpan hasil penimbangan
 * POST /api/weighings
 * Request: {
 *   "uid": "A1B2C3D4",
 *   "vehicle_id": "V001",
 *   "plate": "AB1234CD",
 *   "vehicle_type": "Truck",
 *   "weight_kg": 1250.50,
 *   "status": "weighed"
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

// Validasi field wajib
$required = ['uid', 'vehicle_id', 'plate', 'vehicle_type', 'weight_kg', 'status'];
foreach ($required as $field) {
    if (!isset($input[$field]) || $input[$field] === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Field {$field} wajib diisi."]);
        exit;
    }
}

$uid         = strtoupper(trim($input['uid']));
$vehicleId   = trim($input['vehicle_id']);
$plate       = strtoupper(trim($input['plate']));
$vehicleType = trim($input['vehicle_type']);
$weightKg    = filter_var($input['weight_kg'], FILTER_VALIDATE_FLOAT);
$status      = trim($input['status']);

if ($weightKg === false || $weightKg < 0 || $weightKg > 100000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Berat tidak valid (harus angka antara 0 dan 100000 kg).']);
    exit;
}

$allowedStatus = ['weighed', 'incomplete', 'void'];
if (!in_array($status, $allowedStatus, true)) {
    $status = 'weighed';
}

$db = new Database();
$pdo = $db->getConnection();

try {
    $pdo->beginTransaction();

    // Validasi UID terdaftar dan aktif (pakai transaksi untuk konsistensi)
    $stmt = $pdo->prepare("SELECT id, active FROM vehicles WHERE uid = :uid LIMIT 1");
    $stmt->execute([':uid' => $uid]);
    $vehicle = $stmt->fetch();

    if (!$vehicle) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'UID RFID tidak terdaftar.']);
        exit;
    }

    if (!$vehicle['active']) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Kendaraan tidak aktif.']);
        exit;
    }

    // Cek duplikasi transaction_code (idempotensi sederhana berdasarkan uid + plate + weighed_at dekat)
    // Buat transaction_code unik
    $datePart = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM weighings WHERE uid = :uid AND DATE(weighed_at) = :datePart");
    $stmt->execute([':uid' => $uid, ':datePart' => $datePart]);
    $todayCount = (int)$stmt->fetchColumn() + 1;

    $transactionCode = sprintf('WB-%s-%06d', date('Ymd'), $todayCount);

    // Cek apakah kode sudah ada (amenidagangkan duplikasi pengiriman ulang)
    $chk = $pdo->prepare("SELECT id FROM weighings WHERE transaction_code = :tc LIMIT 1");
    $chk->execute([':tc' => $transactionCode]);
    if ($chk->fetch()) {
        $transactionCode = sprintf('WB-%s-%06d', date('Ymd'), $todayCount + 1000);
    }

    // Simpan transaksi
    $weighedAt = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("
        INSERT INTO weighings (transaction_code, vehicle_id, uid, plate, vehicle_type, weight_kg, weighed_at, status)
        VALUES (:transaction_code, :vehicle_id, :uid, :plate, :vehicle_type, :weight_kg, :weighed_at, :status)
    ");
    $stmt->execute([
        ':transaction_code' => $transactionCode,
        ':vehicle_id'       => (int)$vehicle['id'],
        ':uid'              => $uid,
        ':plate'            => $plate,
        ':vehicle_type'     => $vehicleType,
        ':weight_kg'        => $weightKg,
        ':weighed_at'       => $weighedAt,
        ':status'           => $status,
    ]);

    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        'success'           => true,
        'message'           => 'Data penimbangan berhasil disimpan',
        'transaction_code'  => $transactionCode,
    ]);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log("Weighings API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data penimbangan.']);
}