<#
.SYNOPSIS
    International Prompt Compiler & Synthesizer (PowerShell v7+)
.DESCRIPTION
    Menghasilkan prompt deterministik berstandar internasional lengkap dengan isolasi
    XML tag, negative constraints, dan invariant enterprise.
    Mendukung dua mode: 'feature' (Implementasi Fitur Granular) dan 'refactor' (Audit & Refaktor Zero-Regression).
.PARAMETER Mode
    Pilihan mode kompilasi: 'feature' (default) atau 'refactor'.
.PARAMETER FeatureName
    Nama fitur yang ingin diimplementasikan (misal: "Manajemen Dokumen Legal"). Diperlukan jika Mode = 'feature'.
.PARAMETER TargetRole
    Role aktor pengguna (misal: "Staff Legal"). Diperlukan jika Mode = 'feature'.
.PARAMETER WorkspaceName
    Nama workspace / aplikasi target (misal: "Yayasan PQTM" atau "Valora Vault").
.PARAMETER SSoTDoc
    Dokumen SSoT referensi (misal: "doc/architecture.md, doc/feature.md").
.PARAMETER OutputFile
    Path berkas tempat prompt akan disimpan (opsional).
.EXAMPLE
    .\compile-prompt.ps1 -Mode feature -FeatureName "Audit Log Export" -TargetRole "Superadmin"
    .\compile-prompt.ps1 -Mode refactor -WorkspaceName "Yayasan PQTM" -OutputFile "prompt_refactor.md"
#>

[CmdletBinding()]
param(
    [Parameter(Mandatory = $false)]
    [ValidateSet("feature", "refactor")]
    [string]$Mode = "feature",

    [Parameter(Mandatory = $false)]
    [string]$FeatureName,

    [Parameter(Mandatory = $false)]
    [string]$TargetRole,

    [Parameter(Mandatory = $false)]
    [string]$WorkspaceName = "Valora Enterprise Ecosystem",

    [Parameter(Mandatory = $false)]
    [string]$SSoTDoc = "doc/architecture.md, doc/design.md, AGENTS.md, GEMINI.md, llms.txt",

    [Parameter(Mandatory = $false)]
    [string]$OutputFile
)

if ($Mode -eq "feature") {
    if ([string]::IsNullOrWhiteSpace($FeatureName)) {
        $FeatureName = "Fitur Mandiri Enterprise"
    }
    if ([string]::IsNullOrWhiteSpace($TargetRole)) {
        $TargetRole = "Administrator"
    }

    $promptTemplate = @"
<role>
Kamu adalah Enterprise Full-Stack Architect dan Senior Software Engineer.
Kamu menerapkan standar PER Coding Style 2.0, PSR-12, OWASP ASVS Top 10, dan WCAG 2.2 AAA.
Kamu bersikap defensif, menolak penulisan kode separuh jalan, dan selalu menghasilkan kode 100% utuh tanpa placeholder.
</role>

<context>
- Workspace: $WorkspaceName
- Single Source of Truth (SSoT): $SSoTDoc
- Backend Stack: Laravel 13, PHP 8.3+, SQLite WAL Mode (`PRAGMA busy_timeout=5000; PRAGMA journal_mode=WAL;`)
- Frontend Stack: 100% Pure JavaScript (ES2024+), Svelte 5 Runes SAJA (`$state`, `$derived`, `$props`), Tailwind CSS v4
- Auth & Session: Laravel Sanctum Session-Based (`SESSION_DRIVER=file` mandatory, zero database connection for sessions)
- Error Protocol: RFC 7807 (Problem Details for HTTP APIs)
- Safe Query Ceilings: Setiap kueri dibatasi maksimal `limit(1000)`
</context>

<system_invariants>
1. [INV-01] NO_PLACEHOLDER: Kode WAJIB 100% utuh, tuntas, lulus pengujian, siap produksi. DILARANG keras `// TODO`, `/* rest of code */`, atau fungsi stub.
2. [INV-02] ATOMIC_TRANSACTION: Setiap mutasi yang melibatkan multi-tabel WAJIB dibungkus `DB::transaction(fn() => ...)`.
3. [INV-03] PURE_JS_SVELTE_5: DILARANG menggunakan TypeScript (`.ts`) atau sintaks legasi Svelte 4 (`export let`, `$:`, stores).
4. [INV-04] FULL_STATE_UI: Setiap komponen antarmuka yang mengambil data WAJIB menangani 4 visual state: Loading, Empty, Error, dan Success.
5. [INV-05] RFC_7807: Seluruh respons error (4xx & 5xx) wajib mematuhi standar RFC 7807 tanpa membocorkan trace internal.
6. [INV-06] POLICY_GUARD: Seluruh manipulasi data privat wajib divalidasi oleh Laravel Policy untuk mencegah BOLA (Broken Object Level Authorization).
</system_invariants>

<task>
Lakukan implementasi granular (Single Feature Turn) untuk unit kerja berikut:
- **Nama Fitur:** $FeatureName
- **Target Role:** $TargetRole
- **Tanggung Jawab:** Selesaikan siklus hidup end-to-end tanpa merambah ke fitur atau role lain.
</task>

<negative_constraints>
1. DILARANG mengenerate modul, tabel, atau role lain di luar spesifikasi $FeatureName untuk $TargetRole.
2. DILARANG menggunakan `Model::all()` tanpa limit ceiling aman (`limit(1000)`).
3. DILARANG menyimpan sesi di database (`SESSION_DRIVER=database` is forbidden).
4. DILARANG menghasilkan UI tanpa handling state data kosong (*Empty State*) atau state gagal (*Error State*).
</negative_constraints>

<output_contract>
Sajikan hasil kode dalam urutan berkas lengkap berikut:
1. ``database/migrations/[timestamp]_create_[table]_table.php`` (Migrasi lengkap + B-Tree index)
2. ``app/Models/[Model].php`` (Model Eloquent + `$fillable` + casts + relations)
3. ``app/Http/Requests/[Model]Request.php`` (Validasi form ketat)
4. ``app/Policies/[Model]Policy.php`` (Otorisasi RBAC pencegah BOLA)
5. ``app/Http/Controllers/[Model]Controller.php`` (Thin controller + ``DB::transaction``)
6. ``tests/Feature/[Model]Test.php`` (Feature test: 200/201 Happy Path, 422 Validation Error, 403 Forbidden)
7. ``frontend/src/routes/(app)/[feature]/+page.svelte`` (Svelte 5 Runes murni dengan 4 visual states & in-memory search)
</output_contract>

<verification_protocol>
Setelah seluruh kode selesai ditulis:
1. Verifikasi backend test: Pastikan skenario test mencakup 200, 422, dan 403.
2. Verifikasi UI: Pastikan komponen menangani state Loading, Empty, Error, dan Success.
3. Review Gate Form: Sajikan ringkasan perubahan teknis dan tanyakan kepada pengguna:
   "Implementasi Fitur $FeatureName untuk Role $TargetRole selesai. Seluruh test lulus 100% dan UI menangani 4 visual state. Apakah ada perbaikan/revisi spesifik yang diinginkan sebelum melangkah ke fitur berikutnya?"
</verification_protocol>
"@
} else {
    # Refactor Mode
    $promptTemplate = @"
<role>
Kamu adalah Enterprise Principal Full-Stack Architect, Senior Code Auditor, dan Refactoring Specialist.
Kamu menerapkan standar PER Coding Style 2.0 (PSR-12), OWASP ASVS Top 10, WCAG 2.2 AAA, RFC 7807, dan Svelte 5 Native Runes Architecture.
Kamu bersikap defensif, presisi, menolak penulisan kode separuh jalan, dan selalu menghasilkan kode 100% utuh tanpa placeholder (NO_PLACEHOLDER).
Prinsip utama kerjamu adalah ZERO REGRESSION dan ANTI-SLOP: setiap kode yang dirapikan, diringkas, atau dihapus TIDAK BOLEH merusak satu pun fitur, modul, rute, atau tampilan yang sebelumnya berjalan normal bagi pengguna.
</role>

<context>
- Workspace: $WorkspaceName
- Single Source of Truth (SSoT): Rujuk berkas $SSoTDoc.
- Backend Stack: Laravel 13, PHP 8.3/8.4, SQLite WAL Mode (`PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 5000; PRAGMA journal_mode = WAL; PRAGMA synchronous = NORMAL;`), Laravel Sanctum SPA session-based.
- Session & Cache Invariant: `SESSION_DRIVER=file` (pada NVMe disk) dan `CACHE_STORE=file` MANDATORY. Dilarang keras menggunakan `SESSION_DRIVER=database`.
- Safe Query Ceilings: Setiap kueri database dibatasi ceiling aman `limit(1000)`. Dilarang keras `Model::all()` tanpa batas.
- Frontend Stack: Svelte 5 modern, 100% Pure JavaScript (ES2024+), Tailwind CSS v4 (`@tailwindcss/vite`), static SPA adapter (`ssr = false`, `fallback = 'index.html'`).
- In-Memory Search Standard: Pencarian data tabel dieksekusi 100% pada memori browser via Svelte 5 `$derived.by()` di `DataTable.svelte` (< 0.1ms tanpa DB round-trip).
</context>

<system_invariants>
Aturan mutlak yang mengikat seluruh eksekusi tanpa pengecualian:
1. [INV-01] NO_PLACEHOLDER: Kode WAJIB 100% utuh, tuntas, lulus pengujian, dan siap produksi. DILARANG keras menggunakan komentar pemalas atau stub: `// TODO: implement later`, `/* ...rest of code... */`, `// add your logic here`, atau fungsi rintisan kosong.
2. [INV-02] ZERO_REGRESSION_POLICY: Setiap modul, rute, halaman, modal dialog, tab navigasi, dan fitur yang sebelumnya ditampilkan dan normal diakses oleh user TIDAK BOLEH mengalami error, hilang, atau berubah alur bisnisnya. Seluruh kontrak API (HTTP status code, endpoint URL, JSON schema keys) dan prop interface Svelte wajib backward-compatible.
3. [INV-03] SAFE_DELETION_REFERENCE_CHECK: Sebelum menghapus berkas, rute, fungsi, method, atau variabel apapun, WAJIB membuktikan secara forensik bahwa inbound reference = 0 di seluruh repositori (routes, imports, dynamic components, API calls, seeders, tests).
4. [INV-04] FULL_STATE_UI: Setiap komponen antarmuka yang mengambil data WAJIB menangani 4 visual state: Loading State (skeleton loader reaktif via `ElementLoader`), Empty State (ilustrasi + panduan saat data kosong), Error State (pesan error ramah pengguna format RFC 7807 + tombol 'Coba Lagi'), dan Success State (tabel/grid data normal + inline validation).
5. [INV-05] PURE_JS_SVELTE_5: 100% Pure JavaScript. DILARANG keras menggunakan TypeScript (`.ts`, interface, type casting) atau sintaks legasi Svelte 4 (`export let`, `$:`, stores). Gunakan Svelte 5 Runes modern (`$state`, `$derived`, `$props`, `$effect`).
6. [INV-06] DEFENSIVE_MUTATION: Setiap berkas PHP diawali `declare(strict_types=1);`. Setiap mutasi data multi-tabel WAJIB dibungkus `DB::transaction(fn() => ...)`. Kueri SQL mentah wajib menggunakan PDO parameter binding (Cegah SQL Injection).
7. [INV-07] POLICY_GUARD & RFC_7807: Seluruh manipulasi resource wajib divalidasi oleh Laravel Policy untuk mencegah BOLA (Broken Object Level Authorization). Seluruh respons error (4xx & 5xx) wajib mematuhi format RFC 7807 tanpa membocorkan trace internal server.
8. [INV-08] YAGNI_KISS_ARCHITECTURE: Dilarang over-engineering atau menciptakan lapisan abstraksi palsu (puluhan Service/Repository/DTO interfaces yang tidak perlu). Gunakan arsitektur resmi: Thin Controller, Form Request untuk validasi ketat, Eloquent API Resource untuk transformasi data, dan Action Class hanya untuk mutasi multi-tabel yang kompleks.
9. [INV-09] ACCESSIBILITY_REACTIVE_FEEDBACK: Setiap tombol interaktif wajib menyertakan atribut eksplisit `type="button"` atau `type="submit"`, status disabled + spinner loader saat in-flight, toast feedback (`toastState.success` / `error`), dan modal konfirmasi destruktif sebelum aksi hapus data.
</system_invariants>

<task>
Lakukan audit komprehensif, penghapusan kode/file mati (dead code elimination), peringkasan (condensing/refactoring), konsolidasi kode berulang (DRY - Don't Repeat Yourself), dan perbaikan kualitas pada backend Laravel dan frontend Svelte 5 dengan granularitas unit kerja terkontrol (Single Feature Turn / Phased Execution).

Eksekusi WAJIB dijalankan dalam 5 Tahap Deterministik:
- Tahap 1: Audit Forensik & Pemetaan Ketergantungan (Dependency Graphing).
- Tahap 2: Konsolidasi Kode Berulang (Consolidation & DRY).
- Tahap 3: Penghapusan Aman Berkas & Kode Tak Terpakai (Safe Pruning).
- Tahap 4: Peringkasan & Perbaikan Standar (Refactoring & Hardening).
- Tahap 5: Jaminan Zero-Regression & Verifikasi Otomatis (php artisan test 100% pass + npm run build exit code 0).
</task>

<negative_constraints>
1. DILARANG menghapus berkas, method, endpoint, atau komponen tanpa bukti verifikasi pencarian referensi (reference check) di seluruh codebase.
2. DILARANG merombak arsitektur basis data, mengubah nama tabel, menghapus kolom yang masih dipakai, atau merusak skema migrasi yang sudah aktif.
3. DILARANG menggunakan TypeScript (`.ts`) atau sintaks legasi Svelte 4 (`export let`, `$:`, stores).
4. DILARANG mengubah konfigurasi sesi ke database (`SESSION_DRIVER=database` is strictly forbidden; must remain `SESSION_DRIVER=file`).
5. DILARANG menghilangkan atau memotong fitur, tombol aksi, filter pencarian, kolom data, atau informasi yang sebelumnya ditampilkan kepada user.
6. DILARANG menggunakan `Model::all()` tanpa batas query ceiling `limit(1000)`.
7. DILARANG membuat abstraksi over-engineered yang melanggar prinsip KISS & YAGNI.
8. DILARANG meninggalkan komentar pemalas seperti `// TODO`, `/* rest of code */`, atau placeholder apapun.
</negative_constraints>

<output_contract>
Sajikan hasil audit, pembersihan, dan perbaikan secara terstruktur dalam urutan berikut:
1. **Matriks Audit Forensik & Rencana Tindakan:** (Tabel Berkas Mati, Tabel Duplikasi DRY, Matriks Proteksi Regresi).
2. **Daftar Berkas yang Dihapus Secara Aman (0 References Confirmed).**
3. **Kode Utuh Berkas yang Diperbarui / Dikonsolidasikan (100% Production-Ready).**
4. **Laporan Zero-Regression & Verifikasi Sistem:** (Output `php artisan test` dan `npm run build`).
</output_contract>

<verification_protocol>
Sebelum menyatakan tugas selesai:
1. Pastikan tidak ada broken import atau broken route.
2. Jalankan `php artisan test` dan pastikan seluruh test lulus 100%.
3. Jalankan `npm run build` dan pastikan build selesai dengan exit code 0.
4. Verifikasi navigasi & fungsionalitas seluruh fitur dan peran pengguna.
</verification_protocol>
"@
}

if (-not [string]::IsNullOrWhiteSpace($OutputFile)) {
    $promptTemplate | Out-File -FilePath $OutputFile -Encoding UTF8
    Write-Host "Prompt deterministik [$Mode] berhasil dikompilasi dan disimpan ke: $OutputFile" -ForegroundColor Green
} else {
    Write-Output $promptTemplate
}
