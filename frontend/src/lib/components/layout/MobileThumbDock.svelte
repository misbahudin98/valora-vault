<script>
  import { page } from '$app/state';

  let {
    ontoggle = () => {}
  } = $props();

  let isDashboard = $derived(page.url.pathname === '/dashboard');
</script>

<!-- Mobile Floating Thumb Dock (Ergonomic Thumb Zone Navigation - Apple Spatial Glass) -->
<nav
  id="mobile-thumb-dock"
  aria-label="Navigasi Cepat Jempol Mobile"
  class="mobile-thumb-dock print-hide fixed bottom-3.5 left-1/2 -translate-x-1/2 z-30 md:hidden flex items-center gap-1 p-1 rounded-full bg-white/80 dark:bg-[#090d16]/85 backdrop-blur-xl border border-white/50 dark:border-white/15 before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:rounded-full before:bg-gradient-to-r before:from-transparent before:via-white/40 dark:before:via-white/20 before:to-transparent shadow-xl shadow-slate-950/20 dark:shadow-black/70 select-none transition-all duration-200 animate-in fade-in slide-in-from-bottom-2"
>
  <!-- 1. Beranda / Dashboard Quick Action -->
  <a
    href="/dashboard"
    class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-semibold transition active:scale-95 {isDashboard ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-900'}"
    title="Menuju Dashboard"
  >
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
    </svg>
    <span>Beranda</span>
  </a>

  <!-- 2. Segarkan / Refresh Quick Action -->
  <button
    type="button"
    onclick={() => window.location.reload()}
    class="flex items-center gap-1 px-2.5 py-1.5 rounded-full text-[11px] font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-900 transition active:scale-95 cursor-pointer"
    title="Segarkan Halaman Ini"
  >
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
    </svg>
    <span class="sr-only sm:not-sr-only">Segarkan</span>
  </button>

  <div class="w-px h-4 bg-slate-200 dark:bg-slate-800 my-auto"></div>

  <!-- 3. Buka Menu Utama (Sidebar Drawer Trigger right at Thumb Reach) -->
  <button
    type="button"
    onclick={ontoggle}
    class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-[11px] font-bold text-white bg-indigo-600 hover:bg-indigo-500 shadow-sm transition active:scale-95 cursor-pointer"
    title="Buka Menu Navigasi Lengkap"
    aria-label="Buka Semua Menu"
  >
    <svg class="w-3.5 h-3.5 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
    <span>Menu</span>
  </button>
</nav>
