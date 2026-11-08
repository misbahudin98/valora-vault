---
name: dashboard-architect
description: >-
  Playbook komprehensif, template reaktif Svelte 5, dan query engine Laravel 13 untuk
  merancang serta membangun dasbor analitik multi-temporal interaktif (Harian, Mingguan,
  Bulanan, Tahunan, 5-Tahunan, dan Tahun Ajaran) berstandar internasional Stephen Few,
  Edward Tufte, dan Ralph Kimball. Dipicu oleh '@dashboard', '@analytics', '#kpi',
  atau permintaan perancangan modul analitik dan pelaporan.
---

# Dashboard Architect: Multi-Temporal Analytics Playbook
> **Standard:** Stephen Few (2013), Edward Tufte (2001), Ralph Kimball (2013), Ben Shneiderman (1996), Colin Ware (2020), CRISP-DM, DAMA-DMBOK, Douglas Hubbard (2014), Svelte 5 Runes, Laravel 13.

Keahlian (*skill*) ini dirancang khusus untuk memandu AI coding agent dan engineer dalam merancang, memodelkan, dan mengimplementasikan dasbor analitik interaktif multi-temporal berstandar enterprise yang 100% mematuhi [`.agents/rules/temporal_dashboard_invariants.md`](../../rules/temporal_dashboard_invariants.md).

---

## 1. STRUKTUR DIREKTORI & MODUL PENDUKUNG

Skill ini dilengkapi dengan referensi teoritis, template siap pakai, dan skrip pembantu otomatisasi:

```
.agents/skills/dashboard-architect/
├── SKILL.md                                 # Playbook utama & orchestrator (berkas ini)
├── references/
│   ├── data_analysis_best_practices.md      # Standar analisis: CRISP-DM, Gartner 4-Stages, Metric Tree, Anti-Fallacy
│   ├── few_bullet_graph_spec.md             # Spesifikasi geometri & token Bullet Graph (Few/Tufte)
│   └── kimball_temporal_modeling.md         # Cetak biru Star Schema, Dimensi Tanggal & Rollup
├── templates/
│   ├── MultiTemporalDashboard.svelte        # Template UI Svelte 5 Runes (Pure JS, 4 visual states)
│   └── TemporalAnalyticsService.php         # Template Service Agregator Laravel 13 (Sub-50ms)
└── scripts/
    └── generate-temporal-dimension.ps1      # Generator data dimensi kalender 10 tahun (Masehi & Akademik)
```

---

## 2. 6 HORIZON TEMPORAL STANDARD & ROUTING ENGINE

Setiap dasbor yang dibangun wajib memetakan kebutuhan pelaporan ke dalam **6 Horizon Waktu**:

| Horizon | Granularitas Data | Sumber Data Backend | Latensi Target | Tipe Visualisasi Utama |
| :--- | :--- | :--- | :---: | :--- |
| **1. Harian** | Per Sesi / Waktu / Jam | On-the-fly (tabel fakta transaksional atomik) | $< 20\text{ ms}$ | Status Pill, Baris Sesi, Hourly Heatmap |
| **2. Mingguan** | 7 Hari Kalender Berjalan | On-the-fly rolling window | $< 25\text{ ms}$ | Sparkline 7-hari, Ringkasan Rata-rata |
| **3. Bulanan** | Rekap Tutup Buku 1-12 Bulan | Rollup (`*_temporal_rollups`) | $< 15\text{ ms}$ | Bullet Graph, Distribusi MoM Variance |
| **4. Tahunan** | Tahun Kalender (1 Jan - 31 Des) | Rollup (`*_temporal_rollups`) | $< 15\text{ ms}$ | Small Multiples, Tren YoY 3-Tahun |
| **5. 5-Tahunan** | Kohort Makro 5 Tahun Berurutan | Rollup (`*_temporal_rollups`) | $< 15\text{ ms}$ | Garis Tren Renstra, Cohort Retention |
| **6. Th. Ajaran** | 1 Juli $N$ s/d 30 Juni $N+1$ | Rollup + Dimensi Semester (1 & 2) | $< 20\text{ ms}$ | Breakdown Semester, Pekan Efektif KBM |

---

## 3. WORKFLOW EKSEKUSI: DARI STUDI KASUS MENJADI DASBOR HIDUP

Ketika merancang fitur dasbor baru, ikuti 4 tahap deterministik berikut:

### Tahap 1: Analisis Domain, Grain & Metric Tree (Kimball & CRISP-DM)
Rujuk pedoman mendalam di [`references/data_analysis_best_practices.md`](./references/data_analysis_best_practices.md):
1. Tentukan **North Star Metric & Metric Tree**: Petakan metrik utama ke input drivers (hindari *vanity metrics*).
2. Tentukan **Transaction Fact**: Kejadian atomik terkecil apa yang dicatat? (misal: presensi santri per sesi sholat).
3. Tentukan **Dimensi Pengelompokan**: Berdasarkan apa data dipilah? (Santri, Kamar/Kelas, Pegawai, Unit Sekolah).
4. Tentukan **Ukuran Metrik (Measures)**: Apa yang dihitung?
   - Kuantitas diskrit: `COUNT(*)`, `SUM(hadir)`, `SUM(alpa)`.
   - Rasio turunan atomik: $\frac{\sum\text{Hadir}}{\sum\text{Total}} \times 100\%$ (dilarang *averaging averages*).
   - Baseline target komparatif: target minimum institusi (misal: $95\%$).

### Tahap 2: Implementasi Skema & Dimensi Kalender (Backend)
1. Buat migrasi tabel transaksional atomik dengan indeks B-Tree:
   - Composite index `['tanggal', 'status']` untuk kueri cepat harian.
2. Buat tabel `dim_kalender_akademik` dan jalankan skrip `generate-temporal-dimension.ps1` untuk mengisi data dimensi tanggal.
3. Buat tabel `*_temporal_rollups` dengan indeks unik `['horizon', 'horizon_key', 'unit_tugas']`.

### Tahap 3: Implementasi Service Agregator (Laravel 13)
1. Salin dan sesuaikan template [`TemporalAnalyticsService.php`](./templates/TemporalAnalyticsService.php).
2. Pastikan `getAllowedUnitsForUser()` menghormati batasan RBAC institusi (Unit Scoping Policy).
3. Batasi kueri transaksional dengan `limit(1000)`.

### Tahap 4: Implementasi Komponen Reaktif (Svelte 5 Runes)
1. Salin dan sesuaikan template [`MultiTemporalDashboard.svelte`](./templates/MultiTemporalDashboard.svelte).
2. Terapkan 4 visual states:
   - `isLoading`: Skeleton shimmer bars.
   - `errorMessage`: Alert card RFC 7807 dengan tombol aksi retry bertipe `type="button"`.
   - `isEmpty`: Pesan edukatif dan penunjuk arah penyesuaian filter.
   - `isSuccess`: Kartu ringkasan KPI, Bullet Graph SVG, dan tabel granular.
3. Terapkan pencarian in-memory dengan `$derived.by()` agar latensi $< 0.1\text{ ms}$.

---

## 4. PEDOMAN VISUAL FEW & TUFTE (ANTI-CHARTJUNK)

1. **Gunakan Bullet Graph untuk Target vs Realisasi:**
   Jangan pernah menggunakan *speedometer* atau *gauge* analog. Bullet graph menghemat $80\%$ luas layar dan menampilkan data lebih banyak secara beradab.
2. **Gunakan Small Multiples untuk Perbandingan Antar Unit:**
   Buat kartu grafik mikro yang identik untuk perbandingan antar divisi / cabang / unit organisasi dengan sumbu $Y$ yang seragam ($0\% - 100\%$) agar pimpinan eksekutif dapat membandingkan tren seketika.
3. **Penyajian Angka Monospace:**
   Seluruh tabel data dan angka persentase wajib menggunakan font tabular/monospace (`font-mono`) agar digit angka lurus vertikal saat dibandingkan.

---

## 5. STANDAR APPLE SPATIAL MATERIALS, KURVA PEGAS & AUDITORY UI

1. **Arsitektur Elevasi Permukaan 3-Tier Wajib (Anti-Camouflage UI):**
   - **Tingkat 0 (L0 - Canvas Ground):** Kanvas latar dasar wajib memiliki pemisahan tonal tegas terhadap kartu (`bg-slate-100/70` di Daylight; `bg-[#070b12]` atau `bg-[#090D16]` di Dark Obsidian, bebas *OLED smear*).
   - **Tingkat 1 (L1 - Macro Surface):** Panel kontainer utama (kartu KPI, pipeline kanban, ringkasan kas, chart box) WAJIB menggunakan permukaan solid/semi-opak berbobot (`bg-white` Daylight; `dark:bg-slate-900/90` Dark Obsidian) dengan garis batas hairline 1px (`border border-slate-200/80 dark:border-slate-800`) dan bayangan mikro `shadow-2xs`.
   - **Tingkat 2 (L2 - Micro Surface):** Elemen anak di dalam kontainer L1 (kartu berkas pipeline, baris transaksi rincian, status box) WAJIB memiliki diferensiasi fisik nyata (`bg-slate-50/70 hover:bg-white` atau `bg-white hover:bg-slate-50` Daylight; `dark:bg-slate-950/70 hover:dark:bg-slate-800` atau `dark:bg-slate-900` Dark Obsidian dengan `border border-slate-200 dark:border-slate-800/80` dan `shadow-2xs`). DILARANG KERAS menggunakan warna/token yang identik dengan kontainer induknya.
2. **Larangan Keras Pseudo-Glassmorphism pada Kanvas Polos:**
   - DILARANG KERAS menggunakan kombinasi `backdrop-blur` dengan transparansi rendah (`bg-white/40`, `dark:bg-white/[0.01-0.05]`, `border-white/5`) di atas kanvas solid/flat. Efek blur kaca HANYA diizinkan pada *Header Melayang* atau *Floating Thumb Dock* yang melayang di atas konten bergulir. Di atas kanvas statis, gunakan permukaan semi-opak solid dengan kontras persepsi APCA $L_c \ge 75$ dan delta luminansi $\Delta L \ge 12\%$ guna mencegah efek bunglon visual di mana kartu dan background menyatu tanpa batas.
3. **Harmoni Warna 60-30-10 OKLCH:**
   - 60% Kanvas: Slate-100/70 (Daylight) / Deep Obsidian `#070b12` / `#090D16` (Dark Mode, bebas noda *OLED smearing*).
   - 30% Struktur Kartu: Solid berkarakter (`bg-white dark:bg-slate-900/90`) dengan garis batas presisi 1px.
   - 10% Aksen Fungsional: Warna dasar murni pada Bullet Graph, pill badge, dan CTA primer.
4. **Restraint Ponsel & 60 FPS Mobile GPU:**
   - Pada layar mobile ($< 768\text{px}$), nonaktifkan blur bertumpuk pada kartu metrik bergulir; gunakan semi-opak solid (`bg-white dark:bg-slate-900/95`) guna mencegah *GPU overdraw* dan *thermal throttling*.
5. **Damped Spring Animation & Auditory UI:**
   - Kurva pegas mikro: `cubic-bezier(0.34, 1.56, 0.64, 1)` dengan `active:scale-95`.
   - Suara UI sintetis native Web Audio API ($0\text{ KB}$ aset eksternal) HANYA berbunyi pada peristiwa penting (Ekspor CSV sukses, konfirmasi mutasi, error alert), berstatus **default muted** dengan tombol toggle persisten di `localStorage`.

---

## 6. QUALITY GATE VERIFIKASI SEBELUM SELESAI

- [ ] Lulus pengujian PHPUnit (`php artisan test`) untuk seluruh endpoint analitik (skenario 200, 422, dan 403).
- [ ] Lulus kompilasi frontend (`npm run build`) dengan 0 error dan 0 peringatan a11y.
- [ ] Mematuhi arsitektur elevasi 3-Tier (L0 Kanvas, L1 Kartu Makro, L2 Elemen Mikro) dan bebas dari pseudo-glassmorphism transparan rendah.
- [ ] Waktu respon API kueri analitik $\le 50\text{ ms}$.
- [ ] Seluruh agregasi persentase bebas dari *averaging averages* ($\frac{\sum \text{Num}}{\sum \text{Den}}$).
- [ ] Seluruh kartu KPI menyertakan baseline target atau tren pembanding (MoM / YoY).
- [ ] Seluruh tabel data analitik wajib menyediakan fitur ekspor CSV front-end in-memory (BOM UTF-8, mitigasi formula injection CWE-1236, zero round-trip).
- [ ] Kartu dasbor memenuhi batas kontras APCA $L_c \ge 75$ dan selective translucency di mobile.
- [ ] Seluruh tombol memiliki atribut `type="button"` atau `type="submit"`.
