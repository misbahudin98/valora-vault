# Enterprise Fullstack Architecture Master Specification (v6.31.0 - SemVer 2.0.0)

Dokumen ini adalah spesifikasi arsitektur teknis dan panduan implementasi langkah-demi-langkah berurutan (*deterministic step-by-step*) yang dirancang untuk Full Stack Engineer, junior programmer, dan agen AI otomatis. Mengadopsi standar **Semantic Versioning 2.0.0** (`MAJOR.MINOR.PATCH`). Sesuai benchmark stabilitas produksi `yayasan-pqtm` v6.32.0.

---

## 0. PETA KESINAMBUNGAN DOKUMEN & KERANGKA DIÁTAXIS
Dokumentasi teknis Hyperion Vault distrukturkan berlandaskan **Diátaxis Framework** (Tutorials, How-To Guides, Reference, Explanation) dan kepatuhan presisi terhadap kata kunci **RFC 2119**:

| Kuadran Diátaxis | Berkas | Peruntukan & Tujuan Utama | Standar RFC 2119 |
|---|---|---|---|
| **Reference** | [`../.agents/rules/universal_invariants.md`](../.agents/rules/universal_invariants.md) | **Binding Operational Rules:** 22 Golden Invariants sistem dan Definition of Done. | `MUST` dipatuhi AI & Dev |
| **Reference** | [`../llms.txt`](../llms.txt) | **Router Tugas AI & Token Saver:** Index cepat, 22 Golden Invariants, SOP pasca-clone & safe update. | `REQUIRED` untuk prompt AI |
| **Tutorials** | [`developer_quickstart_guide.md`](developer_quickstart_guide.md) | **Panduan Cepat Pengembang & Spawner:** Panduan praktis inisiasi, spawning proyek baru (~5s), dan alur SDD. | `HIGHLY RECOMMENDED` |
| **Tutorials** | [`../README.md`](../README.md) | **Pintu Masuk Human Developer:** Ikhtisar arsitektur, quickstart 1-klik, panduan update, ringkasan fitur. | `RECOMMENDED` bagi pemula |
| **Reference** | [`architecture.md`](architecture.md) | **Master Technical Blueprint:** SOP detail backend, auth, database, routing, FastCGI, pengujian. | `MUST` jadi acuan teknis |
| **Reference** | [`design.md`](design.md) | **Standar UI/UX & Desain:** SSoT visual (Daylight & Dark Obsidian), token warna, grid 8pt, WCAG AAA. | `SHALL` diikuti frontend |
| **Explanation** | [`prompt.feature.md`](prompt.feature.md) | **Autonomous Feature Engine:** Mesin orchestrator intake spesifikasi fitur (Track A), landing page & gaya (Track B), dan refactoring bergaransi zero-regression (Track C). | `SHOULD` memandu SDD |
| **Reference** | [`feature.md`](feature.md) | **Master Feature Index:** Ringkasan seluruh domain fitur + folder spesifikasi modular [`features/`](features/). | `REQUIRED` untuk katalog |
| **Tutorials** | [`local.inisiasi.md`](local.inisiasi.md) | **Runbook Deterministik Lokal:** Panduan instalasi langkah demi langkah dari nol untuk Laragon. | `MAY` dipakai setup awal |
| **How-To** | [`provisioning_sop.md`](provisioning_sop.md) | **SOP Provisioning Turunan:** Panduan kloning & kustomisasi starterkit ke repositori produksi baru. | `MUST` untuk turunan baru |
| **Explanation** | [`changes.md`](changes.md) | **Log Audit & Jejak Perubahan:** Histori akar masalah, audit teknis, dan evolusi dari v1.0.0 ke v6.32.0. | `SHALL` mencatat perubahan |
| **How-To** | [`version_sync.php`](version_sync.php) | **Automated SemVer Synchronizer:** Alat CLI sinkronisasi versi semantik dan update dokumentasi serentak. | `MUST` menjaga lockstep |

---

## BAGIAN 1: AUDIT PERFORMA & EVALUASI BEBAN SISTEM

### 1.1. Profil Beban & Akar Masalah Insiden
* **Workload:** PHP 8.3/8.4, Nginx, Laravel 13 (Sanctum), MySQL / SQLite, Svelte 5 (Pure JS Runes).
* **Infrastruktur Target:** Hostinger Shared Hosting (Batas koneksi simultan MySQL: 25) & Lingkungan Lokal Laragon Windows.
* **Profil Beban:** 15 – 25 Active Concurrent Users (CCU), 20 RPS Peak.
* **Insiden Sebelum Audit:** `1040 Too Many Connections` (MySQL Connection Exhaustion) pada 25 query simultan, Latensi P99 membengkak hingga 2.500 ms (2,5 detik).
* **Target SLO:** Ketersediaan 99.0% (Toleransi downtime maksimal 7.2 jam/bulan), Latensi P99 &lt; 150 ms, RPO = 24 Jam, RTO = 4 – 12 Jam.
* **Tim:** 1 Full Stack Engineer.

### 1.2. Benchmark Komparasi Driver Sesi
| Parameter Evaluasi | Driver: `database` (Memicu Crash) | Driver: `cookie` | Driver: `file` (**Rekomendasi Arsitektur**) |
|---|---|---|---|
| **Query MySQL / Request** | **2 Query** (1 SELECT read, 1 UPDATE write) | 0 Query | **0 Query** |
| **Koneksi MySQL Terpakai** | Menyandera slot pool koneksi | 0% | **0% (Bebas koneksi DB)** |
| **Latency Overhead** | 50 ms – 2.500 ms (saat table lock) | &lt; 1 ms | **0.2 ms – 0.8 ms (NVMe SSD I/O)** |
| **Batas Ukuran Data** | Tidak terbatas | Terbatas 4 KB (Cookie limit) | **Tidak terbatas** |
| **Dampak Crash** | **KRITIS**: Memicu Connection Exhaustion | Header bloat jika ada flash data | **ZERO DB IMPACT**; I/O terisolasi di disk |

### 1.3. Pembuktian Matematis SLO (Little's Law)
Formula Little's Law: $L = \lambda \times W$ (di mana $L$ = konkurensi koneksi, $\lambda$ = throughput permintaan per detik, $W$ = waktu tunggu).
* **Kondisi Sebelum Audit:** $\lambda = 20 \text{ RPS}$, $W = 2.5 \text{ s} \implies L = 20 \times 2.5 = 50 \text{ koneksi simultan}$ (Melebihi kuota 25 koneksi $\implies$ Server Crash).
* **Pasca Implementasi Arsitektur:**
  - `SESSION_DRIVER=file` $\implies$ Query sesi ke database = 0.
  - Svelte 5 static serving & client routing $\implies$ 60% traffic diselesaikan di frontend/cache.
  - Dynamic API traffic $\lambda_{\text{API}} = 8 \text{ RPS}$.
  - Waktu tahan koneksi database ($W_{\text{DB}}$) dipangkas menjadi $\le 8 \text{ ms}$ ($0.008 \text{ s}$) melalui composite indexing dan PDO timeout 2s.
  - **Konkurensi Database Baru:**
    $$L_{\text{DB}} = 8 \text{ RPS} \times 0.008 \text{ s} = 0.064 \text{ koneksi simultan}$$
  - **Kondisi Terburuk (Peak 20 RPS menyentuh DB):**
    $$L_{\text{DB-peak}} = 20 \text{ RPS} \times 0.008 \text{ s} = 0.16 \text{ koneksi simultan}$$
* **Kesimpulan:** Beban koneksi database hanya memakai **0.64% dari kapasitas 25 koneksi**. Latensi P99 terpangkas dari 2.500 ms menjadi **~65 ms** (jauh di bawah target SLO 150 ms).

### 1.4. FastCGI Dual-Worker Persisten (Windows Laragon)
* Di Windows OS, biner `php-cgi.exe` bersifat *single-threaded*. Nginx Laragon membagi beban via round-robin ke dua port: `127.0.0.1:9003` dan `127.0.0.1:9004`.
* Untuk mencegah worker mati otomatis setelah menangani request, wajib menyetel `$env:PHP_FCGI_MAX_REQUESTS="0"` sebelum meluncurkan proses.

### 1.5. Dekomposisi Sesi vs Registry Perangkat: Mengapa SESSION_DRIVER=file Tetap Digunakan?
* **Akar Masalah Driver Database:** Di Laravel, `SESSION_DRIVER=database` menulis dan membaca data sesi mentah pada *setiap request HTTP* (1 SELECT, 1 UPDATE). Pada batas 25 koneksi Hostinger, 20 RPS peak langsung melumpuhkan database (`1040 Too Many Connections`).
* **Pemisahan Tanggung Jawab (*Separation of Concerns*):**
  1. **Raw Session Engine (`SESSION_DRIVER=file`):** Menangani lifecycle request HTTP, cookie CSRF, token sesi, dan state otentikasi di disk NVMe lokal. Latensi I/O: 0.2 ms, **beban koneksi database: 0%**.
  2. **Device Registry (`user_devices`):** Tabel database khusus audit yang *hanya disentuh* saat event login (1 INSERT), logout (1 DELETE), dan pemantauan sesi admin (1 SELECT).
* **Evaluasi Keamanan & Best Practice:**
  - **Sangat Aman:** Kuki session HttpOnly terhindar dari pencurian XSS. Tampering dicegah via signature HMAC-SHA256 pada kuki `auth_jwt`.
  - **Paling Optimal:** Memberikan transparansi forensik multi-device (IP, User-Agent, Expiry, Revoke per Device) tanpa pernah menyandera *pool* koneksi database bersama.
* **Enterprise Session File Garbage Collector & Disk Pruner:**
  - **Akar Masalah Akumulasi:** Setiap request unauthenticated (seperti `/sanctum/csrf-cookie` dan pengujian unit) menghasilkan berkas sesi 40-karakter di `storage/framework/sessions/`. Mekanisme lotere GC bawaan Laravel (`[2, 100]`) kerap terlewati pada konteks API/testing, berisiko menghabiskan kuota inode shared hosting.
  - **Deteksi & Pembersihan Deterministik:** Metode `UserDevice::pruneDiskSessions($graceMinutes, $force)` memindai direktori disk, melindungi `.gitignore`, mencocokkan sesi aktif dengan registry `user_devices` secara $O(1)$, dan menghapus berkas orphan/kadaluarsa via `@unlink`. Berkas sesi berisikan payload `login_web_` yang tidak terdaftar di `user_devices` langsung dipangkas tanpa menunggu grace period.
  - **Artisan CLI & Automation:** Perintah `php artisan session:prune-files --grace=60` dijadwalkan via `Schedule::command(...)->hourly()` di `routes/console.php`. Terintegrasi pula pada safe updater `install.php update` dan endpoint `/api/v1/admin/sessions/cleanup`.
  - **Telemetri Real-Time:** Controller menyediakan `disk_sessions_count` dan statistik pemulihan ruang disk (`bytes_freed`) yang divisualisasikan pada antarmuka Manajemen Sesi.
* **Zero-Duplication Session Lifecycle (Login & Logout Determinism):**
  - **Akar Masalah Multiplikasi Berkas:** Registrasi ganda middleware sesi pada `bootstrap/app.php` memicu instansiasi sesi ganda pada setiap request stateful SPA. Saat login, `regenerate()` tanpa `$destroy=true` meninggalkan file tamu CSRF di disk. Saat logout, pemanggilan `regenerateToken()` memicu Laravel menulis file sesi tamu zombie baru saat response berakhir.
  - **Rekayasa Single Stateful Pipeline:** Menghapus duplikasi middleware sesi di `bootstrap/app.php`; pipeline tunggal `$middleware->statefulApi()` mengelola sesi secara efisien.
  - **Atomisitas Login:** Menggunakan `$request->session()->regenerate(true)` disertai `@unlink` fisik instan berkas sesi pre-login dari disk NVMe sebelum mendaftarkan entri di `user_devices`.
  - **Atomisitas Logout Tanpa Berkas Zombie:** Mengeliminasi regenerasi token saat logout; mendaftarkan `app()->terminating(...)` hook untuk menjamin pembersihan total berkas sesi aktif maupun berkas pasca-logout sebelum proses FastCGI selesai, dan menghapus seluruh 4 kuki otentikasi di peramban.

---

## BAGIAN 2: INVARIANSI SISTEM (GOLDEN RULES)

1. **Frontend Language Standard:** Pure JavaScript (ES2024+) dengan Svelte 5 Runes (`$state`, `$derived`, `$props`, `$effect`). Tidak menggunakan TypeScript guna menekan kompleksitas kompilasi.
2. **Zero DB Session & Cache:** `SESSION_DRIVER=file` dan `CACHE_STORE=file` absolut. Dilarang menyetel ke `database`.
3. **Database Strict Budget:** 
   - `PDO::ATTR_TIMEOUT => 2` (timeout 2 detik).
   - `PDO::ATTR_PERSISTENT => false` (putus koneksi segera setelah response selesai).
   - SQLite Concurrency WAL (`PRAGMA journal_mode = WAL;`) dan timeout `busy_timeout = 5000`.
4. **Dual-Layer Hybrid Authentication:**
   - Server Authority: Laravel Sanctum HttpOnly session cookie (`laravel_session` + `XSRF-TOKEN`).
   - Client Reactivity: Signed Tamper-Proof JWT Cookie (`auth_jwt`) ditandatangani via HMAC-SHA256 dari `APP_KEY` dan cookie peran ringan (`user_role`).
5. **Multi-Device, Device Tracking & Concurrency Control:**
   - Kuota perangkat: `AUTH_MAX_CONCURRENT_DEVICES=3`.
   - Registry Sesi Nyata: Tabel `user_devices` menyimpan sesi aktif (User ID, IP Address, User-Agent, Device, OS, Browser, Expiry). Tidak menampilkan user yang belum login.
   - Granular Revocation: Mendukung pencabutan per-device (`/sessions/{id}/revoke`), per-user (`/sessions/user/{id}/revoke`), dan global (`/sessions/revoke-all`).
   - Atomic Session Purge: Metode `UserDevice::purgeStaleSessions()` menghapus sesi kadaluarsa atau sesi ber-token lawas via single atomic SQL delete tanpa disk locking.
   - Stateless Invalidation: Kolom `token_version` pada database. Menaikkan nilai ini seketika membatalkan semua sesi perangkat aktif tanpa query scan session table.
6. **Separation of Concerns Admin:**
   - Halaman [Manajemen Pengguna](file:///D:/laragon/www/hyperion-vault/frontend/src/routes/admin/users/+page.svelte) terpisah dari [Manajemen Sesi](file:///D:/laragon/www/hyperion-vault/frontend/src/routes/admin/sessions/+page.svelte).
7. **Clean Minimalist Layout:**
   - Menu "Beranda" ditiadakan dari sidebar navigasi.
   - Tidak ada duplikasi profil pengguna atau tombol logout di topbar.
   - Vanity metrics (seperti badge statis FastCGI Online) dieliminasi dari topbar untuk visual bersih dan jujur.
   - Metrik pill SLO/P99 dihapus dari sidebar.
   - Sidebar disembunyikan sepenuhnya pada landing page (`/`) dan login (`/login`).
8. **Zero Nginx Hack:**
   - Menggunakan gateway perantara [index.php](file:///D:/laragon/www/hyperion-vault/index.php) pada root proyek. Menjaga auto-vhost Laragon (`http://hyperion-vault.test`) dan [phpMyAdmin](file:///D:/laragon/etc/nginx/alias/phpmyadmin.conf) tetap bekerja tanpa modifikasi konfigurasi Nginx.
9. **Brand Identity & AI Indexability:**
   - Favicon vektor SVG (`favicon.svg`) berpadu serasi dengan lambang lightning bolt sidebar.
   - Indeks agen AI komprehensif tersedia di [llms.txt](file:///D:/laragon/www/hyperion-vault/llms.txt) pada root proyek.
10. **Automated Diagnostic Installer & 1-Step Dependency Engine:**
   - Setup pasca-clone diotomatisasi penuh melalui [install.php](file:///D:/laragon/www/hyperion-vault/install.php) (Web & CLI) dengan verifikasi 13 parameter prasyarat.
   - Mendukung resolusi dependensi otomatis (`composer install` & `npm install`) jika belum terpasang, serta konfigurasi domain dan nama proyek dinamis berbasis nama direktori.
   - Lockfile `.installed` secara otomatis memblokir eksekusi ulang untuk melindungi database dan kunci enkripsi di production.
11. **9000+ Records Scalability & Memory Guard:**
   - Tabel `users` dan `user_devices` dilengkapi indeks B-Tree komposit (`[is_active, status]`, `[user_id, last_active_at]`, `name`, `created_at`, `expires_at`).
   - Seluruh endpoint query administratif menerapkan batas plafon aman (`limit 1000`) dan mendukung filter server-side (`search`, `status`) guna mencegah *memory exhaustion DoS* (>18MB) dan *payload bloat* (>2.5MB).
12. **Hybrid Auto-Detect & Safe Confirmation Deployment:**
    - Gateway `index.php` otomatis mendeteksi status instalasi dan me-redirect peramban ke `/install.php`.
    - Mengombinasikan tombol konfirmasi manual untuk mencegah penghapusan basis data bersama di *shared hosting*, serta opsi *headless* (`?auto=1` / CLI) untuk CI/CD pipeline.
13. **Semantic Versioning Standard (SemVer 2.0.0):**
    - Format versi sistem adalah `MAJOR.MINOR.PATCH` (`5.6.0`).
    - Diterapkan secara konsisten di `backend/composer.json`, `frontend/package.json`, konstanta `APP_VERSION` pada `install.php`, badge `AppSidebar.svelte`, dan landing page `+page.svelte`.
    - Peningkatan versi wajib mengikuti aturan: Bumping PATCH untuk bug fix/optimasi; MINOR untuk fitur baru non-breaking; MAJOR untuk perombakan arsitektur atau breaking API change.
14. **Browser-Only Instant Search & Client Chunking (Zero Database Connections on Search):**
    - Filtrasi dan pencarian data tabel dieksekusi 100% pada memori browser via Svelte 5 `$derived.by()` di komponen [DataTable.svelte](file:///D:/laragon/www/hyperion-vault/frontend/src/lib/components/ui/DataTable.svelte).
    - Menghilangkan *keystroke-debounce network trips* ke server API dan memangkas waktu tanggap pencarian menjadi < 0.1 ms tanpa mengonsumsi pool 25 koneksi database MySQL Hostinger.
    - DOM rendering di-chunk 10 baris per halaman untuk menjamin render latency < 16ms (60 FPS) pada ribuan entitas data.
    - **Dynamic Page Size & Mobile Card Stack:** Menyediakan pemilih jumlah baris data per halaman dinamis (`10`, `25`, `50`, `100`), serta otomatis bertransformasi menjadi susunan kartu mandiri berstandar Pro-Grade Compact (`{#snippet card}`) di mobile (< 768px) guna mengeliminasi kelelahan scroll horizontal.
    - **Unobstructed Tab Panes & Breathable Density:** Seluruh tab pane (≤ 5 opsi) wajib 100% terbaca dan terlihat tanpa terpotong atau tersembunyi di balik scroll horizontal, serta mempertahankan ruang bernapas visual (*breathable negative space*) bebas sesak di laptop maupun HP.
15. **In-Memory State Mutations for CRUD Operations:**
    - Operasi Create, Update, Delete, Toggle Status, dan Revoke Session langsung memutasi state lokal array pengguna/sesi di browser secara instan pasca mendapat respons 200 OK dari server (`users[idx] = updatedUser`, `users = [newUser, ...users]`, `users = users.filter(...)`).
    - Mengeliminasi panggilan jaringan penuh `loadUsers()` / `loadSessions()` pasca-mutasi, memangkas latensi alur E2E sebesar ~39% dan menghilangkan beban koneksi sekunder.
16. **Auto-Reject Shield Against Document Preview Leaks:**
    - Pada [+layout.svelte](file:///D:/laragon/www/hyperion-vault/frontend/src/routes/+layout.svelte), hak akses rute diverifikasi secara sinkron melalui `$derived(canAccessPath($page.url.pathname, authState))`.
    - Jika pengguna (guest atau non-admin) mencoba membuka rute terlarang (misal `/admin/users`), sistem merender **Auto-Reject Shield** dengan [ElementLoader.svelte](file:///D:/laragon/www/hyperion-vault/frontend/src/lib/components/ui/ElementLoader.svelte) dan segera memicu pengalihan via `goto()`, tanpa pernah merender isi `{@render children()}`. Hal ini mencegah *document preview leak* atau kedipan konten rahasia sebelum pengalihan selesai.
17. **Reusable Micro Element Loader (`ElementLoader.svelte`):**
    - Komponen animasi pemuat independen murni CSS keyframes dan SVG tanpa dependensi eksternal.
    - Mendukung mode `spinner`, `pulse`, dan `dots` dengan variasi ukuran (`xs`, `sm`, `md`, `lg`) serta warna aksen tema. Digunakan pada button submission state, cell tables, card skeletons, dan auto-reject shields.
18. **CSS Engine Selection & Svelte 5 Native Compatibility:**
    - **Tailwind CSS v4 (@tailwindcss/vite) (Pilihan Utama & Aktif):** Menggunakan mesin Rust/LightningCSS ultra-cepat, memindai modul grafis Vite secara langsung tanpa parser regex AST yang rapuh, mendukung 100% sintaks runes dan snippet Svelte 5 (`{#snippet}`), menghasilkan waktu kompilasi sub-milidetik, serta 0 KB beban runtime.
    - **UnoCSS (Alternatif Cadangan):** Generator utilitas on-demand cepat, namun memerlukan konfigurasi preset manual untuk utilitas Tailwind v4 dan integrasi Vite-nya tidak se-native `@tailwindcss/vite` first-party.
    - **Windi CSS (Dilarang):** Telah dihentikan/sunset sejak 2023, tidak kompatibel dengan arsitektur Vite modern dan Svelte 5.
    - **Panda CSS (Tidak Direkomendasikan):** Berbasis ekstraksi AST compile-time yang berat dengan runtime style wrapper yang menambah beban DOM dan berpotensi memicu konflik dengan reaktivitas signal Svelte 5.
19. **Svelte 5 Fine-Grained Reactivity (Minimal Re-Rendering & Anti-Pattern Elimination):**
    - Svelte 5 meninggalkan Virtual DOM diffing dan component-wide re-rendering secara menyeluruh; compiler mentransformasikan template ke fine-grained signal subscriptions.
    - Pembaruan DOM dieksekusi secara bedah (*surgical DOM mutations*) hanya pada node teks atau atribut yang berubah tanpa memicu re-evaluasi skrip komponen.
    - **Pola Paginasi Aman Murni Turunan ($derived):** Mengganti manipulasi `$effect` dengan `let safeCurrentPage = $derived(Math.min(Math.max(1, currentPage), totalPages));` dan prop mirroring dengan `let perPage = $derived(pageSize || 10);`. Pola ini mengeliminasi *cascading effect microtask cycles* dan mencegah kesalahan fatal `ERR_SVELTE_TOO_MANY_UPDATES`.
20. **Zero-Node SPA Architecture & High-Performance Static Serving on Shared Hosting:**
    - SvelteKit dikonfigurasikan murni sebagai Single Page Application (SPA) Client-Side Rendering (`ssr = false`, `csr = true`, `prerender = false`, `trailingSlash = 'never'` di `src/routes/+layout.js`).
    - `@sveltejs/adapter-static` dikonfigurasikan dengan `fallback: 'index.html'` dan `precompress: true` di `frontend/vite.config.js`, menghasilkan aset `.gz` dan `.br` pra-kompresi secara otomatis saat build.
    - Gateway `index.php` root menyajikan file statis SPA dengan optimasi jaringan tingkat tinggi:
      - Aset fingerprinted Vite (`_app/immutable/*`) dikirim dengan header `Cache-Control: public, max-age=31536000, immutable` untuk latensi 0ms pada kunjungan berulang.
      - SPA fallback shell (`index.html`) dilayani dengan `Cache-Control: no-cache, no-store, must-revalidate` untuk mencegah stale chunk issue saat pembaruan rilis.
      - Dukungan native pre-compressed Brotli (`.br`) dan Gzip (`.gz`) dengan header `Content-Encoding` dan `Vary: Accept-Encoding`, memangkas transfer data CSS/JS hingga ~86% tanpa beban CPU kompresi server.
      - Validasi ETag (`If-None-Match`) menghasilkan respons `304 Not Modified` instan untuk aset non-immutable.
    - `src/app.html` dilengkapi `dns-prefetch` font Google, atribut `data-sveltekit-preload-code="hover"`, serta inline CSS background anti-FOUC (Flash of Unstyled Content) saat transisi tema daylight/nocturnal.
    - Seluruh dependensi Node.js dijamin kompatibel lintas versi melalui `frontend/.npmrc` (`engine-strict=false`, `legacy-peer-deps=true`).
21. **Universal Password Visibility Toggle (Eye Icon Standard):**
    - Seluruh elemen input kata sandi pada aplikasi (baik pada formulir login [LoginForm.svelte](file:///D:/laragon/www/hyperion-vault/frontend/src/lib/components/features/auth/LoginForm.svelte) maupun modal manajemen pengguna [+page.svelte](file:///D:/laragon/www/hyperion-vault/frontend/src/routes/admin/users/+page.svelte)) diwajibkan memiliki tombol pengalih visibilitas interaktif dengan ikon SVG mata terbuka dan mata tercoret.
    - State visibilitas dikelola menggunakan Svelte 5 `$state(false)` secara independen per modal/form dengan reset otomatis saat modal dibuka, dilengkapi atribut `aria-label` dinamis untuk kepatuhan aksesibilitas peramban dan pembaca layar (WCAG), serta divalidasi secara deterministik dalam suite pengujian E2E Playwright UAT.
22. **Konsep Desain Visual & Landing Page ([doc/design.md](file:///D:/laragon/www/hyperion-vault/doc/design.md)):**
    - Kanvas Deep Obsidian `#090d16` dengan kontras rasio WCAG AAA 14.8:1 terhadap teks `#f8fafc`.
    - Tipografi 3-Font Internasional: `Outfit` (Heading/Display), `Inter` (Body/UI), `JetBrains Mono` (Data/Code/Hash/Metrics).
    - Grid spasial 8-point standar industri (`gap-4`, `p-6`, `rounded-2xl`, min 44x44px touch targets).
    - Standar kustomisasi tampilan wizard instalasi (`install.php`) dan landing page terintegrasi.
23. **Autonomous Dual-Track Feature Engine ([doc/prompt.feature.md](file:///D:/laragon/www/hyperion-vault/doc/prompt.feature.md)):**
    - Track A: Mengonversi input bisnis mentah (*Raw Business Input*) menjadi PRD, skema 3NF MySQL berindeks komposit, diagram Mermaid ERD & Sequence, kontrak API Sanctum, dan komponen CRUD Svelte 5.
    - Track B: Menyesuaikan gaya visual landing page dan tema UI sesuai studi kasus bisnis (5 Preset: *Neo-Enterprise Dark*, *Clean Fintech Light*, *Swiss Monochrome*, *Cyberpunk DevTool*, *Midnight Navy*).
24. **Single-Span Accessible Badge Standard ([Badge.svelte](file:///D:/laragon/www/hyperion-vault/frontend/src/lib/components/ui/Badge.svelte)):**
    - Elemen teks dalam badge dirender langsung tanpa pembungkus `<span>` bersarang di dalamnya (`<span>{value}</span>`), mencegah pelanggaran mode ketat Playwright E2E (`strict mode violation: span:has-text() resolved to multiple elements`).
25. **Explicit Easy-to-Understand Action Bar:**
    - Tombol logout di sidebar berlabel eksplisit **"Keluar Sesi Ini"** (`#logout-btn`) dengan tooltip informatif dan ikon SVG pintu keluar.
    - Tombol di dashboard dipisahkan secara tegas: **"Keluar Akun"** (`#logout-btn`, merah peringatan) dan **"Ganti Akun"** (`#to-login-btn`, navigasi netral).
26. **Automated Versioning Registry, Safe In-Place Updater & SemVer 2.0.0 Lockstep:**
    - Sistem mematuhi standar **SemVer 2.0.0 (`MAJOR.MINOR.PATCH`)**:
      - `MAJOR`: Perubahan arsitektur/database yang breaking (*backward-incompatible*).
      - `MINOR`: Penambahan fitur baru yang *backward-compatible* (reset PATCH ke 0).
      - `PATCH`: Perbaikan bug (*bugfix*) dan refaktorisasi internal non-breaking.
    - **Lockstep Synchronization:** Setiap kenaikan versi wajib diperbarui serentak secara atomik di 9 berkas: `backend/composer.json`, `frontend/package.json`, `install.php` (`APP_VERSION`), `backend/routes/api.php` (`$codebaseVersion`), `llms.txt`, `doc/architecture.md`, `doc/design.md`, `doc/prompt.feature.md`, dan `doc/feature.md`, serta diverifikasi via `php doc/version_sync.php status`.
    - Sistem menyediakan perintah `php install.php update` dan antarmuka web updater yang mengeksekusi `artisan optimize:clear`, migrasi aman non-destructive `artisan migrate --force` (DILARANG `migrate:fresh` di production), dan kompilasi ulang SPA.
    - Berkas `.installed` mencatat versi terpasang, tanggal pembaruan, dan riwayat audit `history` secara permanen.
27. **Client-Customizable RBAC & Dynamic Permission Matrix:**
    - Peran pengguna dan izin granular dikonfigurasi melalui [backend/config/permissions.php](file:///D:/laragon/www/hyperion-vault/backend/config/permissions.php).
    - Klien dapat menambahkan peran baru (misal: `doctor`, `pharmacist`, `auditor`, `staff`) tanpa alterasi skema database.
    - Dilindungi oleh middleware `permission:...`, helper `User::hasPermission()`, dan Svelte 5 `$derived(authState.hasPermission())`.
28. **System Element Customization & White-Labeling Standards:**
    - Panduan terstandarisasi untuk kustomisasi installer (`install.php`), kustomisasi landing page & maskot, dan 5 preset gaya desain visual di [design.md](file:///D:/laragon/www/hyperion-vault/doc/design.md) dan [prompt.feature.md](file:///D:/laragon/www/hyperion-vault/doc/prompt.feature.md).
29. **Zero-Overhead Public Telemetry & Local Browser Clock Engine:**
    - Endpoint publik telemetri di-cache via disk lokal NVMe SSD (`CACHE_STORE=file`) dengan header HTTP `Cache-Control: public, max-age=60, s-maxage=300, stale-while-revalidate=120`.
    - Landing page memanggil endpoint ini secara terisolasi tanpa polling berkala ke server.
    - Seluruh perhitungan dan visualisasi status dinamis dilakukan 100% pada memori browser via Svelte 5 `$derived` dan timer interval JavaScript (Intl API).
    - Mempertahankan batas kapasitas Little's Law $L \le 0.024$ koneksi tanpa lonjakan pool database.
30. **Agent Skills Standard (#Graphify & #Ponytail):**
    - **#Graphify (Call-Tree & Connection Leak Tracer):** Menelusuri rantai pemanggilan Laravel Sanctum, middleware pipeline (`CheckUserActive`), dan siklus koneksi PDO secara presisi. Memastikan terminasi koneksi database segera setelah response selesai tanpa menahan slot di FastCGI pool.
    - **#Ponytail (Ultra-Minimalist Performance Engineering):** Mengutamakan perbaikan ultra-minimalis (indeks komposit B-Tree SQLite/MySQL, normalisasi query agregasi terkelompok, switch default driver session/cache ke `file` dan queue ke `sync`, serta eager loading terarah) tanpa over-engineering membuat layer Repository/Service/DTO berlebih yang memboroskan alokasi memori runtime PHP di shared hosting.
31. **Agenda Easy Maintenance & High Availability (HA) Roadmap:**
    - **Fase 1 (Current Shared Hosting):** Single SQLite WAL / MySQL Hostinger, batas 25 koneksi, beban $L \le 0.024$ koneksi, file session/cache di NVMe disk, static SPA build via Nginx/PHP. Maintenance via `php install.php update`.
    - **Fase 2 (Managed VPS Migration):** Ubuntu 24.04 LTS, Nginx FastCGI dedicated worker pool (port 9003/9004), Redis in-memory cache/session, query buffer teroptimasi, cron queue worker.
    - **Fase 3 (High Availability Enterprise Cluster):** Dual stateless web/API nodes di balik Cloudflare / Nginx Load Balancer, SQLite LiteFS / MySQL Primary-Replica dengan ProxySQL, MinIO S3 Object Storage, target SLO 99.99% ketersediaan dan RTO < 15 menit.
32. **Dual-Mode Session & Bearer Authentication Guard:**
    - Middleware sesi Laravel diinjeksikan secara eksplisit ke grup `api` pada `bootstrap/app.php` (`StartSession`, `EncryptCookies`, `AddQueuedCookiesToResponse`, `ShareErrorsFromSession`).
    - Personal Access Token (`token`) diterbitkan saat login dan disimpan di `localStorage ('hyperion_token')`, disertakan sebagai header `Authorization: Bearer <token>` bersama `credentials: 'include'`. Mencegah hilangnya status otentikasi saat navigasi client-side SvelteKit tanpa ketergantungan pada header HTTP `Referer`.
33. **Kesiapan Unggah FTP Turnkey & Root Gateway Security (Hostinger Shared Hosting / cPanel):**
    - Root `.htaccess` mengamankan file sensitif (`.env`, `.sqlite`, `.git`, `composer.*`) dengan status `403 Forbidden` dan merutekan traffic API & SPA ke root gateway `index.php`.
    - Svelte 5 dikompilasi ke SPA statis murni di `frontend/build/` via `@sveltejs/adapter-static` tanpa membutuhkan runtime Node.js di server shared hosting.
    - Database SQLite WAL teroptimasi (`backend/database/database.sqlite`) siap pakai lengkap dengan akun administrator default.
34. **Tree Menu Sidebar Navigation Architecture:**
    - Sidebar dikelompokkan ke dalam Tree Menus hierarkis dengan helper `$state` terbuka/tutup dan pencocokan rute aktif multi-level berbasis `pathname` dan `searchParams` via `$effect`.
35. **Wajib Pure CSR & SPA Architecture (Zero Full-Page Reload & In-Memory UI - Hard Invariant):**
    - Seluruh rute dan halaman frontend WAJIB menggunakan arsitektur Single Page Application (SPA) berbasis Client-Side Rendering (CSR). Root layout wajib mendeklarasikan `export const ssr = false;` dan `export const prerender = false;`.
    - DILARANG menggunakan form submission HTML tradisional (`action="..."`), reload halaman penuh (`window.location.reload()`, navigasi tag `<a>` tanpa router SvelteKit).
    - Seluruh mutasi data dan aksi tombol WAJIB beroperasi melalui asynchronous fetch (`api.request`), reaktif Svelte 5 Runes (`$state`), in-memory cache, dan update DOM instan.
36. **Perampingan & Rasionalisasi Test Suite (Zero Boilerplate, Feature Tests Focused):**
    - Eliminasi berkas boilerplate default.
    - Seluruh pengujian dipusatkan pada Feature Tests fungsional (Sanctum Auth, RBAC, Sessions, Rate Limiting) dengan assertion dinamis berbasis `composer.json` untuk menjamin eksekusi 100% pass sub-3 detik.
37. **Enterprise Multi-Tier Performance & Sub-Second Optimization Engine:**
    - **Compound Database Indexes B-Tree:** Menambahkan indeks komposit pada `users` (`['is_active', 'status']`) dan `user_devices` (`['user_id', 'last_active_at']`, `['expires_at']`) untuk mengeliminasi full-table scans.
    - **Single-Pass SQL Conditional Aggregations:** Menggantikan query hitung berulang dengan single-pass aggregated queries.
    - **Client-Side Request Collapsing & TTL Cache:** In-flight request deduplication di `ApiClient` via `inFlight` Map untuk mencegah duplicate concurrent network calls, ditambah 30-detik in-memory cache dengan auto-purge saat request mutasi POST/PUT/DELETE.
    - **Sub-Tab Lazy Hydration:** Dataset dimuat secara on-demand berbasis `activeSubTab`, meniadakan concurrent mount storm.
    - **Hardware-Accelerated Native SVG Charts:** Visualizer grafik tren performa dan telemetri berbasis SVG vektor murni berakselerasi GPU tanpa dependensi library eksternal.
38. **Zero Role Consolidation & Strict RBAC Matrix Invariant (Zero Aliasing):**
    - Seluruh peran pengguna diakui, disimpan, dan diuji secara mandiri tanpa aliasing runtime atau normalisasi mutatif (`consolidation_map`, `aliases`, `normalizeRoles`, `expandRoleAliases`).
    - Penyimpanan multi-peran dikelola secara transparan dan murni via JSON array di basis data. Multi-role assignment didukung native.
39. **Controlled Modal Lifecycle & Sticky Header Tabs ([`Modal.svelte`](file:///D:/laragon/www/hyperion-vault/frontend/src/lib/components/ui/Modal.svelte)):**
    - Komponen `Modal.svelte` wajib default `show = true` untuk menjamin render seketika dalam blok kondisional `{#if showModal}<Modal>`.
    - Seluruh formulir modal multi-langkah wajib meletakkan bilah navigasi tab di dalam snippet `{#snippet headerTabs()}` pada header modal terkunci (*sticky*), mencegah navigasi tab hilang saat menggulir formulir panjang.
40. **Zero Silent Failure & Reactive Button State (A11y Standard):**
    - Setiap tombol interaktif (`<button>`, `role="button"`) wajib terikat reaktif dengan event handler `onclick`.
    - Setiap aksi tombol yang memicu mutasi atau network call wajib menampilkan siklus 3-tahap: Busy loading state + tombol disabled, Success feedback via floating toast, dan Error safeguard tanpa *silent catch*.
    - Seluruh tombol wajib menyematkan atribut `type="button"` eksplisit dan `aria-label` deskriptif (0 warning a11y).
41. **Mandatory Synchronous Documentation Update Rule (Zero Silent Drift):**
    - Setiap mutasi kode (bugfix, refactor, optimasi fitur) wajib memperbarui berkas dokumentasi terkait (`architecture.md`, `prompt.feature.md`, `features/`, `design.md`, `GEMINI.md`, `changes.md`) secara atomik dalam giliran yang sama sebelum melaporkan penyelesaian.
42. **Ergonomi Ramah Pengguna Senior & Non-Teknis (Baby Boomer UX):**
    - Input form wajib berdaya affordance tinggi dengan border 2px solid kontras (`border border-slate-300 dark:border-slate-700`).
    - Tinggi sentuh input minimal 44px (`min-h-[44px]`), font teks `16px` di mobile (`text-base sm:text-xs`) untuk mengeliminasi *iOS Safari auto-zoom*.
    - Setiap formulir dilengkapi kotak petunjuk ramah (*callout guidance box*) berikon emoji (💡), penanda kolom wajib eksplisit `<span class="text-xs font-bold text-rose-600 bg-rose-50 dark:bg-rose-950/60 px-2 py-0.5 rounded border border-rose-200 dark:border-rose-900">* Wajib Diisi</span>`, dan penanda opsional `(Boleh Kosong)`.
    - Tombol aksi bawah deskriptif dan meyakinkan (`[✕ Batal]`, `[💾 Simpan Perubahan]`) dengan feedback taktil dan target sentuh $\ge 44$px.
43. **Pembersihan Fisik Sesi NVMe Otomatis (Session Disk Unlink):**
    - Sesi yang dibatalkan (logout, revoke perangkat, atau deteksi akun dengan role non-konfigurasi) wajib menghapus file fisik di `storage/framework/sessions/{session_id}` seketika melalui `@unlink()`.
44. **Zero Role Enumeration / Mitigasi Pengungkapan Informasi (OWASP ASVS):**
    - Sistem dilarang membocorkan daftar nama peran yang valid pada pesan kesalahan validasi, otentikasi, atau otorisasi. Seluruh pesan penolakan wajib generik dan netral.
45. **FastCGI Dual Worker Resiliency (Port 9003 / 9004):**
    - Mengonfigurasi dual upstream FastCGI worker di Nginx Laragon (`127.0.0.1:9003` dan `127.0.0.1:9004`) dengan round-robin fallback dan `$env:PHP_FCGI_MAX_REQUESTS="0"` untuk mencegah *502 Bad Gateway* jika worker restart.
46. **Optimasi Rendering Off-Screen & Native SVG:**
    - Visualisasi grafik berbasis native SVG vektor inline tanpa library eksternal (0 KB dependensi).
    - Memanfaatkan utilitas CSS `.cv-auto`, `.cv-auto-row`, dan `.cv-auto-card` berbasis `content-visibility: auto` untuk memangkas waktu initial paint pada ribuan baris tabel.
47. **Core Web Vitals Target 2026:**
    - INP < 100ms via Svelte 5 fine-grained runes.
    - LCP < 1.2s via prekompresi Brotli (`.br`) dan Gzip (`.gz`) statis saat build time.
    - CLS < 0.05 via dimensi layout terdefinisi dan skeleton loader.

---

## BAGIAN 3: STRUKTUR DIREKTORI PROYEK LENGKAP

```
D:/laragon/www/hyperion-vault/
├── index.php                           # Zero-Config Universal Gateway Laragon & Reverse-Proxy
├── install.php                         # Automated Installer, Diagnostic & Safe In-Place Updater v6.30.0
├── .installed                          # Production Security Lock File & Version Audit Registry
├── llms.txt                            # Indeks arsitektur & panduan token-saver untuk AI / LLM
├── changes.md                          # Log rekayasa teknis & catatan perubahan arsitektur
├── local.inisiasi.md                   # Runbook eksekusi lokal efisien token
├── architecture.md                     # Master arsitektur sistem (dokumen ini)
├── design.md                           # Standar konsep desain visual, landing page, dan kustomisasi
├── prompt.feature.md                   # Mesin sintesis fitur otomatis (Track A & Track B)
├── doc/                                # Dokumentasi pendukung & spesifikasi modular
│   ├── feature.md                      # Master Feature Index
│   └── features/                       # Spesifikasi fitur modular terstruktur
│       ├── 01_auth_session.md          # Fitur Autentikasi & Multi-Device Session
│       ├── 02_user_rbac.md              # Fitur User Management & Dynamic RBAC
│       └── 03_system_telemetry.md      # Fitur Gateway Delivery, Telemetri SLO
├── backend/
│   ├── config/
│   │   └── permissions.php             # Client-Customizable Roles & Permissions Matrix
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/V1/
│   │   │   │   ├── Auth/
│   │   │   │   │   └── AuthController.php
│   │   │   │   └── Admin/
│   │   │   │       ├── AdminUserController.php
│   │   │   │       └── AdminSessionController.php
│   │   │   └── Middleware/
│   │   │       ├── CheckUserActive.php
│   │   │       ├── EnsureRole.php
│   │   │       ├── EnsurePermission.php # Granular Permission Access Guard
│   │   │       └── SanitizeInput.php
│   │   ├── Models/
│   │   │   ├── User.php                # Helpers: hasRole, hasPermission, getPermissions
│   │   │   └── UserDevice.php
│   │   └── Services/
│   │       └── JwtService.php
│   ├── bootstrap/
│   │   └── app.php
│   ├── database/
│   │   ├── migrations/
│   │   │   ├── 0001_01_01_000000_create_users_table.php
│   │   │   └── 2026_09_24_161716_create_user_devices_table.php
│   │   └── seeders/
│   │       └── DatabaseSeeder.php
│   ├── routes/
│   │   ├── api.php
│   │   └── web.php
│   └── tests/Feature/
│       ├── SanctumAuthTest.php
│       └── AdminUserManagementTest.php
└── frontend/
    ├── static/
    │   └── favicon.svg
    ├── src/
    │   ├── app.html                        # Google Fonts (Outfit, Inter, JetBrains Mono)
    │   ├── lib/
    │   │   ├── api/
    │   │   │   └── client.js
    │   │   ├── components/
    │   │   │   ├── features/
    │   │   │   │   ├── auth/
    │   │   │   │   │   └── LoginForm.svelte     # Form Login dengan Eye Toggle & Ambient Glow
    │   │   │   │   └── mascot/
    │   │   │   │       └── ArchitectMascot.svelte # Interactive AI Mascot "Volt"
    │   │   │   ├── layout/
    │   │   │   │   ├── AppSidebar.svelte        # Sidebar Navigation ("Keluar Sesi Ini" UX)
    │   │   │   │   └── AppTopbar.svelte         # Breadcrumbs Topbar (Home SVG, Chevron)
    │   │   │   └── ui/
    │   │   │       ├── Badge.svelte             # Status (Ping dot) & Role Badges
    │   │   │       ├── DataTable.svelte         # Browser-Only Instant Search & Pagination
    │   │   │       ├── Modal.svelte             # Accessible Modal dengan Top Neon Accent
    │   │   │       ├── PageLoader.svelte        # Reusable Pure CSS/SVG Glowing Loader
    │   │   │       └── ElementLoader.svelte     # Micro Loader (Spinner/Pulse/Dots)
    │   │   ├── config/
    │   │   │   └── navigation.js
    │   │   └── state/
    │   │       └── auth.svelte.js               # Svelte 5 Runes Auth Store
    │   └── routes/
    │       ├── layout.css                       # Tailwind v4 Theme Fonts & Glassmorphism
    │       ├── +layout.svelte                   # Root Layout, Ambient Glow & Auto-Reject Shield
    │       ├── +page.svelte                     # Modern Landing Page + Mascot Section
    │       ├── login/+page.svelte               # Standalone Login Page
    │       ├── dashboard/+page.svelte           # User Dashboard + Card Keamanan Sesi
    │       └── admin/
    │           ├── dashboard/+page.svelte       # Admin Dashboard + Card Pusat Kendali
    │           ├── users/+page.svelte           # User Management (CRUD + Suspend + In-Memory)
    │           └── sessions/+page.svelte        # Real-Time Device Sessions Audit & Revocation
    └── tests/
        └── auth.uat.spec.js                     # Playwright E2E Test Suite (100% Green)
```

---

## BAGIAN 4: IMPLEMENTASI BACKEND (LARAVEL 13 SANCTUM)

### 4.1. Konfigurasi Environment Backend (`backend/.env`)
```ini
APP_NAME=Hyperion Vault
APP_ENV=local
APP_KEY=base64:aenWdnvQRngxSXybYXySBgvhFy2fNnpPEvD+sfGlMGk=
APP_DEBUG=true
APP_URL=http://hyperion-vault.test

DB_CONNECTION=sqlite

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=.hyperion-vault.test
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Konfigurasi Kustom Auth & Multi-Device
AUTH_TOKEN_TTL_MINUTES=120
AUTH_JWT_COOKIE_NAME=auth_jwt
AUTH_ROLE_COOKIE_NAME=user_role
AUTH_MAX_CONCURRENT_DEVICES=3

CACHE_STORE=file
QUEUE_CONNECTION=sync
```

### 4.2. Migrasi Database Users (`backend/database/migrations/0001_01_01_000000_create_users_table.php`)
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name')->index();
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->text('role')->default('["user"]');
    $table->boolean('is_active')->default(true)->index();
    $table->unsignedInteger('token_version')->default(1);
    $table->rememberToken();
    $table->timestamps();

    // Indeks optimasi performa query pada 9000+ record
    $table->index(['created_at']);
});
```

### 4.3. Model User (`backend/app/Models/User.php`)
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'token_version',
    ];

    protected $appends = ['status'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'token_version' => 'integer',
        ];
    }

    protected function role(): Attribute
    {
        return Attribute::make(
            get: function ($val) {
                if (empty($val)) return ['user'];
                if (is_array($val)) return $val;
                $decoded = json_decode($val, true);
                if (is_array($decoded)) return $decoded;
                return array_values(array_filter(array_map('trim', explode(',', $val))));
            },
            set: fn ($val) => is_array($val) ? json_encode(array_values(array_unique($val))) : json_encode([$val])
        );
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function hasRole(string ...$roles): bool
    {
        return !empty(array_intersect($roles, (array) $this->role));
    }

    public function hasAllRoles(string ...$roles): bool
    {
        return empty(array_diff($roles, (array) $this->role));
    }

    public function getPermissions(): array
    {
        if ($this->isAdmin()) return ['*'];
        $roleMap = config('permissions.roles', []);
        $perms = [];
        foreach ((array) $this->role as $r) {
            if (isset($roleMap[$r]) && is_array($roleMap[$r])) {
                $perms = array_merge($perms, $roleMap[$r]);
            }
        }
        return array_values(array_unique($perms));
    }

    public function hasPermission(string ...$permissions): bool
    {
        if ($this->isAdmin()) return true;
        $userPerms = $this->getPermissions();
        if (in_array('*', $userPerms, true)) return true;
        return !empty(array_intersect($permissions, $userPerms));
    }

    public function hasAllPermissions(string ...$permissions): bool
    {
        if ($this->isAdmin()) return true;
        $userPerms = $this->getPermissions();
        if (in_array('*', $userPerms, true)) return true;
        return empty(array_diff($permissions, $userPerms));
    }
}
```

### 4.3b. Matriks Peran & Hak Akses Klien (`backend/config/permissions.php`)
```php
<?php
return [
    'roles' => [
        'admin'   => ['*'], // Wildcard: seluruh izin otomatis diberikan
        'manager' => ['users.view', 'users.create', 'users.edit', 'sessions.view', 'reports.view', 'reports.export'],
        'staff'   => ['users.view', 'reports.view'],
        'auditor' => ['users.view', 'sessions.view', 'reports.view', 'reports.export'],
        'user'    => ['profile.view', 'profile.edit'],
    ],
];
```
* **Middleware Proteksi:** `EnsurePermission` (`permission:reports.export`) memeriksa izin pengguna secara instan tanpa query database tambahan.
* **Kustomisasi Klien:** Klien dapat mendefinisikan peran kustom (misal `doctor`, `cashier`, `operator`) cukup dengan mendaftarkannya pada array konfigurasi di atas.

### 4.4. Service Signed JWT Anti-Tampering (`backend/app/Services/JwtService.php`)
```php
<?php

namespace App\Services;

class JwtService
{
    public static function createToken(array $payload, int $ttlSeconds = 7200): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + $ttlSeconds;

        $b64Header = self::base64UrlEncode(json_encode($header));
        $b64Payload = self::base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', "{$b64Header}.{$b64Payload}", config('app.key'), true);
        $b64Signature = self::base64UrlEncode($signature);

        return "{$b64Header}.{$b64Payload}.{$b64Signature}";
    }

    public static function verifyToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$b64Header, $b64Payload, $b64Signature] = $parts;
        $expectedSig = self::base64UrlEncode(
            hash_hmac('sha256', "{$b64Header}.{$b64Payload}", config('app.key'), true)
        );

        if (!hash_equals($expectedSig, $b64Signature)) return null;

        $payload = json_decode(self::base64UrlDecode($b64Payload), true);
        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
```

### 4.5. Middleware Proteksi Pengguna Aktif & Multi-Device (`backend/app/Http/Middleware/CheckUserActive.php`)
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (!$user->is_active || $user->status === 'suspended') {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json([
                    'message' => 'Akun Anda telah ditangguhkan. Silakan hubungi administrator.'
                ], 403);
            }

            $currentSessionVersion = (int) $request->session()->get('auth_token_version', 1);
            if ($currentSessionVersion < (int) $user->token_version) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json([
                    'message' => 'Sesi Anda telah dicabut oleh administrator atau sistem. Silakan login kembali.'
                ], 401);
            }
        }

        return $next($request);
    }
}
```

### 4.6. Routing API Terpusat (`backend/routes/api.php`)
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminSessionController;

Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-other-devices', [AuthController::class, 'logoutOtherDevices']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Admin Area: User Management & Session Management Terpisah
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/stats', fn () => response()->json(['total_users' => \App\Models\User::count()]));
        Route::get('/dashboard', fn () => response()->json(['message' => 'Admin dashboard data']));

        // Manajemen Pengguna (CRUD)
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::put('/users/{id}', [AdminUserController::class, 'update']);
        Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);
        Route::post('/users/{id}/toggle-status', [AdminUserController::class, 'toggleStatus']);
        Route::post('/users/{id}/revoke-sessions', [AdminUserController::class, 'revokeSessions']);

        // Manajemen Sesi Aktif Nyata & Forensik
        Route::get('/sessions', [AdminSessionController::class, 'index']);
        Route::post('/sessions/{id}/revoke', [AdminSessionController::class, 'revokeSession']);
        Route::post('/sessions/user/{id}/revoke', [AdminSessionController::class, 'revokeUserSessions']);
        Route::post('/sessions/revoke-all', [AdminSessionController::class, 'revokeAllSessions']);
        Route::post('/sessions/cleanup', [AdminSessionController::class, 'cleanup']);

        // Status Versi & Pembaruan Sistem (Constraint 8)
        Route::get('/version', function () {
            $lockFile = dirname(base_path()) . DIRECTORY_SEPARATOR . '.installed';
            $installedData = file_exists($lockFile) ? json_decode(file_get_contents($lockFile), true) : null;
            $installedVersion = $installedData['version'] ?? '5.5.0';
            $codebaseVersion = '5.6.0';

            return response()->json([
                'codebase_version' => $codebaseVersion,
                'installed_version' => $installedVersion,
                'is_up_to_date' => version_compare($codebaseVersion, $installedVersion, '<='),
                'installed_at' => $installedData['installed_at'] ?? null,
                'updated_at' => $installedData['updated_at'] ?? null,
                'history' => $installedData['history'] ?? [],
            ]);
        });
    });

    Route::middleware('role:user,admin')->prefix('user')->group(function () {
        Route::get('/profile', fn (\Illuminate\Http\Request $r) => response()->json(['user' => $r->user()]));
    });
});
```

---

## BAGIAN 5: ZERO-CONFIG UNIVERSAL GATEWAY & INSTALLER / UPDATER OTOMATIS

### 5.1. Universal Gateway & Security Headers (`index.php`)
Simpan di root folder proyek: `D:\laragon\www\hyperion-vault\index.php`.
```php
<?php
/**
 * Zero-Config Universal Gateway for Laragon Default Nginx
 * Serves Laravel API + Svelte 5 Frontend under single domain (http://hyperion-vault.test)
 * without modifying any Nginx virtual host configurations.
 */

// Security & Speed HTTP Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// 0. Automated Installer & Health Diagnostic Tool
if ($uri === '/install.php' || str_starts_with($uri, '/install.php/')) {
    require __DIR__ . '/install.php';
    exit;
}

// Auto-detect uninstalled state and redirect browser requests to /install.php
if (!file_exists(__DIR__ . '/.installed') && (!file_exists(__DIR__ . '/backend/.env') || !file_exists(__DIR__ . '/backend/database/database.sqlite'))) {
    if (!str_starts_with($uri, '/api/') && !str_starts_with($uri, '/sanctum/')) {
        header('Location: /install.php');
        exit;
    }
}

// 1. API & Sanctum routes -> Forward to Laravel backend
if (str_starts_with($uri, '/api/') || str_starts_with($uri, '/sanctum/') || $uri === '/up') {
    require __DIR__ . '/backend/public/index.php';
    exit;
}

// 2. Storage files -> Forward to Laravel storage
if (str_starts_with($uri, '/storage/')) {
    $storageFile = __DIR__ . '/backend/public' . $uri;
    if (file_exists($storageFile) && !is_dir($storageFile)) {
        serveStaticFile($storageFile);
    }
    require __DIR__ . '/backend/public/index.php';
    exit;
}

// 3. Static files in backend/public
$backendPublicFile = __DIR__ . '/backend/public' . $uri;
if ($uri !== '/' && file_exists($backendPublicFile) && !is_dir($backendPublicFile)) {
    serveStaticFile($backendPublicFile);
}

// 4. Static files in frontend/build or frontend/static
$frontendBuildFile = __DIR__ . '/frontend/build' . $uri;
if ($uri !== '/' && file_exists($frontendBuildFile) && !is_dir($frontendBuildFile)) {
    serveStaticFile($frontendBuildFile);
}

// 5. Development Mode: Proxy to Vite dev server if running on 127.0.0.1:5173
$vitePort = 5173;
$viteSocket = @fsockopen('127.0.0.1', $vitePort, $errno, $errstr, 0.1);
if ($viteSocket) {
    fclose($viteSocket);
    proxyToVite("http://127.0.0.1:{$vitePort}" . $_SERVER['REQUEST_URI']);
    exit;
}

// 6. Production Static Build fallback: Serve frontend/build/index.html
$indexHtml = __DIR__ . '/frontend/build/index.html';
if (file_exists($indexHtml)) {
    header('Content-Type: text/html; charset=UTF-8');
    readfile($indexHtml);
    exit;
}

// 7. Fallback to Laravel if no frontend build exists
require __DIR__ . '/backend/public/index.php';

function serveStaticFile($path) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mimes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
    ];
    header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
    readfile($path);
    exit;
}

function proxyToVite($targetUrl) {
    $ch = curl_init($targetUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

    $headers = [];
    foreach (getallheaders() as $key => $value) {
        if (!in_array(strtolower($key), ['host', 'content-length'])) {
            $headers[] = "{$key}: {$value}";
        }
    }
    $headers[] = 'Host: 127.0.0.1:5173';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH', 'DELETE'])) {
        $body = file_get_contents('php://input');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $response = curl_exec($ch);
    if ($response === false) {
        http_response_code(502);
        echo "Vite Proxy Error: " . curl_error($ch);
        curl_close($ch);
        return;
    }

    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $rawHeaders = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    http_response_code($httpCode);
    foreach (explode("\r\n", $rawHeaders) as $line) {
        if (!empty($line) && !str_starts_with(strtolower($line), 'transfer-encoding:') && !str_starts_with(strtolower($line), 'content-length:')) {
            header($line, false);
        }
    }
    echo $body;
}
```

### 5.2. Automated Setup Wizard & Health Diagnostic Tool (`install.php`)
Simpan di root folder proyek: `D:\laragon\www\hyperion-vault\install.php`.
* **Dukungan Dual-Mode:**
  - **Browser Mode:** `http://hyperion-vault.test/install.php` menyajikan antarmuka visual dark-theme responsif dengan pengecekan diagnostik *real-time*.
  - **CLI Mode:** `php install.php install` mengeksekusi instalasi otomatis tanpa interaksi (headless) yang cocok untuk pipeline CI/CD atau script bootstrap.
* **13 Poin Pemeriksaan Prasyarat:**
  1. Versi PHP (>= 8.2, rekomendasi 8.3/8.4).
  2. 7 Ekstensi PHP wajib (`pdo_sqlite`, `sqlite3`, `curl`, `mbstring`, `openssl`, `fileinfo`, `json`).
  3. 3 Izin Tulis Direktori (`backend/storage`, `backend/bootstrap/cache`, `backend/database`).
  4. 2 Verifikasi Dependensi Paket (`backend/vendor/autoload.php`, `frontend/node_modules`).
* **Proteksi Lockfile Keamanan (`.installed`):**
  - Mencegah penyerang atau pengguna menjalankan ulang skrip instalasi untuk mereset data.
  - Jika berkas `.installed` terdeteksi, operasi instalasi diblokir seketika dengan status terkunci. Untuk mengulang instalasi di development, berkas `.installed` harus dihapus secara manual.

### 5.3. Golden Archetype Spawner Engine (`install.php spawn`)
Sistem mengadopsi pola **Golden Master Archetype Scaffolding** untuk melahirkan proyek-proyek baru dalam hitungan $\le 5$ detik tanpa perlu mengunduh ulang dependensi:
* **Perintah CLI:** `php install.php spawn <nama-folder-target> [--name="..."] [--domain="..."] [--desc="..."]`
* **Antarmuka Web:** Formulir visual terintegrasi pada `http://hyperion-vault.test/install.php` (Card: *Spawner Proyek Baru*).
* **Mekanisme Replikasi Otonom:**
  1. **Zero-Dependency Re-download:** Menggunakan *multi-threaded I/O robocopy* (`/MT:16` di Windows) untuk mereplikasi `backend/vendor` dan `frontend/node_modules` secara lokal instan.
  2. **Sanitasi State Kritis:** Mengecualikan berkas `.git`, `storage/framework/sessions/*`, `storage/logs/*`, `bootstrap/cache/*`, dan `database/database.sqlite` dari proyek induk.
  3. **Isolasi Kriptografi Penuh:** Membangkitkan `APP_KEY` baru via `php artisan key:generate --force` pada proyek target (mencegah eksploitasi dekripsi session antar-proyek).
  4. **Konfigurasi Lingkungan Deterministik:** Menulis `backend/.env` baru dengan Host-Only Cookie (`SESSION_DOMAIN=`) dan `SANCTUM_STATEFUL_DOMAINS` lengkap beserta port pengembangan (`:5173`, `:8000`).
  5. **Inisiasi Database & Seeder Dinamis:** Membuat SQLite baru dan menjalankan `migrate:fresh --seed` yang otomatis membuat akun administrator sesuai domain baru (`admin@<target-domain>`).
  6. **Inisialisasi Git Mandiri:** Menjalankan `git init` dan *initial commit* bersih di folder proyek baru.

### 5.4. Mekanisme Safe In-Place Updater & Riwayat Versi (`install.php` & `.installed`)
Ketika terdapat pembaruan arsitektur atau kode dari GitHub (`git pull`), sistem menyediakan mekanisme pembaruan aman tanpa merusak data:
* **Perintah CLI:** `php install.php update`
* **Antarmuka Web:** Akses `http://hyperion-vault.test/install.php` $\implies$ Sistem otomatis mendeteksi perbedaan versi antara codebase (`APP_VERSION = 5.6.0`) dan berkas `.installed`, menampilkan banner pembaruan, dan menyediakan tombol **"Perbarui Sistem Sekarang (Safe Update)"**.
* **Tahapan Eksekusi Deterministik:**
  1. Membersihkan cache aplikasi (`artisan optimize:clear`).
  2. Menjalankan migrasi pending secara aman (`artisan migrate --force` - **DILARANG** `migrate:fresh` di production agar data pengguna dan sesi tetap utuh).
  3. Mengompilasi ulang bundel statis SPA frontend (`npm run build`) jika folder `node_modules` tersedia.
  4. Memperbarui versi pada `.installed` dan menambahkan entri riwayat ke array `history` (menyimpan versi asal, versi tujuan, waktu update, dan status).

### 5.5. Automated Smart Ship Tooling (`install.php ship`)
Sistem menyediakan automasi commit semantik dan sinkronisasi push yang mengaudit seluruh delta berkas sejak push terakhir:
* **Perintah CLI:** `php install.php ship ["Pesan Kustom"] [--no-push] [--dry-run]`
* **Fitur Utama:**
  1. **Deteksi Delta Multi-Kategori:** Mengelompokkan berkas secara semantik (*Backend*, *Frontend*, *Dokumentasi & SSoT*, *Aturan & Tata Kelola AI*, *Gateway & Konfigurasi*).
  2. **Penyusunan Format Conventional Commits:** Membangkitkan pesan multi-baris berstruktur dengan rincian berkas dan statistik diff baris.
  3. **Imunitas Escaping Windows:** Menulis pesan ke berkas temporer dan mengeksekusi via `git commit -F` untuk mencegah terpotongnya karakter atau baris baru pada terminal Windows.
  4. **Atomic Push:** Otomatis mendeteksi branch aktif dan mem-push ke `origin/<branch>` secara aman.

### 5.6. Skill & SSoT Synchronization Engine (`install.php sync-skills`)
Mekanisme resmi untuk mendistribusikan tata kelola AI, aturan invarian, skill rekayasa, dan dokumen SSoT secara deterministik dari Master Archetype (`hyperion-vault`) ke downstream proyek (`yayasan-pqtm`, dll):
* **Perintah CLI:** `php install.php sync-skills [--target=<path>] [--source=<path>] [--dry-run]`
* **Fitur Utama:**
  1. **Audit Checksum SHA-256:** Membandingkan hash berkas secara deterministik; hanya menyalin berkas yang baru atau mengalami pembaruan konten.
  2. **Isolasi Domain:** Menyelaraskan `.agents/rules/`, `.agents/skills/`, dan dokumen SSoT tanpa menyentuh kode bisnis spesifik proyek downstream.
  3. **Verifikasi Integritas:** Skrip `audit-skills-integrity.ps1` memvalidasi ketiadaan broken links dan kepatuhan YAML frontmatter seluruh skill.

### 5.7. Panduan Kustomisasi Elemen Sistem & White-Labeling
1. **Kustomisasi Installer (`install.php`):**
   - **Logo:** Ganti tag `<svg>` di dalam class `.logo-box` dengan SVG logo klien.
   - **Warna Tema:** Ubah variabel CSS `:root` (`--bg`, `--card-bg`, `--indigo`, `--cyan`, `--green`, `--amber`).
   - **Prasyarat Kustom:** Tambahkan item pemeriksaan baru pada array `$requiredExtensions` dalam fungsi `runDiagnostics()`.
2. **Kustomisasi Landing Page (`frontend/src/routes/+page.svelte`):**
   - Rujuk katalog 5 preset desain di [doc/prompt.feature.md](file:///D:/laragon/www/hyperion-vault/doc/prompt.feature.md) (Track B).
   - Penggantian maskot Volt dengan mockup/banner produk klien cukup dengan menyunting seksi hero.
3. **Kustomisasi Hak Akses Klien (`backend/config/permissions.php`):**
   - Definisikan peran bisnis klien (misal: `doctor`, `pharmacist`, `auditor`, `cashier`) dan petakan izin granularnya.
   - Lindungi rute API dengan middleware `permission:nama_izin`.

---

## BAGIAN 6: IMPLEMENTASI FRONTEND (SVELTE 5 RUNES PURE JS)

### 6.1. State Store Otentikasi (`frontend/src/lib/state/auth.svelte.js`)
* Menggunakan Svelte 5 runes (`$state`, `$derived`).
* Sinkronisasi awal otomatis dari cookie `auth_jwt` dan `user_role` pada constructor client.
* Decode payload JWT tanpa library pihak ketiga (`parseJwt`).
* Penyediaan fungsi `hasRole(...roles)`, `hasPermission(...perms)`, derived getter `isAdmin`, serta derived array `permissions`.

### 6.2. Komponen UI Modular (`frontend/src/lib/components/ui/`)
1. **[DataTable.svelte](file:///D:/laragon/www/hyperion-vault/frontend/src/lib/components/ui/DataTable.svelte):**
   - Live search multi-kolom instan 100% pada memori browser via Svelte 5 `$derived.by()` (< 0.1 ms latency, 0 konsumsi slot koneksi database).
   - Paginasi lazy-load berbasis chunking (default 10 baris) dengan tombol navigasi dinamis dan rendering DOM ringan.
   - Pemuat terintegrasi menggunakan [ElementLoader.svelte](file:///D:/laragon/www/hyperion-vault/frontend/src/lib/components/ui/ElementLoader.svelte) saat `loading = true`.
   - Menggunakan Svelte 5 snippets (`{#snippet header()}`, `{#snippet row(item)}`).
2. **[Modal.svelte](file:///D:/laragon/www/hyperion-vault/frontend/src/lib/components/ui/Modal.svelte):**
   - Dialog modal dengan penutupan via tombol Esc, backdrop click, dan snippet `footer`.
3. **[Badge.svelte](file:///D:/laragon/www/hyperion-vault/frontend/src/lib/components/ui/Badge.svelte):**
   - Indikator status (`Aktif` / `Ditangguhkan`) dan peran pengguna multi-role.
4. **[PageLoader.svelte](file:///D:/laragon/www/hyperion-vault/frontend/src/lib/components/ui/PageLoader.svelte):**
   - Komponen pemuat halaman ringan murni CSS + SVG tanpa dependensi eksternal.
   - Dual glowing spinning rings dengan lambang lightning bolt pusat.
   - Parameter: `fullscreen` (boolean), `size` (`sm`|`md`|`lg`|`xl`), `text` (keterangan status), `variant` (`primary`|`cyan`|`indigo`), dan `backdrop`.
   - Terintegrasi di [+layout.svelte](file:///D:/laragon/www/hyperion-vault/frontend/src/routes/+layout.svelte) saat alur logout.
5. **[ElementLoader.svelte](file:///D:/laragon/www/hyperion-vault/frontend/src/lib/components/ui/ElementLoader.svelte):**
   - Micro element loader murni CSS keyframes + SVG (`spinner`, `pulse`, `dots`) dengan varian ukuran (`xs`, `sm`, `md`, `lg`) dan warna tema.
   - Digunakan pada tabel data, tombol aksi mutasi, dan Auto-Reject Shield.

### 6.3. Komponen Fitur & Maskot Interaktif
1. **[ArchitectMascot.svelte](file:///D:/laragon/www/hyperion-vault/frontend/src/lib/components/features/mascot/ArchitectMascot.svelte):**
   - Maskot AI Enterprise Architect "Volt".
   - Pure SVG + CSS animation, responsif, floating aura, partikel thruster, mata reaktif 4 mood (`happy`, `excited`, `thinking`, `super`).
   - Tur interaktif 6 topik arsitektur (*FastCGI Dual-Worker*, *Zero-DB Session*, *Svelte 5 Runes*, *Signed JWT & Sanctum*, *Skalabilitas 9000+ Data*, dan *Auto-Detect Setup*).
   - Tersemat langsung pada landing page [+page.svelte](file:///D:/laragon/www/hyperion-vault/frontend/src/routes/+page.svelte).

### 6.4. Komponen Layout & Navigasi
1. **[AppSidebar.svelte](file:///D:/laragon/www/hyperion-vault/frontend/src/lib/components/layout/AppSidebar.svelte):**
   - Menu navigasi bersih: Menu "Beranda" ditiadakan, indikator pill SLO/P99 dihapus, badge versi SemVer `v5.4.0`.
   - Unit tunggal profil pengguna dan tombol logout di footer sidebar.
2. **[AppTopbar.svelte](file:///D:/laragon/www/hyperion-vault/frontend/src/lib/components/layout/AppTopbar.svelte):**
   - Hamburger mobile toggle dan breadcrumbs rute aktif tanpa duplikasi profil/logout.
   - Eliminasi badge statis FastCGI Online untuk menyajikan UI yang bersih dan jujur tanpa vanity metric palsu.
3. **[+layout.svelte](file:///D:/laragon/www/hyperion-vault/frontend/src/routes/+layout.svelte):**
   - Boolean derived `isStandalonePage`: Sidebar disembunyikan 100% pada landing page (`/`) dan login (`/login`).
   - Integrasi `<PageLoader fullscreen={true} />` saat `loggingOut` dengan reset state pasca navigasi login.
   - **Auto-Reject Shield (Zero Document Preview Leak):** Otorisasi rute dihitung secara sinkron via `$derived(canAccessPath($page.url.pathname, authState))`. Jika tidak sah, merender shield peringatan akses terbatas dengan `ElementLoader` dan memicu pengalihan via `goto()`, tanpa pernah merender `{@render children()}`.

### 6.5. Halaman Manajemen Sesi Terpisah (`frontend/src/routes/admin/sessions/+page.svelte`)
* Menyajikan audit sesi perangkat nyata dari registry `user_devices`:
  - Ikon tipe perangkat (Desktop/Mobile), sistem operasi, dan browser.
  - Alamat IP dan User-Agent terpotong dengan judul tooltip penuh.
  - Waktu login dan waktu kadaluarsa terformat lokal.
  - Tombol **Cabut Sesi Ini** per device, **Cabut Akun**, tombol **Auto-Remove Sesi Kadaluarsa**, dan kill-switch global.
  - Mutasi state lokal in-memory: Menghapus baris sesi yang dicabut secara langsung dari memori tanpa re-fetch jaringan `loadSessions()`.

### 6.6. Mutasi State In-Memory pada Manajemen Pengguna (`frontend/src/routes/admin/users/+page.svelte`)
* Seluruh operasi CRUD pengguna (Create, Update, Delete, Toggle Status, Revoke Session) mengadopsi mutasi state lokal langsung:
  - **Create:** `users = [res.user, ...users]`.
  - **Update:** `users[idx] = res.user`.
  - **Delete:** `users = users.filter(u => u.id !== user.id)`.
  - **Toggle Status:** `users[idx] = { ...users[idx], is_active: !users[idx].is_active }`.
  - **Revoke Sessions:** `users[idx] = { ...users[idx], token_version: users[idx].token_version + 1 }`.
* Menghilangkan permintaan jaringan sekunder `loadUsers()`, memangkas durasi eksekusi pengujian E2E sebesar ~39% dan mencegah lonjakan koneksi database.
* Terintegrasi dengan dialog modal ([Modal.svelte](file:///D:/laragon/www/hyperion-vault/frontend/src/lib/components/ui/Modal.svelte)) untuk Tambah Pengguna Baru, Edit Pengguna, Penangguhan status (`Tangguhkan` / `Aktifkan`), dan Pencabutan sesi darurat pengguna.

---

## BAGIAN 7: VERIFIKASI PENGUJIAN OTOMATIS (100% PASS)

### 7.1. Verifikasi Backend (PHPUnit SQLite In-Memory)
Jalankan di `backend`:
```powershell
cd D:\laragon\www\hyperion-vault\backend
php artisan test
```
**Hasil:** `Tests: 7 passed (135 assertions), Duration: ~2.3s (100% Passed)`.

### 7.2. Verifikasi Frontend E2E (Playwright Live Browser di `http://hyperion-vault.test`)
Jalankan di `frontend`:
```powershell
cd D:\laragon\www\hyperion-vault\frontend
npx playwright test
```
**Hasil:** `2 passed (56.7s) (100% Passed)`.
1. **Audit Alur Penuh:** CSRF Cookie -> Form Validation 422 -> Login 200 -> Multiple Roles Badge -> Signed JWT Cookie Verification -> Redirect Dashboard -> Logout Cookie Cleanup.
2. **Audit Dynamic Menu Hiding, Route Guard, Admin User Management & Device Session Management:** Standalone landing page (tanpa sidebar) -> Unauthenticated route guard redirect to `/login` -> Regular user login -> Sidebar navigation checks (Beranda ditiadakan) -> Forbidden route guard check -> Admin login -> User Management CRUD & Toggle Status & Session Revoke -> Real Active Device Session Management audit & per-device revocation & auto-remove stale sessions button.

### 7.3. Verifikasi phpMyAdmin
Kunjungi `http://localhost/phpmyadmin/` $\implies$ **HTTP 200 OK**.

---

## BAGIAN 8: PANDUAN EKSEKUSI JUNIOR PROGRAMMER & MODEL AI HEMAT BIAYA (RUNBOOK REPRODUCIBILITY)

Bagian ini dirancang agar setiap insinyur tingkat pemula (junior programmer) atau model AI hemat token (seperti Gemini Flash Lite, Claude Haiku, GPT-4o-mini) dapat memahami, menjalankan, mereplikasi, dan memperluas sistem ini secara deterministik tanpa risiko melanggar invariansi sistem.

### 8.0. Mental Model Hyperion Vault (Pahami dalam 60 Detik)
1. **Frontend (Svelte 5 Runes SPA):**
   - Berjalan 100% di browser klien. Tidak membutuhkan server Node.js di production (dibangun statis via `@sveltejs/adapter-static` ke `frontend/build/index.html`).
   - Reaktivitas menggunakan runes (`$state`, `$derived`, `$props`). Tidak ada Virtual DOM overhead.
   - Pencarian dan filter tabel dieksekusi 100% di RAM browser via `$derived.by()` di [DataTable.svelte](file:///D:/laragon/www/hyperion-vault/frontend/src/lib/components/ui/DataTable.svelte). Keystroke pengetikan **tidak pernah** mengirim query ke server!
2. **Gateway Universal ([index.php](file:///D:/laragon/www/hyperion-vault/index.php)):**
   - Menjembatani auto-vhost Laragon (`http://hyperion-vault.test`) dan phpMyAdmin tanpa modifikasi konfigurasi Nginx.
   - Otomatis mengarahkan ke [install.php](file:///D:/laragon/www/hyperion-vault/install.php) jika aplikasi belum terpasang.
3. **Backend & Sesi ([Laravel 13 Sanctum](file:///D:/laragon/www/hyperion-vault/backend/routes/api.php)):**
   - **Kunci Anti-Crash:** `SESSION_DRIVER=file` menyimpan sesi di disk NVMe (0 query database). Sesi tidak pernah memakan kuota 25 koneksi MySQL Hostinger.
   - Tabel `user_devices` hanyalah buku tamu (audit registry) yang mencatat IP, Browser, dan masa kadaluarsa hanya saat login/logout.
   - Dual Worker FastCGI (port 9003 & 9004) dengan `PHP_FCGI_MAX_REQUESTS=0` menjamin PHP di Windows tidak pernah mati mendadak (mencegah error 502).

### 8.1. Alur Cepat 1-Langkah: Dari Git Clone Menjadi Sistem Berjalan
```powershell
# 1. Pindah ke direktori proyek (Laragon www)
cd D:\laragon\www\hyperion-vault

# 2. Jalankan installer otomatis (otomatis menginstal Composer & npm jika belum ada)
php install.php install

# 3. Luncurkan worker FastCGI ganda persisten (Port 9003 & 9004)
$env:PHP_FCGI_MAX_REQUESTS = "0"
Start-Process -FilePath "D:\laragon\bin\php\8.3\php-cgi.exe" -ArgumentList "-b 127.0.0.1:9003" -WindowStyle Hidden
Start-Process -FilePath "D:\laragon\bin\php\8.3\php-cgi.exe" -ArgumentList "-b 127.0.0.1:9004" -WindowStyle Hidden

# 4. Luncurkan server frontend Vite
cd frontend
Start-Process -FilePath "cmd.exe" -ArgumentList "/c npm run dev -- --host 127.0.0.1 --port 5173" -WindowStyle Hidden
cd ..

# 5. Akses sistem di peramban
# Buka: http://hyperion-vault.test
# Login Admin: admin@hyperion-vault.test | Password: password123
# Login Regular: user@hyperion-vault.test  | Password: password123
```

### 8.1b. Alur Pembaruan Sistem (Safe Update via CLI & Web)
Jika repositori ditarik versi terbarunya (`git pull origin main`):
```powershell
# Jalankan pembaruan aman 1-langkah (non-destructive)
php install.php update
```
Atau buka peramban di `http://hyperion-vault.test/install.php` dan klik tombol **"Perbarui Sistem Sekarang"**. Sistem otomatis menjalankan migrasi aman `migrate --force`, refresh cache, dan kompilasi bundle frontend.

### 8.2. Alur Menambah Fitur Baru Menggunakan `prompt.feature.md`
Untuk menghasilkan kode dan arsitektur fitur baru yang 100% konsisten:
1. Buka berkas [doc/prompt.feature.md](file:///D:/laragon/www/hyperion-vault/doc/prompt.feature.md).
2. **Pilih Jalur:**
   - **Track A (Fitur Fungsional):** Tempelkan keinginan mentah klien ke template *Raw Business Input*. AI akan otomatis menghasilkan `feature.md` yang memuat PRD, skema 3NF dengan indeks B-Tree, diagram Mermaid ERD & Sequence, kontrak API Sanctum, serta kode Svelte 5 CRUD.
   - **Track B (Landing Page & Desain):** Gunakan template penyesuaian gaya visual dan pilih salah satu dari 5 preset tema enterprise yang tersedia.
3. Selalu patuhi standar palet, tipografi 3-font, dan grid 8pt di [doc/design.md](file:///D:/laragon/www/hyperion-vault/doc/design.md).

### 8.3. Checklist Anti-Melanggar Arsitektur (Bagi Junior & AI)
- [ ] DILARANG mengubah `SESSION_DRIVER` ke `database` (Memicu 1040 Too Many Connections).
- [ ] DILARANG menjalankan `php artisan migrate:fresh` di production (Gunakan `php install.php update`).
- [ ] DILARANG menggunakan TypeScript di frontend (Gunakan pure JavaScript Runes).
- [ ] DILARANG memodifikasi file konfigurasi Nginx Laragon (Gunakan gateway `index.php`).
- [ ] DILARANG query database tanpa batas (`Model::all()`). Wajib gunakan `limit(1000)`.
- [ ] DILARANG memicu query pencarian ke server pada setiap ketikan (Gunakan in-memory filtering Svelte 5).
- [ ] WAJIB konfigurasikan izin kustom di `backend/config/permissions.php` tanpa menambah kolom ke tabel users.
- [ ] WAJIB gunakan `deleteCookie()` dengan varian domain saat logout.
- [ ] WAJIB sediakan toggle eye icon SVG pada setiap input password.

### 8.4. Matriks Penyelesaian 7 Masalah Umum (Troubleshooting Matrix)
| Gejala Masalah | Penyebab Utama | Solusi Instan 1-Baris |
|---|---|---|
| **502 Bad Gateway** di Nginx | Worker `php-cgi.exe` belum jalan atau mati | Jalankan kembali worker dengan `$env:PHP_FCGI_MAX_REQUESTS="0"` di port 9003 & 9004. |
| **419 Page Expired (CSRF)** | Cookie `XSRF-TOKEN` belum diambil klien | Panggil `api.request('/sanctum/csrf-cookie')` sebelum request POST/PUT/DELETE. |
| **401 Unauthorized Berulang** | Kuki kadaluarsa atau `token_version` user berubah | Bersihkan kuki via `authState.clear()` dan login ulang dengan kredensial valid. |
| **SQLite Database Locked** | Kueri tulis simultan melebihi timeout default | Pastikan `PRAGMA journal_mode = WAL;` dan `busy_timeout = 5000` aktif di `.env`. |
| **Halaman Kosong / Port Conflict** | Port 5173 terpakai oleh proses lain | Jalankan Vite dengan flag: `npm run dev -- --host 127.0.0.1 --port 5173`. |
| **Playwright Strict Mode Violation** | Duplikasi teks locator atau nested `<span>` | Pastikan komponen seperti `Badge.svelte` tidak menumpuk `<span>` di dalam `<span>`. |
| **Build Error Versi Node** | Versi Node lokal berbeda dengan engine lock | Konfigurasi `frontend/.npmrc` dengan `engine-strict=false` dan `legacy-peer-deps=true`. |

### 8.5. Verifikasi Standar Kualitas (Quality Gates Wajib Hijau)
Sebelum melakukan commit atau rilis kode baru, wajib memverifikasi tiga gerbang pengujian:
```powershell
# Gate 1: PHPUnit Suite (Wajib 100% Pass: 10 tests, 172 assertions)
cd D:\laragon\www\hyperion-vault\backend && php artisan test

# Gate 2: Svelte Static Build Suite (Wajib 100% Pass: Zero a11y warning, Exit code 0)
cd D:\laragon\www\hyperion-vault\frontend && npm run build

# Gate 3: Playwright E2E UAT (Wajib 100% Pass)
cd D:\laragon\www\hyperion-vault\frontend && npx playwright test
```

### 8.6. Pilar Pengerasan v6.30.0 (Adopsi Best Practice Arsitektur Enterprise)
1. **Garbage Collection Sesi Disk & POIN Defense:**
   - Physical unlinking (`@unlink`) pada file sesi PHP saat deautentikasi dan pencabutan sesi admin.
   - Terminating lifecycle middleware hooks untuk mencegah Laravel menulis ulang session file kosong setelah sesi dihancurkan.
   - Perintah terjadwal `php artisan session:prune-files` untuk membersihkan inode disk otomatis.
2. **Pre-Compressed Asset Delivery & Conditional 304:**
   - Root `index.php` secara otomatis mendeteksi dan mengirim file `.br` (Brotli) dan `.gz` (Gzip) dengan header cache `immutable` untuk aset Vite `_app/immutable/`.
   - ETag conditional response `304 Not Modified` menghemat hingga 95% bandwidth repeat visitor.
3. **In-Flight Request Collapsing & Local Cache:**
   - Client API JavaScript menggabungkan request identik yang terjadi bersamaan (in-flight deduplication map) dan meng-cache request GET selama 30 detik untuk mengurangi beban API.
4. **Universal Dual-Theme & A11y Zero Silent Failure:**
   - Mode Terang (Daylight) dan Mode Gelap (Dark Obsidian) terintegrasi via class `.dark` pada `<html>` dan Tailwind v4 `@custom-variant dark`.
   - Semua elemen tombol interaktif memiliki atribut eksplisit `type="button"` / `type="submit"`, tactile loading state, dan lolos uji kompilasi aksesibilitas Svelte 5 tanpa peringatan.

### 8.7. Arsitektur Deployment Produksi Enterprise (Dual-Engine: FTP & SSH)

```
+---------------------------------------------------------------------------------------------------+
|                        TOPOLOGI DEPLOYMENT PRODUKSI HYPERION VAULT                                |
+---------------------------------------------------------------------------------------------------+
| 1. WORKSTATION (DEVELOPER)                                                                        |
|    - npm run build (Frontend SPA statis di frontend/build/)                                       |
|    - composer install --no-dev --optimize-autoloader                                              |
|                                                                                                   |
|    [JALUR A: FTP STREAM DEPLOY]                   [JALUR B: SSH / CLOUD VPS]                      |
|    Eksekusi: php deploy_ftp.php                   Eksekusi: php deploy_ssh.php / bash deploy_ssh.sh|
|         │                                              │                                          |
|         ▼                                              ▼                                          |
| 2. PENGIRIMAN DATA                                2. PROVISIONING SSH                             |
|    - Kemas staging ke deploy_package.zip (~14MB)  - Git pull origin main                          |
|    - Generate runner deploy_runner.php            - Composer install & Artisan optimize           |
|    - Upload curl stream ke FTP port 21            - Salin aset statis ke public_html/             |
|         │                                              │                                          |
|         ▼                                              ▼                                          |
| 3. EKSEKUSI RUNNER DI SERVER                      3. OWASP HARDENED GATEWAY                       |
|    - Trigger HTTPS: /deploy_runner.php?token=...  - Backend di ~/apps/hyperion-vault/             |
|    - ZipArchive unpack dalam 1-2 detik            - Public Gateway di ~/public_html/index.php     |
|    - chmod 775 storage & database                 - Zero file sensitif (.env/sqlite) di webroot   |
|    - Artisan clear, migrate, config/route cache                                                   |
|    - Self-destruct zip & runner                                                                   |
+---------------------------------------------------------------------------------------------------+
```

#### A. Mekanisme Jalur A: FTP Single-Zip Stream Engine (`deploy_ftp.php`)
- **Masalah Metode Konvensional:** Mengunggah loose files (ribuan file `vendor/` dan aset statis) melalui FTP memicu ribuan TCP handshake berulang. Proses lambat (45–90 menit) dan sering gagal di tengah jalan (*broken pipe*).
- **Solusi Rekayasa:** Seluruh rilis dipaketkan ke dalam 1 file `deploy_package.zip` (~14 MB) dan diunggah dalam **15–30 detik**.
- **Runner Ekstraksi Dinamis (`deploy_runner.php`):**
  1. Token rahasia acak 32-karakter (`bin2hex(random_bytes(16))`) disuntikkan ke dalam file runner sebelum diunggah.
  2. Eksekusi dipicu via HTTPS: `https://domain.com/deploy_runner.php?token=<secret>`.
  3. Memvalidasi token dengan `hash_equals()`.
  4. Mengekstrak zip secara atomik pada disk NVMe server dalam tempo 1 detik.
  5. Menyelaraskan izin direktori (`chmod 0775` untuk `storage`, `bootstrap/cache`, `database` dan `chmod 0664` untuk `database.sqlite`).
  6. Menjalankan perintah artisan produksi: `optimize:clear`, `migrate --force`, `config:cache`, `route:cache`, `view:cache`, `session:prune-files`.
  7. **Self-Destruct Mandiri:** Menghapus `deploy_package.zip` dan `deploy_runner.php` segera setelah sukses, mengembalikan laporan JSON terstruktur ke CLI developer.

#### B. Mekanisme Jalur B: SSH OWASP Hardened Architecture (`deploy_ssh.sh` & `deploy_ssh.php`)
- **Pola Pemisahan Direktori (Hardened Security):**
  - Folder `backend/` diletakkan **DI LUAR** direktori publik web (`/home/username/apps/hyperion-vault/backend/`). Berkas `.env`, `database.sqlite`, dan `storage/` kebal 100% dari celah HTTP langsung.
  - Direktori publik (`/home/username/public_html/`) **HANYA** memuat aset statis hasil kompilasi Vite (`_app/`, `favicon.svg`) dan gateway pintu masuk [`index.php`](file:///D:/laragon/www/hyperion-vault/index.php).
- **Automasi 1-Perintah:**
  - Developer lokal cukup mengeksekusi `php deploy_ssh.php` untuk memicu pipeline SSH jarak jauh.
  - Server administrator dapat menjalankan `bash deploy_ssh.sh --split` secara langsung di terminal Linux/VPS.

---

## BAGIAN 9: ENTERPRISE SECURITY ARCHITECTURE & THREAT MODEL

### 9.1. OWASP Top 10 Mitigation Checklist
Arsitektur Hyperion Vault mengimplementasikan kontrol mitigasi deterministik terhadap kerentanan OWASP Top 10:

| ID Kerentanan | Kategori Ancaman | Kontrol Mitigasi Arsitektural Hyperion Vault |
|---|---|---|
| **A01:2021** | Broken Access Control (BOLA / IDOR) | Seluruh endpoint privat dan mutasi resource WAJIB menggunakan Laravel Policy (`UserPolicy`, `$this->authorize(...)`). Middleware RBAC berlapis (`auth:sanctum`, `user.active`, `permission:`). |
| **A02:2021** | Cryptographic Failures | Password hashing menggunakan bcrypt dengan cost factor aman. Token sesi Sanctum bertanda tangan kriptografis dengan rotasi token version (`token_version`). |
| **A03:2021** | Injection (SQL / Command) | **0 Raw SQL query string concatenation.** Seluruh akses data menggunakan Eloquent ORM atau PDO parameter binding terisolasi. PRAGMA foreign keys diaktifkan. |
| **A04:2021** | Insecure Design | Desain berbasis Specification-Driven Development (SDD). Rate limiting pada autentikasi, safe memory limits (plafon 1000 item), dan timeout terkendali. |
| **A05:2021** | Security Misconfiguration | Debug mode nonaktif di produksi (`APP_DEBUG=false`). Berkas konfigurasi, `.env`, dan database tersimpan di luar web root publik (`~/apps/` vs `public_html/`). |
| **A06:2021** | Vulnerable & Outdated Components | Dependensi PHP dan Node.js terkunci melalui lockfiles (`composer.lock`, `package-lock.json`). Audit otomatis via `composer audit` dan `npm audit`. |
| **A07:2021** | Identification & Authentication Failures | Proteksi brute-force login via rate limiter. Pemutusan sesi fisik `@unlink` saat logout/revokasi perangkat. Larangan keras enumerasi role. |
| **A08:2021** | Software & Data Integrity Failures | Bundel rilis diverifikasi dengan checksum dan token rahasia 32-karakter bertanda tangan (`hash_equals()`). Migrasi non-destructive (`migrate --force`). |
| **A09:2021** | Security Logging & Monitoring Failures | Pencatatan audit trail real-time (`user_devices`, `storage/logs/laravel.log`). Endpoint telemetri kesehatan sistem (`/api/v1/admin/telemetry`). |
| **A10:2021** | Server-Side Request Forgery (SSRF) | Validasi ketat terhadap URL masukan eksternal dengan pembatasan skema `http/https` dan resolusi IP privat nonaktif. |

### 9.2. Kebijakan Session Sanctum & Isolasi Sesi File
1. **Penyimpanan Sesi NVMe Mandatori:**
   - Variabel lingkungan WAJIB `SESSION_DRIVER=file`. DILARANG KERAS `SESSION_DRIVER=database`.
   - File sesi disimpan di `backend/storage/framework/sessions/` dengan izin akses terisolasi (`0700` / `0775`).
2. **Kuki & Header Transpor:**
   - Kuki sesi dikonfigurasi dengan flag `HttpOnly=true`, `SameSite=Lax` (atau `Strict`), dan `Secure=true` di lingkungan produksi HTTPS.
3. **Pembersihan Fisik (Physical Session Unlink):**
   - Saat pengguna logout atau sesi dicabut dari tabel `user_devices`, sistem mengeksekusi `@unlink(storage_path('framework/sessions/' . $sessionId))` seketika di disk NVMe, mencegah eksploitasi session replay dan PHP Object Injection (POIN).

### 9.3. Standar Error Handling RFC 7807 (Problem Details for HTTP APIs)
Seluruh respons kegagalan API backend (HTTP 4xx dan 5xx) WAJIB mengembalikan payload berstruktur RFC 7807:
```json
{
  "type": "https://hyperion-vault.test/errors/unprocessable-entity",
  "title": "Unprocessable Entity",
  "status": 422,
  "detail": "Data yang dikirimkan tidak memenuhi kriteria validasi sistem.",
  "instance": "/api/v1/admin/users",
  "errors": {
    "email": ["Format email tidak valid."]
  }
}
```
**Larangan Kebocoran Informasi Sensitif:**
- DILARANG membocorkan exception trace mentah, nama tabel, query SQL mentah, file system path, atau credential `.env` ke dalam response klien.
- Exception teknis wajib dicatat secara internal ke `storage/logs/laravel.log` dengan correlation ID / request ID unik.

### 9.4. Database Transaction Boundary & Defensive Mutations
1. **Batas Transaksi Atomik Multi-Tabel:**
   - Setiap operasi penulisan yang melibatkan lebih dari satu entitas basis data (misal: pendaftaran pengguna + registrasi device + pencatatan log) WAJIB dibungkus dalam transaksi atomik:
   ```php
   DB::transaction(function () use ($data) {
       // Operasi A
       // Operasi B
       // Audit log
   });
   ```
2. **Kepatuhan ACID:** Jika salah satu langkah gagal, seluruh perubahan di-rollback seketika untuk mencegah status database yang inkonsisten (*partial write corruption*).

---

## BAGIAN 10: ARSITEKTUR VALORA AGENCY OS (CLIENTS, SCOPE, BILLING, PROGRESS & IDENTITY)

### 10.1. Model Domain & Relasi Basis Data 3NF
Valora Agency OS mengintegrasikan seluruh siklus kerja agensi perangkat lunak melalui model relasional 3NF:
1. **`Client` ➔ `ClientProject` (1:N):** Klien instansi dapat memiliki beberapa proyek (Development vs Maintenance).
2. **`ClientProject` ➔ `ProjectRole` (1:N):** Proyek memiliki beberapa peran pengguna dinamis (Super Admin, Manager, Operator, Pelanggan, dll.).
3. **`ProjectRole` ➔ `ProjectMenu` (1:N):** Setiap peran memiliki hierarki menu modul kerja dengan 12 kapabilitas granular (`has_create`, `has_read`, `has_update`, `has_delete`, `has_grafik`, `has_box_dashboard`, `has_import`, `has_export`, `has_filter`, `has_print`, `has_notification`, `has_custom_logic`), PIC teknisi (`pic`, `pic_user_id`), harga modul kustom, catatan harga, dan status penyelesaian bertanggal (`is_done`, `completed_date`).
4. **`ClientProject` ➔ `ProjectDatabaseTable` (1:N):** Skema basis data proyek (@Rp 100.000 / tabel relasional) dengan status penyelesaian migrasi (`is_done`).
5. **`ClientProject` ➔ `ClientProjectPayment` (1:N):** Riwayat transaksi pembayaran termin/cicilan klien.
6. **`ClientProject` ➔ `User` (Pivot `client_project_workers` N:M):** Penugasan staf engineer/pekerja ke dalam proyek.
7. **`MasterBillingRate` ➔ `BillingRateHistory` (1:N):** Katalog tarif standar acuan agensi dengan pencatatan jejak audit (*audit trail*) transparan setiap kali terjadi revisi harga.
8. **`AgencyProfile` (Singleton Row):** Master profil legal agensi (nama legal, NPWP, hotline WhatsApp, alamat operasional, rekening bank BCA & Mandiri, penandatangan resmi, logo data URI/PNG) sebagai Single Source of Truth (SSoT) identitas korporasi.

### 10.2. Mesin Valuasi Biaya Otomatis & Transparansi Keuangan
1. **Kalkulasi Nilai Kontrak Dinamis (`recalculateGrandTotal()`):**
   - Komponen Inti: Landing Page Responsive (`landing_price`), Halaman Login (`login_price`), Halaman Register (`register_price`).
   - Modul Menu: Akumulasi seluruh harga `price` pada `project_menus` yang terdaftar di proyek.
   - Tabel Database: Akumulasi seluruh harga `price` pada `project_database_tables`.
   - Grand Total = Subtotal Komponen Inti + Subtotal Menu Modul + Subtotal Tabel Database.
2. **Sinkronisasi Saldo Terbayar & Piutang (`recalculatePaidAmount()`):**
   - `paid_amount` otomatis disinkronkan dari agregat `sum('amount')` pada tabel `client_project_payments`.
   - Sisa piutang dihitung defensif: `max(0.0, grand_total - paid_amount)`.
3. **Integritas Transaksi Pembayaran:**
   - Pembayaran dilarang melebihi sisa tagihan yang belum terbayar (validasi batas wajar RFC 7807 422).
   - Proyek yang telah lunas 100% diproteksi dari pencatatan pembayaran lebih lanjut.
   - Proyek dengan riwayat pembayaran aktif DILARANG dihapus dari sistem (mengembalikan RFC 7807 422) guna melindungi integritas pembukuan keuangan.

### 10.3. Master Tarif Standar & Proteksi Tarif Inti
Katalog tarif standar membedakan dua kategori:
- **Tarif Sistem Inti (`system_core`):** `landing`, `login`, `register`, `menu_price`, `database_table_price`, dan `maintenance_menu_price`. Tarif-tarif ini diproteksi penuh dan tidak dapat dihapus (`destroy` diblokir) karena merupakan pondasi arsitektur tagihan.
- **Katalog Modul Terdaftar (`registered_module`):** Modul-modul reusable yang dapat didaftarkan, diedit, atau dihapus oleh administrator.
- **Transparansi Perubahan:** Setiap pembaruan tarif mewajibkan alasan perubahan minimal 5 karakter yang dicatat permanen di `billing_rate_histories`.

### 10.4. Ekspor Dokumen Resmi & Integritas Visual (100% Preview Fidelity)
1. **Faktur Invoice & Kuitansi Resmi:**
   - Dilengkapi konversi angka ke teks terbilang bahasa Indonesia murni (`terbilang.js`) tanpa library eksternal.
   - Tampilan cetak profesional (*clean print CSS*) bebas navigasi dan tombol aksi.
2. **Ekspor Microsoft Word (.doc HTML MIME):**
   - Menghasilkan dokumen Word berformat murni MSO HTML yang kompatibel dengan Microsoft Office Word, WPS Office, dan LibreOffice.
   - Penanaman logo instansi berbasis base64 langsung ke dalam dokumen Word (`data:image/png;base64,...` dan MSO VML shapes).
   - Tata letak tabel, kop surat dua kolom, border presisi, rincian biaya, catatan syarat & ketentuan, dan kolom tanda tangan resmi persis 100% sesuai dengan tampilan pratinjau antarmuka web.
3. **Proteksi Formula Injection CWE-1236 pada Ekspor CSV:**
   - Seluruh ekspor CSV (data pengguna, proyek klien) menyertakan UTF-8 BOM (`\xEF\xBB\xBF`) dan sanitasi sel string yang diawali `=`, `+`, `-`, atau `@` dengan penambahan prefix petik tunggal (`'`).

### 10.5. Kesiapan Deployment Produksi (Zero `env()` Invariant)
- **Larangan Pemanggilan `env()` di Direktori `app/`:** Seluruh environment variable (`AUTH_JWT_COOKIE_NAME`, `AUTH_ROLE_COOKIE_NAME`, `AUTH_TOKEN_TTL_MINUTES`, `AUTH_MAX_CONCURRENT_DEVICES`, `DB_MAX_CONNECTIONS`) wajib dipetakan ke berkas konfigurasi `config/auth.php` dan `config/database.php`.
- **Dukungan Caching Konfigurasi:** Menjamin perintah produksi `php artisan config:cache` dan `php artisan route:cache` berjalan 100% tanpa menyebabkan `env()` mengembalikan nilai `null`.
- **Kompilasi Frontend Statis:** Konfigurasi SvelteKit adapter static di `frontend/svelte.config.js` menjamin SPA pre-rendering dengan `fallback: 'index.html'` lulus 100% saat dieksekusi via `npm run build`.

