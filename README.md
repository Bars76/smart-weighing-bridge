# smart-weighing-bridge
# README1.md — Panduan Penggunaan & Konfigurasi Smart Weighing Bridge

Panduan lengkap untuk pemula: mulai dari instalasi, import database, konfigurasi ESP32, hingga cara penggunaan oleh orang lain.

---

## 1. Tentang Smart Weighing Bridge

Smart Weighing Bridge adalah sistem monitoring dan pencatatan berat kendaraan berbasis IoT. Sistem ini terdiri dari:

- **Hardware IoT:** ESP32, RFID RC522, 2x Sensor IR, Load Cell HX711, Servo, Wi-Fi
- **Backend:** PHP Native (tanpa framework), MySQL (PDO prepared statements)
- **Frontend:** HTML5, CSS3, JavaScript vanilla, Chart.js
- **Komunikasi:** REST API JSON melalui Wi-Fi

---

## 2. Prasyarat (Sebelum Memulai)

Pastikan komputer Anda telah menginstal:

| Komponen | Keterangan |
|----------|------------|
| XAMPP | Apache + MySQL + PHP (unduh dari https://www.apachefriends.org) |
| Browser web | Chrome, Firefox, Edge, dll. |
| ESP32 | Board mikrokontroler dengan Wi-Fi |
| Sensor pendukung | RFID RC522, 2x Sensor IR, Load Cell HX711, Servo |
| Arduino IDE | Untuk mengupload sketch ke ESP32 |
| Library Arduino | `WiFi.h`, `HTTPClient.h`, `SPI.h`, `MFRC522.h`, `ESP32Servo.h`, `HX711.h` |

---

## 3. Langkah Awal (Cara Menjalankan bagi Orang Lain)

Ikuti langkah-langkah berikut agar siapa saja dapat menjalankan sistem ini:

### Langkah 1: Letakkan File Proyek
Salin seluruh folder `smart-weighing-bridge` ke direktori htdocs XAMPP, contoh:

```
C:\xampp\htdocs\smart-weighing-bridge\
```

Pastikan struktur folder tetap utuh (jangan memindahkan file keluar dari foldernya masing-masing).

### Langkah 2: Nyalakan XAMPP
Buka **XAMPP Control Panel**, lalu nyalakan:
- **Apache**
- **MySQL**

### Langkah 3: Akses Aplikasi
Buka browser dan ketik alamat:

```
http://localhost/smart-weighing-bridge/
```

Aplikasi akan mengarahkan otomatis ke halaman login (`login.php`).

### Langkah 4: Login Pertama Kali
Gunakan akun default yang sudah ada setelah import database:

| Field | Nilai |
|-------|-------|
| Username | `admin` |
| Password | `password` |

> ⚠️ **Penting:** Segera ganti password default setelah login pertama kali melalui halaman **Pengaturan**.

---

## 4. Cara Import Database

Database harus diimpor terlebih dahulu sebelum aplikasi dapat digunakan.

### Langkah-langkah melalui phpMyAdmin

1. Pastikan **Apache** dan **MySQL** sudah nyala di XAMPP.
2. Buka browser: `http://localhost/phpmyadmin/`
3. Klik tab **Database** di atas.
4. Buat database baru dengan nama tepat: `smart_weighing_bridge`
5. Pilih database `smart_weighing_bridge` yang baru dibuat.
6. Klik tab **Import**.
7. Pilih file: `database/smart_weighing_bridge.sql` (ada di dalam folder proyek).
8. Klik tombol **Go** (di bagian bawah).

Tabel yang akan dibuat otomatis:
- `users` — akun administrator
- `vehicles` — data kendaraan terdaftar
- `weighings` — transaksi penimbangan
- `devices` — perangkat IoT terdaftar
- `device_logs` — log status komponen perangkat

### Alternatif via Command Line (CLI)

```bash
mysql -u root -p smart_weighing_bridge < database/smart_weighing_bridge.sql
```

> Jika MySQL meminta password, tekan Enter (kosong) karena default XAMPP biasanya tanpa password.

---

## 5. Struktur Folder Singkat

```
smart-weighing-bridge/
├── index.php                 # Redirect ke login atau dashboard
├── dashboard.php             # Halaman dashboard utama
├── login.php                 # Halaman login administrator
├── logout.php                # Proses logout
│
├── config/
│   └── database.php          # Konfigurasi koneksi PDO ke MySQL
│
├── api/
│   ├── validate.php          # Endpoint validasi RFID (POST)
│   ├── weighings.php         # Endpoint simpan penimbangan (POST)
│   ├── vehicles.php          # Endpoint daftar kendaraan (GET)
│   ├── statistics.php        # Endpoint statistik dashboard (GET)
│   └── device-status.php     # Endpoint heartbeat perangkat (POST)
│
├── pages/
│   ├── vehicle-data.php         # CRUD data kendaraan
│   ├── weighing-history.php     # Riwayat penimbangan + ekspor CSV
│   ├── weighing-detail.php      # Detail transaksi penimbangan
│   ├── device-monitoring.php    # Monitoring perangkat IoT
│   └── settings.php             # Pengaturan akun & token perangkat
│
├── assets/
│   ├── css/style.css          # Styling aplikasi
│   └── js/
│       ├── dashboard.js       # Polling dan pembaruan data
│       └── charts.js          # Chart.js grafik analitik
│
├── layouts/
│   ├── header.php             # Layout header + sidebar
│   └── footer.php             # Layout footer
│
├── database/
│   └── smart_weighing_bridge.sql  # File SQL untuk diimpor
│
├── README.md                  # Dokumentasi asli proyek
└── README1.md                 # Panduan penggunaan ini
```

---

## 6. Cara Menggunakan Aplikasi Web

Setelah login, Anda akan melihat menu di sidebar kiri:

### A. Dashboard
- Menampilkan statistik: total kendaraan ditimbang, total berat, kendaraan hari ini, rata-rata berat.
- Grafik analitik penimbangan (harian, mingguan, bulanan).
- Status perangkat IoT (ESP32 dan komponen).
- Tabel penimbangan terbaru.

### B. Data Kendaraan
- Tambah, edit, nonaktifkan, atau hapus data kendaraan.
- Setiap kendaraan memiliki: ID kendaraan, UID RFID, nomor polisi, jenis, pemilik, dan status aktif/nonaktif.

### C. Riwayat Penimbangan
- Lihat semua transaksi penimbangan yang tersimpan di database.
- Ada filter: nomor polisi, UID RFID, tanggal, jenis kendaraan, dan status.
- Pagination untuk data yang banyak.
- **Ekspor CSV** untuk download data ke Excel.

### D. Detail Penimbangan
- Lihat informasi lengkap satu transaksi (berat, waktu, UID, nomor polisi, pemilik, status).

### E. Monitoring Perangkat
- Pantau status ESP32 dan komponen IoT (RFID, IR_IN, IR_OUT, HX711, Servo, Wi-Fi).
- Status Online/Offline berdasarkan waktu terakhir perangkat terlihat (`last_seen`).

### F. Pengaturan
- Ganti password administrator.
- Kelola device token yang digunakan ESP32 untuk autentikasi API.
- Lihat informasi sistem (versi, backend, database, pengguna saat ini).

---

## 7. Cara Konfigurasi ESP32

ESP32 berkomunikasi dengan server melalui Wi-Fi ke endpoint API yang ada di komputer Anda.

### Langkah 1: Tentukan IP Komputer Server
Pastikan komputer yang menjalankan XAMPP dan ESP32 terhubung ke **jaringan Wi-Fi yang sama**.

Cek IP komputer Anda (Windows):
1. Buka Command Prompt (cmd).
2. Ketik: `ipconfig`
3. Cari alamat IPv4, contoh: `192.168.1.100`

### Langkah 2: Atur API_BASE di Sketch ESP32
Di dalam kode Arduino/ESP32 Anda, tentukan base URL API:

```cpp
const char* API_BASE = "http://192.168.1.100/smart-weighing-bridge/api";
```

Ganti `192.168.1.100` dengan IP komputer tempat XAMPP berjalan.

### Langkah 3: Endpoint yang Digunakan ESP32

| Endpoint | Method | Fungsi |
|----------|--------|--------|
| `/api/validate` | POST | Validasi UID RFID dari kartu |
| `/api/weighings` | POST | Simpan hasil penimbangan (berat, kode transaksi, dll) |
| `/api/device-status` | POST | Heartbeat/status komponen perangkat (RFID, IR, HX711, Servo, Wi-Fi) |

### Contoh Alur Kerja ESP32

1. Saat kartu RFID dibaca → kirim `POST /api/validate` dengan UID.
2. Jika response `valid: true` → nyalakan servo, baca load cell via HX711.
3. Kirim `POST /api/weighings` dengan data berat dan informasi kendaraan.
4. Setiap 30–60 detik → kirim `POST /api/device-status` dengan token perangkat dan status setiap komponen.

### Contoh Request Device Status (JSON)

```json
{
  "device_token": "WEIGHBRIDGE_TOKEN_2026",
  "device_name": "ESP32-Weighbridge-01",
  "components": [
    {"name": "RFID", "status": "active"},
    {"name": "IR_IN", "status": "active"},
    {"name": "IR_OUT", "status": "active"},
    {"name": "HX711", "status": "active"},
    {"name": "Servo", "status": "closed"},
    {"name": "WiFi", "status": "connected"}
  ]
}
```

> Device token harus sama dengan yang sudah terdaftar di halaman **Pengaturan** aplikasi web.

---

## 8. Menguji Endpoint API Tanpa ESP32

Anda bisa mencoba API menggunakan curl (terminal) atau Postman sebelum ESP32 siap.

### Contoh: Validasi RFID
```bash
curl -X POST http://localhost/smart-weighing-bridge/api/validate.php \
  -H "Content-Type: application/json" \
  -d '{"uid":"A1B2C3D4"}'
```

### Contoh: Simpan Penimbangan
```bash
curl -X POST http://localhost/smart-weighing-bridge/api/weighings.php \
  -H "Content-Type: application/json" \
  -d '{"uid":"A1B2C3D4","vehicle_id":"V001","plate":"AB1234CD","vehicle_type":"Truck","weight_kg":1250.50,"status":"weighed"}'
```

### Contoh: Daftar Kendaraan
```bash
curl http://localhost/smart-weighing-bridge/api/vehicles.php
```

### Contoh: Statistik Dashboard
```bash
curl http://localhost/smart-weighing-bridge/api/statistics.php
```

### Contoh: Device Status / Heartbeat
```bash
curl -X POST http://localhost/smart-weighing-bridge/api/device-status.php \
  -H "Content-Type: application/json" \
  -d '{"device_token":"WEIGHBRIDGE_TOKEN_2026","device_name":"ESP32-Weighbridge-01","components":[{"name":"RFID","status":"active"}]}'
```

---

## 9. Troubleshooting / Masalah Umum

| Masalah | Kemungkinan Penyebab | Solusi |
|---------|---------------------|--------|
| Tidak bisa membuka `localhost/smart-weighing-bridge/` | Apache belum nyala | Nyalakan Apache di XAMPP Control Panel |
| Tidak bisa login | Database belum diimpor | Impor `database/smart_weighing_bridge.sql` via phpMyAdmin |
| ESP32 tidak terhubung ke API | IP salah atau jaringan berbeda | Pastikan ESP32 dan komputer di Wi-Fi yang sama; cek IP komputer |
| Status perangkat selalu "Belum Tersedia" | ESP32 belum kirim heartbeat | Pastikan ESP32 mengirim `POST /api/device-status` secara berkala |
| Halaman tampil acak / CSS tidak terbuka | File `assets/css/style.css` tidak ada | Pastikan folder `assets` tidak terhapus |
| "Token keamanan tidak valid" | Masalah CSRF token | Segarkan halaman (F5) dan coba lagi |

---

## 10. Catatan Keamanan

- Segera ganti password default (`admin` / `password`) setelah login pertama.
- Jangan membagikan `device_token` kepada orang yang tidak berwenang.
- Password disimpan dalam bentuk hash (aman), bukan teks biasa.
- Semua query database menggunakan prepared statements untuk mencegah SQL injection.
- Jangan membuka port XAMPP ke internet publik tanpa proteksi tambahan (firewall, HTTPS, dll).

---

## 11. Kontribusi & Lisensi

Proyek ini dibuat untuk keperluan pembelajaran dan implementasi sistem weighing bridge berbasis IoT. Anda bebas mengembangkan dan menyesuaikan sesuai kebutuhan.

---

**Selamat menggunakan Smart Weighing Bridge!** 🚀
