# Invariants Sistem: Dasbor Analitik & Pelaporan Multi-Temporal (temporal_dashboard_invariants.md)
> **Engine:** Valora Vault v6.31.0 / v8.0.0 SDD-Enterprise-Deterministic  
> **Standards:** Stephen Few (2013), Edward Tufte (2001), Ralph Kimball (2013), Ben Shneiderman (1996), Colin Ware (2020), CRISP-DM, DAMA-DMBOK 2nd Ed, Douglas Hubbard (2014), WCAG 2.2 AAA, RFC 7807, PER Coding Style 2.0  
> **Target:** Autonomous Coding Agents (Antigravity, Gemini, Claude, Cursor) & Human Engineers  
> **Trigger Tags:** `@dashboard`, `@analytics`, `#kpi` | **Playbook:** [`.agents/skills/dashboard-architect/SKILL.md`](../skills/dashboard-architect/SKILL.md)

---

## 1. PRINSIP ARSITEKTUR UTAMA

Setiap perancangan dan implementasi antarmuka analitik, dasbor eksekutif, serta pelaporan data berkala wajib mematuhi 9 Invarian Mutlak berikut:

```
┌────────────────────────────────────────────────────────────────────────┐
│ 9 GOLDEN INVARIANTS DASBOR ANALITIK & PELAPORAN MULTI-TEMPORAL         │
├────────────────────────────────────────────────────────────────────────┤
│ 1. [INV-DASH-01] MANDATORY_4_VISUAL_STATES (Loading, Empty, Err, Succ) │
│ 2. [INV-DASH-02] ZERO_CHARTJUNK_AND_FEW_RATIO (Bullet Graph & Spark)   │
│ 3. [INV-DASH-03] IN_MEMORY_CLIENT_INTERACTIVITY (< 0.1ms via $derived) │
│ 4. [INV-DASH-04] SAFE_QUERY_CEILINGS_AND_INDEX (limit 1000 + B-Tree)   │
│ 5. [INV-DASH-05] HYBRID_ROLLUP_ROUTING (On-the-fly vs Rollup Snapshot) │
│ 6. [INV-DASH-06] TIMEZONE_AND_ACADEMIC_DETERMINISM (WIB & July-June)   │
│ 7. [INV-DASH-07] METRIC_INTEGRITY_AND_ANTI_FALLACY (Weighted, Baseline)│
│ 8. [INV-DASH-08] CLIENT_SIDE_CSV_EXPORT (In-Memory, BOM UTF-8, Zero-Trip)│
│ 9. [INV-DASH-09] SPATIAL_MATERIALS_AND_MOBILE_RESTRAINT (Apple, OKLCH) │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 2. 8 INVARIAN MUTLAK MULTI-TEMPORAL

### [INV-DASH-01] Mandat 4 State Visual Komponen (Mandatory 4 Visual States)
Setiap komponen visual analitik (kartu ringkasan KPI, grafik batang/tren, tabel data rincian) WAJIB menangani 4 status visual secara eksplisit tanpa asumsi:
1. **Loading State:** Skeleton/Shimmer loaders dengan dimensi proporsional yang merefleksikan tata letak akhir (mencegah *Cumulative Layout Shift* / CLS).
2. **Empty State:** Ilustrasi visual zero-data, penjelasan ramah dalam Bahasa Indonesia, dan instruksi penyesuaian filter waktu.
3. **Error State:** Panel error berstandar RFC 7807 dengan rincian pesan aman (tanpa membocorkan struktur internal server/SQL) dan tombol aksi interaktif `type="button"` bertuliskan "Coba Lagi" (*retry CTA*).
4. **Success State:** Tampilan data normal dengan visualisasi kontras tinggi, angka terformat rapi, dan transisi halus.

### [INV-DASH-02] Eliminasi Total Chartjunk & Rasio Data-Ink (Few & Tufte Standard)
1. **Dilarang Keras Chartjunk:** Dilarang menggunakan diagram lingkaran 3D (*3D pie charts*), speedometer analog (*circular gauges*), gradien berlebih, drop-shadow dekoratif pekat, dan garis kisi (*gridlines*) tebal.
2. **Mandat Bullet Graph (Stephen Few):** Metrik target tunggal wajib direpresentasikan dalam bentuk **Bullet Graph** horizontal kompak (batang pencapaian aktual, garis penanda target komparatif, dan 3 rentang kualitatif netral: buruk, memuaskan, baik).
3. **Mandat Sparklines:** Tren temporal mikro wajib menggunakan *sparklines* inline berbasis SVG/CSS native berukuran tinggi $\le 24\text{px}$ tanpa label sumbu numerik yang mengotori pandangan mata.
4. **Zero Heavy Charting Dependencies:** Dilarang memasang pustaka grafik monolitik berat (seperti Chart.js bundel penuh atau D3 lengkap) yang merusak Core Web Vitals. Gunakan SVG native berbobot ringan ($< 15\text{KB}$) atau canvas modern.

### [INV-DASH-03] Interaktivitas Klien In-Memory (< 0.1ms Latency)
1. Mengadopsi prinsip Ben Shneiderman: *“Overview first, zoom and filter, then details-on-demand.”*
2. Seluruh interaksi sekunder di sisi klien (pencarian teks pada tabel, sorting kolom, dan filter sub-kategori) WAJIB dieksekusi secara instan pada memori browser via Svelte 5 `$derived.by()` dengan latensi $< 0.1\text{ms}$.
3. Permintaan jaringan (*network re-fetch*) HANYA diperbolehkan ketika pengguna mengganti horizon waktu utama (*Time-Grain Selector*) atau unit institusi.

### [INV-DASH-04] Plafon Kueri Aman & Indeks Komposit B-Tree (Safe Ceilings)
1. Kueri transaksional analitik dibatasi plafon aman maksimal 1000 rekaman (`limit(1000)`). Dilarang keras melakukan `Model::all()` pada dataset temporal.
2. Setiap tabel data transaksi wajib memiliki indeks komposit B-Tree:
   - `['tanggal', 'status']`
   - `['entity_id', 'tanggal']` (contoh: `user_id`, `client_id`, atau `item_id`)
   - `['horizon', 'horizon_key', 'scope_key']` pada tabel rollup periodik.
3. Seluruh kalkulasi persentase dan agregasi agregat (SUM, COUNT, AVG) wajib dilakukan di level database engine atau tabel rollup, bukan di-looping pada memory PHP collection.

### [INV-DASH-05] Rute Agregasi Hibrida (Hybrid Rollup Routing - Kimball Standard)
Sistem membagi penarikan data ke dalam 2 strategi deterministik:
1. **Horizon Pendek ($\le 31$ Hari: Harian & Mingguan):** Dihitung secara *on-the-fly* dari tabel fakta transaksional atomik memanfaatkan indeks komposit tanggal.
2. **Horizon Panjang ($\ge 1$ Bulan: Bulanan, Tahunan, 5-Tahunan, dan Tahun Ajaran):** WAJIB dibaca langsung dari tabel *Pre-aggregated Periodic Snapshot Rollup* (`*_temporal_rollups`). Kueri level ini dijamin memiliki waktu respon sub-50ms.

### [INV-DASH-06] Determinisme Zona Waktu & Kalender Akademik (WIB & Dual Calendar)
1. Seluruh waktu dan tanggal dipasakkan pada zona waktu resmi institusi: **WIB (Asia/Jakarta / UTC+7)**.
2. Sistem wajib mendukung pemisahan tegas antara dua sistem kalender:
   - **Kalender Masehi (Gregorian):** 1 Januari s/d 31 Desember.
   - **Kalender Akademik / Tahun Ajaran:** 1 Juli tahun $N$ s/d 30 Juni tahun $N+1$, terbagi atas Semester Ganjil (1 Juli – 31 Des) dan Semester Genap (1 Jan – 30 Jun).
3. **Kohort 5-Tahunan:** Didefinisikan sebagai jendela 5 tahun kalender berurutan (misal: `2022-2026`) untuk monitoring Rencana Strategis (Renstra).

### [INV-DASH-07] Integritas Metrik & Pencegahan Falasi Statistik (Metric Integrity & Anti-Fallacy)
1. **Larangan *Averaging of Averages* (Falasi Matematis):** Dilarang keras merata-ratakan nilai persentase atau rasio antar unit secara langsung tanpa pembobotan volume sampel (*weighted average*). Setiap agregasi rasio wajib dihitung dari pembagian total atomik:
   $$\text{Rasio Gabungan} = \frac{\sum \text{Numerator}}{\sum \text{Denominator}} \times 100\% \quad (\text{BUKAN } \text{AVG}(\text{persentase}))$$
2. **Mandat Komparasi Kontekstual & Baseline:** Angka KPI tunggal dilarang disajikan tanpa konteks komparatif. Setiap kartu KPI utama WAJIB menyertakan minimal 1 dimensi perbandingan:
   - Target operasional institusi (misal garis target $95\%$).
   - Delta temporal komparatif ($+3.2\%$ MoM / YoY) dengan indikator arah netral/positif/negatif.
3. **Pencegahan *Vanity Metrics* (Douglas Hubbard Standard):** Setiap metrik yang ditampilkan pada kartu ringkasan eksekutif harus memiliki ambang batas operasional (*actionable threshold*) yang memicu tindakan perbaikan (misal: warna peringatan dan tombol filter tindak lanjut jika kehadiran $< 85\%$).
4. **Mitigasi *Simpson's Paradox*:** Ketika menampilkan agregasi makro institusi yang mengalami anomali atau deviasi ekstrem, antarmuka wajib menyediakan akses cepat untuk segmentasi mikro (per unit tugas/divisi, per cabang, atau per kategori entitas).

### [INV-DASH-08] Mandat Ekspor CSV Front-End pada Tabel Data Analitik (Client-Side CSV Export)
1. Seluruh tabel rincian data analitik dan pelaporan berkala WAJIB menyediakan kontrol ekspor berkas CSV langsung dari memori browser (*front-end in-memory zero round-trip*).
2. Berkas CSV wajib diproteksi menggunakan UTF-8 BOM (`\uFEFF`) agar terbaca sempurna di MS Excel tanpa karakter rusak, melakukan sanitasi karakter berbahaya (`=`, `+`, `-`, `@`) guna memitigasi CSV Formula Injection (CWE-1236), dan menyertakan timestamp pada nama file.

### [INV-DASH-09] Apple Spatial Materials, 60-30-10 OKLCH & 3-Tier Surface Elevation Hierarchy
1. **Arsitektur Elevasi Permukaan 3-Tier (Anti-Camouflage UI):**
   - **Tingkat 0 (L0 - Canvas Ground):** Kanvas latar dasar wajib memiliki pemisahan tonal tegas terhadap kartu (`bg-slate-100/70` di Daylight; `bg-[#070b12]` atau `bg-[#090D16]` di Dark Obsidian, bebas *OLED smear* dan *optical halation*).
   - **Tingkat 1 (L1 - Macro Surface):** Panel kontainer utama (kartu KPI, pipeline kanban, ringkasan kas, chart box) WAJIB menggunakan permukaan solid/semi-opak berbobot (`bg-white` Daylight; `dark:bg-slate-900/90` Dark Obsidian) dengan garis batas hairline 1px (`border border-slate-200/80 dark:border-slate-800`) dan bayangan mikro `shadow-2xs`.
   - **Tingkat 2 (L2 - Micro Surface):** Elemen anak di dalam kontainer L1 (kartu berkas pipeline, baris transaksi rincian, status box) WAJIB memiliki diferensiasi fisik nyata (`bg-slate-50/70 hover:bg-white` atau `bg-white hover:bg-slate-50` Daylight; `dark:bg-slate-950/70 hover:dark:bg-slate-800` atau `dark:bg-slate-900` Dark Obsidian dengan `border border-slate-200 dark:border-slate-800/80` dan `shadow-2xs`). DILARANG KERAS menggunakan warna/token yang identik dengan kontainer induknya.
2. **Larangan Keras Pseudo-Glassmorphism pada Kanvas Polos:**
   - DILARANG KERAS menggunakan kombinasi `backdrop-blur` dengan transparansi rendah (`bg-white/40`, `dark:bg-white/[0.01-0.05]`, `border-white/5`) di atas kanvas solid/flat. Efek blur kaca HANYA diizinkan pada *Header Melayang* atau *Floating Thumb Dock* yang melayang di atas konten bergulir. Di atas kanvas statis, gunakan permukaan semi-opak solid dengan kontras persepsi APCA $L_c \ge 75$ dan delta luminansi $\Delta L \ge 12\%$ guna mencegah efek bunglon visual di mana kartu dan background menyatu tanpa batas.
3. **Harmoni Warna Dasar 60-30-10 OKLCH:**
   - 60% Kanvas Netral: Daylight (`bg-slate-100/70`) atau Dark Obsidian (`bg-[#070b12]`/`bg-[#090D16]`).
   - 30% Struktur Permukaan Kartu: Solid berkarakter (`bg-white dark:bg-slate-900/90`) dengan garis batas presisi 1px.
   - 10% Aksen Fungsional: Warna dasar murni ($100\%$ saturasi) untuk status badge, Bullet Graph target, dan CTA primer.
4. **Selective Translucency & Mobile GPU Restraint:** Di layar ponsel ($< 768\text{px}$), efek blur berat (`backdrop-filter: blur(...)`) **HANYA** diizinkan pada *Sticky Header* dan *Floating Thumb Dock*. Kartu metrik KPI dan tabel analitik di HP wajib beralih ke semi-opak ringan tanpa blur (`bg-white dark:bg-slate-900/95`) demi menjaga performa rendering $60\text{ FPS}$ pada ponsel pintar menengah.
5. **Restrained Auditory UI (Tactile Web Audio):** Suara UI menggunakan sintesis prosedural native Web Audio API ($0\text{ KB}$ aset eksternal). Suara **HANYA** berbunyi pada peristiwa penting (*significant milestone events*: Ekspor CSV selesai, konfirmasi aksi, error RFC 7807), berstatus **default muted** di lingkungan kantor/institusi dengan tombol toggle persisten di `localStorage`, dan DILARANG bersuara saat hovering atau membaca grafik.

---

## 3. CHECKLIST VERIFIKASI SEBELUM REVIEW GATE

Setiap kali modul dasbor/analitik selesai dibangun, pastikan checklist berikut lulus 100%:
- [ ] Komponen Svelte 5 menangani Loading Skeleton, Empty State, Error State, dan Success State.
- [ ] Seluruh tabel data analitik menyediakan tombol dan fitur ekspor CSV front-end in-memory.
- [ ] Efek material kaca mematuhi kontras APCA $L_c \ge 75$ dan selective translucency di HP.
- [ ] Tombol Retry pada Error State menyertakan atribut eksplisit `type="button"`.
- [ ] Tidak ada dependensi pustaka grafik eksternal yang melebihi 20KB.
- [ ] Filter pencarian di memori merespon instan tanpa re-fetching server.
- [ ] Kueri backend memiliki batas `limit(1000)` dan seluruh filter waktu menggunakan kolom berindeks.
- [ ] Agregasi persentase dihitung dari total atomik ($\frac{\sum \text{Num}}{\sum \text{Den}}$), bebas dari falasi *averaging averages*.
- [ ] Setiap KPI utama menyertakan baseline perbandingan (Target atau Delta Temporal MoM/YoY).
- [ ] Format respon error backend mematuhi RFC 7807 (`application/problem+json`).

