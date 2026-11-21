<?php
/**
 * Endpoint ringan untuk ESP32: mengembalikan batas berat dari tabel pengaturan.
 * GET /iot_cihuy/api/batas.php
 * Respons: {"status":"ok","batas_berat":5000}
 */
declare(strict_types=1);
require __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$batas = (float) pengaturan('batas_berat', '5000');

echo json_encode([
    'status'      => 'ok',
    'batas_berat' => $batas,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
