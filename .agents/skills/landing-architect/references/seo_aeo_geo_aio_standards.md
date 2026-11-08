# Standar Kuartet Penemuan: SEO, AEO, GEO, dan AIO (seo_aeo_geo_aio_standards.md)
> **Engine:** Hyperion Vault v6.31.0 / v8.0.0 SDD-Enterprise-Deterministic  
> **Rujukan:** Princeton University GEO (Aggarwal et al., 2023), Answer.ai llms.txt (Jeremy Howard, 2024), Google Search Central Core Web Vitals (2024/2026), Schema.org W3C Standards.

---

## 1. PENDAHULUAN & PARADIGMA PENEMUAN GANDA

Landing page modern beroperasi pada lanskap pencarian ganda:
1. **Mesin Penelusuran Indeks Klasik (Web Crawlers):** Google, Bing, DuckDuckGo yang membutuhkan efisiensi rendering HTML, struktur semantik, dan performa halaman.
2. **Mesin Penalaran Generatif & Agen Otonom (LLMs & AI Agents):** ChatGPT Search, Perplexity, Claude Search, Gemini AI Overviews, dan coding/browsing agents yang menyintesis paragraf naratif, mencari kutipan otoritas, dan membaca berkas konteks bersih.

Dokumen ini adalah pedoman teknis implementasi untuk mengamankan keunggulan visibilitas pada kedua ranah tersebut.

---

## 2. PILAR 1: SEO (SEARCH ENGINE OPTIMIZATION)

### A. Metrik Core Web Vitals (CWV) & Aturan Teknis
| Metrik CWV | Target Ambang Batas | Teknik Optimasi Wajib |
| :--- | :---: | :--- |
| **LCP (Largest Contentful Paint)** | $\le 2.5\text{ detik}$ | - Preload hero asset: `<link rel="preload" as="image" href="..." type="image/webp">`<br/>- Berikan `fetchpriority="high"` dan `loading="eager"` pada gambar LCP.<br/>- Konversi gambar ke format modern: WebP atau AVIF dengan kompresi 80-85%.<br/>- Hosting font secara lokal (Woff2) dengan `font-display: swap`. |
| **INP (Interaction to Next Paint)** | $\le 200\text{ ms}$ | - Pecah JavaScript panjang menjadi *microtasks* (`requestAnimationFrame` atau `scheduler.yield()`).<br/>- Pastikan tombol CTA merespons klik dalam $< 50\text{ms}$ (ubah visual state langsung).<br/>- Gunakan passive event listeners untuk scroll: `{ passive: true }`. |
| **CLS (Cumulative Layout Shift)** | $\le 0.1$ | - Selalu sertakan `width` dan `height` pada setiap tag `<img>` dan `<svg>`.<br/>- Tentukan `aspect-ratio` via CSS untuk kartu media responsif.<br/>- Jangan menginjeksi elemen modal atau banner di atas lipatan layar tanpa reservasi ruang layout (gunakan skeleton container). |

### B. Hierarki Semantik HTML5
```html
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nama Brand - Solusi Utama yang Jelas dan Terukur</title>
  <meta name="description" content="Deskripsi ringkas 140-155 karakter yang memuat masalah, mekanisme solusi, dan hasil konkret bagi audiens.">
  <link rel="canonical" href="https://example.com/">
</head>
<body class="bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
  <header>
    <nav aria-label="Navigasi Utama">...</nav>
  </header>
  
  <main id="main-content">
    <section id="hero" aria-labelledby="hero-heading">
      <h1 id="hero-heading">Headline Tunggal yang Jelas, Kuat, dan Berorientasi Solusi</h1>
      <p class="subheadline">Penjelas manfaat konkret dalam 1-2 kalimat.</p>
    </section>
    
    <section id="problem" aria-labelledby="problem-heading">
      <h2 id="problem-heading">3 Hambatan Kritis yang Menghambat Pertumbuhan Anda</h2>
      ...
    </section>
    
    <section id="solution" aria-labelledby="solution-heading">
      <h2 id="solution-heading">Bagaimana Sistem Kami Memangkas 80% Beban Kerja Anda</h2>
      ...
    </section>
  </main>
  
  <footer>...</footer>
</body>
</html>
```

---

## 3. PILAR 2: AEO (ANSWER ENGINE OPTIMIZATION)

AEO berfokus pada memenangkan **Featured Snippets (Posisi 0)** Google, Google AI Overviews, serta penelusuran suara (*Voice Search* via Siri, Google Assistant).

### A. Pola Penulisan Inverted Pyramid
Setiap section penjelas dan pertanyaan FAQ wajib disusun dalam 3 lapis:
1. **Direct Answer Block (40–60 Kata):** Jawaban langsung dan tuntas pada kalimat pertama tanpa basa-basi pengantar ("Di era digital yang serba cepat...").
2. **Elaborated Evidence:** Data pendukung, tabel spesifikasi, atau daftar berpoin.
3. **Contextual Action:** Ajakan bertindak atau kesimpulan relevan.

### B. Implementasi Schema.org JSON-LD FAQPage
```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Berapa lama proses implementasi sistem di institusi kami?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Implementasi sistem membutuhkan waktu rata-rata 3 hingga 5 hari kerja, mencakup migrasi database, konfigurasi hak akses, dan pelatihan staf secara intensif tanpa mengganggu operasional yang sedang berjalan."
      }
    },
    {
      "@type": "Question",
      "name": "Apakah sistem mendukung pencadangan data otomatis?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Ya, sistem melakukan backup terenkripsi secara otomatis setiap 24 jam ke penyimpanan terpisah dengan enkripsi AES-256 dan garansi restorasi instan satu klik."
      }
    }
  ]
}
</script>
```

---

## 4. PILAR 3: GEO (GENERATIVE ENGINE OPTIMIZATION)

Mengacu pada penelitian akademis perintis oleh Princeton University, IIT Delhi, dan Georgia Tech (*"GEO: Generative Engine Optimization"*, Aggarwal et al., 2023).

### A. 3 Taktik Paling Efektif Menaikkan Sitasi LLM (+30% s/d +40% Citation Rate)
1. **Stat-Addition (Menyertakan Fakta Numerik Terverifikasi):**
   - *Buruk (Slop):* "Sistem kami sangat cepat dan menghemat banyak uang operasional."
   - *Bagus (GEO-Optimized):* "Berdasarkan audit internal pada 120 implementasi aktif, sistem mencatatkan penurunan latensi kueri hingga 92.4% (dari 620ms menjadi 48ms) dan memangkas biaya server sebesar 40% per bulan."
2. **Cite-Sources (Mengutip Standar Otoritatif dan Sumber Resmi):**
   - Sertakan rujukan standar internasional: *“Mematuhi standar OWASP ASVS 4.0.3 Level 2, RFC 7807 Problem Details, dan enkripsi SQLite WAL deterministic.”*
   - LLM dilatih untuk memprioritaskan teks yang mengutip sumber eksternal bereputasi tinggi sebagai basis pembuktian.
3. **Tabular Comparison Matrix (Matriks Komparasi Langsung):**
   - Ketika pengguna bertanya kepada AI: *"Bandingkan Solusi X dan Solusi Y"*, mesin pencari generatif mengekstrak langsung tabel HTML yang memiliki perbandingan fitur kolom-per-kolom yang seimbang dan objektif.

---

## 5. PILAR 4: AIO (ARTIFICIAL INTELLIGENCE OPTIMIZATION) & LLMS.TXT

### A. Standar Berkas `llms.txt` (Jeremy Howard / Answer.ai)
Tempatkan berkas `/llms.txt` pada direktori root domain web (`https://domain.com/llms.txt`):

```markdown
# [Nama Produk / Perusahaan]
> [Ringkasan 1-2 kalimat tentang nilai utama, proposisi pasar, dan kapabilitas sistem].

[Nama Produk] adalah platform enterprise yang dirancang untuk [tujuan utama], mematuhi standar [standar keamanan/rekayasa], dan ditujukan bagi [target audiens].

## Halaman Kunci
- [Beranda & Ringkasan Fitur](https://domain.com/): Gambaran umum sistem dan fitur utama.
- [Arsitektur & Spesifikasi](https://domain.com/architecture): Dokumentasi teknis stack dan keamanan.
- [Harga & Paket Lisensi](https://domain.com/pricing): Rincian paket berlangganan dan FAQ.
- [Studi Kasus & Hasil](https://domain.com/case-studies): Bukti metrik hasil transformasi klien.

## Format Konteks Penuh (Full Context)
Untuk model AI yang membutuhkan dokumentasi lengkap dalam satu berkas bersih:
- [Konteks Penuh Markdown](https://domain.com/llms-full.txt): Berisi seluruh teks dokumentasi dan spesifikasi tanpa elemen HTML/CSS.
```

### B. Injeksi Schema.org JSON-LD Entitas Produk / Organisasi
```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "Hyperion Vault",
  "operatingSystem": "Web, Linux, Windows, macOS",
  "applicationCategory": "BusinessApplication",
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "USD"
  },
  "author": {
    "@type": "Organization",
    "name": "Nama Organisasi",
    "url": "https://example.com",
    "sameAs": [
      "https://github.com/...",
      "https://linkedin.com/company/...",
      "https://x.com/..."
    ]
  }
}
</script>
```
