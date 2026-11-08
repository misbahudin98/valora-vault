### INTAKE STUDI KASUS OPERASIONAL
- DOMAIN UTAMA: [Nama Domain Bisnis, misal: Logistik & Distribusi Inventaris Multi-Gudang]
- TARGET ROLE: [Nama Peran Kanonikal Utama, misal: admin_gudang, supervisor_logistik]
- LEVEL KRITIKALITAS: MISSION-CRITICAL (Tutup Buku & Audit Fisik)

#### 1. KONTEKS & PAIN POINTS KLIEN
[Tuliskan 1-2 paragraf narasi operasional riil klien, kendala manual, dan titik rawan kebocoran/human-error]

#### 2. PERUMUSAN JOBS-TO-BE-DONE (JTBD)
- JTBD-01: Sebagai [Peran], ketika [Pemicu Operasional], saya ingin [Tindakan Inti], sehingga [Hasil Kuantitatif/Outcome Bisnis].
- JTBD-02: Sebagai [Peran], ketika [Pemicu Operasional], saya ingin [Tindakan Inti], sehingga [Hasil Kuantitatif/Outcome Bisnis].
- JTBD-03: Sebagai [Peran], ketika [Pemicu Operasional], saya ingin [Tindakan Inti], sehingga [Hasil Kuantitatif/Outcome Bisnis].

#### 3. DEKOMPOSISI EVENTSTORMING & MODEL DATA
- COMMANDS: [Daftar aksi pemicu, misal: BuatSuratJalan, KonfirmasiPenerimaan, LaporkanSelisih]
- DOMAIN EVENTS: [Daftar kejadian masa lalu, misal: MutasiDiajukan, BarangDalamPerjalanan, MutasiSelesai]
- READ MODELS: [Daftar tabel/tampilan data yang dibutuhkan aktor untuk memutuskan]
- PROPOSED TABLES:
  * `[nama_tabel_1]`: id, [kolom_kunci_1], [kolom_kunci_2], status, timestamps
  * `[nama_tabel_2]`: id, [foreign_id], [kolom_kunci_1], [kolom_kunci_2], timestamps

#### 4. KONTRAK UI/UX & 4 VISUAL STATES (SVELTE 5)
- LOADING: [Deskripsi skeleton shimmer loader yang proporsional]
- EMPTY: [Deskripsi pesan zero-data, panduan filter waktu, dan CTA awal]
- ERROR: [Format penanganan RFC 7807 dengan tombol aksi retry type="button"]
- SUCCESS: [KPI Cards Few-Tufte, Filter In-Memory < 0.1ms, Tabel Data Monospace]
