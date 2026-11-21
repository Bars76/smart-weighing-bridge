/*
 * SMART WEIGHING BRIDGE - Firmware ESP32
 * ---------------------------------------------------------------
 * Alur sesuai prd.md v1.1:
 *   1. Standby, tunggu IR1 mendeteksi kendaraan masuk
 *   2. Servo 1 membuka palang masuk
 *   3. Load Cell (HX711) mengukur berat
 *   4. Cek batas berat -> LED hijau / merah + buzzer
 *   5. RC522 membaca UID kartu RFID
 *   6. POST berat + UID ke server PHP -> simpan ke MySQL
 *   7. UID valid (Sesuai ATAU Berlebih): data tetap tercatat, servo 2 membuka
 *      palang keluar, tunggu IR2, lalu tutup. Jika Berlebih, lampu merah + buzzer
 *      tetap menyala sampai kendaraan selesai keluar.
 *      Jika UID tidak terdaftar: palang keluar tetap tertutup.
 *   8. Kirim heartbeat tiap 15 detik dan status LIVE tiap 1 detik
 *      (berat, tahap, palang, UID, RSSI, uptime) ke /api/live.php.
 *   9. Kembali standby
 *
 * Pin mengikuti konfigurasi yang diberikan (lihat bagian DEFINISI PIN).
 *
 * Library yang dibutuhkan (Arduino IDE - Library Manager):
 *   - MFRC522 (by GithubCommunity)
 *   - ESP32Servo
 *   - HX711 (by bogde)
 *   - WiFi & HTTPClient (sudah bawaan ESP32 Arduino Core)
 *   - SPI (bawaan)
 */

#include <SPI.h>
#include <MFRC522.h>
#include <ESP32Servo.h>
#include "HX711.h"
#include <WiFi.h>
#include <HTTPClient.h>

// ---------------- 1. DEFINISI PIN PERANGKAT KERAS ----------------
#define RFID_SS_PIN    5
#define RFID_RST_PIN   4
#define RFID_SCK_PIN   18
#define RFID_MISO_PIN  19
#define RFID_MOSI_PIN  23

const int SERVO_MASUK_PIN  = 13;
const int SERVO_KELUAR_PIN = 14;

const int IR_MASUK_PIN     = 34; // Sensor IR 1
const int IR_KELUAR_PIN    = 35; // Sensor IR 2

const int HX711_DOUT_PIN   = 16;
const int HX711_SCK_PIN    = 17;

const int LED_MERAH_PIN    = 25; // Peringatan / Overload / Gagal
const int LED_HIJAU_PIN    = 26; // Aman / Memenuhi Syarat
const int BUZZER_PIN       = 27; // Alarm

// ---------------- 2. KONFIGURASI Jaringan & Server ----------------
const char* WIFI_SSID     = "NAMA_WIFI";
const char* WIFI_PASSWORD = "PASSWORD_WIFI";

// Ganti dengan IP LAN komputer yang menjalankan XAMPP.
// Contoh: 192.168.1.10. Pastikan ESP32 satu jaringan dengan komputer ini.
const char* SERVER_TERAKHIR = "http://192.168.xxx.x/iot_cihuy/api/timbangan.php";
const char* SERVER_BATAS    = "http://192.168.xxx.x/iot_cihuy/api/batas.php";
const char* SERVER_HEARTBEAT= "http://192.168.xxx.x/iot_cihuy/api/heartbeat.php";
const char* SERVER_LIVE     = "http://192.168.xxx.x/iot_cihuy/api/live.php";

// ---------------- 3. KALIBRASI & NILAI DEFAULT ----------------
// Faktor kalibrasi HX711. Ganti dengan hasil kalibrasi load cell Anda.
// Cara Singkat:
//   1. Kosongkan platform, panggil scale.tare() di setup.
//   2. Letakkan beban tahu (misal 1000 gram).
//   3. Baca scale.get_units(10) tanpa set_scale -> misal 812.50.
//   4. faktor = 812.50 / 1000 = 0.8125 -> isi di bawah ini.
const float FAKTOR_KALIBRASI = 1.0;

// Dipakai jika server tidak bisa dijangkau saat ambil batas berat.
// Seharusnya sama dengan nilai di halaman Pengaturan ( default 5000 gram ).
const float BATAS_FALLBACK = 5000.0;

const int SERVO_TUTUP = 0;
const int SERVO_BUKA  = 90;

// Logika sensor IR. Module IR umum aktif-LOW (deteksi = LOW).
// Jika modul Anda aktif-HIGH, ubah menjadi HIGH.
const int IR_TERDETEKSI = LOW;

// ---------------- 4. OBJEK & STATE ----------------
MFRC522 mfrc522(RFID_SS_PIN, RFID_RST_PIN);
Servo servoMasuk;
Servo servoKeluar;
HX711 scale;

enum State { STANDBY, MASUK, TIMBANG, TUNGGU_RFID, KELUAR };
State state = STANDBY;

float beratSekarang = 0;
float batasBerat    = BATAS_FALLBACK;
bool  beratBerlebih = false;
String uidTerakhir  = "";
unsigned long lastDebug     = 0;
unsigned long lastHeartbeat = 0;
unsigned long lastLive      = 0;
const unsigned long HEARTBEAT_INTERVAL = 15000; // kirim status tiap 15 detik
const unsigned long LIVE_INTERVAL      = 1000;  // kirim berat real-time tiap 1 detik

// ---------------- 5. FUNGSI BANTU ----------------
void bunyiAlarm(bool aktif) {
  if (aktif) tone(BUZZER_PIN, 1000);
  else       noTone(BUZZER_PIN);
}

void indikator(bool aman) {
  digitalWrite(LED_HIJAU_PIN, aman ? HIGH : LOW);
  digitalWrite(LED_MERAH_PIN, aman ? LOW  : HIGH);
  bunyiAlarm(!aman);
}

void matikanIndikator() {
  digitalWrite(LED_HIJAU_PIN, LOW);
  digitalWrite(LED_MERAH_PIN, LOW);
  bunyiAlarm(false);
}

String uidToString(byte *buffer, byte ukuran) {
  String s = "";
  for (byte i = 0; i < ukuran; i++) {
    if (buffer[i] < 0x10) s += "0";
    s += String(buffer[i], HEX);
  }
  s.toUpperCase();   // format harus sama dengan UID di halaman Data Kendaraan
  return s;
}

// Ambil batas berat dari database through api/batas.php.
// Format respons: {"status":"ok","batas_berat":5000}
float ambilBatasDariServer() {
  if (WiFi.status() != WL_CONNECTED) return BATAS_FALLBACK;

  HTTPClient http;
  http.begin(SERVER_BATAS);
  int code = http.GET();
  if (code != 200) { http.end(); return BATAS_FALLBACK; }

  String res = http.getString();
  http.end();

  int pos = res.indexOf("\"batas_berat\":");
  if (pos < 0) return BATAS_FALLBACK;

  String angka = res.substring(pos + 14); // lewati '"batas_berat":'
  angka.trim();
  float nilai = angka.toFloat();
  return (nilai > 0) ? nilai : BATAS_FALLBACK;
}

// Kirim "saya masih hidup" ke server agar dashboard menampilkan status nyata.
void kirimHeartbeat() {
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  http.begin(SERVER_HEARTBEAT);
  http.GET();        // server mencatat waktu terakhir = sekarang
  http.end();
}

// Nama state untuk dikirim ke server (harus cocok dengan api/live.php).
String namaState(State s) {
  switch (s) {
    case MASUK:       return "MASUK";
    case TIMBANG:     return "TIMBANG";
    case TUNGGU_RFID: return "TUNGGU_RFID";
    case KELUAR:      return "KELUAR";
    default:          return "STANDBY";
  }
}

// Kirim status live tiap detik: berat, tahap, palang, UID, WiFi, uptime.
void kirimStatusLive() {
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  http.begin(SERVER_LIVE);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  float b = scale.get_units(3);
  if (b < 0) b = 0;

  String body = "berat=" + String(b, 2)
              + "&state=" + namaState(state)
              + "&palang_masuk="  + String(servoMasuk.read()  == SERVO_BUKA ? 1 : 0)
              + "&palang_keluar=" + String(servoKeluar.read() == SERVO_BUKA ? 1 : 0)
              + "&uid_rfid=" + uidTerakhir
              + "&rssi=" + String(WiFi.RSSI())
              + "&uptime_ms=" + String(millis());

  http.POST(body);
  http.end();
}

// Kirim hasil penimbangan. Mengembalikan HTTP code; respons ditaruh di `respon`.
// POST: berat=<gram>&uid_rfid=<UID>
int kirimKeServer(float berat, String uid, String &respon) {
  respon = "";
  if (WiFi.status() != WL_CONNECTED) return -1;

  HTTPClient http;
  http.begin(SERVER_TERAKHIR);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  String body = "berat=" + String(berat, 2) + "&uid_rfid=" + uid;
  int code = http.POST(body);
  respon = http.getString();
  http.end();
  return code;
}

// Baca berat rata-rata dari HX711 (gram).
float bacaBerat() {
  float b = scale.get_units(10);
  return (b < 0) ? 0 : b;
}

void debugState(const char* pesan) {
  if (millis() - lastDebug > 1500) {
    Serial.println(pesan);
    lastDebug = millis();
  }
}

// ---------------- 6. SETUP ----------------
void setup() {
  Serial.begin(115200);

  pinMode(LED_MERAH_PIN, OUTPUT);
  pinMode(LED_HIJAU_PIN, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  pinMode(IR_MASUK_PIN, INPUT);
  pinMode(IR_KELUAR_PIN, INPUT);
  matikanIndikator();

  // RFID dengan SPI pin custom
  SPI.begin(RFID_SCK_PIN, RFID_MISO_PIN, RFID_MOSI_PIN, RFID_SS_PIN);
  mfrc522.PCD_Init();
  Serial.println("RFID siap.");

  // Load cell
  scale.begin(HX711_DOUT_PIN, HX711_SCK_PIN);
  scale.set_scale(FAKTOR_KALIBRASI);
  scale.tare();
  Serial.println("HX711 siap.");

  // Servo
  servoMasuk.attach(SERVO_MASUK_PIN);
  servoKeluar.attach(SERVO_KELUAR_PIN);
  servoMasuk.write(SERVO_TUTUP);
  servoKeluar.write(SERVO_TUTUP);

  // WiFi
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  Serial.print("Menghubungkan ke WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println();
  Serial.print("ESP32 IP: ");
  Serial.println(WiFi.localIP());

  batasBerat = ambilBatasDariServer();
  Serial.print("Batas berat: ");
  Serial.print(batasBerat);
  Serial.println(" gram");
}

// ---------------- 7. LOOP / STATE MACHINE ----------------
void loop() {
  // Pastikan WiFi tetap terhubung
  if (WiFi.status() != WL_CONNECTED) {
    WiFi.reconnect();
    delay(1000);
  }

  // Heartbeat non-blocking: tidak mengganggu alur penimbangan.
  if (millis() - lastHeartbeat >= HEARTBEAT_INTERVAL) {
    kirimHeartbeat();
    lastHeartbeat = millis();
  }

  // Status live tiap 1 detik untuk Dashboard & Monitor Publik.
  if (millis() - lastLive >= LIVE_INTERVAL) {
    kirimStatusLive();
    lastLive = millis();
  }

  switch (state) {

    // --- 1. STANDBY: tunggu kendaraan masuk ---
    case STANDBY:
      debugState("Standby - menunggu IR1...");
      if (digitalRead(IR_MASUK_PIN) == IR_TERDETEKSI) {
        Serial.println("IR1: kendaraan terdeteksi. Buka palang masuk.");
        servoMasuk.write(SERVO_BUKA);
        state = MASUK;
        delay(500);
      }
      break;

    // --- 2. MASUK: tunggu kendaraan lewat palang masuk ---
    case MASUK:
      if (digitalRead(IR_MASUK_PIN) != IR_TERDETEKSI) {
        servoMasuk.write(SERVO_TUTUP);
        Serial.println("Palang masuk ditutup. Mulai penimbangan.");
        state = TIMBANG;
        delay(1500); // beri waktu kendaraan berada di atas platform
      }
      break;

    // --- 3. TIMBANG: baca berat, cek batas ---
    case TIMBANG:
      beratSekarang = bacaBerat();
      Serial.print("Berat: ");
      Serial.print(beratSekarang);
      Serial.println(" gram");

      beratBerlebih = (beratSekarang > batasBerat);
      if (beratBerlebih) {
        Serial.println("BERLEBIH - lampu merah ON (tetap boleh lewat, tetap tercatat)");
        indikator(false);
      } else {
        Serial.println("SESUAI - lampu hijau ON");
        indikator(true);
      }
      state = TUNGGU_RFID;
      break;

    // --- 4. TUNGGU_RFID: scan kartu, kirim ke server ---
    case TUNGGU_RFID:
      if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
        String uid = uidToString(mfrc522.uid.uidByte, mfrc522.uid.size);
        uidTerakhir = uid;
        Serial.print("UID terbaca: ");
        Serial.println(uid);

        String respon;
        int code = kirimKeServer(beratSekarang, uid, respon);
        Serial.print("HTTP ");
        Serial.println(code);
        Serial.println(respon);

        if (code == 201) {
          // Data tersimpan baik Sesuai maupun Berlebih. Keduanya boleh lewat.
          beratBerlebih = (respon.indexOf("\"status_berat\":\"Berlebih\"") >= 0);

          if (beratBerlebih) {
            Serial.println("Berat berlebih tercatat - lampu merah tetap ON sampai kendaraan keluar.");
            indikator(false);   // merah + buzzer menyala selama proses keluar
          } else {
            Serial.println("Sesuai - lampu hijau ON.");
            indikator(true);
          }
          servoKeluar.write(SERVO_BUKA);
          state = KELUAR;
        } else if (code == 404) {
          Serial.println("UID tidak terdaftar - palang keluar ditutup.");
          indikator(false);
          delay(2000);
          mfrc522.PICC_HaltA();
          break; // tetap tunggu RFID scan ulang
        } else {
          Serial.println("Gagal mengirim data - palang keluar ditutup.");
          indikator(false);
          delay(2000);
          mfrc522.PICC_HaltA();
          break;
        }

        mfrc522.PICC_HaltA();
      } else {
        debugState("Tunggu kartu RFID...");
      }
      break;

    // --- 5. KELUAR: tunggu IR2, lalu tutup palang ---
    case KELUAR:
      debugState("Menunggu kendaraan melewati IR2...");
      if (digitalRead(IR_KELUAR_PIN) == IR_TERDETEKSI) {
        delay(1000); // tunggu kendaraan benar-benar lewat
        if (digitalRead(IR_KELUAR_PIN) != IR_TERDETEKSI) {
          servoKeluar.write(SERVO_TUTUP);
          Serial.println("IR2: kendaraan keluar. Palang ditutup.");
          matikanIndikator();
          batasBerat = ambilBatasDariServer(); // refresh batas (bila diubah admin)
          state = STANDBY;
          delay(500);
        }
      }
      break;
  }
}
