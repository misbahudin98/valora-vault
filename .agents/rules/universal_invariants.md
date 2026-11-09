# Universal System Invariants & Anti-Slop Policy (SDD-Enterprise-Deterministic)
> **Version:** 8.0.0 | **Standard:** Specification-Driven Development (SDD) & PER Coding Style 2.0  
> **Target Scope:** Universal across Valora Vault Core Engine, Valora Vault, Yayasan PQTM, and all generated repos.

---

## 1. STANDAR INTERNASIONAL SOFTWARE ENGINEERING

### 1.1 Code Style & Syntactic Invariants
- **Backend (PHP):** Kepatuhan penuh terhadap PSR-12 dan PER Coding Style 2.0.
  - Setiap berkas PHP WAJIB diawali dengan `declare(strict_types=1);`.
  - Type-hinting ketat pada parameter method, return types, dan property declarations.
- **Frontend (JavaScript / Svelte):**
  - Standar Prettier formatting untuk seluruh berkas JavaScript dan Svelte.
  - **100% Pure JavaScript (ES2024+):** DILARANG KERAS menggunakan TypeScript (`.ts`, interface TypeScript, type annotations, atau dependensi TypeScript).
  - **Svelte 5 Runes SAJA:** Wajib menggunakan runes modern (`$state`, `$derived`, `$props`, `$effect`). DILARANG KERAS menggunakan sintaks legasi Svelte 4 (`export let`, `$:`, writable stores).

### 1.2 Keamanan & Standar OWASP Top 10
- **OWASP ASVS & OWASP Top 10 Compliance:**
  - **SQL Injection Prevention:** 0 raw query tanpa parameter binding. Seluruh query wajib memanfaatkan Eloquent ORM atau PDO parameter binding terproteksi.
  - **Broken Object Level Authorization (BOLA) Prevention:** Setiap endpoint mutasi atau akses data privat WAJIB diproteksi oleh Laravel Policy (`$this->authorize(...)` atau `Gate::authorize(...)`).
  - **Cross-Site Scripting (XSS) Prevention:** Output rendering aman via Svelte escaping bawaan, sanitasi input ketat, dan Content-Security-Policy.
  - **Cross-Site Request Forgery (CSRF) Prevention:** Token Sanctum CSRF (`/sanctum/csrf-cookie`) dengan SameSite cookie `lax`/`strict`.
  - **Mass-Assignment Guard:** Eloquent `$fillable` eksplisit pada setiap Model; dilarang `guarded = []`.
- **Standar Representasi Kesalahan RFC 7807 (Problem Details for HTTP APIs):**
  - Seluruh respons kesalahan API (4xx & 5xx) WAJIB mematuhi format RFC 7807 (`type`, `title`, `status`, `detail`, `instance`, `errors`).
  - DILARANG membocorkan jejak kueri SQL, file paths server, kredensial `.env`, atau nama role internal pada respons error klien.

### 1.3 Pengujian & Verifikasi Kualitas (Test-Driven Quality)
- **PHPUnit / Pest Feature Test Mandate & 4 Pillars (Vladimir Khorikov 2020):**
  - Setiap endpoint API backend WAJIB memiliki Feature Test dengan minimal 3 skenario mutlak:
    1. `200 OK` / `201 Created` (Happy path transaksi valid).
    2. `422 Unprocessable Entity` (Validasi form gagal pada input cacat/kosong).
    3. `403 Forbidden` (RBAC authorization & policy guard menolak aktor tanpa wewenang).
  - Mengikuti pola AAA (Arrange-Act-Assert) dan mandat Classical Detroit School (anti-over-mocking internal). Rujukan SSoT: [`doc/testing_standards.md`](../../doc/testing_standards.md).
- **UI Accessibility (WCAG 2.2 Level AAA):**
  - Kontras warna teks terhadap latar belakang minimal 7:1 untuk teks normal pada mode Daylight dan Dark Obsidian.
  - Struktur semantic HTML5 (`<header>`, `<nav>`, `<main>`, `<section>`, `<article>`).
  - Target sentuh ergonomis (WCAG 2.2 SC 2.5.8): Tombol primer/form input utama $\ge 44\text{px}$; tombol mikro transaksional baris tabel minimal 30–34px dengan padding/spasi sentuh yang memadai tanpa memaksakan ukuran box visual yang raksasa/bloated.
  - `aria-label` deskriptif pada seluruh elemen tombol interaktif (0 warning a11y).

---

## 2. KEBIJAKAN ANTI-SLOP & ZERO TOLERANCE TERHADAP KODE DANGKAL

### [RULE-01-NO-PLACEHOLDER] NO PLACEHOLDER (KODE 100% UTUH & PRODUCTION-READY)
- DILARANG KERAS menggunakan komentar pemalas atau pemotongan kode:
  `// TODO: implement later`, `/* ...rest of code... */`, `// add your logic here`, atau `console.log('clicked')`.
- Seluruh kode yang dihasilkan WAJIB 100% utuh, tuntas, dapat dikompilasi, dan langsung berfungsi di lingkungan produksi (*production-ready*).

### [RULE-02-FULL-STATE-UI] FULL-STATE UI MANDATE (4 VISUAL STATES)
Setiap komponen antarmuka Svelte 5 Runes yang mengambil atau memproses data WAJIB menangani 4 State secara visual tanpa terkecuali:
1. **Loading State:** Skeleton loader reaktif atau indikator visual saat fetching data (`ElementLoader` / `PageLoader`).
2. **Empty State:** Tampilan ilustratif dengan pesan informatif dan tombol panduan jika data kosong.
3. **Error State:** Pesan error ramah pengguna dengan tombol aksi 'Coba Lagi' jika terjadi kegagalan jaringan/server.
4. **Success / Populated State:** Tampilan tabel/grid normal lengkap dengan validasi inline form.

### [RULE-03-YAGNI-KISS] YAGNI & ANTI-OVERENGINEERING
- Terapkan prinsip YAGNI (You Aren't Gonna Need It) & KISS (Keep It Simple, Stupid).
- DILARANG menciptakan abstraksi palsu seperti puluhan lapisan `Service`, `Repository`, `Interface`, `Adapter`, `Factory` jika fitur tersebut hanya sebuah operasi CRUD biasa.
- **Pola Standar Valora:**
  - Thin Controller.
  - Form Request untuk validasi ketat.
  - Eloquent API Resource untuk transformasi data.
  - Action Class HANYA jika melibatkan mutasi logika bisnis multi-tabel atau integrasi eksternal kompleks.

### [RULE-04-NO-GHOST-FEATURES] NO GHOST FEATURES
- DILARANG menambahkan fitur liar (*ghost features*) yang tidak tercantum dalam dokumen kontrak SDD.
- Fokus 100% pada ketahanan, efisiensi, dan keakuratan fitur yang diminta.

### [RULE-05-ATOMIC-MUTATION] DEFENSIVE & ATOMIC MUTATION
- Setiap penulisan/mutasi data yang melibatkan lebih dari satu tabel WAJIB dibungkus dalam `DB::transaction(fn() => ...)`.
- DILARANG silent failure: Tangkap exception, catat log kesalahan secara terstruktur ke storage log, dan sajikan respons terstruktur RFC 7807 ke pengguna.

### [RULE-06-REACTIVE-FEEDBACK] REACTIVE FEEDBACK & ACCESSIBILITY
- Setiap tombol aksi interaktif WAJIB memiliki:
  - Atribut eksplisit `type="button"` atau `type="submit"`.
  - Status visual reaktif: status *disabled* dan *spinner* saat *submitting*.
  - Notifikasi toast feedback interaktif (`toastState.success` / `toastState.error`).
  - Modal konfirmasi destruktif sebelum menghapus data penting.

### [RULE-07-SINGLE-FEATURE-TURN] SINGLE FEATURE TURN & AUTONOMOUS EXECUTION LOOP
- **Granularitas Unit Kerja:** Eksekusi kode HANYA 1 Fitur untuk 1 Role dalam satu giliran kerja (*Single Feature Turn*).
- **Otonomi Dalam Giliran:** Seluruh aksi alat (*tool calls*, baca/tulis berkas, migrasi, penulisan test, perbaikan bug, dan kompilasi *build*) dijalankan secara proaktif dan otonom tanpa meminta konfirmasi manual per baris perintah.
- **Review Gate Checkpoint:** Setelah unit kerja selesai dan terverifikasi 100% (`php artisan test` dan `npm run build` lulus), sajikan formulir Review Gate resmi dan tunggu persetujuan pengguna sebelum melangkah ke fitur berikutnya.

### [RULE-08-COMPACT-DENSITY] PRO-GRADE COMPACT DENSITY & MOBILE THUMB ERGONOMICS (ANTI-BLOAT)
- **Anti-Pattern "Fisher-Price" UI:** Dilarang keras menghasilkan antarmuka mobile yang terasa sesak dan kekanak-kanakan (border 2px tebal, padding kartu bertumpuk `p-4` di dalam kontainer mobile, header raksasa >56px, atau teks 16px pada kartu data padat).
- **Standar Visual Pro-Grade Compact:**
  - Garis batas tegas 1px (*hairline* `border border-slate-200/80 dark:border-slate-800/80`).
  - Bayangan mikro lembut `shadow-2xs` (bukan drop-shadow tebal).
  - Padding rapat dan bernapas (`p-2` s/d `p-2.5`, `space-y-1.5` s/d `space-y-2`).
  - Tipografi proporsional: Header modul `h-11.5 sm:h-12` (~46-48px), judul kartu `text-xs font-bold`, label metadata `text-[11px]` atau `text-[10px] uppercase font-bold tracking-wider`, angka `font-mono text-xs`.
  - Micro-action buttons pada baris tabel/kartu: `min-h-[30px] px-2.5 py-1 text-[11px] font-semibold`.
- **Drawer Navigasi Bertahap (Progressive Disclosure):**
  - Pada layar ponsel, seluruh grup menu bertingkat (accordion tree) di sidebar drawer **wajib tertutup (collapsed) secara default** (`expandedTrees = { ...: false }`). Hanya grup rute yang sedang aktif yang otomatis terbuka. Lebar drawer dibatasi `w-60` (240px).
- **Floating Thumb Dock (Navigasi Instan Tanpa Scroll ke Atas):**
  - Dilarang membuat bilah footer statis tebal yang memotong layar mobile secara permanen.
  - Gunakan kapsul floating di tengah bawah mobile (`fixed bottom-3.5 left-1/2 -translate-x-1/2 z-30 md:hidden`) dengan efek kaca (*frosted glass* `backdrop-blur-md bg-white/92 dark:bg-slate-950/95 rounded-full border shadow-xl`) yang menyediakan 3 akses cepat: `[ 🏠 Beranda ]`, `[ ↻ Segarkan ]`, dan `[ ☰ Menu ]`.
  - Viewport `<main>` wajib memiliki safe padding bawah `pb-16 sm:pb-5`.

### [RULE-09-NO-ALIAS] NO ALIAS (ZERO ALIASING POLICY)
- **Larangan Alias & Proxy Penamaan:** DILARANG KERAS membuat nama alias, kamus mapping alias (`aliases`, `alias_map`), wrapper alias, shorthand re-export, atau sinonim buatan untuk peran pengguna (*roles*), hak izin (*permissions*), rute (*routes*), method controller, model, tabel, kolom basis data, variabel state, maupun komponen antarmuka.
- **Explicit Canonical SSoT:** Setiap entitas sistem wajib dipanggil dan direferensikan menggunakan nama kanonikal aslinya secara eksplisit.
- **Alasan Mutlak:** Aliasing mengaburkan transparansi kode, memicu ambiguitas penelusuran (*traceability*), menimbulkan celah otorisasi (BOLA), dan memicu bug regresi yang sulit dilacak.

### [RULE-10-NO-UNAUTHORIZED-CONSOLIDATION] NO UNAUTHORIZED CONSOLIDATION (ZERO CONSOLIDATION POLICY)
- **Larangan Konsolidasi Monolitik Sepihak:** DILARANG KERAS menggabungkan (*merging/collapsing*) beberapa berkas terpisah, modul terpisah, controller independen, skrip utilitas, atau komponen antarmuka menjadi modul atau berkas "serbaguna" monolitik tanpa instruksi tertulis pengguna.
- **Integritas Peran & Hak Akses:** DILARANG mengonsolidasi beberapa peran pengguna (*roles*) menjadi peran gabungan (`consolidation_map`, `normalizeRoles`, dll.). Seluruh peran wajib berdiri sendiri sesuai penugasan riil.
- **Integritas Relasional 3NF:** DILARANG mengonsolidasi tabel relasional 3NF menjadi kolom array/JSON bebas tanpa alasan yang divalidasi.
- **Bounded Context & SRP:** Setiap file, komponen, dan entitas wajib mempertahankan *Single Responsibility Principle* (SRP) dan batas domain aslinya.

### [RULE-11-ZERO-REGRESSION-EXECUTION] ZERO REGRESSION & DEFENSIVE EXECUTION (NO NEW BUGS GUARANTEE)
- **Prinsip Garansi Tanpa Bug Baru:** Setiap eksekusi prompt (fitur baru, refaktorisasi, atau perbaikan bug) WAJIB menjamin 100% bebas dari regresi (*zero regression*) dan tidak menyebabkan masalah atau bug baru pada sistem yang telah berjalan.
- **Preservasi Kontrak & Kompatibilitas Mundur:** Dilarang mengubah signature method publik, struktur respons API aktif, atau kolom database tanpa mitigasi kompatibilitas mundur.
- **Verifikasi Pengujian Otomatis Mutlak:** Agen WAJIB menjalankan uji otomatis (`php artisan test tests/Feature` dan `npm run build`) hingga 100% lulus sebelum mengakhiri setiap giliran kerja.

### [RULE-12-TABLE-CARD-AND-PAGE-SIZE] MANDAT MEKANISME CARD & KONTROL BARIS PER HALAMAN (SEMUA TABEL)
- **Mekanisme Card View:** Seluruh tabel data WAJIB menyediakan mekanisme tampilan kartu (*Card View*). Pada layar mobile (≤ 768px), susunan kartu (*Card Stack*) wajib aktif secara otomatis tanpa memaksa scroll horizontal yang merusak pengalaman pengguna. Pada layar laptop/desktop, sediakan opsi tampilan tabel dan kartu yang rapi.
- **Kontrol Paginasi & Baris per Halaman (Page Size):** Setiap tabel data WAJIB menyediakan kontrol interaktif jumlah data yang ditampilkan per halaman (pilihan: 10, 25, 50, 100 entri) yang dapat disetel langsung oleh pengguna, ditempatkan secara ergonomis pada bilah atas atau footer tabel.

### [RULE-13-SPACIOUS-BREATHING-ROOM] MANDAT RUANG BERNAPAS & TAMPILAN ANTI-SESAK (HP & LAPTOP)
- **Eliminasi Tampilan Sesak & Penuh:** DILARANG KERAS menghasilkan antarmuka yang terlihat sesak, padat berhimpitan, atau penuh sesak (*cramped*) baik di layar HP maupun di laptop/desktop.
- **Prinsip Ruang Bernapas (Breathing Room):**
  - Terapkan jarak (*whitespace*) proporsional antar-elemen (`gap-2.5` s/d `gap-4`, `p-3` s/d `p-5`).
  - Gunakan garis pembatas tipis 1px (*hairline* `border border-stone-200/80 dark:border-emerald-950/80`) dan bayangan mikro lembut `shadow-2xs`.
  - Dilarang menumpuk border tebal 2px di dalam kartu mobile yang memakan ruang vertikal.
  - Sediakan safe padding bawah (`pb-16 sm:pb-8`) agar elemen terbawah tidak tertutup oleh bilah navigasi bawah HP (*Floating Thumb Dock*).

### [RULE-14-TAB-PANE-VISIBILITY] MANDAT KETERBACAAN & VISIBILITAS UTUH TAB PANE (ANTI-HIDDEN TABS)
- **Visibilitas Utuh & Keterbacaan:** Setiap tab pane (subtab navigasi, header modal tabs, segmented control) WAJIB menjamin keterbacaan tinggi dan DILARANG menyembunyikan atau memotong menu/tab dari pandangan pengguna baik di HP maupun di laptop.
- **Larangan Scroll Horizontal pada Tab Terbatas:** Untuk kelompok tab dengan jumlah moderat (≤ 6 tab) atau label yang tidak terlalu panjang, seluruh tab WAJIB terlihat utuh tanpa memaksa pengguna melakukan scroll/geser horizontal (`overflow-x-auto whitespace-nowrap`).
- **Responsive Wrap Mandate:** Gunakan pembungkusan baris responsif (*responsive multi-line wrap* `flex-wrap gap-1.5` atau responsive grid) sehingga seluruh tab langsung terlihat sekilas (*glanceable*) dan dapat diakses dengan 1 sentuhan tanpa ada tab penting yang tertutup di luar layar.

### [RULE-15-ELEGANT-MINIMALISM] MANDAT MINIMALISME ELEGAN, TIPOGRAFI TERBACA, NEGATIVE SPACE, & AKSESIBILITAS (WCAG 2.2 AAA)
- **Keanggunan Fungsional (Dieter Rams & Mies van der Rohe):** Antarmuka wajib mempraktikkan *"Less, but better"*. Hilangkan elemen dekoratif tak berguna, border bertumpuk, atau saturasi warna agresif. Ketenangan visual (*visual serenity*) diperoleh dari keselarasan proporsi dan ketertiban grid.
- **Hierarki & Keterbacaan Tipografi (Robert Bringhurst & Ellen Lupton):**
  - **Batas Panjang Baris (Measure):** Teks paragraf dan form wajib dibatasi 45–75 CPL (karakter per baris) guna mencegah kelelahan mata (*tracking fatigue*).
  - **Skala Modular:** Terapkan rasio tipografi modular yang tegas. Judul menggunakan kontras bobot (*font-bold*) dan warna tajam; teks sekunder menggunakan muted tone (`text-slate-500` / `text-stone-400`); data tabular wajib menggunakan `font-mono` untuk keteraturan pemindaian kolom.
- **Negative Space & Rasio Data-ke-Tinta (Edward Tufte & Refactoring UI):**
  - Maksimalkan *Data-to-Ink Ratio*: Pemisahan kelompok data dilakukan melalui ruang kosong terukur (*whitespace* / *negative space* via Gestalt Proximity), bukan dengan membungkus setiap item dalam kotak bergaris tebal.
  - Gunakan garis batas 1px lembut (*subtle hairline border* `border-slate-200/80 dark:border-slate-800/80`) dan bayangan mikro halus `shadow-2xs`.
- **Batas Kontainer Terkendali (Restrained Layout Ceilings):**
  - Konten halaman DILARANG membentang 100% tanpa batas pada layar desktop lebar.
  - Wajib dibatasi kontainer responsif: `max-w-3xl` / `max-w-4xl` untuk pembacaan dan form; `max-w-6xl` / `max-w-7xl` untuk dasbor analitik dan tabel data, dengan pemusatan otomatis `mx-auto px-4 sm:px-6 lg:px-8`.
- **Standar Aksesibilitas Universal (WCAG 2.2 AAA):**
  - Rasio kontras teks wajib $\ge 4.5:1$ (AA) dan diutamakan $\ge 7:1$ (AAA).
  - Target sentuh minimal $44 \times 44\text{px}$ pada tombol utama; target minimal $24 \times 24\text{px}$ pada tombol mikro tabel padat.
  - Cincin fokus keyboard wajib tampak jelas (`focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:outline-none`).
  - Semantik HTML5 murni (`<main>`, `<nav>`, `<aside>`, `<header>`, `<footer>`) dengan atribut ARIA pelengkap.

### [RULE-16-MASTERPIECE-AUTHENTICITY] MANDAT MASTERPIECE CRAFTSMANSHIP, KEASLIAN KARYA (HUMAN-CRAFTED AUTHENTICITY), & KEBIJAKAN ANTI-AI SLOP
- **Eliminasi Klise Visual AI (Anti-AI Clichés):**
  - DILARANG KERAS menggunakan palet warna "AI generic/SaaS template" seperti latar belakang gradient ungu-pink neon (`from-indigo-500 via-purple-500 to-pink-500`), cyan glow berlebihan, ornamen blob lingkaran kabur (`blur-3xl opacity-30`) yang mengambang tanpa guna, atau ikon sparkle/bintang AI dekoratif yang tidak fungsional.
  - Dilarang efek frosted glass / glassmorphism buram yang mengaburkan kontras teks.
- **Karya Berkarakter & Human-Crafted Aesthetic:**
  - Wajib menerapkan palet tonal teredam bermartabat (*dignified muted tonal palette*): monokrom Slate/Stone/Obsidian yang tenang dipadukan dengan tepat 1 warna aksen fungsional berkarakter (Emerald di Yayasan PQTM, Indigo/Emerald di Valora Vault).
  - Tipografi berbobot dan berirama (*editorial rhythm*): heading tegas, label mikro terukur, `font-mono` untuk angka, timestamp, UUID, dan data tabular.
  - Tekstur dan pembatas presisi: hairline 1px borders (`border border-slate-200/80 dark:border-slate-800/80`), bayangan mikro lembut `shadow-2xs`, dan negative space lapang.
- **Harmoni Fungsionalitas & Zero Superfluous Elements:**
  - Setiap elemen antarmuka wajib memiliki alasan keberadaan fungsional (*purpose-driven UI*). Jika suatu elemen visual tidak membantu navigasi atau pemahaman data, hapus elemen tersebut (*Ockham's Razor UI*).

### [RULE-17-CLIENT-SIDE-CSV-EXPORT] MANDAT EKSPOR CSV CLIENT-SIDE PADA SEMUA TABEL DATA (ZERO-ROUNDTRIP FRONTEND CSV EXPORT)
- **Kewajiban Mutlak Seluruh Tabel:** Setiap tabel data yang dibuat pada seluruh modul antarmuka (Users, Sessions, Audit, System, dan seluruh modul bisnis turunan) WAJIB menyediakan kontrol aksi ekspor data ke format CSV langsung dari sisi peramban (*client-side export*).
- **Zero Round-Trip Backend:** Proses pengunduhan data DILARANG memicu pemanggilan endpoint API baru atau kueri ulang ke database backend untuk data yang telah ter-load pada state/memori frontend. Ekspor wajib dieksekusi seketika menggunakan `Blob` in-memory.
- **Standar Format & Keamanan Berkas CSV:**
  1. **UTF-8 Byte Order Mark (BOM: `\uFEFF`):** Wajib disematkan di awal stream berkas agar Microsoft Excel (Windows/Mac) dan Google Sheets langsung mengenali set karakter lokal dan aksara tanpa *mojibake*.
  2. **Escaping & String Wrapping:** Seluruh data teks wajib dibungkus dalam tanda kutip dua (`"..."`) dan karakter kutip internal wajib di-escape menjadi ganda (`""`).
  3. **Mitigasi CSV Formula Injection (CWE-1236):** Seluruh sel yang dimulai dengan simbol kalkulasi (`=`, `+`, `-`, `@`) wajib ditambahkan prefix petik tunggal (`'`) agar tidak dieksekusi sebagai formula berbahaya ketika dibuka di spreadsheet pengguna.
  4. **Pemberian Nama File Semantis:** Nama berkas unduhan wajib memuat nama entitas dan stempel tanggal, contoh: `[entitas]_[YYYY-MM-DD].csv`.
  5. **SSoT Reusable Implementation:** Gunakan komponen kanonikal [`DataTable.svelte`](../../frontend/src/lib/components/ui/DataTable.svelte) yang telah mengintegrasikan utility [`csvExport.js`](../../frontend/src/lib/utils/csvExport.js) secara default (`enableExport = true`).

### [RULE-18-COLOR-CONTRAST-AND-MOTION-CHOREOGRAPHY] MANDAT SAINS WARNA 60-30-10, KONTRAS PERSEPSI APCA / WCAG AAA, & KOREOGRAFI MOTION GSAP
- **Sains Warna & Harmoni 60-30-10 (OKLCH Standard):**
  - Alokasi proporsional: 60% Kanvas Dominan netral (`bg-slate-50` / `bg-slate-950`), 30% Struktur/Kartu/Border (`bg-white` / `bg-slate-900` + hairline border), 10% Aksen fungsional pemantik aksi (CTA, status aktif).
  - Dilarang tabrakan saturasi tinggi; gunakan palet tonal OKLCH seragam persepsi (*perceptually uniform*).
- **Batas Kontras Persepsi APCA & WCAG 2.2 AAA:**
  - Teks konten / body: Wajib memenuhi APCA Lightness Contrast $L_c \ge 75$ dan WCAG AAA $\ge 7:1$.
  - Heading / Teks judul: Wajib APCA $L_c \ge 60$ dan WCAG $\ge 4.5:1$.
  - Border interaktif & elemen UI non-teks: Wajib APCA $L_c \ge 45$ dan WCAG SC 1.4.11 $\ge 3:1$.
- **Koreografi Motion Design & GreenSock (GSAP) Discipline:**
  - **Pemisahan Makro vs Mikro (*Nested Motion*):** Kontainer kartu bergerak makro (`y: 20px` s/d `30px`, durasi $0.4\text{s} - 0.5\text{s}$); teks di dalamnya bergerak mikro (`y: 8px` s/d `14px`, durasi $0.25\text{s} - 0.35\text{s}$ dengan stagger $0.04\text{s} - 0.08\text{s}$). DILARANG melempar teks dari jarak $> 20\text{px}$ karena memicu disorientasi.
  - **Performance & Durasi Maksimal:** Durasi total animasi masuk maksimal $600\text{ms}$; dilarang animasi berlarut-larut ($> 800\text{ms}$). Hanya gunakan properti GPU-composited (`transform: translate3d/scale`, `opacity`). Dilarang menganimasikan geometri layout (`top/left/width/height/margin/padding`).
  - **Memory Leak Protection:** Seluruh inisiasi GSAP di Svelte 5 WAJIB dibungkus `gsap.context()` dan di-revert saat unmount (`ctx.revert()`).
  - **Vestibular Safety:** WAJIB menghormati `@media (prefers-reduced-motion: reduce)` tanpa pengecualian.

### [RULE-19-SPATIAL-PROPORTION-AND-BALANCE] MANDAT SAINS SPASIAL, RITME GEOMETRIS, KESEIMBANGAN SIMETRI-ASIMETRI, & NEGATIVE SPACE
- **Sains Jarak Gestalt & Rasio Kedekatan 1:3:6:**
  - Jarak mikro (ikon-teks, label-input): `gap-1` s/d `gap-2` ($4\text{px} - 8\text{px}$).
  - Jarak meso (antar-baris, antar-kartu): `gap-3` s/d `gap-5` ($12\text{px} - 20\text{px}$).
  - Jarak makro (antar-seksi, batas modul): `py-8` s/d `py-16` ($32\text{px} - 64\text{px}$).
  - Jarak elemen yang saling berhubungan logis WAJIB lebih rapat daripada jarak pemisah grup.
- **Batas Keterbacaan Robert Bringhurst (Optimal Measure & Inverse Leading):**
  - Teks baca/deskripsi/form WAJIB dibatasi panjang barisnya pada **45–75 CPL** (`max-w-3xl` / `max-w-4xl`). DILARANG membiarkan teks membentang melintasi monitor ultra-wide tanpa kontainer pembatas.
  - *Inverse Leading:* Heading display wajib rapat (`leading-tight` / $1.15 - 1.25$), sedangkan teks paragraf wajib renggang (`leading-relaxed` / $1.5 - 1.65$). Data numerik tabular wajib `font-mono` dengan `leading-none` / `leading-tight`.
- **Negative Space Aktif & Eliminasi '1+1=3 Noise' (Edward Tufte & Dieter Rams):**
  - Pisahkan kelompok data menggunakan ruang negatif (*whitespace*) proporsional dan garis batas *hairline* 1px halus (`border border-slate-200/80 dark:border-slate-800/80`). DILARANG membuat kotak di dalam kotak (*nested container bloat*) atau border tebal yang menimbulkan visual noise.
- **Keseimbangan Simetri Formal vs Asimetri Dinamis:**
  - Gunakan simetri aksial untuk dokumen cetak formal (kuitansi, akta, invoice, sertifikat).
  - Gunakan keseimbangan dinamis asimetris (*Visual Weight Equilibrium*) untuk landing page dan dashboard (misal: Split-Hero 55%:45% atau Bento Grid berbobot optik setara).

### [RULE-20-UNIVERSAL-DOCUMENTATION-ARCHITECTURE] MANDAT ARSITEKTUR DOKUMENTASI MULTI-PERSONA & DIÁTAXIS FRAMEWORK
- **Adaptasi 4 Persona (Universal Usability):** Setiap proyek WAJIB menyediakan dokumentasi berjenjang yang melayani 4 persona:
  1. *User Operasional (Staf):* Checklist prosedural cepat, diagram alur, penanganan error.
  2. *User Awam (Klien / Wali Santri):* Bahasa empatik, transparansi proses & biaya, analogi dunia nyata, FAQ non-teknis.
  3. *Profesional (Notaris, Auditor, Arsitek):* Rujukan dasar hukum/regulasi formal, diagram C4 (Level 1–4), skema 3NF, audit trail.
  4. *Baby Boomers (Generasi Senior / Lansia):* Tipografi kontras tinggi (7:1), ukuran font nyaman ($\ge 12\text{pt}$ / $16\text{px}$), instruksi linier bernomor tanpa langkah tersembunyi (*zero hidden steps*), analogi konkret, dan pesan peredam kecemasan (*fear-of-error reduction*).
- **Kuadran Diátaxis & Keterbacaan Plain Language (ISO 24495-1):**
  - Pisahkan dokumen ke dalam 4 kuadran: Tutorials, How-To Guides, Reference, dan Explanation.
  - Gunakan kalimat aktif (*active voice*), batas rata-rata 15–20 kata per kalimat, dan target keterbacaan Flesch-Kincaid Grade Level 7–8.
- **Visualisasi Formal (BPMN 2.0 & C4 Model):**
  - Diagram alur bisnis wajib menggunakan swimlanes BPMN 2.0.
  - Arsitektur teknis sistem wajib menggunakan The C4 Model (Context, Container, Component, Code).

### [RULE-21-FILTER-AND-SORT-ENGINE] MANDAT MESIN FILTER DINAMIS, KOLOM SORT MULTI-TIPE, & PEMISAHAN TABEL TUNGGAL PER HALAMAN (SEMUA FITUR OPERASIONAL)
- **1. Multi-Field In-Memory Instant Filtering (< 0.1ms):**
  - Setiap tabel data operasional (Pengguna, Sesi, Transaksi, Audit, Master Data) WAJIB menyediakan kontrol filter pencarian instan dengan debounce 120ms yang memindai seluruh kolom teks kunci (nomor referensi/kode, nama entitas, identitas, PIC, dan status).
  - Filter segmented status/kategori berbasis tombol/pill wajib menerapkan pembungkusan baris responsif (`flex-wrap gap-1.5` sesuai RULE-14) agar tidak terpotong di layar HP.
- **2. Deterministic Multi-Type Column Sorting Engine:**
  - Setiap tabel data operasional WAJIB mendukung pengurutan dua arah (Ascending `▲` & Descending `▼`) pada kolom utama dengan perbandingan tipe yang tepat:
    - *Tanggal / Waktu:* Kronologis unix timestamp (bukan leksikal string).
    - *Mata Uang & Angka:* Nilai numerik integer/float murni.
    - *Teks / Nama:* `localeCompare('id', { sensitivity: 'base' })`.
    - *Status / Fase Pipeline:* Bobot urutan sekuensial status domain kanonik.
  - Header kolom yang dapat diurutkan wajib menyertakan indikator visual arah sort aktif dan atribut aksesibilitas `aria-sort`.
- **3. In-Memory Resiliency & Zero Round-Trip:**
  - Seluruh mekanisme filter dan sort dieksekusi secara in-memory pada memori peramban via Svelte 5 `$derived.by()` tanpa memicu round-trip kueri ulang ke server backend.
- **4. Single-Responsibility View Ceilings (Anti-Multi-Table Bloat):**
  - DILARANG KERAS menumpuk lebih dari 1 tabel data independen berskala besar dalam satu viewport halaman gulung tunggal.
  - Setiap entitas transaksional yang memiliki siklus hidup (lifecycle) dan tujuan bisnis berbeda WAJIB dipisahkan ke dalam sub-halaman/rute mandiri atau sub-tab dengan tree menu navigasi terisolasi di sidebar. Hal ini menjaga Cognitive Load tetap rendah, mematuhi prinsip Single Responsibility Principle (SRP), dan menghindarkan pengguna mobile dari kelelahan gulung vertikal ekstrim (*Thumb Zone fatigue*).

### [RULE-22-SURFACE-CONTRAST-AND-ELEVATION-HIERARCHY] MANDAT KONTRAS PERMUKAAN MULTI-TIER, ELEVASI VISUAL (L0/L1/L2), & LARANGAN KERAS FLATNESS PSEUDO-GLASSMORPHISM (ANTI-CAMOUFLAGE UI)
- **1. Arsitektur Elevasi Permukaan 3-Tier Wajib:**
  - **L0 - Canvas Ground (Dasar Halaman):** Latar belakang kanvas wajib memiliki kontras tonal yang jelas dan terukur terhadap kartu di atasnya (`bg-slate-100/70` Daylight; `bg-[#070b12]` atau `bg-[#090d16]` Dark Obsidian bebas *OLED smearing*).
  - **L1 - Macro Surface (Kartu Kontainer Utama):** Seluruh kartu utama (kartu metrik KPI, kanban pipeline, panel modul, form container, bento grid) WAJIB menonjol tegas dari kanvas L0 (`bg-white` solid Daylight; `dark:bg-slate-900/90` Dark Obsidian) dengan garis batas presisi 1px (`border border-slate-200/80 dark:border-slate-800`) dan bayangan mikro `shadow-2xs`.
  - **L2 - Micro Surface (Elemen Tersemat di dalam Kartu):** Seluruh elemen anak di dalam kartu induk (kartu berkas pipeline, baris transaksi, card view tabel, item antrean) WAJIB memiliki kontras fisik yang jelas terhadap kontainer L1 (`bg-slate-50/70 hover:bg-white` atau `bg-white hover:bg-slate-50` Daylight; `dark:bg-slate-950/70 hover:dark:bg-slate-800` atau `dark:bg-slate-900` Dark Obsidian dengan `border border-slate-200 dark:border-slate-800/80` dan `shadow-2xs`). DILARANG KERAS menggunakan warna/token yang identik dengan kontainer induknya.
- **2. Larangan Keras Pseudo-Glassmorphism pada Kanvas Polos (Anti-Visual Camouflage):**
  - DILARANG KERAS menggunakan kombinasi `backdrop-blur` dengan transparansi rendah (`bg-white/40`, `dark:bg-white/[0.01-0.05]`, `border-white/5`) di atas kanvas solid/flat. Efek blur kaca HANYA diizinkan pada *Header Melayang* atau *Floating Thumb Dock* yang melayang di atas konten bergulir. Di atas kanvas statis, gunakan permukaan semi-opak solid dengan kontras persepsi APCA $L_c \ge 75$ dan delta luminansi $\Delta L \ge 12\%$ guna mencegah efek bunglon (*visual camouflage*) dan hilangnya batas fisik antar-elemen.
- **3. Rasio Kontras Garis & Teks (WCAG 2.2 AAA):**
  - Garis batas pemisah wajib memenuhi WCAG SC 1.4.11 ($\ge 3:1$) dan teks di dalam kartu wajib memenuhi APCA $L_c \ge 75$ (WCAG AAA $7:1$).

---

## 3. INVARIAN TEKNOLOGI STACK VALORA VAULT

### 3.1 Backend Invariants
- **PHP 8.3 / 8.4 dengan Laravel 13.**
- **Auth:** Laravel Sanctum SPA Session-Based.
  - **MUTLAK:** `SESSION_DRIVER=file` (Penyimpanan file sesi di disk NVMe, 0 koneksi DB).
  - **DILARANG KERAS:** `SESSION_DRIVER=database` (Mencegah kehabisan koneksi MySQL/SQLite).
- **Database:** SQLite WAL mode persisten:
  ```sql
  PRAGMA foreign_keys = ON;
  PRAGMA busy_timeout = 5000;
  PRAGMA journal_mode = WAL;
  PRAGMA synchronous = NORMAL;
  ```
- **Kueri Terproteksi:** Seluruh kueri wajib dibatasi limit ceiling aman (`limit(1000)`). Dilarang `Model::all()` tanpa batasan.

### 3.2 Frontend Invariants
- **Svelte 5 Runes SAJA:** `$state`, `$derived`, `$props`, `$effect`.
- **Pure JavaScript:** Tanpa modul `.ts`.
- **Single Page Application (SPA):** Static adapter build via `@sveltejs/adapter-static` (`fallback: 'index.html'`, `ssr = false`).
- **Styling:** Tailwind CSS v4 (@theme tokens, utility-first, Daylight & Dark Obsidian dual-theme).
- **Instant In-Memory Search:** Pencarian tabel dilakukan di browser melalui `$derived.by()` tanpa network trip ke DB per ketikan.

---

## 4. 5 GOLDEN INVARIANTS KEAMANAN KUNCI (CROSS-REPO)

- **[INV-SEC-01]** Larangan pembacaan/penulisan data tanpa otorisasi Policy (Mencegah BOLA).
- **[INV-SEC-02]** Larangan query mentah (Raw SQL) tanpa parameter binding.
- **[INV-SEC-03]** Larangan penyimpanan session di database (`SESSION_DRIVER=file` only).
- **[INV-SEC-04]** Wajib sanitasi input dan validasi FormRequest ketat.
- **[INV-SEC-05]** Respons error wajib berformat RFC 7807 tanpa leak info sensitif.
