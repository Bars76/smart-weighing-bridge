/*
 * HX711 CALIBRATION TOOL - Smart Weighing Bridge
 * ---------------------------------------------------------------
 * Sketch khusus untuk kalibrasi load cell. Tidak perlu menyentuh
 * firmware utama sampai faktor kalibrasi ditemukan.
 *
 * Pin mengikuti firmware utama:
 *   HX711 DOUT -> GPIO 16
 *   HX711 SCK  -> GPIO 17
 *
 * Cara pakai:
 *   1. Upload sketch ini ke ESP32, buka Serial Monitor (115200).
 *   2. Pastikan platform KOSONG, lalu kirim perintah: t
 *      ( Ini akan mengnolkan pembacaan / tare )
 *   3. Letakkan beban yang diketahui, misalnya 1000 gram.
 *   4. Kirim perintah: 1
 *      ( Sketch akan menghitung faktor kalibrasi otomatis )
 *      Atau kirim w<gram>, misal w500 untuk beban 500 gram.
 *   5. Salin nilai FAKTOR_KALIBRASI yang tercetak ke firmware utama.
 *
 * Perintah yang tersedia:
 *   t      = tare / nolkan (platform HARUS kosong)
 *   1      = hitung faktor dengan beban 1000 gram
 *   w<gram>= hitung faktor dengan beban tertentu, misal w2000
 *   s      = tampilkan faktor yang sedang dipakai sketch ini
 *   h      = tampilkan bantuan
 */
#include "HX711.h"

const int HX711_DOUT_PIN = 16;
const int HX711_SCK_PIN  = 17;

HX711 scale;

// Faktor sementara dipakai 1.0 supaya angka mentah mudah dibaca.
// Nanti ganti dengan hasil kalibrasi di firmware utama.
float faktorSementara = 1.0;

unsigned long lastPrint = 0;
String inputSerial = "";

void tampilBantuan() {
  Serial.println();
  Serial.println(F("=== PERINTAH KALIBRASI ==="));
  Serial.println(F("t       = tare / nolkan (platform kosong!)"));
  Serial.println(F("1       = hitung faktor dengan beban 1000 gram"));
  Serial.println(F("w<gram> = hitung faktor, misal: w500"));
  Serial.println(F("s       = lihat faktor sementara"));
  Serial.println(F("h       = tampilkan bantuan ini"));
  Serial.println(F("==========================="));
  Serial.println();
}

void hitungFaktor(float gram) {
  if (gram <= 0) {
    Serial.println(F("Error: berat beban harus lebih dari 0."));
    return;
  }

  Serial.print(F("Membaca 20 sampel dengan beban "));
  Serial.print(gram, 1);
  Serial.println(F(" gram..."));

  long raw = scale.get_value(20);   // nilai mentah setelah tare

  Serial.print(F("Nilah raw sekarang : "));
  Serial.println(raw);

  if (raw == 0) {
    Serial.println(F("Error: raw = 0. Pastikan beban benar-benar di atas platform dan HX711 terhubung."));
    return;
  }

  if (raw < 0) {
    Serial.println(F("PERINGATAN: nilai raw NEGATIF saat ada beban!"));
    Serial.println(F("Kemungkinan kabel load cell terbalik. Tukar A+ <-> A- (atau E+ <-> E-)."));
    Serial.println(F("Atau bisa juga dengan mengalikan faktor dengan -1."));
  }

  float faktor = (float) raw / gram;
  faktorSementara = faktor;
  scale.set_scale(faktorSementara);

  Serial.println();
  Serial.println(F("=== HASIL KALIBRASI ==="));
  Serial.print(F("Beban diketahui  : ")); Serial.print(gram, 1); Serial.println(F(" gram"));
  Serial.print(F("Nilai raw        : ")); Serial.println(raw);
  Serial.print(F("FAKTOR_KALIBRASI = ")); Serial.println(faktor, 6);
  Serial.println(F("Salin angka di atas ke firmware utama:"));
  Serial.print(F("const float FAKTOR_KALIBRASI = ")); Serial.print(faktor, 6); Serial.println(F(";"));
  Serial.println();
}

void prosesPerintah(String cmd) {
  cmd.trim();
  if (cmd.length() == 0) return;

  if (cmd == "t" || cmd == "T") {
    Serial.println(F("Menolkan (tare)... pastikan platform KOSONG."));
    scale.tare(20);
    Serial.println(F("Tare selesai. Sekarang letakkan beban yang diketahui."));
  }
  else if (cmd == "1") {
    hitungFaktor(1000.0);
  }
  else if (cmd.charAt(0) == 'w' || cmd.charAt(0) == 'W') {
    float gram = cmd.substring(1).toFloat();
    hitungFaktor(gram);
  }
  else if (cmd == "s" || cmd == "S") {
    Serial.print(F("Faktor sementara = "));
    Serial.println(faktorSementara, 6);
  }
  else if (cmd == "h" || cmd == "H" || cmd == "?") {
    tampilBantuan();
  }
  else {
    Serial.print(F("Perintah tidak dikenal: ")); Serial.println(cmd);
    tampilBantuan();
  }
}

void setup() {
  Serial.begin(115200);
  while (!Serial) { delay(10); }

  Serial.println();
  Serial.println(F("=== KALIBRASI LOAD CELL - SMART WEIGHING BRIDGE ==="));
  Serial.println(F("DOUT = GPIO 16 | SCK = GPIO 17"));
  Serial.println();

  scale.begin(HX711_DOUT_PIN, HX711_SCK_PIN);
  delay(1000);                  // tunggu modul stabil setelah power-on

  if (!scale.is_ready()) {
    Serial.println(F("ERROR: HX711 tidak siap. Cek kabel VCC, GND, DOUT, SCK!"));
    Serial.println(F("VCC -> 5V/3V3, GND -> GND ESP32, DT->16, SCK->17"));
    while (1) { delay(1000); }
  }

  scale.set_scale(faktorSementara);
  scale.tare(20);               // anggap awalnya kosong

  Serial.println(F("HX711 siap. Platform dianggap KOSONG."));
  Serial.println(F("Kirim 't' untuk tare ulang, '1' untuk kalibrasi 1000 gram, atau 'h' untuk bantuan."));
  Serial.println();
  Serial.println(F("raw             | gram"));
  Serial.println(F("----------------+---------------"));
}

void loop() {
  // Terima perintah dari Serial Monitor
  if (Serial.available() > 0) {
    inputSerial = Serial.readStringUntil('\n');
    prosesPerintah(inputSerial);
  }

  // Tampilkan pembacaan tiap 500 ms
  if (millis() - lastPrint >= 500) {
    long raw   = scale.get_value(5);
    float gram = scale.get_units(5);

    Serial.print(raw);
    Serial.print("             | ");
    Serial.println(gram, 2);

    if (raw < -50) {
      Serial.println(F("^ raw negatif besar -> cek kabel A+/A- load cell!"));
    }

    lastPrint = millis();
  }
}
