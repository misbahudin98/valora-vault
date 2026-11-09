# Panduan Presentasi Eksekutif Valora Agency OS (Valora Vault v6.31.0)
> **Sasaran Presentasi:** Dewan Direksi, Stakeholder Manajemen & Klien PT Valora Digital Solusindo  
> **Tema Utama:** Sistem Operasi Agensi Digital, Alur End-to-End Bisnis & Pembuktian Mutu Rekayasa (73 Tests Passed)

---

## 1. Pilihan Format Presentasi (Tersedia 2 Opsi)

### Opsi A: Presentasi Interaktif Web HTML5 (Sangat Direkomendasikan ⭐)
- **Lokasi Berkas:** [`doc/presentation/valora_agency_os_presentation.html`](valora_agency_os_presentation.html)
- **Cara Menjalankan:**
  - Cukup klik dua kali berkas `.html` tersebut atau buka di peramban favorit Anda (Google Chrome, Microsoft Edge, Safari, Mozilla Firefox).
  - Bekerja 100% mandiri (*offline-capable*), bebas ketergantungan internet atau server lokal.
- **Pintasan Keyboard untuk Presenter:**
  - `→` (Panah Kanan) / `Spasi` / `Page Down`: Berpindah ke slide berikutnya.
  - `←` (Panah Kiri) / `Backspace` / `Page Up`: Kembali ke slide sebelumnya.
  - `F`: Aktifkan / keluar mode Layar Penuh (*Fullscreen*).
  - `N`: Buka / tutup Catatan Presenter (*Speaker Notes*) yang memuat poin bicara dan intisari bisnis setiap slide.
  - `Home` / `End`: Lompat langsung ke slide awal atau slide penutup.
- **Fitur Unggulan:**
  - **Simulasi Uji Otomatis:** Tombol "Simulasi Test (73/73)" di pojok kanan atas untuk mensimulasikan eksekusi pengujian PHPUnit langsung di depan audiens.
  - **Filter Interaktif Skenario Testing:** Pilah 11 suite pengujian berdasarkan domain (*Autentikasi & Keamanan*, *Klien & Scope*, *Finansial & Billing*), lalu klik untuk melihat rincian pola Arrange-Act-Assert (AAA) dan cakupan pengujian.
  - **Bilah Progres Gradien:** Indikator visual progres presentasi di bagian paling atas layar.

---

### Opsi B: Berkas Microsoft PowerPoint Asli (.pptx)
- **Lokasi Berkas:** [`doc/presentation/valora_agency_os_presentation.pptx`](valora_agency_os_presentation.pptx)
- **Format:** Standar Layar Lebar Widescreen 16:9 (13.333" x 7.5").
- **Kompatibilitas:** Microsoft PowerPoint 2016 s/d 2026, Office 365, Google Slides, WPS Office, dan LibreOffice Impress.
- **Cocok Untuk:** Presentasi proyektor ruangan rapat konvensional, klik pointer wireless, atau lampiran berkas email ke dewan direksi.

---

## 2. Struktur 12 Slide Presentasi & Waktu Alokasi Bicara

| No | Judul Slide | Pokok Bahasan Utama | Estimasi Durasi |
|:---|:---|:---|:---:|
| **01** | **Judul & Pernyataan Visi** | Valora Agency OS v6.31.0, identitas PT Valora Digital Solusindo, ringkasan arsitektur Hyperion Vault. | 1.5 Menit |
| **02** | **Latar Belakang & Masalah** | 4 titik kegagalan agensi: Scope creep liar, piutang tertahan, chat reporting tercecer, dan invoice manual tidak standar. | 2.5 Menit |
| **03** | **Arsitektur Solusi Enterprise** | 3-Tier Enterprise: Laravel 13 SPA, Svelte 5 Native Runes, SQLite WAL, OWASP ASVS L2. | 2.0 Menit |
| **04** | **Alur #1: Intake Klien** | Registrasi profil, kontrak *Development* vs *Maintenance*, target deadline, dan penunjukan Lead PIC tim. | 2.0 Menit |
| **05** | **Alur #2: Scope Builder & 12 Kapabilitas** | Struktur modul 3NF, pembatasan ruang lingkup, dan matriks 12 kapabilitas fitur standar agensi. | 2.5 Menit |
| **06** | **Alur #3: Monitoring Progres Dinamis** | Visualisasi Tufte (0-100%), transisi siklus status modul, log catatan harian, sinkronisasi widget sidebar. | 2.0 Menit |
| **07** | **Alur #4: Master Tarif Standar Acuan** | SSoT harga resmi: Tarif jam per peran keahlian, tarif flat fitur, retainer SLA, dan estimator otomatis. | 2.0 Menit |
| **08** | **Alur #5: Invoice, Termin & Kuitansi** | Penagihan termin (DP, Milestone, Pelunasan), pelacakan mutasi, kuitansi terbilang rupiah, pesan WhatsApp. | 2.5 Menit |
| **09** | **Alur #6: Ekspor Microsoft Word (.doc)** | Office Word HTML MIME, penanaman logo Base64 fisik (`wdInlineShapePicture`), kop 2 kolom, zero-mismatch. | 2.0 Menit |
| **10** | **Profil Legal & Dashboard Mini Sidebar** | SSoT identitas PT Valora Digital Solusindo dan navigasi carousel proyek aktif di bilah samping. | 1.5 Menit |
| **11** | **Matriks 11 Skenario Testing** | Pembuktian mutu rekayasa: 11 Test Suites, 73 Tests Passed, 768 Assertions, 0 Failures (100% Green). | 2.5 Menit |
| **12** | **Kesimpulan, Kesiapan & Roadmap** | 100% production-ready, kenaikan efisiensi 300%, rencana Midtrans VA/QRIS dan Client Portal. | 2.0 Menit |
| **Total** | **Alokasi Waktu Ideal** | **Presentasi Lengkap & Komprehensif** | **~25 Menit** |

---

## 3. Matriks 11 Skenario Pengujian Fitur (73 Tests / 768 Assertions)

Berikut adalah daftar 11 test suite resmi yang telah diverifikasi kelulusannya 100% pada sistem backend:

1. **`Tests\Feature\SanctumAuthTest`** (10 Tests / 115 Assertions)  
   Menguji siklus autentikasi stateful SPA Sanctum, pembatasan rate limit login brute-force, rotasi CSRF cookie, dan pembatalan token sesi.
2. **`Tests\Feature\AdminUserManagementTest`** (8 Tests / 114 Assertions)  
   Menguji siklus hidup pengguna, validasi izin berbasis peran (RBAC), penonaktifan sesi perangkat lain, dan penghapusan aman (*soft delete*).
3. **`Tests\Feature\AgencyProfileTest`** (5 Tests / 51 Assertions)  
   Menguji *singleton record* profil legal PT Valora Digital Solusindo, pembatasan otorisasi administrator, dan format respons RFC 7807.
4. **`Tests\Feature\ClientProjectManagementTest`** (7 Tests / 88 Assertions)  
   Menguji registrasi klien, validasi tipe kontrak (*Development* vs *Maintenance*), anggaran, dan tenggat waktu target.
5. **`Tests\Feature\ProjectScopeManagementTest`** (6 Tests / 58 Assertions)  
   Menguji pembuatan ruang lingkup modul, validasi enum 12 kapabilitas fitur, dan akumulasi estimasi jam pengerjaan.
6. **`Tests\Feature\ProjectProgressMonitoringTest`** (6 Tests / 61 Assertions)  
   Menguji kalkulasi persentase progres, catatan harian pengembang, dan transisi status modul (*Pending* -> *In Progress* -> *Done*).
7. **`Tests\Feature\BillingAndMasterRateTest`** (10 Tests / 92 Assertions)  
   Menguji master tarif baku, pembuatan faktur termin, rekonsiliasi pembayaran bertahap, dan keakuratan fungsi Terbilang Rupiah.
8. **`Tests\Feature\UserExtendedProfileAndProjectWorkersTest`** (7 Tests / 73 Assertions)  
   Menguji penunjukan Lead PIC proyek, penugasan kolaborator tim kerja, dan atribut profil tambahan staf.
9. **`Tests\Feature\SecurityHeadersTest`** (6 Tests / 38 Assertions)  
   Menguji pemenuhan standar OWASP ASVS Level 2: header CSP, HSTS, X-Content-Type-Options, X-Frame-Options, dan proteksi clickjacking.
10. **`Tests\Feature\RoleConsolidationTest`** (4 Tests / 42 Assertions)  
    Menguji kebijakan *anti-aliasing* dan *zero unauthorized consolidation*, memastikan setiap peran dan izin memiliki batasan diskrit.
11. **`Tests\Feature\AdminStudioTest`** (4 Tests / 36 Assertions)  
    Menguji generator spesifikasi Blueprint Studio dan parsing template dokumen *intake*.

---

## 4. Tips Menghadapi Tanya Jawab (Q&A) Teknis

- **Jika ditanya tentang keamanan data finansial:**  
  *Jawaban:* "Valora Agency OS menerapkan mutasi transaksional atomic via `DB::transaction()` dengan proteksi parameter binding PDO penuh untuk mencegah race conditions dan injeksi SQL. Setiap transaksi pembayaran dicatat dalam audit trail lengkap dengan identitas staf dan timestamp."
- **Jika ditanya mengapa menggunakan SQLite bukan MySQL/PostgreSQL:**  
  *Jawaban:* "SQLite pada mode WAL (Write-Ahead Logging) memberikan latensi sub-milidetik tanpa *overhead network socket*, ideal untuk throughput agensi internal dengan ribuan transaksi per hari. Selain itu, basis data terisolasi dalam satu berkas terenkripsi/terlindungi yang sangat memudahkan *cold-backup* harian otomatis."
- **Jika ditanya bagaimana jika koneksi internet terputus saat mencetak dokumen Word:**  
  *Jawaban:* "Dokumen `.doc` yang diekspor menggunakan penanaman logo Base64 PNG fisik (`wdInlineShapePicture`). Artinya seluruh aset visual tertanam langsung di dalam berkas dokumen, sehingga tidak akan hilang atau berubah format saat dibuka di komputer tanpa internet."
