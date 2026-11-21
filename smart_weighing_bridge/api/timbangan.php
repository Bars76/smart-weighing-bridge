<?php
/**
 * REST API untuk ESP32 (Network Layer + Middleware Layer).
 *
 * POST /iot_cihuy/api/timbangan.php
 *   body: berat=<gram>&uid_rfid=<UID>   (form encoded atau JSON)
 *   response: { "status":"ok", "status_berat":"Sesuai", ... }
 *
 * GET /iot_cihuy/api/timbangan.php
 *   response: pembacaan terakhir.
 */
declare(strict_types=1);
require __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

function respond(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $row = $pdo->query(
        'SELECT p.id_penimbangan, p.berat, p.batas_berat, p.status_berat, p.waktu_penimbangan,
                k.nomor_kendaraan, k.uid_rfid
         FROM penimbangan p JOIN kendaraan k ON k.id_kendaraan = p.id_kendaraan
         ORDER BY p.waktu_penimbangan DESC LIMIT 1'
    )->fetch();
    respond(200, ['status' => 'ok', 'data' => $row ?: null]);
}

if ($method !== 'POST') {
    respond(405, ['status' => 'error', 'pesan' => 'Metode tidak diizinkan.']);
}

// Terima form-encoded atau JSON.
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) $input = $decoded;
}

$berat = isset($input['berat']) ? (float)$input['berat'] : null;
$uid   = isset($input['uid_rfid']) ? trim((string)$input['uid_rfid']) : '';

if ($berat === null || $berat < 0 || $uid === '') {
    respond(422, ['status' => 'error', 'pesan' => 'Field berat (gram) dan uid_rfid wajib diisi.']);
}

$stmt = $pdo->prepare('SELECT id_kendaraan, nomor_kendaraan FROM kendaraan WHERE uid_rfid = ? AND status = "Aktif" LIMIT 1');
$stmt->execute([$uid]);
$kendaraan = $stmt->fetch();

if (!$kendaraan) {
    respond(404, ['status' => 'error', 'pesan' => 'UID RFID tidak terdaftar atau kendaraan tidak aktif.', 'uid_rfid' => $uid]);
}

$batas = (float) pengaturan('batas_berat', '5000');
$statusBerat = $berat > $batas ? 'Berlebih' : 'Sesuai';

$insert = $pdo->prepare(
    'INSERT INTO penimbangan (id_kendaraan, berat, batas_berat, status_berat) VALUES (?,?,?,?)'
);
$insert->execute([(int)$kendaraan['id_kendaraan'], $berat, $batas, $statusBerat]);

respond(201, [
    'status'      => 'ok',
    'id_penimbangan' => (int)$pdo->lastInsertId(),
    'nomor_kendaraan' => $kendaraan['nomor_kendaraan'],
    'berat'       => $berat,
    'batas_berat' => $batas,
    'status_berat'=> $statusBerat,
    'waktu'       => date('Y-m-d H:i:s'),
]);
