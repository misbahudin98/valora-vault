# Spesifikasi Fitur: Valora Agency OS - Client & Project Intake Management
> **Status:** Production-Ready (Lulus 100% Unit/Feature Test & NPM Build)  
> **Target Role:** `admin` (Super Administrator / Lead Architect)  
> **Modul Terkait:** [`backend/app/Http/Controllers/Api/V1/Admin/ClientProjectController.php`](../../backend/app/Http/Controllers/Api/V1/Admin/ClientProjectController.php), [`frontend/src/routes/admin/clients/+page.svelte`](../../frontend/src/routes/admin/clients/+page.svelte)

---

## 1. RINGKASAN EKSEKUTIF
Modul Manajemen Klien & Intake Proyek adalah inti operasional dari **PT Valora Digital Solusindo** untuk mengelola seluruh siklus hidup proyek digital agensi, mulai dari registrasi klien baru, konfigurasi kontrak (Development vs Maintenance), dynamic roles builder, hingga kalkulasi otomatis estimasi valuasi berdasarkan paket komponen standar (Landing Page, Login, Register, App Core).

---

## 2. SKEMA BASIS DATA RELASIONAL 3NF
- **`clients`**: Master data entitas klien/instansi mitra (`id`, `name`, `contact_person`, `phone`, `email`, `address`, `industry`, `timestamps`).
- **`client_projects`**: Entitas proyek/aplikasi yang dipesan (`id`, `client_id`, `project_name`, `app_name`, `project_type`, `lead_pic`, `lead_pic_id`, `github_url`, `app_desc`, `use_landing`, `use_login`, `use_register`, `landing_price`, `login_price`, `register_price`, `landing_capabilities`, `login_capabilities`, `register_capabilities`, `status`, `grand_total`, `paid_amount`, `timestamps`).
- **`client_project_workers`**: Tabel pivot penugasan staf engineer/pekerja (`id`, `project_id`, `user_id`, `role_in_project`, `timestamps`).
- **`project_roles`**: Konfigurasi peran dinamis per proyek (`id`, `project_id`, `role_name`, `sort_order`, `timestamps`).
- **`project_menus`**: Modul/fitur per role dengan penugasan PIC fleksibel dan 12 checklist kapabilitas teknis (`id`, `project_role_id`, `menu_name`, `menu_desc`, `pic`, `pic_user_id`, `price`, `is_done`, `completed_date`, `has_create`, `has_read`, `has_update`, `has_delete`, `has_grafik`, `has_box_dashboard`, `has_import`, `has_export`, `has_filter`, `has_print`, `has_notification`, `has_custom_logic`, `sort_order`, `timestamps`).
- **`project_database_tables`**: Skema tabel basis data proyek (@Rp 100.000) (`id`, `project_id`, `table_name`, `price`, `is_done`, `timestamps`).

---

## 3. PETA ENDPOINT RESTFUL API
| Method | Endpoint | Proteksi Middleware | Deskripsi |
|---|---|---|---|
| `GET` | `/api/v1/admin/clients` | `auth:sanctum`, `role:admin`, `permission:projects.view` | Ambil daftar proyek & klien (pencarian debounced, filter tipe, limit 1000) |
| `GET` | `/api/v1/admin/clients/export` | `auth:sanctum`, `role:admin`, `permission:projects.view` | Ekspor seluruh proyek ke CSV (UTF-8 BOM `\xEF\xBB\xBF` & sanitasi CWE-1236) |
| `GET` | `/api/v1/admin/clients/{id}` | `auth:sanctum`, `role:admin`, `permission:projects.view` | Ambil detail 1 proyek beserta klien, roles, menus (10 kapabilitas), dan tabel DB |
| `POST` | `/api/v1/admin/clients` | `auth:sanctum`, `role:admin`, `permission:projects.create` | Daftarkan proyek baru (atomic transaction Client + Project + Roles) |
| `PUT` | `/api/v1/admin/clients/{id}` | `auth:sanctum`, `role:admin`, `permission:projects.edit` | Perbarui data proyek, klien, status, dan role dinamis |
| `DELETE` | `/api/v1/admin/clients/{id}` | `auth:sanctum`, `role:admin`, `permission:projects.delete` | Hapus proyek (cascade on delete roles dan auto-cleanup orphaned client) |
| `POST` | `/api/v1/admin/projects/{id}/roles` | `auth:sanctum`, `role:admin`, `permission:projects.edit` | Tambah role login baru ke proyek |
| `DELETE` | `/api/v1/admin/roles/{id}` | `auth:sanctum`, `role:admin`, `permission:projects.edit` | Hapus role login dan seluruh menu di dalamnya |
| `POST` | `/api/v1/admin/roles/{id}/menus` | `auth:sanctum`, `role:admin`, `permission:projects.edit` | Tambah menu fitur baru dengan PIC fleksibel dan 10 checklist kapabilitas |
| `PUT` | `/api/v1/admin/menus/{id}` | `auth:sanctum`, `role:admin`, `permission:projects.edit` | Perbarui menu fitur (PIC, 10 kapabilitas, tarif kustom, deskripsi) |
| `POST` | `/api/v1/admin/menus/{id}/toggle-done` | `auth:sanctum`, `role:admin`, `permission:projects.edit` | Toggle status selesai pengerjaan menu dengan tanggal dan teknisi |
| `DELETE` | `/api/v1/admin/menus/{id}` | `auth:sanctum`, `role:admin`, `permission:projects.edit` | Hapus menu fitur dan update otomatis nilai valuasi grand total |
| `POST` | `/api/v1/admin/projects/{id}/database-tables` | `auth:sanctum`, `role:admin`, `permission:projects.edit` | Tambah tabel database ke skema proyek (@Rp 100.000) |
| `POST` | `/api/v1/admin/database-tables/{id}/toggle-done` | `auth:sanctum`, `role:admin`, `permission:projects.edit` | Toggle status penyelesaian pembuatan tabel basis data |
| `DELETE` | `/api/v1/admin/database-tables/{id}` | `auth:sanctum`, `role:admin`, `permission:projects.edit` | Hapus tabel database dan update otomatis nilai valuasi grand total |

---

## 4. KONTRAK UI / UX SVELTE 5
- **4 Visual States**: Loading skeleton, Empty state dengan CTA "+ Tambah Client / Proyek", Error RFC 7807 dengan tombol Coba Lagi, dan Success state.
- **Dual-mode View**: Tabel data desktop dan susunan Card View otomatis pada layar ponsel (≤ 768px).
- **In-Memory Search**: Filter instan browser `< 0.1ms` via `$derived.by()`.
- **Client-Side CSV Export**: Unduh CSV instan tanpa round-trip backend berlebih.
- **Pro-Grade Compact**: Hairline 1px border (`border-slate-200/80 dark:border-slate-800`), bayangan mikro `shadow-2xs`, alokasi warna 60-30-10 OKLCH.
- **Modal Detail & Setting Terpadu**: Fitur "Detail & Setting" dioperasikan langsung melalui komponen modal komprehensif [`frontend/src/lib/components/clients/ClientDetailSettingModal.svelte`](../../frontend/src/lib/components/clients/ClientDetailSettingModal.svelte) tanpa meninggalkan halaman direktori klien. Modal mencakup profil instansi, tombol edit data proyek lengkap, kartu valuasi finansial, kontrol interaktif toggle fitur standar & penyesuaian tarif khusus (Landing, Login, Register), selector tabs peran dinamis, daftar menu dengan penugasan PIC fleksibel dan checklist 10 kapabilitas teknis, serta skema tabel basis data proyek (@Rp 100.000). Rute deep-link `/admin/clients/[id]` secara otomatis dialihkan ke modal direktori.
