# SYSTEM INSTRUCTION: DETERMINISTIC ENTERPRISE FULLSTACK BOOTSTRAPPER (LARAGON ZERO-CONFIG v6.30.0)
Role: Enterprise CTO & Senior Implementation Engineer
Stack: PHP 8.3/8.4 | Laravel 13 + Sanctum | SQLite WAL / MySQL | Svelte 5 (Pure JS, Runes) | Tailwind CSS v4 | Nginx (Laragon Default Auto-VHost)
Domains: Unified Single Domain `http://valora-vault.test` (melayani Frontend & API sekaligus, alias: `localhost`, `127.0.0.1`)
Working Directory: `D:\laragon\www\valora-vault`
Prinsip: Zero Fluff, Hemat Token, SemVer 2.0.0 (6.30.0), Granular Client RBAC & Permission Matrix (`config/permissions.php`), In-Place Safe Updater (`php install.php update`), Urutan Kronologis Anti-Terlewat, Multiple Roles, Tamper-Proof JWT Cookie, Real Device Tracking, Automated Installer (install.php), Session File Disk GC, Brotli/Gzip Root Delivery, Request Collapsing Map, Universal Dual-Theme, Maskot AI Volt, Reusable PageLoader, Skalabilitas 9000+ Data, Zero Nginx Hack, Design System Tokens, Autonomous Feature Engine.

---

### FASE 0: PRE-FLIGHT ENVIRONMENT LOKAL (LAKUKAN SEKALI)

1. **Pastikan Ekstensi SQLite Aktif di PHP (`D:\laragon\bin\php\8.3\php.ini`)**:
   Pastikan baris berikut **tidak** diawali tanda titik koma (`;`):
   ```ini
   extension=pdo_sqlite
   extension=sqlite3
   ```
2. **Perbaiki Listener IPv6 Default Nginx Laragon (`D:\laragon\etc\nginx\sites-enabled\00-default.conf`)**:
   Tambahkan `listen [::]:80 default_server;` agar `http://localhost/phpmyadmin` tidak memicu 404 akibat benturan IPv6:
   ```nginx
   server {
       listen 80 default_server;
       listen [::]:80 default_server;
       server_name localhost;
       root "D:/laragon/www/";
       include "D:/laragon/etc/nginx/alias/*.conf";
   }
   ```
3. **Jalankan Worker Persisten PHP-CGI (Dual Worker 9003 & 9004)**:
   Gunakan `$env:PHP_FCGI_MAX_REQUESTS="0"` agar worker tidak mati otomatis setelah melayani sejumlah request:
   ```powershell
   $env:PHP_FCGI_MAX_REQUESTS = "0"
   Start-Process -FilePath "D:\laragon\bin\php\8.3\php-cgi.exe" -ArgumentList "-b 127.0.0.1:9003" -WindowStyle Hidden
   Start-Process -FilePath "D:\laragon\bin\php\8.3\php-cgi.exe" -ArgumentList "-b 127.0.0.1:9004" -WindowStyle Hidden
   ```
4. **Reload Nginx Laragon**:
   ```powershell
   & "D:\laragon\bin\nginx\nginx-1.22.0\nginx.exe" -p "D:\laragon\bin\nginx\nginx-1.22.0" -s reload
   ```

---

### FASE 1: STRUKTUR DIREKTORI & INISIASI PROYEK

Jalankan perintah berikut di PowerShell:
```powershell
mkdir "D:\laragon\www\valora-vault" -ErrorAction SilentlyContinue
cd "D:\laragon\www\valora-vault"

# 1. Scaffolding Backend Laravel
composer create-project laravel/laravel backend
cd backend
composer require laravel/sanctum
cd ..

# 2. Scaffolding Frontend Svelte 5 (Pure JS Runes) + Tailwind v4
npx sv create frontend --template minimal --no-add-ons --types none
cd frontend
# Pastikan .npmrc dibuat untuk mencegah error versi Node
Set-Content .npmrc "engine-strict=false`nlegacy-peer-deps=true"
npm install
npm install -D @playwright/test @tailwindcss/vite tailwindcss @sveltejs/adapter-static
cd ..
```

---

### FASE 2: BACKEND CONFIGURATION & IMPLEMENTASI LENGKAP

1. **Konfigurasi Environment Backend (`backend/.env`)**:
   ```ini
   APP_NAME=ValoraVault
   APP_ENV=local
   APP_KEY= # Dihasilkan via php artisan key:generate
   APP_DEBUG=true
   APP_URL=http://valora-vault.test
   FRONTEND_URL=http://valora-vault.test
   SANCTUM_STATEFUL_DOMAINS=valora-vault.test,localhost,127.0.0.1

   DB_CONNECTION=sqlite

   SESSION_DRIVER=file
   SESSION_LIFETIME=120
   SESSION_PATH=/
   SESSION_DOMAIN=.valora-vault.test
   SESSION_SECURE_COOKIE=false
   SESSION_HTTP_ONLY=true
   SESSION_SAME_SITE=lax

   # Konfigurasi Kustom Auth & Batas Multi-Device
   AUTH_TOKEN_TTL_MINUTES=120
   AUTH_JWT_COOKIE_NAME=auth_jwt
   AUTH_ROLE_COOKIE_NAME=user_role
   AUTH_MAX_CONCURRENT_DEVICES=3

   CACHE_STORE=file
   QUEUE_CONNECTION=sync
   ```

2. **Inisialisasi File Database SQLite**:
   ```powershell
   cd D:\laragon\www\valora-vault\backend
   php artisan key:generate
   New-Item -ItemType File -Path "database/database.sqlite" -Force
   ```

3. **Isolasi Database Testing In-Memory (`backend/phpunit.xml`)**:
   ```xml
   <env name="DB_CONNECTION" value="sqlite"/>
   <env name="DB_DATABASE" value=":memory:"/>
   ```

4. **Migrasi Tabel Users & Devices**:
   - `0001_01_01_000000_create_users_table.php`:
     ```php
     Schema::create('users', function (Blueprint $table) {
         $table->id();
         $table->string('name')->index();
         $table->string('email')->unique();
         $table->timestamp('email_verified_at')->nullable();
         $table->string('password');
         $table->text('role')->default('["user"]');
         $table->boolean('is_active')->default(true);
         $table->string('status')->default('active');
         $table->unsignedInteger('token_version')->default(1);
         $table->rememberToken();
         $table->timestamps();

         $table->index(['is_active', 'status']);
         $table->index(['created_at']);
     });
     ```
   - `2026_09_24_161716_create_user_devices_table.php`:
     ```php
     Schema::create('user_devices', function (Blueprint $table) {
         $table->id();
         $table->foreignId('user_id')->constrained()->cascadeOnDelete();
         $table->string('session_id')->index();
         $table->string('ip_address', 45)->nullable();
         $table->text('user_agent')->nullable();
         $table->string('device_name')->default('Unknown Device');
         $table->string('device_type')->default('desktop');
         $table->string('platform')->default('Unknown OS');
         $table->string('browser')->default('Unknown Browser');
         $table->unsignedInteger('token_version')->default(1);
         $table->timestamp('last_active_at')->useCurrent();
         $table->timestamp('expires_at')->nullable()->index();
         $table->timestamps();

         $table->index(['user_id', 'last_active_at']);
     });
     ```

5. **Model User & UserDevice**:
   - `backend/app/Models/User.php`: Cast `role` array, helper `hasRole()`, `token_version`, dan relasi `devices()`.
   - `backend/app/Models/UserDevice.php`: Deteksi User-Agent (platform, browser, device_type), relasi `user()`, dan metode `purgeStaleSessions()` berbasis atomic SQL delete.

6. **Service Signed JWT Anti-Tampering (`backend/app/Services/JwtService.php`)**:
   Menghasilkan JWT token standar yang ditandatangani via `hash_hmac('sha256', ..., config('app.key'))`.

7. **Middleware Proteksi Pengguna Aktif & Multi-Device (`backend/app/Http/Middleware/CheckUserActive.php`)**:
   Memeriksa `is_active` dan memeriksa `session('auth_token_version') < user.token_version` untuk terminasi sesi perangkat seketika.

8. **Controller Admin User Management (Full CRUD) (`backend/app/Http/Controllers/Api/V1/Admin/AdminUserController.php`)**:
   Menyediakan fungsi `index()` (dengan proteksi limit aman 1000 dan filter pencarian nama/email serta status untuk skalabilitas 9000+ record), `store()`, `update()`, `destroy()`, `toggleStatus()`, dan `revokeSessions()`.

9. **Controller Admin Active Sessions (`backend/app/Http/Controllers/Api/V1/Admin/AdminSessionController.php`)**:
   Menyediakan fungsi `index()` (membaca registry `user_devices` dengan limit plafon aman), `revokeSession($id)` (cabut 1 sesi per device), `revokeUserSessions($userId)` (cabut semua sesi user), `revokeAllSessions()` (kill-switch global), dan `cleanup()` (trigger pembersihan sesi kadaluarsa via indeks B-Tree).

10. **Routing API (`backend/routes/api.php`)**:
    Endpoint Sanctum CSRF cookie, Auth (login/logout/logout-other-devices/me), Admin User CRUD, Admin Active Device Sessions, dan Version Audit (`GET /api/v1/admin/version`).

11. **Matriks Hak Akses Granular & Middleware RBAC (`backend/config/permissions.php` & `EnsurePermission.php`)**:
    - Konfigurasi peran & hak akses tanpa migrasi skema tabel (`backend/config/permissions.php`).
    - Registrasi alias middleware di `backend/bootstrap/app.php`: `'permission' => \App\Http\Middleware\EnsurePermission::class`.
    - Injeksi klaim `permissions` ke dalam signed JWT cookie dan helper Svelte 5 `$derived.by(() => auth.hasPermission('users.view'))`.

12. **Seeder Database (`backend/database/seeders/DatabaseSeeder.php`)**:
    Membersihkan tabel `UserDevice` dan melakukan seeding akun default:
    ```php
    UserDevice::query()->delete();
    User::updateOrCreate(
        ['email' => 'admin@valora-vault.test'],
        ['name' => 'Valora Admin', 'password' => 'password123', 'role' => ['admin', 'manager'], 'is_active' => true, 'status' => 'active', 'token_version' => 1]
    );
    User::updateOrCreate(
        ['email' => 'user@valora-vault.test'],
        ['name' => 'Pengguna Regular', 'password' => 'password123', 'role' => ['user'], 'is_active' => true, 'status' => 'active', 'token_version' => 1]
    );
    ```

---

### FASE 3: UNIVERSAL GATEWAY ZERO-CONFIG & INSTALLER / UPDATER OTOMATIS

1. **Universal Gateway & Security Headers (`D:\laragon\www\valora-vault\index.php`)**:
   - Menghubungkan Nginx auto-vhost bawaan Laragon langsung ke Laravel dan Vite tanpa custom conf.
   - Menginjeksikan header keamanan perbankan: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`.
   - Mengarahkan rute `/install.php` langsung ke installer mandiri.

2. **Automated Setup, Diagnostic Wizard & Safe Updater (`D:\laragon\www\valora-vault\install.php`)**:
   - Skrip setup 1-klik untuk memeriksa 13 prasyarat sistem, otomatis memasang dependensi Composer & npm jika belum ada, inisialisasi `.env` dengan nama & domain dinamis, generate `APP_KEY`, inisialisasi database SQLite, eksekusi migrasi & seeding, serta membuat berkas lock keamanan `.installed`.
   - Mode Fresh Install: `php install.php install` atau browser: `http://valora-vault.test/install.php`.
   - Mode Safe In-Place Update: `php install.php update`. Menjalankan `migrate --force` non-destruktif (DILARANG `migrate:fresh`), `optimize:clear`, frontend SPA build, dan audit log riwayat versi di `.installed`.

---

### FASE 4: FRONTEND SETUP (SVELTE 5 PURE JS RUNES & MODULAR COMPONENTS)

1. **Tipografi & Favicon SVG Brand (`frontend/src/app.html`)**:
   Google Fonts preconnect untuk 3-Font System: `Inter` (UI), `Outfit` (headings), dan `JetBrains Mono` (tokens, IP). Vektor rounded icon gradient indigo-cyan dengan lambang lightning bolt putih ditautkan pada `<link rel="icon" type="image/svg+xml" href="/favicon.svg" />` di `frontend/src/app.html`.
2. **Komponen UI Modular (`src/lib/components/ui/`)**:
   - `DataTable.svelte`: Pencarian instan 100% memori browser (`$derived.by()`) dan paginasi lazy chunking dengan `safeCurrentPage` bebas efek cascade, shortcut keyboard (`/`).
   - `Modal.svelte`: Accessible dialog modal dengan top neon accent bar.
   - `Badge.svelte`: Status (pulsing ping dot) dan role badge renderer dengan single-span rendering (bebas strict mode nested span violation).
   - `PageLoader.svelte`: Komponen pemuat halaman ringan murni CSS + SVG dual spinning rings.
   - `ElementLoader.svelte`: Micro loader murni CSS+SVG (`spinner`, `pulse`, `dots`) untuk tombol, tabel, dan shield layout.
3. **Komponen Fitur & Maskot (`src/lib/components/features/mascot/`)**:
   - `ArchitectMascot.svelte`: Maskot AI "Volt" dengan 4 mood reaktif, animasi SVG+CSS, dan panduan 6 pilar arsitektur.
4. **Komponen Layout (`src/lib/components/layout/`)**:
   - `AppSidebar.svelte`: Navigasi sidebar tanpa Beranda, tombol eksplisit **"Keluar Sesi Ini"** (`#logout-btn`) dengan ikon pintu keluar SVG dan tooltip deskriptif, profil tunggal di footer, badge versi `v6.30.0`.
   - `AppTopbar.svelte`: Mobile hamburger & breadcrumbs Valora Vault dengan SVG Home icon dan chevron separator (bebas dari vanity metric "FastCGI Online").
5. **Layout Induk & Halaman Utama**:
   - `src/routes/+layout.svelte`: Sidebar disembunyikan sepenuhnya pada landing page (`/`) dan login (`/login`), integrasi `<PageLoader />` saat logout, dan **Auto-Reject Shield** sinkron pencegah document preview leak sebelum pengalihan `goto()`.
   - `src/routes/+page.svelte`: Landing page modern terintegrasi dengan `<ArchitectMascot />`.
6. **Halaman Manajemen Terpisah**:
   - `src/routes/admin/users/+page.svelte`: CRUD pengguna, toggle status, pencabutan sesi, query ceiling limit 1000 items, mutasi state lokal in-memory (tanpa re-fetch jaringan `loadUsers()`).
   - `src/routes/admin/sessions/+page.svelte`: Audit sesi multi-device per perangkat (IP Address, User-Agent, Expiration), tombol **Cabut Sesi Ini** per device, **Cabut Akun**, tombol **Auto-Remove Sesi Kadaluarsa**, kill-switch global, dan mutasi state in-memory.
7. **Sistem Desain & Engine Generator Fitur**:
   - `design.md`: Spesifikasi konsep desain visual Universal Dual-Theme (Daylight Mode & Dark Obsidian Mode `#090d16`), tipografi 3-font (Outfit/Inter/JetBrains Mono), grid 8pt, rasio kontras WCAG AAA 14.8:1, konsep landing page, dan panduan kustomisasi wizard instalasi & branding.
   - `prompt.feature.md`: Mesin sintesis otomatis Dual-Track: Track A (Raw Business Input -> PRD + DB Schema + ERD Mermaid + Sequence Flow + Svelte 5 CRUD di `features/`) dan Track B (Landing Page & Design Style Customization + White-Labeling Guide).
   - `features/`: Modul dokumentasi fitur terstruktur (`01_auth_session.md`, `02_user_rbac.md`, `03_system_telemetry.md`).
   - `version_sync.php`: Mesin otomasi sinkronisasi versi SemVer & pembaruan dokumentasi serentak.

---

### FASE 5: VERIFIKASI PENGUJIAN OTOMATIS (100% DETERMINISTIK)

1. **Jalankan Vite Dev Server**:
   ```powershell
   cd D:\laragon\www\valora-vault\frontend
   npm run dev
   ```

2. **Eksekusi Test Backend (SQLite In-Memory)**:
   ```powershell
   cd D:\laragon\www\valora-vault\backend
   php artisan test
   # Target: 7 passed, 135 assertions (100% Passed)
   ```

3. **Eksekusi Test UAT Playwright (Browser Live di `http://valora-vault.test`)**:
   ```powershell
   cd D:\laragon\www\valora-vault\frontend
   npx playwright test
   # Target: 2 passed (Alur Penuh Sanctum + Dynamic Menu Hiding, Route Guard, Admin User CRUD & Device Session Management)
   ```

4. **Verifikasi Safe In-Place Update (CLI)**:
   ```powershell
   cd D:\laragon\www\valora-vault
   php install.php update
   # Target: STATUS: SUCCESS, .installed version history updated
   ```

5. **Verifikasi phpMyAdmin**:
   Buka `http://localhost/phpmyadmin/` -> Wajib return HTTP 200 OK.
