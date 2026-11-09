<script>
  import { page } from '$app/state';
  import { authState } from '$lib/state/auth.svelte.js';
  import { themeState } from '$lib/state/theme.svelte.js';
  import { tactileAudio } from '$lib/utils/audioFeedback.js';
  import Badge from '$lib/components/ui/Badge.svelte';

  let {
    ontoggle = () => {},
    onlogout = null,
    loggingOut = false
  } = $props();

  let isAudioMuted = $state(false);

  $effect(() => {
    if (typeof window !== 'undefined') {
      isAudioMuted = tactileAudio.muted;
    }
  });

  function handleToggleAudio() {
    isAudioMuted = tactileAudio.toggleMute();
  }

  function handleToggleTheme() {
    tactileAudio.playToggle(!themeState.isDark);
    themeState.toggle();
  }

  let pageTitle = $derived.by(() => {
    const path = page.url.pathname;
    if (path === '/') return 'Beranda';
    if (path === '/dashboard') return authState.hasRole('admin') ? 'Enterprise Dashboard' : 'Dashboard Akun';
    if (path === '/admin/dashboard') return 'Enterprise Dashboard';
    if (path === '/admin/clients') return 'Manajemen Klien & Proyek';
    if (path === '/admin/tagihan') return 'Tagihan & Invoice Proyek';
    if (path.startsWith('/admin/tagihan/invoice')) return 'Detail Invoice Tagihan';
    if (path.startsWith('/admin/tagihan/kuitansi')) return 'Kuitansi Resmi Pembayaran';
    if (path === '/admin/users') return 'Kelola Pengguna';
    if (path === '/admin/sessions') return 'Manajemen Sesi Aktif';
    if (path === '/admin/system') return 'Sistem & Profil Agensi';
    if (path === '/admin/studio') return 'Blueprint Studio';
    return path.replace('/', '').replace('/', ' / ');
  });
</script>

<header id="app-topbar" class="app-topbar print-hide sticky top-0 z-40 h-11.5 sm:h-12 bg-white/95 dark:bg-[#090d16]/95 backdrop-blur-xl backdrop-saturate-150 border-b border-stone-200/80 dark:border-white/10 shadow-2xs before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/30 dark:before:via-white/15 before:to-transparent px-2.5 sm:px-4 flex items-center justify-between transition-colors duration-200">
  <!-- Left: Mobile toggle & Breadcrumb -->
  <div class="flex items-center gap-2 sm:gap-2.5 min-w-0">
    <!-- Mobile Hamburger Button (Compact 34px) -->
    <button
      type="button"
      onclick={() => { tactileAudio.playTap(); ontoggle(); }}
      class="lg:hidden w-8.5 h-8.5 flex items-center justify-center rounded-lg text-stone-600 hover:text-stone-900 dark:text-slate-400 dark:hover:text-white hover:bg-stone-100 dark:hover:bg-slate-800 transition cursor-pointer active:scale-95 shrink-0"
      aria-label="Buka Navigasi"
    >
      <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
      </svg>
    </button>

    <!-- Clean Breadcrumb indicator with SVG Home icon -->
    <nav aria-label="Breadcrumb" class="flex items-center gap-1.5 text-[11px] text-stone-600 dark:text-slate-400 min-w-0">
      <a href="/" onclick={() => tactileAudio.playTap()} aria-label="Beranda" class="flex items-center text-stone-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-cyan-400 transition shrink-0">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
      </a>
      <svg class="w-3 h-3 text-stone-400 dark:text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
      <span class="font-semibold text-stone-700 dark:text-slate-300 hidden sm:inline shrink-0">{import.meta.env.VITE_APP_NAME || 'Valora Vault'}</span>
      <svg class="w-3 h-3 text-stone-400 dark:text-slate-600 hidden sm:inline shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
      <span class="text-stone-900 dark:text-white font-bold capitalize truncate max-w-[140px] sm:max-w-xs md:max-w-none text-xs">{pageTitle}</span>
    </nav>
  </div>

  <!-- Right: Audio Switcher, Theme Switcher, Profile Pill & Explicit Actions -->
  <div class="flex items-center gap-1.5 sm:gap-2">
    <!-- Auditory Feedback Toggle Button -->
    <button
      type="button"
      onclick={handleToggleAudio}
      class="w-8.5 h-8.5 rounded-lg border transition-all duration-150 active:scale-95 cursor-pointer flex items-center justify-center shrink-0 {isAudioMuted ? 'bg-stone-100 dark:bg-slate-900 text-stone-400 dark:text-slate-500 border-stone-200/80 dark:border-slate-800' : 'bg-indigo-50 dark:bg-indigo-950/60 border-indigo-300/80 dark:border-indigo-500/40 text-indigo-600 dark:text-indigo-400 shadow-2xs'}"
      title={isAudioMuted ? 'Suara UI Senyap (Klik untuk menyalakan audio feedback Apple)' : 'Suara UI Aktif (Klik untuk membisukan)'}
      aria-label={isAudioMuted ? 'Aktifkan suara feedback' : 'Bisukan suara feedback'}
    >
      {#if isAudioMuted}
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
        </svg>
      {:else}
        <svg class="w-3.5 h-3.5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
        </svg>
      {/if}
    </button>

    <!-- Daylight / Dark Mode Switcher (Compact 34px) -->
    <button
      type="button"
      onclick={handleToggleTheme}
      title={themeState.isDark ? 'Beralih ke Mode Terang (Daylight)' : 'Beralih ke Mode Gelap (Dark)'}
      aria-label="Ganti Mode Tema"
      class="w-8.5 h-8.5 rounded-lg text-stone-700 hover:text-stone-950 dark:text-slate-300 dark:hover:text-white bg-stone-100 hover:bg-stone-200 dark:bg-slate-900 dark:hover:bg-slate-800 transition cursor-pointer border border-stone-200/80 dark:border-slate-800 flex items-center justify-center active:scale-95 shrink-0"
    >
      {#if themeState.isDark}
        <!-- Sun Icon (Active Dark -> Switch to Light) -->
        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
      {:else}
        <!-- Moon Icon (Active Light -> Switch to Dark) -->
        <svg class="w-4 h-4 text-stone-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
      {/if}
    </button>

    {#if authState.isAuthenticated}
      <!-- User Profile Badge -->
      <div class="flex items-center gap-2 pl-2 border-l border-stone-200 dark:border-slate-800">
        <div class="flex items-center gap-2 px-2.5 py-1 rounded-xl bg-stone-100/90 dark:bg-slate-900/90 border border-stone-200/80 dark:border-slate-800">
          <div class="relative">
            <div class="w-7 h-7 rounded-lg bg-gradient-to-tr from-indigo-600 to-cyan-500 flex items-center justify-center font-bold text-white text-[10px] shadow-xs">
              {authState.name.slice(0, 2).toUpperCase()}
            </div>
            <!-- Live Status Pulse Dot -->
            <span class="absolute -bottom-0.5 -right-0.5 flex h-2 w-2">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
          </div>
          <div class="hidden sm:flex flex-col text-left">
            <span class="text-[11.5px] font-extrabold text-stone-900 dark:text-white truncate max-w-[120px]">
              {authState.name}
            </span>
            <div class="flex items-center gap-1 mt-0.5">
              <span class="text-[8.5px] font-mono font-bold uppercase px-1 py-0.2 rounded bg-indigo-100 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300">
                {authState.roles[0] || 'User'}
              </span>
            </div>
          </div>
        </div>

        {#if onlogout}
          <button
            type="button"
            onclick={onlogout}
            disabled={loggingOut}
            title="Keluar dari sesi ini secara aman"
            aria-label="Keluar dari sistem"
            class="min-h-[40px] px-2.5 py-1.5 rounded-xl text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-rose-200 dark:border-rose-900/40 transition flex items-center gap-1.5 text-xs font-bold cursor-pointer disabled:opacity-50 active:scale-95"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span class="hidden md:inline">{loggingOut ? 'Keluar...' : 'Keluar'}</span>
          </button>
        {/if}
      </div>
    {/if}
  </div>
</header>
