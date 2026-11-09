# Contoh Nyata Audit & Transformasi Prompt: Before vs After
> Studi kasus nyata peningkatan efektivitas prompt dari rentan-slop (Tier 4) menjadi standar emas internasional (Tier 1).

---

## 1. KASUS BEFORE (PROMPT GENERIK / SLOP-PRONE)

### Teks Prompt Asli:
```text
Tolong buatkan sistem manajemen user dan role di Laravel dan frontend Svelte.
Harus aman dan cepat. Ada tabel user, bisa tambah edit hapus user, terus ada rolenya.
Buatkan kodenya yang lengkap ya, jangan ada error.
```

### Hasil Audit Menggunakan `audit-prompt.ps1`:
```text
=================================================================
   INTERNATIONAL PROMPT ENGINEERING AUDITOR & QUALITY SCORER     
=================================================================
HASIL AUDIT METRIK:
  1. Role & Identity Calibration : 0 / 15
  2. Context Grounding & SSoT    : 0 / 20
  3. Scope Granularity & Anti-Bulk: 8 / 15
  4. Constraint Hardening & Anti-Slop: 0 / 20
  5. Output Schema & Determinism : 0 / 15
  6. Verification & Review Gate  : 0 / 15
-----------------------------------------------------------------
TOTAL SKOR KUALITAS : 8 / 100
STATUS TIER         : Tier 4: Naive / Slop-Prone (KRITIS)
-----------------------------------------------------------------

PROMPT SMELLS / WARNINGS DITEMUKAN:
  ! [SMELL] Terdapat kata sifat subjektif tanpa metrik teknis terukur: "aman dan cepat".
  ! [SMELL] Terdeteksi Bulk Generation request: Meminta backend + frontend + multi-role tanpa batasan granular.

REKOMENDASI PERBAIKAN STRUKTURAL:
  - [Dimensi 1] Definisikan role arsitek sistem dan standar koding (PER Coding Style 2.0).
  - [Dimensi 2] Kunci versi teknologi (Laravel 13, Svelte 5 Runes, Pure JS, SQLite WAL).
  - [Dimensi 3] Pecah tugas menjadi Single Feature Turn (1 Fitur untuk 1 Role).
  - [Dimensi 4] Tambahkan klausul tegas NO_PLACEHOLDER dan mandat 4 Visual State UI.
  - [Dimensi 5] Berikan daftar target file path spesifik.
  - [Dimensi 6] Wajibkan pengujian otomatis (Feature Test) dan Review Gate.
=================================================================
```

---

## 2. KASUS AFTER (STANDAR EMAS INTERNASIONAL / TIER 1 MISSION-CRITICAL)

### Teks Prompt Hasil Refactoring:
```markdown
<role>
Kamu adalah Principal Full-Stack Engineer dan Security Architect.
Kamu wajib mematuhi standar PSR-12, PER Coding Style 2.0, OWASP ASVS Top 10, dan WCAG 2.2 AAA.
Kamu menolak kode separuh jadi, melarang keras penggunaan placeholder, dan menerapkan penanganan error defensif.
</role>

<context>
- Workspace: Valora Vault Enterprise
- Single Source of Truth: `doc/architecture.md` dan `doc/feature.md`
- Backend: Laravel 13, PHP 8.3+, SQLite WAL Mode (`PRAGMA journal_mode=WAL; PRAGMA busy_timeout=5000;`)
- Frontend: 100% Pure JavaScript (ES2024+), Svelte 5 Runes (`$state`, `$derived`, `$props`), Tailwind CSS v4
- Auth: Laravel Sanctum SPA Session-Based (`SESSION_DRIVER=file` MANDATORY; `SESSION_DRIVER=database` FORBIDDEN)
- Query Ceiling: Limit maksimal 1000 item (`limit(1000)`). Pencarian in-memory pada memori browser client (< 0.1ms).
- Error Handling: Standar RFC 7807 (Problem Details for HTTP APIs).
</context>

<system_invariants>
1. [INV-01] NO_PLACEHOLDER: Kode wajib 100% utuh, tuntas, dan siap produksi. DILARANG keras `// TODO`, `/* rest of code */`, atau fungsi stub.
2. [INV-02] ATOMIC_TRANSACTION: Setiap mutasi data multi-tabel wajib dibungkus `DB::transaction(fn() => ...)`.
3. [INV-03] PURE_JS_SVELTE_5: DILARANG menggunakan TypeScript (`.ts`) atau sintaks legasi Svelte 4 (`export let`, `$:`, stores).
4. [INV-04] FULL_STATE_UI: Setiap komponen antarmuka yang mengambil data wajib menangani 4 visual state: Loading (Skeleton), Empty (Ilustrasi & Panduan), Error (Alert RFC 7807 & Tombol Retry), dan Success (DataTable + Form).
5. [INV-05] RFC_7807: Seluruh respons error (4xx & 5xx) wajib mematuhi format RFC 7807 tanpa membocorkan trace internal.
6. [INV-06] POLICY_GUARD: Otorisasi Policy wajib diterapkan pada setiap endpoint untuk mencegah BOLA.
</system_invariants>

<task>
Lakukan implementasi Single Feature Turn:
- **Nama Fitur:** Manajemen Penugasan Role Pengguna (User Role Assignment)
- **Target Role:** Superadmin
- **Cakupan:** Tuntaskan alur pemberian dan pencabutan role terhadap pengguna, validasi input, pencatatan audit log, proteksi otorisasi, dan antarmuka Svelte 5.
</task>

<negative_constraints>
1. DILARANG mengenerate modul lain di luar User Role Assignment (Cegah Bulk Generation).
2. DILARANG menggunakan `Model::all()` tanpa ceiling aman.
3. DILARANG mengabaikan salah satu dari 4 visual state antarmuka.
</negative_constraints>

<output_contract>
Sajikan kode utuh 100% dalam berkas:
1. `app/Http/Requests/AssignUserRoleRequest.php` (Validasi form ketat)
2. `app/Policies/UserRolePolicy.php` (Proteksi RBAC BOLA)
3. `app/Http/Controllers/UserRoleController.php` (Thin controller + `DB::transaction`)
4. `tests/Feature/UserRoleAssignmentTest.php` (3 Skenario test: 200 OK, 422 Invalid Role, 403 Non-Admin)
5. `frontend/src/routes/(app)/users/[id]/roles/+page.svelte` (Svelte 5 Runes murni dengan 4 visual states)
</output_contract>

<verification_protocol>
Sebelum menyatakan tugas selesai:
1. Jalankan `php artisan test --filter=UserRoleAssignmentTest` dan pastikan 100% lulus.
2. Jalankan `npm run build` dan pastikan 0 error / 0 a11y warning.
3. Tampilkan Review Gate Form:
   "Implementasi Fitur User Role Assignment untuk Role Superadmin selesai. Seluruh test lulus 100% dan UI menangani 4 visual state. Apakah ada perbaikan/revisi spesifik yang diinginkan sebelum melangkah ke fitur berikutnya?"
</verification_protocol>
```

### Hasil Audit Ulang Menggunakan `audit-prompt.ps1`:
```text
=================================================================
HASIL AUDIT METRIK:
  1. Role & Identity Calibration : 15 / 15
  2. Context Grounding & SSoT    : 20 / 20
  3. Scope Granularity & Anti-Bulk: 15 / 15
  4. Constraint Hardening & Anti-Slop: 20 / 20
  5. Output Schema & Determinism : 15 / 15
  6. Verification & Review Gate  : 15 / 15
-----------------------------------------------------------------
TOTAL SKOR KUALITAS : 100 / 100
STATUS TIER         : Tier 1: Deterministic Mission-Critical (STANDAR EMAS)
-----------------------------------------------------------------
SELAMAT: Prompt ini telah mematuhi seluruh protokol deterministik internasional!
=================================================================
```
