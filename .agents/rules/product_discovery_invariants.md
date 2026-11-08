# Invariants Sistem: Product Discovery & Requirements Engineering (product_discovery_invariants.md)
> **Engine:** Hyperion Vault v6.31.0 / v8.0.0 SDD-Enterprise-Deterministic  
> **Standards:** Marty Cagan (2017), Teresa Torres (2021), Karl Wiegers 3rd Ed (2013), Anthony Ulwick (2016), Eric Evans (DDD), Martin Fowler (PoEAA), Edgar F. Codd (3NF), Markus Winand (SQL Indexing), Jakob Nielsen (NN/g), Don Norman, RFC 7807, WCAG 2.2 AAA  
> **Target:** Autonomous Coding Agents (Antigravity, Gemini, Claude, Cursor) & Human Engineers  
> **Trigger Tags:** `@discovery`, `@feature`, `@jtbd` | **Playbook:** [`.agents/skills/discovery-architect/SKILL.md`](../skills/discovery-architect/SKILL.md)

---

## 1. PRINSIP PERUMUSAN FITUR ENTERPRISE

Setiap inisiasi fitur, modul, atau modifikasi alur kerja dari sebuah studi kasus perusahaan atau klien wajib mematuhi 9 Invarian Mutlak berikut:

```
┌────────────────────────────────────────────────────────────────────────┐
│ 9 GOLDEN INVARIANTS PRODUCT DISCOVERY & REQUIREMENTS ENGINEERING       │
├────────────────────────────────────────────────────────────────────────┤
│ 1. [INV-DISC-01] NO_SPECULATIVE_FEATURES (Mandatory JTBD Statement)    │
│ 2. [INV-DISC-02] 4_PRODUCT_RISKS_CLEARANCE (Value, Usability, Feas, V) │
│ 3. [INV-DISC-03] ROLE_ALIGNMENT_MANDATE (Strict Canonical RBAC Mapping)│
│ 4. [INV-DISC-04] GHERKIN_VERIFICATION_CONTRACT (Happy, 422, 403 BOLA)  │
│ 5. [INV-DISC-05] FULL_STATE_UI_MAPPING (Loading, Empty, Error, Success)│
│ 6. [INV-DISC-06] TRIPLE_PILLAR_DESIGN_AUDIT (Logic, DB 3NF, UI/UX NN/g)│
│ 7. [INV-DISC-07] PRO_GRADE_COMPACT_UI (Mobile Density & Thumb Dock)    │
│ 8. [INV-DISC-08] ZERO_REGRESSION_AND_DEFENSIVE_SCOPE (0 Bug Garansi)   │
│ 9. [INV-DISC-09] CLIENT_SIDE_CSV_EXPORT_MANDATE (In-Memory BOM UTF-8)  │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 2. 9 INVARIAN MUTLAK REKAYASA KEBUTUHAN

### [INV-DISC-01] Larangan Fitur Spekulatif & Mandat Formula JTBD (No Vanity Features)
1. Dilarang keras merancang modul, tabel database, atau endpoint yang tidak bersumber dari alur kerja operasional riil klien.
2. Setiap fitur yang diajukan WAJIB didukung minimal satu pernyataan **Jobs-to-be-Done (JTBD)** baku berformat Anthony Ulwick:
   $$\text{Job Statement} = \mathbf{\text{[Kata Kerja Aksi]}} + \mathbf{\text{[Objek Kontekstual]}} + \mathbf{\text{[Pengjelas Konteks/Kondisi]}}$$
   *Dilarang menggunakan narasi fitur-sentris subjektif seperti "User ingin menu baru yang modern".*

### [INV-DISC-02] Audit 4 Risiko Produk Fundamental (Marty Cagan Standard)
Sebelum sebuah ide fitur disetujui masuk ke Blueprint Studio atau dikerjakan tim rekayasa, fitur wajib lolos audit 4 risiko:
1. **Value Risk:** Apakah ada bukti bahwa jika fitur ini tidak dibuat, operasional klien terhambat atau merugi? Fitur pemanis (*vanity*) wajib dieliminasi.
2. **Usability Risk:** Apakah antarmuka dapat dioperasikan oleh peran target tanpa dokumentasi tebal?
3. **Feasibility Risk:** Apakah arsitektur saat ini (latensi P99 $< 150\text{ ms}$, kuota memori, koneksi DB terbatas) mampu menopangnya?
4. **Business Viability Risk:** Apakah fitur mematuhi regulasi legal, SOP institusi, dan batasan audit?

### [INV-DISC-03] Pengikatan Peran Kanonikal Mutlak (Canonical Role Alignment & Zero Aliasing)
1. Dilarang merancang aksi (*Command*) atau tampilan (*Read Model*) tanpa pemilik peran (*Actor Role*) yang jelas.
2. Seluruh hak akses fitur WAJIB dipetakan secara eksplisit pada salah satu dari peran kanonikal yang terdaftar di `backend/config/permissions.php`.
3. **Larangan Keras Alias & Konsolidasi Peran:** Dilarang keras membuat peran bayangan (*shadow roles*), kamus mapping alias peran, atau mengonsolidasi peran yang berbeda tanggung jawabnya menjadi peran gabungan (`consolidation_map`, `normalizeRoles`, dll.). Seluruh peran wajib diakui dan disimpan secara mandiri dan presisi.

### [INV-DISC-04] Kontrak Verifikasi Gherkin Deterministik (Given-When-Then)
Setiap spesifikasi fitur wajib menyertakan minimal 3 skenario pengujian Gherkin yang dapat langsung diterjemahkan menjadi PHPUnit Feature Test:
1. **Skenario 1 (Happy Path):** Permintaan valid menghasilkan respon HTTP 200/201 dan mutasi atomik transaksional.
2. **Skenario 2 (Negative / Validation Path):** Input cacat/kosong ditolak dengan HTTP 422 dan respon RFC 7807 Problem Details.
3. **Skenario 3 (Security & Authorization Path):** Akses tanpa hak ditolak oleh Policy dengan HTTP 403 Forbidden guna mencegah kerentanan BOLA (*Broken Object Level Authorization*).

### [INV-DISC-05] Pemetaan 4 State Visual Antarmuka (Full-State UI)
Setiap alur fitur pada antarmuka pengguna (Svelte 5) wajib memetakan perilaku visual secara lengkap:
- **Loading State:** Skeleton/shimmer loader dengan dimensi proporsional.
- **Empty State:** Penjelasan ramah saat data nol + tombol CTA aksi awal.
- **Error State:** Kotak pesan RFC 7807 + tombol coba lagi (*retry CTA*) dengan `type="button"`.
- **Success State:** Tabel data interaktif / form entri terisi normal.

### [INV-DISC-06] Mandat Audit Desain 3-Pilar (Triple-Pillar Design Audit)
Sebelum spesifikasi fitur disahkan (*design freeze*) dan dikompilasi menjadi kode produksi, spesifikasi WAJIB diaudit dan lulus 100% pada 3 pilar rekayasa perangkat lunak:
1. **Pilar Logika Bisnis (DDD & FSM):**
   - Menggunakan *Ubiquitous Language* kanonikal domain institusi.
   - Alur *State Machine* wajib deterministik dengan *guard conditions* eksplisit dan pencatatan riwayat audit (*audit trail*).
   - Seluruh mutasi multi-tabel dibungkus transaksi atomik (`DB::transaction`).
2. **Pilar Basis Data (3NF Normalization & Markus Winand Indexing):**
   - Skema mematuhi 3NF (bebas dari *update/delete anomaly*).
   - Foreign key wajib mengunci integritas referensial (`restrict` / `cascade`).
   - Tabel memiliki definisi indeks komposit B-Tree berorientasi performa (*Equality column first, then Range column*).
   - Tidak ada kolom yatim (*Zero Orphan Columns*): setiap input UI memetakan kolom database 1:1.
3. **Pilar UI/UX (Jakob Nielsen 10 Heuristics, WCAG 2.2 AAA & Apple Spatial Materials):**
   - Mematuhi heuristik NN/g: *visibility of system status*, pencegahan kesalahan (*error prevention / disable button on submit*), dan dialog konfirmasi destruktif.
   - **Usability Risk Clearance on Glass UI & 3-Tier Surface Elevation (NN/g):** Elemen semi-transparan wajib memiliki *contrast backing floor* (APCA $L_c \ge 75$ / WCAG 7:1). Dilarang menempatkan teks atau input form di atas latar belakang grafis berisik yang memicu kegagalan pemisahan objek (*figure-ground segregation failure*).
   - **Mandat Kontras Permukaan 3-Tier (L0/L1/L2):** Setiap spesifikasi antarmuka wajib memvalidasi pemisahan visual tegas antara L0 Kanvas dasar (`bg-slate-100/70` / `dark:bg-[#070b12]`), L1 Kartu Makro (`bg-white` / `dark:bg-slate-900/90` dengan border 1px & `shadow-2xs`), dan L2 Elemen Mikro tersemat dengan diferensiasi jelas. DILARANG KERAS merancang kartu/komponen transparan rendah (`bg-white/40`, `dark:bg-white/[0.02]`) di atas kanvas solid yang menyebabkan kartu "tenggelam" ke dalam latar belakang (*visual camouflage*).
   - Kontras warna teks minimal 7:1 (AAA) dan seluruh elemen tombol menyertakan atribut eksplisit `type="button"` atau `type="submit"`.
   - Pemilihan *Delivery Target* tepat (dilarang memaksakan rute `+page.svelte` jika berupa dialog/drawer/inline).

### [INV-DISC-07] Standar Visual Pro-Grade Compact & Ergonomi Mobile Thumb Dock
1. **Anti-Bloat & Anti-Sesak ("Fisher-Price" UI Ban):**
   - Dilarang keras menggunakan border tebal 2px, padding bertumpuk `p-4` di dalam card mobile, atau font raksasa yang memboroskan ruang vertikal dan membuat viewport ponsel sempit/sesak.
   - Wajib menerapkan batas tegas 1px (*hairline* `border border-slate-200/80 dark:border-slate-800/80`), bayangan mikro `shadow-2xs`, padding kompak (`p-2` s/d `p-2.5`), dan tombol aksi mikro `min-h-[30px] px-2.5 py-1 text-[11px]`.
2. **Drawer Navigasi Bertahap (Progressive Disclosure):**
   - Sidebar drawer mobile wajib menginisialisasi seluruh accordion grup menu dalam kondisi tertutup (*collapsed* `false`), hanya membuka grup rute aktif secara otomatis. Lebar drawer dibatasi maksimal `w-60` (240px).
3. **Floating Thumb Dock (0-Scroll Navigation Access):**
   - Dilarang bilah footer statis tebal yang mencuri permanen 60px viewport layar ponsel.
   - Wajib menyertakan kapsul floating di tengah bawah viewport mobile (`fixed bottom-3.5 left-1/2 -translate-x-1/2 z-30 md:hidden`) berisi pintasan *Beranda*, *Segarkan*, dan *Menu* agar pengguna saat membaca tabel panjang tidak perlu scroll ke atas untuk berpindah rute.
   - Area `<main>` wajib memiliki safe padding `pb-16 sm:pb-5`.
4. **Minimalisme Elegan, Negative Space, & Aksesibilitas (Dieter Rams, Tufte, Bringhurst):**
   - Desain mempraktikkan *"Less, but better"*: mengeliminasi dekorasi/border berlebih dan mengandalkan ruang bernapas (*negative space*) untuk pemisahan logis.
   - Keterbacaan tipografi: panjang baris 45–75 CPL, modular scale terstruktur, dan `font-mono` untuk data tabular/keuangan.
   - Batas kontainer terkontrol (`max-w-3xl`/`4xl` untuk form/artikel; `max-w-6xl`/`7xl` untuk tabel/dasbor), tidak membentang liar memenuhi layar.
   - Aksesibilitas penuh WCAG 2.2 AAA: kontras $\ge 4.5:1$ (AA) / $7:1$ (AAA), touch target $\ge 44\text{px}$, keyboard focus visible ring.
5. **Masterpiece Craftsmanship & Kebijakan Anti-AI Slop:**
   - Dilarang keras menggunakan klise visual AI (gradient ungu-pink neon SaaS template, blob blur mengambang tanpa guna, frosted glass buram berlebihan).
   - Terapkan keaslian estetika karya manusia (*human-crafted feel*): palet tonal teredam bermartabat, tipografi berirama, dan desain fungsional berorientasi tujuan (*purpose-driven UI*).

### [INV-DISC-08] Zero Regression & Defensive Scope Constraint (No New Bugs Guarantee)
1. Setiap perumusan fitur baru atau penyesuaian alur kerja wajib memetakan dependensi sistem yang sudah berjalan guna mencegah efek samping (*side effects*).
2. Dilarang merancang perubahan yang merusak kompatibilitas kontrak API atau relasi tabel yang sedang aktif.
3. Eksekusi kode turunan wajib diverifikasi 100% bebas dari cacat regresi (*zero regression proof*) via uji otomatis sebelum diserahkan ke pengguna.

### [INV-DISC-09] Mandat Ekspor CSV Sisi Klien pada Seluruh Tabel Data Baru (Client-Side CSV Export)
1. Setiap perumusan fitur yang memuat tabel data (daftar entri transaksi, master data, pelaporan, log audit) WAJIB menyertakan tombol aksi "Ekspor CSV" langsung dari sisi peramban (*client-side in-memory export*).
2. Proses unduh berkas DILARANG memicu request berulang ke backend untuk data yang telah ter-load pada state/memori peramban (*zero round-trip*).
3. Berkas CSV wajib diproteksi menggunakan UTF-8 BOM (`\uFEFF`) agar kompatibel dengan Excel, sanitasi tanda kutip ganda (`""`), mitigasi formula injection CWE-1236, dan menyertakan timestamp pada penamaan berkas.

---

## 3. CHECKLIST VERIFIKASI DISCOVERY SEBELUM REVIEW GATE

- [ ] Fitur memiliki pernyataan JTBD baku (Kata Kerja + Objek + Konteks).
- [ ] Fitur telah lolos audit 4 risiko produk Marty Cagan.
- [ ] Seluruh endpoint dan form telah diikat ke peran kanonikal resmi tanpa alias dan tanpa konsolidasi.
- [ ] Terdapat 3 skenario Gherkin (200, 422, 403) siap uji.
- [ ] Memetakan 4 visual state antarmuka: Loading, Empty, Error, Success.
- [ ] Lulus Audit Desain 3-Pilar (Logika Bisnis DDD/FSM, Basis Data 3NF/Index, UI/UX NN/g & WCAG AAA).
- [ ] Mematuhi Standar Pro-Grade Compact & Floating Thumb Dock (Bebas UI sesak, hairline 1px, 0-scroll thumb nav).
- [ ] Mematuhi Standar Elegan Minimalis, Negative Space, & Aksesibilitas WCAG 2.2 AAA (Dieter Rams, Tufte, Bringhurst).
- [ ] Mematuhi Standar Masterpiece Craftsmanship & Anti-AI Slop (Keaslian karya manusia, tonal teredam, zero generic AI clichés).
- [ ] Menyediakan fitur Ekspor CSV front-end in-memory dengan BOM UTF-8 pada seluruh tabel data.
- [ ] Menjamin 100% zero-regression tanpa memicu bug atau masalah baru pada modul yang telah berjalan.
- [ ] Teks analisis kompatibel 100% untuk disalin ke Tahap 1 Blueprint Studio (`/admin/studio`).

