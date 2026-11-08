<script>
  import { page } from '$app/state';
  import { authState } from '$lib/state/auth.svelte.js';
  import { getVisibleNavItems } from '$lib/config/navigation.js';

  let {
    isOpen = false,
    onclose = () => {},
    onlogout = () => {},
    loggingOut = false
  } = $props();

  // Dynamic visible navigation links
  let visibleNavs = $derived(getVisibleNavItems(authState));

  // Collapsible tree branches state
  let expandedTrees = $state({});

  function toggleTree(id) {
    expandedTrees[id] = !expandedTrees[id];
  }

  // Auto-expand tree if any child matches current path
  $effect(() => {
    const currentPath = page.url.pathname;
    const currentTab = page.url.searchParams.get('tab');
    for (const item of visibleNavs) {
      if (item.isTree && item.children) {
        const hasActiveChild = item.children.some(child => {
          if (child.href.includes('?tab=')) {
            const childTab = new URL(child.href, 'http://dummy.local').searchParams.get('tab');
            return childTab === currentTab;
          }
          return child.href === currentPath;
        });
        if (hasActiveChild) {
          expandedTrees[item.id] = true;
        }
      }
    }
  });
</script>

{#snippet navIcon(iconName, isActive = false)}
  <div class="w-4.5 h-4.5 flex items-center justify-center shrink-0 {isActive ? 'text-white' : 'text-stone-500 dark:text-slate-400'}">
    {#if iconName === 'dashboard'}
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
      </svg>
    {:else if iconName === 'shield'}
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
      </svg>
    {:else if iconName === 'users'}
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
      </svg>
    {:else if iconName === 'key'}
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
      </svg>
    {:else if iconName === 'server'}
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
      </svg>
    {:else if iconName === 'studio'}
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
      </svg>
    {:else if iconName === 'rocket'}
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.63 8.41m5.96 5.96a14.926 14.926 0 01-5.84 2.58m-.12-8.54a6 6 0 00-7.38 5.84h4.8m2.58-5.84a14.927 14.927 0 00-2.58 5.84m2.69-4.2a1.5 1.5 0 11-2.12-2.13 1.5 1.5 0 012.12 2.13z" />
      </svg>
    {:else}
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
    {/if}
  </div>
{/snippet}

<!-- Mobile Backdrop -->
{#if isOpen}
  <button
    type="button"
    aria-label="Tutup Menu"
    class="fixed inset-0 z-40 bg-black/70 backdrop-blur-sm lg:hidden transition-opacity"
    onclick={onclose}
  ></button>
{/if}

<!-- Left Sidebar (Enterprise Dual-Theme Minimalist Glassmorphic - Pro-Grade Compact 240px) -->
<aside
  id="app-sidebar"
  class="fixed inset-y-0 left-0 z-50 w-60 bg-white/85 dark:bg-[#090d16]/85 border-r border-stone-200/80 dark:border-white/10 backdrop-blur-2xl before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/30 dark:before:via-white/15 before:to-transparent flex flex-col transition-all duration-200 ease-in-out lg:translate-x-0 {isOpen ? 'translate-x-0' : '-translate-x-full'} shadow-2xl lg:shadow-none"
>
  <!-- Brand Logo & Header (Slim 46px) -->
  <div class="h-11.5 sm:h-12 px-3 flex items-center justify-between border-b border-stone-200/80 dark:border-white/10">
    <a href="/" class="flex items-center gap-2 group" onclick={onclose}>
      <div class="w-6.5 h-6.5 rounded-lg bg-gradient-to-tr from-indigo-600 via-indigo-500 to-cyan-400 flex items-center justify-center shadow-xs group-hover:scale-105 transition duration-150">
        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
      </div>
      <div>
        <span class="text-xs font-bold tracking-tight text-stone-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-300 transition">{import.meta.env.VITE_APP_NAME || 'Hyperion Vault'}</span>
        <span class="block text-[8px] uppercase font-mono tracking-wider text-indigo-600 dark:text-indigo-400">v6.30.0</span>
      </div>
    </a>
    <button
      type="button"
      onclick={onclose}
      aria-label="Tutup Sidebar"
      class="lg:hidden w-8.5 h-8.5 flex items-center justify-center rounded-lg text-stone-500 hover:text-stone-900 dark:text-slate-400 dark:hover:text-white hover:bg-stone-100 dark:hover:bg-slate-800/80 transition cursor-pointer active:scale-95 shrink-0"
    >
      <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
  </div>

  <!-- Navigation Menu Items (Clean & Compact) -->
  <nav id="main-navigation" class="flex-1 px-2 py-2 space-y-1 overflow-y-auto">
    {#each visibleNavs as item, idx (item.id)}
      {#if idx === 0 || item.group !== visibleNavs[idx - 1]?.group}
        <div class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400 px-2.5 {idx > 0 ? 'mt-3 pt-1.5 border-t border-stone-200/50 dark:border-slate-800/50' : 'mt-0.5'} mb-1 font-mono">
          {item.group || 'Modul Navigasi'}
        </div>
      {/if}

      {#if item.isTree}
        {@const isExpanded = !!expandedTrees[item.id]}
        {@const isAnyChildActive = item.children?.some(c => {
          if (c.href.includes('?tab=')) {
            const cTab = new URL(c.href, 'http://dummy.local').searchParams.get('tab');
            return page.url.pathname === c.href.split('?')[0] && page.url.searchParams.get('tab') === cTab;
          }
          return c.href === page.url.pathname;
        })}
        <div class="space-y-0.5">
          <!-- Collapsible Tree Parent Trigger -->
          <button
            type="button"
            onclick={() => toggleTree(item.id)}
            class="w-full min-h-[34px] flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs font-medium transition cursor-pointer active:scale-[0.99] {isAnyChildActive ? 'text-indigo-600 dark:text-indigo-400 bg-gradient-to-r from-indigo-500/10 to-cyan-500/5 dark:from-indigo-950/40 dark:to-cyan-950/20 font-bold border border-indigo-500/20 shadow-2xs' : 'text-stone-700 dark:text-slate-300 hover:bg-stone-100 dark:hover:bg-slate-800/50'}"
          >
            <div class="flex items-center gap-2 min-w-0">
              {@render navIcon(item.icon, isAnyChildActive)}
              <span class="tracking-tight text-xs truncate">{item.label}</span>
            </div>
            <svg
              class="w-3.5 h-3.5 transition-transform duration-200 {isExpanded ? 'rotate-90 text-indigo-600 dark:text-indigo-400' : 'text-stone-400'}"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>

          <!-- Collapsible Indented Tree Branches -->
          {#if isExpanded && item.children}
            <div class="ml-2.5 pl-2 border-l border-indigo-200 dark:border-indigo-900/40 space-y-0.5 py-0.5 animate-in fade-in duration-150">
              {#each item.children as child (child.id)}
                {@const isChildActive = child.href.includes('?tab=')
                  ? (page.url.pathname === child.href.split('?')[0] && page.url.searchParams.get('tab') === new URL(child.href, 'http://dummy.local').searchParams.get('tab'))
                  : (child.href === page.url.pathname)}
                <a
                  href={child.href}
                  onclick={onclose}
                  class="min-h-[30px] flex items-center gap-2 px-2 py-1 rounded-md text-[11px] font-medium transition cursor-pointer active:scale-[0.99] {isChildActive ? 'bg-indigo-600 text-white font-bold shadow-2xs' : 'text-stone-600 dark:text-slate-300 hover:bg-stone-100 dark:hover:bg-slate-800/40'}"
                >
                  <span class="w-1.5 h-1.5 rounded-full shrink-0 {isChildActive ? 'bg-cyan-300 shadow-[0_0_5px_#22d3ee]' : 'bg-stone-300 dark:bg-slate-700'}"></span>
                  <span class="tracking-tight truncate">{child.label}</span>
                </a>
              {/each}
            </div>
          {/if}
        </div>
      {:else}
        {@const isActive = page.url.pathname === item.href || (item.href !== '/' && page.url.pathname.startsWith(item.href + '/'))}
        <a
          href={item.href}
          onclick={onclose}
          class="min-h-[34px] flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-all duration-150 active:scale-[0.99] {isActive ? 'bg-gradient-to-r from-indigo-600 via-indigo-600 to-indigo-700 text-white shadow-xs border border-indigo-400/30' : 'text-stone-700 dark:text-slate-300 hover:text-stone-900 dark:hover:text-white hover:bg-stone-100 dark:hover:bg-slate-800/60'}"
        >
          {@render navIcon(item.icon, isActive)}
          <span class="tracking-tight text-xs">{item.label}</span>
        </a>
      {/if}
    {/each}
  </nav>

  <!-- Bottom User Profile & Compact Logout -->
  <div class="p-2 border-t border-stone-200/80 dark:border-white/10 bg-stone-50/70 dark:bg-[#090d16]/90 backdrop-blur-md">
    {#if authState.isAuthenticated}
      <div class="space-y-1.5">
        <!-- User Identity Row -->
        <div class="flex items-center gap-2 px-1 min-w-0">
          <div class="w-6.5 h-6.5 rounded-lg bg-gradient-to-tr from-indigo-600 to-cyan-500 flex items-center justify-center font-bold text-white text-[10px] shadow-2xs shrink-0">
            {authState.name.slice(0, 2).toUpperCase()}
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs font-bold text-stone-900 dark:text-white truncate leading-none">{authState.name}</p>
            <div class="flex items-center gap-1 mt-0.5">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_5px_#10b981]"></span>
              <span class="text-[8.5px] text-indigo-700 dark:text-indigo-400 font-mono tracking-wider uppercase truncate font-bold">
                {authState.roles[0] || 'user'}
              </span>
            </div>
          </div>
        </div>

        <!-- Easy-to-Understand Explicit Logout Button -->
        <button
          id="logout-btn"
          type="button"
          onclick={onlogout}
          disabled={loggingOut}
          title="Keluar dari sesi perangkat ini secara aman"
          class="w-full min-h-[36px] py-1 px-2.5 rounded-lg text-xs font-bold text-rose-700 dark:text-rose-300 hover:text-white bg-rose-50 hover:bg-rose-600 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-900/40 transition flex items-center justify-center gap-1.5 disabled:opacity-50 cursor-pointer shadow-2xs active:scale-95"
        >
          <svg class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          <span class="tracking-wide text-[11px]">{loggingOut ? 'Mengakhiri Sesi...' : 'Keluar Sesi Ini'}</span>
        </button>
      </div>
    {:else}
      <a
        id="nav-login-btn"
        href="/login"
        onclick={onclose}
        class="w-full py-1.5 px-2.5 text-xs font-semibold rounded-lg text-white bg-gradient-to-r from-indigo-600 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 shadow-xs transition flex items-center justify-center gap-1.5 active:scale-95"
      >
        <span>Masuk Sistem</span>
      </a>
    {/if}
  </div>
</aside>
