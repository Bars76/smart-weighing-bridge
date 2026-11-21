<?php
/**
 * Status penimbangan real-time.
 *
 * POST /iot_cihuy/api/live.php   -> ESP32 menimpa status terkini
 * GET  /iot_cihuy/api/live.php   -> halaman mengambil status (JSON)
 */
declare(strict_types=1);
require __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = $_POST;
    if (empty($input)) {
        $decoded = json_decode(file_get_contents('php://input'), true);
        if (is_array($decoded)) $input = $decoded;
    }

    $berat = isset($input['berat']) ? (float) $input['berat'] : 0.0;
    $state = strtoupper(trim((string) ($input['state'] ?? 'STANDBY')));
    $allowed = ['STANDBY', 'MASUK', 'TIMBANG', 'TUNGGU_RFID', 'KELUAR'];
    if (!in_array($state, $allowed, true)) $state = 'STANDBY';
    if ($berat < 0) $berat = 0;

    $masuk  = !empty($input['palang_masuk'])  ? 1 : 0;
    $keluar = !empty($input['palang_keluar']) ? 1 : 0;
    $uid    = substr(trim((string) ($input['uid_rfid'] ?? '')), 0, 50);
    $rssi   = isset($input['rssi']) && $input['rssi'] !== '' ? (int) $input['rssi'] : null;
    $uptime = isset($input['uptime_ms']) ? (int) $input['uptime_ms'] : 0;

    $sql = 'INSERT INTO live_status
              (id, berat, state, palang_masuk, palang_keluar, uid_rfid, rssi, uptime_ms)
            VALUES (1, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              berat = VALUES(berat), state = VALUES(state),
              palang_masuk = VALUES(palang_masuk), palang_keluar = VALUES(palang_keluar),
              uid_rfid = VALUES(uid_rfid), rssi = VALUES(rssi), uptime_ms = VALUES(uptime_ms),
              diperbarui = CURRENT_TIMESTAMP';

    $pdo->prepare($sql)->execute([$berat, $state, $masuk, $keluar, $uid, $rssi, $uptime]);

    echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'pesan' => 'Metode tidak diizinkan.']);
    exit;
}

$row = $pdo->query(
    'SELECT l.*, TIMESTAMPDIFF(SECOND, l.diperbarui, NOW()) AS umur
     FROM live_status l WHERE l.id = 1'
)->fetch() ?: [
    'berat' => 0, 'state' => 'STANDBY', 'palang_masuk' => 0, 'palang_keluar' => 0,
    'uid_rfid' => '', 'rssi' => null, 'uptime_ms' => 0, 'diperbarui' => null, 'umur' => 9999,
];

// Umur dihitung MySQL supaya tidak terpengaruh beda timezone PHP (UTC) vs MySQL (WIB).
$umur = isset($row['umur']) ? (int) $row['umur'] : 9999;
$serverTime = (string) $pdo->query('SELECT NOW()')->fetchColumn();

echo json_encode([
    'status'      => 'ok',
    'online'      => ($umur >= 0 && $umur <= 3),
    'berat'       => (float) $row['berat'],
    'state'       => $row['state'],
    'palang_masuk' => (int) $row['palang_masuk'],
    'palang_keluar'=> (int) $row['palang_keluar'],
    'uid_rfid'    => $row['uid_rfid'],
    'rssi'        => $row['rssi'] === null ? null : (int) $row['rssi'],
    'uptime_ms'   => (int) $row['uptime_ms'],
    'diperbarui'  => $row['diperbarui'],
    'server_time' => $serverTime,
    'batas_berat' => (float) pengaturan('batas_berat', '5000'),
], JSON_UNESCAPED_UNICODE);
