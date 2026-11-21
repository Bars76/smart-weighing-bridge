<?php
/** Ganti password (wajib untuk akun bawaan pada login pertama). */
declare(strict_types=1);
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require_login();

$errors = [];
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $lama = $_POST['password_lama'] ?? '';
    $baru = $_POST['password_baru'] ?? '';
    $ulang = $_POST['password_ulang'] ?? '';

    $stmt = $pdo->prepare('SELECT password FROM users WHERE id_user = ? LIMIT 1');
    $stmt->execute([(int) $user['id']]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($lama, (string) $hash)) {
        $errors[] = 'Password lama tidak benar.';
    }
    if (strlen($baru) < 8) {
        $errors[] = 'Password baru minimal 8 karakter.';
    }
    if ($baru !== $ulang) {
        $errors[] = 'Konfirmasi password baru tidak cocok.';
    }

    if (!$errors) {
        $newHash = password_hash($baru, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password = ?, must_change_password = 0 WHERE id_user = ?')
            ->execute([$newHash, (int) $user['id']]);
        $_SESSION['user']['must_change'] = false;
        session_regenerate_id(true);
        flash('success', 'Password berhasil diubah. Selamat menggunakan sistem.');
        header('Location: dashboard.php');
        exit;
    }
}

require __DIR__ . '/includes/header.php';
?>

<p class="section-index">KEAMANAN / AKUN</p>
<h2 style="font-size:30px;margin-bottom:10px">Ganti <em style="color:var(--p-accent);font-style:normal">password</em></h2>
<p class="muted" style="font-size:13px;max-width:60ch;line-height:1.8;margin-bottom:28px">
  <?= !empty($user['must_change'])
        ? 'Untuk keamanan, password bawaan harus diganti sebelum Anda menggunakan dashboard.'
        : 'Perbarui password akun Anda secara berkala.' ?>
</p>

<section class="card" style="max-width:560px">
  <div class="card-head"><div><p class="section-index">FORM</p><h2 class="card-title">Password baru</h2><p class="card-sub">Gunakan minimal 8 karakter, kombinasi huruf dan angka.</p></div></div>

  <?php foreach ($errors as $err): ?>
    <div class="alert alert-error" role="alert"><?= h($err) ?></div>
  <?php endforeach; ?>

  <form method="post">
    <?= csrf_field() ?>
    <div class="field" style="margin-bottom:18px"><label for="password_lama">Password lama</label>
      <input id="password_lama" name="password_lama" type="password" required autocomplete="current-password"></div>
    <div class="field" style="margin-bottom:18px"><label for="password_baru">Password baru</label>
      <input id="password_baru" name="password_baru" type="password" required minlength="8" autocomplete="new-password"></div>
    <div class="field"><label for="password_ulang">Ulangi password baru</label>
      <input id="password_ulang" name="password_ulang" type="password" required minlength="8" autocomplete="new-password"></div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Simpan password</button>
      <?php if (empty($user['must_change'])): ?><a class="btn btn-ghost" href="dashboard.php">Batal</a><?php endif; ?>
    </div>
  </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
