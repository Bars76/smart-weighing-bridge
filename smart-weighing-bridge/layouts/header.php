<?php
/**
 * Layout Header - Smart Weighing Bridge
 * Output: doctype, head, body, dashboard-layout, sidebar, main-content, content-header
 */

declare(strict_types=1);

// Hitung kedalaman direktori dari root web untuk membuat relative path yang benar
$uri      = $_SERVER['REQUEST_URI'] ?? '/';
$pathPart = parse_url($uri, PHP_URL_PATH);
$dir      = dirname($pathPart); // misal: /smart-weighing-bridge atau /smart-weighing-bridge/pages
$depth    = substr_count($dir, '/') - 1; // kurangi leading slash
$depth    = max(0, $depth);

$assetsPrefix = $depth > 0 ? str_repeat('../', $depth) : '';
$navPrefix    = $depth > 0 ? str_repeat('../', $depth) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Smart Weighing Bridge'); ?> - Smart Weighing Bridge</title>
    <link rel="stylesheet" href="<?php echo $assetsPrefix; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        /* =============================================================================
           Smart Weighing Bridge - Custom Styles
           Tema monitoring industri IoT: navy gelap, putih, aksen biru
           ============================================================================= */
        :root {
          --navy-dark: #0f172a;
          --navy: #1e293b;
          --blue: #3b82f6;
          --blue-dark: #2563eb;
          --white: #ffffff;
          --slate: #f1f5f9;
          --border: #e2e8f0;
          --text: #0f172a;
          --text-muted: #64748b;
          --success: #22c55e;
          --danger: #ef4444;
          --warning: #f59e0b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
          font-family: 'Inter', sans-serif;
          background: #f1f5f9;
          color: var(--text);
          line-height: 1.6;
          margin: 0;
        }
        a { text-decoration: none; color: inherit; }
        button { font-family: inherit; cursor: pointer; border: none; background: transparent; }
        ul, ol { list-style: none; }
        img { max-width: 100%; display: block; }

        .me-2 { margin-right: 0.5rem; }
        .text-center { text-align: center; }

        .badge {
          display: inline-flex;
          align-items: center;
          padding: 4px 10px;
          border-radius: 20px;
          font-size: 11px;
          font-weight: 600;
          text-transform: uppercase;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger  { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-secondary { background: #e2e8f0; color: #475569; }

        .card {
          background: var(--white);
          border: 1px solid var(--border);
          border-radius: 14px;
          padding: 24px;
        }

        .alert {
          padding: 12px 16px;
          border-radius: 8px;
          font-size: 13px;
          margin-bottom: 16px;
        }

        .table-wrapper { overflow-x: auto; border: 1px solid var(--border); border-radius: 12px; }
        .data-table {
          width: 100%;
          border-collapse: collapse;
          background: var(--white);
          font-size: 13px;
        }
        .data-table th {
          background: #f8fafc;
          color: var(--text-muted);
          font-weight: 600;
          text-transform: uppercase;
          letter-spacing: 0.3px;
          padding: 14px 16px;
          text-align: left;
          border-bottom: 1px solid var(--border);
          font-size: 12px;
        }
        .data-table td {
          padding: 14px 16px;
          border-bottom: 1px solid #f1f5f9;
          color: #334155;
        }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover { background: #f8fafc; }

        .form-group { margin-bottom: 16px; }
        .form-group label {
          display: block;
          margin-bottom: 6px;
          font-weight: 600;
          font-size: 13px;
          color: #334155;
        }
        .form-group input,
        .form-group select {
          width: 100%;
          padding: 10px 14px;
          border: 1px solid #cbd5e1;
          border-radius: 8px;
          font-size: 13px;
          color: var(--text);
          background: var(--white);
        }

        .btn-sm {
          padding: 6px 14px;
          background: var(--blue);
          color: var(--white);
          font-size: 13px;
          font-weight: 600;
          border-radius: 8px;
          transition: background 0.2s;
        }
        .btn-sm:hover { background: var(--blue-dark); }

        /* ---------- Sidebar ---------- */
        .dashboard-layout {
          display: flex;
          min-height: 100vh;
        }
        .sidebar {
          width: 260px;
          background: #0f172a;
          color: #ffffff;
          display: flex;
          flex-direction: column;
          position: fixed;
          height: 100vh;
          overflow-y: auto;
          z-index: 100;
          transition: transform 0.3s ease;
          box-shadow: 4px 0 12px rgba(0,0,0,0.15);
        }
        .sidebar-brand {
          padding: 24px 20px;
          display: flex;
          align-items: center;
          gap: 12px;
          border-bottom: 1px solid #1e293b;
        }
        .brand-icon {
          width: 44px;
          height: 44px;
          background: linear-gradient(135deg, #3b82f6, #2563eb);
          border-radius: 10px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 20px;
          color: #fff;
          box-shadow: 0 4px 6px rgba(59,130,246,0.3);
        }
        .brand-text { display: flex; flex-direction: column; }
        .brand-name { font-weight: 700; font-size: 15px; letter-spacing: 0.5px; }
        .brand-sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }

        .sidebar-nav { padding: 24px 0; flex: 1; }
        .nav-item {
          display: flex;
          align-items: center;
          gap: 14px;
          padding: 14px 24px;
          color: #cbd5e1;
          font-size: 14px;
          font-weight: 500;
          transition: all 0.2s;
          border-left: 3px solid transparent;
        }
        .nav-item:hover { background: #1e293b; color: #ffffff; }
        .nav-item.active {
          background: #1e293b;
          color: #ffffff;
          border-left: 3px solid #3b82f6;
        }
        .nav-item i { width: 24px; text-align: center; font-size: 16px; }

        .sidebar-footer {
          padding: 24px;
          border-top: 1px solid #1e293b;
        }
        .user-info {
          display: flex;
          align-items: center;
          gap: 12px;
          margin-bottom: 20px;
        }
        .user-avatar {
          width: 44px;
          height: 44px;
          background: linear-gradient(135deg, #3b82f6, #2563eb);
          border-radius: 12px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 18px;
          color: #fff;
        }
        .user-meta { display: flex; flex-direction: column; }
        .user-name { font-weight: 600; font-size: 14px; color: #ffffff; }
        .user-role { font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.4px; }
        .btn-logout {
          width: 100%;
          padding: 10px 16px;
          background: transparent;
          color: #94a3b8;
          border: 1px solid #334155;
          border-radius: 8px;
          font-size: 13px;
          font-weight: 500;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 8px;
          transition: all 0.2s;
        }
        .btn-logout:hover { background: #334155; color: #ffffff; border-color: #475569; }

        /* ---------- Main Content ---------- */
        .main-content {
          flex: 1;
          margin-left: 260px;
          padding: 0;
          min-height: 100vh;
          display: flex;
          flex-direction: column;
        }
        .content-header {
          background: #ffffff;
          border-bottom: 1px solid #e2e8f0;
          padding: 20px 32px;
          display: flex;
          justify-content: space-between;
          align-items: center;
          flex-shrink: 0;
        }
        .header-left h1.page-title {
          font-size: 22px;
          font-weight: 700;
          color: #0f172a;
          margin-bottom: 4px;
        }
        .page-subtitle { font-size: 13px; color: #64748b; }
        .header-right { display: flex; align-items: center; gap: 24px; }
        .datetime {
          font-size: 14px;
          font-weight: 600;
          color: #334155;
          font-variant-numeric: tabular-nums;
          min-width: 150px;
          text-align: right;
        }
        .status-indicator {
          display: flex;
          align-items: center;
          gap: 8px;
          padding: 6px 12px;
          background: #f8fafc;
          border: 1px solid #e2e8f0;
          border-radius: 20px;
          font-size: 13px;
          font-weight: 600;
          color: #334155;
        }
        .status-dot {
          width: 8px;
          height: 8px;
          border-radius: 50%;
          background: #94a3b8;
          animation: pulse 2s infinite;
        }
        .status-dot.online { background: #22c55e; }
        .status-dot.offline { background: #ef4444; }
        @keyframes pulse {
          0%, 100% { opacity: 1; }
          50% { opacity: 0.5; }
        }

        .main-body {
          flex: 1;
          overflow-x: auto;
        }

        /* ---------- Stats Grid ---------- */
        .stats-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
          gap: 20px;
          padding: 24px 32px;
        }
        .stat-card {
          background: #ffffff;
          border: 1px solid #e2e8f0;
          border-radius: 14px;
          padding: 22px;
          display: flex;
          align-items: center;
          gap: 16px;
          transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
          transform: translateY(-2px);
          box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
        }
        .stat-icon {
          width: 48px;
          height: 48px;
          border-radius: 12px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 20px;
          color: #ffffff;
          flex-shrink: 0;
        }
        .stat-icon.bg-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .stat-icon.bg-orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-icon.bg-green { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .stat-icon.bg-purple { background: linear-gradient(135deg, #a855f7, #9333ea); }
        .stat-label { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 4px; }
        .stat-value { font-size: 24px; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }

        /* ---------- Section Header ---------- */
        .section-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin: 0 32px 16px;
        }
        .section-title { font-size: 17px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .btn-icon {
          width: 32px;
          height: 32px;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          border-radius: 6px;
          color: #64748b;
          background: #f1f5f9;
          font-size: 14px;
          margin: 0 2px;
          transition: all 0.2s;
        }
        .btn-icon:hover { background: #e2e8f0; color: #3b82f6; }

        /* ---------- Monitoring ---------- */
        .monitoring-section { padding: 0 32px 24px; }
        .monitoring-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
          gap: 14px;
        }
        .mon-card {
          background: #ffffff;
          border: 1px solid #e2e8f0;
          border-radius: 12px;
          padding: 18px 16px;
          display: flex;
          flex-direction: column;
          gap: 6px;
        }
        .mon-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; }
        .mon-value { font-size: 20px; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; word-break: break-all; }
        .mon-unit { font-size: 12px; color: #64748b; font-weight: 500; }
        .mon-weight .mon-value { color: #2563eb; }

        /* ---------- Device Status ---------- */
        .device-section { padding: 0 32px 24px; }
        .device-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
          gap: 14px;
        }
        .device-item {
          background: #ffffff;
          border: 1px solid #e2e8f0;
          border-radius: 12px;
          padding: 16px;
          display: flex;
          align-items: center;
          gap: 12px;
          transition: border-color 0.2s;
        }
        .device-item:hover { border-color: #cbd5e1; }
        .device-icon {
          width: 40px;
          height: 40px;
          border-radius: 10px;
          background: #f1f5f9;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 18px;
          color: #334155;
          flex-shrink: 0;
        }
        .device-info { display: flex; flex-direction: column; }
        .device-name { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; }
        .device-val { font-size: 14px; font-weight: 600; color: #0f172a; }
        .text-success { color: #166534; }
        .text-danger { color: #991b1b; }
        .text-warning { color: #92400e; }
        .text-muted { color: #64748b; }
        .device-connection {
          font-size: 12px;
          font-weight: 600;
          padding: 4px 10px;
          border-radius: 20px;
          background: #f1f5f9;
          color: #334155;
        }
        .device-last-seen { margin-top: 10px; padding-left: 4px; }
        .device-last-seen small { color: #64748b; font-size: 12px; }

        /* ---------- Charts ---------- */
        .charts-section { padding: 0 32px 24px; }
        .chart-filter {
          display: flex;
          gap: 8px;
          margin-bottom: 16px;
        }
        .filter-btn {
          padding: 8px 16px;
          font-size: 13px;
          font-weight: 600;
          color: #64748b;
          background: #ffffff;
          border: 1px solid #e2e8f0;
          border-radius: 8px;
          transition: all 0.2s;
        }
        .filter-btn:hover { color: #3b82f6; border-color: #3b82f6; }
        .filter-btn.active {
          background: #3b82f6;
          color: #ffffff;
          border-color: #3b82f6;
        }
        .chart-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
          gap: 20px;
        }
        .chart-card {
          background: #ffffff;
          border: 1px solid #e2e8f0;
          border-radius: 14px;
          padding: 20px;
          height: 300px;
          display: flex;
          flex-direction: column;
        }

        /* ---------- Recent Table ---------- */
        .recent-table-section { padding: 0 32px 40px; }
        .table-controls {
          display: flex;
          gap: 12px;
          margin-bottom: 16px;
          align-items: center;
        }
        .table-controls input[type="text"],
        .table-controls select {
          padding: 10px 14px;
          border: 1px solid #cbd5e1;
          border-radius: 8px;
          font-size: 13px;
          color: #0f172a;
          background: #ffffff;
        }
        .table-controls input[type="text"] { min-width: 260px; }
        .table-controls select { min-width: 170px; cursor: pointer; }
        .pagination-info {
          margin-top: 12px;
          font-size: 13px;
          color: #64748b;
          padding-left: 4px;
        }

        /* Responsive */
        @media (max-width: 900px) {
          .sidebar { transform: translateX(-100%); }
          .main-content { margin-left: 0; }
          .header-right { gap: 12px; }
          .datetime { min-width: auto; }
        }
        @media (max-width: 768px) {
          .card { padding: 16px; }
          .data-table { font-size: 12px; }
          .data-table th, .data-table td { padding: 10px 12px; }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon"><i class="fas fa-weight-scale"></i></div>
                <div class="brand-text">
                    <div class="brand-name">Smart Weighing</div>
                    <div class="brand-sub">Bridge</div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="<?php echo $navPrefix; ?>dashboard.php" class="nav-item <?php echo ($activePage ?? '') === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie nav-icon"></i>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="<?php echo $navPrefix; ?>pages/vehicle-data.php" class="nav-item <?php echo ($activePage ?? '') === 'vehicles' ? 'active' : ''; ?>">
                    <i class="fas fa-truck nav-icon"></i>
                    <span class="nav-label">Data Kendaraan</span>
                </a>
                <a href="<?php echo $navPrefix; ?>pages/weighing-history.php" class="nav-item <?php echo ($activePage ?? '') === 'history' ? 'active' : ''; ?>">
                    <i class="fas fa-history nav-icon"></i>
                    <span class="nav-label">Riwayat Penimbangan</span>
                </a>
                <a href="<?php echo $navPrefix; ?>pages/device-monitoring.php" class="nav-item <?php echo ($activePage ?? '') === 'devices' ? 'active' : ''; ?>">
                    <i class="fas fa-microchip nav-icon"></i>
                    <span class="nav-label">Monitoring Perangkat</span>
                </a>
                <a href="<?php echo $navPrefix; ?>pages/settings.php" class="nav-item <?php echo ($activePage ?? '') === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog nav-icon"></i>
                    <span class="nav-label">Pengaturan</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar"><i class="fas fa-user"></i></div>
                    <div class="user-meta">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Pengguna'); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($_SESSION['role'] ?? 'operator'); ?></div>
                    </div>
                </div>
                <a href="<?php echo $navPrefix; ?>logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Keluar
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <div class="header-left">
                    <h1 class="page-title"><?php echo htmlspecialchars($pageTitle ?? 'Dashboard'); ?></h1>
                    <p class="page-subtitle"><?php echo htmlspecialchars($pageSubtitle ?? ''); ?></p>
                </div>
                <div class="header-right">
                    <div class="datetime" id="currentDateTime"></div>
                    <div class="status-indicator">
                        <span class="status-dot" id="espDot"></span>
                        <span class="status-text" id="espText">Memuat...</span>
                    </div>
                </div>
            </header>

            <div class="main-body">