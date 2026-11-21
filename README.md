# Smart Weighing Bridge — Web Dashboard

Dokumentasi implementasi sistem jembatan timbang mini berbasis IoT (ESP32 + PHP + MySQL) sesuai `prd.md` versi 1.1.

---

## 1. Ringkasan Pekerjaan

Proyek ini dibangun dari awal (sebelumnya hanya ada `prd.md`). Yang telah dilakukan:

### 1.1 Pembaruan PRD (`prd.md`)
- Versi dinaikkan **1.0 → 1.1** dengan catatan revisi.
- Menambahkan **FR-16, FR-17, FR-18** (login, logout, pembatasan akses).
- Memperkuat **NFR-06** (keamanan data berbasis role).
- Menambahkan **tabel users** di desain database (6.1).
- Menambahkan **7.1 Halaman Login** dan menggeser penomoran halaman lain.
- Menambahkan **TC-13 s.d. TC-15** (kasus uji login/akses).
- Menandai rencana "autentikasi admin" di Bab 11 sebagai sudah direalisasikan.

### 1.2 Aplikasi web (PHP + MySQL)
- Sistem autentikasi sessions PHP dengan password hash (`password_hash` / `password_verify`).
- Halaman login sebagai entry point, logout, dan redirect berdasarkan peran.
- Dashboard dengan statistik, aktivitas terbaru, dan kondisi sistem.
- CRUD Data Kendaraan (Admin) dengan validasi UID RFID duplikat.
- Riwayat Penimbangan dengan pencarian (nomor/nama/UID) dan filter tanggal.
- Pengaturan batas berat (Admin).
- Monitor Publik untuk Pengamat (bisa dilihat tanpa login).
- REST API untuk ESP32 mengirim hasil penimbangan.

### 1.3 Desain
Arah visual **Apple-inspired** — hasil penerapan skill `frontend-design` dan
`impeccable-design-polish` (lihat `.opencode/skills/open-design/`):
- Palet putih bersih (`#ffffff`), permukaan sekunder abu-abu netral (`#f5f5f7`), teks `#1d1d1f`, aksen biru **`#0071e3`**.
- Tipografi system/SF-like (`-apple-system`, `SF Pro Display`, `Segoe UI`) dengan judul besar, tebal, dan tracking rapat.
- Sudut membulat (10–18px), tombol pill, shadow lembut, navigation bar translusen dengan blur — khas Apple.
- Whitespace lapang, transisi halus, satu animasi masuk saat load, tanpa gradien mencolok.
- Kepadatan data tetap dijaga: grafik 7 hari, KPI strip, timeline, gauge, dan tabel terstruktur di setiap halaman.
- Responsif di desktop/tablet/ponsel, fokus terlihat, kontras memadai, dan `prefers-reduced-motion` didukung.

---

## 2. Struktur File

```
iot_cihuy/
├── prd.md                    # Dokumen kebutuhan (v1.1)
├── README.md                 # File ini
├── index.php                 # Halaman login (entry point)
├── proses_login.php          # Proses autentikasi
├── logout.php                # Akhiri session
├── dashboard.php             # Dashboard utama
├── kendaraan.php             # CRUD data kendaraan (Admin)
├── riwayat.php               # Riwayat + pencarian
├── pengaturan.php            # Pengaturan batas berat (Admin)
├── lihat.php                 # Monitor publik (Pengamat)
├── config/
│   └── database.php          # Koneksi PDO + helper pengaturan
├── includes/
│   ├── auth.php              # Session, role, flash message
│   ├── header.php            # Sidebar + topbar
│   └── footer.php            # Penutup layout
├── api/
│   ├── timbangan.php         # REST API kirim hasil penimbangan (ESP32)
│   ├── batas.php             # REST API ambil batas berat (ESP32)
│   ├── heartbeat.php         # REST API status koneksi ESP32
│   └── live.php              # REST API status timbang real-time
├── esp32/
│   ├── smart_weighing_bridge.ino  # Firmware lengkap ESP32
│   └── hx711_calibrasi.ino        # Sketch khusus kalibrasi load cell
├── assets/
│   ├── css/app.css           # Sistem visual
│   └── js/
│       ├── app.js            # Konfirmasi hapus
│       └── live.js           # Polling status live (1,5 detik)
├── sql/
│   └── schema.sql            # Schema + data awal database
└── .opencode/skills/...      # Skill design-taste-frontend (project scope)
```

---

## 3. Instalasi, Konfigurasi & Cara Menggunakan

### 3.1 Instalasi lokal
1. Pastikan **XAMPP** berjalan (Apache + MySQL).
2. Import database:
   ```bash
   mysql -u root < sql/schema.sql
   ```
   Atau buka phpMyAdmin → import `sql/schema.sql`.
3. Buka di browser: `http://localhost/iot_cihuy/`

### 3.2 Konfigurasi (singkat)

**A. Firmware** — `esp32/smart_weighing_bridge.ino`

| Baris | Diisi dengan |
|---|---|
| 57–58 | `WIFI_SSID` / `WIFI_PASSWORD` WiFi Anda |
| 62–65 | `192.168.xxx.x` → **IP LAN komputer XAMPP** (cek `ipconfig`) |
| 74 | `FAKTOR_KALIBRASI` dari `hx711_calibrasi.ino` |
| 85 | `IR_TERDETEKSI` → `LOW` atau `HIGH` sesuai modul IR |

> ⚠️ **Jangan upload firmware sebelum baris 62–65 diganti.** File ini sengaja
> memakai IP placeholder agar aman dibagikan. ESP32 yang sedang berjalan masih
> memakai firmware lama berisi IP asli — jangan di-upload ulang sampai IP diisi.

**B. Server** (opsional, untuk publikasi) — environment variable PowerShell:
```powershell
$env:SW_DB_USER = "sw_user"
$env:SW_DB_PASS = "password-kuat"
$env:SW_DEBUG   = "false"
```

**C. Batas berat** — ubah lewat menu **Pengaturan** di web. ESP32 mengambilnya
otomatis via `api/batas.php`, tidak perlu upload ulang firmware.

> API ESP32 **tanpa token** (prototipe lokal). Jangan pakai MySQL `root` tanpa
> password di lingkungan publik.

### 3.3 Akun login

| Role | Username | Password | Hak akses |
|---|---|---|---|
| Admin | `admin` | `admin123` | Semua halaman + CRUD + pengaturan |
| Operator | `operator` | `operator123` | Dashboard, kendaraan (lihat), riwayat, proses penimbangan |
| Pengamat | *(tanpa login)* | — | Halaman `lihat.php` (lihat-only) |

Login langsung masuk Dashboard — **tidak ada paksa ganti password**. Halaman
`ganti_password.php` tetap tersedia jika Anda ingin mengubah password secara sukarela.

> Catatan: `admin` mungkin sudah diganti passwordnya dari `admin123` selama pengujian.
> Jika `admin123` gagal, pakai password yang Anda buat, atau reset lewat database.

### 3.4 Alur penggunaan sehari-hari

**Admin**
1. Buka `/iot_cihuy/` → login → ganti password (pertama kali).
2. **Data Kendaraan** → tambah kendaraan + UID kartu RFID.
3. **Pengaturan** → atur batas berat maksimum (gram).
4. **Dashboard** → pantau berat live, grafik 7 hari, dan status ESP32.
5. **Riwayat** → cari/filter catatan penimbangan.

**Operator**
1. Login → Dashboard (lihat berat live & kondisi palang).
2. Pastikan kendaraan & UID RFID sudah terdaftar Admin.
3. Proses penimbangan di ESP32; kartu RFID dibaca setelah penimbangan.
4. Jika berat berlebih, kendaraan tetap lewat dengan lampu merah + buzzer.

**Pengamat (publik)**
- Buka `/iot_cihuy/lihat.php` tanpa login — angka berat, tahap proses, status
  palang, dan info perangkat diperbarui otomatis tiap 1,5 detik.

### 3.5 Proteksi bawaan

- API ESP32 tanpa token (mode prototipe): `timbangan.php`, `live.php`, `heartbeat.php`, `batas.php`.
- CSRF token pada semua form (login, kendaraan, pengaturan, ganti password, logout).
- Logout hanya lewat **POST** (tidak bisa lewat URL).
- Rate limit login: 5 kali gagal → dikunci 60 detik.
- Password tersimpan sebagai hash (`password_hash`), detail error DB tidak ditampilkan.
- `.htaccess` memblokir akses langsung ke `config/`, `includes/`, `sql/`, `esp32/`,
  `README.md`, `prd.md`, dan file `.sql`. Pastikan Apache mengizinkan `.htaccess`
  (`AllowOverride All`) atau terapkan aturan tersebut di `httpd.conf`.

---

## 4. Basis Data

Database: `smart_weighing` (utf8mb4)

| Tabel | Fungsi |
|---|---|
| `users` | Akun admin/operator (hash password, role, `must_change_password`) |
| `kendaraan` | Data kendaraan + UID RFID |
| `penimbangan` | Hasil penimbangan (berat, batas, status, waktu) |
| `pengaturan` | Konfigurasi sistem (mis. `batas_berat`, heartbeat) |
| `live_status` | Satu baris status real-time (ditimpa tiap detik oleh ESP32) |

Batas berat default: **5000 gram** (sesuai Load Cell 5 kg).

---

## 5. REST API (ESP32)

> Mode prototipe: endpoint **tidak memerlukan token**. Cocok untuk firmware
> versi lama. Jangan publikasikan API ini ke internet tanpa proteksi tambahan.

### Kirim penimbangan
```
POST http://localhost/iot_cihuy/api/timbangan.php
Content-Type: application/x-www-form-urlencoded

berat=3000&uid_rfid=A1B2C3D4
```

Respons sukses (`201`):
```json
{
  "status": "ok",
  "id_penimbangan": 8,
  "nomor_kendaraan": "B 1234 ABC",
  "berat": 3000,
  "batas_berat": 5000,
  "status_berat": "Sesuai",
  "waktu": "2026-11-21 17:17:02"
}
```

Respons error:
- `404` — UID RFID tidak terdaftar / kendaraan nonaktif
- `422` — field wajib kosong
- `405` — metode selain POST/GET

### Ambil pembacaan terakhir
```
GET http://localhost/iot_cihuy/api/timbangan.php
```

Contoh ESP32 (Arduino, potongan):
```cpp
HTTPClient http;
http.begin("http://<IP-SERVER>/iot_cihuy/api/timbangan.php");
http.addHeader("Content-Type", "application/x-www-form-urlencoded");
String body = "berat=" + String(beratGram) + "&uid_rfid=" + uid;
int code = http.POST(body);
String res = http.getString();
http.end();
```

---

## 6. Matriks Hak Akses

| Halaman | Admin | Operator | Pengamat |
|---|---|---|---|
| Login / Logout | ✅ | ✅ | — |
| Dashboard | ✅ | ✅ | — |
| Data Kendaraan (ubah/hapus) | ✅ | lihat saja | — |
| Riwayat Penimbangan | ✅ | ✅ | — |
| Pengaturan | ✅ | ❌ (403) | — |
| Monitor Publik (`lihat.php`) | ✅ | ✅ | ✅ |

---

## 7. Pengujian yang Dilakukan

| Pengujian | Hasil |
|---|---|
| Sintaks seluruh file PHP (`php -l`) | ✅ Lulus (13 file) |
| Import schema MySQL | ✅ 4 tabel, 2 user, 4 kendaraan, 5 riwayat awal |
| Halaman login | ✅ HTTP 200 |
| Akses dashboard tanpa login | ✅ 302 → login |
| Login password salah | ✅ Pesan error, tetap di login |
| Login berhasil | ✅ Session terbentuk, redirect dashboard |
| Semua halaman dengan session | ✅ HTTP 200 |
| Operator mengakses Pengaturan | ✅ HTTP 403 "Akses ditolak" |
| API GET/POST (valid, berlebih, UID invalid) | ✅ Sesuai ekspektasi |
| CRUD kendaraan & ubah batas berat | ✅ Berhasil & data uji dibersihkan |

---

## 8. Firmware ESP32

File: `esp32/smart_weighing_bridge.ino` — buka dengan Arduino IDE, pilih board **ESP32 Dev Module**, install library:
- MFRC522 (GithubCommunity)
- ESP32Servo
- HX711 (bogde)
- WiFi & HTTPClient (bawaan core ESP32)

Sebelum upload, ubah di firmware:
- `WIFI_SSID` / `WIFI_PASSWORD`
- `SERVER_TERAKHIR`, `SERVER_BATAS`, `SERVER_HEARTBEAT`, `SERVER_LIVE` → IP LAN komputer XAMPP (misal `192.168.1.10`)
- `FAKTOR_KALIBRASI` → hasil kalibrasi load cell
- `IR_TERDETEKSI` → `LOW` atau `HIGH` sesuai modul sensor IR

Endpoint pendukung:
```
GET http://<IP-PC>/iot_cihuy/api/batas.php
→ {"status":"ok","batas_berat":5000}
```
Firmware mengambil batas berat dari database saat startup dan setelah setiap kendaraan keluar, dengan fallback `5000` gram jika server tidak terjangkau.

Heartbeat status perangkat (dikirim ESP32 tiap 15 detik):
```
GET http://<IP-PC>/iot_cihuy/api/heartbeat.php
→ {"status":"ok","device":"esp32-1","server_time":"..."}
```
Server menyimpan waktu terakhir di `pengaturan.esp32_last_seen`. Dashboard dan Monitor Publik menampilkan **ESP32 Terhubung** jika heartbeat terakhir ≤ 60 detik, selain itu **ESP32 Terputus** beserta waktu terakhir terhubung. Nilai awal schema sengaja tanggal lama agar status mulai dari "Terputus".

### Penimbangan real-time
ESP32 mengirim status tiap **1 detik** ke `api/live.php` (berat HX711 langsung, tahap proses, status palang, UID, RSSI, uptime). Server menyimpannya di **satu baris** tabel `live_status` yang ditimpa terus, sehingga tidak membengkak.

```
GET  http://<IP-PC>/iot_cihuy/api/live.php   → JSON status terkini
POST http://<IP-PC>/iot_cihuy/api/live.php   → ESP32 mengirim status
```

Halaman **Dashboard** dan **Monitor Publik** melakukan polling lewat `assets/js/live.js` tiap 1,5 detik dan memperbarui angka, stepper tahap (`Siaga → Masuk → Timbang → RFID → Keluar`), status palang, dan info perangkat **tanpa reload**. Status online ditentukan server: data lebih tua dari 3 detik dianggap offline.

Data live **tidak** masuk ke tabel `penimbangan` — riwayat/laporan hanya berisi transaksi yang selesai.

### Perilaku berat berlebih
- Berat berlebih **tetap disimpan** ke database dengan `status_berat = Berlebih` (API `timbangan.php` tidak menolak).
- Kartu RFID yang valid (Sesuai **atau** Berlebih) **tetap membuka palang keluar**.
- Jika Berlebih, **lampu merah + buzzer menyala terus** sampai kendaraan melewati IR2 dan palang keluar ditutup; lalu indikator dimatikan dan sistem kembali standby.
- Hanya UID yang **tidak terdaftar** yang membuat palang keluar tetap tertutup.

### Kalibrasi load cell
Gunakan `esp32/hx711_calibrasi.ino` (upload sementara, pin DOUT=16, SCK=17):
1. Platform kosong → kirim `t` di Serial Monitor (tare).
2. Letakkan beban yang diketahui, misal 1000 gram → kirim `1` (atau `w500` untuk 500 gram).
3. Sketch mencetak nilai `FAKTOR_KALIBRASI = ...` → salin ke firmware utama:
   ```cpp
   const float FAKTOR_KALIBRASI = <hasil>;
   ```
Perintah lain: `s` (lihat faktor), `h` (bantuan). Jika nilai raw **negatif** saat ada beban, tukar kabel `A+ ↔ A-` pada HX711.

### Pin yang digunakan
| Perangkat | Pin GPIO |
|---|---|
| RFID SS / RST / SCK / MISO / MOSI | 5 / 4 / 18 / 19 / 23 |
| Servo masuk / keluar | 13 / 14 |
| IR masuk / keluar | 34 / 35 |
| HX711 DOUT / SCK | 16 / 17 |
| LED merah / hijau / buzzer | 25 / 26 / 27 |

---

## 9. Troubleshooting

### ESP32 tidak terhubung / status selalu "Terputus"

Jika Dashboard dan Monitor Publik menunjukkan **ESP32 Terputus** padahal Serial Monitor sudah menampilkan `ESP32 IP: 192.168.xxx.xx`, penyebab paling sering adalah **firewall Windows** yang memblokir koneksi masuk ke Apache.

Cek aturan firewall yang bermasalah (perhatikan `Action = Block`):

```powershell
Get-NetFirewallRule -DisplayName "Apache HTTP Server" | Select-Object DisplayName, Enabled, Action
```

Contoh kondisi yang bermasalah:

```text
DisplayName        Enabled Action
-----------        ------- ------
Apache HTTP Server   True  Block
Apache HTTP Server   True  Block
```

Nonaktifkan aturan tersebut:

```powershell
Disable-NetFirewallRule -DisplayName "Apache HTTP Server"
```

Verifikasi ulang — hasil yang diharapkan `Enabled = False`:

```powershell
Get-NetFirewallRule -DisplayName "Apache HTTP Server" | Select-Object DisplayName, Enabled, Action
```

```text
DisplayName        Enabled Action
-----------        ------- ------
Apache HTTP Server   False  Block
Apache HTTP Server   False  Block
```

Setelah itu reset ESP32, tunggu ±15 detik, lalu refresh Dashboard. Alternatif lain: ubah profil jaringan Wi-Fi dari `Public` menjadi `Private`:

```powershell
Set-NetConnectionProfile -NetworkCategory Private
```

Pemeriksaan lain bila masih gagal:
- ESP32 dan komputer harus berada di **Wi-Fi / subnet yang sama**.
- IP server di firmware harus IP LAN komputer XAMPP (misal `192.168.1.10`), **bukan `localhost`**.
- Buka `http://192.168.xxx.xx/iot_cihuy/api/live.php` dari browser HP yang satu jaringan — harus muncul JSON.
- Lihat `access.log` Apache: harus ada request `GET /iot_cihuy/api/heartbeat.php` dan `/api/live.php` dari IP ESP32.

---

## 10. catatan

- Implementasi ini adalah **prototipe** sesuai ruang lingkup PRD (bukan alat timbang legal/industri).
- Default XAMPP (`root` tanpa password) **hanya untuk lokal**. Sebelum publikasikan, set `SW_DB_USER`/`SW_DB_PASS` dan buat user MySQL khusus.
- Password bawaan (`admin123` / `operator123`) **wajib diganti** sebelum aplikasi dipublikasikan. Akun bawaan sudah dipaksa ganti password pada login pertama.
- API ESP32 berjalan **tanpa token** (prototipe lokal). Jangan ekspos API ini ke internet tanpa proteksi tambahan.
- Logo/ilustrasi pada halaman login dibuat sebagai SVG vektor lokal (bukan foto pihak ketiga).
- Skill `design-taste-frontend` terpasang pada scope project di `.opencode/skills/design-taste-frontend`, bersama skill desain lain di `.opencode/skills/open-design/`.
