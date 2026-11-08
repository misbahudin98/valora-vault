<script>
  /**
   * Interactive Mascot Component: "Volt" the Enterprise AI System Architect
   * Pure SVG + CSS animation, responsive, educational, and delightful.
   */
  let currentTopic = $state('intro');
  let isHovered = $state(false);
  let clickCount = $state(0);
  let mascotMood = $state('happy'); // 'happy' | 'thinking' | 'excited' | 'super'

  const topics = [
    {
      id: 'fastcgi',
      icon: '⚡',
      label: 'FastCGI Dual-Worker',
      title: 'FastCGI Dual-Worker Persisten (Windows Laragon)',
      text: 'Di Windows, php-cgi.exe single-threaded. Kami mengaktifkan 2 worker di port 9003 & 9004 dengan $env:PHP_FCGI_MAX_REQUESTS="0". Hasilnya: Nginx round-robin stabil tanpa 502 Bad Gateway!',
      mood: 'excited'
    },
    {
      id: 'session',
      icon: '🗄️',
      label: 'Zero-DB Session',
      title: 'Dekomposisi Sesi vs Registry Database',
      text: 'Sesi disimpan di file NVMe lokal (SESSION_DRIVER=file), bukan di MySQL. Koneksi MySQL yang tersandera per request turun menjadi 0%, mencegah crash 1040 Too Many Connections di Hostinger!',
      mood: 'happy'
    },
    {
      id: 'runes',
      icon: '🚀',
      label: 'Svelte 5 Runes',
      title: 'Reaktivitas Fine-Grained Pure JS',
      text: 'Menggunakan $state, $derived, dan $effect tanpa Virtual DOM. Kompilasi ultra-ringan menghasilkan latensi rendering browser sub-milidetik!',
      mood: 'excited'
    },
    {
      id: 'security',
      icon: '🛡️',
      label: 'Signed JWT & Sanctum',
      title: 'Dual-Layer Hybrid Authentication',
      text: 'Otoritas absolut ada di cookie HttpOnly Laravel Sanctum. Svelte 5 membaca signed cookie auth_jwt ber-HMAC-SHA256 untuk reaktivitas UI instan tanpa bisa dimanipulasi klien.',
      mood: 'super'
    },
    {
      id: 'scale',
      icon: '📈',
      label: 'Skala 9000+ Data',
      title: 'Perlindungan Memori & Indeks B-Tree',
      text: 'Indeks komposit pada users & user_devices serta query ceiling limit 1000 items mencegah crash memory exhaustion (>18MB) saat data membengkak hingga puluhan ribu baris.',
      mood: 'thinking'
    },
    {
      id: 'installer',
      icon: '🔄',
      label: 'Auto-Detect Setup',
      title: 'Gateway Auto-Detect & install.php',
      text: 'index.php otomatis mendeteksi jika aplikasi belum terpasang dan mengarahkan browser ke install.php untuk diagnostik 13 prasyarat dan instalasi 1-klik yang aman!',
      mood: 'happy'
    }
  ];

  const currentTopicData = $derived(
    topics.find(t => t.id === currentTopic) || {
      title: 'Halo Rekan Engineer! Saya Volt ⚡',
      text: 'Saya maskot AI Architect sistem template ini. Klik salah satu tombol fitur di bawah untuk melihat bagaimana kami memangkas latensi P99 dari 2.500ms menjadi ~65ms!',
      mood: 'happy'
    }
  );

  function selectTopic(id) {
    currentTopic = id;
    const t = topics.find(item => item.id === id);
    if (t) mascotMood = t.mood;
  }

  function handleMascotClick() {
    clickCount++;
    const moods = ['happy', 'excited', 'thinking', 'super'];
    mascotMood = moods[clickCount % moods.length];
    
    // Cycle topic on direct click
    const nextIndex = clickCount % (topics.length + 1);
    if (nextIndex < topics.length) {
      currentTopic = topics[nextIndex].id;
    } else {
      currentTopic = 'intro';
    }
  }
</script>

<div class="relative w-full max-w-4xl mx-auto my-12 p-6 sm:p-8 rounded-3xl bg-slate-900/90 border border-slate-700/60 shadow-2xl backdrop-blur-xl overflow-hidden">
  <!-- Glowing ambient light -->
  <div class="absolute -top-24 -left-24 w-72 h-72 bg-cyan-500/15 rounded-full blur-3xl pointer-events-none"></div>
  <div class="absolute -bottom-24 -right-24 w-72 h-72 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none"></div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center relative z-10">
    
    <!-- Left Column: Interactive Animated Mascot Character -->
    <div class="lg:col-span-4 flex flex-col items-center justify-center text-center">
      <button 
        type="button"
        class="relative cursor-pointer group select-none bg-transparent border-0 p-0 text-left outline-none"
        onclick={handleMascotClick}
        onmouseenter={() => isHovered = true}
        onmouseleave={() => isHovered = false}
        aria-label="Klik Volt untuk interaksi"
      >
        <!-- Floating Ambient Aura -->
        <div class="absolute inset-0 rounded-full bg-gradient-to-tr from-cyan-500/30 to-indigo-500/30 blur-2xl transition-all duration-500 group-hover:scale-125 {isHovered ? 'opacity-80' : 'opacity-40'}"></div>

        <!-- Volt SVG Character -->
        <svg
          class="w-48 h-48 sm:w-56 sm:h-56 transition-transform duration-300 ease-out transform group-hover:scale-105 filter drop-shadow-[0_10px_20px_rgba(6,182,212,0.3)] animate-float"
          viewBox="0 0 200 200"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <!-- Thruster Glow Particle -->
          <ellipse cx="100" cy="178" rx="22" ry="6" fill="#06b6d4" opacity="0.6" class="animate-pulse" />
          <path d="M92 178 L100 192 L108 178 Z" fill="url(#thrusterGrad)" class="animate-bounce" />

          <!-- Body Shell -->
          <rect x="58" y="105" width="84" height="68" rx="28" fill="url(#bodyGrad)" stroke="#475569" stroke-width="2.5" />
          
          <!-- Chest Lightning Emblem -->
          <rect x="78" y="122" width="44" height="34" rx="10" fill="#090d16" stroke="#06b6d4" stroke-width="1.5" />
          <path d="M102 127 L93 140 H101 L97 151 L108 138 H100 L104 127 Z" fill="#38bdf8" class="animate-pulse" />

          <!-- Arms (Hovering) -->
          <path d="M48 126 C40 132, 40 148, 49 154 C53 156, 56 150, 54 144 Z" fill="url(#limbGrad)" stroke="#334155" stroke-width="2" />
          <path d="M152 126 C160 132, 160 148, 151 154 C147 156, 144 150, 146 144 Z" fill="url(#limbGrad)" stroke="#334155" stroke-width="2" />

          <!-- Head Unit -->
          <rect x="50" y="44" width="100" height="76" rx="36" fill="url(#headGrad)" stroke="#64748b" stroke-width="2.5" />

          <!-- Antenna -->
          <rect x="97" y="24" width="6" height="22" rx="3" fill="#64748b" />
          <circle cx="100" cy="20" r="10" fill="url(#antennaGrad)" stroke="#38bdf8" stroke-width="2" class="animate-ping opacity-75" />
          <circle cx="100" cy="20" r="7" fill="#06b6d4" />

          <!-- Ear Sensors -->
          <rect x="42" y="66" width="10" height="28" rx="4" fill="#0ea5e9" opacity="0.9" />
          <rect x="148" y="66" width="10" height="28" rx="4" fill="#6366f1" opacity="0.9" />

          <!-- Cyber Visor / Face Screen -->
          <rect x="62" y="56" width="76" height="50" rx="20" fill="#020617" stroke="#1e293b" stroke-width="2" />

          <!-- Dynamic Eyes Based on Mood -->
          {#if mascotMood === 'happy'}
            <!-- Curved Smiling Cyan Eyes -->
            <path d="M74 84 C74 76, 88 76, 88 84" stroke="#22d3ee" stroke-width="4.5" stroke-linecap="round" fill="none" class="animate-pulse" />
            <path d="M112 84 C112 76, 126 76, 126 84" stroke="#22d3ee" stroke-width="4.5" stroke-linecap="round" fill="none" class="animate-pulse" />
          {:else if mascotMood === 'excited'}
            <!-- Wide Star Eyes -->
            <circle cx="81" cy="80" r="7" fill="#38bdf8" />
            <circle cx="83" cy="78" r="2.5" fill="#ffffff" />
            <circle cx="119" cy="80" r="7" fill="#38bdf8" />
            <circle cx="121" cy="78" r="2.5" fill="#ffffff" />
            <path d="M96 92 Q100 97 104 92" stroke="#22d3ee" stroke-width="2.5" stroke-linecap="round" fill="none" />
          {:else if mascotMood === 'thinking'}
            <!-- Thinking Focus Eyes -->
            <ellipse cx="81" cy="80" rx="7" ry="4" fill="#a855f7" />
            <ellipse cx="119" cy="78" rx="7" ry="6" fill="#a855f7" />
            <circle cx="121" cy="76" r="2" fill="#ffffff" />
          {:else}
            <!-- Super Hero Glowing Eyes -->
            <polygon points="73,83 88,77 84,87" fill="#38bdf8" />
            <polygon points="127,83 112,77 116,87" fill="#38bdf8" />
          {/if}

          <!-- Cheeks Rosy Glow -->
          <circle cx="70" cy="94" r="5" fill="#ec4899" opacity="0.4" />
          <circle cx="130" cy="94" r="5" fill="#ec4899" opacity="0.4" />

          <!-- Gradients Definitions -->
          <defs>
            <linearGradient id="bodyGrad" x1="58" y1="105" x2="142" y2="173" gradientUnits="userSpaceOnUse">
              <stop stop-color="#1e293b" />
              <stop offset="1" stop-color="#0f172a" />
            </linearGradient>
            <linearGradient id="headGrad" x1="50" y1="44" x2="150" y2="120" gradientUnits="userSpaceOnUse">
              <stop stop-color="#334155" />
              <stop offset="0.5" stop-color="#1e293b" />
              <stop offset="1" stop-color="#0f172a" />
            </linearGradient>
            <linearGradient id="limbGrad" x1="40" y1="120" x2="60" y2="160" gradientUnits="userSpaceOnUse">
              <stop stop-color="#475569" />
              <stop offset="1" stop-color="#1e293b" />
            </linearGradient>
            <linearGradient id="antennaGrad" x1="90" y1="10" x2="110" y2="30" gradientUnits="userSpaceOnUse">
              <stop stop-color="#38bdf8" />
              <stop offset="1" stop-color="#6366f1" />
            </linearGradient>
            <linearGradient id="thrusterGrad" x1="92" y1="178" x2="108" y2="192" gradientUnits="userSpaceOnUse">
              <stop stop-color="#38bdf8" />
              <stop offset="1" stop-color="transparent" />
            </linearGradient>
          </defs>
        </svg>

        <!-- Tap Tip Badge -->
        <span class="inline-block mt-3 px-3 py-1 rounded-full bg-slate-800/90 border border-cyan-500/30 text-cyan-300 text-xs font-medium tracking-wide shadow-sm hover:border-cyan-400">
          💡 Klik saya untuk ekspresi & tips!
        </span>
      </button>
    </div>

    <!-- Right Column: Interactive Speech Bubble & Architecture Insights -->
    <div class="lg:col-span-8 space-y-6 text-left">
      <!-- Mascot Speech Bubble Box -->
      <div class="relative p-6 rounded-2xl bg-slate-800/80 border border-cyan-500/30 shadow-lg backdrop-blur-md">
        <!-- Bubble Pointer Triangle (pointing toward Volt on desktop) -->
        <div class="hidden lg:block absolute -left-3 top-12 w-0 h-0 border-t-8 border-t-transparent border-r-12 border-r-slate-800 border-b-8 border-b-transparent"></div>

        <div class="flex items-center gap-3 mb-2">
          <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-cyan-950 text-cyan-400 border border-cyan-500/40">
            Penjelasan Arsitek
          </span>
          <span class="text-xs text-slate-400">
            SLO Latensi: <strong class="text-emerald-400">&lt; 150ms</strong> • MySQL Overhead: <strong class="text-cyan-400">0%</strong>
          </span>
        </div>

        <h3 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
          <span>{currentTopicData.title}</span>
        </h3>

        <p class="text-sm sm:text-base text-slate-300 mt-2 leading-relaxed">
          {currentTopicData.text}
        </p>
      </div>

      <!-- Interactive Topic Selector Pills -->
      <div class="space-y-2">
        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pilih Topik untuk Dijelaskan:</span>
        <div class="flex flex-wrap gap-2">
          {#each topics as topic}
            <button
              type="button"
              onclick={() => selectTopic(topic.id)}
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs sm:text-sm font-medium transition-all duration-200 cursor-pointer {currentTopic === topic.id ? 'bg-gradient-to-r from-cyan-500 to-indigo-600 text-white shadow-md shadow-cyan-500/20 scale-105 border-transparent' : 'bg-slate-800/80 text-slate-300 hover:bg-slate-700/80 hover:text-white border border-slate-700/60'}"
            >
              <span>{topic.icon}</span>
              <span>{topic.label}</span>
            </button>
          {/each}
        </div>
      </div>
    </div>

  </div>
</div>

<style>
  @keyframes float {
    0%, 100% {
      transform: translateY(0px) rotate(0deg);
    }
    50% {
      transform: translateY(-8px) rotate(1deg);
    }
  }

  .animate-float {
    animation: float 4s ease-in-out infinite;
  }
</style>
