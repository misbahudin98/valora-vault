# Universal System Invariants & Autonomous Agent Rules (GEMINI.md)
> **Engine:** Hyperion Vault v6.31.0 / v8.0.0 SDD-Enterprise-Deterministic  
> **Standards:** PER Coding Style 2.0, OWASP Top 10, WCAG 2.2 AAA, RFC 7807, SemVer 2.0.0

---

## 1. ATURAN KERJA UTAMA & ANTI-SLOP POLICY

Seluruh model AI (Gemini, Antigravity, Claude, Cursor) dan engineer manusia wajib mematuhi aturan ini:

1. **[RULE-01-NO-PLACEHOLDER] Kode 100% Utuh & Production-Ready:**
   - Dilarang keras menggunakan komentar pemalas atau kode rintisan: `// TODO: implement later`, `/* ...rest of code... */`, `// add your logic here`, atau `console.log('clicked')`.
   - Kode yang dihasilkan WAJIB tuntas, lulus pengujian, dan langsung siap dideploy ke produksi.

2. **[RULE-02-FULL-STATE-UI] Mandat 4 State Visual Komponen Svelte 5:**
   Setiap tampilan data WAJIB menangani 4 state:
   - Loading State (Skeleton loader reaktif).
   - Empty State (Ilustrasi dan panduan jelas jika data kosong).
   - Error State (Pesan ramah pengguna dengan tombol aksi 'Coba Lagi').
   - Success / Populated State (Tampilan tabel/grid normal + inline validation).

3. **[RULE-03-YAGNI-KISS] Anti-Overengineering:**
   - Gunakan Thin Controller, Form Request untuk validasi ketat, Eloquent API Resource untuk transformasi data.
   - Action Class HANYA jika melibatkan mutasi logika multi-tabel.

4. **[RULE-04-NO-GHOST-FEATURES] Larangan Fitur Liar di Luar Kontrak SDD:**
   - Dilarang keras menambahkan fitur liar (*ghost features*) yang tidak tercantum dalam dokumen kontrak SDD atau instruksi pengguna.
   - Fokus 100% pada ketahanan, efisiensi, dan keakuratan fitur yang diminta.

5. **[RULE-05-ATOMIC-MUTATION] Mutasi Defensif & Transaksional:**
   - Setiap berkas PHP diawali `declare(strict_types=1);`.
   - Mutasi multi-tabel WAJIB dibungkus `DB::transaction(fn() => ...)`.
   - Tangkap exception kritis, catat ke log, dan kembalikan format error RFC 7807.

6. **[RULE-06-REACTIVE-FEEDBACK] Aksesibilitas & Umpan Balik:**
   - Tombol wajib menyertakan `type="button"` / `type="submit"`, status disabled + spinner saat proses, toast notification interaktif, dan modal konfirmasi destruktif.

7. **[RULE-07-SINGLE-FEATURE-TURN] Granularitas Unit Kerja & Eksekusi Otonom (Single Feature Turn):**
   - Eksekusi kode HANYA 1 Fitur untuk 1 Role dalam satu giliran kerja (*Single Feature Turn*).
   - Jalankan seluruh tool calls (baca/tulis berkas, migrasi, penulisan test, perbaikan bug, dan kompilasi build) secara proaktif dan otonom dalam giliran tersebut.
   - Setelah selesai dan lulus 100%, sajikan formulir Review Gate resmi dan tunggu persetujuan manusia sebelum melangkah ke fitur berikutnya.

8. **[RULE-08-COMPACT-DENSITY] Desain Visual Pro-Grade & Ergonomi Mobile (Anti-Bloat/Anti-Sesak):**
   - **Eliminasi UI Balita ("Fisher-Price" Bloat):** Dilarang border tebal 2px, padding bertumpuk `p-4` di dalam card mobile, atau teks raksasa yang membuat viewport sesak dan boros ruang vertikal.
   - **Standar Pro-Grade Compact:** Gunakan garis batas 1px (*hairline* `border border-slate-200/80 dark:border-slate-800/80`), bayangan mikro `shadow-2xs`, padding kompak (`p-2` s/d `p-2.5`), dan tipografi proporsional (judul kartu `text-xs font-bold`, label/badge `text-[11px]` atau `text-[10px] uppercase font-bold`, angka `font-mono`).
   - **Micro Action Buttons:** Tombol aksi pada kartu/tabel mobile menggunakan tinggi kompak `min-h-[30px] px-2.5 py-1 text-[11px]` dengan area sentuh efektif.
   - **Accordion Menu Default Tertutup:** Di sidebar/drawer mobile, seluruh grup menu wajib tertutup (*collapsed*) secara default (`expandedTrees = { ...: false }`), hanya membuka rute yang sedang aktif secara otomatis guna mencegah drawer menggelembung panjang.
   - **Floating Thumb Dock (Navigasi 0-Scroll):** Wajib menyertakan kapsul floating di tengah bawah mobile (`fixed bottom-3.5 left-1/2 -translate-x-1/2 z-30 md:hidden`) berisi pintasan *Beranda*, *Segarkan*, dan *Menu* agar pengguna saat membaca data panjang tidak perlu scroll ke atas untuk berpindah rute. Hindari bilah footer statis tebal yang memotong permanen layar HP. Viewport `<main>` wajib memiliki safe padding `pb-16 sm:pb-5`.

9. **[RULE-09-NO-ALIAS] Larangan Keras Pembuatan Alias & Proxy Penamaan (Zero Aliasing Policy):**
   - DILARANG KERAS membuat nama alias, mapping alias (`aliases`, `alias_map`), wrapper alias, shorthand re-export, atau sinonim buatan untuk peran (*roles*), izin (*permissions*), rute (*routes*), method controller, tabel, kolom basis data, variabel state, maupun komponen antarmuka.
   - Setiap entitas wajib dipanggil dan direferensikan secara eksplisit menggunakan nama kanonikal aslinya (Single Source of Truth).
   - Aliasing dilarang mutlak karena menciptakan ambiguitas, memicu celah otorisasi (BOLA), dan menyebabkan cacat regresi tersembunyi.

10. **[RULE-10-NO-UNAUTHORIZED-CONSOLIDATION] Larangan Konsolidasi Sepihak Tanpa Izin (Zero Unauthorized Consolidation):**
    - DILARANG KERAS menggabungkan (*merging/collapsing*) beberapa file modul independen, controller, skrip terpisah, atau komponen antarmuka menjadi modul monolitik raksasa (*mega-bundle*).
    - DILARANG mengonsolidasi atau menormalisasi peran pengguna (*roles*) menjadi peran gabungan (`consolidation_map`, `normalizeRoles`, dll.). Seluruh peran wajib diakui dan disimpan secara mandiri dan presisi.
    - DILARANG mengonsolidasi tabel relasional 3NF menjadi kolom array/JSON liar.
    - Pertahankan batas domain (*bounded context*) dan *Single Responsibility Principle* (SRP) pada setiap komponen dan berkas, kecuali jika pengguna secara tertulis menginstruksikan konsolidasi.

11. **[RULE-11-ZERO-REGRESSION-EXECUTION] Garansi 100% Zero Bug & Zero Regression Setiap Eksekusi Prompt:**
    - Setiap eksekusi prompt (fitur baru, perbaikan bug, atau refaktorisasi) WAJIB menjamin 100% bebas dari regresi dan TIDAK menimbulkan masalah/bug baru pada fitur yang telah ada.
    - Wajib mempertahankan kompatibilitas mundur (*backward compatibility*): dilarang mengubah signature method publik, struktur respons API aktif, atau kolom database yang telah digunakan fitur lain tanpa migrasi defensif.
    - Agen WAJIB menjalankan uji otomatis (`php artisan test tests/Feature` dan `npm run build`) hingga 100% lulus sebelum menyelesaikan putaran kerja.

12. **[RULE-12-TABLE-CARD-AND-PAGE-SIZE] Mandat Mekanisme Card & Kontrol Baris per Halaman (Semua Tabel):**
    - Seluruh tabel data WAJIB menyediakan mekanisme tampilan kartu (*Card View*). Pada layar ponsel / mobile (≤ 768px), susunan kartu (*Card Stack*) wajib aktif secara otomatis tanpa memaksa scroll horizontal yang merusak keterbacaan. Pada layar desktop/laptop, sediakan opsi tampilan tabel dan kartu yang rapi.
    - Setiap tabel WAJIB menyediakan kontrol interaktif jumlah baris yang ditampilkan per halaman (*Page Size Selector*: 10, 25, 50, 100 entri) yang dapat disetel langsung oleh pengguna di bilah kontrol atau footer paginasi.

13. **[RULE-13-SPACIOUS-BREATHING-ROOM] Mandat Ruang Bernapas & Tampilan Anti-Sesak (HP & Laptop):**
    - DILARANG KERAS membuat tampilan antarmuka yang terlihat sesak, padat berhimpitan, atau penuh sesak (*cramped*) baik di layar HP maupun laptop/desktop.
    - Terapkan ruang bernapas visual (*whitespace*) yang proporsional: jarak antar-elemen terukur (`gap-2.5` s/d `gap-4`), padding seimbang (`p-3` s/d `p-5`), garis pembatas tipis 1px (*hairline*), kontainer pembatas lebar (*max-w-7xl* / *max-w-[1550px]*), serta safe padding bawah (`pb-16 sm:pb-8`) agar konten terbawah tidak tertutup navigasi jempol (*Floating Thumb Dock*).

14. **[RULE-14-TAB-PANE-VISIBILITY] Mandat Keterbacaan & Visibilitas Utuh Tab Pane (Anti-Hidden Tabs):**
    - Setiap tab pane (subtab navigasi, header modal tabs, segmented control) WAJIB menjamin keterbacaan tinggi dan DILARANG menyembunyikan atau memotong menu/tab dari pandangan pengguna baik di HP maupun di laptop.
    - Untuk tab pane dengan jumlah dan panjang kata yang moderat (≤ 6 tab): seluruh tab WAJIB langsung terlihat utuh tanpa memaksa pengguna menggunakan scroll horizontal (`overflow-x-auto whitespace-nowrap`). Gunakan pembungkusan baris responsif (*responsive multi-line wrap* `flex-wrap gap-1.5`) agar seluruh tab langsung terbaca sekilas dan dapat ditekan seketika.

15. **[RULE-15-ELEGANT-MINIMALISM] Mandat Minimalisme Elegan, Keterbacaan Tipografi, Negative Space, & Aksesibilitas Universal (WCAG 2.2 AAA):**
    - **Filosofi Inti (Dieter Rams & Mies van der Rohe):** *"Weniger, aber besser"* (Kurang, tapi lebih baik). Hapus ornamen yang tidak perlu, border bertumpuk, atau saturasi warna agresif. Keanggunan dicapai melalui fungsi murni, ketenangan visual (*quiet luxury / visual serenity*), dan kejelasan hierarki.
    - **Keterbacaan & Hierarki Tipografi (Robert Bringhurst & Ellen Lupton):**
      - Terapkan batas panjang baris ideal (*optimal measure*): 45–75 karakter per baris (CPL). DILARANG membiarkan teks mengalir membentang 100% melintasi monitor ultra-lebar.
      - Skala modular proporsional: Bedakan hierarki dengan kontras bobot (*font-bold*) dan warna teks terukur (`text-slate-900 dark:text-white` untuk judul, `text-slate-600 dark:text-slate-400` untuk body, `text-slate-400 dark:text-slate-500` untuk teks bantu).
      - Wajib gunakan `font-mono` untuk data tabular, angka keuangan, timestamp, UUID, dan hash teknis agar mudah dipindai secara vertikal (*vertical alignment scannability*).
    - **Negative Space & Rasio Data-ke-Tinta (Edward Tufte & Refactoring UI):**
      - Maksimalkan *Data-to-Ink Ratio*: Biarkan ruang bernapas (*whitespace / negative space*) memisahkan kelompok logis (Gestalt Law of Proximity), bukan kotak di dalam kotak (*nested container bloat*) atau border tebal.
      - Garis batas halus (*subtle hairline 1px border* `border-slate-200/80 dark:border-slate-800/80`) dan bayangan mikro lembut (`shadow-2xs`).
    - **Batas Kontainer Terkontrol (Restrained Layout Ceilings):**
      - Konten formulir dan teks baca dibatasi maksimal `max-w-3xl` / `max-w-4xl`.
      - Dashboard dan tabel data analitik dibatasi maksimal `max-w-6xl` / `max-w-7xl` dengan margin otomatis seimbang (`mx-auto px-4 sm:px-6 lg:px-8`). Konten DILARANG menempel atau meregang liar ke tepi terluar layar (*edge-to-edge cramming*).
    - **Aksesibilitas Universal (WCAG 2.2 AAA & Apple HIG):**
      - Rasio kontras teks minimal 4.5:1 terhadap latar belakang (AA) dan target 7:1 (AAA).
      - Target sentuh minimal $44 \times 44\text{px}$ pada tombol utama dan aksi jempol mobile; $24 \times 24\text{px}$ minimum absolut untuk tombol mikro pada tabel padat (WCAG SC 2.5.8).
      - Ring fokus keyboard yang tegas dan kontras (`focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:outline-none`).
      - Struktur HTML semantik (`<main>`, `<nav>`, `<aside>`, `<header>`, `<footer>`) dan atribut ARIA eksplisit.

16. **[RULE-16-MASTERPIECE-AUTHENTICITY] Standar Desain Masterpiece, Keaslian Karya (Human-Crafted Feel), & Kebijakan Anti-AI Slop:**
    - **Eliminasi Klise Visual AI (Zero AI Design Clichés):**
      - DILARANG KERAS menggunakan latar belakang gradasi ungu-pink neon (`from-indigo-500 via-purple-500 to-pink-500`), cyan glow berlebihan, blob blur lingkaran mengambang tanpa guna (`blur-3xl opacity-30`), efek frosted glass / glassmorphism buram yang merusak keterbacaan, atau ikon sparkle/bintang AI tanpa arti.
    - **Karya Berkarakter & Human-Crafted Aesthetic:**
      - Gunakan palet tonal teredam bermartabat (*dignified muted tonal palette*): monokrom Slate/Stone/Obsidian yang tenang dipadukan dengan tepat 1 warna aksen fungsional berkarakter (Emerald tajam untuk Yayasan PQTM, Indigo/Emerald untuk Hyperion Vault).
      - Tipografi berbobot dan berirama (*editorial rhythm*): heading tegas, label mikro terukur, font-mono untuk angka/data tabular.
      - Tekstur dan pembatas presisi: hairline 1px borders (`border border-slate-200/80 dark:border-slate-800/80`), micro shadows 2xs, dan negative space lapang.
    - **Harmoni Fungsionalitas & Zero Superfluous Elements:**
      - Setiap elemen antarmuka wajib memiliki alasan keberadaan fungsional (*purpose-driven UI*). Jika suatu elemen visual tidak membantu navigasi atau pemahaman data, hapus elemen tersebut (*Ockham's Razor UI*).

17. **[RULE-17-CLIENT-SIDE-CSV-EXPORT] Mandat Ekspor CSV Front-End pada Semua Tabel Data (Zero-Roundtrip CSV):**
    - Seluruh tabel data WAJIB menyediakan tombol aksi ekspor CSV (`Export CSV` / `Unduh CSV`) langsung dari sisi peramban (*client-side memory*).
    - Proses ekspor DILARANG memicu query ulang atau request berlebih ke server backend untuk data yang telah ter-load di state/store frontend (*zero round-trip*).
    - Wajib menggunakan standar format CSV yang aman dan kompatibel dengan Microsoft Excel dan Google Sheets:
      - Menambahkan UTF-8 Byte Order Mark (BOM: `\uFEFF`) pada awal file agar karakter lokal/spesial dan aksara terbaca sempurna tanpa *mojibake*.
      - Sanitasi nilai cell: bungkus nilai bertipe string dalam tanda kutip dua (`"..."`) dan lakukan escaping terhadap tanda kutip internal (`""`).
      - Cegah CSV Formula Injection CWE-1236 (mitigasi sel yang diawali `=`, `+`, `-`, atau `@` dengan menambahkan prefix petik tunggal).
      - Penamaan file otomatis yang semantik dan menyertakan timestamp/tanggal (`[nama_tabel]_[YYYY-MM-DD].csv`).
      - Komponen tabel kanonikal [`DataTable.svelte`](frontend/src/lib/components/ui/DataTable.svelte) dan utility [`csvExport.js`](frontend/src/lib/utils/csvExport.js) wajib dijadikan referensi SSoT implementasi.

18. **[RULE-18-COLOR-CONTRAST-AND-MOTION-CHOREOGRAPHY] Mandat Sains Warna 60-30-10, Kontras Persepsi APCA / WCAG AAA, & Koreografi Motion GSAP:**
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

19. **[RULE-19-SPATIAL-PROPORTION-AND-BALANCE] Mandat Sains Spasial, Ritme Geometris, Keseimbangan Simetri-Asimetri, & Negative Space:**
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

20. **[RULE-20-UNIVERSAL-DOCUMENTATION-ARCHITECTURE] Mandat Arsitektur Dokumentasi Multi-Persona & Diátaxis Framework:**
    - **Adaptasi 4 Persona (Universal Usability):** Setiap proyek WAJIB menyediakan dokumentasi berjenjang yang melayani 4 persona:
      1. *User Operasional (Staf):* Checklist prosedural cepat, diagram alur, penanganan error.
      2. *User Awam (Klien):* Bahasa empatik, transparansi proses & biaya, analogi dunia nyata, FAQ non-teknis.
      3. *Profesional (Notaris, Auditor, Arsitek):* Rujukan dasar hukum/regulasi formal, diagram C4 (Level 1–4), skema 3NF, audit trail.
      4. *Baby Boomers (Generasi Senior / Lansia):* Tipografi kontras tinggi (7:1), ukuran font nyaman, instruksi linier bernomor tanpa langkah tersembunyi (*zero hidden steps*), analogi konkret, dan pesan peredam kecemasan (*fear-of-error reduction*).
    - **Kuadran Diátaxis & Keterbacaan Plain Language (ISO 24495-1):**
      - Pisahkan dokumen ke dalam 4 kuadran: Tutorials, How-To Guides, Reference, dan Explanation.
      - Gunakan kalimat aktif (*active voice*), batas rata-rata 15–20 kata per kalimat, dan target keterbacaan Flesch-Kincaid Grade Level 7–8.
    - **Visualisasi Formal (BPMN 2.0 & C4 Model):**
      - Diagram alur bisnis wajib menggunakan swimlanes BPMN 2.0.
      - Arsitektur teknis sistem wajib menggunakan The C4 Model (Context, Container, Component, Code).

21. **[RULE-21-FILTER-AND-SORT-ENGINE] Mandat Mesin Filter Dinamis, Kolom Sort Multi-Tipe, & Pemisahan Tabel Tunggal (Semua Fitur Operasional):**
    - **Filter Instan In-Memory:** Setiap tabel data operasional wajib menyediakan kontrol pencarian debounced 120ms ke seluruh kolom kunci, serta segmented tabs/pills ber-wrap responsif (`flex-wrap gap-1.5` sesuai RULE-14).
    - **Sorting Multi-Tipe Determinik:** Pengurutan dua arah (Ascending/Descending) wajib type-aware: perbandingan timestamp numerik untuk tanggal, komparasi integer murni untuk nominal uang/angka, perbandingan alfabetis lokal bahasa Indonesia untuk teks, dan urutan sekuensial status domain.
    - **Pemisahan Tabel Tunggal per Halaman (Anti-Multi-Table Bloat):** DILARANG menumpuk lebih dari 1 tabel data independen dalam 1 viewport halaman gulung. Entitas berbeda WAJIB dipisahkan ke dalam sub-halaman atau sub-tab dengan tree menu navigasi sidebar yang terisolasi guna mencegah *cognitive overload* dan kelelahan gulung vertikal mobile.

22. **[RULE-22-SURFACE-CONTRAST-AND-ELEVATION-HIERARCHY] Mandat Kontras Permukaan Multi-Tier, Elevasi Visual (L0/L1/L2), & Larangan Keras Flatness Pseudo-Glassmorphism (Anti-Camouflage UI):**
    - **Arsitektur Elevasi Permukaan 3-Tier Wajib:**
      - **L0 - Canvas Ground (Dasar Halaman):** Latar belakang kanvas wajib memiliki kontras tonal yang jelas dan terukur terhadap kartu di atasnya (`bg-slate-100/70` Daylight; `bg-[#070b12]` atau `bg-[#090d16]` Dark Obsidian bebas *OLED smearing*).
      - **L1 - Macro Surface (Kartu Kontainer Utama):** Seluruh kartu utama (kartu metrik KPI, kanban pipeline, panel modul, form container, bento grid) WAJIB menonjol tegas dari kanvas L0 (`bg-white` solid Daylight; `dark:bg-slate-900/90` Dark Obsidian) dengan garis batas presisi 1px (`border border-slate-200/80 dark:border-slate-800`) dan bayangan mikro `shadow-2xs`.
      - **L2 - Micro Surface (Elemen Tersemat di dalam Kartu):** Seluruh elemen anak di dalam kartu induk (kartu berkas pipeline, baris transaksi, card view tabel, item antrean) WAJIB memiliki kontras fisik yang jelas terhadap kontainer L1 (`bg-slate-50/70 hover:bg-white` atau `bg-white hover:bg-slate-50` Daylight; `dark:bg-slate-950/70 hover:dark:bg-slate-800` atau `dark:bg-slate-900` Dark Obsidian dengan `border border-slate-200 dark:border-slate-800/80` dan `shadow-2xs`). DILARANG KERAS menggunakan warna/token yang identik dengan kontainer induknya.
    - **Larangan Keras Pseudo-Glassmorphism pada Kanvas Polos (Anti-Visual Camouflage):**
      - DILARANG KERAS menggunakan kombinasi `backdrop-blur` dengan transparansi rendah (`bg-white/40`, `dark:bg-white/[0.01-0.05]`, `border-white/5`) di atas kanvas solid/flat. Efek blur kaca HANYA diizinkan pada *Header Melayang* atau *Floating Thumb Dock* yang melayang di atas konten bergulir. Di atas kanvas statis, gunakan permukaan semi-opak solid dengan kontras persepsi APCA $L_c \ge 75$ dan delta luminansi $\Delta L \ge 12\%$ guna mencegah efek bunglon (*visual camouflage*) dan hilangnya batas fisik antar-elemen.

---

## 2. INVARIAN STACK HYPERION VAULT

- **Backend:** Laravel 13, PHP 8.3 / 8.4.
  - **Auth:** Laravel Sanctum SPA Session-Based (`SESSION_DRIVER=file` MANDATORY; `SESSION_DRIVER=database` FORBIDDEN).
  - **Database:** SQLite WAL mode (`PRAGMA foreign_keys=ON; PRAGMA busy_timeout=5000; PRAGMA journal_mode=WAL; PRAGMA synchronous=NORMAL;`).
  - **Safe Query Ceilings:** Limit kueri 1000 item (`limit(1000)`). Dilarang `Model::all()` tanpa batas.
- **Frontend:**
  - **Svelte 5 Runes SAJA:** `$state`, `$derived`, `$props`, `$effect`. Dilarang sintaks legasi Svelte 4 (`export let`, `$:`, stores).
  - **100% Pure JavaScript:** DILARANG file `.ts` atau sintaks TypeScript.
  - **Tailwind CSS v4:** Utility-first dengan token `@theme`, dual-theme Daylight & Dark Obsidian.
  - **Pencarian In-Memory:** Filter data tabel pada memori browser via `$derived.by()` di `DataTable.svelte` (< 0.1ms).

---

## 3. 5 GOLDEN INVARIANTS KEAMANAN KUNCI

- **[INV-SEC-01]** Larangan pembacaan/penulisan data tanpa otorisasi Policy (Cegah BOLA).
- **[INV-SEC-02]** Larangan query mentah (Raw SQL) tanpa parameter binding (Cegah SQL Injection).
- **[INV-SEC-03]** Larangan penyimpanan session di database (`SESSION_DRIVER=file` only).
- **[INV-SEC-04]** Wajib sanitasi input dan validasi FormRequest ketat.
- **[INV-SEC-05]** Respons error wajib berformat RFC 7807 tanpa membocorkan struktur internal server.

---

## 4. PROTOKOL HUMAN-IN-THE-LOOP EKSEKUSI SDD

1. **Zero-Bulk Generation:** Dilarang mengenerate seluruh modul/fitur sekaligus dalam satu putaran.
2. **Granularitas Unit Kerja:** Eksekusi kode HANYA 1 Fitur untuk 1 Role dalam satu giliran kerja (*Single Feature Turn*).
3. **Eksekusi Otonom Dalam Giliran:** Selama menyelesaikan unit kerja tersebut, jalankan seluruh aksi alat (*tool calls*, baca/tulis berkas, migrasi, penulisan test, perbaikan bug, dan kompilasi *build*) secara proaktif dan otonom tanpa meminta izin manual per baris perintah.
4. **Review Gate & Approval Loop:**
   - Tuntaskan implementasi end-to-end fitur (Migration, Model, FormRequest, Policy, Controller, Test, Svelte 5 View) hingga `php artisan test` dan `npm run build` lulus 100%.
   - Berikan ringkasan perubahan teknis dan ajukan formulir Review Gate resmi ke pengguna untuk konfirmasi sebelum melangkah ke fitur berikutnya.

---

## 5. PETA 5+2 ENGINE ARSITEKTUR HYPERION VAULT

| Domain Arsitektur | Invarian Sistem (Rules) | Playbook Eksekusi (Skill) | SSoT Dokumen (Whitepaper) |
| :--- | :--- | :--- | :--- |
| **1. Discovery & Rekayasa Kebutuhan** | [`.agents/rules/product_discovery_invariants.md`](.agents/rules/product_discovery_invariants.md) | [`.agents/skills/discovery-architect/SKILL.md`](.agents/skills/discovery-architect/SKILL.md) | [`doc/software_design_standards.md`](doc/software_design_standards.md) |
| **2. Blueprint Studio & ERD Canvas** | [`.agents/rules/blueprint_studio_invariants.md`](.agents/rules/blueprint_studio_invariants.md) | Antarmuka GUI [`/admin/studio`](frontend/src/routes/admin/studio/+page.svelte) | [`doc/features/05_blueprint_studio.md`](doc/features/05_blueprint_studio.md) |
| **3. Dasbor Analitik & Multi-Temporal**| [`.agents/rules/temporal_dashboard_invariants.md`](.agents/rules/temporal_dashboard_invariants.md) | [`.agents/skills/dashboard-architect/SKILL.md`](.agents/skills/dashboard-architect/SKILL.md) | [`doc/data_analysis_standard.md`](doc/data_analysis_standard.md) |
| **4. Clean Code & Zero-Regression**  | [`.agents/rules/clean_architecture_invariants.md`](.agents/rules/clean_architecture_invariants.md) | [`.agents/skills/clean-code-refactor/SKILL.md`](.agents/skills/clean-code-refactor/SKILL.md) | [`doc/architecture.md`](doc/architecture.md) & [`doc/testing_standards.md`](doc/testing_standards.md) |
| **5. Security Hardening & Zero-Vuln** | [`.agents/rules/security_invariants.md`](.agents/rules/security_invariants.md) | [`.agents/skills/security-hardening/SKILL.md`](.agents/skills/security-hardening/SKILL.md) | [`doc/security_standards.md`](doc/security_standards.md) |
| **+1. Landing & Growth Architect**   | [`.agents/rules/landing_page_invariants.md`](.agents/rules/landing_page_invariants.md) | [`.agents/skills/landing-architect/SKILL.md`](.agents/skills/landing-architect/SKILL.md) | [`doc/landing_page_standards.md`](doc/landing_page_standards.md) |
| **+2. Prompt Architect & AI Audit**   | [`.agents/rules/prompt_architect_trigger.md`](.agents/rules/prompt_architect_trigger.md) | [`.agents/skills/prompt-architect/SKILL.md`](.agents/skills/prompt-architect/SKILL.md) | [`doc/prompt.feature.md`](doc/prompt.feature.md) |

