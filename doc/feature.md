# Hyperion Vault - Master Feature Index (v6.31.0)
> **Master Ringkasan Fitur Inti Starter Kit & Indeks Spesifikasi Modular.**  
> Detail spesifikasi mendalam per domain fungsional tersimpan terstruktur di folder [`doc/features/*.md`](features/).  
> Panduan praktis inisiasi dan spawning proyek baru tersedia di [`doc/developer_quickstart_guide.md`](developer_quickstart_guide.md).

---

## 0. PETA DOMAIN & URUTAN BACA FITUR

| # | Domain Fungsional | Berkas Spesifikasi Modular | Tabel DB Terkait | Controller & Middleware Utama |
|---|---|---|---|---|
| **1** | **Autentikasi & Keamanan Sesi Multi-Perangkat** | [`doc/features/01_auth_session.md`](features/01_auth_session.md) | `user_devices`, `personal_access_tokens` | `AuthController`, `CheckUserActive`, `SanitizeInput` |
| **2** | **Manajemen Pengguna & Granular RBAC** | [`doc/features/02_user_rbac.md`](features/02_user_rbac.md) | `users` | `AdminUserController`, `EnsurePermission`, `permissions.php` |
| **3** | **Gateway Performa, Telemetri SLO & Installer** | [`doc/features/03_system_telemetry.md`](features/03_system_telemetry.md) | System Cache, Sessions FS, `.installed` | `AdminSessionController`, `index.php`, `install.php` |
| **4** | **Mode Maintenance & Observabilitas Log Sistem** | [`doc/features/04_maintenance_observability.md`](features/04_maintenance_observability.md) | Storage Logs, Down Config | `AdminSystemController`, `TelemetryController`, `hooks.client.js` |
| **5** | **Feature Blueprint Studio & Spec-to-Code Factory** | [`doc/features/05_blueprint_studio.md`](features/05_blueprint_studio.md) | File System / Markdown SDD | `AdminStudioController`, `AdminStudioTest` |

---

## 1. PRODUCT REQUIREMENTS DOCUMENT (PRD) & OBJEKTIF BISNIS

Hyperion Vault adalah *enterprise fullstack starter kit* siap produksi yang dibangun untuk memecahkan tiga tantangan utama aplikasi web modern di lingkungan shared hosting dan VPS:
1. **Eliminasi MySQL Connection Exhaustion (Error 1040):** Memindahkan penyimpanan session mentah dari database ke NVMe file (`SESSION_DRIVER=file`), menyisakan 0% beban koneksi database untuk siklus request sesi.
2. **Keamanan Sesi Multi-Perangkat & POIN Defense:** Menyediakan audit forensik perangkat fisik (`user_devices`), isolasi sesi multi-device dengan kuota aman (maksimal 3 perangkat), penangkal pemalsuan token via Signed HMAC-SHA256 JWT, serta penghancuran fisik file sesi (`@unlink`) untuk mencegah serangan *PHP Object Injection* (POIN).
3. **Reaktivitas UI Sub-Milidetik:** Menggabungkan kompilasi statis Svelte 5 Runes (`@sveltejs/adapter-static`), *in-flight request collapsing*, memori cache 30 detik pada API client, serta penyaringan instan di memori peramban tanpa query server per ketikan tombol (*zero keystroke latency*).

---

## 2. PERSONA & MATRIKS HAK AKSES (RBAC MATRIX)

Sistem mengadopsi Dynamic Role-Permission Matrix yang terpusat pada [`backend/config/permissions.php`](../backend/config/permissions.php) tanpa membutuhkan tabel pivot database yang memperlambat query:

| Role Code | Nama Peran | Hak Akses Utama | Scope Domain |
|---|---|---|---|
| `admin` | Super Administrator | Wildcard `['*']` (Full CRUD User, Audit Sesi, Cleanup Inode, Reset Kredensial) | Seluruh Sistem |
| `manager` | Operasional / Manajer | `users.view`, `sessions.view`, `dashboard.view` (Monitoring & Supervisi) | Operasional |
| `user` | Pengguna Umum / Member | `dashboard.view`, `profile.edit` (Portal Layanan Mandiri) | Profil Mandiri |

> **Prinsip Multiple Roles:** Satu akun pengguna dapat mengemban lebih dari satu peran sekaligus (misal: `['admin', 'manager']`) yang tersimpan dalam format JSON array terindeks pada tabel `users`. Helper `hasRole()` dan `hasPermission()` bekerja instan di level backend maupun frontend.

---

## 3. KRITERIA PENERIMAAN GLOBAL (ACCEPTANCE CRITERIA)

### AC-G1: Zero Connection Exhaustion pada Beban Puncak
- **Given:** Sistem menerima lonjakan trafik hingga 20 RPS dengan 25 concurrent users di shared hosting.
- **When:** Pengguna melakukan navigasi halaman, pencarian tabel, dan interaksi formulir.
- **Then:** Nilai Little's Law koneksi database tetap $L \le 0.16$ (jauh di bawah batas 25 koneksi), dan latensi P99 $\le 150\text{ ms}$.

### AC-G2: Zero Silent Failure pada Seluruh Elemen Interaktif
- **Given:** Pengguna berinteraksi dengan tombol, modal, atau formulir di antarmuka web.
- **When:** Tombol ditekan atau data disubmit.
- **Then:** Seluruh event handler terikat reaktif, tombol menampilkan state loading/disabled secara visual, atribut `type="button"` / `type="submit"` terpasang eksplisit, dan hasil operasi dilaporkan via toast notification (`toastState`).

### AC-G3: Eliminasi File Sesi Usang (POIN & Inode Protection)
- **Given:** Pengguna logout, akun ditangguhkan, atau sesi dicabut oleh admin.
- **When:** Permintaan terminasi sesi diproses.
- **Then:** Record database `user_devices` dihapus, token Sanctum dicabut, file sesi PHP fisik di-unlink (`@unlink`), dan terminating middleware hook mencegah penulisan ulang file sesi kosong.

---

## 4. PETA ENDPOINT RESTFUL API UTAMA

| Method | Endpoint | Proteksi Middleware | Deskripsi Fungsi |
|---|---|---|---|
| `GET` | `/sanctum/csrf-cookie` | `web` | Pre-warming cookie CSRF Sanctum & inisialisasi sesi |
| `POST` | `/api/v1/auth/login` | `web`, `guest` | Autentikasi kredensial, issue Signed JWT, registrasi perangkat |
| `POST` | `/api/v1/auth/logout` | `auth:sanctum`, `user.active` | Logout, unlink fisik session file, revoke token PAT |
| `GET` | `/api/v1/auth/me` | `auth:sanctum`, `user.active` | Ambil profil pengguna terotentikasi & matriks hak akses |
| `GET` | `/api/v1/admin/users` | `auth:sanctum`, `user.active`, `permission:users.view` | Ambil daftar pengguna terdaftar (limit 1000) |
| `GET` | `/api/v1/admin/users/export` | `auth:sanctum`, `user.active`, `permission:reports.export` | Ekspor seluruh pengguna ke CSV stream dengan overhead memori O(1) |
| `POST` | `/api/v1/admin/users` | `auth:sanctum`, `user.active`, `permission:users.create` | Buat akun pengguna baru dengan multi-role |
| `PUT` | `/api/v1/admin/users/{id}` | `auth:sanctum`, `user.active`, `permission:users.edit` | Perbarui data profil, password, peran, status |
| `POST` | `/api/v1/admin/users/{id}/toggle-status` | `auth:sanctum`, `user.active`, `permission:users.edit` | Switch aktif/tangguh akun pengguna & invalidate token |
| `POST` | `/api/v1/admin/users/{id}/revoke-sessions` | `auth:sanctum`, `user.active`, `permission:users.edit` | Cabut seluruh sesi aktif akun pengguna tertentu |
| `DELETE` | `/api/v1/admin/users/{id}` | `auth:sanctum`, `user.active`, `permission:users.delete` | Hapus akun pengguna, revoke sesi, unlink file |
| `GET` | `/api/v1/admin/sessions` | `auth:sanctum`, `user.active`, `permission:sessions.view` | Audit sesi perangkat login aktif & metrik disk |
| `POST` | `/api/v1/admin/sessions/{id}/revoke` | `auth:sanctum`, `user.active`, `permission:sessions.revoke` | Cabut sesi satu perangkat spesifik |
| `POST` | `/api/v1/admin/sessions/user/{userId}/revoke` | `auth:sanctum`, `user.active`, `permission:sessions.revoke` | Cabut seluruh perangkat milik pengguna tertentu |
| `POST` | `/api/v1/admin/sessions/cleanup` | `auth:sanctum`, `user.active`, `permission:sessions.revoke` | Auto-remove sesi expired & garbage collect disk (KB freed) |
| `POST` | `/api/v1/admin/sessions/revoke-all` | `auth:sanctum`, `user.active`, `permission:sessions.revoke` | Emergency kill-switch seluruh sesi aktif kecuali browser saat ini |
| `GET` | `/api/v1/admin/system/status` | `auth:sanctum`, `user.active`, `role:admin` | Cek status maintenance, metrik server, dan kapasitas log disk |
| `POST` | `/api/v1/admin/system/maintenance` | `auth:sanctum`, `user.active`, `role:admin` | Toggle mode maintenance dengan custom secret bypass URL |
| `GET` | `/api/v1/admin/system/logs` | `auth:sanctum`, `user.active`, `role:admin` | Baca dan filter log backend Laravel (`storage/logs/laravel.log`) |
| `GET` | `/api/v1/admin/system/client-logs` | `auth:sanctum`, `user.active`, `role:admin` | Baca dan filter telemetri error browser klien (`client.log`) |
| `POST` | `/api/v1/admin/system/logs/clear` | `auth:sanctum`, `user.active`, `role:admin` | Kosongkan berkas log backend atau frontend secara aman |
| `POST` | `/api/v1/telemetry/errors` | Public API (`throttle:60,1`) | Ingest telemetri error klien non-blocking dengan PII sanitization |
