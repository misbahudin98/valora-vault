<script>
  import { goto } from '$app/navigation';
  import { authState } from '$lib/state/auth.svelte.js';
  import { themeState } from '$lib/state/theme.svelte.js';
  import { tactileAudio } from '$lib/utils/audioFeedback.js';
  import ArchitectMascot from '$lib/components/features/mascot/ArchitectMascot.svelte';

  let isAudioMuted = $state(false);

  $effect(() => {
    if (typeof window !== 'undefined') {
      isAudioMuted = tactileAudio.muted;
    }
  });

  function handleToggleTheme() {
    tactileAudio.playToggle(!themeState.isDark);
    themeState.toggle();
  }

  function handleToggleAudio() {
    isAudioMuted = tactileAudio.toggleMute();
  }

  function handleCtaClick() {
    tactileAudio.playSuccess();
    if (authState.isAuthenticated) {
      goto('/dashboard');
    } else {
      goto('/login');
    }
  }

  function handleNavClick() {
    tactileAudio.playTap();
  }
</script>

<svelte:head>
  <!-- Google SEO Standards 2026 -->
  <title>Valora Vault - Platform Arsitektur Web Skalabilitas Tinggi | Laravel 13 Sanctum & Svelte 5</title>
  <meta name="description" content="Platform enterprise fullstack dengan latensi P99 < 150ms, SLO 99.0%, multi-role RBAC, tamper-proof signed JWT cookie, dan optimasi konkurensi SQLite di Hostinger." />
  <meta name="keywords" content="Valora Vault, Enterprise Fullstack, Laravel 13 Sanctum, Svelte 5 Runes, High Performance Web, P99 Latency, Multi-Role RBAC, SQLite Concurrency, Hostinger Premium" />
  <meta name="author" content="Enterprise CTO & Engineering Team" />
  <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
  <link rel="canonical" href="http://valora-vault.test/" />

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website" />
  <meta property="og:url" content="http://valora-vault.test/" />
  <meta property="og:title" content="Valora Vault - Platform Arsitektur Web Skalabilitas Tinggi" />
  <meta property="og:description" content="Solusi arsitektur web modern anti-bottleneck dengan latensi P99 < 150ms, eliminasi MySQL connection exhaustion, dan keamanan multi-device." />
  <meta property="og:image" content="http://valora-vault.test/og-cover.png" />
  <meta property="og:locale" content="id_ID" />
  <meta property="og:site_name" content="Valora Vault" />

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="Valora Vault - High Concurrency Fullstack Architecture" />
  <meta name="twitter:description" content="Latensi P99 < 150ms, SLO 99.0%, Multi-Role RBAC, Svelte 5 Runes & Laravel 13 Sanctum." />
  <meta name="twitter:image" content="http://valora-vault.test/og-cover.png" />

  <!-- Structured Data JSON-LD (Schema.org Graph: WebApplication, WebSite, Organization, FAQPage for AEO) -->
  {@html `<script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "Organization",
        "@id": "http://valora-vault.test/#organization",
        "name": "Valora Vault Engineering",
        "url": "http://valora-vault.test/",
        "logo": "http://valora-vault.test/favicon.svg",
        "description": "Pusat Rekayasa Arsitektur Web Skalabilitas Tinggi Berbasis Svelte 5 dan Laravel 13 Sanctum."
      },
      {
        "@type": "WebSite",
        "@id": "http://valora-vault.test/#website",
        "url": "http://valora-vault.test/",
        "name": "Valora Vault",
        "publisher": { "@id": "http://valora-vault.test/#organization" }
      },
      {
        "@type": "WebApplication",
        "@id": "http://valora-vault.test/#webapp",
        "name": "Valora Vault Enterprise Starter Kit",
        "url": "http://valora-vault.test/",
        "description": "Platform enterprise fullstack performa tinggi berbasis Laravel 13 Sanctum dan Svelte 5 Runes dengan latensi P99 < 150ms.",
        "applicationCategory": "DeveloperApplication",
        "operatingSystem": "All",
        "offers": {
          "@type": "Offer",
          "price": "0",
          "priceCurrency": "USD"
        },
        "featureList": [
          "Latensi P99 di bawah 150ms",
          "SLO 99.0% Ketersediaan Tinggi",
          "Multiple Role RBAC dalam Satu Record",
          "Tamper-Proof Signed JWT Cookie HMAC-SHA256",
          "Pencabutan Sesi Multi-Perangkat Instan",
          "Zero-Hacking Laragon Nginx Auto-VHost"
        ]
      },
      {
        "@type": "FAQPage",
        "@id": "http://valora-vault.test/#faq",
        "mainEntity": [
          {
            "@type": "Question",
            "name": "Bagaimana Valora Vault mengatasi insiden MySQL Connection Exhaustion?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Valora Vault memisahkan raw session engine menggunakan SESSION_DRIVER=file di NVMe SSD sehingga beban koneksi database untuk sesi menjadi 0%, dan mengisolasi audit perangkat di tabel user_devices hanya saat login/logout."
            }
          },
          {
            "@type": "Question",
            "name": "Mengapa Valora Vault menggunakan Svelte 5 Runes dibandingkan React atau Vue?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Svelte 5 Runes menggunakan sistem reaktivitas berbasis sinyal mikro tanpa kompilasi Virtual DOM, menghasilkan ukuran bundle JavaScript di bawah 45 kB dan latensi render sub-milidetik."
            }
          },
          {
            "@type": "Question",
            "name": "Bagaimana sistem otentikasi hybrid Sanctum dan Signed JWT bekerja?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Server tetap memegang otoritas penuh via HttpOnly session Sanctum, sementara client Svelte 5 membaca status peran dan nama dari cookie auth_jwt bertanda tangan kriptografis HMAC-SHA256 APP_KEY anti-tampering."
            }
          }
        ]
      }
    ]
  }
  </script>`}
</svelte:head>

<div class="relative overflow-hidden bg-slate-50 text-slate-800 dark:bg-[#090d16] dark:text-slate-100 transition-colors duration-300 min-h-screen flex flex-col font-sans selection:bg-indigo-500 selection:text-white">
  
  <!-- Subtle Architectural Illumination & Ambient Glow -->
  <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_60%_at_50%_-10%,rgba(79,70,229,0.08),transparent)] dark:bg-[radial-gradient(ellipse_80%_60%_at_50%_-10%,rgba(79,70,229,0.18),transparent)] pointer-events-none -z-10"></div>
  <div class="absolute inset-0 bg-[linear-gradient(to_right,#64748b0a_1px,transparent_1px),linear-gradient(to_bottom,#64748b0a_1px,transparent_1px)] dark:bg-[linear-gradient(to_right,#1e293b14_1px,transparent_1px),linear-gradient(to_bottom,#1e293b14_1px,transparent_1px)] bg-[size:3.5rem_3.5rem] pointer-events-none -z-10"></div>

  <!-- ================= STICKY APPLE SPATIAL GLASS HEADER ================= -->
  <header class="sticky top-0 z-40 w-full backdrop-blur-xl backdrop-saturate-150 bg-white/80 dark:bg-[#090d16]/80 border-b border-slate-200/80 dark:border-white/10 shadow-2xs transition-all duration-200">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-14 sm:h-16 flex items-center justify-between gap-3">
      
      <!-- Brand Logo & Release Pill -->
      <a href="/" onclick={handleNavClick} class="flex items-center gap-2.5 group shrink-0">
        <div class="w-8 h-8 rounded-xl bg-indigo-600/10 dark:bg-indigo-500/20 border border-indigo-500/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-extrabold text-sm shadow-inner group-hover:scale-105 active:scale-95 transition-transform duration-150">
          HV
        </div>
        <div class="flex flex-col">
          <div class="flex items-center gap-1.5">
            <span class="font-bold text-slate-900 dark:text-white text-sm sm:text-base tracking-tight font-display">Valora Vault</span>
            <span class="text-[9px] px-1.5 py-0.5 rounded font-mono font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800/60 hidden sm:inline-block">v6.31.0</span>
          </div>
          <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium hidden sm:block">Enterprise Fullstack Engine</span>
        </div>
      </a>

      <!-- Desktop Nav Links -->
      <nav class="hidden md:flex items-center gap-1 bg-slate-100/80 dark:bg-slate-900/80 p-1 rounded-full border border-slate-200/80 dark:border-white/10 text-xs">
        <a href="#hero" onclick={handleNavClick} class="px-3 py-1 rounded-full text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-white hover:bg-white dark:hover:bg-slate-800 transition font-medium">Beranda</a>
        <a href="#metrics" onclick={handleNavClick} class="px-3 py-1 rounded-full text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-white hover:bg-white dark:hover:bg-slate-800 transition font-medium">Benchmark SLO</a>
        <a href="#pillars" onclick={handleNavClick} class="px-3 py-1 rounded-full text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-white hover:bg-white dark:hover:bg-slate-800 transition font-medium">Pilar Arsitektur</a>
        <a href="#mascot" onclick={handleNavClick} class="px-3 py-1 rounded-full text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-white hover:bg-white dark:hover:bg-slate-800 transition font-medium">Simulasi AI</a>
      </nav>

      <!-- Action Controls: Audio Toggle, Theme Toggle & Portal CTA -->
      <div class="flex items-center gap-2">
        <!-- Auditory UI Sound Toggle (Apple Tactile Web Audio) -->
        <button
          type="button"
          onclick={handleToggleAudio}
          class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl flex items-center justify-center border transition-all duration-150 active:scale-95 cursor-pointer {isAudioMuted ? 'bg-slate-100 dark:bg-slate-800/80 border-slate-300 dark:border-slate-700 text-slate-400' : 'bg-indigo-50 dark:bg-indigo-950/60 border-indigo-300/80 dark:border-indigo-500/40 text-indigo-600 dark:text-indigo-400 shadow-2xs'}"
          title={isAudioMuted ? 'Suara UI Senyap (Klik untuk menyalakan audio Apple feedback)' : 'Suara UI Aktif (Klik untuk membisukan)'}
          aria-label={isAudioMuted ? 'Aktifkan suara feedback' : 'Bisukan suara feedback'}
        >
          {#if isAudioMuted}
            <!-- Muted Icon -->
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
            </svg>
          {:else}
            <!-- Sound Active Icon with Waves -->
            <svg class="w-4 h-4 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
            </svg>
          {/if}
        </button>

        <!-- Theme Toggle Button (Daylight / Dark Obsidian) -->
        <button
          type="button"
          onclick={handleToggleTheme}
          class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 transition-all duration-150 active:scale-95 cursor-pointer hover:border-indigo-400 shadow-2xs"
          title="Alihkan tema visual (Terang / Gelap)"
          aria-label="Toggle tema visual"
        >
          {#if themeState.isDark}
            <!-- Sun Icon for Dark Mode -->
            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          {:else}
            <!-- Moon Icon for Daylight Mode -->
            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
          {/if}
        </button>

        <!-- Header CTA Button -->
        <button
          type="button"
          onclick={handleCtaClick}
          class="px-3.5 sm:px-4 py-1.5 sm:py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-sm shadow-indigo-600/30 border border-indigo-400/30 active:scale-95 transition-all duration-150 flex items-center gap-1.5 cursor-pointer"
        >
          <span>{authState.isAuthenticated ? 'Dashboard' : 'Masuk'}</span>
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
          </svg>
        </button>
      </div>

    </div>
  </header>

  <main class="flex-1">
  <!-- ================= HERO SECTION ================= -->
  <section id="hero" class="max-w-6xl mx-auto px-4 sm:px-6 pt-8 pb-14 sm:pt-16 sm:pb-20">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
      
      <!-- Hero Left: Typography & Value Proposition -->
      <div class="lg:col-span-7 space-y-5 text-center lg:text-left">
        <!-- Release Badge (Spatial Chamfer Pill) -->
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/80 dark:bg-slate-900/80 border border-indigo-500/30 text-indigo-700 dark:text-indigo-300 text-xs font-medium backdrop-blur-md shadow-2xs">
          <span class="flex h-2 w-2 relative">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-cyan-500"></span>
          </span>
          <span>Rilis Enterprise v6.31.0 • Zero Bottleneck Engine</span>
        </div>

        <!-- Main H1 SEO Title -->
        <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-slate-900 dark:text-white leading-[1.15] font-display">
          Skalabilitas Enterprise. <br class="hidden sm:inline" />
          <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 via-cyan-600 to-indigo-800 dark:from-indigo-300 dark:via-cyan-200 dark:to-slate-100">
            Latensi P99 &lt; 150ms.
          </span>
        </h1>

        <!-- High-Impact Summary -->
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 max-w-xl mx-auto lg:mx-0 leading-relaxed font-normal">
          Mengeliminasi insiden kehabisan koneksi database (*MySQL Connection Exhaustion*) pada shared hosting. Dilengkapi otentikasi Sanctum berbasis <strong>Signed JWT Cookie Anti-Tampering</strong>, <strong>Multi-Role RBAC</strong>, dan frontend reaktif ultra-cepat <strong>Svelte 5 Runes</strong>.
        </p>

        <!-- CTA Action Buttons with Spring Micro-Interactions -->
        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3 pt-2">
          <button
            type="button"
            id="hero-cta-btn"
            onclick={handleCtaClick}
            class="w-full sm:w-auto px-6 py-3.5 rounded-xl font-semibold text-sm text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/30 border border-indigo-400/30 active:scale-[0.98] transition-all duration-150 flex items-center justify-center gap-2 cursor-pointer min-h-[44px]"
          >
            <span>{authState.isAuthenticated ? 'Buka Dashboard Sistem' : 'Masuk ke Portal Sistem'}</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
          </button>

          <a
            href="#metrics"
            onclick={handleNavClick}
            class="w-full sm:w-auto px-6 py-3.5 rounded-xl font-semibold text-sm text-slate-700 dark:text-slate-300 bg-white/80 dark:bg-slate-800/80 hover:bg-slate-100 dark:hover:bg-slate-700/80 border border-slate-200/90 dark:border-slate-700 hover:text-indigo-600 dark:hover:text-white active:scale-[0.98] transition-all duration-150 text-center backdrop-blur-md shadow-2xs min-h-[44px] flex items-center justify-center"
          >
            Pelajari Benchmark SLO
          </a>
        </div>

        <!-- Trust Badges with Emerald Highlight -->
        <div class="pt-3 flex flex-wrap items-center justify-center lg:justify-start gap-5 text-xs text-slate-600 dark:text-slate-300">
          <div class="flex items-center gap-1.5">
            <svg class="w-4 h-4 text-emerald-500 dark:text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            <span>Hostinger Premium Ready</span>
          </div>
          <div class="flex items-center gap-1.5">
            <svg class="w-4 h-4 text-emerald-500 dark:text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            <span>Single Domain Zero Nginx Hack</span>
          </div>
          <div class="flex items-center gap-1.5">
            <svg class="w-4 h-4 text-emerald-500 dark:text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            <span>HMAC-SHA256 Signed Cookies</span>
          </div>
        </div>
      </div>

      <!-- Hero Right: Apple Spatial Glass Card (Architectural Visual Simulation) -->
      <div class="lg:col-span-5 relative flex justify-center">
        <div class="relative w-full max-w-md rounded-2xl bg-white/80 dark:bg-slate-900/85 border border-slate-200/90 dark:border-white/10 p-5 sm:p-6 shadow-xl dark:shadow-[0_12px_40px_0_rgba(0,0,0,0.45)] backdrop-blur-xl backdrop-saturate-150 hover:border-indigo-400/50 dark:hover:border-indigo-500/40 transition-all duration-300 before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/40 dark:before:via-white/20 before:to-transparent group">
          
          <!-- Mock Window Header (macOS Traffic Lights) -->
          <div class="flex items-center justify-between pb-3.5 border-b border-slate-200/80 dark:border-slate-800 mb-4">
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-rose-500 inline-block"></span>
              <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span>
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
            </div>
            <span class="text-[11px] font-mono text-slate-500 dark:text-slate-400">cluster.valora-vault.test:80</span>
            <span class="text-[10px] px-2 py-0.5 rounded bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 font-mono font-bold border border-emerald-300/80 dark:border-emerald-800/60">LIVE</span>
          </div>

          <!-- Animated Architecture Nodes Graphic -->
          <div class="space-y-3.5">
            <!-- Node 1: FastCGI Pool -->
            <div class="p-3 bg-slate-50/90 dark:bg-slate-950/70 rounded-xl border border-slate-200/80 dark:border-slate-800 flex items-center justify-between hover:border-indigo-400 transition">
              <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-400 flex items-center justify-center font-bold text-xs shrink-0">
                  PHP
                </div>
                <div>
                  <div class="text-xs font-semibold text-slate-900 dark:text-white">PHP-CGI Workers (Dual Instance)</div>
                  <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">127.0.0.1:9003 & 9004 • Upstream Round-Robin</div>
                </div>
              </div>
              <span class="text-xs font-mono text-emerald-600 dark:text-emerald-400 font-bold">0 Drop</span>
            </div>

            <!-- Connector Beam Animation -->
            <div class="flex justify-center -my-1">
              <div class="h-3.5 w-0.5 bg-gradient-to-b from-indigo-500 to-cyan-500 animate-pulse"></div>
            </div>

            <!-- Node 2: Database Layer -->
            <div class="p-3 bg-slate-50/90 dark:bg-slate-950/70 rounded-xl border border-slate-200/80 dark:border-slate-800 flex items-center justify-between hover:border-cyan-400 transition">
              <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-cyan-100 dark:bg-cyan-900/60 text-cyan-700 dark:text-cyan-400 flex items-center justify-center font-bold text-xs shrink-0">
                  SQL
                </div>
                <div>
                  <div class="text-xs font-semibold text-slate-900 dark:text-white">SQLite Concurrency Isolation</div>
                  <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">WAL Mode Active • Zero MySQL Exhaustion</div>
                </div>
              </div>
              <span class="text-xs font-mono text-cyan-600 dark:text-cyan-300 font-bold">P99: 12ms</span>
            </div>

            <!-- Connector Beam Animation -->
            <div class="flex justify-center -my-1">
              <div class="h-3.5 w-0.5 bg-gradient-to-b from-cyan-500 to-emerald-500 animate-pulse"></div>
            </div>

            <!-- Node 3: Svelte 5 Runes SPA -->
            <div class="p-3 bg-slate-50/90 dark:bg-slate-950/70 rounded-xl border border-slate-200/80 dark:border-slate-800 flex items-center justify-between hover:border-emerald-400 transition">
              <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-bold text-xs shrink-0">
                  SV5
                </div>
                <div>
                  <div class="text-xs font-semibold text-slate-900 dark:text-white">Svelte 5 Runes ($state, $derived)</div>
                  <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">Client-side JWT Decode • Multi-Role UI</div>
                </div>
              </div>
              <span class="text-xs font-mono text-emerald-600 dark:text-emerald-300 font-bold">&lt; 1ms React</span>
            </div>
          </div>

          <!-- Bottom Micro Metrics Strip -->
          <div class="mt-4 pt-3.5 border-t border-slate-200/80 dark:border-slate-800 grid grid-cols-3 gap-2 text-center text-[10px]">
            <div>
              <span class="text-slate-500 dark:text-slate-400 block">Throughput</span>
              <span class="font-bold text-slate-900 dark:text-white font-mono">20 RPS Peak</span>
            </div>
            <div>
              <span class="text-slate-500 dark:text-slate-400 block">Avail SLO</span>
              <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">99.0% target</span>
            </div>
            <div>
              <span class="text-slate-500 dark:text-slate-400 block">RTO / RPO</span>
              <span class="font-bold text-cyan-600 dark:text-cyan-400 font-mono">4h / 24h</span>
            </div>
          </div>

        </div>
      </div>

    </div>
  </section>

  <!-- ================= AI ARCHITECT MASCOT (VOLT) ================= -->
  <section id="mascot" class="max-w-6xl mx-auto px-4 sm:px-6 py-4">
    <ArchitectMascot />
  </section>

  <!-- ================= BENCHMARK SLO METRICS ================= -->
  <section id="metrics" class="border-t border-b border-slate-200/80 dark:border-slate-800 bg-slate-100/60 dark:bg-slate-950/60 py-12 backdrop-blur-md">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
      
      <div class="text-center max-w-2xl mx-auto mb-10">
        <h2 class="text-xs uppercase font-mono font-bold tracking-widest text-indigo-600 dark:text-indigo-400 mb-2">High-Impact SLO Metrics</h2>
        <p class="text-2xl font-bold text-slate-900 dark:text-white">Audit & Verifikasi Terhadap Beban Nyata</p>
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Metric Card 1 -->
        <div class="relative overflow-hidden p-5 rounded-2xl bg-white/80 dark:bg-slate-900/80 border border-slate-200/80 dark:border-white/10 hover:border-indigo-500/40 hover:-translate-y-0.5 active:scale-[0.99] transition duration-200 backdrop-blur-xl shadow-2xs before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/30 dark:before:via-white/15 before:to-transparent">
          <div class="text-2xl sm:text-3xl font-extrabold text-indigo-600 dark:text-indigo-400 font-mono mb-1">&lt; 150ms</div>
          <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">P99 Response Time</div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">Ditekan dari insiden 2.5s menjadi hitungan milidetik secara stabil.</p>
        </div>

        <!-- Metric Card 2 -->
        <div class="relative overflow-hidden p-5 rounded-2xl bg-white/80 dark:bg-slate-900/80 border border-slate-200/80 dark:border-white/10 hover:border-cyan-500/40 hover:-translate-y-0.5 active:scale-[0.99] transition duration-200 backdrop-blur-xl shadow-2xs before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/30 dark:before:via-white/15 before:to-transparent">
          <div class="text-2xl sm:text-3xl font-extrabold text-cyan-600 dark:text-cyan-400 font-mono mb-1">99.0%</div>
          <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">Target SLO Ketersediaan</div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">Toleransi downtime maksimal 7.2 jam/bulan untuk shared hosting.</p>
        </div>

        <!-- Metric Card 3 -->
        <div class="relative overflow-hidden p-5 rounded-2xl bg-white/80 dark:bg-slate-900/80 border border-slate-200/80 dark:border-white/10 hover:border-emerald-500/40 hover:-translate-y-0.5 active:scale-[0.99] transition duration-200 backdrop-blur-xl shadow-2xs before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/30 dark:before:via-white/15 before:to-transparent">
          <div class="text-2xl sm:text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 font-mono mb-1">20 RPS</div>
          <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">Beban Puncak Simultan</div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">15-25 CCU teruji tanpa connection exhaustion maupun pool timeout.</p>
        </div>

        <!-- Metric Card 4 -->
        <div class="relative overflow-hidden p-5 rounded-2xl bg-white/80 dark:bg-slate-900/80 border border-slate-200/80 dark:border-white/10 hover:border-amber-500/40 hover:-translate-y-0.5 active:scale-[0.99] transition duration-200 backdrop-blur-xl shadow-2xs before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/30 dark:before:via-white/15 before:to-transparent">
          <div class="text-2xl sm:text-3xl font-extrabold text-amber-600 dark:text-amber-400 font-mono mb-1">0 Hack</div>
          <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">Nginx Laragon Bawaan</div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">Zero-config vhost, phpMyAdmin 100% normal tanpa sentuh konfig Laragon.</p>
        </div>
      </div>

    </div>
  </section>

  <!-- ================= PILAR REKAYASA ARSITEKTUR ================= -->
  <section id="pillars" class="max-w-6xl mx-auto px-4 sm:px-6 py-14 sm:py-18">
    <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-12">
      <h2 class="text-xs uppercase font-mono font-bold tracking-widest text-indigo-600 dark:text-indigo-400 mb-2">Pilar Rekayasa Arsitektur</h2>
      <p class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">Standar Enterprise CTO untuk Proyek Skalabel</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
      <!-- Feature 1 -->
      <div class="relative overflow-hidden p-6 rounded-2xl bg-white/80 dark:bg-slate-900/80 border border-slate-200/80 dark:border-white/10 hover:border-indigo-400/50 dark:hover:border-indigo-500/40 hover:-translate-y-1 active:scale-[0.99] transition duration-200 backdrop-blur-xl shadow-2xs before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/30 dark:before:via-white/15 before:to-transparent">
        <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-4">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Signed JWT Cookie Anti-Tamper</h3>
        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed font-normal">
          Ditandatangani secara kriptografis menggunakan algoritma HMAC-SHA256 via <code>APP_KEY</code>. Data role dan nama dapat dibaca instan oleh Svelte 5 tanpa risiko manipulasi oleh pengguna di sisi browser.
        </p>
      </div>

      <!-- Feature 2 -->
      <div class="relative overflow-hidden p-6 rounded-2xl bg-white/80 dark:bg-slate-900/80 border border-slate-200/80 dark:border-white/10 hover:border-cyan-400/50 dark:hover:border-cyan-500/40 hover:-translate-y-1 active:scale-[0.99] transition duration-200 backdrop-blur-xl shadow-2xs before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/30 dark:before:via-white/15 before:to-transparent">
        <div class="w-10 h-10 rounded-xl bg-cyan-100 dark:bg-cyan-900/40 text-cyan-600 dark:text-cyan-400 flex items-center justify-center mb-4">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Multiple Roles dalam 1 Record</h3>
        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed font-normal">
          Pengguna dapat mengemban banyak kewenangan sekaligus (contoh: <code>['admin', 'manager']</code>) tanpa memerlukan tabel pivot database yang lambat. Helper model <code>hasRole()</code> dan <code>isAdmin()</code> terintegrasi penuh.
        </p>
      </div>

      <!-- Feature 3 -->
      <div class="relative overflow-hidden p-6 rounded-2xl bg-white/80 dark:bg-slate-900/80 border border-slate-200/80 dark:border-white/10 hover:border-emerald-400/50 dark:hover:border-emerald-500/40 hover:-translate-y-1 active:scale-[0.99] transition duration-200 backdrop-blur-xl shadow-2xs before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/30 dark:before:via-white/15 before:to-transparent">
        <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-4">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
        </div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Terminasi Sesi & Multi-Device</h3>
        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed font-normal">
          Pencabutan sesi secara instan berbasis <code>token_version</code> tanpa beban query sesi MySQL. Admin dapat menangguhkan user atau mencabut akses perangkat yang mencurigakan dalam 1 klik.
        </p>
      </div>
    </div>
  </section>

  <!-- ================= CTA BANNER SECTION ================= -->
  <section class="max-w-6xl mx-auto px-4 sm:px-6 pb-20">
    <div class="relative overflow-hidden rounded-3xl bg-white/80 dark:bg-slate-900/90 border border-slate-200/90 dark:border-white/10 p-8 sm:p-12 text-center backdrop-blur-xl shadow-xl before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/40 dark:before:via-white/20 before:to-transparent">
      <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-3">Siap Menggunakan Template Ini di Proyek Berikutnya?</h2>
      <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 max-w-xl mx-auto mb-6 leading-relaxed">
        Cukup clone ke folder <code>D:\laragon\www\&lt;nama_proyek&gt;</code>, jalankan <code>migrate --seed</code>, dan nikmati lingkungan fullstack berkinerja tinggi tanpa setup rumit.
      </p>
      <button
        type="button"
        onclick={handleCtaClick}
        class="px-8 py-3.5 rounded-xl font-bold text-sm text-white bg-indigo-600 hover:bg-indigo-500 shadow-lg shadow-indigo-600/30 active:scale-95 transition-all duration-150 cursor-pointer min-h-[44px]"
      >
        {authState.isAuthenticated ? 'Masuk ke Dashboard' : 'Mulai Akses Sekarang'}
      </button>
    </div>
  </section>
  </main>

  <!-- ================= SEMANTIC SEO FOOTER ================= -->
  <footer class="border-t border-slate-200/80 dark:border-slate-800 bg-white/80 dark:bg-slate-950 py-8 text-center text-xs text-slate-500 dark:text-slate-400 mt-auto">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <span class="font-bold text-slate-800 dark:text-slate-200">Valora Vault</span>
        <span>•</span>
        <span>Arsitektur Enterprise CTO Indonesia (v6.31.0)</span>
      </div>
      <div class="text-[11px] text-slate-500 dark:text-slate-400">
        PHP 8.3/8.4 • Laravel 13 Sanctum • Svelte 5 Runes • SQLite Concurrency • Nginx Laragon
      </div>
      <div>
        &copy; {new Date().getFullYear()} Hak Cipta Dilindungi Undang-Undang.
      </div>
    </div>
  </footer>

  <!-- ================= FLOATING THUMB DOCK (MOBILE ONLY) ================= -->
  <div class="fixed bottom-3.5 left-1/2 -translate-x-1/2 z-40 md:hidden flex items-center gap-1.5 p-1 rounded-full bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200/90 dark:border-white/15 shadow-lg shadow-black/15">
    <a
      href="#hero"
      onclick={handleNavClick}
      class="px-3 py-1.5 rounded-full text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 active:scale-95 transition"
      aria-label="Kembali ke atas"
    >
      Top
    </a>
    <button
      type="button"
      onclick={handleToggleTheme}
      class="w-8 h-8 rounded-full flex items-center justify-center text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 active:scale-95 transition"
      title="Alihkan tema visual"
      aria-label="Toggle tema visual"
    >
      {#if themeState.isDark}
        <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
      {:else}
        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
      {/if}
    </button>
    <button
      type="button"
      onclick={handleToggleAudio}
      class="w-8 h-8 rounded-full flex items-center justify-center text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 active:scale-95 transition"
      title="Toggle audio feedback"
      aria-label="Toggle audio feedback"
    >
      {#if isAudioMuted}
        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
        </svg>
      {:else}
        <svg class="w-3.5 h-3.5 text-indigo-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
        </svg>
      {/if}
    </button>
    <button
      type="button"
      onclick={handleCtaClick}
      class="px-3 py-1.5 rounded-full text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 active:scale-95 transition"
    >
      {authState.isAuthenticated ? 'Dashboard' : 'Masuk'}
    </button>
  </div>

</div>

<style>
  @media (prefers-reduced-motion: reduce) {
    *, ::before, ::after {
      animation-duration: 0.01ms !important;
      animation-iteration-count: 1 !important;
      transition-duration: 0.01ms !important;
      scroll-behavior: auto !important;
    }
  }
</style>
