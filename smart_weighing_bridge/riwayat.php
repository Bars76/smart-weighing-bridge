<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require_login();

$q = trim($_GET['q'] ?? '');
$tanggal = trim($_GET['tanggal'] ?? '');

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(k.nomor_kendaraan LIKE ? OR k.nama_kendaraan LIKE ? OR k.uid_rfid LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($tanggal !== '') {
    $where[] = 'DATE(p.waktu_penimbangan) = ?';
    $params[] = $tanggal;
}
$sql = 'SELECT p.*, k.nomor_kendaraan, k.nama_kendaraan, k.jenis_kendaraan
        FROM penimbangan p JOIN kendaraan k ON k.id_kendaraan = p.id_kendaraan';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY p.waktu_penimbangan DESC LIMIT 200';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$totalBerat = 0.0;
$jmlBerlebih = 0;
foreach ($rows as $r) {
    $totalBerat += (float) $r['berat'];
    if ($r['status_berat'] === 'Berlebih') $jmlBerlebih++;
}
$rata = count($rows) > 0 ? $totalBerat / count($rows) : 0;

require __DIR__ . '/includes/header.php';
?>

<p class="section-index">01 / RIWAYAT</p>
<h2 style="font-size:30px;margin-bottom:10px">Catatan <em style="color:var(--p-accent-hi);font-style:normal">penimbangan</em></h2>
<p class="muted" style="font-size:13px;max-width:60ch;line-height:1.8;margin-bottom:28px">Arsip seluruh hasil timbang beserta identitas kendaraan, batas berat, dan waktu pencatatan. Gunakan filter untuk menyempitkan hasil.</p>

<section class="kpi-strip" aria-label="Ringkasan hasil filter">
  <div class="kpi kpi-accent"><p class="kpi-label">Catatan tampil</p><p class="kpi-value"><?= count($rows) ?></p><p class="kpi-note">Dari filter aktif</p></div>
  <div class="kpi"><p class="kpi-label">Total berat</p><p class="kpi-value"><?= number_format($totalBerat, 0, ',', '.') ?><span class="stat-unit">g</span></p><p class="kpi-note">Akumulasi hasil</p></div>
  <div class="kpi"><p class="kpi-label">Rata-rata</p><p class="kpi-value"><?= number_format($rata, 0, ',', '.') ?><span class="stat-unit">g</span></p><p class="kpi-note">Per penimbangan</p></div>
  <div class="kpi"><p class="kpi-label">Berlebih</p><p class="kpi-value" style="color:var(--bad)"><?= $jmlBerlebih ?></p><p class="kpi-note">Melebihi batas</p></div>
</section>

<section class="card" style="margin-bottom:24px">
  <div class="card-head">
    <div><p class="section-index">02 / FILTER</p><h2 class="card-title">Cari riwayat</h2><p class="card-sub">Filter berdasarkan kendaraan atau tanggal penimbangan.</p></div>
  </div>
  <form class="search-bar" method="get">
    <div class="field"><label for="q">Kendaraan / UID</label>
      <input id="q" name="q" value="<?= h($q) ?>" placeholder="Nomor, nama, atau UID RFID"></div>
    <div class="field"><label for="tanggal">Tanggal</label>
      <input id="tanggal" name="tanggal" type="date" value="<?= h($tanggal) ?>"></div>
    <button class="btn btn-primary" type="submit">
      <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg>
      Cari
    </button>
    <?php if ($q !== '' || $tanggal !== ''): ?><a class="btn btn-ghost" href="riwayat.php">Reset</a><?php endif; ?>
  </form>
</section>

<section class="card">
  <div class="card-head">
    <div><p class="section-index">03 / ARSIP</p><h2 class="card-title">Catatan penimbangan</h2><p class="card-sub">Menampilkan <?= count($rows) ?> catatan terbaru (maksimal 200).</p></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>No</th><th>Kendaraan</th><th>Jenis</th><th class="align-right">Berat</th><th class="align-right">Batas</th><th>Status</th><th>Waktu</th></tr></thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="7" class="muted">Tidak ada catatan yang cocok.</td></tr>
      <?php else: foreach ($rows as $i => $r): ?>
        <tr>
          <td class="row-index"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></td>
          <td>
            <div class="mono-cell">
              <span class="mono-avatar" aria-hidden="true"><?= h(strtoupper(mb_substr($r['nomor_kendaraan'], 0, 1))) ?></span>
              <span><strong><?= h($r['nomor_kendaraan']) ?></strong><br><span class="muted" style="font-size:11px"><?= h($r['nama_kendaraan']) ?></span></span>
            </div>
          </td>
          <td><?= h($r['jenis_kendaraan']) ?></td>
          <td class="num align-right"><?= number_format((float)$r['berat'], 0, ',', '.') ?> g</td>
          <td class="num align-right muted"><?= number_format((float)$r['batas_berat'], 0, ',', '.') ?> g</td>
          <td><span class="badge <?= $r['status_berat'] === 'Sesuai' ? 'badge-ok' : 'badge-bad' ?>"><?= h($r['status_berat']) ?></span></td>
          <td class="muted" style="white-space:nowrap"><?= date('d M Y, H:i', strtotime($r['waktu_penimbangan'])) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
