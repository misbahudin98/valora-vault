# Distilasi Pembelajaran Siklus PowerShell & Invariant SDD
> **Sumber:** Rekaman Eksekusi Bersama, Evaluasi PowerShell Terminal, dan Invariant Engine Hyperion Vault v8.0.0.

---

## 1. LATAR BELAKANG & AKAR MASALAH (ROOT CAUSE ANALYSIS)

Dari ribuan interaksi dan siklus eksekusi di terminal PowerShell bersama engineer manusia, kami mengidentifikasi 5 penyakit utama pada generasi kode AI (*Model Degradation Traps*) dan solusi deterministiknya:

| Penyakit Model Generatif | Gejala Lapangan di PowerShell | Solusi & Invariant Wajib |
| :--- | :--- | :--- |
| **1. Bulk Sprawl Drift** | Model diminta membuat seluruh modul, lalu kehabisan konteks dan mulai memotong kode di tengah jalan. | **Single Feature Turn:** 1 Fitur untuk 1 Role per siklus kerja. Dilarang bulk generation. |
| **2. Lazy Placeholder Slop** | Muncul komentar `// TODO`, `/* rest of code */`, `// implement later`, atau fungsi kosong. | **NO_PLACEHOLDER Rule:** Kode wajib 100% utuh, tuntas, lulus pengujian, siap produksi. |
| **3. UI Amnesia (1-State Trap)** | Frontend hanya menampilkan state sukses; aplikasi crash saat loading lama atau saat data kosong. | **4 Visual States Mandate:** Wajib visual Loading, Empty, Error, dan Success. |
| **4. Ghost DB Corruption** | Mutasi multi-tabel putus di tengah saat koneksi lambat, meninggalkan database setengah terisi. | **Atomic Transaction:** Wajib `DB::transaction()` dan defensive exception catching. |
| **5. Unbounded Query Leaks** | `Model::all()` menarik 50,000 baris ke memori dan menghabiskan RAM server. | **Safe Query Ceilings:** Limit kueri 1000 item (`limit(1000)`) dan filter in-memory browser. |

---

## 2. INVARIANT WAJIB DALAM SETIAP PROMPT KODING (INVARIANT CHECKLIST)

Setiap kali Anda membuat prompt untuk agen koding, 7 Invariant ini WAJIB disuntikkan ke dalam bagian `<system_invariants>` atau aturan sistem:

### [INV-01] NO PLACEHOLDER (KODE 100% UTUH)
```text
DILARANG KERAS menggunakan komentar pemalas atau pemotongan kode:
'// TODO: implement later', '/* ...rest of code... */', '// add your logic here', atau 'console.log('clicked')'.
Seluruh kode yang dihasilkan WAJIB 100% utuh, tuntas, dapat dikompilasi, dan langsung berfungsi di lingkungan produksi.
```

### [INV-02] SINGLE FEATURE TURN (GRANULARITAS TUNTAS)
```text
Eksekusi kode HANYA 1 Fitur untuk 1 Role dalam satu kali giliran kerja.
Tuntaskan siklus hidup fungsional end-to-end:
Migration -> Eloquent Model -> FormRequest -> Policy (BOLA guard) -> Controller (Atomic) -> Pest/PHPUnit Test -> Svelte 5 Runes View.
```

### [INV-03] FULL-STATE UI MANDATE (4 VISUAL STATES)
```text
Setiap komponen antarmuka Svelte 5 Runes WAJIB menangani 4 State secara visual:
1. Loading State (Skeleton loader reaktif).
2. Empty State (Ilustrasi dan panduan jelas jika data kosong).
3. Error State (Pesan ramah pengguna berformat RFC 7807 dengan tombol aksi 'Coba Lagi').
4. Success / Populated State (Tampilan tabel/grid normal + inline validation).
```

### [INV-04] STRICT BACKEND SAFETY (PER 2.0 & RFC 7807)
```text
Setiap berkas PHP diawali 'declare(strict_types=1);'.
Setiap mutasi multi-tabel dibungkus 'DB::transaction(fn() => ...)'.
Setiap respon error mematuhi standar RFC 7807 tanpa membocorkan stack trace internal atau kredensial server.
```

### [INV-05] PURE JAVASCRIPT & SVELTE 5 RUNES
```text
100% Pure JavaScript (ES2024+). DILARANG berkas .ts atau sintaks TypeScript.
Gunakan Svelte 5 Runes SAJA ($state, $derived, $props, $effect). Dilarang sintaks legasi Svelte 4 ($:, export let, stores).
Pencarian data tabel wajib in-memory via $derived.by() (< 0.1ms).
```

### [INV-06] SESSION & DATABASE DEFENSE
```text
Wajib 'SESSION_DRIVER=file' pada disk NVMe. DILARANG 'SESSION_DRIVER=database'.
Database SQLite WAL mode (busy_timeout=5000, WAL, synchronous=NORMAL).
Seluruh kueri wajib memiliki batas maksimal limit(1000). Dilarang Model::all() tanpa limit.
```

### [INV-07] REVIEW GATE PROTOCOL (HUMAN-IN-THE-LOOP)
```text
Langkah A: Tuntaskan kode, pastikan PHPUnit test dan npm run build lulus 100%.
Langkah B: Sajikan ringkasan perubahan teknis dan ajukan Review Gate Form:
"Implementasi Fitur [Nama Fitur] untuk Role [Nama Role] selesai. Seluruh test lulus 100% dan UI menangani 4 visual state. Apakah ada perbaikan/revisi spesifik yang diinginkan sebelum melangkah ke fitur berikutnya?"
Langkah C: DILARANG melangkah ke fitur/role berikutnya tanpa izin eksplisit pengguna.
```
