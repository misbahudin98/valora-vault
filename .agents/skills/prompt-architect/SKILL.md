---
name: prompt-architect
description: >-
  Audits, evaluates, refines, and synthesizes international-grade AI prompts based on
  global best practices (Anthropic XML, OpenAI Delimited, DeepMind Framing) and
  enterprise deterministic invariants (Anti-Slop, SDD, 4-Visual-State UI, Zero-Hallucination).
  Explicitly triggered by tags '@prompt', '@pa', '@prompt-architect', '#prompt', '#pa', or whenever the user
  asks to review, evaluate, score, optimize, or craft a prompt.
---

# Prompt Architect: International Prompt Engineering & Audit Engine
> **Standar Internasional:** Anthropic Constitutional Prompting, OpenAI Developer Standards, DeepMind Cognitive Framing, dan SDD-Enterprise-Deterministic v8.0.0.

Keahlian (*skill*) ini dirancang khusus dari seluruh siklus eksekusi nyata di terminal PowerShell bersama manusia dan agen koding untuk:
1. **Meninjau & Mengaudit Prompt (Audit Mode):** Menguji keefektifan prompt draf terhadap 6 Dimensi Kualitas Internasional dan mendeteksi kerentanan kode malas (*code slop*), halusinasi, dan bias model.
2. **Menghasilkan Prompt Berkualitas Internasional (Synthesis Mode):** Mengompilasi prompt siap pakai berstandar emas dengan isolasi XML, invariant sistem mutlak, dan kriteria verifikasi deterministik.

---

## 1. PANDUAN REFERENSI CEPAT (PROGRESSIVE DISCLOSURE)

Untuk pendalaman materi, rujuk berkas modular berikut:
- **Rubrik Penilaian Lengkap (0-100):** [`references/international_rubric.md`](./references/international_rubric.md)
- **Arketipe & Blueprint Siap Pakai:** [`references/prompt_archetypes.md`](./references/prompt_archetypes.md)
- **Invariant & Pelajaran Siklus PowerShell:** [`references/powershell_sdd_invariants.md`](./references/powershell_sdd_invariants.md)
- **Skrip Auditor Otomatis (PowerShell):** [`scripts/audit-prompt.ps1`](./scripts/audit-prompt.ps1)
- **Skrip Compiler Prompt (PowerShell):** [`scripts/compile-prompt.ps1`](./scripts/compile-prompt.ps1)
- **Studi Kasus Before vs After:** [`examples/prompt_audit_before_after.md`](./examples/prompt_audit_before_after.md)

---

## 2. WORKFLOW 1: MENINJAU KEEFEKTIFAN PROMPT (AUDIT & BENCHMARK MODE)

Gunakan alur kerja ini ketika pengguna memberikan prompt yang ingin dicek, dinilai, atau disempurnakan.

### Langkah 1: Ekstraksi & Deteksi Scope
Identifikasi elemen dasar dari prompt yang dianalisis:
1. Apakah target model ditentukan? (Claude, Gemini, GPT-4o, DeepSeek, dsb.)
2. Apa tugas utama yang diminta?
3. Apakah ada aroma *Bulk Generation* (meminta terlalu banyak hal dalam 1 giliran)?

### Langkah 2: Evaluasi 6 Dimensi Internasional (Total: 100 Poin)
Beri skor untuk setiap dimensi berdasarkan [`references/international_rubric.md`](./references/international_rubric.md):
- **Dimensi 1: Role & Persona Calibration (Bobot: 15)**  
  Apakah model diberikan otoritas domain, standar koding (misal: PER 2.0 / PSR-12), dan mentalitas defensif?
- **Dimensi 2: Context Grounding & SSoT (Bobot: 20)**  
  Apakah ada rujukan SSoT dokumen nyata, file path, skema DB, atau batasan infrastruktur server?
- **Dimensi 3: Scope Granularity & Anti-Bulk (Bobot: 15)**  
  Apakah mematuhi prinsip *Single Feature Turn* (1 Fitur untuk 1 Role), atau terjebak dalam *Bulk Sprawl*?
- **Dimensi 4: Constraint Hardening & Anti-Slop (Bobot: 20)**  
  Apakah ada aturan keras `NO_PLACEHOLDER`, larangan komentar pemalas (`// TODO`, `/* rest of code */`), mandat 4 Visual State (Loading, Empty, Error, Success), serta proteksi visual 3-Tier Surface Elevation (anti-pseudo-glassmorphism / anti-visual-camouflage)?
- **Dimensi 5: Output Schema & Contract Determinism (Bobot: 15)**  
  Apakah format keluaran terkunci secara presisi (target file path lengkap, blok XML terisolasi, kepatuhan RFC 7807)?
- **Dimensi 6: Verification, Self-Correction & Review Gate (Bobot: 15)**  
  Apakah ada kriteria pengujian terukur (Feature Test 200/422/403, build pass 0 warning) dan checkpoint persetujuan manusia (*Human-in-the-Loop*)?

### Langkah 3: Eksekusi Tool Auditor (Opsional / Helper)
Jalankan skrip PowerShell untuk audit otomatis:
```powershell
powershell -ExecutionPolicy Bypass -File ".agents/skills/prompt-architect/scripts/audit-prompt.ps1" -PromptText "<TEKS_PROMPT_PENGGUNA>"
```

### Langkah 4: Sajikan Diagnostic Scorecard
Sajikan hasil peninjauan dalam format tabel skor standar internasional:
```markdown
### 📊 HASIL AUDIT PROMPT INTERNASIONAL
- **Skor Total:** [X] / 100
- **Maturity Tier:** [Tier 1: Gold / Tier 2: Enterprise / Tier 3: Basic / Tier 4: Slop-Prone]

| Dimensi Kualitas | Skor | Status | Catatan Kritis |
| :--- | :---: | :---: | :--- |
| 1. Role & Identity Calibration | X/15 | ⚠️/✅ | [Catatan] |
| 2. Context Grounding & SSoT | X/20 | ⚠️/✅ | [Catatan] |
| 3. Scope Granularity & Anti-Bulk | X/15 | ⚠️/✅ | [Catatan] |
| 4. Constraint Hardening & Anti-Slop | X/20 | ⚠️/✅ | [Catatan] |
| 5. Output Schema & Determinism | X/15 | ⚠️/✅ | [Catatan] |
| 6. Verification & Review Gate | X/15 | ⚠️/✅ | [Catatan] |

#### ⚠️ Kerentanan / Prompt Smells Terdeteksi:
- [Sebutkan kelemahan spesifik: kata sifat subjektif, ketiadaan negative constraints, dll.]

#### 💡 Rekomendasi Peningkatan:
- [Sebutkan 3-5 poin konkret untuk menaikkan skor ke Tier 1]
```

---

## 3. WORKFLOW 2: MENGHASILKAN PROMPT BERKUALITAS INTERNASIONAL (SYNTHESIS MODE)

Ketika pengguna meminta prompt baru atau meminta prompt lama dioptimalkan ke standar internasional, susun prompt menggunakan **Arketipe XML-Delimited Anthropic & Enterprise SDD**:

### Template Standar Struktur Prompt (International Gold Standard):
```markdown
<role>
Kamu adalah [NAMA_PERAN_SPESIFIK] tingkat Enterprise.
Kamu menerapkan standar [PSR-12 / PER Coding Style 2.0 / OWASP Top 10 / WCAG 2.2 AAA].
Kamu bersikap defensif, menolak penulisan kode separuh jalan, dan selalu menghasilkan kode 100% utuh tanpa placeholder.
</role>

<context>
- Workspace / Lingkungan: [NAMA_PROJEK]
- Single Source of Truth (SSoT): [PATH_FILE_SSOT]
- Arsitektur & Database: [FRAMEWORK_VERSI, DB_CONFIG_INVARIANTS]
- Frontend Stack: [SVELTE_5_RUNES, TAILWIND_V4, PURE_JAVASCRIPT]
- Format Error & Auth: [RFC 7807, SESSION_DRIVER=file]
- In-Memory Limits: [Safe query ceiling limit(1000)]
</context>

<system_invariants>
1. [INV-01] NO_PLACEHOLDER: Kode WAJIB 100% utuh, tuntas, lulus pengujian, siap produksi. DILARANG keras '// TODO', '/* rest of code */', atau fungsi stub kosong.
2. [INV-02] SINGLE_FEATURE_TURN: Eksekusi kode HANYA 1 Fitur untuk 1 Role dalam satu kali giliran kerja.
3. [INV-03] FULL_STATE_UI: Setiap antarmuka data WAJIB menangani 4 visual state: Loading, Empty, Error, dan Success.
4. [INV-04] DEFENSIVE_MUTATION: Mutasi multi-tabel WAJIB dibungkus 'DB::transaction(fn() => ...)'.
5. [INV-05] PURE_JS_SVELTE_5: Dilarang TypeScript (.ts); gunakan Svelte 5 Runes modern ($state, $derived, $props).
6. [INV-06] POLICY_GUARD: Otorisasi Policy wajib diterapkan pada setiap resource untuk mencegah BOLA (Broken Object Level Authorization).
7. [INV-07] NO_ALIAS: DILARANG membuat alias nama, alias mapping, wrapper alias, atau sinonim buatan untuk peran, izin, rute, method, tabel, kolom, atau komponen. Gunakan penamaan kanonikal eksplisit (SSoT).
8. [INV-08] NO_UNAUTHORIZED_CONSOLIDATION: DILARANG mengonsolidasi sepihak (menggabungkan peran, controller, komponen, atau tabel 3NF) tanpa instruksi tertulis pengguna.
9. [INV-09] ZERO_REGRESSION_EXECUTION: Setiap eksekusi prompt WAJIB menjamin 100% bebas dari regresi dan TIDAK menimbulkan bug/masalah baru pada sistem.
</system_invariants>

<task>
Lakukan implementasi tuntas untuk:
- Nama Fitur: [NAMA_FITUR]
- Target Role: [NAMA_ROLE]
- Deskripsi Fungsional: [SPESIFIKASI_USER_STORY_DAN_KRITERIA_PENERIMAAN]
</task>

<negative_constraints>
1. DILARANG mengenerate modul atau role lain di luar spesifikasi di atas (Anti-Bulk).
2. DILARANG menggunakan Model::all() tanpa batas limit.
3. DILARANG menghasilkan UI tanpa handling Empty State atau Error State.
4. DILARANG membuat alias penamaan atau kamus pemetaan alias yang mengaburkan nama asli.
5. DILARANG mengonsolidasi berkas, komponen, atau peran terpisah menjadi mega-bundle monolitik.
6. DILARANG merusak fungsionalitas lama atau memicu bug baru pada sistem yang telah berjalan.
</negative_constraints>

<output_contract>
Sajikan kode utuh 100% per berkas dengan file path eksplisit:
1. `path/to/Migration.php`
2. `path/to/Model.php`
3. `path/to/FormRequest.php`
4. `path/to/Policy.php`
5. `path/to/Controller.php`
6. `path/to/FeatureTest.php` (Minimal 3 skenario: 200/201, 422, 403)
7. `path/to/Component.svelte` (Svelte 5 Runes murni dengan 4 visual states)
</output_contract>

<verification_protocol>
Sebelum menyatakan tugas selesai:
1. Pastikan feature test menguji Happy Path, Form Validation, dan RBAC Policy.
2. Pastikan kompilasi frontend bebas error dan bebas a11y warnings.
3. Sajikan Review Gate Form resmi:
   "Implementasi Fitur [Nama Fitur] untuk Role [Nama Role] selesai. Seluruh test lulus 100% dan UI menangani 4 visual state. Apakah ada perbaikan/revisi spesifik yang diinginkan sebelum melangkah ke fitur berikutnya?"
</verification_protocol>
```

---

## 4. WORKFLOW 3: MENGHASILKAN PROMPT REFAKTORISASI & ZERO-REGRESSION (REFACTOR MODE)

Ketika pengguna meminta prompt untuk audit menyeluruh, pembersihan dead code, konsolidasi duplikasi (DRY), dan perbaikan kualitas pada backend & frontend, susun prompt menggunakan **Arketipe D (Enterprise Refactor & Zero-Regression Remediator)**:

### Format Eksekusi Cepat (CLI PowerShell):
```powershell
powershell -ExecutionPolicy Bypass -File ".agents/skills/prompt-architect/scripts/compile-prompt.ps1" -Mode refactor -WorkspaceName "[NAMA_PROJEK]"
```

### 5 Invariant Kunci Prompt Refaktor:
1. **Zero Regression Guarantee:** Menegaskan secara mutlak bahwa tidak ada rute, modul, atau fitur yang hilang/rusak.
2. **Safe Deletion Check:** Memeriksa 0 inbound reference via `git grep` sebelum menghapus berkas apapun.
3. **Strict Typing PHP 8.3+:** Menegakkan `declare(strict_types=1);` dan casting tipe eksplisit.
4. **WCAG 2.2 AAA Accessibility:** Mengharuskan atribut eksplisit `type="button"` / `type="submit"` pada seluruh elemen `<button>`.
5. **Clean SPA Adapter Build:** Memverifikasi `npm run build` dan `php artisan test` mencapai 100% lulus.

---

## 5. VERIFIKASI DIRI (SELF-VERIFICATION GATE SEBELUM MERESPON PENGGUNA)

Sebelum mengembalikan hasil audit atau prompt kepada pengguna, pastikan:
1. [ ] Apakah prompt hasil rekayasa bebas dari kata sifat subjektif tanpa metrik?
2. [ ] Apakah klausul `NO_PLACEHOLDER` dan `4 Visual States` tertulis tegas?
3. [ ] Apakah ada negative constraints yang mencegah kebiasaan buruk model (bulk sprawl, TypeScript leak, unhandled errors)?
4. [ ] Apakah skema output mengunci format secara deterministik?
5. [ ] Apakah ada kriteria verifikasi pengujian otomatis dan Review Gate (Human-in-the-Loop)?

