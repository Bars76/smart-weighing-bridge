<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require_login();

$totalKendaraan = (int) $pdo->query("SELECT COUNT(*) FROM kendaraan WHERE status = 'Aktif'")->fetchColumn();
$totalPenimbangan = (int) $pdo->query('SELECT COUNT(*) FROM penimbangan')->fetchColumn();
$hariIni = (int) $pdo->query("SELECT COUNT(*) FROM penimbangan WHERE DATE(waktu_penimbangan) = CURDATE()")->fetchColumn();
$berlebih = (int) $pdo->query("SELECT COUNT(*) FROM penimbangan WHERE status_berat = 'Berlebih' AND DATE(waktu_penimbangan) = CURDATE()")->fetchColumn();
$batas = (float) pengaturan('batas_berat', '5000');
$rataRata = (float) $pdo->query('SELECT COALESCE(AVG(berat), 0) FROM penimbangan')->fetchColumn();

// Grafik 7 hari terakhir
$chartRows = $pdo->query(
    'SELECT DATE(waktu_penimbangan) AS hari, COUNT(*) AS n FROM penimbangan
     WHERE waktu_penimbangan >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(waktu_penimbangan)'
)->fetchAll();
$chartMap = [];
foreach ($chartRows as $cr) { $chartMap[$cr['hari']] = (int) $cr['n']; }
$namaHari = ['Sun'=>'Min','Mon'=>'Sen','Tue'=>'Sel','Wed'=>'Rab','Thu'=>'Kam','Fri'=>'Jum','Sat'=>'Sab'];
$days = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime('-' . $i . ' days'));
    $en = date('D', strtotime($d));
    $days[] = [
        'label' => $namaHari[$en] ?? $en,
        'n'     => $chartMap[$d] ?? 0,
        'today' => ($i === 0),
    ];
}
$chartMax   = max(1, max(array_column($days, 'n')));
$chartTotal = array_sum(array_column($days, 'n'));

// Distribusi status (seluruh data)
$dist = $pdo->query('SELECT status_berat, COUNT(*) AS n FROM penimbangan GROUP BY status_berat')->fetchAll(PDO::FETCH_KEY_PAIR);
$distOk  = (int) ($dist['Sesuai'] ?? 0);
$distBad = (int) ($dist['Berlebih'] ?? 0);
$distTotal = max(1, $distOk + $distBad);
$okPct = (int) round($distOk / $distTotal * 100);
$badPct = 100 - $okPct;

$terakhir = $pdo->query(
    'SELECT p.berat, p.status_berat, p.waktu_penimbangan, k.nomor_kendaraan, k.nama_kendaraan
     FROM penimbangan p JOIN kendaraan k ON k.id_kendaraan = p.id_kendaraan
     ORDER BY p.waktu_penimbangan DESC LIMIT 1'
)->fetch();

$riwayat = $pdo->query(
    'SELECT p.id_penimbangan, p.berat, p.status_berat, p.waktu_penimbangan,
            k.nomor_kendaraan, k.nama_kendaraan
     FROM penimbangan p JOIN kendaraan k ON k.id_kendaraan = p.id_kendaraan
     ORDER BY p.waktu_penimbangan DESC LIMIT 6'
)->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<!-- OVERVIEW -->
<section class="overview">
  <div class="overview-main">
    <p class="section-index">01 / RINGKASAN</p>
    <h2 class="overview-title">Operasional timbang <em>dalam satu pandangan.</em></h2>
    <p class="overview-copy">Seluruh aktivitas jembatan timbang — pembacaan berat, identitas kendaraan, dan status palang — terekam otomatis dan tersaji di sini secara langsung.</p>
    <div class="overview-foot">
      <div class="kpi"><p class="kpi-label">Total catatan</p><p class="kpi-value"><?= $totalPenimbangan ?></p></div>
      <div class="kpi"><p class="kpi-label">Rata-rata berat</p><p class="kpi-value"><?= number_format($rataRata, 0, ',', '.') ?><span class="stat-unit">g</span></p></div>
      <div class="kpi"><p class="kpi-label">Batas sistem</p><p class="kpi-value"><?= number_format($batas, 0, ',', '.') ?><span class="stat-unit">g</span></p></div>
    </div>
  </div>
  <div class="overview-side">
    <p class="section-index">02 / AKTIVITAS</p>
    <div class="chart">
      <div class="chart-head">
        <div><p class="kpi-label">Penimbangan 7 hari terakhir</p><p class="chart-total"><?= $chartTotal ?></p></div>
        <span class="muted" style="font-size:11px">transaksi</span>
      </div>
      <div class="chart-bars">
        <?php foreach ($days as $d): ?>
          <div class="chart-col <?= $d['today'] ? 'today' : '' ?>" title="<?= $d['label'] ?>: <?= $d['n'] ?>">
            <div class="chart-bar" style="height:<?= max(2, (int) round($d['n'] / $chartMax * 100)) ?>%"></div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="chart-labels">
        <?php foreach ($days as $d): ?><span class="<?= $d['today'] ? 'today' : '' ?>"><?= h($d['label']) ?></span><?php endforeach; ?>
      </div>
    </div>
    <div class="dist">
      <div class="dist-label"><span>Status penimbangan</span><span><?= $okPct ?>% sesuai</span></div>
      <div class="dist-track">
        <div class="dist-ok" style="width:<?= $okPct ?>%"></div>
        <div class="dist-bad" style="width:<?= $badPct ?>%"></div>
      </div>
      <div class="dist-label" style="margin-top:8px"><span><span class="badge badge-ok">Sesuai <?= $distOk ?></span></span><span><span class="badge badge-bad">Berlebih <?= $distBad ?></span></span></div>
    </div>
  </div>
</section>

<!-- LIVE MONITORING -->
<section class="card live-panel">
  <div class="card-head">
    <div>
      <p class="section-index">LIVE / PEMBACAAN LANGSUNG</p>
      <h2 class="card-title">Status timbang langsung</h2>
      <p class="card-sub" id="live-update">Menunggu data dari ESP32…</p>
    </div>
    <span class="pill pill-off" id="live-pill"><span class="dot"></span> <span id="live-pill-text">Perangkat offline</span></span>
  </div>
  <div class="live-grid">
    <div class="live-reading">
      <p class="kpi-label">Berat di platform</p>
      <p class="live-number"><span id="live-berat">—</span><span class="stat-unit">GRAM</span></p>
      <div class="gauge">
        <div class="gauge-head"><span>Beban terhadap batas</span><strong id="live-persen">0%</strong></div>
        <div class="gauge-track">
          <div class="gauge-fill" id="live-gauge" style="width:0%"></div>
          <div class="gauge-mark" style="left:100%"></div>
        </div>
      </div>
      <p class="kpi-note" id="live-state" style="margin-top:14px">Siaga</p>
    </div>
    <div class="live-steps" id="live-steps">
      <div class="live-step" data-step="STANDBY"><span class="n">1</span> Deteksi / siaga</div>
      <div class="live-step" data-step="MASUK"><span class="n">2</span> Kendaraan masuk</div>
      <div class="live-step" data-step="TIMBANG"><span class="n">3</span> Menimbang</div>
      <div class="live-step" data-step="TUNGGU_RFID"><span class="n">4</span> Baca RFID</div>
      <div class="live-step" data-step="KELUAR"><span class="n">5</span> Keluar</div>
    </div>
    <div class="live-device">
      <p class="kpi-label" style="margin-bottom:8px">Kondisi perangkat</p>
      <div class="palang-row"><span>Palang masuk</span><strong id="live-palang-masuk" class="tutup">Tertutup</strong></div>
      <div class="palang-row"><span>Palang keluar</span><strong id="live-palang-keluar" class="tutup">Tertutup</strong></div>
      <div class="palang-row"><span>UID RFID</span><strong id="live-uid" class="num">—</strong></div>
      <div class="palang-row"><span>WiFi RSSI</span><strong id="live-rssi" class="num">—</strong></div>
      <div class="palang-row"><span>Uptime</span><strong id="live-uptime" class="num">—</strong></div>
    </div>
  </div>
</section>

<!-- KPI STRIP -->
<section class="kpi-strip" aria-label="Indikator utama">
  <div class="kpi kpi-accent"><p class="kpi-label">Kendaraan aktif</p><p class="kpi-value"><?= $totalKendaraan ?></p><p class="kpi-note">UID RFID terdaftar</p></div>
  <div class="kpi"><p class="kpi-label">Hari ini</p><p class="kpi-value"><?= $hariIni ?></p><p class="kpi-note">Penimbangan masuk</p></div>
  <div class="kpi"><p class="kpi-label">Batas berat</p><p class="kpi-value"><?= number_format($batas, 0, ',', '.') ?><span class="stat-unit">g</span></p><p class="kpi-note">Konfigurasi admin</p></div>
  <div class="kpi"><p class="kpi-label">Kondisi hari ini</p><p class="kpi-value" style="font-size:19px"><?= $berlebih > 0 ? 'Perlu perhatian' : 'Semua sesuai' ?></p><p class="kpi-note"><?= $berlebih ?> penimbangan berlebih</p></div>
</section>

<div class="grid-2">
  <!-- TIMELINE -->
  <section class="card">
    <div class="card-head">
      <div><p class="section-index">03 / AKTIVITAS</p><h2 class="card-title">Penimbangan terbaru</h2><p class="card-sub">Catatan masuk terakhir dari jembatan timbang.</p></div>
      <a class="btn btn-ghost btn-sm" href="riwayat.php">Lihat semua</a>
    </div>
    <div class="timeline">
      <?php if (!$riwayat): ?>
        <p class="muted" style="padding:24px 0">Belum ada data penimbangan.</p>
      <?php else: foreach ($riwayat as $r): ?>
        <div class="timeline-item">
          <span class="timeline-dot <?= $r['status_berat'] === 'Berlebih' ? 'bad' : '' ?>" aria-hidden="true"></span>
          <div>
            <div class="timeline-title"><?= h($r['nomor_kendaraan']) ?> <span class="num muted" style="margin-left:8px"><?= number_format((float)$r['berat'], 0, ',', '.') ?> g</span></div>
            <div class="timeline-sub"><?= h($r['nama_kendaraan']) ?> · <span class="badge <?= $r['status_berat'] === 'Sesuai' ? 'badge-ok' : 'badge-bad' ?>" style="margin-left:4px"><?= h($r['status_berat']) ?></span></div>
          </div>
          <span class="timeline-time"><?= date('d M, H:i', strtotime($r['waktu_penimbangan'])) ?></span>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </section>

  <!-- DEVICE -->
  <section class="card">
    <div class="card-head"><div><p class="section-index">04 / PERANGKAT</p><h2 class="card-title">Kondisi sistem</h2><p class="card-sub">Status koneksi & pembacaan terkini.</p></div></div>
    <div class="device-readout">
      <span class="big"><?= $terakhir ? number_format((float)$terakhir['berat'], 0, ',', '.') : '—' ?></span>
      <span class="unit">GRAM</span>
    </div>
    <div class="device-meta">
      <div class="device-meta-row"><span>Status ESP32</span><strong class="badge <?= $espOnline ? 'badge-ok' : 'badge-bad' ?>"><?= $espOnline ? 'Terhubung' : 'Terputus' ?></strong></div>
      <div class="device-meta-row"><span>Terakhir terhubung</span><strong><?= h($espKeterangan) ?></strong></div>
      <div class="device-meta-row"><span>Pembacaan terakhir</span><strong><?= $terakhir ? h($terakhir['nomor_kendaraan']) : '—' ?></strong></div>
      <div class="device-meta-row"><span>Status berat</span><strong><?= $terakhir ? h($terakhir['status_berat']) : '—' ?></strong></div>
      <div class="device-meta-row"><span>Koneksi database</span><strong class="badge badge-ok">Terhubung</strong></div>
      <div class="device-meta-row"><span>Hak akses Anda</span><strong><?= is_admin() ? 'Administrator' : 'Operator' ?></strong></div>
    </div>
    <div class="endpoint-box">
      <p class="kpi-label" style="margin-bottom:10px">Endpoint ESP32</p>
      <code>POST /iot_cihuy/api/timbangan.php</code>
      <p>Kirim <code>berat</code> (gram) dan <code>uid_rfid</code>. Heartbeat: <code>GET /api/heartbeat.php</code> tiap 15 detik.</p>
    </div>
  </section>
</div>

<?php
$pageScripts[] = <<<'JS'
(function () {
  function el(id) { return document.getElementById(id); }
  function rupiah(n) { return Number(n || 0).toLocaleString('id-ID'); }
  function durasi(ms) {
    var s = Math.floor((Number(ms) || 0) / 1000);
    var h = Math.floor(s / 3600); s %= 3600;
    var m = Math.floor(s / 60); s %= 60;
    return (h ? h + 'j ' : '') + (m ? m + 'm ' : '') + s + 'd';
  }
  function palang(elId, nilai) {
    var e = el(elId);
    e.textContent = nilai ? 'Terbuka' : 'Tertutup';
    e.className = nilai ? 'buka' : 'tutup';
  }
  LiveMonitor.start(function (d) {
    var online = !d.error && !!d.online;
    var berat = Number(d.berat) || 0;
    var batas = Number(d.batas_berat) || 5000;
    var over = berat > batas;

    el('live-pill').className = 'pill ' + (online ? 'pill-ok' : 'pill-off');
    el('live-pill-text').textContent = online ? 'ESP32 Live' : 'Perangkat offline';
    el('live-berat').textContent = online ? rupiah(berat) : '—';
    el('live-berat').parentElement.classList.toggle('over', online && over);

    var pct = Math.min(100, Math.round(berat / Math.max(1, batas) * 100));
    el('live-persen').textContent = pct + '%';
    el('live-gauge').style.width = pct + '%';
    el('live-gauge').className = 'gauge-fill' + (over ? ' over' : '');

    el('live-state').textContent = online ? LiveMonitor.label(d.state) : 'Menunggu data…';
    var idx = LiveMonitor.urutan.indexOf(d.state);
    document.querySelectorAll('#live-steps .live-step').forEach(function (step, i) {
      step.classList.toggle('active', online && i === idx);
      step.classList.toggle('done', online && idx >= 0 && i < idx);
    });

    palang('live-palang-masuk', online && !!d.palang_masuk);
    palang('live-palang-keluar', online && !!d.palang_keluar);
    el('live-uid').textContent = online && d.uid_rfid ? d.uid_rfid : '—';
    el('live-rssi').textContent = online && d.rssi !== null ? d.rssi + ' dBm' : '—';
    el('live-uptime').textContent = online ? durasi(d.uptime_ms) : '—';
    el('live-update').textContent = online
      ? 'Diperbarui otomatis · ' + (d.diperbarui || '')
      : 'ESP32 belum mengirim data live.';
  }, 1500);
})();
JS;
require __DIR__ . '/includes/footer.php'; ?>
