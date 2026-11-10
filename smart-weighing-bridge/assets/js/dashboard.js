/**
 * Smart Weighing Bridge - Dashboard JavaScript
 * Mengelola pembaruan data secara berkala tanpa reload halaman
 */

const API_BASE = 'api'; // Relatif terhadap root proyek

let refreshInterval = null;
const REFRESH_MS = 10000; // Perbarui setiap 10 detik

function initDashboard(initialStats, initialDevice) {
  // Tampilkan waktu dan tanggal saat ini
  updateDateTime();
  setInterval(updateDateTime, 1000);

  // Set status awal perangkat dari PHP
  renderDeviceStatus(initialDevice);

  // Muat statistik dan grafik
  loadStatistics();
  loadRecentWeighings();

  // Mulai polling berkala
  startRefresh();
}

function updateDateTime() {
  const now = new Date();
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
  const el = document.getElementById('currentDateTime');
  if (el) el.textContent = now.toLocaleString('id-ID', options);
}

function startRefresh() {
  if (refreshInterval) clearInterval(refreshInterval);
  refreshInterval = setInterval(() => {
    loadStatistics();
    loadRecentWeighings();
    loadDeviceStatus();
  }, REFRESH_MS);
}

function stopRefresh() {
  if (refreshInterval) clearInterval(refreshInterval);
}

/* ------------------------------------------------------------------ */
/* Statistik                                                          */
/* ------------------------------------------------------------------ */
async function loadStatistics() {
  try {
    const res = await fetch(`${API_BASE}/statistics.php`, { cache: 'no-store' });
    if (!res.ok) throw new Error('Gagal memuat statistik');
    const data = await res.json();
    if (!data.success) return;

    const d = data.data;

    setText('statTotalWeighings', d.total_weighings);
    setText('statTotalWeight', d.total_weight_kg.toFixed(2) + ' kg');
    setText('statTodayWeighings', d.today_weighings);
    setText('statAvgWeight', d.avg_weight_kg.toFixed(2) + ' kg');

    // Update grafik jika tersedia
    if (typeof window.updateCharts === 'function') {
      window.updateCharts(d);
    }
  } catch (err) {
    console.error('Statistik gagal dimuat:', err);
  }
}

/* ------------------------------------------------------------------ */
/* Status perangkat                                                   */
/* ------------------------------------------------------------------ */
async function loadDeviceStatus() {
  try {
    // Ambil device logs terbaru untuk tiap komponen
    // Kita gunakan endpoint statistics untuk mendapatkan device status melalui dashboard PHP
    // Namun untuk real-time, kita ambil langsung dari DB lewat statistics atau endpoint khusus.
    // Di sini kita asumsikan dashboard sudah menyertakan data awal dan polling dilakukan
    // melalui endpoint yang akan ditambahkan. Untuk sekarang, gunakan data dari variabel awal.
  } catch (err) {
    console.error('Device status gagal dimuat:', err);
  }
}

function renderDeviceStatus(device) {
  const dot = document.getElementById('espDot');
  const text = document.getElementById('espText');
  const connBadge = document.getElementById('deviceConnBadge');
  const devEsp32 = document.getElementById('devEsp32');
  const devWifi = document.getElementById('devWifi');

  const isOnline = device.connection_status === 'online';

  if (dot) {
    dot.className = 'status-dot ' + (isOnline ? 'online' : 'offline');
  }
  if (text) {
    text.textContent = isOnline ? 'ESP32 Online' : 'ESP32 Offline / Belum Tersedia';
  }
  if (connBadge) connBadge.textContent = isOnline ? 'Online' : 'Offline / Belum Tersedia';
  if (devEsp32) devEsp32.textContent = isOnline ? 'Online' : 'Offline / Belum Tersedia';
  if (devWifi) devWifi.textContent = isOnline ? 'Terhubung' : 'Terputus / Belum Tersedia';

  // Komponen
  const compMap = {
    RFID: 'devRfid',
    IR_IN: 'devIrIn',
    IR_OUT: 'devIrOut',
    HX711: 'devHx711',
    Servo: 'devServo',
  };

  const compByName = {};
  (device.components || []).forEach(c => { compByName[c.component_name] = c.component_status; });

  Object.entries(compMap).forEach(([name, elId]) => {
    const el = document.getElementById(elId);
    if (el) {
      const status = compByName[name] || 'unknown';
      el.textContent = componentLabel(status);
      el.className = 'device-val ' + componentClass(status);
    }
  });

  const lastSeenEl = document.querySelector('.device-last-seen small');
  if (device.last_seen && lastSeenEl) {
    lastSeenEl.textContent = 'Terakhir dilihat: ' + device.last_seen;
    lastSeenEl.parentElement.style.display = 'block';
  }
}

function componentLabel(status) {
  const map = {
    active: 'Aktif',
    inactive: 'Tidak Aktif',
    open: 'Terbuka',
    closed: 'Tertutup',
    unknown: 'Belum Tersedia',
  };
  return map[status] || 'Belum Tersedia';
}

function componentClass(status) {
  const map = {
    active: 'text-success',
    inactive: 'text-danger',
    open: 'text-warning',
    closed: 'text-success',
    unknown: 'text-muted',
  };
  return map[status] || 'text-muted';
}

/* ------------------------------------------------------------------ */
/* Penimbangan terbaru                                                */
/* ------------------------------------------------------------------ */
async function loadRecentWeighings() {
  try {
    const res = await fetch(`${API_BASE}/statistics.php`, { cache: 'no-store' });
    // Tabel terbaru di-render oleh PHP; JS hanya refresh statistik.
    // Untuk update tabel tanpa reload, dapat ditambahkan endpoint terpisah.
  } catch (err) {
    console.error('Recent weighings gagal dimuat:', err);
  }
}

function setText(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = value;
}

// Expose untuk charts.js
window.initDashboard = initDashboard;
window.renderDeviceStatus = renderDeviceStatus;