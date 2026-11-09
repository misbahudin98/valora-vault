# Hyperion Vault - Konsep Desain & Landing Page (design.md)
> **Spesifikasi Konsep Desain Visual, Blueprint Landing Page, dan Panduan Kustomisasi Elemen (v6.31.0)**  
> *Single Source of Truth (SSoT) untuk UI/UX Designer, Frontend Engineer, dan LLM/AI Agent.*

---

## 1. TUJUAN BERKAS & KESINAMBUNGAN DOKUMENTASI

Berkas ini dirancang sebagai panduan tunggal konsep visual dan antarmuka. Posisi berkas ini dalam hierarki dokumentasi proyek:

```
[llms.txt]           <- 1. Index Cepat, Router Tugas & Golden Invariants untuk AI
      │
[README.md]          <- 2. Pintu Masuk Developer: Gambaran Proyek & Quickstart
      │
[architecture.md]   <- 3. Master Blueprint Rekayasa Sistem (Backend, Auth, DB, Updater)
      │
[design.md]          <- 4. Konsep Desain Visual, Landing Page & Kustomisasi Elemen (BERKAS INI)
      │
[prompt.feature.md]  <- 5. SDD Master Compiler Fitur Baru (Track A) & Desain (Track B)
      │
[doc/feature.md]     <- 6. Master Index Fitur & Spesifikasi Modular (doc/features/*.md)
      │
[local.inisiasi.md]  <- 7. Runbook Deterministik Inisiasi Lokal Laragon (Fase 0 - 5)
      │
[changes.md]         <- 8. Audit Trail Historis & Log Perubahan Rekayasa Arsitektur
```

- **Tujuan Berkas:** Menjelaskan secara tegas konsep desain visual yang diadopsi (Universal Dual-Theme Daylight & Dark Obsidian), konsep landing page konversi tinggi, serta panduan praktis kustomisasi elemen visual (termasuk wizard instalasi dan landing page).
- **Target Pembaca:** UI/UX Designer, Frontend Engineer (Svelte 5 + Tailwind v4), dan AI Agent perancang antarmuka.

---

## 2. KONSEP DESAIN VISUAL (VISUAL DESIGN CONCEPT)

### A. Filosofi Desain: Neo-Enterprise Apple Spatial Materials & Glassmorphism
Hyperion Vault mengadopsi estetika **Neo-Enterprise Apple Spatial Materials & Glassmorphism** yang memadukan keandalan sistem perbankan dengan antarmuka futuristik berkinerja tinggi, terinspirasi oleh Apple macOS/visionOS Human Interface Guidelines:
1. **Apple Materials & Dynamic Vibrancy:** Menggunakan material bertingkat (*Ultra-Thin, Thin, Regular, Thick*) dengan `backdrop-blur-xl backdrop-saturate-150` dan dasar kontras terlindungi (*contrast shield*), menjamin teks tidak pernah kabur atau kehilangan keterbacaan (APCA $L_c \ge 75$).
2. **Pencahayaan Spekular Tepi 1px (1px Specular Edge Chamfer):** Garis tepi atas bergradasi halus (`before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/20 before:to-transparent`) yang mensimulasikan refraksi fisik kaca tebal.
3. **Kenyamanan Operator (Anti-Eye-Fatigue):** Menggunakan palet Deep Obsidian (`#090d16`) untuk Dark Mode dan White/Slate-50 untuk Daylight Mode, memberikan fleksibilitas pencahayaan penuh sesuai preferensi pengguna.
4. **Fisika Pegas Teredam (Damped Spring Physics):** Animasi mikro berkecepatan awal tinggi (*fast-to-start*), dapat dibatalkan seketika (*interruptible*), dan berhenti lembut (`active:scale-[0.98]`).
5. **Umpan Balik Auditori Taktil (Tactile Web Audio):** Suara mikro sintetis prosedural via Web Audio API (< 1 KB, zero external audio asset) untuk penegasan sentuhan yang lembut, dengan kontrol Mute persisten di `localStorage`.
6. **Kejelasan Kriptografis:** Penggunaan font monospaced untuk identitas sesi, alamat IP, timestamp UTC, dan token guna memudahkan verifikasi forensik instan.

### B. Rasio Kontras & Standar Aksesibilitas (WCAG AAA)
- Teks primer (`#f8fafc`) di atas kanvas Deep Obsidian (`#090d16`) menghasilkan rasio kontras **14.8:1** (jauh melampaui batas minimum WCAG AAA yaitu 7:1).
- Teks sekunder (`#94a3b8`) menghasilkan rasio kontras **6.2:1** (lulus WCAG AA).
- Status semantik (Sukses `#10b981`, Bahaya `#f43f5e`, Peringatan `#f59e0b`) dipadukan dengan teks kontras tinggi dan indikator bentuk (titik pendar berdenyut / *pulsing ping dot*), sehingga ramah bagi pengguna dengan gangguan penglihatan warna (*color blind friendly*).

### C. Sistem Tipografi 2-Font Terstandarisasi (2-Font Modern Standard)
Tipografi disederhanakan menjadi 2 rumpun font terstandarisasi untuk memangkas *network payload*, mengoptimalkan *Core Web Vitals* (FCP < 1s), dan menjaga keharmonisan visual:
1. **Display & Heading Font (`Plus Jakarta Sans`):**
   - Digunakan untuk: Judul halaman (`H1`–`H3`), banner hero CTA, nilai metrik besar, tagline brand, dan badge penting.
   - Sifat: Geometris kontemporer, berwibawa, modern, rasio estetika tinggi. Ukuran: 20px – 48px (`text-xl` s.d. `text-5xl`), font-weight: 600 – 800.
2. **Primary UI, Body & Technical Font (`Inter`):**
   - Digunakan untuk: Isi tabel data, formulir, teks paragraf, tombol aksi, label navigasi, serta data monospaced / teknis (Session ID, IP address, user-agent, timestamp).
   - Sifat: Keterbacaan superior pada layar padat, rasio *x-height* optimal. Ukuran: 12px – 16px (`text-xs` s.d. `text-base`).

### D. Sistem Token Warna (Design Tokens)
```css
/* Kanvas & Permukaan */
--color-canvas:        #090d16; /* Kanvas utama dashboard & landing page */
--color-surface:       #0f172a; /* Latar kartu & panel data (slate-900) */
--color-surface-hover: #1e293b; /* Baris interaktif saat hover (slate-800) */
--color-border:        rgba(51, 65, 85, 0.6);  /* Border kartu hairline (slate-700/60) */
--color-border-subtle: rgba(30, 41, 59, 0.8);  /* Garis pembatas tabel (slate-800/80) */

/* Identitas Brand */
--color-brand-primary: #4f46e5; /* Indigo-600 (Aksi utama) */
--color-brand-glow:    #6366f1; /* Indigo-500 (Pendar fokus) */
--color-brand-accent:  #06b6d4; /* Cyan-500 (Aksen & sorotan data) */
--color-brand-accent-glow: #22d3ee; /* Cyan-400 (Pendar aktif) */

/* Tipografi */
--text-primary:        #f8fafc; /* Slate-50 */
--text-secondary:      #94a3b8; /* Slate-400 */
--text-muted:          #64748b; /* Slate-500 */

/* Status Semantik */
--color-success:       #10b981; /* Sesi aktif, online (emerald-500) */
--color-warning:       #f59e0b; /* Menunggu tindakan (amber-500) */
--color-danger:        #f43f5e; /* Cabut sesi, akun nonaktif (rose-500) */
--color-info:          #0284c7; /* Hak akses, log audit (sky-600) */
```

### E. Sistem Grid Spasial 8-Point
Semua ukuran margin, padding, tinggi baris, dan jarak antar elemen (`gap`) menggunakan kelipatan **8px** (dengan varian mikro **4px**):
- `4px` (`p-1`, `gap-1`): Jarak mikro antara ikon SVG dan teks label.
- `8px` (`p-2`, `gap-2`): Padding tombol kecil, jarak antar badge hak akses.
- `12px` (`p-3`, `gap-3`): Padding sel tabel padat, margin kontrol filter.
- `16px` (`p-4`, `gap-4`): Padding kartu standar, jarak antar input form.
- `24px` (`p-6`, `gap-6`): Padding panel dashboard utama, modal dialog.
- `32px` (`p-8`, `gap-8`): Margin pemisah antar modul besar.
- `48px` (`py-12`) & `64px` (`py-16`): Padding vertikal seksi landing page.

### F. Geometri Sudut (Border Radius)
- `rounded-sm` (2px): Indikator garis status, tooltip mikro.
- `rounded-lg` (8px): Input text, tombol filter, dropdown select.
- `rounded-xl` (12px): Kartu panel data, container tabel.
- `rounded-2xl` (16px): Dialog modal, kartu sorotan hero, container installer.
- `rounded-full` (9999px): Badge status, pill hak akses, avatar, tombol aksi bulat.

### G. Komponen UI Dasar & Ergonomi
1. **Password Eye-Toggle**: Seluruh formulir kata sandi wajib menyertakan tombol toggle mata SVG murni (`$state(false)`) untuk melihat/menyembunyikan teks tanpa plugin luar.
2. **Accessible Modals**: Dilengkapi top neon accent bar, backdrop blur, fokus otomatis, dan listener tombol `Escape`.
3. **Responsive DataTable**:
   - Pencarian instan 100% memori browser (`$derived.by()`) tanpa query jaringan saat mengetik.
   - Kolom aksi utama mendukung `sticky right-0` dengan latar belakang buram agar tidak tersembunyi pada layar smartphone.
4. **Micro-Loaders (Pure CSS + SVG)**:
   - `PageLoader.svelte`: Layar pemuat ringan saat perpindahan rute atau transisi logout.
   - `ElementLoader.svelte`: Indikator pemuatan mikro (`spinner`, `pulse`, `dots`) di dalam tombol dan tabel.

---

## 3. KONSEP LANDING PAGE (LANDING PAGE CONCEPT)

### A. Tujuan Bisnis & Alur Konversi (Conversion Funnel)
Landing page Hyperion Vault bukan sekadar halaman statis, melainkan representasi keandalan teknis sistem:
1. **Membangun Otoritas Instan (< 3 Detik):** Menampilkan metrik operasional riil (SLO Uptime 99.0%, Latensi P99 ~65ms, 0 Sesi Database) di atas lipatan layar (*above the fold*).
2. **Edukasi Arsitektur Interaktif:** Memandu pengunjung memahami bagaimana arsitektur ini memangkas koneksi MySQL dan menjamin skalabilitas 9000+ data.
3. **Pola Baca Z-Pattern:**
   - Kiri Atas: Logo & Status Versi SemVer (`v5.6.0`).
   - Kanan Atas: Tombol Masuk Cepat / Akses Dashboard.
   - Kiri Tengah: Headline Proposisi Nilai & Dual Call-to-Action (CTA).
   - Kanan Tengah: Maskot AI Interaktif 'Volt'.
   - Bawah: Pita Bukti Metrik Kinerja & 6 Pilar Bento Grid.

### B. Blueprint Cetak Biru Landing Page
```
+--------------------------------------------------------------------------+
| [Logo: Hyperion Vault]                      [v5.6.0]  [Masuk Dashboard]  |
+--------------------------------------------------------------------------+
| HERO SECTION (Split-View Asymmetrical Balance):                         |
|                                                                          |
|  [TAGLINE] High-Performance Zero-Overhead Enterprise Vault               |
|  [SUBHEADLINE] Arsitektur tangguh Laravel 13 + Svelte 5 Runes.           |
|  Latensi P99 < 150ms, Zero-DB Session, siap skala 9000+ data.            |
|                                                                          |
|  [ Tombol: Mulai Eksplorasi ]   [ Tombol: Dokumentasi Arsitektur ]       |
|                                                                          |
|                   +----------------------------------+                   |
|                   |      MASKOT AI 'VOLT' (SVG)      |                   |
|                   |  "Halo! Saya Volt, pemandu       |                   |
|                   |   arsitektur sistem Anda!"       |                   |
|                   |  [Mood: Happy/Excited/Thinking]  |                   |
|                   +----------------------------------+                   |
+--------------------------------------------------------------------------+
| METRIC PROOF BAR (Bilah Pembuktian Kinerja Nyata):                       |
| [ 99.0% SLO Uptime ]   |   [ P99: 65ms Riil ]   |   [ 0 Sesi di MySQL ]  |
+--------------------------------------------------------------------------+
| 6 PILAR ARSITEKTUR BENTO GRID (3x2 Responsive):                          |
| 1. FastCGI Dual-Worker    2. Zero-DB Session       3. Svelte 5 Runes     |
| 4. Signed JWT & Sanctum   5. Skala 9000+ Records   6. Auto Safe Updater  |
+--------------------------------------------------------------------------+
| SECURITY FORENSICS & LITTLE'S LAW PROOF:                                 |
| Formula matematis kapasitas $L = \lambda \times W$ & bukti anti-exhaust   |
+--------------------------------------------------------------------------+
| CONVERSION BOTTOM CTA & DOKUMENTASI INDEKS CEPAT (llms.txt):             |
| [Buka Akun Admin Default]             [Lihat Panduan Integrasi API]      |
+--------------------------------------------------------------------------+
| FOOTER: Hak Cipta, Badge SemVer 2.0.0, Indeks Berkas llms.txt            |
+--------------------------------------------------------------------------+
```

### C. Maskot AI Interaktif 'Volt'
Komponen [`frontend/src/lib/components/features/mascot/ArchitectMascot.svelte`](../frontend/src/lib/components/features/mascot/ArchitectMascot.svelte) berfungsi sebagai pemandu interaktif yang memanusiakan sistem enterprise:
- **Konstruksi Murni Vektor:** Dibuat 100% menggunakan SVG inline dan animasi CSS `@keyframes` (nol aset gambar raster eksternal, 0 KB beban HTTP request tambahan).
- **4 Ekspresi Reaktif:**
  - `happy`: Kondisi istirahat santai, antena berdenyut lembut.
  - `excited`: Saat tombol ditekan atau aksi sukses diselesaikan.
  - `thinking`: Saat proses kalkulasi atau pemuatan data berlangsung.
  - `super`: Mode audit arsitektur penuh dengan cincin neon menyala.
- **Pemandu Interaktif:** Pengunjung dapat mengeklik tombol pilar untuk mendengarkan narasi Volt mengenai mitigasi bottleneck MySQL dan arsitektur file-session.

### D. Optimasi SEO Semantik & Metadata
Landing page wajib mengimplementasikan struktur semantik HTML5 murni:
- `<header>`: Navigasi utama dengan link berstandar aksesibilitas.
- `<main>`: Membungkus hero banner, metric bar, dan bento feature grid.
- `<footer>`: Informasi lisensi, tautan audit, dan penanda versi SemVer.
- **Standar Google Lighthouse:** Target skor **100 Performance, 100 Accessibility, 100 Best Practices, 100 SEO**.

---

## 4. PANDUAN KUSTOMISASI ELEMEN ANTARMUKA (WHITE-LABELING GUIDE)

Gunakan panduan teknis berikut untuk menyesuaikan tampilan sistem sesuai identitas visual klien atau brand korporat Anda:

### A. Kustomisasi Tampilan Wizard Instalasi (`install.php`)
File [`install.php`](../install.php) adalah wizard instalasi & pembaruan mandiri (*self-contained*). Seluruh styling terkapsulasi di dalamnya tanpa dependensi CSS luar.

1. **Mengubah Tema Warna Installer (CSS Variables):**
   Buka [`install.php`](../install.php) baris 396–412 pada blok `<style>`:
   ```css
   :root {
       --bg: #090d16;                  /* Ganti latar belakang, misal: #0a1128 (Midnight Navy) */
       --card-bg: rgba(15, 23, 42, 0.85); /* Permukaan kartu kaca */
       --border: rgba(51, 65, 85, 0.6);  /* Border kartu */
       --cyan: #06b6d4;                /* Ganti aksen warna sekunder */
       --indigo: #6366f1;              /* Ganti aksen warna primer */
       --green: #10b981;               /* Warna indikator sukses / checklist */
   }
   ```
2. **Mengganti Logo & Ikon Header Installer:**
   - Cari blok `.logo-box` di [`install.php`](../install.php) sekitar baris 445–455.
   - Ganti elemen `<svg>` dengan logo vektor SVG perusahaan Anda.
3. **Mengubah Judul & Teks Sambutan:**
   - Buka baris 393 untuk `<title>`.
   - Buka baris 452 untuk tag `<h1>Hyperion Vault Setup Wizard</h1>` dan subteks deskripsi di bawahnya.
4. **Menambah Pemeriksaan Diagnostik Kustom:**
   - Pada method `runDiagnostics()` di [`install.php`](../install.php), tambahkan dependensi PHP atau modul kustom klien:
     ```php
     $requiredExtensions['gd'] = 'Manipulasi Gambar & Avatar';
     $requiredExtensions['curl'] = 'Integrasi Payment Gateway';
     ```

### B. Kustomisasi Tampilan Landing Page (`frontend/src/routes/+page.svelte`)
1. **Mengadopsi Salah Satu dari 5 Preset Desain:**
   Jika klien menginginkan tema visual tertentu, sesuaikan kelas latar belakang dan warna di [`frontend/src/routes/+page.svelte`](../frontend/src/routes/+page.svelte):
   - **Preset 1 (Neo-Enterprise Dark - Default):** `bg-[#090d16] text-slate-100`, aksen Indigo `#4f46e5` & Cyan `#06b6d4`.
   - **Preset 2 (Clean Fintech Light):** `bg-slate-50 text-slate-900`, kartu `bg-white border-slate-200`, aksen Cobalt Blue `#1d4ed8` & Emerald `#059669`.
   - **Preset 3 (Swiss Monochrome):** `bg-white text-black`, kartu `bg-white border-black`, font editorial minimalis tanpa gradien warna.
   - **Preset 4 (Cyberpunk DevTool):** `bg-[#080c14] text-emerald-400`, aksen Terminal Green `#10b981` & Amber `#f59e0b`.
   - **Preset 5 (Midnight Navy):** `bg-[#0a1128] text-slate-100`, kartu `bg-[#101b3b]`, aksen Sapphire Blue `#3b82f6` & Gold `#f59e0b`.
2. **Mengganti Maskot AI Volt dengan Mockup Produk:**
   - Pada [`frontend/src/routes/+page.svelte`](../frontend/src/routes/+page.svelte), cari tag `<ArchitectMascot />`.
   - Jika ingin diganti dengan gambar tangkapan layar antarmuka dashboard, gantikan dengan:
     ```svelte
     <div class="relative rounded-2xl border border-slate-700/60 overflow-hidden shadow-2xl">
       <img src="/dashboard-preview.webp" alt="Pratinjau Antarmuka" class="w-full h-auto" />
     </div>
     ```
3. **Menyesuaikan Copywriting & Nilai Proposisi:**
   - Ubah teks headline, sub-headline, label metrik, dan 6 kartu bento grid sesuai spesifikasi bisnis klien.

### C. Kustomisasi Komponen Dashboard (`frontend/src/lib/components/`)
1. **Sidebar Navigation ([`AppSidebar.svelte`](../frontend/src/lib/components/layout/AppSidebar.svelte)):**
   - Tambahkan menu navigasi baru dengan ikon SVG inline.
   - Hak akses menu dapat dikontrol secara reaktif dengan `$derived.by(() => auth.hasPermission('menu.name'))`.
2. **Status & Role Badges ([`Badge.svelte`](../frontend/src/lib/components/ui/Badge.svelte)):**
   - Mendukung varian status: `active`, `suspended`, `pending`, `admin`, `manager`, `staff`, `user`.
   - Kustomisasi skema warna badge baru cukup dengan menambahkan mapping kelas Tailwind di properti komponen.

---

## 5. SINERGI DENGAN MESIN SINTESIS FITUR (`prompt.feature.md`)

Untuk mengotomatisasi perancangan antarmuka landing page atau fitur baru:
1. **Untuk Landing Page & Penyesuaian Desain:** Buka [`prompt.feature.md`](prompt.feature.md) dan isi formulir **Track B (Landing Page & Design Style Request)**. Agen AI akan secara otomatis menghasilkan kode Svelte 5 yang 100% mematuhi aturan dalam berkas `design.md` ini.
2. **Untuk Penambahan Fitur Baru (CRUD/API):** Buka [`prompt.feature.md`](prompt.feature.md) dan isi formulir **Track A (Raw Business Input Request)**.

---

## 6. OPTIMASI MOBILE-FIRST & TOUCH TARGETS

- **Touch Target Minimum:** Setiap tombol, checkbox, dan tab selector memiliki area sentuh minimal **$44 \times 44\text{ px}$** (`min-h-[44px] min-w-[44px]`) untuk kepatuhan WCAG 2.2 AAA.
- **Mobile Navigation Bar & Sticky Controls:** Khusus layar ponsel (`lg:hidden`), tray kontrol melayang di bagian bawah atau topbar terisolasi dengan aksesibilitas satu jempol (*thumb-zone navigation*) dan feedback taktil.
- **Zero Horizontal Overflow:** Seluruh tabel data responsif dan panel metrik dibungkus kontainer `overflow-x-auto` dengan indikator bayangan lembut pada tepi kanan tanpa memicu *horizontal layout shift*.

---

## 7. REAL-TIME TELEMETRY & SYSTEM HEALTH INDICATOR SYSTEM

1. **Dynamic Clock & Telemetry Bar:**
   - Panel indikator waktu server & runtime gateway (`bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800`) dengan radar dot pulsing (`animate-ping bg-cyan-400`).
   - Format waktu dan telemetri disinkronkan secara presisi via JavaScript Intl API dan timer interval tanpa membebani thread utama.
2. **Active Component & Node Highlighting:**
   - Komponen worker yang aktif (misal: FastCGI Worker 9003 & 9004, SQLite WAL Mode) ditandai secara reaktif dengan:
     - Border & Ring: `border-indigo-500 dark:border-cyan-400 ring-2 ring-indigo-500/20`.
     - Latar Belakang: `bg-indigo-50/40 dark:bg-indigo-950/40 shadow-md`.
     - Lencana Pulsing: `● LIVE` / `● AKTIF` (`bg-emerald-600 text-white animate-pulse text-[9px] font-bold`).
3. **Proof Bar Telemetri Publik:**
   - Metrik sesi aktif, kuota konkurensi perangkat (`user_devices`), latensi P99, dan ketersediaan SLO terhubung ke endpoint publik yang di-cache via NVMe SSD file cache (TTL 300 detik) untuk menjaga batas Little's Law $L \le 0.024$ koneksi tanpa lonjakan koneksi database.

---

## 8. STANDAR MODAL DIALOG STICKY HEADER & ERGONOMI SENIOR / BABY BOOMER (v6.31.0)

Untuk memastikan antarmuka mudah digunakan oleh operator korporat, manajer senior, dan pengguna non-teknis (*gaptek*), setiap dialog modal multi-langkah (*multi-step modal*) wajib mematuhi standar berikut:

### A. Pola Sticky Header Tabs (`headerTabs` Snippet)
1. **Penyematan di Header Modal:**
   - Navigasi sub-tab **DILARANG** diletakkan di dalam area konten yang tergulir (*body*).
   - Seluruh bilah tab dan indikator langkah wajib disematkan ke dalam snippet `headerTabs` pada komponen [`Modal.svelte`](../frontend/src/lib/components/ui/Modal.svelte).
   - Kontainer header terkunci (*pinned/sticky*) di bagian atas dialog dengan latar `backdrop-blur-sm bg-slate-50/95 dark:bg-slate-900/95` dan batas bawah tegas (`border-b`).
2. **Invarian Keterlihatan:**
   - Saat pengguna menggulir formulir panjang ke bawah, bilah tab tidak pernah hilang dari layar.
   - Pengguna senior dapat melihat posisi tahapannya setiap saat dan berpindah ke tahap lain dengan 1 klik tanpa perlu menggulir kembali ke atas.

### B. Matriks Affordance Tab Fisik (Anti-Flat Design)
- **Bentuk Tombol Tab:** Tab bukan berupa teks bergaris bawah (*flat underline*), melainkan kartu tombol fisik dengan `border border-slate-300 dark:border-slate-700`, elevasi bayangan halus, dan sudut melengkung `rounded-xl`.
- **Nomor Langkah:** Setiap tab wajib menampilkan lingkaran nomor langkah kontras tinggi (`w-5 h-5` / `w-6 h-6`).
- **Status Tab Aktif:** Wajib menggunakan latar Indigo pekat (`bg-indigo-600 text-white border border-indigo-400 shadow-md scale-[1.02]`).
- **Status Tab Inaktif:** Menggunakan latar putih/gelap dengan border 1px tegas (`bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-300 dark:border-slate-700 hover:border-indigo-500 hover:bg-indigo-50/80 dark:hover:bg-slate-700`).

### C. Tipografi & Ergonomi Bidang Isian (Senior & Non-Tech Accessibility)
1. **Ukuran Bidang Isian (Touch Target Tinggi):**
   - Tinggi minimal input adalah **44px – 46px** (`min-h-[44px] px-3.5 py-2.5 sm:py-2 rounded-xl`).
   - Ukuran font teks input adalah `text-base sm:text-xs` (16px pada ponsel untuk mencegah *iOS auto-zoom*, 12-14px pada layar desktop).
   - Border input wajib berbatas tegas 1px dengan kontras tinggi (`border border-slate-300 dark:border-slate-700`) agar jelas terlihat pada semua jenis monitor.
   - Efek fokus wajib menyala tegas dengan cincin 2px (`focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20`).
2. **Label Eksplisit & Penanda Keharusan:**
   - Label berukuran `text-xs sm:text-sm font-bold text-slate-900 dark:text-white mb-1.5`.
   - Kolom wajib: Ditandai badge visual eksplisit `<span class="text-xs font-bold text-rose-600 bg-rose-50 dark:bg-rose-950/60 px-2 py-0.5 rounded border border-rose-200 dark:border-rose-900">* Wajib Diisi</span>`.
   - Kolom opsional: Ditandai secara meyakinkan dengan `<span class="text-xs font-normal text-slate-500 dark:text-slate-400">(Boleh Kosong)</span>` atau `(Bila Ada)`.
3. **Kotak Panduan Kontekstual di Setiap Tahap:**
   - Di awal setiap tahap formulir, sediakan kotak petunjuk ramah (*callout box*) berlatar indigo lembut (`bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60`) dengan ikon emoji besar (💡, 📋, 👤, 🛡️, ⚙️) dan kalimat panduan santun.

### D. Bilah Aksi Bawah yang Meyakinkan (Reassuring Bottom Action Bar)
- **Tombol Navigasi Bawah:**
  - Sisi Kiri: `[✕ Batal / Tutup]` (netral, border border-slate-300, touch target $\ge 44$px) dan `[← Kembali ke Tahap Sebelumnya]` (jika bukan di tahap 1).
  - Sisi Kanan: `[Lanjut ke Tahap {X}: {Label} 👉]` dan `[💾 Simpan Data]` (solid Indigo `bg-indigo-600 hover:bg-indigo-500`, border `border-indigo-400`, teks putih tebal, shadow-md, touch target $\ge 44$px).
- Menghilangkan tombol tanpa teks atau tombol ikon kecil tanpa label yang membingungkan pengguna senior.

### E. Standar Tombol Aksi Tabel Data (Anti-Flat Outline Matrix)
1. **Dilarang Teks Flat Tanpa Outline:** Seluruh tombol aksi pada baris tabel **WAJIB** memiliki border minimal 1px dan warna semantik eksplisit.
2. **Matriks Tombol Baris Tabel:**
   - **Tombol Detail / Inspeksi:** Border slate (`bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 shadow-2xs`) dengan SVG icon.
   - **Tombol Edit:** Border indigo (`bg-indigo-50 hover:bg-indigo-100 text-indigo-800 border border-indigo-300 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-700/60 shadow-2xs`) dengan SVG icon.
   - **Tombol Cabut Sesi / Hapus:** Border rose (`bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-300 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-700/60 shadow-2xs`) dengan SVG icon.

---

## 9. STANDAR KETERBACAAN TAB PANE UNTUK PENGGUNA SENIOR / BABY BOOMER (v6.31.0)

Menjawab kebutuhan pengguna senior (usia 50–65+) dan non-teknis (*gaptek*) tanpa memperbesar dimensi fisik elemen atau ukuran font:

### A. Prinsip Rekayasa Keterbacaan & Affordance Tanpa Pembesaran Ukuran
1. **Tray Fisik Segmented Control (Physical Switchboard Metaphor):**
   - Seluruh grup tab dibungkus dalam track/nampan fisik (`p-1 sm:p-1.5 bg-slate-200/90 dark:bg-slate-900 rounded-2xl border border-slate-300 dark:border-slate-800 shadow-inner`).
   - Memberikan sinyal visual instan bahwa deretan tab merupakan sakelar fisik yang dapat ditekan bergantian.
2. **Tab Aktif Berkekuatan Tinggi (High-Contrast Active Pop-Out):**
   - Tab aktif menggunakan latar Indigo pekat (`bg-indigo-600 text-white font-bold border border-indigo-400 shadow-md ring-2 ring-indigo-500/20`).
   - Disertai lencana status eksplisit: `● Aktif` (border tipis kontras) dan lingkaran angka berkontras maksimal.
3. **Tab Inaktif Jelas Interaktif (Clickable Inactive Affordance):**
   - Tab inaktif menggunakan latar putih bersih (`bg-white dark:bg-slate-800`), teks hitam kontras tinggi (`text-slate-900 dark:text-slate-100 font-bold`), dan border fisik 1px kontras (`border border-slate-300 dark:border-slate-700`).
   - DILARANG menggunakan teks abu-abu pudar atau tombol flat tanpa border untuk tab inaktif karena menyerupai label mati (*disabled label*).
4. **Bilah Panduan Mikro (Micro-Guidance Bar):**
   - Di atas tab, sediakan panduan ringkas berukuran `text-[11px] font-bold text-slate-700 dark:text-slate-300`:
     `Langkah X dari Y: [Label]` dan `💡 Klik kotak tab untuk berpindah`.
5. **Semantik Aksesibilitas Penuh (a11y):**
   - Kontainer ber-atribut `role="tablist"`, tombol ber-atribut `role="tab"` dan `aria-selected={currentTab === tab.id}`.

---

## 10. SISTEM NOTIFIKASI TOAST REUSABLE & STATUS FORM (v6.31.0)

Untuk memberikan umpan balik status penyimpanan formulir yang instan, jelas, dan dapat digunakan ulang di seluruh komponen:

### A. Arsitektur Toast Component & Runes State Bus
1. **State Bus Terpusat (`toastState`):**
   - Berkas: [`frontend/src/lib/state/toast.svelte.js`](../frontend/src/lib/state/toast.svelte.js)
   - Menggunakan pure Svelte 5 runes (`$state` array `toasts = $state([])`).
   - Menyediakan API ramah pengembang:
     - `toastState.success(message, title?, duration?)`
     - `toastState.error(message, title?, duration?)`
     - `toastState.warning(message, title?, duration?)`
     - `toastState.info(message, title?, duration?)`
     - `toastState.remove(id)` & `toastState.clear()`
2. **Komponen Visual Reusable (`Toast.svelte`):**
   - Berkas: [`frontend/src/lib/components/ui/Toast.svelte`](../frontend/src/lib/components/ui/Toast.svelte)
   - **Moda Melayang Tengah Atas (Top-Center Floating Bus - Default):** Dipasang di [`+layout.svelte`](../frontend/src/routes/+layout.svelte) (`<Toast />`) sebagai floating toast stack melayang di **tengah atas layar** (`fixed top-4 left-1/2 -translate-x-1/2 z-[9999] max-w-sm sm:max-w-md w-[calc(100%-2rem)]`), langsung berada di garis pandang sentral pengguna.
   - **Moda Inline Form Component:** Dapat disematkan langsung di dalam formulir spesifik (`<Toast inline={true} message={statusMsg} type="error" />`).

### B. Matriks Token Semantik Toast (High-Contrast Daylight & Dark)
| Tipe Varian | Latar & Border (Daylight) | Latar & Border (Dark Mode) | Ikon & Lencana | Durasi Standar |
| :--- | :--- | :--- | :--- | :--- |
| **`success`** | `bg-emerald-50/95 border-emerald-500 text-emerald-950` | `bg-[#06291c]/95 border-emerald-500 text-emerald-50` | `✓` Bulatan Emerald | 4.5 detik |
| **`error`** | `bg-rose-50/95 border-rose-500 text-rose-950` | `bg-rose-950/95 border-rose-500 text-rose-50` | `✕` Bulatan Rose | 6.5 detik |
| **`warning`** | `bg-amber-50/95 border-amber-500 text-amber-950` | `bg-amber-950/95 border-amber-500 text-amber-50` | `⚠` Bulatan Amber | 5.0 detik |
| **`info`** | `bg-sky-50/95 border-sky-500 text-sky-950` | `bg-sky-950/95 border-sky-500 text-sky-50` | `ℹ` Bulatan Sky | 4.0 detik |

### C. Standar Ergonomi Baby Boomer UX pada Toast
- **Penempatan Tengah Atas (Top-Center):** Memastikan notifikasi terbaca seketika di ponsel maupun monitor lebar tanpa terlewatkan oleh lansia (*tunnel vision mitigation*).
- **Ukuran Teks & Tipografi:** Judul tebal berukuran `text-xs sm:text-sm font-bold`, badan teks `text-xs font-medium leading-relaxed` berkontras tinggi (WCAG AAA).
- **Target Sentuh Tombol Tutup:** Tombol silang `✕` memiliki bidang sentuh minimal **44px × 44px** (`min-h-[44px] min-w-[44px]`) untuk kenyamanan jemari lansia.
- **Aksesibilitas (a11y):** Dilengkapi atribut `role="alert"`, `aria-live="polite"`, dan `aria-label="Tutup notifikasi"`.

### D. UX Simpan Langsung Pembaruan Data (Multi-Step Direct Save Affordance)
Saat mengedit entitas data yang telah ada:
1. **Banner Mode Edit di Sticky Header:** Menampilkan banner informatif di dalam `{#snippet headerTabs()}` dengan tombol aksi cepat `[💾 Simpan Cepat]` yang dapat ditekan kapan saja.
2. **Tombol "Simpan Perubahan" di Setiap Tahap:** Tombol aksi `[💾 Simpan Data]` disediakan di bilah aksi bawah setiap tahap formulir modal, mengeliminasi keharusan pengguna mengklik "Lanjut" berulang kali hingga langkah terakhir hanya untuk menyimpan perubahan.

---

## 11. STANDAR TIPOGRAFI SKALA REM & LAYOUT PROPORSIONAL ENTERPRISE (v6.31.0)

### A. Root Rem Scaling (14px Base)
Pada [`frontend/src/routes/layout.css`](../frontend/src/routes/layout.css):
```css
:root {
  font-size: 14px;
}
```
Penetapan basis `14px` memastikan kalkulasi satuan `rem` Tailwind CSS menghasilkan dimensi teks, padding, margin, dan kelengkungan yang seimbang (*sleek enterprise look*) tanpa komponen terlihat membengkak atau kaku.

### B. Matriks Hierarki Tipografi & Spacing Terstandarisasi
| Level Elemen | Ukuran Font Tailwind | Nilai Komputasi Pixel | Penggunaan |
| :--- | :--- | :--- | :--- |
| **Page Title** | `text-lg sm:text-xl font-bold` | `18px / 20px` | Judul utama halaman dashboard & admin |
| **Card Header** | `text-sm sm:text-base font-bold` | `14px / 16px` | Judul panel kartu, modal title |
| **Body / Input** | `text-base sm:text-xs` | `16px mobile / 12px desktop` | Label form, input teks, isi tabel data |
| **Secondary / Meta** | `text-[10px] sm:text-[11px]` | `10px / 11px` | IP address, token version, subtitle panduan |
| **Stat Numbers** | `text-xl font-extrabold font-mono` | `20px` | Angka metrik analitik & telemetri |

### C. Matriks Kelengkungan (Border Radius) & Padding Kontainer
| Komponen UI | Kelas Kelengkungan (Radius) | Kelas Padding | Catatan Ergonomi |
| :--- | :--- | :--- | :--- |
| **Outer Viewport Container** | `max-w-6xl` | `p-3 sm:p-5 space-y-4` | Mencegah margin samping terlalu tebal di desktop |
| **Primary Hub / Tab Card** | `rounded-2xl` | `p-4 sm:p-5 shadow-xs` | Container utama panel modul |
| **Sub-Cards & Data Tables** | `rounded-xl` | `p-3 sm:p-3.5 border` | Border 1px tegas |
| **Form Inputs & Selects** | `rounded-xl` | `px-3.5 py-2.5 min-h-[44px]` | Nyaman disentuh di mobile ($\ge 44$px), anti auto-zoom |
| **Top Navigation Bar** | `h-13 sm:h-14` | `px-3 sm:px-4` | Ketinggian presisi 52–56px |
| **Modal Container** | `rounded-xl sm:rounded-2xl` | `px-4 py-2.5 sm:px-5 sm:py-3` | Header dan footer padat |

---

## 12. STANDAR AFFORDANCE TOMBOL FISIK & HARMONISASI WARNA (v6.31.0)

Untuk mencegah *flat text syndrome* (di mana elemen yang dapat diklik tampak seperti teks biasa atau label statis), seluruh tombol interaktif wajib memenuhi kriteria:

### A. 4 Pilar Affordance Tombol Fisik
1. **Batas Geometri Tegas (Explicit Borders):** Setiap tombol wajib memiliki garis batas visual 1px tegas (`border border-slate-300 dark:border-slate-700`).
2. **Solid Contrast Fill:** Dilarang menggunakan latar transparan tanpa batas untuk tombol tindakan penting.
3. **Elevasi Bayangan Taktil (Tactile Elevation):** Tombol memiliki bayangan fisik halus (`shadow-xs` / `shadow-md`).
4. **Respon Interaksi Fisik (Physical Press Feedback):** Efek kompresi saat diklik: `active:scale-[0.98]` atau `active:scale-95`, dengan transisi halus `transition-all duration-150` dan kursor `cursor-pointer`.

### B. Segmented Controls & Pill Buttons
- Navigasi tab, filter sesi, dan selector status menggunakan kontainer bertray (`tray`) dengan tombol segmen yang memiliki batas pembeda nyata antara opsi terpilih (*active*) dan belum dipilih (*inactive*).

---

## 13. STANDAR ERGONOMI CENTRALIZED DASHBOARD & TELEMETRY MONITORING (v6.31.0)

### A. Segmented Subtab Navigation (`/dashboard`)
- **Pill Container:** Kontainer `p-1 bg-slate-100 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 flex flex-wrap gap-1`.
- **Active State:** `bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold shadow-xs border border-slate-300 dark:border-slate-700`.
- **Inactive State:** `text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-white/60 dark:hover:bg-slate-800/60`.
- **Responsive Stacking:** Di mobile tombol subtab wajib menggunakan pembungkusan responsif multi-baris (`flex flex-wrap gap-1`) agar seluruh tab langsung terlihat jelas tanpa scroll horizontal.

### B. Visualisasi Progres & Metrik Performa (Dual/Triple Bar Trend Charts)
- Kartu metrik analitik berdampingan (misal: Sesi Aktif vs Perangkat Terdaftar, P99 Latensi vs SLO).
- Visualisasi batang dinamis berbasis SVG vektor dengan skala tinggi adaptif berbasis nilai maksimum data riil tanpa dependensi npm eksternal.

---

## 14. STANDAR TABEL DATA MASTER & ERGONOMI BABY BOOMER (v6.31.0)

Untuk menjamin konsistensi pengalaman pengguna (*user experience*) dan kenyamanan bagi operator senior (*baby boomer*):

### A. Komponen Header Bar Standar Data Master
- **Latar & Batas:** `bg-white/80 dark:bg-slate-900/80 p-3.5 sm:p-4 rounded-xl border border-slate-200 dark:border-slate-800 backdrop-blur-xl shadow-xs`.
- **Indikator Status Bercahaya:** `<span class="w-2.5 h-2.5 rounded-full bg-cyan-400 shadow-[0_0_8px_#22d3ee]"></span>` di samping judul modul.
- **Tombol Tambah Utama:** `px-4 py-2 min-h-[44px] bg-indigo-600 hover:bg-indigo-500 text-white border border-indigo-400 rounded-xl text-xs font-bold shadow-md transition flex items-center gap-1.5 cursor-pointer active:scale-[0.98]`.
- **Zero Title Redundancy:** Menyembunyikan judul ganda di dalam komponen anak jika halaman pembungkus (*parent route*) telah memiliki judul halaman.

### B. Filter & Search Controls Grid
- Grid responsif (`flex flex-col sm:flex-row gap-2.5`).
- Input & Select: `px-3.5 py-2 min-h-[44px] text-base sm:text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white`.

### C. Tipografi Tabel & Paginasi Client-Side
- **Thead:** Monospace uppercase `text-[10px] sm:text-[11px] font-mono text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900`.
- **Tbody Rows:** Transisi hover lembut `hover:bg-slate-50 dark:hover:bg-slate-900/50 transition`.
- **Paginasi Bar:** Ditempatkan di bagian footer tabel dengan tombol navigasi `Prev` dan `Next` ber-touch target $\ge 44$px (`aria-label` eksplisit).

### D. Aksesibilitas Baby Boomer & Anti-Truncation
- **Target Sentuh & Input Lega:** Input dan kontrol interaktif wajib memiliki tinggi minimal 44px (`min-h-[44px]`), garis batas tegas `border border-slate-300 dark:border-slate-700`, dan ukuran font nyaman dibaca `text-base sm:text-xs`.
- **Callout Guidance Berikon Emoji:** Setiap formulir modal dilengkapi kotak petunjuk ramah pengguna senior dengan ikon emoji eksplisit (💡 untuk petunjuk).
- **Badge Status Penanda:** Label form dilengkapi badge eksplisit `<span class="text-xs font-bold text-rose-600 bg-rose-50 dark:bg-rose-950/60 px-2 py-0.5 rounded border border-rose-200 dark:border-rose-900">* Wajib Diisi</span>` atau `(Boleh Kosong)`.
- **Tanpa Pemotongan Teks (Anti-Truncation):** Menghapus kelas pemotongan `truncate` pada label penting di dialog modal, menggunakan `break-words whitespace-normal leading-tight`.
- **Lokalisasi Format Tanggal Indonesia:** Seluruh tanggal ditampilkan menggunakan format standar Indonesia melalui helper `formatIndoDate()`.

---

## 15. STANDAR GRAFIK VEKTOR SVG & RENDER OFF-SCREEN (v6.31.0)

### A. Hardware-Accelerated Native SVG Charts
- **Tanpa Library Eksternal:** Seluruh visualisasi tren dan telemetri wajib menggunakan elemen vektor native `<svg viewBox="0 0 {width} {height}" role="img">`.
- **Keunggulan Ergonomi:** Tajam sempurna pada semua layar Retina/High-DPI, rendering akselerasi GPU tanpa relayout thrashing, serta 0 KB beban dependensi npm.

### B. Off-Screen CSS Content Visibility (.cv-auto)
- **Engine Optimization:** Menetapkan utilitas `.cv-auto`, `.cv-auto-row` (`contain-intrinsic-size: 0 52px`), dan `.cv-auto-card` (`contain-intrinsic-size: 0 140px`) berbasis `content-visibility: auto`.
- **Dampak Performa:** Browser engine melewati kalkulasi gaya dan tata letak untuk elemen tabel yang berada di luar batas pandang layar (*viewport*), memangkas waktu initial paint hingga > 60%.

---

## 16. PANDUAN ERGONOMI & DENSITAS INFORMASI SENIOR (BABY BOOMER & ENTERPRISE ACCESSIBILITY)

1. **Ketajaman & Kepadatan Visual (High Density, High Legibility):**
   - Border bidang isian form dan tombol menggunakan border tegas 1px berdaya affordance tinggi (`border border-slate-300 dark:border-slate-700`) dengan rasio kontras WCAG AAA.
   - Touch/click target memenuhi standar aksesibilitas minimum `44px × 44px`.
2. **Viewport Safeguard Modal Dialog:**
   - Seluruh modal formulir wajib memiliki batasan tinggi `max-h-[calc(100dvh-1.5rem)] sm:max-h-[calc(100dvh-3rem)]`.
   - Header dan Footer tombol aksi wajib bersifat *pinned/sticky*, sedangkan area input data di-scroll secara independen (`overflow-y-auto`).
3. **Zero Silent Failure & Reactive Feedback:**
   - Setiap mutasi wajib menampilkan 3 siklus:
     1. *Busy State:* Tombol disabled + indikator loading/spinner.
     2. *Success Feedback:* Floating Toast banner di posisi atas tengah.
     3. *Error Safeguard:* Pesan kesalahan kontekstual yang spesifik via toast atau inline tanpa *silent catch*.
4. **Pola Multi-Item Assignment (`MultiItemPicker.svelte`):**
   - Menggunakan pencarian instan di memori browser (`$derived.by()`) tanpa request API berulang.
   - Menampilkan badge counter real-time (contoh: `2 / 5 dipilih`).
   - Menyediakan tombol cepat *Pilih Semua* dan *Hapus Pilihan*.
   - Item yang dinonaktifkan wajib menampilkan badge alasan disabilitas yang gamblang (contoh: "Sudah Digunakan").

---

## 17. STANDAR KEAMANAN ENTERPRISE (OWASP TOP 10 & ISO 27001) & SEO GOOGLE 2026

### A. Enterprise Security Headers (OWASP Top 10 & ISO 27001)
Setiap respons API backend dan root gateway wajib menyematkan header keamanan standar:
- `X-Content-Type-Options: nosniff`: Mencegah MIME sniffing exploit.
- `X-Frame-Options: SAMEORIGIN`: Mencegah serangan clickjacking/framing.
- `X-XSS-Protection: 1; mode=block`: Menangkal serangan Cross-Site Scripting reflektif.
- `Referrer-Policy: strict-origin-when-cross-origin`: Menjaga kerahasiaan URL rujukan.
- `Permissions-Policy: camera=(), microphone=(), geolocation=()`: Membatasi akses sensor perangkat keras tanpa izin.
- `Strict-Transport-Security`: HSTS otomatis pada koneksi HTTPS produksi (`max-age=31536000; includeSubDomains`).

### B. Google SEO Core Algorithm Standards 2026
1. **Bahasa Kanonikal & Viewport:** `<html lang="id">` dan `viewport-fit=cover` pada `app.html`.
2. **Robots Directives:**
   - Landing page & Login: `index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1`.
   - Modul internal (`/dashboard`, `/admin/*`, `/api/*`): `noindex, nofollow` untuk privasi audit dan proteksi crawl budget.
3. **Structured Data JSON-LD:** Schema.org `WebApplication` dengan rincian fitur dan lisensi enterprise.
4. **Rich Social Suite:** Open Graph (`og:type`, `og:site_name`, `og:locale`, `og:image`) & Twitter Card (`summary_large_image`).
5. **Hierarchy Heading Strict:** 1 tag `<h1>` per halaman publik, diikuti `<h2>`, `<h3>` hierarkis.
6. **Zero Nested `<main>` Violation:** Struktur layout menjamin tidak ada nesting `<main>` di dalam `<main>`.
7. **W3C Sitemap Compliant:** Berkas `sitemap.xml` XML compliant berstandar W3C/Sitemaps.org dengan metadata `lastmod`, `changefreq`, dan `priority`.

---

## 18. MANDAT MEKANISME CARD & KONTROL BARIS PER HALAMAN (SEMUA TABEL DATA)

### A. Mobile Auto-Card Stack & Desktop Table
1. **Dual Representation:** Seluruh tabel data (`DataTable.svelte` dan tabel kustom) WAJIB mendukung mekanisme kartu:
   - Pada layar mobile (`< 768px` / `md:hidden`), baris data otomatis dirender sebagai tumpukan kartu (*card stack*) yang terstruktur, padat, dan rapi.
   - Pada layar laptop/desktop (`>= 768px` / `md:table`), data ditampilkan dalam format tabular penuh bergaris batas 1px (*hairline border*) dengan hover transisi lembut.
2. **Fallback Card Rendering:** Jika komponen pemanggil tidak menyediakan slot kustom `#snippet card(item)`, komponen tabel wajib menyediakan kartu fallback otomatis yang mengekstrak kolom utama, status, dan tombol aksi tanpa merusak tata letak mobile.

### B. Kontrol Interaktif Baris per Halaman (`perPage` Selector)
1. **Pilihan Kepadatan Fleksibel:** Setiap tabel data WAJIB menyediakan pemilih (*dropdown select*) jumlah entri per halaman interaktif di bilah paginasi:
   - Opsi standar: **10 baris**, **25 baris**, **50 baris**, dan **100 baris**.
2. **Reaktivitas Instan:** Ketika pengguna mengubah jumlah baris per halaman, halaman aktif otomatis diatur ulang ke halaman 1 (`currentPage = 1`) dan tabel merender ulang seketika di memori browser tanpa reload halaman.

---

## 19. MANDAT RUANG BERNAPAS VISUAL & ANTI-SESAK (HP & LAPTOP)

### A. Prinsip Anti-Sesak & Spacious Breathing Room
1. **Eliminasi Tampilan Sesak / Berdesakan:**
   - Dilarang menjejalkan terlalu banyak kartu, teks, tombol aksi, atau metrik dalam satu baris yang mengakibatkan konten terhimpit atau teks saling bertumpuk (*cramped/congested UI*).
   - Gunakan spasi vertikal proporsional (`space-y-4` atau `space-y-6`), padding dalam kartu yang pas (`p-3 sm:p-4`), dan margin antar-seksi yang memberi jeda visual yang nyaman bagi mata.
2. **Pembatasan Lebar Maksimal Kontainer (Max-Width Container):**
   - Pada layar laptop dan layar monitor lebar, konten dashboard dan formulir dibatasi dengan kontainer terukur (`max-w-7xl` atau `max-w-6xl` dengan `mx-auto`) untuk mencegah mata lelah membaca baris teks yang membentang terlalu panjang.
3. **Ergonomi Navigasi Mobile 0-Scroll (Floating Thumb Dock):**
   - Pada layar ponsel, hindari footer statis tebal yang memotong permanen 20% area viewport.
   - Gunakan bilah navigasi melayang (*Floating Thumb Dock*) di tengah bawah (`fixed bottom-3.5 left-1/2 -translate-x-1/2 z-30 md:hidden`) yang kompak dan tidak menutupi isi data.
   - Pastikan area scroll `<main>` memiliki safe padding dasar minimal `pb-16 sm:pb-6` agar seluruh baris kartu data terbawah dapat digulir dan dibaca penuh tanpa terhalang dock.

---

## 20. MANDAT VISIBILITAS & KETERBACAAN TAB PANE (ZERO HIDDEN TABS VIA RESPONSIVE WRAP)

### A. Eliminasi Tab Terpotong di Balik Scroll Horizontal
1. **Masalah Desain Konvensional:**
   - Bilah tab navigasi yang menggunakan `overflow-x-auto whitespace-nowrap` sering kali menyembunyikan tab ke-5 dan ke-6 di sisi kanan layar laptop beresolusi 1366x768 atau di layar ponsel, sehingga pengguna tidak menyadari adanya menu tab tambahan kecuali menggeser ke kanan.
2. **Mandat Multi-Line Responsive Wrap (Untuk Tab <= 6):**
   - Untuk kelompok tab dengan jumlah moderat (6 tab atau kurang) dan panjang kata wajar, DILARANG KERAS menyembunyikan tab dengan scroll horizontal tersembunyi.
   - Wajib menggunakan pembungkusan responsif multi-baris: `flex flex-wrap items-center gap-1.5` atau layout grid adaptif (`grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-1.5`).
   - Seluruh tab navigasi wajib langsung tampak dan dapat diakses dengan sekali pandang (100% visible) di layar ponsel maupun laptop.
3. **Keterbacaan & Affordance:**
   - Setiap tab wajib memiliki kontras warna tegas saat aktif, border jelas saat inaktif, dan target sentuh minimal 36px–40px agar ramah sentuhan jari di HP.

---

## 21. MANDAT MINIMALISME ELEGAN, TIPOGRAFI TERBACA, NEGATIVE SPACE, & AKSESIBILITAS UNIVERSAL

### A. Filosofi Reduksionisme Fungsional (Dieter Rams & Mies van der Rohe)
1. **Weniger, aber besser (Kurang, tapi lebih baik):**
   - Mengeliminasi ornamen non-fungsional, bayangan tebal yang berlebihan, atau kontur garis berulang-ulang (*nested boxes*).
   - Keindahan antarmuka lahir dari fungsi yang jelas, ketepatan spasi, dan ketenangan visual (*visual serenity*).
2. **Estetika Internasional (Swiss International Style):**
   - Menggunakan sistem grid matematis yang harmonis.
   - Perataan kiri alami (*flush-left, ragged-right*) untuk kenyamanan ritme mata saat memindai teks panjang.

### B. Keterbacaan Tipografi & Hierarki Visual (Robert Bringhurst & Ellen Lupton)
1. **Panjang Baris Ideal (*The Optimal Measure*):**
   - Paragraf, deskripsi, dan form input dibatasi antara 45 hingga 75 karakter per baris (CPL).
   - Teks DILARANG dibiarkan mengalir selebar viewport monitor desktop/laptop karena memicu kelelahan melacak baris berikutnya (*tracking fatigue*).
2. **Karakter Tabular & Data Teknis:**
   - Seluruh data tabular, angka keuangan, timestamp, UUID, dan hash teknis WAJIB menggunakan `font-mono` untuk menjamin perataan vertikal yang mudah dipindai mata sekilas (*vertical column scanability*).
3. **Skala Modular:**
   - Gunakan diferensiasi kontras bobot (*font-bold*) dan warna teks terukur (`text-slate-900 dark:text-white` untuk judul, `text-slate-600 dark:text-slate-400` untuk body, `text-slate-400 dark:text-slate-500` untuk teks bantu).

### C. Negative Space & Rasio Data-ke-Tinta (Edward Tufte & Refactoring UI)
1. **Ruang Bernapas sebagai Elemen Struktural:**
   - *Whitespace* bukanlah ruang kosong yang sia-sia, melainkan pemisah struktural primer.
   - Elemen yang berhubungan dikelompokkan berdasarkan kedekatan jarak (*Gestalt Law of Proximity*), bukan dengan membungkus setiap elemen ke dalam kotak bergaris tebal.
2. **Garis Batas Halus & Bayangan Mikro:**
   - Gunakan garis pembatas 1px halus (*hairline border* `border-slate-200/80 dark:border-slate-800/80`) dan bayangan mikro `shadow-2xs`.

### D. Batas Kontainer Terkendali (Restrained Layout Ceilings)
1. **Pencegahan Tampilan Melebar Liar:**
   - Konten halaman DILARANG dibiarkan memenuhi seluruh lebar layar tanpa batas pada monitor ultrawide/2K/4K.
   - Batas kontainer: `max-w-3xl` / `max-w-4xl` untuk membaca dan formulir; `max-w-6xl` / `max-w-7xl` untuk dasbor analitik dan tabel data dengan margin terpusat `mx-auto px-4 sm:px-6 lg:px-8`.

### E. Aksesibilitas Universal (WCAG 2.2 AAA & Living Design Systems)
1. **Rasio Kontras Warna:** Minimal 4.5:1 untuk teks normal (AA) dan diutamakan 7:1 (AAA).
2. **Target Sentuh:** Minimal $44 \times 44\text{px}$ pada tombol utama dan aksi mobile; minimal $24 \times 24\text{px}$ pada tombol mikro tabel padat.
3. **Cincin Fokus Keyboard:** Wajib memiliki indikator fokus keyboard yang kontras dan jelas (`focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:outline-none`).

---

## 22. STANDAR DESAIN MASTERPIECE, KEASLIAN KARYA (HUMAN-CRAFTED AUTHENTICITY), & KEBIJAKAN ANTI-AI SLOP

### A. Eliminasi Penuh Klise Visual AI (Zero AI Design Clichés)
1. **Latar Belakang & Gradasi Murahan:**
   - Dilarang menggunakan gradient ungu-pink neon (`from-indigo-500 via-purple-500 to-pink-500` atau `from-cyan-400 to-blue-600`) pada background hero dan tombol utama.
   - Dilarang memasang lingkaran ornamen kabur (*blurred glow blobs* `blur-3xl opacity-30`) yang melayang acak tanpa fungsi struktural.
2. **Glassmorphism Berlebihan & Keterbacaan:**
   - Efek frosted glass atau backdrop-blur hanya diizinkan untuk Floating Thumb Dock mobile atau bilah sticky header dengan transparansi terukur (`bg-white/80 dark:bg-slate-900/80 backdrop-blur-md`).
   - Dilarang membungkus seluruh konten kartu utama dalam frosted glass buram tebal yang membuat teks kehilangan kontras tajam.
3. **Pemanis Dekoratif Artifisial:**
   - Dilarang menempatkan ikon bintang/sparkle atau ornamen dekoratif AI generatif yang tidak membawa makna fungsi nyata bagi tugas pengguna.

### B. Keaslian Karya & Human-Crafted Aesthetic
1. **Palet Tonal Teredam Bermartabat (*Dignified Muted Tonal Palette*):**
   - Utamakan monokrom elegan (Slate/Stone/Obsidian) dengan saturasi tenang yang dikombinasikan dengan tepat 1 warna aksen fungsional berkarakter (Emerald tajam di Yayasan PQTM, Indigo/Emerald di Hyperion Vault).
2. **Ritme Tipografi Berbobot (*Editorial Typographic Rhythm*):**
   - Menggabungkan hirarki heading tegas, label mikro terukur, dan `font-mono` untuk seluruh angka, tanggal, dan data teknis.
3. **Tekstur Presisi:**
   - Mengandalkan hairline border 1px yang renyah (`border border-slate-200/80 dark:border-slate-800/80`), micro shadows `shadow-2xs`, dan negative space yang lapang.

### C. Harmoni Fungsionalitas & Zero Superfluous Elements (Ockham's Razor UI)
1. Setiap elemen antarmuka wajib dapat mempertanggungjawabkan keberadaannya (*purpose-driven UI*).
2. Elemen visual yang tidak membantu pengguna memahami informasi, menyelesaikan tugas, atau bernavigasi harus dihapus demi ketenangan kognitif (*visual serenity*).

---

## 23. MANDAT EKSPOR CSV FRONT-END CLIENT-SIDE PADA SELURUH TABEL DATA (ZERO ROUND-TRIP)

### A. Prinsip Zero Round-Trip & Kinerja Ekspor Instan
1. **Ekspor In-Memory Peramban (< 5ms):**
   - Seluruh tabel data yang disajikan pada antarmuka Hyperion Vault dan aplikasi turunan (Users, Sessions, Audit, System, dan modul kustom) WAJIB menyediakan kontrol tombol **"Ekspor CSV"** langsung dari memori browser.
   - Dilarang memicu query ulang atau request berlebih ke server backend untuk data yang telah ter-load di state/store frontend (*zero round-trip*).
2. **Kepatuhan Format Internasional:**
   - **UTF-8 Byte Order Mark (BOM: `\uFEFF`):** Disematkan di awal stream berkas agar Microsoft Excel (Windows & macOS) dan Google Sheets langsung mengenali set karakter lokal dan aksara tanpa *mojibake*.
   - **Sanitasi Sel & Wrapping:** Seluruh data teks dibungkus tanda kutip dua (`"..."`) dan karakter kutip internal di-escape menjadi ganda (`""`).
   - **Proteksi Formula Injection CWE-1236:** Seluruh sel yang diawali simbol matematika/kalkulasi (`=`, `+`, `-`, `@`) otomatis ditambahkan prefix petik tunggal (`'`).
   - **Penamaan Semantis Berstempel Tanggal:** Berkas unduhan otomatis dinamai dengan format `[nama_tabel]_[YYYY-MM-DD].csv`.
   - **SSoT Reusable Implementation:** Didukung terpusat oleh utility kanonikal [`csvExport.js`](../frontend/src/lib/utils/csvExport.js) dan terintegrasi otomatis di [`DataTable.svelte`](../frontend/src/lib/components/ui/DataTable.svelte).

---

## 24. STANDAR SAINS WARNA 60-30-10, KONTRAS PERSEPSI APCA & WCAG 2.2 AAA, SERTA ARSITEKTUR KOREOGRAFI MOTION GSAP

### A. Sains Ruang Warna OKLCH, Kaidah Emas 60-30-10, & Interaksi Tonal Albers-Itten
1. **Keunggulan Ruang Warna OKLCH (*W3C CSS Color 4 / Björn Ottosson*):**
   - Menggantikan ruang warna sRGB/HSL linier yang memiliki distorsi luminansi persepsi manusia (warna kuning HSL tampak 3x lebih silau daripada biru meski *lightness*-nya sama).
   - OKLCH memisahkan $L$ (*perceptual lightness*), $C$ (*chroma/saturation*), dan $h$ (*hue angle*), menjamin setiap variasi kontras antar-status memiliki bobot optik retina yang setara dan presisi di layar Daylight maupun Dark Obsidian.
2. **Kaidah Emas Proporsi Distribusi Warna 60-30-10:**
   - **60% Dominan (Kanvas Utama & Ruang Negatif):** Latar belakang netral (`bg-slate-50` / `bg-slate-950`). Menjadi fondasi ketenangan kognitif (*visual serenity*).
   - **30% Struktural (Permukaan & Kartu):** Komponen kartu, panel data, kontainer modal, dan hairline 1px border (`bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800/80`), serta teks pendukung berbobot menengah (`text-slate-600 dark:text-slate-400`).
   - **10% Aksen & Intensi (Action & Attention Anchors):** Tombol CTA primer, status badge aktif, fokus interaktif, dan progres bar. Tidak boleh lebih dari 10% agar tidak menimbulkan kejenuhan visual (*visual fatigue*).
3. **Prinsip Kontras Simultan Josef Albers & Johannes Itten:**
   - Warna aksen tidak boleh bertabrakan dengan warna saturasi tinggi lain. Satu aksen berkarakter (Indigo/Cyan untuk Hyperion Engine, Emerald untuk Notaris/PPAT, Forest Green untuk Pesantren) wajib dikelilingi oleh palet netral teredam (*dignified muted ground*) agar perhatian pengguna terfokus seketika.

### B. Standar Kontras Persepsi APCA (WCAG 3.0) & Kepatuhan WCAG 2.2 AAA
1. **Accessible Perceptual Contrast Algorithm (APCA) — Standar Baru W3C Silver/WCAG 3.0:**
   - Berbeda dari rasio matematis statis WCAG 2 (yang sering memberi nilai keliru pada latar gelap), APCA memperhitungkan frekuensi spasial font (ketebalan/weight dan ukuran teks) terhadap luminansi latar belakang.
   - **Ambang Batas Lightness Contrast ($L_c$):**
     - **Teks Utama / Body Paragraph ($14\text{px} - 16\text{px}$ regular):** Wajib $L_c \ge 75$ (setara WCAG AAA 7:1).
     - **Heading / Teks Besar ($\ge 20\text{px}$ bold):** Wajib $L_c \ge 60$ (setara WCAG AA 4.5:1).
     - **Batas Kontrol Interaktif & Non-Text UI (Border input, switch, checkbox):** Wajib $L_c \ge 45$ (WCAG SC 1.4.11 $\ge 3:1$).
     - **Placeholder & Teks Muted (Non-kritis):** Wajib $L_c \ge 30$.
2. **Eliminasi Efek '1+1=3 Noise' (Edward Tufte):**
   - Dilarang membuat border pekat tebal di sekitar teks berbobot tebal yang menyebabkan ilusi optik garis getar (*visual vibration*). Garis batas wajib menggunakan hairline halus 1px bertransparansi lembut (`border-slate-200/80 dark:border-slate-800/80`).

### C. Arsitektur Koreografi Motion Design GSAP & Svelte 5 (Nested Motion Discipline)
1. **Pemisahan Koreografi Makro (Elemen) vs Mikro (Teks):**
   - **Gerak Makro Kontainer (Kartu, Panel, Grid Bento):** Bergerak vertikal halus sejauh $20\text{px} - 30\text{px}$ dengan durasi $0.4\text{s} - 0.5\text{s}$, stagger $0.1\text{s} - 0.15\text{s}$.
   - **Gerak Mikro Tipografi (Judul, Subjudul, Paragraf):** Teks DILARANG dilempar dari jarak jauh ($> 20\text{px}$) karena merusak kenyamanan membaca. Teks bergerak vertikal mikro hanya $8\text{px} - 14\text{px}$, durasi $0.25\text{s} - 0.35\text{s}$, dengan stagger antar-baris/kata $0.04\text{s} - 0.08\text{s}$.
2. **Kurva Easing Alami (Natural Physics):**
   - Elemen Masuk (*Entrance*): Gunakan kurva perlambatan `power2.out`, `expo.out`, atau `cubic-bezier(0.16, 1, 0.3, 1)`.
   - Elemen Keluar (*Exit*): Gunakan kurva percepatan `power2.in` (keluar gesit tanpa menghambat operator).
   - Dilarang keras menggunakan transisi linear kaku atau efek memantul berlebihan (*elastic/bouncy slop*) pada antarmuka operasional enterprise.
3. **Plafon Anggaran Durasi (Motion Duration Budget):**
   - Micro-Interactions (Hover, Button Click, Active Dot): $100\text{ms} - 150\text{ms}$.
   - Component State (Accordion, Drawer, Modal, Tab Switch): $200\text{ms} - 300\text{ms}$.
   - Section Entrance & Pinned Scrub: $400\text{ms} - 600\text{ms}$.
   - Dilarang total durasi melebihi $800\text{ms}$ untuk interaksi sistem.
4. **Proteksi Memory Leak SPA Svelte 5:**
   - Seluruh instansiasi GSAP wajib dibungkus dalam `gsap.context((self) => { ... }, containerEl)`.
   - Wajib menyertakan *cleanup hook* pada siklus hidup Svelte: `return () => ctx.revert()`.
5. **Mandat Aksesibilitas Vestibular (WCAG 2.2 SC 2.3.3):**
   - Seluruh CSS dan JavaScript GSAP WAJIB mendeteksi preferensi pengguna:
     `const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;`
   - Jika bernilai `true`, animasi spasial langsung dilewati (*bypassed*) dan langsung menampilkan kondisi final instan (0ms).

### D. Typographic Scale Robert Bringhurst & Ritme Spasial 8-Point
1. **Batas Optimal Baris Teks (Measure):**
   - 45–75 karakter per baris (CPL). Hindari teks membentang tanpa pembatas container (`max-w-3xl` untuk baca, `max-w-7xl` untuk tabel dasbor).
2. **Tinggi Baris Proporsional (Proportional Line Height):**
   - Heading Display ($32\text{px} - 48\text{px}$): `leading-[1.15]` s/d `leading-[1.25]`.
   - Body & Paragraph ($14\text{px} - 16\text{px}$): `leading-[1.5]` s/d `leading-[1.65]`.
   - Tabel Finansial & Monospace ($11\text{px} - 13\text{px}$): `leading-none` atau `leading-tight`.
3. **Harmoni Grid 8-Point:**
   - Menggunakan kelipatan 4px/8px untuk seluruh properti spasial: `gap-1` (4px), `gap-2` (8px), `gap-3` (12px), `gap-4` (16px), `gap-6` (24px), `gap-8` (32px).

---

## 25. STANDAR SAINS SPASIAL, RITME GEOMETRIS, KESEIMBANGAN SIMETRI-ASIMETRI, DAN NEGATIVE SPACE

### A. Sains Jarak & Hukum Kedekatan Gestalt (Law of Proximity)
1. **Hukum Persepsi Spasial (Wertheimer & Koffka):**
   - Otak manusia secara otomatis mengelompokkan objek-objek visual berdasarkan jarak spasial relatifnya, bukan berdasarkan garis batas buatan (*borders*).
   - Elemen yang berhubungan erat secara fungsional WAJIB diletakkan lebih dekat satu sama lain daripada elemen yang berbeda kelompok.
2. **Kaidah Hirarki Jarak Rasio 1:3:6 (The Proximity Hierarchy):**
   - **Micro-Distance ($1\times$ — $4\text{px} - 8\text{px}$):** Jarak internal komponen atomik (ikon ke label teks, label input langsung ke form field, badge status ke teks indikator).
   - **Meso-Distance ($3\times$ — $16\text{px} - 24\text{px}$):** Jarak antar elemen sekelompok (jarak antar form field dalam 1 grup, jarak antar kartu dalam grid seksi).
   - **Macro-Distance ($6\times$ — $48\text{px} - 64\text{px}$):** Jarak pemisah antar seksi fungsional atau blok modul yang berbeda.

### B. Kesimetrisan Formal vs Ketidaksirmetrisan Fungsional Dinamis (Jan Tschichold)
1. **Simetri Aksial (Bilateral/Axial Symmetry):**
   - Diterapkan secara ketat pada dokumen cetak otentik dan instrumen hukum formal: Akta Notaris/PPAT, Buku Repertorium, Kuitansi/Invoice A4, dan Lembar Pengesahan. Menghadirkan kesan martabat, sakralitas hukum, dan stabilitas institusional.
2. **Ketidaksirmetrisan Dinamis (*Asymmetrical Dynamic Balance*):**
   - Diterapkan pada tata letak aplikasi modern dan landing page. Keseimbangan visual dicapai bukan dengan mencerminkan bentuk secara kaku (*static mirror*), melainkan melalui **Keseimbangan Bobot Visual (*Visual Weight Equilibrium*)**.
   - *Pola Split-View Asimetris:* Blok teks persuasif tebal bermassa optik padat (55%–60% lebar) diseimbangkan oleh mockup antarmuka interaktif yang lebih transparan dan berpendar (40%–45% lebar).
   - *Pola Bento Grid Asimetris:* Satu kartu primer lebar ganda (`col-span-2`) diseimbangkan oleh dua kartu mikro bertumpuk vertikal (`col-span-1`). Keduanya memiliki momen gaya gravitasi optik (*optical gravity*) yang seimbang.

### C. Negative Space sebagai Elemen Aktif & Eliminasi '1+1=3 Noise' (Emil Ruder & Edward Tufte)
1. **Ruang Bernapas Aktif (*Active Silence as Form* - Emil Ruder):**
   - Negative space (*whitespace*) bukan kekosongan pasif yang harus dipadatkan oleh ornamen. Whitespace adalah instrumen aktif yang memberikan kejelasan optik dan ketenangan visual (*visual serenity*).
2. **Eliminasi Efek '1+1=3 Noise' (Edward Tufte):**
   - Jangan memisahkan data dengan menumpuk border tebal berulang-ulang yang memicu getaran optik (*visual vibration*).
   - Gunakan negative space terukur untuk memisahkan panel data. Garis pembatas hanya boleh berupa hairline 1px bertransparansi lembut (`border-slate-200/80 dark:border-slate-800/80`).
   - Dilarang keras *nested container bloat* (kotak formulir di dalam kotak kartu di dalam kotak seksi).
3. **Pemberian Ruang Bernapas Psikologis (*Breathing Room* - Dieter Rams):**
   - Terapkan padding proporsional (`p-3` s/d `p-5`) dan margin otomatis terkontrol (`mx-auto`).

### D. Proporsi Ukuran Relasional & Skala Geometris (Golden Ratio & Root Rectangles)
1. **Skala Proporsi Ukuran Antar-Elemen:**
   - **Target Sentuh vs Ukuran Visual:** Tombol aksi utama minimal $44 \times 44\text{px}$ untuk jempol mobile (Apple HIG / WCAG 2.2 SC 2.5.8), tombol mikro data padat minimal $24 \times 24\text{px}$.
   - **Ikon vs Tipografi:** Ukuran ikon SVG atomik tidak boleh mendominasi teks (tinggi $14\text{px} - 18\text{px}$ untuk teks $12\text{px} - 14\text{px}$; tinggi $20\text{px} - 24\text{px}$ untuk judul).
2. **Skala Emas Arsitektural ($\phi \approx 1.618$) & Format Dinamis ($\sqrt{2} \approx 1.414$):**
   - Rasio 1 : 1.618 digunakan untuk proporsi perbandingan lebar konten utama terhadap sidebar pendukung (`62% : 38%`).
   - Rasio 1 : 1.414 (ISO 216) digunakan untuk seluruh template cetak dokumen fisik standar kenotariatan (A4/F4).

---

## 26. ARSITEKTUR ELEVASI PERMUKAAN 3-TIER (L0/L1/L2) & ELIMINASI PSEUDO-GLASSMORPHISM (ANTI-CAMOUFLAGE UI)

### A. Doktrin 3-Tier Surface Elevation
Untuk menjamin keterbacaan instan dan memisahkan antar-elemen antarmuka secara tegas tanpa mengorbankan estetika minimalis elegan, seluruh tata letak antarmuka wajib mematuhi matriks 3-Tier Surface Elevation:

1. **L0 - Canvas Ground (Dasar Kanvas Halaman):**
   - Menjadi fondasi visual paling bawah tempat seluruh kartu berlabuh.
   - *Daylight Mode:* `bg-slate-100/70` atau `bg-slate-100/80` (bukan putih bersih yang memicu hilangnya batas dengan kartu putih).
   - *Dark Obsidian Mode:* `bg-[#070b12]` atau `bg-[#090d16]` (void pekat yang bebas dari noda *OLED smearing*).

2. **L1 - Macro Surface (Kartu Kontainer Utama):**
   - Mewadahi modul fungsional: Kartu Metrik KPI, Panel Alur Kerja Pipeline/Kanban, Wadah Tabel Data, Formulir Pendaftaran, dan Bento Grid Containers.
   - *Daylight Mode:* Solid `bg-white` (atau `bg-white/95`) dengan pembatas presisi 1px `border border-slate-200/80` dan bayangan mikro `shadow-2xs`.
   - *Dark Obsidian Mode:* Solid berbobot `dark:bg-slate-900/90` dengan pembatas presisi 1px `dark:border-slate-800` dan bayangan mikro `shadow-2xs`.
   - DILARANG KERAS menggunakan opasitas rendah ekstrim seperti `bg-white/40` atau `dark:bg-white/[0.02]` di atas kanvas solid.

3. **L2 - Micro Surface (Elemen Tersemat di dalam Kartu Induk):**
   - Mewadahi elemen anak yang berada di dalam kontainer L1: Kartu berkas pipeline pada rel kolom, baris transaksi interaktif, item antrean prioritas, card view pada tabel mobile.
   - *Daylight Mode:* `bg-slate-50/70 hover:bg-white` atau `bg-white hover:bg-slate-50` (di atas rel kolom `bg-slate-100/50`), dengan `border border-slate-200` dan `shadow-2xs`.
   - *Dark Obsidian Mode:* `dark:bg-slate-950/70 hover:dark:bg-slate-800` atau `dark:bg-slate-900` (di atas rel kolom `dark:bg-slate-950/60`), dengan `border dark:border-slate-800/80` dan `shadow-2xs`.
   - DILARANG KERAS menggunakan token warna yang persis sama dengan kontainer induknya.

### B. Larangan Keras "Pseudo-Glassmorphism" di Atas Kanvas Polos
1. **Kegagalan Fisika Optik Glassmorphism:** Efek kaca blur (`backdrop-blur`) membutuhkan objek grafis bertekstur, wallpaper dinamis, atau gradasi kontras tinggi di belakangnya agar pembiasan (*refraction*) dapat terjadi. Di atas kanvas solid/flat, `backdrop-blur` tidak menghasilkan pembiasan apa pun, melainkan hanya bertindak sebagai transparansi kusam (*milky wash* di Light Mode, *dark void* di Dark Mode).
2. **Eliminasi Efek Bunglon (Anti-Visual Camouflage):** Penggunaan transparansi rendah di atas kanvas flat melenyapkan jangkar spasial (*spatial anchors*) pengguna, memicu ketidakpastian afordansi (*affordance ambiguity*), dan menyebabkan kelelahan mata (*visual fatigue*). Efek kaca HANYA diizinkan pada bilah mengambang di atas konten bergulir (*Header Navigasi Melayang* dan *Floating Thumb Dock*).

---

*Hyperion Vault Design Specification v6.36.0 — High Performance, Neo-Enterprise Surface Elevation, Zero Cognitive Overload.*
