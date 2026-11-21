# PRODUCT REQUIREMENTS DOCUMENT (PRD)
# SMART WEIGHING BRIDGE
### Sistem Jembatan Timbang Mini Berbasis Internet of Things (IoT)

**Versi:** 1.1  
**Jenis Proyek:** Proyek Internet of Things (IoT)

---

### Catatan Revisi

- **Versi 1.1:** Menambahkan halaman login untuk Admin & Operator, tabel users di database, dan kasus uji login (TC-13 s.d. TC-15).  
**Platform:** ESP32 dan Web Dashboard

---

## 1. Pendahuluan

### 1.1 Latar Belakang

Perkembangan teknologi Internet of Things (IoT) memungkinkan perangkat elektronik saling terhubung untuk mengumpulkan, mengirimkan, dan mengolah data secara otomatis. Salah satu penerapannya adalah sistem penimbangan yang dapat mengukur berat, mengidentifikasi objek, serta menyimpan hasil pengukuran ke dalam basis data.

Jembatan timbang merupakan perangkat yang digunakan untuk mengetahui berat kendaraan atau muatan. Dalam proses penimbangan, pencatatan berat dan identifikasi kendaraan menjadi bagian penting agar setiap hasil pengukuran dapat diketahui asalnya dan diperiksa kembali.

Berdasarkan kebutuhan tersebut, dikembangkan Smart Weighing Bridge sebagai prototipe jembatan timbang mini berbasis IoT. Sistem ini menggunakan ESP32 sebagai pusat kendali, Load Cell untuk mengukur berat kendaraan, dan modul HX711 untuk membaca sinyal dari sensor. Dua sensor inframerah digunakan untuk mendeteksi kendaraan ketika memasuki area timbang dan memastikan kendaraan telah keluar sepenuhnya.

Sistem juga dilengkapi dua servo sebagai penggerak palang masuk dan keluar, serta RFID untuk mengidentifikasi kendaraan setelah proses penimbangan. Buzzer dan lampu indikator digunakan untuk memberikan informasi apabila berat kendaraan melebihi batas yang telah ditentukan. Seluruh hasil penimbangan dikirimkan ke server untuk disimpan dan ditampilkan melalui dashboard web.

### 1.2 Tujuan Produk

1. Membuat prototipe jembatan timbang mini yang dapat mengukur berat kendaraan secara otomatis.
2. Mengidentifikasi kendaraan menggunakan RFID.
3. Mengendalikan palang masuk dan keluar menggunakan dua servo.
4. Memberikan peringatan ketika berat kendaraan melebihi batas maksimum.
5. Mengirim dan menyimpan hasil penimbangan ke dalam basis data.
6. Menampilkan hasil penimbangan dan riwayat kendaraan melalui dashboard web.
7. Menerapkan arsitektur IoT empat lapisan.

### 1.3 Ruang Lingkup

Smart Weighing Bridge dikembangkan sebagai prototipe skala kecil untuk menimbang kendaraan mini atau benda dengan berat yang sesuai kapasitas sensor. Sistem bukan merupakan alat timbang kendaraan industri dan tidak ditujukan untuk pengukuran legal atau perdagangan.

---

## 2. Gambaran Umum Produk

### 2.1 Deskripsi Produk

Smart Weighing Bridge merupakan sistem penimbangan kendaraan mini yang mengintegrasikan sensor berat, identifikasi RFID, pengendalian palang otomatis, indikator kondisi berat, dan penyimpanan data berbasis web.

Sistem bekerja dengan mendeteksi kendaraan yang masuk, melakukan pengukuran berat, memeriksa batas maksimum, membaca identitas RFID, dan memastikan kendaraan telah keluar dari area timbang.

### 2.2 Target Pengguna

- **Admin:** Mendaftarkan kendaraan, mengelola data RFID, melihat hasil penimbangan, dan memantau riwayat.
- **Operator:** Mengoperasikan proses penimbangan dan melakukan pemindaian RFID.
- **Pengamat:** Melihat informasi berat dan status penimbangan melalui dashboard.

### 2.3 Arsitektur Sistem

Sistem menggunakan arsitektur IoT empat lapisan.

**1. Smart Things Layer**
- ESP32 sebagai mikrokontroler.
- Load Cell 5 kg sebagai sensor berat.
- HX711 sebagai modul pembaca sinyal Load Cell.
- Dua sensor inframerah.
- Dua motor servo.
- RFID RC522 dan kartu RFID.
- Active buzzer.
- Lampu LED merah dan hijau.
- Baterai atau power bank sebagai sumber daya.

**2. Network Layer and Gateways**
- Wi-Fi sebagai media komunikasi.
- HTTP/REST API untuk mengirim data dari ESP32 ke server.
- Router atau hotspot sebagai penghubung jaringan.

**3. Middleware Layer**
- PHP sebagai backend dan API.
- MySQL sebagai basis data.
- XAMPP sebagai lingkungan server lokal.

**4. Application Layer**
- HTML, CSS, dan JavaScript untuk antarmuka.
- Dashboard web untuk menampilkan hasil penimbangan.
- Chart.js untuk visualisasi data jika diperlukan.

---

## 3. Alur Kerja Sistem

### 3.1 Alur Utama

1. Sistem berada dalam kondisi siaga dan menunggu kendaraan memasuki area timbang.
2. Sensor inframerah pertama (IR 1) mendeteksi kendaraan yang datang.
3. ESP32 memerintahkan servo pertama untuk membuka palang masuk.
4. Kendaraan bergerak menuju platform penimbangan.
5. Load Cell mendeteksi berat kendaraan dan mengirimkan sinyal ke HX711.
6. HX711 mengolah sinyal agar dapat dibaca oleh ESP32.
7. ESP32 menghitung berat berdasarkan hasil pembacaan dan kalibrasi sensor.
8. Sistem membandingkan berat dengan batas maksimum yang telah ditentukan.
9. Jika berat melebihi batas, buzzer berbunyi dan lampu merah menyala. Jika berat sesuai batas, lampu hijau menyala.
10. Setelah penimbangan selesai, operator melakukan pemindaian kartu RFID.
11. ESP32 membaca UID RFID dan mengirimkannya ke server untuk proses identifikasi.
12. Server memeriksa apakah UID telah terdaftar.
13. Jika RFID valid, sistem menghubungkan identitas kendaraan dengan hasil penimbangan.
14. Servo kedua membuka palang keluar.
15. Kendaraan bergerak meninggalkan area penimbangan.
16. Sensor inframerah kedua (IR 2) mendeteksi kendaraan pada jalur keluar.
17. Setelah kendaraan melewati sensor sepenuhnya, servo kedua menutup palang.
18. Sistem menyimpan dan menampilkan hasil penimbangan pada dashboard.
19. Sistem kembali ke kondisi siaga untuk menerima kendaraan berikutnya.

### 3.2 Diagram Alur

Kendaraan Masuk  
↓  
IR 1 Mendeteksi Kendaraan  
↓  
Servo 1 Membuka Palang Masuk  
↓  
Kendaraan Berada di Atas Load Cell  
↓  
HX711 Membaca Sinyal Sensor  
↓  
ESP32 Mengolah Berat  
↓  
Pemeriksaan Batas Berat  
↓  
- Berat berlebih: Buzzer ON dan lampu merah ON.
- Berat sesuai: Lampu hijau ON.

↓  
Pemindaian RFID  
↓  
Validasi UID Kendaraan  
↓  
Penyimpanan Hasil Penimbangan  
↓  
Servo 2 Membuka Palang Keluar  
↓  
Kendaraan Melewati IR 2  
↓  
Kendaraan Dipastikan Keluar  
↓  
Servo 2 Menutup Palang  
↓  
Dashboard dan Database Diperbarui  
↓  
Sistem Kembali Siaga.

---

## 4. Kebutuhan Fungsional

Kebutuhan fungsional menjelaskan fitur yang harus tersedia pada sistem.

| ID | Fitur | Deskripsi |
|---|---|---|
| FR-01 | Deteksi kendaraan masuk | IR 1 mendeteksi kendaraan yang memasuki area timbang. |
| FR-02 | Palang masuk otomatis | Servo 1 membuka palang setelah kendaraan terdeteksi. |
| FR-03 | Pengukuran berat | Load Cell dan HX711 mengukur berat kendaraan. |
| FR-04 | Pengolahan berat | ESP32 menghitung berat berdasarkan kalibrasi sensor. |
| FR-05 | Pemeriksaan batas berat | Sistem membandingkan hasil pengukuran dengan batas maksimum. |
| FR-06 | Peringatan kelebihan berat | Buzzer dan lampu merah aktif ketika berat melebihi batas. |
| FR-07 | Indikator berat normal | Lampu hijau aktif ketika berat sesuai batas. |
| FR-08 | Identifikasi RFID | RC522 membaca UID kartu RFID setelah penimbangan. |
| FR-09 | Validasi kendaraan | Server memeriksa UID terhadap data kendaraan terdaftar. |
| FR-10 | Palang keluar otomatis | Servo 2 membuka palang setelah RFID berhasil divalidasi. |
| FR-11 | Deteksi kendaraan keluar | IR 2 memastikan kendaraan telah melewati jalur keluar. |
| FR-12 | Penyimpanan data | Sistem menyimpan identitas kendaraan dan hasil penimbangan. |
| FR-13 | Dashboard | Sistem menampilkan berat, identitas, status, dan riwayat penimbangan. |
| FR-14 | Registrasi kendaraan | Admin dapat menambahkan dan mengelola data kendaraan serta UID RFID. |
| FR-15 | Pengaturan batas berat | Admin dapat mengatur batas maksimum penimbangan. |
| FR-16 | Login pengguna | Admin & Operator autentikasi dengan username + password sebelum mengakses dashboard. |
| FR-17 | Logout | Mengakhiri session dan kembali ke halaman login. |
| FR-18 | Pembatasan akses | Admin: kelola kendaraan, RFID, batas berat, & pengguna. Operator: proses penimbangan & scan RFID saja. Pengamat: lihat tanpa login. |

### 4.1 Ketentuan Kondisi Sistem

**Kondisi berat sesuai batas:**
- Lampu hijau menyala.
- Buzzer tidak aktif.
- Hasil penimbangan berstatus Sesuai.

**Kondisi berat melebihi batas:**
- Lampu merah menyala.
- Buzzer berbunyi.
- Hasil penimbangan berstatus Berlebih.

**Kondisi RFID tidak terdaftar:**
- Sistem menampilkan pemberitahuan bahwa kendaraan belum terdaftar.
- Proses identifikasi dinyatakan gagal.
- Palang keluar tidak dibuka.
- Operator perlu melakukan penanganan sesuai prosedur.

**Kondisi kendaraan belum keluar:**
- IR 2 belum memberikan konfirmasi bahwa kendaraan telah melewati jalur keluar.
- Palang keluar tetap terbuka.
- Sistem belum kembali ke kondisi siaga.

---

## 5. Kebutuhan Nonfungsional

| ID | Kebutuhan | Deskripsi |
|---|---|---|
| NFR-01 | Akurasi | Pembacaan berat harus melalui proses kalibrasi Load Cell. |
| NFR-02 | Keandalan | Sistem harus dapat menjalankan proses penimbangan secara berurutan. |
| NFR-03 | Kemudahan penggunaan | Dashboard memiliki tampilan sederhana dan mudah dipahami. |
| NFR-04 | Penyimpanan | Riwayat penimbangan harus tersimpan dalam MySQL. |
| NFR-05 | Komunikasi | ESP32 dapat mengirim data ke server melalui Wi-Fi. |
| NFR-06 | Keamanan data | Data kendaraan dan hasil penimbangan hanya dapat dikelola oleh pengguna terautentikasi dengan role sesuai (Admin/Operator). Akses tanpa login hanya untuk pengamat (lihat-only). |
| NFR-07 | Pemulihan | Sistem dapat kembali ke kondisi siaga setelah satu proses penimbangan selesai. |

---

## 6. Kebutuhan Data dan Database

Database dirancang menggunakan MySQL dan terdiri dari tiga tabel utama.

### 6.1 Tabel Users

Digunakan untuk menyimpan akun pengguna sistem.

| Nama Kolom | Tipe Data | Keterangan |
|---|---|---|
| id_user | INT | Primary key |
| username | VARCHAR(50) | Username unik |
| password | VARCHAR(255) | Password hash |
| nama_lengkap | VARCHAR(100) | Nama lengkap pengguna |
| role | ENUM | Role: admin, operator |
| status | ENUM | Status: aktif, nonaktif |

### 6.2 Tabel Kendaraan

Digunakan untuk menyimpan informasi kendaraan yang sudah didaftarkan.

| Nama Kolom | Tipe Data | Keterangan |
|---|---|---|
| id_kendaraan | INT | Primary key |
| nomor_kendaraan | VARCHAR(20) | Nomor kendaraan |
| nama_kendaraan | VARCHAR(100) | Nama atau kode kendaraan |
| jenis_kendaraan | VARCHAR(50) | Jenis kendaraan |
| uid_rfid | VARCHAR(50) | UID kartu RFID |
| status | ENUM | Aktif atau tidak aktif |

### 6.2 Tabel Penimbangan

Digunakan untuk menyimpan setiap hasil penimbangan.

| Nama Kolom | Tipe Data | Keterangan |
|---|---|---|
| id_penimbangan | INT | Primary key |
| id_kendaraan | INT | Foreign key ke tabel kendaraan |
| berat | DECIMAL(10,2) | Berat hasil pengukuran dalam gram |
| batas_berat | DECIMAL(10,2) | Batas maksimum saat penimbangan |
| status_berat | ENUM | Sesuai atau Berlebih |
| waktu_penimbangan | DATETIME | Waktu pengukuran |

### 6.3 Tabel Pengaturan

Digunakan untuk menyimpan konfigurasi sistem.

| Nama Kolom | Tipe Data | Keterangan |
|---|---|---|
| id_pengaturan | INT | Primary key |
| nama_pengaturan | VARCHAR(100) | Nama konfigurasi |
| nilai_pengaturan | VARCHAR(100) | Nilai konfigurasi |

Contoh pengaturan yang disimpan adalah batas maksimum berat kendaraan.

### 6.4 Hubungan Antar Tabel

- Satu kendaraan dapat memiliki banyak riwayat penimbangan.
- Setiap riwayat penimbangan terhubung dengan satu kendaraan yang teridentifikasi.
- Pengaturan sistem digunakan sebagai acuan dalam pemeriksaan berat.

---

## 7. Kebutuhan Antarmuka

### 7.1 Halaman Login

Halaman masuk untuk Admin dan Operator. Form terdiri dari:
- Field username
- Field password
- Tombol Login
- Link ke register (hanya untuk Admin pertama kali)

Validasi kredensial dilakukan pada server. Jika login berhasil, pengguna dialihkan ke dashboard sesuai role-nya. Jika gagal, menampilkan pesan "Username atau password salah".

### 7.2 Dashboard Utama

Dashboard menampilkan:
- Berat kendaraan yang sedang ditimbang.
- Identitas kendaraan.
- Status penimbangan.
- Batas maksimum berat.
- Indikator kondisi sistem.
- Riwayat penimbangan terbaru.

### 7.3 Halaman Data Kendaraan

Fitur:
- Menambahkan data kendaraan.
- Mendaftarkan UID RFID.
- Mengubah informasi kendaraan.
- Menghapus atau menonaktifkan data kendaraan.
- Melihat daftar kendaraan yang terdaftar.

### 7.4 Halaman Riwayat Penimbangan

Fitur:
- Menampilkan seluruh catatan penimbangan.
- Menampilkan nomor kendaraan.
- Menampilkan berat kendaraan.
- Menampilkan status sesuai atau berlebih.
- Menampilkan tanggal dan waktu penimbangan.
- Melakukan pencarian berdasarkan kendaraan atau tanggal.

### 7.5 Halaman Pengaturan

Fitur:
- Mengubah batas maksimum berat.
- Melihat konfigurasi sistem.
- Mengatur parameter yang diperlukan dalam proses penimbangan.

---

## 8. Kebutuhan Perangkat Keras dan Perangkat Lunak

### 8.1 Perangkat Keras

| Komponen | Jumlah | Fungsi |
|---|---:|---|
| ESP32 | 1 | Pusat kendali |
| Load Cell 5 kg | 1 | Pengukur berat |
| HX711 | 1 | Pengolah sinyal Load Cell |
| Sensor inframerah | 2 | Deteksi kendaraan masuk dan keluar |
| Servo motor | 2 | Penggerak palang masuk dan keluar |
| RFID RC522 | 1 | Pembaca kartu RFID |
| Kartu/tag RFID | Sesuai kebutuhan | Identitas kendaraan |
| Active buzzer | 1 | Peringatan kelebihan berat |
| LED merah | 1 | Indikator berat berlebih |
| LED hijau | 1 | Indikator berat sesuai |
| Baterai atau power bank | 1 | Sumber daya |
| Platform timbang | 1 | Tempat kendaraan diletakkan |

### 8.2 Perangkat Lunak

| Perangkat Lunak | Fungsi |
|---|---|
| Arduino IDE | Pemrograman ESP32 |
| PHP | Backend dan REST API |
| MySQL | Penyimpanan data |
| XAMPP | Server lokal |
| HTML | Struktur dashboard |
| CSS | Tampilan dashboard |
| JavaScript | Interaksi dashboard |
| Chart.js | Visualisasi data jika diperlukan |
| Web Browser | Mengakses dashboard |

---

## 9. Batasan Sistem

1. Sistem merupakan prototipe jembatan timbang mini, bukan alat timbang kendaraan industri.
2. Kapasitas pengukuran mengikuti spesifikasi Load Cell 5 kg.
3. Identifikasi kendaraan dilakukan menggunakan kartu atau tag RFID.
4. Setiap UID RFID harus didaftarkan terlebih dahulu pada database.
5. Sistem menggunakan jaringan Wi-Fi untuk mengirim data ke server.
6. Penyimpanan data membutuhkan server yang aktif dan dapat diakses oleh ESP32.
7. Sistem menggunakan dua sensor inframerah untuk mendeteksi kendaraan masuk dan memastikan kendaraan keluar.
8. Sistem menggunakan dua servo sebagai penggerak palang masuk dan keluar.

---

## 10. Kriteria Keberhasilan dan Pengujian

Sistem dinyatakan berhasil apabila memenuhi kriteria berikut.

| ID | Pengujian | Kriteria Keberhasilan |
|---|---|---|
| TC-01 | Deteksi kendaraan masuk | IR 1 mendeteksi kendaraan dengan benar. |
| TC-02 | Palang masuk | Servo 1 membuka palang setelah kendaraan terdeteksi. |
| TC-03 | Pengukuran berat | Load Cell dapat membaca berat kendaraan setelah dikalibrasi. |
| TC-04 | Berat sesuai batas | Lampu hijau menyala dan buzzer tidak aktif. |
| TC-05 | Berat berlebih | Lampu merah menyala dan buzzer berbunyi. |
| TC-06 | Pembacaan RFID valid | UID dapat dibaca dan dikenali oleh server. |
| TC-07 | Pembacaan RFID tidak valid | Sistem menolak UID yang belum terdaftar. |
| TC-08 | Palang keluar | Servo 2 membuka setelah proses identifikasi berhasil. |
| TC-09 | Deteksi kendaraan keluar | IR 2 mendeteksi kendaraan yang melewati jalur keluar. |
| TC-10 | Penutupan palang | Servo 2 menutup setelah kendaraan keluar sepenuhnya. |
| TC-11 | Penyimpanan database | Data kendaraan dan hasil penimbangan tersimpan dengan benar. |
| TC-12 | Dashboard | Data hasil penimbangan dapat ditampilkan melalui web. |
| TC-13 | Login valid | Admin/Operator dapat login dengan kredensial yang benar dan dialihkan ke dashboard. |
| TC-14 | Login invalid | Sistem menolak login dengan username/password salah dan tetap di halaman login. |
| TC-15 | Akses ditolak | Pengguna tanpa session valid diarahkan kembali ke halaman login. |

---

## 11. Pengembangan Selanjutnya

Pengembangan lebih lanjut yang dapat dilakukan setelah prototipe utama berhasil antara lain:

1. ~~Menambahkan autentikasi admin pada dashboard~~ (sudah menjadi requirement utama di versi 1.1).
2. Menambahkan fitur ekspor riwayat penimbangan ke Excel atau PDF.
3. Menambahkan grafik statistik penimbangan.
4. Menambahkan pencatatan jumlah kendaraan yang telah ditimbang.
5. Mengembangkan penyimpanan data menggunakan server cloud agar dapat diakses dari jaringan berbeda.

---

## 12. Penutup

Smart Weighing Bridge dirancang sebagai prototipe sistem jembatan timbang mini yang mengintegrasikan pengukuran berat, identifikasi kendaraan menggunakan RFID, pengendalian palang otomatis, serta pencatatan data berbasis web. Dengan penerapan arsitektur IoT empat lapisan, sistem diharapkan mampu menunjukkan proses integrasi antara sensor, mikrokontroler, jaringan komunikasi, server, basis data, dan aplikasi pengguna secara terstruktur.