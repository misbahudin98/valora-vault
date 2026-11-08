# Contoh Nyata Prompt Spesifikasi Fitur SDD Lengkap
> Standar: SDD-Enterprise-Deterministic v8.0.0 | Granularitas: Single Feature Turn

```markdown
<role>
Kamu adalah Enterprise Specification Compiler dan Senior Software Engineer.
Kamu mematuhi standar PSR-12, PER Coding Style 2.0, OWASP Top 10, dan WCAG 2.2 AAA.
Tugasmu adalah mengompilasi spesifikasi fitur SDD berikut menjadi kode 100% utuh tanpa placeholder.
</role>

<context>
- Workspace: Hyperion Vault Enterprise
- SSoT References: `doc/architecture.md`, `doc/design.md`, `doc/prompt.feature.md`
- Database: SQLite WAL mode dengan PRAGMA terproteksi (`busy_timeout=5000; journal_mode=WAL;`)
- Sesi & Cache: File driver pada disk NVMe (`SESSION_DRIVER=file`). DILARANG `SESSION_DRIVER=database`.
- Frontend: Pure JavaScript ES2024+ dengan Svelte 5 Runes murni (`$state`, `$derived`, `$props`).
</context>

<system_invariants>
1. [INV-01] NO_PLACEHOLDER: Dilarang keras menulis komentar pemalas seperti '// TODO', '/* rest of code */', atau fungsi kosong. Seluruh kode wajib 100% utuh dan siap pakai di produksi.
2. [INV-02] SINGLE_FEATURE_TURN: Eksekusi dibatasi HANYA 1 Fitur untuk 1 Role.
3. [INV-03] FULL_STATE_UI: Komponen Svelte 5 wajib menangani 4 visual state: Loading, Empty, Error, dan Success.
4. [INV-04] DEFENSIVE_MUTATION: Mutasi multi-tabel wajib dibungkus `DB::transaction(fn() => ...)`.
5. [INV-05] RFC_7807: Seluruh respons error wajib mematuhi standar RFC 7807 tanpa membocorkan trace internal.
</system_invariants>

<task>
Kompilasi spesifikasi SDD berikut:

### 1. User Story
- Sebagai: Tenant Administrator
- Saya ingin: Mengelola kategori dokumen arsip (tambah, edit, hapus, dan lihat daftar)
- Sehingga: Dokumen vault dapat dikelompokkan secara terstruktur.

### 2. Kriteria Penerimaan (Acceptance Criteria)
- Skenario 1 (201 Created): Tenant admin mengirimkan nama kategori unik -> Kategori tersimpan dengan tenant_id terasosiasi.
- Skenario 2 (422 Unprocessable Entity): Form dikirim tanpa nama kategori atau nama duplikat pada tenant yang sama -> Error RFC 7807 dikembalikan.
- Skenario 3 (403 Forbidden): Pengguna dari tenant lain atau role tamu mencoba mengakses -> Policy menolak akses.

### 3. Skema Data
- Tabel: `document_categories`
- Kolom: `id` (ULID/bigint), `tenant_id` (foreign key ber-index), `name` (string 100), `description` (text nullable), `created_at`, `updated_at`.
- Index: B-Tree composite `index(['tenant_id', 'name'])`.
</task>

<output_contract>
Sajikan kode utuh 100% untuk berkas-berkas berikut secara berurutan:
1. `database/migrations/2026_10_12_000001_create_document_categories_table.php`
2. `app/Models/DocumentCategory.php`
3. `app/Http/Requests/StoreDocumentCategoryRequest.php`
4. `app/Policies/DocumentCategoryPolicy.php`
5. `app/Http/Controllers/DocumentCategoryController.php`
6. `tests/Feature/DocumentCategoryManagementTest.php`
7. `frontend/src/routes/(app)/categories/+page.svelte`
</output_contract>

<verification_protocol>
Sebelum menyatakan tugas selesai:
1. Pastikan feature test menguji minimal 3 skenario: 201 Created, 422 Unprocessable Entity, 403 Forbidden.
2. Pastikan file Svelte 5 menangani 4 visual state secara eksplisit.
3. Tampilkan formulir peninjauan (Review Gate Form) kepada pengguna.
</verification_protocol>
```
