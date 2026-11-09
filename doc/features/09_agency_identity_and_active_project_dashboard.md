# Fitur 09: Profil Legal Agency, Identitas Terpadu & Dashboard Mini Proyek Aktif
> **Domain:** Enterprise Agency Governance & Real-Time Operational Cockpit  
> **Target:** Hyperion Vault / Valora OS v6.31.0  
> **Standar:** Stephen Few (2013), Edward Tufte (2001), RFC 7807, Svelte 5 Runes, Laravel 13, OWASP Top 10

---

## 1. DESKRIPSI & LATAR BELAKANG BISNIS (JTBD)

Dalam ekosistem agensi digital modern, identitas korporasi, legalitas lembaga, rekening perbankan, dan kontak operasional harus dapat dikonfigurasi secara dinamis tanpa perlu mengubah baris kode program (*zero-hardcode*). Seluruh media output resmi—mulai dari Faktur Invoice, Kuitansi Pembayaran, template pesan konfirmasi WhatsApp, hingga dokumen Microsoft Word (.doc) dan cetak PDF—wajib mengambil data identitas agensi secara dinamis dari satu sumber kebenaran (*Single Source of Truth*).

Selain itu, kebutuhan tim manajemen dan pengembang untuk memantau status proyek yang **sedang aktif dikerjakan** membutuhkan instrumen pemantauan langsung pada bilah samping navigasi (*sidebar*). Fitur ini merombak ringkasan proyek statis menjadi **Dashboard Mini Khusus Proyek yang Sedang Dikerjakan**, menyajikan metrik granular rasio modul selesai, status tagihan riil, PIC tim, dan kontrol navigasi multi-proyek (*carousel switcher*).

---

## 2. ARSITEKTUR BASIS DATA & ENTITAS

### Tabel `agency_profiles`
Menyimpan profil legal, perizinan, kontak operasional, serta rekening perbankan agensi:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `INTEGER PRIMARY KEY` | ID Tunggal (Singleton Row / ID 1) |
| `agency_name` | `VARCHAR(255)` | Nama Merek Agensi (cth: Valora Digital Solusindo) |
| `legal_name` | `VARCHAR(255)` | Nama Badan Hukum Resmi (cth: PT VALORA DIGITAL SOLUSINDO) |
| `nib` | `VARCHAR(100)` | Nomor Induk Berusaha / Izin Usaha |
| `email` | `VARCHAR(255)` | Email Resmi Agensi |
| `hotline_wa` | `VARCHAR(50)` | Nomor Hotline WhatsApp / Kontak Helpdesk |
| `operational_address`| `TEXT` | Alamat Fisik Kantor Operasional |
| `github_repo_url` | `VARCHAR(255)` | Repositori GitHub Utama Agensi |
| `logo_url` | `VARCHAR(500)` | URL / Data URI Logo Agensi |
| `bank_name` | `VARCHAR(100)` | Nama Bank Utama (cth: Bank Mandiri) |
| `bank_account_no` | `VARCHAR(100)` | Nomor Rekening Utama |
| `bank_account_name` | `VARCHAR(255)` | Atas Nama Rekening Utama |
| `secondary_bank_name` | `VARCHAR(100)` | Nama Bank Sekunder (Opsional, cth: Bank BCA) |
| `secondary_bank_account_no` | `VARCHAR(100)` | Nomor Rekening Sekunder |
| `secondary_bank_account_name` | `VARCHAR(255)` | Atas Nama Rekening Sekunder |
| `invoice_terms` | `TEXT` | Syarat & Ketentuan Standar Invoice |

---

## 3. ARSITEKTUR BACKEND & RESTFUL API

### Controller & Policy
- **Controller:** `App\Http\Controllers\Api\V1\Admin\AgencyProfileController`
- **Form Request:** `App\Http\Requests\Admin\UpdateAgencyProfileRequest`
- **Policy:** `App\Policies\AgencyProfilePolicy` (Otorisasi: Admin atau izin `system.manage`)

### Peta Endpoint RESTful:
| Method | Endpoint | Hak Akses | Deskripsi |
|---|---|---|---|
| `GET` | `/api/v1/admin/agency-profile` | `auth:sanctum` | Ambil profil agensi aktif (fallback default jika belum diset) |
| `PUT` | `/api/v1/admin/agency-profile` | `auth:sanctum`, `role:admin` | Perbarui identitas, alamat, kontak, dan rekening bank |

---

## 4. INTEGRASI MEDIA & OUTPUT DINAMIS

Profil agensi tersinkronisasi secara otomatis pada seluruh media output:
1. **Faktur Invoice Preview & Cetak:** Kop surat dinamis, rincian rekening pembayaran, syarat & ketentuan, serta hotline WhatsApp resmi.
2. **Kuitansi Pembayaran:** Border formal, watermark resmi, tanda tangan otoritas keuangan, stempel stempel agensi.
3. **Pesan WhatsApp:** Teks terformat (*markdown bold/list*) mencakup nomor invoice/kuitansi, rincian nominal, rekening transfer, dan hotline konfirmasi.
4. **Ekspor Microsoft Word (.doc):** Penanaman logo PNG base64 fisik resolusi tinggi (`wdInlineShapePicture`), layout tabel kop surat 2 kolom, dan stempel digital.

---

## 5. DASHBOARD MINI PROYEK AKTIF (SIDEBAR WIDGET)

Tersemat pada bagian bawah [`AppSidebar.svelte`](file:///D:/laragon/www/valora-vault/frontend/src/lib/components/layout/AppSidebar.svelte):
1. **Filter Khusus Proyek Aktif:** Mengambil data proyek dengan `status: 'Aktif'` via `/api/v1/admin/clients?status=Aktif&limit=50`.
2. **Carousel Switcher:** Kontrol navigasi `< 1/N >` untuk beralih antar proyek aktif secara instan.
3. **Few/Tufte Bullet Progress Bar:** Rasio penyelesaian modul (`done/total Modul Selesai`) dengan progress bar gradien emerald-to-cyan.
4. **Grid Operasional:** Menampilkan *Lead PIC Tim* dan kalkulasi saldo tagihan (`Lunas`, `Sisa Rp ...`, atau `Belum DP`).
5. **Micro Action Bar:** Akses cepat ke halaman monitoring progres (`/admin/progres`), rincian scope (`/admin/clients`), dan repositori GitHub proyek.
6. **4 Visual States (RULE-02):** Loading (skeleton shimmer), Empty ("Tidak ada proyek aktif" + link buat proyek), Error (pesan + tombol coba lagi), dan Success.
