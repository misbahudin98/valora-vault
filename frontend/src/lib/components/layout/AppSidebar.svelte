<script>
  import { page } from '$app/state';
  import { authState } from '$lib/state/auth.svelte.js';
  import { getVisibleNavItems } from '$lib/config/navigation.js';
  import { api } from '$lib/api/client.js';
  import { onMount } from 'svelte';

  let {
    isOpen = false,
    onclose = () => {}
  } = $props();

  // Dynamic visible navigation links
  let visibleNavs = $derived(getVisibleNavItems(authState));

  // Collapsible tree branches state
  let expandedTrees = $state({});

  // Active In-Progress Projects Mini Dashboard State (Valora Agency OS)
  let activeProjectsState = $state({
    loading: true,
    error: null,
    projects: [],
    currentIndex: 0
  });

  // Calculate project module progress percentage & done stats helper
  function computeProjectProgress(project) {
    let total = 0;
    let done = 0;
    (project?.roles || []).forEach(r => {
      (r.menus || []).forEach(m => {
        total++;
        if (m.is_done) done++;
      });
    });
    const percent = total > 0 ? Math.round((done / total) * 100) : 0;
    return { percent, total, done };
  }

  async function loadActiveProjects() {
    if (!authState.isAuthenticated || !authState.hasRole('admin')) {
      activeProjectsState.loading = false;
      return;
    }
    activeProjectsState.loading = true;
    activeProjectsState.error = null;
    try {
      const res = await api.request('/api/v1/admin/clients?status=Aktif&limit=50');
      const list = Array.isArray(res?.data) ? res.data : [];
      activeProjectsState.projects = list.map(p => {
        const prog = computeProjectProgress(p);
        const grandTotal = Number(p.grand_total || 0);
        const paidAmount = Number(p.paid_amount || 0);
        const remaining = Math.max(0, grandTotal - paidAmount);
        return {
          ...p,
          _progress: prog,
          _grandTotal: grandTotal,
          _paidAmount: paidAmount,
          _remaining: remaining
        };
      });
      if (activeProjectsState.currentIndex >= activeProjectsState.projects.length) {
        activeProjectsState.currentIndex = 0;
      }
    } catch (err) {
      activeProjectsState.error = err?.message || 'Gagal memuat proyek yang sedang dikerjakan.';
    } finally {
      activeProjectsState.loading = false;
    }
  }

  onMount(() => {
    loadActiveProjects();
  });

  function prevProject() {
    if (activeProjectsState.projects.length <= 1) return;
    if (activeProjectsState.currentIndex > 0) {
      activeProjectsState.currentIndex--;
    } else {
      activeProjectsState.currentIndex = activeProjectsState.projects.length - 1;
    }
  }

  function nextProject() {
    if (activeProjectsState.projects.length <= 1) return;
    if (activeProjectsState.currentIndex < activeProjectsState.projects.length - 1) {
      activeProjectsState.currentIndex++;
    } else {
      activeProjectsState.currentIndex = 0;
    }
  }

  let currentProject = $derived(
    activeProjectsState.projects[activeProjectsState.currentIndex] || null
  );

  function formatShortRupiah(num) {
    if (!num) return 'Rp 0';
    if (num >= 1000000000) {
      return 'Rp ' + (num / 1000000000).toFixed(1).replace('.0', '') + ' M';
    }
    if (num >= 1000000) {
      return 'Rp ' + (num / 1000000).toFixed(1).replace('.0', '') + ' Jt';
    }
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(num);
  }

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
    {:else if iconName === 'folder'}
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
      </svg>
    {:else if iconName === 'activity'}
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
      </svg>
    {:else if iconName === 'receipt'}
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8h4" />
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
  class="print-hide fixed inset-y-0 left-0 z-50 w-60 bg-white/85 dark:bg-[#090d16]/85 border-r border-stone-200/80 dark:border-white/10 backdrop-blur-2xl before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/30 dark:before:via-white/15 before:to-transparent flex flex-col transition-all duration-200 ease-in-out lg:translate-x-0 {isOpen ? 'translate-x-0' : '-translate-x-full'} shadow-2xl lg:shadow-none"
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

  <!-- Bottom: Dashboard Mini Khusus Proyek yang Sedang Dikerjakan -->
  <div class="p-2 border-t border-stone-200/80 dark:border-white/10 bg-stone-50/70 dark:bg-[#090d16]/90 backdrop-blur-md">
    {#if authState.isAuthenticated}
      <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900/90 border border-stone-200/80 dark:border-slate-800 shadow-2xs space-y-2">
        <!-- Header: Label & Pager Controls -->
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-1.5 min-w-0">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_6px_#10b981] animate-pulse shrink-0"></span>
            <span class="text-[9.5px] font-bold tracking-wider uppercase text-stone-700 dark:text-slate-300 font-mono truncate">Proyek Dikerjakan</span>
          </div>

          <div class="flex items-center gap-1 shrink-0">
            {#if activeProjectsState.projects.length > 1}
              <!-- Carousel Switcher Controls -->
              <div class="flex items-center gap-0.5 bg-stone-100 dark:bg-slate-800/80 rounded-md px-1 py-0.5 border border-stone-200/60 dark:border-slate-700/60">
                <button
                  type="button"
                  onclick={prevProject}
                  class="w-4 h-4 rounded flex items-center justify-center text-stone-600 hover:text-stone-900 dark:text-slate-300 dark:hover:text-white transition cursor-pointer"
                  title="Proyek Sebelumnya"
                  aria-label="Proyek Sebelumnya"
                >
                  <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                  </svg>
                </button>
                <span class="text-[8.5px] font-mono font-bold text-stone-700 dark:text-slate-300 px-0.5">
                  {activeProjectsState.currentIndex + 1}/{activeProjectsState.projects.length}
                </span>
                <button
                  type="button"
                  onclick={nextProject}
                  class="w-4 h-4 rounded flex items-center justify-center text-stone-600 hover:text-stone-900 dark:text-slate-300 dark:hover:text-white transition cursor-pointer"
                  title="Proyek Berikutnya"
                  aria-label="Proyek Berikutnya"
                >
                  <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              </div>
            {/if}

            <button
              type="button"
              onclick={loadActiveProjects}
              disabled={activeProjectsState.loading}
              class="w-4.5 h-4.5 rounded flex items-center justify-center text-stone-400 hover:text-stone-700 dark:hover:text-white hover:bg-stone-100 dark:hover:bg-slate-800 transition cursor-pointer disabled:opacity-40"
              title="Segarkan data proyek aktif"
              aria-label="Segarkan"
            >
              <svg class="w-3 h-3 {activeProjectsState.loading ? 'animate-spin text-indigo-500' : ''}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </button>
          </div>
        </div>

        {#if activeProjectsState.loading}
          <!-- Loading State (Shimmer Skeleton) -->
          <div class="space-y-1.5 py-1 animate-pulse">
            <div class="h-3 w-4/5 bg-stone-200 dark:bg-slate-800 rounded"></div>
            <div class="h-2 w-1/2 bg-stone-200/70 dark:bg-slate-800/70 rounded"></div>
            <div class="h-1.5 w-full bg-stone-200 dark:bg-slate-800 rounded-full mt-2"></div>
            <div class="grid grid-cols-2 gap-1.5 pt-1">
              <div class="h-5 bg-stone-200 dark:bg-slate-800 rounded-lg"></div>
              <div class="h-5 bg-stone-200 dark:bg-slate-800 rounded-lg"></div>
            </div>
          </div>
        {:else if activeProjectsState.error}
          <!-- Error State -->
          <div class="p-2 rounded-lg bg-rose-50/60 dark:bg-rose-950/30 border border-rose-200/60 dark:border-rose-900/40 text-center space-y-1">
            <p class="text-[10px] text-rose-600 dark:text-rose-400 font-medium leading-tight">
              {activeProjectsState.error}
            </p>
            <button
              type="button"
              onclick={loadActiveProjects}
              class="text-[9px] font-bold text-rose-700 dark:text-rose-300 hover:underline cursor-pointer"
            >
              Coba Lagi ↻
            </button>
          </div>
        {:else if activeProjectsState.projects.length === 0}
          <!-- Empty State -->
          <div class="p-2.5 rounded-lg bg-stone-50 dark:bg-slate-950/60 border border-stone-200/60 dark:border-slate-800/60 text-center space-y-1.5">
            <p class="text-[10px] text-stone-500 dark:text-slate-400 leading-tight">
              Tidak ada proyek yang sedang dikerjakan.
            </p>
            <a
              href="/admin/clients"
              onclick={onclose}
              class="inline-flex items-center gap-1 text-[10px] font-bold text-indigo-600 dark:text-cyan-400 hover:underline"
            >
              + Buat Proyek Baru
            </a>
          </div>
        {:else if currentProject}
          <!-- Populated Active Project Card -->
          <div class="space-y-2">
            <!-- Project Name & Type Badge -->
            <div>
              <div class="flex items-center justify-between gap-1.5">
                <a
                  href="/admin/progres"
                  onclick={onclose}
                  class="text-xs font-bold text-stone-900 dark:text-white truncate tracking-tight hover:text-indigo-600 dark:hover:text-cyan-400 transition"
                  title={currentProject.app_name}
                >
                  {currentProject.app_name}
                </a>
                <span class="shrink-0 px-1 py-0.2 rounded text-[8.5px] font-bold uppercase tracking-wider {currentProject.project_type === 'maintenance' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20'}">
                  {currentProject.project_type === 'maintenance' ? 'SLA' : 'Dev'}
                </span>
              </div>
              <p
                class="text-[10px] text-stone-500 dark:text-slate-400 truncate mt-0.5"
                title={currentProject.client?.name || 'Klien Umum'}
              >
                {currentProject.client?.name || 'Klien Umum'}
              </p>
            </div>

            <!-- Few/Tufte Bullet Progress Bar -->
            <div class="space-y-1">
              <div class="flex items-center justify-between text-[9px]">
                <span class="text-stone-500 dark:text-slate-400 font-medium">
                  {#if currentProject._progress.total > 0}
                    {currentProject._progress.done}/{currentProject._progress.total} Modul Selesai
                  {:else}
                    Inisiasi Modul
                  {/if}
                </span>
                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">
                  {currentProject._progress.percent}%
                </span>
              </div>
              <div class="h-1.5 w-full bg-stone-100 dark:bg-slate-800 rounded-full overflow-hidden p-0.25 border border-stone-200/50 dark:border-slate-800/80">
                <div
                  class="h-full rounded-full transition-all duration-300 bg-gradient-to-r from-emerald-500 to-cyan-500"
                  style="width: {currentProject._progress.percent}%"
                ></div>
              </div>
            </div>

            <!-- Mini Operasional & Tagihan Grid -->
            <div class="grid grid-cols-2 gap-1.5 pt-0.5">
              <div class="px-2 py-1 rounded-lg bg-stone-50 dark:bg-slate-950/70 border border-stone-200/60 dark:border-slate-800/60 min-w-0">
                <div class="text-[8.5px] text-stone-500 dark:text-slate-400 font-medium uppercase tracking-wider">PIC Tim</div>
                <div
                  class="text-[10px] font-semibold text-stone-800 dark:text-slate-200 truncate mt-0.5"
                  title={currentProject.lead_pic || currentProject.leadPicUser?.name || 'Tim Valora'}
                >
                  {currentProject.lead_pic || currentProject.leadPicUser?.name || 'Tim Valora'}
                </div>
              </div>

              <div class="px-2 py-1 rounded-lg bg-stone-50 dark:bg-slate-950/70 border border-stone-200/60 dark:border-slate-800/60 min-w-0">
                <div class="text-[8.5px] text-stone-500 dark:text-slate-400 font-medium uppercase tracking-wider">Tagihan</div>
                <div class="text-[10px] font-mono font-bold truncate mt-0.5">
                  {#if currentProject._remaining <= 0 && currentProject._grandTotal > 0}
                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">Lunas</span>
                  {:else if currentProject._paidAmount > 0}
                    <span class="text-indigo-600 dark:text-cyan-400" title="Sisa {formatShortRupiah(currentProject._remaining)}">
                      Sisa {formatShortRupiah(currentProject._remaining)}
                    </span>
                  {:else}
                    <span class="text-rose-500 dark:text-rose-400">Belum DP</span>
                  {/if}
                </div>
              </div>
            </div>

            <!-- Action Quick Links (Micro Action Bar) -->
            <div class="pt-1 flex items-center justify-between border-t border-stone-100 dark:border-slate-800/60 text-[10px]">
              <a
                href="/admin/progres"
                onclick={onclose}
                class="inline-flex items-center gap-1 font-semibold text-indigo-600 hover:text-indigo-700 dark:text-cyan-400 dark:hover:text-cyan-300 hover:underline"
                title="Buka Monitoring Progres Modul"
              >
                <span>Progres</span>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
              </a>

              <div class="flex items-center gap-2">
                {#if currentProject.github_url}
                  <a
                    href={currentProject.github_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-stone-400 hover:text-stone-700 dark:hover:text-white transition p-0.5"
                    title="Buka Github Repository"
                  >
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" />
                    </svg>
                  </a>
                {/if}

                <a
                  href="/admin/clients"
                  onclick={onclose}
                  class="font-semibold text-stone-500 hover:text-stone-800 dark:text-slate-400 dark:hover:text-slate-200 hover:underline"
                  title="Buka Manajemen Proyek & Klien"
                >
                  Scope
                </a>
              </div>
            </div>
          </div>
        {/if}
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
