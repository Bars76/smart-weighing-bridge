<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_admin()) {
    $aksi = $_POST['aksi'] ?? '';
    $nomor = trim($_POST['nomor_kendaraan'] ?? '');
    $nama  = trim($_POST['nama_kendaraan'] ?? '');
    $jenis = trim($_POST['jenis_kendaraan'] ?? '');
    $uid   = trim($_POST['uid_rfid'] ?? '');
    $status = $_POST['status'] === 'Tidak Aktif' ? 'Tidak Aktif' : 'Aktif';

    if ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            $pdo->prepare('DELETE FROM kendaraan WHERE id_kendaraan = ?')->execute([$id]);
            flash('success', 'Data kendaraan dihapus.');
        } catch (PDOException $e) {
            flash('error', 'Tidak dapat menghapus: kendaraan memiliki riwayat penimbangan. Nonaktifkan saja.');
        }
        header('Location: kendaraan.php');
        exit;
    }

    if ($nomor === '' || $nama === '' || $jenis === '' || $uid === '') {
        $errors[] = 'Semua field wajib diisi.';
    } else {
        try {
            if ($aksi === 'ubah') {
                $id = (int) ($_POST['id'] ?? 0);
                $pdo->prepare('UPDATE kendaraan SET nomor_kendaraan=?, nama_kendaraan=?, jenis_kendaraan=?, uid_rfid=?, status=? WHERE id_kendaraan=?')
                    ->execute([$nomor, $nama, $jenis, $uid, $status, $id]);
                flash('success', 'Data kendaraan diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO kendaraan (nomor_kendaraan, nama_kendaraan, jenis_kendaraan, uid_rfid, status) VALUES (?,?,?,?,?)')
                    ->execute([$nomor, $nama, $jenis, $uid, $status]);
                flash('success', 'Kendaraan baru ditambahkan.');
            }
            header('Location: kendaraan.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Nomor kendaraan atau UID RFID sudah terdaftar.';
        }
    }
}

$edit = null;
if (is_admin() && isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM kendaraan WHERE id_kendaraan = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}

$daftar = $pdo->query('SELECT * FROM kendaraan ORDER BY id_kendaraan DESC')->fetchAll();
$aktif = (int) $pdo->query("SELECT COUNT(*) FROM kendaraan WHERE status = 'Aktif'")->fetchColumn();
$nonaktif = count($daftar) - $aktif;
$totalTimbang = (int) $pdo->query('SELECT COUNT(*) FROM penimbangan')->fetchColumn();

require __DIR__ . '/includes/header.php';
?>

<p class="section-index">01 / REGISTRASI</p>
<h2 style="font-size:30px;margin-bottom:10px">Data <em style="color:var(--p-accent-hi);font-style:normal">kendaraan</em></h2>
<p class="muted" style="font-size:13px;max-width:60ch;line-height:1.8;margin-bottom:28px">Kelola identitas kendaraan dan kartu RFID yang digunakan pada proses penimbangan. Setiap UID harus terdaftar sebelum palang keluar dibuka.</p>

<section class="kpi-strip" aria-label="Ringkasan kendaraan">
  <div class="kpi kpi-accent"><p class="kpi-label">Total terdaftar</p><p class="kpi-value"><?= count($daftar) ?></p><p class="kpi-note">Seluruh kendaraan</p></div>
  <div class="kpi"><p class="kpi-label">Status aktif</p><p class="kpi-value"><?= $aktif ?></p><p class="kpi-note">Dapat ditimbang</p></div>
  <div class="kpi"><p class="kpi-label">Nonaktif</p><p class="kpi-value"><?= $nonaktif ?></p><p class="kpi-note">Tidak diizinkan lewat</p></div>
  <div class="kpi"><p class="kpi-label">Total penimbangan</p><p class="kpi-value"><?= $totalTimbang ?></p><p class="kpi-note">Catatan tersimpan</p></div>
</section>

<?php if (is_admin()): ?>
<section class="card" style="margin-bottom:24px">
  <div class="card-head">
    <div>
      <p class="section-index"><?= $edit ? '02 / PERUBAHAN DATA' : '02 / PENDAFTARAN BARU' ?></p>
      <h2 class="card-title"><?= $edit ? 'Ubah kendaraan' : 'Tambah kendaraan' ?></h2>
      <p class="card-sub">Lengkapi data berikut untuk mengaitkan kartu RFID dengan kendaraan.</p>
    </div>
    <?php if ($edit): ?><a class="btn btn-ghost btn-sm" href="kendaraan.php">Batal</a><?php endif; ?>
  </div>

  <?php foreach ($errors as $err): ?>
    <div class="alert alert-error" role="alert"><?= h($err) ?></div>
  <?php endforeach; ?>

  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="<?= $edit ? 'ubah' : 'tambah' ?>">
    <input type="hidden" name="id" value="<?= h((string)($edit['id_kendaraan'] ?? '')) ?>">
    <div class="form-grid">
      <div class="field"><label for="nomor">Nomor kendaraan</label>
        <input id="nomor" name="nomor_kendaraan" required maxlength="20"
               value="<?= h($edit['nomor_kendaraan'] ?? '') ?>" placeholder="B 1234 ABC"></div>
      <div class="field"><label for="nama">Nama / kode kendaraan</label>
        <input id="nama" name="nama_kendaraan" required maxlength="100"
               value="<?= h($edit['nama_kendaraan'] ?? '') ?>" placeholder="Mobil Pengangkut A"></div>
      <div class="field"><label for="jenis">Jenis kendaraan</label>
        <input id="jenis" name="jenis_kendaraan" required maxlength="50"
               value="<?= h($edit['jenis_kendaraan'] ?? '') ?>" placeholder="Truck, Pickup, Box"></div>
      <div class="field"><label for="uid">UID RFID</label>
        <input id="uid" name="uid_rfid" required maxlength="50"
               value="<?= h($edit['uid_rfid'] ?? '') ?>" placeholder="A1B2C3D4"></div>
      <div class="field"><label for="status">Status</label>
        <select id="status" name="status">
          <option value="Aktif" <?= ($edit['status'] ?? 'Aktif') === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
          <option value="Tidak Aktif" <?= ($edit['status'] ?? '') === 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
        </select></div>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        <?= $edit ? 'Simpan perubahan' : 'Tambah kendaraan' ?>
      </button>
    </div>
  </form>
</section>
<?php endif; ?>

<section class="card">
  <div class="card-head">
    <div><p class="section-index"><?= is_admin() ? '03' : '02' ?> / DAFTAR</p><h2 class="card-title">Daftar kendaraan</h2><p class="card-sub"><?= count($daftar) ?> kendaraan terdaftar dalam sistem.</p></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>No</th><th>Kendaraan</th><th>Jenis</th><th>UID RFID</th><th>Status</th><?= is_admin() ? '<th class="align-right">Aksi</th>' : '' ?></tr></thead>
      <tbody>
      <?php if (!$daftar): ?>
        <tr><td colspan="6" class="muted">Belum ada kendaraan terdaftar.</td></tr>
      <?php else: foreach ($daftar as $i => $k): ?>
        <tr>
          <td class="row-index"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></td>
          <td>
            <div class="mono-cell">
              <span class="mono-avatar" aria-hidden="true"><?= h(strtoupper(mb_substr($k['nomor_kendaraan'], 0, 1))) ?></span>
              <span><strong><?= h($k['nomor_kendaraan']) ?></strong><br><span class="muted" style="font-size:11px"><?= h($k['nama_kendaraan']) ?></span></span>
            </div>
          </td>
          <td><?= h($k['jenis_kendaraan']) ?></td>
          <td class="num"><?= h($k['uid_rfid']) ?></td>
          <td><span class="badge <?= $k['status'] === 'Aktif' ? 'badge-ok' : 'badge-neutral' ?>"><?= h($k['status']) ?></span></td>
          <?php if (is_admin()): ?>
          <td class="align-right">
            <div class="btn-row" style="justify-content:flex-end">
              <a class="btn btn-ghost btn-sm" href="kendaraan.php?edit=<?= (int)$k['id_kendaraan'] ?>">Ubah</a>
              <form method="post" onsubmit="return confirm('Hapus kendaraan ini?')">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" value="<?= (int)$k['id_kendaraan'] ?>">
                <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
              </form>
            </div>
          </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
