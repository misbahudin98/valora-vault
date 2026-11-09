# Invariants Sistem: Rekayasa Landing Page & Optimasi Penemuan (landing_page_invariants.md)
> **Engine:** Valora Vault v6.31.0 / v8.0.0 SDD-Enterprise-Deterministic  
> **Standards:** Google Core Web Vitals (2024/2026), Princeton GEO (Aggarwal et al., 2023), Answer.ai llms.txt (Jeremy Howard, 2024), MECLABS Conversion Heuristic, StoryBrand SB7 (Donald Miller), Julian Shapiro CRO, WCAG 2.2 AAA, Schema.org JSON-LD  
> **Target:** Autonomous Coding Agents (Antigravity, Gemini, Claude, Cursor) & Human Engineers  
> **Trigger Tags:** `@landing`, `@landing-page`, `@seo`, `@geo`, `@cro`, `@aio` | **Playbook:** [`.agents/skills/landing-architect/SKILL.md`](../skills/landing-architect/SKILL.md)

---

## 1. PRINSIP ARSITEKTUR UTAMA

Setiap perancangan, copywriting, dan implementasi kode Landing Page wajib mematuhi 7 Invarian Mutlak berikut:

```
┌────────────────────────────────────────────────────────────────────────┐
│ 7 GOLDEN INVARIANTS LANDING PAGE & GROWTH ARCHITECTURE                 │
├────────────────────────────────────────────────────────────────────────┤
│ 1. [INV-LAND-01] CORE_WEB_VITALS_PERFORMANCE_BUDGET (LCP, INP, CLS)   │
│ 2. [INV-LAND-02] QUAD_BOT_OPTIMIZATION (SEO, AEO, GEO, AIO/llms.txt)   │
│ 3. [INV-LAND-03] STORYBRAND_AND_CRO_CONVERSION_ANATOMY (SB7 + MECLABS)│
│ 4. [INV-LAND-04] MOTION_TIERING_AND_REDUCED_MOTION (CSS vs Svelte/GSAP)│
│ 5. [INV-LAND-05] TYPOLOGY_MATCHED_UX_DENSITY (B2B, SaaS, Ecom, Agency) │
│ 6. [INV-LAND-06] MOBILE_FIRST_THUMB_ZONE_ERGONOMICS (Touch Target 44px)│
│ 7. [INV-LAND-07] DETERMINISTIC_VERIFICATION_AND_NO_PLACEHOLDER         │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 2. 7 INVARIAN MUTLAK REKAYASA LANDING PAGE

### [INV-LAND-01] Anggaran Kinerja Core Web Vitals (Core Web Vitals Performance Budget)
Landing page merupakan pintu gerbang konversi; kegagalan performa berakibat langsung pada lonjakan *bounce rate* dan de-ranking oleh mesin pencari:
1. **Largest Contentful Paint (LCP) $\le 2.5\text{ detik}$:**
   - Elemen visual Hero (gambar/mockup) wajib memakai format WebP/AVIF dengan kompresi teroptimasi.
   - Wajib menyertakan atribut `fetchpriority="high"` dan `loading="eager"` pada gambar utama LCP di atas lipatan layar (*above-the-fold*).
   - Dilarang memuat background video berukuran berat ($> 1.5\text{MB}$) secara autoplay di viewport awal tanpa poster image statis.
2. **Interaction to Next Paint (INP) $\le 200\text{ ms}$:**
   - Dilarang mengeksekusi komputasi JavaScript monolitik pada main-thread saat pengguna mengklik CTA atau membuka accordion.
   - Seluruh event listener interaksi sekunder wajib di-debounce/throttle.
3. **Cumulative Layout Shift (CLS) $\le 0.1$:**
   - Seluruh elemen gambar, SVG, dan embed video WAJIB memiliki rasio aspek atau atribut `width` dan `height` eksplisit.
   - Dilarang menginjeksi banner promo, floating popup, atau pengumuman dinamis di atas konten utama tanpa reservasi ruang layout (skeleton placeholder).

### [INV-LAND-02] Penyelarasan Kuartet Optimasi Penemuan (SEO, AEO, GEO, AIO)
Landing page wajib dapat dibaca, dipahami, dan disintesis oleh mesin pencari web konvensional maupun penalaran LLM:
1. **SEO (Search Engine Optimization):**
   - Tepat satu tag `<h1>` per halaman yang memuat *Primary Value Proposition*.
   - Hierarki heading wajib linier tanpa melompati level (`h1` ➔ `h2` ➔ `h3`).
   - Tag semantik wajib diterapkan: `<header>`, `<nav>`, `<main>`, `<section id="...">`, `<article>`, `<footer>`.
   - Metadata lengkap: Canonical URL, Meta Title ($\le 60$ karakter), Meta Description ($\le 155$ karakter), OpenGraph, dan Twitter Card.
2. **AEO (Answer Engine Optimization):**
   - Menjawab pertanyaan pengguna secara langsung menggunakan gaya penulisan *Inverted Pyramid* (kesimpulan di kalimat awal).
   - Setiap pertanyaan FAQ atau subjudul solusi wajib memuat blok jawaban ringkas 40–60 kata yang siap diekstrak oleh Google Featured Snippets dan Voice Search.
3. **GEO (Generative Engine Optimization - Standar Riset Princeton):**
   - Mematuhi kaidah *Aggarwal et al. (2023)* untuk memaksimalkan probabilitas disitasi oleh ChatGPT Search, Perplexity, Claude, dan Gemini:
     - **Authoritative Citations:** Sertakan rujukan standar formal (ISO, OWASP, Gartner, dsb.) dan kutipan pakar terverifikasi.
     - **Statistical Evidence:** Sajikan angka numerik konkret, persentase efisiensi, dan tolok ukur terukur (hindari klaim subjektif kosong).
     - **Direct Comparison Matrix:** Sediakan tabel komparasi perbandingan tabular eksplisit (*Cara Lama/Kompetitor vs Solusi Anda*).
4. **AIO (AI & Agent Optimization):**
   - Wajib menyediakan berkas `/llms.txt` terstruktur di root domain mengacu pada standar Jeremy Howard (Answer.ai).
   - Injeksi Schema.org JSON-LD lengkap yang tervalidasi: `Organization`, `WebSite`, `Product` / `SoftwareApplication`, `FAQPage`, dan tautan relasi entitas `sameAs`.

### [INV-LAND-03] Anatomi Konversi StoryBrand & CRO (SB7 & MECLABS Heuristic)
Setiap tata letak landing page wajib menyusun narasi psikologis konversi berstruktur:
1. **Hero Section:**
   - Persona & Masalah Kontekstual yang dialami audiens.
   - Value Proposition yang jelas dalam 5 detik pertama (What it is, Who it is for, What benefit it delivers).
   - Primary Call-to-Action (CTA) berdaya kontras tinggi + Micro-proof ("Tanpa Kartu Kredit", "Setup 2 Menit").
2. **Trust / Authority Bar:**
   - Logo mitra/klien, sertifikasi keamanan, atau pengakuan industri.
3. **Agitasi Masalah (Problem & Friction):**
   - 3 rasa sakit utama (*villains/pain points*) yang dirasakan pengguna jika tidak menggunakan solusi.
4. **Pilar Solusi & Mekanisme (The Guide & 3-Step Plan):**
   - 3 tahapan langkah mudah untuk memulai (StoryBrand Plan).
5. **Bukti Transformasi (Social Proof):**
   - Testimoni otentik dengan foto, nama lengkap, peran, dan metrik hasil terukur.
6. **Matriks Komparasi Nilai:**
   - Tabel perbandingan fitur & benefit penentu keputusan.
7. **Mitigasi Risiko & FAQ (Anxiety Reversal):**
   - Menghilangkan friksi keraguan pembeli (garansi uang kembali, keamanan data, kemudahan migrasi).
8. **Final CTA & Sticky Action:**
   - Penegasan kembali ajakan bertindak dengan tombol penutup yang jelas.

### [INV-LAND-04] Alokasi Berjenjang Animasi, Kontras & Aksesibilitas (Motion Tiering & Reduced Motion)
Animasi harus meningkatkan pemahaman, bukan memperlambat atau membuat pusing:
1. **Tier 1 — Pure CSS3 (GPU Composited):**
   - Digunakan untuk 80% kebutuhan visual: Hover button, state glow, skeleton shimmer, dan transisi warna.
   - HANYA menganimasikan properti GPU: `transform: translate3d()` dan `opacity`.
   - **DILARANG KERAS** menganimasikan properti dimensi/layout geometri (`top`, `left`, `width`, `height`, `margin`, `padding`) karena memicu Reflow & Layout Shift.
2. **Tier 2 — Svelte 5 Native Runes & `svelte/motion`:**
   - Digunakan untuk transisi siklus hidup DOM: Buka/tutup modal, accordion collapse, drawer, dynamic counter (`tweened`/`spring`).
3. **Tier 3 — GSAP + ScrollTrigger / Lenis (Hanya untuk Timeline Terikat Scroll):**
   - HANYA diizinkan jika terdapat kebutuhan *scrubbed timeline* berdasarkan posisi scroll, *pinning section* horizontal, atau *SVG path morphing*.
   - Wajib menerapkan *lazy loading* untuk pustaka GSAP (hanya dimuat saat elemen masuk ke viewport).
   - Wajib isolasi memori via `gsap.context()` dan panggil `ctx.revert()` saat komponen di-unmount.
4. **Tier 4 — Canvas / WebGL / Three.js Strict Budgeting (Standar Web3 & Immersive Web):**
   - HANYA diizinkan sebagai aksen visual komplementer, DILARANG memblokir *Main Thread* atau menghalangi teks terbaca.
   - Wajib diisolasi via `IntersectionObserver`: jeda animasi render loop (`cancelAnimationFrame`) seketika saat elemen berada di luar viewport untuk mencegah boros baterai dan panas CPU/GPU.
   - Wajib menyediakan *instant static fallback* (gambar WebP/AVIF ringan) pada perangkat mobile berdaya rendah atau saat pengguna mengaktifkan `prefers-reduced-motion`.
5. **Disiplin Koreografi Bersarang (Nested Motion Discipline):**
   - **Gerak Makro Kontainer (Kartu, Panel Bento):** `y: 20px` s/d `30px`, durasi $0.4\text{s} - 0.5\text{s}$, stagger $0.1\text{s} - 0.15\text{s}$.
   - **Gerak Mikro Tipografi (Judul, Subjudul, Paragraf):** DILARANG melempar teks $> 20\text{px}$. Teks bergerak vertikal mikro hanya `y: 8px` s/d `14px`, durasi $0.25\text{s} - 0.35\text{s}$, dengan stagger antar-baris/kata $0.04\text{s} - 0.08\text{s}$.
   - **Kurva Easing & Plafon Durasi:** Gunakan `cubic-bezier(0.16, 1, 0.3, 1)` / `power2.out`. Plafon durasi total $\le 600\text{ms}$.
6. **Proporsi Warna 60-30-10, Elevasi Permukaan 3-Tier & Kontras APCA / WCAG 2.2 AAA:**
   - **Proporsi 60-30-10:** 60% kanvas dominan, 30% kontainer/kartu/garis rambut 1px, 10% tombol aksen CTA.
   - **Arsitektur Elevasi 3-Tier (Anti-Camouflage Bento Grid):** Seluruh kartu fitur, kartu bento grid, testimoni, dan pricing box WAJIB memiliki pemisahan elevasi fisik tegas: L0 kanvas dasar (`bg-slate-100/70` Daylight; `bg-[#070b12]` / `bg-[#0a0a0a]` Dark Mode), L1 kartu makro solid (`bg-white` Daylight; `dark:bg-slate-900/90` Dark Mode dengan `border border-slate-200/80 dark:border-slate-800` dan `shadow-2xs`), L2 kartu tersemat/badge dengan diferensiasi jelas. DILARANG KERAS membuat kartu bento transparan pucat (`bg-white/40`, `dark:bg-white/[0.02]`) di atas kanvas flat yang menyebabkan batas kartu melebur dan tidak terbaca.
   - **Kontras APCA & WCAG AAA:** Kontras teks body wajib APCA $L_c \ge 75$ (WCAG AAA 7:1) dan heading $L_c \ge 60$ (WCAG AA 4.5:1). Garis batas interaktif wajib memenuhi rasio kontras minimal 3:1.
7. **Mandat Aksesibilitas WCAG 2.2 AAA (`prefers-reduced-motion`):**
   - Setiap berkas CSS dan skrip animasi GSAP/Canvas WAJIB menyertakan klausul `@media (prefers-reduced-motion: reduce)` atau deteksi JS `window.matchMedia`.
   - Jika pengguna mengaktifkan preferensi pengurangan gerak, seluruh animasi wajib dinetralkan seketika menjadi transisi statis / *instant cut*.
8. **Invarian Apple Spatial Materials & Contrast Shield:**
   - Gaya Glassmorphism HANYA diizinkan jika menerapkan *Contrast Shield* (`backdrop-blur-xl backdrop-saturate-150` dipadukan dasar warna solid berkepadatan minimal 70% di light maupun dark mode). Teks wajib memenuhi APCA $L_c \ge 75$ (WCAG AAA). Dilarang teks buram transparan.
   - Wajib menyertakan 1px garis spekular (*specular edge highlight*) di batas atas komponen kaca.
9. **Invarian Tactile Web Audio UI Feedback (Apple Earcons via Web Audio API):**
   - Suara UI HANYA berupa procedural synthesis via native Web Audio API (0 KB file eksternal) berdurasi $\le 150\text{ms}$.
   - Dilarang keras *autoplay sound* saat halaman dimuat awal. Suara hanya aktif pasca interaksi pengguna dan wajib menyediakan kontrol *Mute* yang tersimpan di `localStorage`.
   - Suara bersifat multimodal non-eksklusif (seluruh aksi wajib memiliki umpan balik visual yang mandiri).

### [INV-LAND-05] Keselarasan Tipologi UX Berdasarkan Model Bisnis
UI landing page wajib mengadaptasi karakteristik psikologis audiensnya:
1. **B2B Enterprise / High-Ticket:** Data density tinggi, warna korporat netral (Slate/Navy), garis 1px hairline, kartu verifikasi ISO/SOC2, CTA utama *Jadwalkan Demo* / *Kontak Konsultan*.
2. **B2C Micro-SaaS / DevTools:** Estetika modern / Dark Obsidian, cuplikan kode mono yang dapat disalin, visual UI interaktif atau sandbox, CTA utama *Mulai Gratis* tanpa kartu kredit.
3. **High-Ticket Consulting / Agency:** Tipografi editorial kontras (Serif dipadu Sans-Serif), studi kasus before-after mendalam, formulir kualifikasi selektif.
4. **E-Commerce / DTC:** Visual produk heroik, ulasan bintang UGC, urgensi stok/diskon elegan, CTA *Beli Sekarang* / *Add to Cart* yang sticky.
5. **EdTech / Kursus:** Narasi transformasi pencapaian, silabus modular minggu demi minggu, bukti video alumni, countdown timer batch pendaftaran.
6. **Web3 / DeFi / Cryptographic Protocol:** Estetika *Dark Mode Luxury* (Obsidian `#0a0a0a`, hairline border 1px `border-white/10`, Bento Grid asimetris); telemetri live protocol (TVL, volume 24h, audit badges CertiK/OpenZeppelin); *Progressive Custody Onboarding* (preview/sandbox bebas tanpa paksaan connect wallet di awal, wallet hanya saat mutasi on-chain); tanda tangan transaksi ramah manusia (*human-readable signatures*, anti-blind signing).

### [INV-LAND-06] Ergonomi Mobile-First & Zona Jempol (Thumb Zone)
Lebih dari 60% lalu lintas landing page berasal dari ponsel pintar:
1. **Patuhi Steven Hoober Thumb Zone:** Elemen interaktif kunci dan tombol aksi sekunder wajib dapat dijangkau oleh jempol satu tangan tanpa regangan berlebih.
2. **Floating Action Pill / Sticky Bottom Dock:** Sediakan bilah aksi ringkas di tengah bawah layar mobile (`fixed bottom-3.5 left-1/2 -translate-x-1/2 z-30 md:hidden`) yang memuat CTA utama dan tombol kontak instan.
3. **Minimum Target Sentuh:** Setiap tombol, tautan, dan input form wajib memiliki area sentuh minimal $44 \times 44\text{px}$.
4. **Pro-Grade Compact Typography:** Judul kartu `text-xs font-bold`, label `text-[11px]`, padding `p-2.5`, border 1px hairline anti-bloat.

### [INV-LAND-07] Verifikasi Deterministik & Kebijakan Anti-Slop (No Placeholder)
1. **Zero Placeholder Copywriting:** Dilarang keras menggunakan teks rintisan pemalas seperti *Lorem Ipsum*, `Teks judul di sini`, atau `Deskripsi singkat produk`. Setiap teks wajib ditulis utuh, persuasif, kontekstual, dan bermakna.
2. **State Lengkap Form:** Setiap formulir penangkapan prospek (Lead Capture / Contact / Newsletter) WAJIB menangani 4 visual state:
   - Loading State (Spinner tombol + status disabled).
   - Empty State (Placeholder input yang membimbing).
   - Error State (Validasi inline RFC 7807 ramah pengguna).
   - Success State (Pesan konfirmasi jelas + langkah instruksi selanjutnya).
3. **Zero Broken Links & Valid JSON-LD:** Seluruh anchor link wajib mengarah ke ID section yang valid, dan blok JSON-LD wajib lulus validasi sintaks Schema.org tanpa peringatan error.
