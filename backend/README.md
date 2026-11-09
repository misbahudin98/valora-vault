# Valora Vault - Backend Architecture (Laravel 13 Sanctum)

Backend API berkinerja tinggi untuk arsitektur Valora Agency OS dengan latensi P99 < 150ms dan perlindungan *Zero MySQL Connection Exhaustion* di shared hosting maupun VPS.

---

## ⚡ Karakteristik Utama Backend
1. **Zero DB Session Driver (`SESSION_DRIVER=file`)**:
   - Sesi HTTP disimpan di disk NVMe SSD lokal, mengonsumsi 0 slot koneksi database.
   - Mengeliminasi insiden crash `1040 Too Many Connections` pada kuota 25 koneksi MySQL shared hosting.
   - Terminasi sesi memicu `@unlink(storage_path('framework/sessions/' . $sessionId))` fisik secara real-time.
2. **Dual-Layer Hybrid Authentication**:
   - Server: HttpOnly session cookie Sanctum (`laravel_session` + `XSRF-TOKEN`).
   - Client: Signed HMAC-SHA256 JWT cookie (`auth_jwt`) via `APP_KEY` untuk render reaktif instan di browser.
3. **Registry Sesi & Device Granular (`user_devices`)**:
   - Audit real-time multi-device (IP, User-Agent, Device, OS, Expiry).
   - Pencabutan instan per-device, per-user (+1 `token_version`), atau darurat global.
4. **Dynamic RBAC Matrix ([`config/permissions.php`](config/permissions.php))**:
   - 5 Peran kanonikal resmi: `admin`, `manager`, `staff`, `auditor`, `user`.
   - Multi-role disimpan native via array JSON. Dilarang keras konsolidasi atau aliasing.
   - Proteksi route: `Route::middleware('role:admin,manager')` dan `Route::middleware('permission:reports.export')`.
5. **Keamanan Berlapis (OWASP ASVS & ISO 27001)**:
   - Middleware `SecurityHeaders`: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy`, HSTS.
   - Middleware `SanitizeInput`: Perlindungan injeksi payload.
   - Middleware `CheckUserActive`: Inaktivasi instan akun dan pemutusan sesi jika role tidak valid tanpa membocorkan daftar role (Zero Role Enumeration).
6. **Windows Laragon FastCGI Dual-Worker**:
   - Worker ganda di port 9003 & 9004 dengan `$env:PHP_FCGI_MAX_REQUESTS="0"` mencegah 502 Bad Gateway.

---

## 🧪 Eksekusi Pengujian Backend
```bash
php artisan test
```
Semua 73 tests (768 assertions) wajib lulus 100% green.
