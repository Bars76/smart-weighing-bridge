<?php
/** Header + sidebar untuk halaman aplikasi (setelah login). */
$titles = [
    'dashboard.php'  => 'Dashboard',
    'kendaraan.php'  => 'Data Kendaraan',
    'riwayat.php'    => 'Riwayat Penimbangan',
    'pengaturan.php' => 'Pengaturan',
    'lihat.php'      => 'Monitor Publik',
];
$current = basename($_SERVER['PHP_SELF']);
$page_title = $titles[$current] ?? 'Smart Weighing Bridge';
$user = current_user();
$role_label = user_role() === 'admin' ? 'Administrator' : 'Operator';

// Status koneksi ESP32 dihitung dari waktu heartbeat terakhir di database.
$lastSeenRaw = pengaturan('esp32_last_seen', '');
$lastSeenTs  = ($lastSeenRaw !== '' && $lastSeenRaw !== '0000-00-00 00:00:00')
    ? strtotime($lastSeenRaw) : 0;
$selisihDetik = $lastSeenTs > 0 ? (time() - $lastSeenTs) : -1;
$espOnline   = ($selisihDetik >= 0 && $selisihDetik <= 60);
$espLabel    = $espOnline ? 'ESP32 Terhubung' : 'ESP32 Terputus';
$espKeterangan = $lastSeenTs > 0
    ? date('d M Y, H:i:s', $lastSeenTs)
    : 'Belum pernah terhubung';
?>
<!doctype html>
<html lang="id" data-type="software" data-radius="soft" data-buttons="solid" data-motion>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="page-type" content="software">
  <meta name="site-kind" content="software">
  <title><?= h($page_title) ?> · Smart Weighing Bridge</title>
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="app">
  <a class="skip-link" href="#konten">Lewati ke konten</a>

  <div class="shell">
    <aside class="sidebar" aria-label="Navigasi utama">
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

      <nav class="side-nav">
        <a href="dashboard.php" class="nav-item <?= $current === 'dashboard.php' ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h7v7H4zM13 4h7v4h-7zM13 11h7v9h-7zM4 14h7v6H4z"/></svg>
          Dashboard
        </a>
        <a href="kendaraan.php" class="nav-item <?= $current === 'kendaraan.php' ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 16l2-6h14l2 6v3h-3a2 2 0 01-4 0h-4a2 2 0 01-4 0H3zM7 9l1.5-4h7L17 9"/></svg>
          Data Kendaraan
        </a>
        <a href="riwayat.php" class="nav-item <?= $current === 'riwayat.php' ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 7v5l3 2M21 12a9 9 0 11-3-6.7M21 4v5h-5"/></svg>
          Riwayat Penimbangan
        </a>
        <?php if (is_admin()): ?>
        <a href="pengaturan.php" class="nav-item <?= $current === 'pengaturan.php' ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 15a3 3 0 100-6 3 3 0 000 6zM19.4 15a1.7 1.7 0 00.3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 00-1.9-.3 1.7 1.7 0 00-1 1.6v.2h-4v-.2a1.7 1.7 0 00-1-1.6 1.7 1.7 0 00-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 00.3-1.9 1.7 1.7 0 00-1.6-1H3v-4h.2a1.7 1.7 0 001.6-1 1.7 1.7 0 00-.3-1.9L4.4 7 7.2 4.2l.1.1a1.7 1.7 0 001.9.3 1.7 1.7 0 001-1.6V3h4v.2a1.7 1.7 0 001 1.6 1.7 1.7 0 001.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 00-.3 1.9 1.7 1.7 0 001.6 1h.2v4h-.2a1.7 1.7 0 00-1.6 1z"/></svg>
          Pengaturan
        </a>
        <?php endif; ?>
        <a href="lihat.php" class="nav-item <?= $current === 'lihat.php' ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
          Monitor Publik
        </a>
      </nav>

      <div class="side-foot">
        <div class="user-card">
          <span class="avatar" aria-hidden="true"><?= h(strtoupper(mb_substr($user['nama_lengkap'] ?? 'U', 0, 1))) ?></span>
          <span class="user-meta"><strong><?= h($user['nama_lengkap'] ?? 'Pengguna') ?></strong><small><?= h($role_label) ?></small></span>
        </div>
        <form method="post" action="logout.php">
          <?= csrf_field() ?>
          <button class="btn btn-ghost btn-block" type="submit">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M21 19V5a2 2 0 00-2-2h-6"/></svg>
            Keluar
          </button>
        </form>
      </div>
    </aside>

    <main class="main" id="konten">
      <header class="topbar">
        <div>
          <p class="eyebrow">Smart Weighing Bridge</p>
          <h1 class="topbar-title"><?= h($page_title) ?></h1>
        </div>
        <div class="topbar-status">
          <span class="pill <?= $espOnline ? 'pill-ok' : 'pill-off' ?>" title="Terakhir terhubung: <?= h($espKeterangan) ?>">
            <span class="dot"></span> <?= $espLabel ?>
          </span>
        </div>
      </header>

      <div class="content">
        <?php $flash = take_flash(); if ($flash): ?>
          <div class="alert alert-<?= h($flash['jenis']) ?>" role="status"><?= h($flash['pesan']) ?></div>
        <?php endif; ?>
