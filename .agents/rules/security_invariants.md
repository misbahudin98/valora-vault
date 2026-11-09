# Universal Security & Hardening Invariants (.agents/rules/security_invariants.md)
> **Engine:** Valora Vault v6.31.0 / v8.0.0 SDD-Enterprise-Deterministic  
> **Standar Rujukan:** RFC 7807, RFC 9457, RFC 8725, RFC 6265bis, RFC 8446, RFC 9116, OWASP Top 10 (2021/2025), OWASP ASVS v4.0.3 (Level 2 Enterprise), NIST SP 800-63B, CIS Critical Security Controls v8, ISO/IEC 27001:2022 Annex A.8.

Invarian ini adalah hukum pertahanan mutlak (*mandatory defensive invariants*) yang wajib divalidasi dan dipatuhi oleh seluruh AI agent, pengembang, dan skrip otomatisasi di seluruh repositori ekosistem Valora Vault.

---

## 10 ATURAN EMAS KEAMANAN WEB & API (GOLDEN SECURITY INVARIANTS)

### [INV-SEC-01] Zero Raw Query & Parameter Binding Mandate (OWASP A03 Injection)
- **Larangan Keras:** Dilarang keras menulis query mentah SQL menggunakan penggabungan string (`$sql = "... WHERE id = " . $id` atau `DB::raw("... $variable ...")`).
- **Kewajiban Mutlak:**
  - 100% kueri database wajib memanfaatkan Eloquent ORM, Query Builder berparameter, atau PDO binding eksplisit (`DB::select('SELECT * FROM users WHERE email = ?', [$email])`).
  - Setiap parameter input wajib melalui tipe data tervalidasi sebelum masuk ke query engine.
  - Plafon aman (*Safe Query Ceiling*): Setiap kueri jamak tanpa paginasi wajib dibatasi maksimal 1000 item (`->limit(1000)`); dilarang `Model::all()` terbuka.

### [INV-SEC-02] Broken Object Level Authorization (BOLA/IDOR) Zero Tolerance (OWASP A01)
- **Larangan Keras:** Dilarang mempercayai input ID klien (`santri_id`, `user_id`, `halaqah_id`, `kamar_id`) tanpa memverifikasi hak kepemilikan dan relasi organisasi aktor yang sedang login.
- **Kewajiban Mutlak:**
  - Setiap endpoint mutasi (`POST`, `PUT`, `PATCH`, `DELETE`) dan pembacaan data privat (`GET /detail/{id}`) WAJIB diproteksi oleh Laravel Policy (`$this->authorize(...)` atau `Gate::authorize(...)`).
  - Pengecekan Policy wajib memvalidasi hierarki peran (RBAC) dan kepemilikan data antar entitas (*tenant/scoping boundary*).

### [INV-SEC-03] Mass-Assignment Guard & Strict Form Request (OWASP BOPLA)
- **Larangan Keras:** Dilarang menggunakan `$guarded = []` pada seluruh Eloquent Model.
- **Kewajiban Mutlak:**
  - Seluruh model Eloquent wajib mendeklarasikan `$fillable` secara eksplisit hanya untuk kolom yang sah diubah pengguna.
  - Setiap mutasi wajib melalui Form Request khusus yang membatasi aturan validasi ketat (`bail`, `required`, tipe data, batas karakter, aturan numerik/format tanggal).
  - Controller wajib mengambil data hanya dari `$request->validated()`, bukan `$request->all()`.

### [INV-SEC-04] RFC 7807 & RFC 9457 Unified Error Representation (Anti-Information Leakage)
- **Larangan Keras:** Dilarang membocorkan jejak kueri database SQL, file paths server, kredensial konfigurasi `.env`, atau nama role internal pada respons kesalahan klien.
- **Kewajiban Mutlak:**
  - Seluruh respons error HTTP (4xx & 5xx) wajib mematuhi format RFC 7807 / RFC 9457:
    ```json
    {
      "type": "https://valora.vault/errors/validation-failed",
      "title": "Unprocessable Entity",
      "status": 422,
      "detail": "Data masukan formulir tidak memenuhi kriteria validasi.",
      "instance": "/api/v1/admin/users",
      "errors": {}
    }
    ```
  - Mode produksi (`APP_DEBUG=false`) wajib menutupi stack trace sistem dan mengalihkannya ke log berkas lokal terenkripsi.

### [INV-SEC-05] Session Driver Isolation & Multi-Device Registry
- **Larangan Keras:** Dilarang menggunakan `SESSION_DRIVER=database` yang membebani koneksi SQL dan rentan *deadlock* transaksi.
- **Kewajiban Mutlak:**
  - Wajib menggunakan `SESSION_DRIVER=file` pada direktori berkecepatan tinggi (`storage/framework/sessions`) dengan mekanisme pembersihan berkas fisik otomatis (*Garbage Collection unlink*).
  - Setiap login perangkat wajib mencatat jejak ke tabel `user_devices` (IP address terpotong aman, User-Agent, platform, browser, waktu aktif terakhir).
  - Mekanisme pembatalan sesi instan (*Granular & Total Revocation*): Mendukung pencabutan sesi per perangkat atau pencabutan massal dengan menaikkan atribut `token_version` pada entitas User.

### [INV-SEC-06] RFC 8725 JWT Best Current Practices & Algorithmic Guard
- **Larangan Keras:** Dilarang mendukung algoritma `none`, algoritma simetris lemah, atau pertukaran kunci HMAC vs Asimetris (*key confusion*).
- **Kewajiban Mutlak:**
  - Token JWT wajib ditandatangani menggunakan algoritma aman minimal `HS256` (HMAC-SHA256) dengan secret key berentropi tinggi ($\ge 256$ bit / 32 karakter acak kriptografis).
  - Token wajib menyertakan klaim standar `iat`, `exp` (maksimal TTL 7 hari untuk token sesi), `sub` (ID pengguna), dan `ver` (token_version).
  - Token yang dipresentasikan wajib memvalidasi kecocokan `ver` terhadap versi aktif pengguna di basis data.

### [INV-SEC-07] RFC 6265bis Cookie Security & CSRF Defense
- **Larangan Keras:** Dilarang menyimpan token autentikasi di `localStorage` atau `sessionStorage` peramban yang rentan dibaca oleh serangan XSS.
- **Kewajiban Mutlak:**
  - Autentikasi sesi berbasis cookie Sanctum wajib menetapkan atribut:
    - `HttpOnly: true` (mencegah akses JavaScript peramban).
    - `Secure: true` pada koneksi produksi HTTPS (opsional fleksibel pada dev lokal HTTP).
    - `SameSite: Lax` atau `Strict` (mencegah penyerangan *Cross-Site Request Forgery*).
  - Seluruh permintaan non-idempotent (`POST`, `PUT`, `DELETE`) wajib menyertakan header token anti-CSRF `X-XSRF-TOKEN`.

### [INV-SEC-08] W3C CSP Level 3 & Defensive Security Headers
- **Larangan Keras:** Dilarang menggunakan `'unsafe-inline'` atau `'unsafe-eval'` pada Content Security Policy produksi tanpa *nonce* atau *hash* kriptografis.
- **Kewajiban Mutlak:**
  - Setiap respons web wajib menyertakan HTTP Security Headers lengkap:
    - `Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; font-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none';`
    - `X-Frame-Options: DENY` (Anti-Clickjacking).
    - `X-Content-Type-Options: nosniff` (Anti-MIME Sniffing).
    - `Referrer-Policy: strict-origin-when-cross-origin`.
    - `Permissions-Policy: camera=(), microphone=(), geolocation=()`.
    - `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload` (HSTS).

### [INV-SEC-09] NIST SP 800-63B Credential & Rate-Limiting Defense
- **Larangan Keras:** Dilarang memberlakukan aturan komposisi sandi kuno yang membingungkan pengguna namun mudah ditebak; dilarang menyimpan sandi dengan enkripsi reversibel atau hash MD5/SHA1.
- **Kewajiban Mutlak:**
  - Sandi wajib di-hash menggunakan algoritma kuat berbiaya adaptif: `Argon2id` (direkomendasikan) atau `Bcrypt` dengan work factor minimal 12.
  - Panjang sandi minimal 8 karakter (disarankan 12 karakter untuk akun berhak akses tinggi).
  - Pembatasan laju (*Rate Limiting*): Endpoint autentikasi `/api/v1/auth/login` wajib menerapkan throttling ketat (maksimal 5 percobaan gagal per menit per IP/akun sebelum *lockout* sementara).

### [INV-SEC-10] Audit Trail Logging & Vulnerability Disclosure (RFC 9116)
- **Larangan Keras:** Dilarang mencatat data sensitif pengguna (kata sandi mentah, token authorization, nomor kartu identitas lengkap) ke dalam log aplikasi.
- **Kewajiban Mutlak:**
  - Setiap mutasi administratif (perubahan status akun, modifikasi hak akses, reset sandi, pencabutan sesi, mutasi data masal) wajib mencatat log audit berstruktur (*Audit Trail*) memuat: `user_id`, `action`, `ip_address`, `timestamp`, dan status hasil.
  - Setiap deployment publik wajib menyediakan kontak pelaporan kerentanan keamanan terstandar RFC 9116 pada berkas `/.well-known/security.txt`.
