# Template Kontrak Pengujian Gherkin (Feature Acceptance Criteria)
> Standard: SDD-Enterprise-Deterministic v8.0.0 (3 Skenario Wajib: 200/201, 422, 403).

---

## FITUR: [NAMA FITUR] - PERAN: [NAMA ROLE]

### Skenario 1 (Happy Path - Transaksi Sukses & Mutasi Atomik)
- **Given:** Pengguna telah login dengan peran `[TARGET_ROLE]` dan sesi aktif di peramban.
- **When:** Pengguna mengirimkan payload data valid ke endpoint `POST /api/v1/[resource]` dengan parameter lengkap.
- **Then:** Sistem mengeksekusi mutasi dalam `DB::transaction()`, mencatat log audit, dan mengembalikan respon HTTP 201 Created dengan skema data yang sesuai.

### Skenario 2 (Negative Path - Validasi Input Gagal & Format RFC 7807)
- **Given:** Pengguna mengirimkan payload dengan field wajib bernilai `null` atau format data tidak valid.
- **When:** Permintaan diproses oleh `FormRequest`.
- **Then:** Sistem menolak permintaan dengan respon HTTP 422 Unprocessable Entity, menyajikan rincian `application/problem+json` tanpa membocorkan struktur internal database.

### Skenario 3 (Security Path - Otorisasi Ditolak & Pencegahan BOLA)
- **Given:** Aktor lain yang tidak memiliki izin hak kelola mencoba mengakses atau memutasi resource ini.
- **When:** Otorisasi diuji oleh `Policy` (`$user->can('...')`).
- **Then:** Sistem memblokir akses dan mengembalikan respon HTTP 403 Forbidden.
