# Kompendium Standar Keamanan Web Kanonikal Internasional
> **Engine:** Valora Vault v6.31.0 / v8.0.0 SDD-Enterprise-Deterministic  
> **Standar Rujukan:** IETF RFCs (7807, 8725, 6265bis, 8446, 9116, 9457), OWASP Top 10 (2021/2025), OWASP ASVS v4.0.3, NIST SP 800-63B, CIS Critical Security Controls v8, ISO/IEC 27001:2022 Annex A.8, SOC 2 Type II.

---

## 1. STANDAR INTERNET ENGINEERING TASK FORCE (IETF RFCS)

### 1.1 RFC 7807 & RFC 9457: Problem Details for HTTP APIs
- **Tujuan Kemanan:** Mengeliminasi celah *Information Disclosure* akibat variasi format error yang tidak terstandarisasi atau membocorkan jejak kode internal.
- **Format Kanonikal:**
  ```http
  HTTP/1.1 403 Forbidden
  Content-Type: application/problem+json

  {
    "type": "https://valora.vault/errors/forbidden-access",
    "title": "Forbidden",
    "status": 403,
    "detail": "Anda tidak memiliki wewenang otorisasi Policy untuk mengakses sumber daya ini.",
    "instance": "/api/v1/santri/142",
    "code": "POLICY_DENIED"
  }
  ```
- **Aturan Implementasi:**
  1. `type` berupa URI pengidentifikasi jenis error.
  2. `title` ringkas dan manusiawi (sesuai status HTTP).
  3. `status` mencerminkan kode status HTTP.
  4. `detail` penjelasan spesifik untuk konteks insiden tanpa membocorkan nama tabel atau query SQL.
  5. `instance` rute URI yang memicu masalah.

### 1.2 RFC 8725: JSON Web Token Best Current Practices
- **Algoritma Validasi:**
  - Tolak secara eksplisit token dengan header `{"alg": "none"}`.
  - Cegah serangan *Key Confusion*: Pisahkan kunci HMAC simetris dengan kunci publik/privat RSA/ECDSA.
- **Klaim Standar Wajib:**
  - `sub` (Subject): ID unik entitas pengguna.
  - `iat` (Issued At): Timestamp terbit UTC.
  - `exp` (Expiration Time): Waktu kadaluarsa mutlak (TTL maksimal 7 hari untuk session token).
  - `ver` (Token Version): Integer versi token untuk mendukung instant multi-device revocation.
- **Verifikasi Kriptografi:**
  - Menggunakan kunci rahasia berkekuatan minimal 256-bit entropy (`HS256` atau `EdDSA/ES256`).

### 1.3 RFC 6265bis: Cookies HTTP State Management Mechanism
- **Karakteristik Pertahanan Cookie:**
  - `HttpOnly`: Mencegah pembacaan kredensial sesi via serangan Cross-Site Scripting (XSS).
  - `Secure`: Memastikan cookie hanya dikirimkan melalui saluran terenkripsi TLS/HTTPS.
  - `SameSite`:
    - `Lax`: Default aman untuk aplikasi web transaksional; mencegah pengiriman cookie pada request lintas situs (Cross-Site Request Forgery) dari pihak ketiga.
    - `Strict`: Untuk transaksi perbankan atau mutasi sensitif tinggi.
  - Prefix Perlindungan:
    - `__Host-`: Mengunci cookie pada nama host spesifik, path `/`, dan wajib bertanda `Secure`.
    - `__Secure-`: Mengunci cookie pada saluran HTTPS.

### 1.4 RFC 8446: The Transport Layer Security (TLS) Protocol Version 1.3
- **Fitur Kunci:**
  - Menghilangkan cipher suites lemah yang rentan eksploitasi (RC4, Triple-DES, SHA-1, MD5, export ciphers).
  - Wajib mendukung *Perfect Forward Secrecy* (PFS) berbasis ECDHE (Elliptic Curve Diffie-Hellman Ephemeral).
  - Enkripsi penuh pada proses handshake untuk melindungi identitas sertifikat peramban dari inspeksi ISP/middlebox.

### 1.5 RFC 9116: A File Format to Aid in Security Vulnerability Disclosure (security.txt)
- Setiap sistem produksi wajib menempatkan berkas teks pada path `/.well-known/security.txt`:
  ```text
  Contact: mailto:security@valora-vault.test
  Expires: 2027-12-31T23:59:59.000Z
  Preferred-Languages: id, en
  Canonical: http://valora-vault.test/.well-known/security.txt
  Policy: http://valora-vault.test/security-policy
  ```

---

## 2. OWASP ASVS (APPLICATION SECURITY VERIFICATION STANDARD) LEVEL 2

Valora Vault menargetkan kepatuhan **Level 2 (Applications that handle sensitive transactions / PII)**:

| Domain Verifikasi ASVS | Kontrol Kunci | Implementasi di Valora Vault |
| :--- | :--- | :--- |
| **V1: Architecture** | Pembagian zona kepercayaan dan kontrol BOLA | Laravel Middleware (`auth:sanctum`, `EnsureRole`, `EnsurePermission`) + Laravel Policy |
| **V2: Authentication** | Ketahanan sandi, rate limiting, anti brute-force | Argon2id hashing, throttle middleware 5 req/min, account lockout |
| **V3: Session Management**| Isolasi sesi, anti-fixation, pembatalan instan | `SESSION_DRIVER=file`, multi-device registry `user_devices`, `token_version` invalidation |
| **V4: Access Control** | Least privilege, pencegahan BOLA/IDOR | Policy check pada seluruh model, pengecekan scoping unit/organisasi |
| **V5: Validation & Sanitization** | Form request validation, anti SQLi, XSS defense | FormRequest ketat, sanitasi input middleware, Eloquent parameter binding |
| **V7: Error Handling & Logging** | RFC 7807 problem details, masking data privat | Exception handler format RFC 7807, redaksi kata sandi dari log |
| **V14: Configuration** | Security headers, CSP Level 3, HSTS | Middleware `SecurityHeaders.php`, `.env` production lock |

---

## 3. NIST SP 800-63B (DIGITAL IDENTITY GUIDELINES)

Pedoman modern dari National Institute of Standards and Technology (NIST):

1. **Aturan Sandi Pengguna (Memorized Secrets):**
   - Panjang minimal $\ge 8$ karakter (disarankan 12 karakter untuk administrator).
   - Panjang maksimal minimal $\ge 64$ karakter untuk mengizinkan kalimat sandi (*passphrase*).
   - **Hapus Aturan Usang:** Jangan paksa pengguna memasukkan kombinasi simbol acak yang mustahil diingat atau mengubah sandi setiap 90 hari tanpa ada indikasi kebocoran (karena justru memicu pengguna menulis sandi di kertas/notes).
   - **Pencegahan Sandi Bocor:** Bandingkan sandi baru dengan daftar kata sandi terpopuler (*breached password blacklist*).
2. **Pembatasan Percobaan Masuk (Rate Limiting):**
   - Batasi percobaan otentikasi gagal per akun dan per IP publik untuk menghentikan serangan *credential stuffing*.
3. **Penyimpanan Kredensial:**
   - Gunakan fungsi derivasi kunci berkekuatan tinggi dengan *salt* unik: `Argon2id` (memory-hard) atau `Bcrypt` (work factor $\ge 12$).

---

## 4. CIS CONTROLS V8 & ISO/IEC 27001:2022 ANNEX A.8

### CIS Controls v8 (Critical Security Controls):
- **Control 3 (Data Protection):** Enkripsi data sensitif (PII) menggunakan `APP_KEY` AES-256-CBC.
- **Control 5 (Account Management):** Penonaktifan akun otomatis jika status `is_active = 0`.
- **Control 6 (Access Control Management):** Prinsip *Least Privilege* melalui RBAC dinamis (`backend/config/permissions.php`).
- **Control 8 (Audit Log Management):** Pencatatan jejak mutasi kritis ke dalam file log harian terotasi.
- **Control 16 (Application Software Security):** Pemindaian kerentanan dependensi via `composer audit` dan `npm audit`.

### ISO/IEC 27001:2022 Annex A.8 (Technological Controls):
- **A.8.2 Privileged access rights:** Pembatasan akses superadmin.
- **A.8.3 Information access restriction:** Akses data sesuai peran organisasi.
- **A.8.5 Secure authentication:** Multi-device session tracking dan single-source credentials.
- **A.8.25 Secure development life cycle:** Spesifikasi SDD, pengujian unit test PHPUnit $\ge 3$ skenario (200, 422, 403), dan review gate manusia sebelum rilis.
- **A.8.28 Secure coding:** Penerapan PER Coding Style 2.0, zero warning a11y, zero placeholder.
