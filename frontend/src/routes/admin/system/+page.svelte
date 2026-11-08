<script>
  import { onMount, onDestroy } from 'svelte';
  import { goto } from '$app/navigation';
  import gsap from 'gsap';
  import { api } from '$lib/api/client.js';
  import { authState } from '$lib/state/auth.svelte.js';
  import { toastState } from '$lib/state/toast.svelte.js';
  import Badge from '$lib/components/ui/Badge.svelte';
  import Modal from '$lib/components/ui/Modal.svelte';
  import ElementLoader from '$lib/components/ui/ElementLoader.svelte';

  let activeTab = $state('logs'); // 'logs' | 'client' | 'maintenance'
  let loading = $state(true);
  let actionLoading = $state(false);
  let pageContainer = $state(null);
  let gsapCtx;

  $effect(() => {
    if (pageContainer && typeof window !== 'undefined' && !loading) {
      const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      if (prefersReducedMotion) return;

      if (gsapCtx) gsapCtx.revert();
      gsapCtx = gsap.context(() => {
        gsap.from('.gsap-header, .gsap-panel', {
          y: 14,
          opacity: 0,
          duration: 0.4,
          stagger: 0.05,
          ease: 'power2.out',
          clearProps: 'transform,opacity',
        });
      }, pageContainer);
    }
  });

  onDestroy(() => {
    if (gsapCtx) gsapCtx.revert();
  });

  // System status state
  let systemStatus = $state({
    maintenance: { is_active: false, secret: null, bypass_url: null },
    server: { php_version: '', laravel_version: '', server_time: '', disk_free_space: '', memory_usage: '' },
    logs: { laravel_log_size: '0 B', client_log_size: '0 B' }
  });

  // Log state
  let backendLogs = $state([]);
  let clientLogs = $state([]);
  let selectedLevel = $state('ALL');
  let searchQuery = $state('');
  let expandedLogs = $state(new Set());

  // Modals
  let showConfirmMaintenance = $state(false);
  let showConfirmClearLogs = $state(false);
  let clearType = $state('all');

  onMount(async () => {
    authState.syncFromCookies();
    if (!authState.isAuthenticated || !authState.hasRole('admin')) {
      goto('/dashboard');
      return;
    }
    await refreshAll();
  });

  async function refreshAll() {
    loading = true;
    try {
      await Promise.all([
        fetchSystemStatus(),
        fetchBackendLogs(),
        fetchClientLogs()
      ]);
    } finally {
      loading = false;
    }
  }

  async function fetchSystemStatus() {
    try {
      const res = await api.request('/api/v1/admin/system/status');
      if (res) {
        systemStatus = res;
      }
    } catch (err) {
      toastState.error(err.message || 'Gagal memuat status sistem.');
    }
  }

  async function fetchBackendLogs() {
    try {
      const params = new URLSearchParams();
      if (selectedLevel !== 'ALL') params.append('level', selectedLevel);
      if (searchQuery) params.append('search', searchQuery);
      const res = await api.request(`/api/v1/admin/system/logs?${params.toString()}`);
      backendLogs = res?.data || [];
    } catch (err) {
      toastState.error(err.message || 'Gagal memuat log backend.');
    }
  }

  async function fetchClientLogs() {
    try {
      const params = new URLSearchParams();
      if (searchQuery) params.append('search', searchQuery);
      const res = await api.request(`/api/v1/admin/system/logs/client?${params.toString()}`);
      clientLogs = res?.data || [];
    } catch (err) {
      toastState.error(err.message || 'Gagal memuat log klien.');
    }
  }

  async function handleToggleMaintenance() {
    actionLoading = true;
    try {
      const nextAction = systemStatus.maintenance.is_active ? 'disable' : 'enable';
      const res = await api.request('/api/v1/admin/system/maintenance/toggle', {
        method: 'POST',
        body: JSON.stringify({ action: nextAction })
      });
      toastState.success(res.message);
      showConfirmMaintenance = false;
      await fetchSystemStatus();
    } catch (err) {
      toastState.error(err.message || 'Gagal mengubah status maintenance.');
    } finally {
      actionLoading = false;
    }
  }

  async function handleClearLogs() {
    actionLoading = true;
    try {
      const res = await api.request('/api/v1/admin/system/logs', {
        method: 'DELETE',
        body: JSON.stringify({ type: clearType })
      });
      toastState.success(res.message);
      showConfirmClearLogs = false;
      await refreshAll();
    } catch (err) {
      toastState.error(err.message || 'Gagal membersihkan log.');
    } finally {
      actionLoading = false;
    }
  }

  function toggleExpand(index) {
    if (expandedLogs.has(index)) {
      expandedLogs.delete(index);
    } else {
      expandedLogs.add(index);
    }
    expandedLogs = new Set(expandedLogs);
  }

  function getLevelBadgeVariant(level) {
    switch (level?.toUpperCase()) {
      case 'EMERGENCY':
      case 'ALERT':
      case 'CRITICAL':
      case 'ERROR':
        return 'danger';
      case 'WARNING':
        return 'warning';
      case 'NOTICE':
      case 'INFO':
        return 'info';
      case 'DEBUG':
        return 'neutral';
      default:
        return 'neutral';
    }
  }
</script>

<svelte:head>
  <title>Pemantauan Sistem & Log — Hyperion Vault</title>
  <meta name="robots" content="noindex, nofollow" />
</svelte:head>

<div bind:this={pageContainer} class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
  <!-- Header (Apple Spatial Glassmorphism & Specular Chamfer) -->
  <div class="gsap-header p-4 sm:p-5 rounded-2xl bg-white/75 dark:bg-slate-900/60 backdrop-blur-xl border border-white/50 dark:border-white/10 shadow-sm relative overflow-hidden before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/50 dark:before:via-white/20 before:to-transparent bg-gradient-to-br from-indigo-500/[0.04] via-transparent to-cyan-500/[0.03] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
        <span>Sistem & Audit Log</span>
        <Badge variant={systemStatus.maintenance.is_active ? 'danger' : 'success'}>
          {systemStatus.maintenance.is_active ? 'MAINTENANCE AKTIF' : 'SISTEM NORMAL'}
        </Badge>
      </h1>
      <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
        Pemantauan kesehatan server, kontrol mode maintenance, dan inspeksi riwayat error backend & frontend.
      </p>
    </div>

    <div class="flex items-center gap-2.5">
      <button
        type="button"
        onclick={refreshAll}
        disabled={loading}
        class="text-xs px-3.5 py-2 rounded-lg bg-white/80 hover:bg-white dark:bg-slate-800/80 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold border border-white/50 dark:border-white/10 transition flex items-center gap-1.5 shadow-xs cursor-pointer disabled:opacity-50 active:scale-95"
      >
        <svg class="w-3.5 h-3.5 {loading ? 'animate-spin' : ''}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <span>Segarkan</span>
      </button>

      <button
        type="button"
        onclick={() => { clearType = 'all'; showConfirmClearLogs = true; }}
        class="text-xs px-3.5 py-2 rounded-lg bg-rose-50/80 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/50 text-rose-700 dark:text-rose-400 font-semibold border border-rose-200/80 dark:border-rose-800/40 transition flex items-center gap-1.5 shadow-xs cursor-pointer active:scale-95"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
        <span>Bersihkan Log</span>
      </button>
    </div>
  </div>

  <!-- Navigation Tabs: Unobstructed & Responsive (Apple Spatial Glass) -->
  <div class="gsap-panel grid grid-cols-3 gap-1 p-1 bg-white/70 dark:bg-slate-900/60 backdrop-blur-xl rounded-xl border border-white/50 dark:border-white/10 shadow-2xs">
    <button
      type="button"
      onclick={() => { activeTab = 'logs'; fetchBackendLogs(); }}
      class="px-2.5 sm:px-4 py-2 sm:py-2.5 text-xs font-semibold rounded-lg transition-all flex items-center justify-center sm:justify-start gap-1.5 sm:gap-2 {activeTab === 'logs' ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'}"
      aria-label="Tab Log Backend Laravel"
    >
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <span class="truncate">
        <span class="sm:hidden">Backend</span>
        <span class="hidden sm:inline">Log Backend (Laravel)</span>
      </span>
      <span class="hidden sm:inline text-[10px] px-1.5 py-0.5 rounded-full bg-slate-200 dark:bg-slate-700 font-mono shrink-0">{systemStatus.logs.laravel_log_size}</span>
    </button>

    <button
      type="button"
      onclick={() => { activeTab = 'client'; fetchClientLogs(); }}
      class="px-2.5 sm:px-4 py-2 sm:py-2.5 text-xs font-semibold rounded-lg transition-all flex items-center justify-center sm:justify-start gap-1.5 sm:gap-2 {activeTab === 'client' ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'}"
      aria-label="Tab Error Klien Frontend"
    >
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
      </svg>
      <span class="truncate">
        <span class="sm:hidden">Klien</span>
        <span class="hidden sm:inline">Error Klien (Frontend JS)</span>
      </span>
      <span class="hidden sm:inline text-[10px] px-1.5 py-0.5 rounded-full bg-slate-200 dark:bg-slate-700 font-mono shrink-0">{systemStatus.logs.client_log_size}</span>
    </button>

    <button
      type="button"
      onclick={() => { activeTab = 'maintenance'; fetchSystemStatus(); }}
      class="px-2.5 sm:px-4 py-2 sm:py-2.5 text-xs font-semibold rounded-lg transition-all flex items-center justify-center sm:justify-start gap-1.5 sm:gap-2 {activeTab === 'maintenance' ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'}"
      aria-label="Tab Status Server & Maintenance"
    >
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
      <span class="truncate">
        <span class="sm:hidden">Server</span>
        <span class="hidden sm:inline">Status Server & Maintenance</span>
      </span>
    </button>
  </div>

  <!-- TAB 1: BACKEND LOGS -->
  {#if activeTab === 'logs'}
    <div class="space-y-4">
      <!-- Filter Bar -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
        <div class="flex flex-wrap items-center gap-2">
          <span class="text-xs font-medium text-slate-500">Tingkat:</span>
          {#each ['ALL', 'ERROR', 'WARNING', 'INFO', 'DEBUG'] as lvl}
            <button
              type="button"
              onclick={() => { selectedLevel = lvl; fetchBackendLogs(); }}
              class="px-2.5 py-1 text-xs rounded-lg font-medium transition cursor-pointer {selectedLevel === lvl ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'}"
            >
              {lvl}
            </button>
          {/each}
        </div>

        <div class="relative flex-1 max-w-sm">
          <input
            type="text"
            bind:value={searchQuery}
            onkeydown={(e) => e.key === 'Enter' && fetchBackendLogs()}
            placeholder="Cari pesan error atau Trace ID..."
            class="w-full text-xs pl-8 pr-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
          <svg class="w-3.5 h-3.5 absolute left-2.5 top-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>

      <!-- Log Entries -->
      {#if loading}
        <div class="py-12 flex justify-center"><ElementLoader /></div>
      {:else if backendLogs.length === 0}
        <div class="text-center py-12 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800">
          <p class="text-xs text-slate-500">Tidak ada catatan log ditemukan untuk filter ini.</p>
        </div>
      {:else}
        <div class="space-y-2">
          {#each backendLogs as log, i}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden text-xs">
              <button
                type="button"
                onclick={() => toggleExpand(i)}
                class="w-full text-left p-3 flex items-start gap-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition cursor-pointer"
              >
                <Badge variant={getLevelBadgeVariant(log.level)}>{log.level}</Badge>
                <div class="flex-1 min-w-0">
                  <div class="flex flex-wrap items-center gap-2 text-slate-400 text-[11px] font-mono">
                    <span>{log.timestamp}</span>
                    {#if log.trace_id}
                      <span class="px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/40">
                        Trace: {log.trace_id}
                      </span>
                    {/if}
                  </div>
                  <div class="text-slate-800 dark:text-slate-200 font-mono mt-1 break-words font-medium">
                    {log.message}
                  </div>
                </div>
                {#if log.stack}
                  <span class="text-slate-400 text-xs">{expandedLogs.has(i) ? '▲ Tutup' : '▼ Detail'}</span>
                {/if}
              </button>

              {#if expandedLogs.has(i) && log.stack}
                <div class="p-3 bg-slate-950 text-slate-300 font-mono text-[11px] overflow-x-auto border-t border-slate-800 whitespace-pre">
                  {log.stack}
                </div>
              {/if}
            </div>
          {/each}
        </div>
      {/if}
    </div>
  {/if}

  <!-- TAB 2: CLIENT JS LOGS -->
  {#if activeTab === 'client'}
    <div class="space-y-4">
      <div class="flex items-center justify-between bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
        <span class="text-xs text-slate-500">Error antarmuka yang tertangkap otomatis dari peramban pengguna:</span>
        <button
          type="button"
          onclick={() => { clearType = 'client'; showConfirmClearLogs = true; }}
          class="text-xs text-rose-600 dark:text-rose-400 font-medium hover:underline cursor-pointer"
        >
          Bersihkan Log Klien Saja
        </button>
      </div>

      {#if loading}
        <div class="py-12 flex justify-center"><ElementLoader /></div>
      {:else if clientLogs.length === 0}
        <div class="text-center py-12 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800">
          <p class="text-xs text-slate-500">Belum ada error peramban yang tercatat (Client Clean!).</p>
        </div>
      {:else}
        <div class="space-y-2">
          {#each clientLogs as err, i}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-3 text-xs space-y-1.5">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="font-mono text-rose-600 dark:text-rose-400 font-bold">{err.message}</span>
                <span class="text-slate-400 text-[11px] font-mono">{err.timestamp}</span>
              </div>
              <div class="flex flex-wrap items-center gap-3 text-[11px] text-slate-500 font-mono">
                <span>URL: {err.url || '-'}</span>
                <span>Error ID: {err.error_id}</span>
                {#if err.ip}<span>IP: {err.ip}</span>{/if}
              </div>
              {#if err.stack}
                <pre class="p-2 rounded bg-slate-950 text-slate-300 font-mono text-[10px] overflow-x-auto whitespace-pre">{err.stack}</pre>
              {/if}
            </div>
          {/each}
        </div>
      {/if}
    </div>
  {/if}

  <!-- TAB 3: SERVER STATUS & MAINTENANCE -->
  {#if activeTab === 'maintenance'}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Maintenance Card -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <span>Mode Maintenance (Down)</span>
          </h2>
          <Badge variant={systemStatus.maintenance.is_active ? 'danger' : 'success'}>
            {systemStatus.maintenance.is_active ? 'AKTIF' : 'NON-AKTIF'}
          </Badge>
        </div>

        <p class="text-xs text-slate-500 dark:text-slate-400">
          Ketika mode maintenance aktif, seluruh pengunjung umum akan menerima pesan 503 (Layanan Dalam Pemeliharaan). Administrator dapat mengakses sistem menggunakan token rahasia (bypass secret).
        </p>

        {#if systemStatus.maintenance.is_active && systemStatus.maintenance.bypass_url}
          <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/40 space-y-1">
            <span class="text-[11px] font-bold text-amber-800 dark:text-amber-400">Tautan Akses Rahasia Administrator (Bypass):</span>
            <div class="font-mono text-xs text-slate-800 dark:text-slate-200 break-all select-all">
              {systemStatus.maintenance.bypass_url}
            </div>
          </div>
        {/if}

        <div class="pt-2">
          <button
            type="button"
            onclick={() => { showConfirmMaintenance = true; }}
            disabled={actionLoading}
            class="w-full text-xs py-2.5 px-4 rounded-xl font-semibold transition cursor-pointer {systemStatus.maintenance.is_active ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : 'bg-rose-600 hover:bg-rose-500 text-white'}"
          >
            {systemStatus.maintenance.is_active ? 'Matikan Mode Maintenance (Pulihkan Sistem)' : 'Aktifkan Mode Maintenance'}
          </button>
        </div>
      </div>

      <!-- Server Metrics Card -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 space-y-4">
        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Metrik Lingkungan Server</h2>

        <div class="grid grid-cols-2 gap-3 text-xs">
          <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
            <span class="text-slate-400 text-[10px] block">Versi PHP:</span>
            <span class="font-mono font-bold text-slate-900 dark:text-white">{systemStatus.server.php_version || '-'}</span>
          </div>
          <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
            <span class="text-slate-400 text-[10px] block">Versi Framework:</span>
            <span class="font-mono font-bold text-slate-900 dark:text-white">Laravel {systemStatus.server.laravel_version || '-'}</span>
          </div>
          <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
            <span class="text-slate-400 text-[10px] block">Beban Memori:</span>
            <span class="font-mono font-bold text-slate-900 dark:text-white">{systemStatus.server.memory_usage} / {systemStatus.server.memory_limit}</span>
          </div>
          <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
            <span class="text-slate-400 text-[10px] block">Kapasitas Disk Bebas:</span>
            <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{systemStatus.server.disk_free_space}</span>
          </div>
        </div>

        {#if systemStatus.database}
          <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-slate-400 text-[10px] block">Database Engine & Environment:</span>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase font-mono {systemStatus.database.is_production ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'}">
                {systemStatus.database.is_production ? 'PRODUKSI' : 'LOCAL'} ({systemStatus.database.default_driver?.toUpperCase() || 'SQL'})
              </span>
            </div>
            {#if systemStatus.database.sqlite}
              <div class="grid grid-cols-2 gap-2 text-[11px] font-mono text-slate-700 dark:text-slate-300">
                <div>Journal: <strong class="text-indigo-600 dark:text-indigo-400">{systemStatus.database.sqlite.journal_mode}</strong></div>
                <div>Sync: <strong class="text-indigo-600 dark:text-indigo-400">{systemStatus.database.sqlite.synchronous}</strong></div>
                <div>Ukuran File: <strong>{systemStatus.database.sqlite.db_size}</strong></div>
                <div>Busy Timeout: <strong>{systemStatus.database.sqlite.busy_timeout}</strong></div>
              </div>
            {:else if systemStatus.database.mysql}
              <div class="grid grid-cols-2 gap-2 text-[11px] font-mono text-slate-700 dark:text-slate-300">
                <div>Engine: <strong class="text-indigo-600 dark:text-indigo-400">{systemStatus.database.mysql.engine}</strong></div>
                <div>Versi: <strong>{systemStatus.database.mysql.version}</strong></div>
                <div>Pool Ceiling: <strong class="text-emerald-600 dark:text-emerald-400">{systemStatus.database.mysql.connection_pool_max} Koneksi</strong></div>
                <div>Status: <strong class="text-emerald-600 dark:text-emerald-400">{systemStatus.database.mysql.tls_status}</strong></div>
              </div>
            {/if}
          </div>
        {/if}

        <div class="text-[11px] text-slate-500 font-mono">
          Waktu Server: {systemStatus.server.server_time} ({systemStatus.server.timezone})
        </div>
      </div>
    </div>
  {/if}
</div>

<!-- Modal Konfirmasi Maintenance -->
<Modal
  show={showConfirmMaintenance}
  title={systemStatus.maintenance.is_active ? 'Nonaktifkan Mode Maintenance?' : 'Aktifkan Mode Maintenance?'}
  onclose={() => { showConfirmMaintenance = false; }}
>
  <div class="space-y-3 text-xs text-slate-600 dark:text-slate-300">
    {#if systemStatus.maintenance.is_active}
      <p>Apakah Anda yakin ingin memulihkan sistem? Akses pengguna umum akan dibuka kembali.</p>
    {:else}
      <p class="text-rose-600 dark:text-rose-400 font-semibold">
        PERINGATAN: Seluruh pengunjung umum akan terhalang dan melihat layar 503 Service Unavailable. Anda akan diberikan token rahasia untuk tetap mengakses sistem.
      </p>
    {/if}
  </div>

  <div class="mt-5 flex justify-end gap-2.5">
    <button
      type="button"
      onclick={() => { showConfirmMaintenance = false; }}
      aria-label="Batalkan aksi"
      class="min-h-[44px] px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
    >
      ✕ Batal
    </button>
    <button
      type="button"
      onclick={handleToggleMaintenance}
      disabled={actionLoading}
      aria-label="Konfirmasi pengubahan status mode maintenance"
      class="min-h-[44px] px-5 py-2 rounded-xl text-xs font-bold text-white transition cursor-pointer active:scale-[0.98] shadow-md {systemStatus.maintenance.is_active ? 'bg-emerald-600 hover:bg-emerald-500 shadow-emerald-600/30' : 'bg-rose-600 hover:bg-rose-500 shadow-rose-600/30'}"
    >
      {actionLoading ? 'Memproses...' : 'Ya, Lanjutkan'}
    </button>
  </div>
</Modal>

<!-- Modal Konfirmasi Clear Logs -->
<Modal
  show={showConfirmClearLogs}
  title="Bersihkan Berkas Log?"
  onclose={() => { showConfirmClearLogs = false; }}
>
  <div class="space-y-2 text-xs text-slate-600 dark:text-slate-300">
    <p>Tindakan ini akan mengosongkan berkas log ({clearType === 'all' ? 'Backend & Frontend' : clearType}). Data yang telah dihapus tidak dapat dipulihkan.</p>
  </div>

  <div class="mt-5 flex justify-end gap-2.5">
    <button
      type="button"
      onclick={() => { showConfirmClearLogs = false; }}
      aria-label="Batalkan pembersihan log"
      class="min-h-[44px] px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
    >
      ✕ Batal
    </button>
    <button
      type="button"
      onclick={handleClearLogs}
      disabled={actionLoading}
      aria-label="Konfirmasi bersihkan berkas log"
      class="min-h-[44px] px-5 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white transition cursor-pointer active:scale-[0.98] shadow-md shadow-rose-600/30"
    >
      {actionLoading ? 'Membersihkan...' : 'Ya, Bersihkan Log'}
    </button>
  </div>
</Modal>
