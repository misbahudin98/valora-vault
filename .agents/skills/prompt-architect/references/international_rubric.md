# International Prompt Engineering Evaluation & Audit Rubric (IPEEAR v3.0)
> **Governance:** Standardised against Anthropic Prompt Engineering Interactive Tutorial, OpenAI Developer Guides, Google DeepMind System Framing, and SDD-Enterprise-Deterministic.

---

## 1. IKHTISAR SISTEM EVALUASI

Prompt Engineering standar internasional bukan sekadar "menyusun kalimat perintah yang sopan atau menarik". Di level enterprise dan misi kritis, sebuah prompt adalah **spesifikasi teknis (executable specification)** yang mengarahkan *latent space* LLM secara deterministik untuk menghasilkan output yang dapat diverifikasi, bebas halusinasi, dan siap produksi.

Rubrik ini membagi kualitas prompt ke dalam **6 Dimensi Inti (Core Dimensions)** dengan total bobot **100 Poin**.

---

## 2. 6 DIMENSI EVALUASI KUALITAS INTERNASIONAL

```mermaid
radar
  title "Bobot Evaluasi Kualitas Prompt Internasional (Total: 100)"
  "1. Role & Identity Calibration (15%)": 15
  "2. Context Grounding & SSoT (20%)": 20
  "3. Scope Granularity & Anti-Bulk (15%)": 15
  "4. Constraint Hardening & Anti-Slop (20%)": 20
  "5. Output Schema & Determinism (15%)": 15
  "6. Verification & Self-Correction (15%)": 15
```

### Dimensi 1: Role & Persona Calibration (Bobot: 15 Poin)
Menguji apakah model diberikan identitas kognitif, domain otoritas, dan batasan mental yang presisi.
- **0 - 5 Poin (Lemah):** Tidak ada role, atau hanya role generik ("Kamu adalah programmer hebat").
- **6 - 10 Poin (Menengah):** Role spesifik ("Senior Laravel & Svelte 5 Engineer"), namun tanpa ekspektasi mental atau standar koding yang mengikat.
- **11 - 15 Poin (Internasional):** Role presisi lengkap dengan domain otoritas, standar metodologi (misal: *Enterprise Architect strictly abiding by PER Coding Style 2.0 & OWASP Top 10*), dan sikap defensif terhadap kode ceroboh.

### Dimensi 2: Context Grounding & SSoT Integrity (Bobot: 20 Poin)
Menguji apakah prompt menyediakan Single Source of Truth (SSoT), referensi berkas nyata, atau batasan lingkungan sehingga LLM tidak perlu "menebak" atau "berhalusinasi".
- **0 - 7 Poin (Lemah):** Tidak ada konteks sistem. Model dipaksa mengasumsikan struktur database, versi framework, atau konfigurasi server.
- **8 - 14 Poin (Menengah):** Konteks umum diberikan dalam paragraf panjang yang bercampur aduk dengan instruksi tugas.
- **15 - 20 Poin (Internasional):** Konteks terisolasi secara struktural (`<context>` atau delimited markdown), mengutip file path absolut/relatif SSoT, schema eksplisit, limitasi infrastruktur (misal: *Hostinger MySQL Max 25 connection, SQLite WAL mode, NVMe file session*), dan melarang spekulasi.

### Dimensi 3: Scope Granularity & Anti-Bulk (Bobot: 15 Poin)
Menguji apakah prompt mematuhi batas beban kognitif (*cognitive load ceiling*) dan memecah tugas menjadi unit atomik (prinsip *Single Responsibility* / *Single Feature Turn*).
- **0 - 5 Poin (Lemah/Bulk Slop):** Meminta seluruh modul aplikasi (Auth + Multi-Role CRUD + Payment + Admin Dashboard + Landing Page) dalam 1 prompt. Pasti menghasilkan pemotongan kode dan halusinasi.
- **6 - 10 Poin (Menengah):** Meminta 1 modul besar yang masih memiliki multi-tanggung jawab tanpa urutan pengerjaan jelas.
- **11 - 15 Poin (Internasional):** Mematuhi aturan atomik: **1 Fitur untuk 1 Role per turn**, dengan tahapan pipeline modular (Migration -> Model -> FormRequest -> Policy -> Controller -> Test -> Svelte 5 UI).

### Dimensi 4: Constraint Hardening & Anti-Slop Policy (Bobot: 20 Poin)
Menguji kekebalan prompt terhadap kode malas, placeholder, asumsi palsu, dan pelanggaran keamanan.
- **0 - 7 Poin (Lemah):** Tidak ada batasan negatif (*Negative Constraints*). Model bebas menaruh `// TODO: implement later`, `/* ...rest of code... */`, atau `console.log('clicked')`.
- **8 - 14 Poin (Menengah):** Ada larangan umum ("Jangan gunakan placeholder"), tetapi tidak ada sanksi struktural atau definisi eksplisit dari apa yang dilarang.
- **15 - 20 Poin (Internasional):** Invariant eksplisit dan mutlak:
  - `NO_PLACEHOLDER`: Kode wajib 100% utuh, tuntas, lulus pengujian, siap produksi.
  - `NEGATIVE_CONSTRAINTS`: Larangan keras (misal: dilarang TypeScript di frontend Svelte 5, dilarang `SESSION_DRIVER=database`, dilarang raw query tanpa binding).
  - `FULL_STATE_UI`: Wajib menangani 4 visual state (Loading, Empty, Error, Success).
  - `PRO_GRADE_COMPACT_UI`: Desain anti-bloat/anti-sesak (hairline 1px border, shadow-2xs, padding p-2.5, tipografi data-density, accordion drawer default tertutup, dan Floating Thumb Dock navigasi jempol 0-scroll di mobile).
  - `COLOR_CONTRAST_AND_MOTION`: Mematuhi kaidah warna 60-30-10 (OKLCH), kontras APCA $L_c \ge 75$ (body) & $L_c \ge 60$ (heading) / WCAG AAA, serta koreografi GSAP berjenjang (kontainer makro $\le 30\text{px}$ vs teks mikro $\le 14\text{px}$, durasi $\le 600\text{ms}$, GPU properties, isolasi `gsap.context()`, dan `prefers-reduced-motion`).
  - `SPATIAL_PROPORTION`: Kepatuhan sains jarak Gestalt (rasio kedekatan mikro:meso:makro 1:3:6), panjang baris optimal 45–75 CPL via `max-w-3xl`/`4xl`, inverse leading (heading rapat vs body lega), ruang negatif aktif Tufte/Ruder (eliminasi '1+1=3 Noise' & nested container bloat), dan keseimbangan dinamis asimetris (Visual Weight Equilibrium).

### Dimensi 5: Output Schema & Contract Determinism (Bobot: 15 Poin)
Menguji apakah format keluaran model terkunci secara presisi menggunakan format yang dapat diparsing mesin (*machine-readable*) atau standar internasional yang diakui.
- **0 - 5 Poin (Lemah):** Format bebas tak berstruktur ("Tuliskan kodenya di bawah").
- **6 - 10 Poin (Menengah):** Meminta format Markdown umum tanpa struktur folder, file path, atau pembatas yang jelas.
- **11 - 15 Poin (Internasional):** Kontrak output presisi: target file path eksplisit, format kode utuh per file, penggunaan XML tags demarcated (`<code>`, `<diff>`, `<test>`), kepatuhan terhadap RFC 7807 untuk API error, dan struktur data JSON schema terdefinisi.

### Dimensi 6: Verification, Self-Correction & Review Gate (Bobot: 15 Poin)
Menguji apakah prompt menuntut bukti eksekusi (*Proof-of-Work*), kriteria uji otomatis (*automated testing*), dan checkpoint persetujuan manusia (*Human-in-the-Loop*).
- **0 - 5 Poin (Lemah):** Tidak ada kriteria verifikasi. Model langsung menganggap pekerjaannya selesai begitu selesai menulis teks.
- **6 - 10 Poin (Menengah):** Hanya meminta "pastikan tidak ada error" tanpa instruksi bagaimana cara mengujinya.
- **11 - 15 Poin (Internasional):** Kriteria lolos uji terdefinisi secara kuantitatif:
  - PHPUnit/Pest Feature Test wajib lulus minimal 3 skenario (200/201, 422, 403).
  - Linting & kompilasi statis (`npm run build`) wajib 0 error dan 0 warning.
  - Review Gate Checkpoint eksplisit sebelum beralih ke tugas berikutnya.

---

## 3. TINGKAT MATURITAS PROMPT (MATURITY TIERS)

| Total Skor | Tingkat Kualitas | Kategori | Implikasi Operasional |
| :---: | :---: | :---: | :--- |
| **0 - 45** | **Tier 4: Naive / Slop-Prone** | Kritis | Menghasilkan kode buntung, placeholder masif, halusinasi schema, dan bug fatal di produksi. Wajib ditulis ulang total. |
| **46 - 70** | **Tier 3: Functional Basic** | Rentan | Dapat bekerja untuk skrip satu kali jalan, namun gagal pada sistem multi-tabel, RBAC, dan integrasi frontend reaktif. |
| **71 - 89** | **Tier 2: Enterprise Compliant** | Standar Baik | Memenuhi standar arsitektur industri. Aman digunakan untuk pengembangan fitur standar dengan sedikit supervisi. |
| **90 - 100** | **Tier 1: Deterministic Mission-Critical** | Standar Emas Internasional | 100% deterministik, zero-hallucination, full-state handled, anti-slop hardened, dan siap audit enterprise. |

---

## 4. CHECKLIST VULNERABILITY & PROMPT SMELLS

Sebelum mengesahkan prompt, periksa ada/tidaknya "Prompt Smells" berikut:

- [ ] **Smell 1: Vague Superlatives:** Penggunaan kata sifat tanpa metrik ("buat secepat mungkin", "buat sekeren mungkin", "buat yang aman"). Ganti dengan metrik terukur: `< 0.1ms query`, `WCAG AAA 7:1 contrast`, `RFC 7807 error format`.
- [ ] **Smell 2: Multi-Role Cramming:** Menugaskan model menjadi backend, frontend, devops, copywriter, dan SEO optimizer sekaligus dalam 1 prompt untuk tugas besar.
- [ ] **Smell 3: Implicit Assumptions:** Membiarkan model menebak nama tabel atau relasi database.
- [ ] **Smell 4: Missing Error States:** Hanya meminta happy path tanpa skenario validasi gagal, akses ditolak, atau koneksi timeout.
- [ ] **Smell 5: Open-Ended Termination:** Model tidak diberitahu kapan tugas selesai dan format laporan penutup yang diharapkan.
