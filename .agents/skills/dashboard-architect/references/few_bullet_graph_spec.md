# Spesifikasi Geometri & Desain Bullet Graph (Stephen Few Standard)
> **Referensi:** Stephen Few, *Bullet Graph Design Specification* (Perceptual Edge, 2005) & *Information Dashboard Design* (2013).

---

## 1. ANATOMI BULLET GRAPH

Bullet Graph dirancang sebagai pengganti meteran analog (*gauge/speedometer*) yang boros ruang pada dasbor. Bullet Graph mengemas 5 dimensi data dalam satu batang horizontal padat:

```
0%          Poor (60%)     Satisfactory (85%)   Good (100%)
┌──────────────────────┬────────────────────────┬──────────────┐
│                      │ ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓    │              │
│                      │ ▓▓ Actual Bar (78%) ▓▓ │  │           │
│                      │ ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓    │  │ Target    │
└──────────────────────┴────────────────────────┴──┼───────────┘
                                                   95%
```

1. **Comparative Measure (Target Line):** Garis vertikal hitam pekat yang melintang tegak lurus terhadap batang performa untuk menunjukkan target institusi (misal: $95\%$).
2. **Featured Measure (Actual Performance Bar):** Batang horizontal utama yang merepresentasikan capaian nyata (misal: $78\%$).
3. **Qualitative Ranges (Interval Kualitatif):** Tiga blok gradasi netral di latar belakang yang mendefinisikan status:
   - *Poor (Buruk):* $0\% - 60\%$
   - *Satisfactory (Cukup):* $60\% - 85\%$
   - *Good (Baik):* $85\% - 100\%$

---

## 2. KONTRAK WARNA & AKSESIBILITAS WCAG 2.2 AAA

* **Hindari 'Lampu Lalu Lintas' Menyala:** Jangan gunakan latar belakang merah-kuning-hijau terang mencolok yang saling bertabrakan (*visual shouting*). Gunakan palet monokromatik netral untuk interval latar belakang, dan warna semantik lembut untuk batang performa.
* **Token Tailwind CSS v4:**
  - Latar Buruk: `bg-slate-200 dark:bg-slate-800`
  - Latar Cukup: `bg-slate-150 dark:bg-slate-850`
  - Latar Baik: `bg-slate-100 dark:bg-slate-900`
  - Batang Aktual: `bg-emerald-600 dark:bg-emerald-500` (atau `bg-rose-600` jika di bawah ambang batas kritis).
  - Garis Target: `bg-slate-950 dark:bg-white`

---

## 3. IMPLEMENTASI SVG MURNI SVELTE 5 (ZERO DEPENDENCY)

Komponen Bullet Graph kompak dapat dirender langsung via SVG native tanpa pustaka tambahan:

```svelte
<script>
    let { 
        actual = 88, 
        target = 95, 
        ranges = [60, 85, 100], 
        height = 20, 
        label = '' 
    } = $props();

    // Normalisasi skala 0 - 100%
    let actualPct = $derived(Math.min(Math.max(actual, 0), 100));
    let targetPct = $derived(Math.min(Math.max(target, 0), 100));
</script>

<div class="w-full space-y-1">
    {#if label}
        <div class="flex justify-between text-xs font-medium">
            <span class="text-slate-600 dark:text-slate-400">{label}</span>
            <span class="font-bold text-slate-900 dark:text-white font-mono">{actual}%</span>
        </div>
    {/if}

    <svg class="w-full h-5 rounded-xs overflow-hidden" viewBox="0 0 100 {height}" preserveAspectRatio="none">
        <!-- 1. Qualitative Ranges (Background) -->
        <rect x="0" y="0" width="{ranges[0]}" height="{height}" class="fill-slate-200 dark:fill-slate-800" />
        <rect x="{ranges[0]}" y="0" width="{ranges[1] - ranges[0]}" height="{height}" class="fill-slate-150 dark:fill-slate-750" />
        <rect x="{ranges[1]}" y="0" width="{ranges[2] - ranges[1]}" height="{height}" class="fill-slate-100 dark:fill-slate-700" />

        <!-- 2. Actual Performance Bar (Tengah) -->
        <rect 
            x="0" 
            y="{height * 0.25}" 
            width="{actualPct}" 
            height="{height * 0.5}" 
            class="fill-emerald-600 dark:fill-emerald-400 transition-all duration-300" 
        />

        <!-- 3. Target Marker (Garis Penanda Vertikal) -->
        <line 
            x1="{targetPct}" 
            y1="{height * 0.1}" 
            x2="{targetPct}" 
            y2="{height * 0.9}" 
            class="stroke-slate-950 dark:stroke-white stroke-[2.5]" 
        />
    </svg>
</div>
```
