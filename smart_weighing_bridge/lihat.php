<?php
/** Monitor publik (Pengamat): lihat berat & status tanpa login. */
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';

$terakhir = $pdo->query(
    'SELECT p.berat, p.status_berat, p.waktu_penimbangan, k.nomor_kendaraan, k.nama_kendaraan
     FROM penimbangan p JOIN kendaraan k ON k.id_kendaraan = p.id_kendaraan
     ORDER BY p.waktu_penimbangan DESC LIMIT 1'
)->fetch();
$terbaru = $pdo->query(
    'SELECT p.berat, p.status_berat, p.waktu_penimbangan, k.nomor_kendaraan
     FROM penimbangan p JOIN kendaraan k ON k.id_kendaraan = p.id_kendaraan
     ORDER BY p.waktu_penimbangan DESC LIMIT 5'
)->fetchAll();
$batas = (float) pengaturan('batas_berat', '5000');

$lastSeenRaw = pengaturan('esp32_last_seen', '');
$lastSeenTs  = ($lastSeenRaw !== '' && $lastSeenRaw !== '0000-00-00 00:00:00')
    ? strtotime($lastSeenRaw) : 0;
$selisih = $lastSeenTs > 0 ? (time() - $lastSeenTs) : -1;
$online  = ($selisih >= 0 && $selisih <= 60);
$ket     = $lastSeenTs > 0 ? date('d M Y, H:i:s', $lastSeenTs) : 'Belum pernah terhubung';

$beratSekarang = $terakhir ? (float) $terakhir['berat'] : 0;
$persen = min(100, (int) round($beratSekarang / max(1, $batas) * 100));
$berlebih = $terakhir && $terakhir['status_berat'] === 'Berlebih';
?>
<!doctype html>
<html lang="id" data-type="software" data-radius="soft" data-motion>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Monitor Publik · Smart Weighing Bridge</title>
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
  <a class="skip-link" href="#konten">Lewati ke konten</a>
  <header class="topbar" style="position:static">
    <div class="brand" style="padding:0">
      <span class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 32 32" width="32" height="32" fill="none">
          <rect x="2.75" y="2.75" width="26.5" height="26.5" rx="3" stroke="#0071e3" stroke-width="1.5"/>
          <path d="M8 21.5V14m5 7.5V10m5 11.5v-5m5 5V12" stroke="#0071e3" stroke-width="2" stroke-linecap="round"/>
          <path d="M7 24h18" stroke="#0071e3" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </span>
      <span class="brand-text"><strong>Smart Weighing</strong><small>Monitor Publik</small></span>
    </div>
    <div style="display:flex;align-items:center;gap:12px">
      <span class="pill <?= $online ? 'pill-ok' : 'pill-off' ?>" id="live-pill" title="Terakhir terhubung: <?= h($ket) ?>">
        <span class="dot"></span> <span id="live-pill-text"><?= $online ? 'ESP32 Live' : 'ESP32 Terputus' ?></span>
      </span>
      <button class="btn btn-ghost btn-sm" type="button"
              onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = 'index.php'; }">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
        Kembali
      </button>
      <a class="btn btn-ghost btn-sm" href="index.php">Masuk</a>
    </div>
  </header>

  <main class="content" id="konten" style="max-width:1000px">
    <p class="section-index">01 / LIVE MONITOR</p>
    <h2 style="font-size:30px;margin-bottom:8px">Pantauan <em style="color:var(--p-accent-hi);font-style:normal">penimbangan</em></h2>
    <p class="muted" style="font-size:12px;margin-bottom:28px">Mode pengamat · angka diperbarui otomatis langsung dari ESP32.</p>

    <section class="kiosk">
      <div class="kiosk-top">
        <span class="live-badge"><span class="pulse"></span> Pembacaan langsung</span>
        <span class="kpi-label" id="live-update">Batas <?= number_format($batas, 0, ',', '.') ?> g</span>
      </div>

      <p class="kiosk-weight" id="live-number-wrap">
        <span id="live-berat"><?= $terakhir ? number_format($beratSekarang, 0, ',', '.') : '—' ?></span><span>GRAM</span>
      </p>

      <div class="kiosk-sub">
        <span>Tahap <strong id="live-state">—</strong></span>
        <span>Kendaraan <strong id="live-kendaraan"><?= $terakhir ? h($terakhir['nomor_kendaraan']) : '—' ?></strong></span>
        <span>Waktu <strong id="live-waktu"><?= $terakhir ? date('d M Y, H:i:s', strtotime($terakhir['waktu_penimbangan'])) : '—' ?></strong></span>
      </div>

      <div class="gauge">
        <div class="gauge-head"><span>Beban terhadap batas maksimum</span><strong id="live-persen"><?= $persen ?>%</strong></div>
        <div class="gauge-track">
          <div class="gauge-fill <?= $berlebih ? 'over' : '' ?>" id="live-gauge" style="width:<?= $persen ?>%"></div>
          <div class="gauge-mark" style="left:100%"></div>
        </div>
      </div>

      <div class="kiosk-rule"></div>
      <div class="live-grid" style="margin-top:0">
        <div class="live-steps" style="grid-column:1 / -1;flex-direction:row;flex-wrap:wrap" id="live-steps">
          <div class="live-step" data-step="STANDBY"><span class="n">1</span> Siaga</div>
          <div class="live-step" data-step="MASUK"><span class="n">2</span> Masuk</div>
          <div class="live-step" data-step="TIMBANG"><span class="n">3</span> Timbang</div>
          <div class="live-step" data-step="TUNGGU_RFID"><span class="n">4</span> RFID</div>
          <div class="live-step" data-step="KELUAR"><span class="n">5</span> Keluar</div>
        </div>
        <div class="live-device" style="grid-column:1 / -1;padding-left:0;border-left:0;padding-top:8px">
          <div class="palang-row"><span>Palang masuk</span><strong id="live-palang-masuk" class="tutup">Tertutup</strong></div>
          <div class="palang-row"><span>Palang keluar</span><strong id="live-palang-keluar" class="tutup">Tertutup</strong></div>
          <div class="palang-row"><span>UID RFID</span><strong id="live-uid" class="num">—</strong></div>
          <div class="palang-row"><span>WiFi RSSI</span><strong id="live-rssi" class="num">—</strong></div>
          <div class="palang-row"><span>Terakhir terhubung</span><strong><?= h($ket) ?></strong></div>
        </div>
      </div>
    </section>

    <section class="card">
      <div class="card-head"><div><p class="section-index">02 / AKTIVITAS</p><h2 class="card-title">Penimbangan terbaru</h2><p class="card-sub">Lima catatan terakhir dari jembatan timbang.</p></div></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>No</th><th>Kendaraan</th><th class="align-right">Berat</th><th>Status</th><th>Waktu</th></tr></thead>
          <tbody>
          <?php if (!$terbaru): ?><tr><td colspan="5" class="muted">Belum ada data penimbangan.</td></tr><?php endif; ?>
          <?php foreach ($terbaru as $i => $r): ?>
            <tr>
              <td class="row-index"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></td>
              <td><strong><?= h($r['nomor_kendaraan']) ?></strong></td>
              <td class="num align-right"><?= number_format((float)$r['berat'], 0, ',', '.') ?> g</td>
              <td><span class="badge <?= $r['status_berat'] === 'Sesuai' ? 'badge-ok' : 'badge-bad' ?>"><?= h($r['status_berat']) ?></span></td>
              <td class="muted" style="white-space:nowrap"><?= date('d M, H:i', strtotime($r['waktu_penimbangan'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <script src="assets/js/live.js"></script>
  <script>
  (function () {
    function el(id) { return document.getElementById(id); }
    function rupiah(n) { return Number(n || 0).toLocaleString('id-ID'); }
    function palang(id, nilai) {
      var e = el(id);
      e.textContent = nilai ? 'Terbuka' : 'Tertutup';
      e.className = nilai ? 'buka' : 'tutup';
    }
    LiveMonitor.start(function (d) {
      var online = !d.error && !!d.online;
      var berat = Number(d.berat) || 0;
      var batas = Number(d.batas_berat) || 5000;
      var over = berat > batas;

      el('live-pill').className = 'pill ' + (online ? 'pill-ok' : 'pill-off');
      el('live-pill-text').textContent = online ? 'ESP32 Live' : 'ESP32 Terputus';
      el('live-berat').textContent = online ? rupiah(berat) : '—';
      el('live-number-wrap').classList.toggle('over', online && over);
      el('live-number-wrap').style.color = online && over ? 'var(--bad)' : '';

      el('live-state').textContent = online ? LiveMonitor.label(d.state) : 'Menunggu data…';
      el('live-kendaraan').textContent = online && d.uid_rfid ? d.uid_rfid : '—';
      el('live-waktu').textContent = online ? (d.diperbarui || '—') : '—';

      var pct = Math.min(100, Math.round(berat / Math.max(1, batas) * 100));
      el('live-persen').textContent = pct + '%';
      el('live-gauge').style.width = pct + '%';
      el('live-gauge').className = 'gauge-fill' + (over ? ' over' : '');

      var idx = LiveMonitor.urutan.indexOf(d.state);
      document.querySelectorAll('#live-steps .live-step').forEach(function (step, i) {
        step.classList.toggle('active', online && i === idx);
        step.classList.toggle('done', online && idx >= 0 && i < idx);
      });

      palang('live-palang-masuk', online && !!d.palang_masuk);
      palang('live-palang-keluar', online && !!d.palang_keluar);
      el('live-uid').textContent = online && d.uid_rfid ? d.uid_rfid : '—';
      el('live-rssi').textContent = online && d.rssi !== null ? d.rssi + ' dBm' : '—';
      el('live-update').textContent = online ? 'Batas ' + rupiah(batas) + ' g · live' : 'Menunggu data live…';
    }, 1500);
  })();
  </script>
</body>
</html>
