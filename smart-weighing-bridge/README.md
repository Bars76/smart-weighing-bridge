# Smart Weighing Bridge

Sistem monitoring dan pencatatan berat kendaraan berbasis ESP32, RFID RC522, dua sensor IR, Load Cell HX711, servo, dan Wi-Fi. Dibangun dengan PHP Native, MySQL, HTML, CSS, dan JavaScript tanpa framework. Dapat dijalankan secara lokal menggunakan XAMPP.

---

## 1. Deskripsi Proyek

Smart Weighing Bridge adalah aplikasi web untuk monitoring dan pencatatan transaksi penimbangan kendaraan pada jembat timbang (weighbridge). Data berat dikirim dari ESP32 melalui Wi-Fi ke server lokal, disimpan ke MySQL, dan dapat dipantau melalui dashboard web. Setiap kendaraan diidentifikasi menggunakan UID RFID RC522, dan dua sensor IR digunakan untuk mendeteksi kedatangan dan keberangkatan kendaraan.

---

## 2. Teknologi yang Digunakan

| Layer | Teknologi |
|-------|-----------|
| Backend | PHP Native (tanpa framework) |
| Database | MySQL (PDO + prepared statements) |
| API | REST JSON |
| Frontend | HTML5, CSS3, JavaScript (vanilla) |
| Grafik | Chart.js |
| Ikon | Font Awesome 6 |
| Lingkungan | XAMPP (Apache + MySQL) |
| Perangkat IoT | ESP32, RFID RC522, 2x Sensor IR, Load Cell HX711, Servo, Wi-Fi |

---

## 3. Struktur Folder

```
smart-weighing-bridge/
├── index.php                 # Redirect ke login atau dashboard
├── dashboard.php             # Halaman dashboard utama
├── login.php                 # Halaman login administrator
├── logout.php                # Proses logout
│
├── config/
│   └── database.php          # Konfigurasi koneksi PDO
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
│   ├── device-monitoring.php    # Monitoring perangkat IoT (ESP32 & komponen)
│   └── settings.php             # Pengaturan akun & token perangkat
│
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   ├── dashboard.js      # Polling dan pembaruan data
│   │   └── charts.js         # Chart.js grafik
│   └── images/
│
├── layouts/
│   ├── header.php            # Layout header (disertakan halaman)
│   └── footer.php            # Layout footer (disertakan halaman)
│
├── database/
│   └── smart_weighing_bridge.sql  # File SQL untuk diimpor
│
└── README.md
```

---

## 4. Cara Instalasi XAMPP

1. **Download dan instal XAMPP**  
   https://www.apachefriends.org/index.html  
   Pilih versi yang mencakup Apache dan MySQL.

2. **Jalankan XAMPP Control Panel**  
   Nyalakan **Apache** dan **MySQL**.

3. **Letakkan proyek**  
   Salin folder `smart-weighing-bridge` ke direktori htdocs XAMPP, contoh:  
   ```
   C:\xampp\htdocs\smart-weighing-bridge\
   ```

4. **Pastikan akses**  
   Buka browser dan kunjungi:  
   ```
   http://localhost/smart-weighing-bridge/
   ```

---

## 5. Cara Membuat Database

1. Buka phpMyAdmin: `http://localhost/phpmyadmin/`
2. Buat database baru dengan nama: `smart_weighing_bridge`
3. Pilih database tersebut, lalu tab **SQL** / **Import**
4. Impor file: `database/smart_weighing_bridge.sql`

File SQL akan membuat tabel berikut beserta relasi dan indeks:
- `users` — akun administrator
- `vehicles` — data kendaraan terdaftar
- `weighings` — transaksi penimbangan
- `devices` — perangkat IoT terdaftar
- `device_logs` — log status komponen perangkat

---

## 6. Cara Mengimpor File SQL

Masuk ke phpMyAdmin, pilih database `smart_weighing_bridge`, klik tab **Import**, pilih file `smart_weighing_bridge.sql`, lalu klik **Go**.

Alternatif via CLI:
```bash
mysql -u root -p smart_weighing_bridge < database/smart_weighing_bridge.sql
```

---

## 7. Cara Menjalankan Aplikasi

1. Pastikan Apache dan MySQL berjalan di XAMPP.
2. Buka browser: `http://localhost/smart-weighing-bridge/`
3. Aplikasi akan mengarahkan ke halaman login (`login.php`).
4. Masuk dengan akun administrator.

---

## 8. Cara Membuat Akun Administrator

### Akun Default (setelah impor SQL)
- **Username:** `admin`
- **Password:** `password`

**Penting:** Segera ganti password default setelah login pertama kali untuk keamanan.

### Membuat Akun Baru melalui phpMyAdmin

Masuk ke phpMyAdmin > tabel `users` > **Insert**, isikan:
- `username`: nama pengguna (unik)
- `password_hash`: gunakan fungsi `password_hash()` di PHP atau buat hash melalui script kecil
- `full_name`: nama lengkap
- `role`: `admin` atau `operator`
- `is_active`: `1`

Contoh menghasilkan hash password via PHP:
```php
<?php echo password_hash('password_anda', PASSWORD_DEFAULT); ?>
```

Salin hasil hash tersebut ke kolom `password_hash`.

---

## 9. Cara Mengatur API_BASE pada ESP32

Base URL API adalah:
```
http://IP_KOMPUTER/smart-weighing-bridge/api
```

Contoh dalam sketch ESP32 (Arduino):
```cpp
const char* API_BASE = "http://192.168.1.100/smart-weighing-bridge/api";
```

Ganti `192.168.1.100` dengan IP komputer tempat XAMPP berjalan. Pastikan ESP32 terhubung ke jaringan Wi-Fi yang sama dengan komputer server.

Endpoint yang digunakan ESP32:
- `POST /api/validate` — validasi UID RFID
- `POST /api/weighings` — simpan hasil penimbangan
- `POST /api/device-status` — heartbeat status perangkat

Endpoint tambahan (digunakan dashboard web):
- `GET /api/vehicles` — daftar kendaraan
- `GET /api/statistics` — statistik dan grafik

---

## 10. Cara Menguji Endpoint API

Anda dapat menggunakan curl (terminal) atau Postman.

### 10.1 Validasi RFID (POST /api/validate)

```bash
curl -X POST http://localhost/smart-weighing-bridge/api/validate.php \
  -H "Content-Type: application/json" \
  -d '{"uid":"A1B2C3D4"}'
```

Respons berhasil (kendaraan aktif):
```json
{"valid":true,"vehicle":{"id":1,"vehicle_id":"V001","plate":"AB1234CD","vehicle_type":"Truck"}}
```

Respons gagal (tidak terdaftar):
```json
{"valid":false,"message":"UID RFID tidak terdaftar."}
```

### 10.2 Simpan Penimbangan (POST /api/weighings)

```bash
curl -X POST http://localhost/smart-weighing-bridge/api/weighings.php \
  -H "Content-Type: application/json" \
  -d '{"uid":"A1B2C3D4","vehicle_id":"V001","plate":"AB1234CD","vehicle_type":"Truck","weight_kg":1250.50,"status":"weighed"}'
```

Respons berhasil:
```json
{"success":true,"message":"Data penimbangan berhasil disimpan","transaction_code":"WB-2026-000001"}
```

### 10.3 Daftar Kendaraan (GET /api/vehicles)

```bash
curl http://localhost/smart-weighing-bridge/api/vehicles.php
```

### 10.4 Statistik (GET /api/statistics)

```bash
curl http://localhost/smart-weighing-bridge/api/statistics.php
```

### 10.5 Status Perangkat / Heartbeat (POST /api/device-status)

```bash
curl -X POST http://localhost/smart-weighing-bridge/api/device-status.php \
  -H "Content-Type: application/json" \
  -d '{"device_token":"WEIGHBRIDGE_TOKEN_2026","device_name":"ESP32-Weighbridge-01","components":[{"name":"RFID","status":"active"},{"name":"IR_IN","status":"active"},{"name":"IR_OUT","status":"active"},{"name":"HX711","status":"active"},{"name":"Servo","status":"closed"},{"name":"WiFi","status":"connected"}]}'
```

---

## 11. Cara Menghubungkan ESP32 ke Dashboard

1. **Upload sketch ESP32** yang menggunakan library:
   - `WiFi.h`
   - `HTTPClient.h`
   - `SPI.h`
   - `MFRC522.h`
   - `ESP32Servo.h`
   - `HX711.h`

2. **Konfigurasi Wi-Fi** pada sketch agar terhubung ke jaringan yang sama dengan komputer server XAMPP.

3. **Setel API_BASE** ke alamat komputer server, contoh:
   ```cpp
   const char* API_BASE = "http://192.168.1.100/smart-weighing-bridge/api";
   ```

4. **Implementasikan alur kerja** pada ESP32:
   - Saat kartu RFID dibaca, kirim `POST /api/validate` dengan UID.
   - Jika `valid: true`, nyalakan servo, baca load cell melalui HX711, lalu kirim `POST /api/weighings` dengan data berat.
   - Secara berkala (misal setiap 30–60 detik), kirim `POST /api/device-status` dengan token perangkat dan status tiap komponen sebagai heartbeat.

5. **Dashboard** akan menampilkan status perangkat berdasarkan `last_seen` dari tabel `devices`. Jika ESP32 tidak mengirim heartbeat dalam jangka waktu tertentu, status akan ditampilkan sebagai offline/belum tersedia.

---

## 12. Halaman Monitoring Perangkat

Halaman `pages/device-monitoring.php` digunakan untuk memantau status ESP32 dan seluruh komponen IoT:

- **Status Koneksi**: Online/Offline berdasarkan waktu `last_seen` terakhir (threshold 5 menit)
- **Log Komponen**: RFID, IR_IN, IR_OUT, HX711, Servo, Wi-Fi dengan status masing-masing (Aktif/Tidak Aktif/Terbuka/Tertutup/Belum Tersedia)
- Jika ESP32 belum pernah mengirim heartbeat, status akan menampilkan "Belum Tersedia"

## 13. Halaman Pengaturan

Halaman `pages/settings.php` berisi:

- **Ubah Password**: Ganti password administrator dengan verifikasi password lama
- **Token Perangkat**: Kelola device token yang digunakan ESP32 untuk autentikasi API
- **Informasi Sistem**: Detail versi, backend, database, dan pengguna saat ini

## 15. Catatan Keamanan

- Password disimpan dengan `password_hash()` dan diverifikasi dengan `password_verify()`.
- Semua query database menggunakan PDO prepared statements.
- Token CSRF dilindungi pada formulir administrasi.
- Password Wi-Fi dan token perangkat **tidak** disimpan dalam file frontend atau ditampilkan di dashboard.
- Error database tidak dibocorkan ke klien.

---

## 16. Pengujian

Setelah instalasi selesai, periksa hal berikut:

1. **Struktur folder** — semua file dan direktori ada sesuai spesifikasi.
2. **Sintaks PHP** — jalankan `php -l` pada setiap file PHP atau buka halaman via browser tanpa error.
3. **Koneksi MySQL** — login berhasil dan data dimuat dari database.
4. **Endpoint API** — uji semua endpoint dengan curl/Postman (lihat bagian 10).
5. **Proses login** — masuk dengan akun admin, coba logout.
6. **CRUD kendaraan** — tambah, ubah, nonaktifkan, dan hapus kendaraan di halaman Data Kendaraan.
7. **Penyimpanan transaksi** — kirim data penimbangan melalui API, verifikasi muncul di dashboard dan riwayat.
8. **Tampilan dashboard** — kartu statistik, grafik, dan tabel terbaru ditampilkan.
9. **Responsif** — buka pada layar Desktop, tablet, dan ponsel.

Jika ESP32 belum terhubung, status perangkat di dashboard akan menampilkan "Belum Tersedia" hingga pertama kali ESP32 mengirim heartbeat. Ini adalah perilaku sengaja agar data sensor tidak ditampilkan sebagai palsu.

---

## Lisensi

Proyek ini dibuat untuk keperluan pembelajaran dan implementasi sistem weighing bridge berbasis IoT.