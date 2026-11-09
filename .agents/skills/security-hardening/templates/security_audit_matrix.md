# Lembar Kerja Evaluasi Audit Keamanan & Hardening (12 Checkpoints)
> **Target Skor Kelulusan:** $\ge 90\%$ (Minimal 11 dari 12 Checkpoint Lulus Tanpa Temuan Kritis)

---

## 1. IDENTITAS AUDIT
- **Nama Modul / Rilis:** ________________________
- **Repositori Target:** `[Nama / Path Repositori Target]`
- **Tanggal Audit:** YYYY-MM-DD
- **Auditor / DevSecOps:** ________________________

---

## 2. TABEL 12 CHECKPOINTS KEAMANAN KANONIKAL

| No | Checkpoint Keamanan | Standar Rujukan | Status (Pass/Fail) | Bukti Verifikasi / Catatan Teknis |
|:---|:---|:---|:---:|:---|
| 1 | **SQL Injection Zero-Tolerance** | OWASP Top 10 A03 | [ ] | 0 string concatenation dalam query SQL; parameter binding PDO 100%. |
| 2 | **BOLA / IDOR Authorization Guard** | OWASP Top 10 A01 | [ ] | Seluruh endpoint mutasi & detail privat diverifikasi oleh Laravel Policy. |
| 3 | **Mass-Assignment Guard** | OWASP BOPLA | [ ] | Model Eloquent mendefinisikan `$fillable` eksplisit; dilarang `guarded = []`. |
| 4 | **RFC 7807/9457 Error Defense** | RFC 7807, RFC 9457 | [ ] | Respons 4xx/5xx terformat baku tanpa membocorkan trace atau path server. |
| 5 | **Session Driver File Isolation** | Valora Invariant | [ ] | `SESSION_DRIVER=file` aktif; `user_devices` mencatat riwayat perangkat. |
| 6 | **JWT RFC 8725 & Token Revocation**| RFC 8725 | [ ] | HS256 HMAC signature valid; klaim `ver` diverifikasi untuk instant revocation. |
| 7 | **Cookie RFC 6265bis Defense** | RFC 6265bis | [ ] | Cookie bertanda `HttpOnly`, `SameSite=Lax`, dan `Secure` pada mode HTTPS. |
| 8 | **Content-Security-Policy (CSP L3)** | W3C CSP Level 3 | [ ] | Header CSP aktif, `frame-ancestors 'none'`, larangan script pihak ketiga. |
| 9 | **HTTP Security Headers Lengkap** | OWASP Secure Headers | [ ] | `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`. |
| 10 | **NIST SP 800-63B Password & Rate Limit** | NIST SP 800-63B | [ ] | Hashing Argon2id / Bcrypt; rate limiting `throttle:5,1` aktif pada login. |
| 11 | **RFC 9116 security.txt Public** | RFC 9116 | [ ] | Berkas `/.well-known/security.txt` dapat diakses dan formatnya valid. |
| 12 | **Safe Query Ceiling (Plafon 1000)**| Valora Invariant | [ ] | Kueri multi-data memiliki batas `limit(1000)` untuk mencegah DoS memori. |

---

## 3. KALKULASI SKOR KESIAPAN KEAMANAN (SECURITY READINESS SCORE)

$$\text{Skor Kesiapan} = \left( \frac{\text{Jumlah Checkpoint Lulus}}{12} \right) \times 100\%$$

- **Total Lulus:** _____ / 12
- **Persentase Kesiapan:** _____%
- **Keputusan:**
  - [ ] **LULUS (Siap Produksi):** Skor $\ge 90\%$ dan 0 temuan kritis (No SQLi, No BOLA, No Plaintext Password).
  - [ ] **DITOLAK (Perlu Hardening Ulang):** Skor $< 90\%$ atau terdapat minimal 1 temuan celah keamanan kritis.

---

## 4. DAFTAR TEMUAN & LANGKAH REMEDIASI
1. *[Temuan #1]: Deskripsi celah, file terkait, dan langkah perbaikan.*
2. *[Temuan #2]: Deskripsi celah, file terkait, dan langkah perbaikan.*
