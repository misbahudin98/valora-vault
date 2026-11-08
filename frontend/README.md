# Hyperion Vault - Frontend Architecture (Svelte 5 Runes + Tailwind CSS v4)

Frontend Single Page Application (SPA) ultra-cepat berbasis **Svelte 5 Runes (Pure JavaScript)** dan **Tailwind CSS v4** dengan kompilasi statis `@sveltejs/adapter-static` tanpa ketergantungan runtime Node.js di server produksi.

---

## ⚡ Prinsip & Standar Rekayasa Frontend
1. **Pure Client-Side Rendering (CSR) & SPA**:
   - Dideklarasikan pada `src/routes/+layout.js`:
     ```javascript
     export const ssr = false;
     export const csr = true;
     export const prerender = false;
     export const trailingSlash = 'never';
     ```
   - Dilarang submit form HTML tradisional (`action="..."`) dan reload halaman penuh (`window.location.reload()`).
   - Seluruh mutasi berjalan asinkron via `api.request(...)` dan memperbarui state lokal seketika (*in-memory optimistic update*).
2. **Svelte 5 Runes (Pure JavaScript)**:
   - Dilarang menggunakan TypeScript. Wajib ES2024 JavaScript murni dengan runes `$state`, `$derived`, `$props`, dan `$effect`.
   - Menggunakan `$derived.by()` untuk komputasi filter data agar terbebas dari siklus *cascading effect microtask*.
3. **Pencarian Data Instan di Memori Browser (< 0.1ms)**:
   - Komponen `DataTable.svelte` dan `MultiItemPicker.svelte` menyaring data langsung di memori browser.
   - Zero network trips pada setiap ketikan tombol pencarian, menghemat bandwidth dan koneksi database.
4. **Zero Silent Failure & Aksesibilitas (WCAG AAA)**:
   - Seluruh event handler tombol terikat reaktif (`onclick`).
   - Tombol mutasi wajib menyediakan indikator loading dan state `disabled` saat proses berlangsung untuk mencegah *double submit*.
   - Notifikasi mengambang (`Toast.svelte`) memberikan umpan balik langsung atas setiap aksi pengguna.
   - Tombol dialog dan form menyematkan `type="button"` eksplisit dan `aria-label` ramah pembaca layar (0 warning a11y).
5. **Controlled Modal Lifecycle**:
   - Komponen `Modal.svelte` wajib default `show = true` untuk menjamin render seketika dalam blok `{#if showCondition}`.
   - Formulir bertahap wajib meletakkan tab navigasi di dalam snippet `{#snippet headerTabs()}` pada header sticky.
6. **Ergonomi Ramah Pengguna Senior & Non-Teknis (Baby Boomer UX)**:
   - Input minimal 44px di mobile (`text-[16px]` anti iOS auto-zoom), border 2px solid kontras tinggi, badge wajib/opsional eksplisit, callout panduan berikon emoji, dan tombol aksi bawah meyakinkan dengan bidang sentuh $\ge 44$px.
7. **Core Web Vitals Target 2026 & SEO**:
   - INP < 100ms, LCP < 1.2s, CLS < 0.05.
   - Prekompresi statis Brotli (`.br`) & Gzip (`.gz`), Pra-koneksi Google Fonts, Schema.org JSON-LD, Open Graph, dan Twitter Cards di `+page.svelte`.

---

## 🚀 Perintah Operasional
```bash
# Menjalankan server dev lokal
npm run dev

# Kompilasi build statis untuk produksi (Hostinger / Nginx)
npm run build

# Menjalankan pengujian E2E Playwright UAT
npm run test:e2e
```
Hasil `npm run build` wajib menghasilkan exit code 0 tanpa error dan tanpa peringatan aksesibilitas (0 a11y warnings).
