<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $batas = trim($_POST['batas_berat'] ?? '');
    if (!is_numeric($batas) || (float)$batas <= 0) {
        flash('error', 'Batas berat harus berupa angka positif.');
    } else {
        set_pengaturan('batas_berat', (string)(float)$batas);
        flash('success', 'Batas berat berhasil disimpan: ' . number_format((float)$batas, 0, ',', '.') . ' gram.');
    }
    header('Location: pengaturan.php');
    exit;
}

$pengaturan = $pdo->query('SELECT * FROM pengaturan ORDER BY id_pengaturan')->fetchAll();
$batas = (float) pengaturan('batas_berat', '5000');
$skala = max(5000.0, $batas * 1.25);
$persentase = min(100, (int) round($batas / $skala * 100));
$mark = (int) round(5000 / $skala * 100);
$lastSeen = pengaturan('esp32_last_seen', 'Belum pernah terhubung');

require __DIR__ . '/includes/header.php';
?>

<p class="section-index">01 / KONFIGURASI</p>
<h2 style="font-size:30px;margin-bottom:10px">Pengaturan <em style="color:var(--p-accent-hi);font-style:normal">sistem</em></h2>
<p class="muted" style="font-size:13px;max-width:60ch;line-height:1.8;margin-bottom:28px">Atur batas maksimum penimbangan. Perubahan langsung dibaca oleh ESP32 pada siklus heartbeat berikutnya tanpa perlu meng-upload ulang firmware.</p>

<div class="settings-grid">
  <!-- LEAD -->
  <section class="settings-lead">
    <p class="section-index">02 / BATAS BERAT</p>
    <p class="kpi-label">Ambang maksimum saat ini</p>
    <p class="big-number"><?= number_format($batas, 0, ',', '.') ?><span>GRAM</span></p>
    <p class="muted" style="font-size:12px;line-height:1.8;max-width:48ch">Nilai ini dipakai untuk menentukan status <strong>Sesuai</strong> atau <strong>Berlebih</strong> pada setiap penimbangan. Berat di atas ambang tetap tercatat dan kendaraan tetap diizinkan lewat dengan indikator merah.</p>

    <div class="gauge">
      <div class="gauge-head"><span>Kapasitas referensi</span><strong><?= number_format($batas, 0, ',', '.') ?> / <?= number_format($skala, 0, ',', '.') ?> g</strong></div>
      <div class="gauge-track">
        <div class="gauge-fill <?= $batas > 5000 ? 'over' : '' ?>" style="width:<?= $persentase ?>%"></div>
        <div class="gauge-mark" style="left:<?= $mark ?>%" title="5000 g — kapasitas Load Cell"></div>
      </div>
      <div class="dist-label" style="margin-top:8px"><span>Garis penanda = kapasitas Load Cell 5 kg</span><span><?= $persentase ?>%</span></div>
    </div>

    <form method="post" style="margin-top:28px;padding-top:24px;border-top:1px solid var(--line)">
      <?= csrf_field() ?>
      <div class="field">
        <label for="batas_berat">Ubah batas berat (gram)</label>
        <input id="batas_berat" name="batas_berat" type="number" min="1" step="1" required
               value="<?= h(number_format($batas, 0, '', '')) ?>">
        <span class="hint">Satuan gram. Disarankan tidak melebihi spesifikasi sensor.</span>
      </div>
      <div class="form-actions"><button class="btn btn-primary" type="submit">Simpan pengaturan</button></div>
    </form>
  </section>

  <!-- SIDE -->
  <div style="display:flex;flex-direction:column;gap:20px">
    <section class="card">
      <div class="card-head"><div><p class="section-index">03 / NILAI TERSIMPAN</p><h2 class="card-title">Konfigurasi database</h2><p class="card-sub">Semua pengaturan tersimpan di tabel <code>pengaturan</code>.</p></div></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Nama</th><th class="align-right">Nilai</th></tr></thead>
          <tbody>
          <?php foreach ($pengaturan as $p): ?>
            <tr>
              <td class="num" style="font-size:11px"><?= h($p['nama_pengaturan']) ?></td>
              <td class="num align-right"><?= h($p['nilai_pengaturan']) ?><?= $p['nama_pengaturan'] === 'batas_berat' ? ' g' : '' ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="card">
      <div class="card-head"><div><p class="section-index">04 / SISTEM</p><h2 class="card-title">Tentang aplikasi</h2><p class="card-sub">Informasi lingkungan & koneksi.</p></div></div>
      <div class="about-list">
        <div class="about-item"><p class="kpi-label">Versi PRD</p><p>Smart Weighing Bridge v1.1 · prototipe IoT</p></div>
        <div class="about-item"><p class="kpi-label">Arsitektur</p><p>ESP32 → HTTP REST → PHP 8.2 → MySQL (MariaDB 10.4) → Dashboard</p></div>
        <div class="about-item"><p class="kpi-label">Perangkat ESP32</p><p>Terakhir terhubung: <?= h($lastSeen) ?></p></div>
        <div class="about-item"><p class="kpi-label">API penimbangan</p><p><code>POST /iot_cihuy/api/timbangan.php</code></p></div>
        <div class="about-item"><p class="kpi-label">API batas & heartbeat</p><p><code>GET /api/batas.php</code> · <code>GET /api/heartbeat.php</code></p></div>
      </div>
    </section>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
