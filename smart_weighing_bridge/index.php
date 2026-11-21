<?php
require __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$flash = take_flash();
$error = $flash['pesan'] ?? '';
?>
<!doctype html>
<html lang="id" data-type="software" data-radius="soft" data-motion>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="page-type" content="software">
  <meta name="site-kind" content="software">
  <meta name="description" content="Masuk ke Smart Weighing Bridge — dashboard jembatan timbang IoT.">
  <title>Masuk · Smart Weighing Bridge</title>
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="login-page">
  <section class="login-art" data-enter aria-label="Tentang sistem">
    <div class="brand">
      <span class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 32 32" width="32" height="32" fill="none">
          <rect x="2.75" y="2.75" width="26.5" height="26.5" rx="3" stroke="#0071e3" stroke-width="1.5"/>
          <path d="M8 21.5V14m5 7.5V10m5 11.5v-5m5 5V12" stroke="#0071e3" stroke-width="2" stroke-linecap="round"/>
          <path d="M7 24h18" stroke="#0071e3" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </span>
      <span class="brand-text"><strong>Smart Weighing</strong><small>Jembatan Timbang IoT</small></span>
    </div>

    <div class="login-art-copy">
      <h2>Timbang kendaraan. <em>Catat hasilnya.</em> Pantau dari mana saja.</h2>
      <p>Sistem jembatan timbang mini berbasis ESP32 yang menghubungkan sensor berat, RFID, dan palang otomatis ke satu dashboard.</p>
    </div>

    <div class="login-illus" aria-hidden="true">
      <svg viewBox="0 0 460 200" fill="none">
        <path d="M20 150h420" stroke="#86868b" stroke-width="1"/>
        <path d="M60 150V96h340v54" stroke="#aeaeb2" stroke-width="1.2"/>
        <path d="M96 96V70h268v26" stroke="#c7c7cc" stroke-width="1.2"/>
        <rect x="150" y="52" width="160" height="18" rx="2" stroke="#0071e3" stroke-width="1.2"/>
        <path d="M230 52V30" stroke="#aeaeb2" stroke-width="1"/>
        <circle cx="230" cy="24" r="5" stroke="#0071e3" stroke-width="1.2"/>
        <path d="M60 96l-24-6v-18h24M400 96l24-6v-18h-24" stroke="#aeaeb2" stroke-width="1.2"/>
        <path d="M36 72v18M424 72v18" stroke="#aeaeb2" stroke-width="1.2"/>
        <g stroke="#34c759" stroke-width="1.2">
          <path d="M120 122h40M120 128h24"/>
          <path d="M300 122h40M300 128h24"/>
        </g>
        <circle cx="230" cy="110" r="25" stroke="#0071e3" stroke-width="1.2"/>
        <path d="M230 96v14l10 6" stroke="#0071e3" stroke-width="1.2" stroke-linecap="round"/>
      </svg>
    </div>

    <div class="login-art-foot">Prototipe IoT · ESP32 · PHP · MySQL · XAMPP</div>
  </section>

  <section class="login-panel" data-enter>
    <div class="login-box">
      <p class="eyebrow">Smart Weighing Bridge</p>
      <h1>Masuk ke sistem</h1>
      <p class="lead">Gunakan akun Admin atau Operator untuk mengelola dan memantau penimbangan.</p>

      <?php if ($error): ?>
        <div class="alert alert-error" role="alert"><?= h($error) ?></div>
      <?php endif; ?>

      <form action="proses_login.php" method="post" autocomplete="on">
        <?= csrf_field() ?>
        <div class="field">
          <label for="username">Username</label>
          <input id="username" name="username" type="text" required autofocus
                 autocomplete="username" placeholder="Masukkan username">
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" required
                 autocomplete="current-password" placeholder="Masukkan password">
        </div>
        <button class="btn btn-primary btn-block" type="submit">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M21 19V5a2 2 0 00-2-2h-6"/></svg>
          Masuk
        </button>
      </form>

      <p class="login-note">
        Akun demo: <code>admin</code> / <code>admin123</code> (Administrator) ·
        <code>operator</code> / <code>operator123</code> (Operator).
      </p>
    </div>
  </section>
</body>
</html>
