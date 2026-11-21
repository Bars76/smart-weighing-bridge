<?php
/**
 * Heartbeat ESP32 -> server.
 * Dipanggil firmware tiap 15 detik agar dashboard mengetahui status perangkat.
 *
 * GET/POST /iot_cihuy/api/heartbeat.php?device=esp32-1
 * Respons: {"status":"ok","device":"esp32-1","server_time":"..."}
 */
declare(strict_types=1);
require __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$device = trim((string) ($_POST['device'] ?? $_GET['device'] ?? 'esp32-1'));
if ($device === '') {
    $device = 'esp32-1';
}

set_pengaturan('esp32_last_seen', date('Y-m-d H:i:s'));
set_pengaturan('esp32_device', $device);

echo json_encode([
    'status'      => 'ok',
    'device'      => $device,
    'server_time' => date('Y-m-d H:i:s'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
