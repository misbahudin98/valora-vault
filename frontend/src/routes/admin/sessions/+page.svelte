<script>
  import { onMount, onDestroy } from 'svelte';
  import { goto } from '$app/navigation';
  import gsap from 'gsap';
  import { api } from '$lib/api/client.js';
  import { authState } from '$lib/state/auth.svelte.js';
  import { toastState } from '$lib/state/toast.svelte.js';
  import DataTable from '$lib/components/ui/DataTable.svelte';
  import Badge from '$lib/components/ui/Badge.svelte';

  let sessions = $state([]);
  let meta = $state({ total_tracked: 0, active_users: 0, max_devices_cap: 3, disk_sessions_count: 0 });
  let loading = $state(true);
  let actionLoading = $state({});
  let pageContainer = $state(null);
  let gsapCtx;

  $effect(() => {
    if (pageContainer && typeof window !== 'undefined' && !loading) {
      const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      if (prefersReducedMotion) return;

      if (gsapCtx) gsapCtx.revert();
      gsapCtx = gsap.context(() => {
        gsap.from('.gsap-card', {
          y: 16,
          opacity: 0,
          duration: 0.42,
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

  // Client-side hard route protection
  onMount(async () => {
    authState.syncFromCookies();
    if (!authState.isAuthenticated) {
      goto('/login');
      return;
    }
    if (!authState.hasRole('admin')) {
      goto('/dashboard');
      return;
    }
    await loadSessions();
  });

  async function loadSessions() {
    loading = true;
    try {
      const res = await api.request('/api/v1/admin/sessions');
      sessions = res.data || [];
      meta = res.meta || { total_tracked: sessions.length, active_users: 0, max_devices_cap: 3, disk_sessions_count: 0 };
    } catch (err) {
      toastState.error(err.message || 'Gagal memuat daftar sesi aktif.');
      if (err.status === 403) {
        goto('/dashboard');
      }
    } finally {
      loading = false;
    }
  }

  async function handleRevokeSingleSession(session) {
    const isCurrent = session.is_current;
    const confirmPrompt = isCurrent
      ? 'PERINGATAN: Ini adalah sesi browser Anda saat ini. Jika dicabut, Anda akan langsung logout. Lanjutkan?'
      : `Cabut sesi perangkat "${session.device_name}" (${session.ip_address}) untuk ${session.user_name}?`;

    if (!confirm(confirmPrompt)) {
      return;
    }

    actionLoading[`dev_${session.id}`] = true;

    try {
      const res = await api.request(`/api/v1/admin/sessions/${session.id}/revoke`, {
        method: 'POST'
      });
      toastState.success(res.message);
      if (isCurrent) {
        authState.clear();
        goto('/login');
        return;
      }
      // Optimasi Render In-Memory (Zero Network Re-Fetch)
      sessions = sessions.filter(s => s.id !== session.id);
      meta = { ...meta, total_tracked: Math.max(0, meta.total_tracked - 1) };
    } catch (err) {
      toastState.error(err.message || 'Gagal mencabut sesi perangkat.');
    } finally {
      actionLoading[`dev_${session.id}`] = false;
    }
  }

  async function handleRevokeUserSessions(session) {
    if (!confirm(`Cabut seluruh sesi login untuk ${session.user_name} di semua perangkat?`)) {
      return;
    }

    actionLoading[`user_${session.user_id}`] = true;

    try {
      const res = await api.request(`/api/v1/admin/sessions/user/${session.user_id}/revoke`, {
        method: 'POST'
      });
      toastState.success(res.message);
      // Optimasi Render In-Memory (Zero Network Re-Fetch)
      sessions = sessions.filter(s => s.user_id !== session.user_id);
      meta = { ...meta, total_tracked: sessions.length };
    } catch (err) {
      toastState.error(err.message || 'Gagal mencabut sesi pengguna.');
    } finally {
      actionLoading[`user_${session.user_id}`] = false;
    }
  }

  async function handleCleanupSessions() {
    actionLoading['cleanup'] = true;

    try {
      const res = await api.request('/api/v1/admin/sessions/cleanup', {
        method: 'POST'
      });
      toastState.success(res.message);
      await loadSessions();
    } catch (err) {
      toastState.error(err.message || 'Gagal menjalankan pembersihan sesi.');
    } finally {
      actionLoading['cleanup'] = false;
    }
  }

  async function handleRevokeAllSessions() {
    if (!confirm('PERINGATAN DARURAT: Cabut seluruh sesi aktif untuk SEMUA perangkat pengguna di sistem (kecuali sesi browser Anda saat ini)?')) {
      return;
    }

    actionLoading['all'] = true;

    try {
      const res = await api.request('/api/v1/admin/sessions/revoke-all', {
        method: 'POST'
      });
      toastState.success(res.message);
      await loadSessions();
    } catch (err) {
      toastState.error(err.message || 'Gagal mencabut seluruh sesi.');
    } finally {
      actionLoading['all'] = false;
    }
  }

  function formatTime(isoStr) {
    if (!isoStr) return '-';
    try {
      const d = new Date(isoStr);
      return d.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    } catch {
      return isoStr;
    }
  }
</script>

<svelte:head>
  <title>Manajemen Sesi & Perangkat — Hyperion Vault</title>
  <meta name="robots" content="noindex, nofollow" />
</svelte:head>

{#if authState.isAuthenticated && authState.hasRole('admin')}
<div bind:this={pageContainer} class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
  <!-- Header Card (Apple Spatial Glassmorphism & Specular Chamfer) -->
  <div class="gsap-card p-4 sm:p-5 rounded-2xl bg-white/75 dark:bg-slate-900/60 backdrop-blur-xl border border-white/50 dark:border-white/10 shadow-sm relative overflow-hidden before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/50 dark:before:via-white/20 before:to-transparent bg-gradient-to-br from-cyan-500/[0.04] via-transparent to-indigo-500/[0.03] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
        <span>Manajemen Sesi & Perangkat Aktif</span>
        <span class="text-xs font-mono font-normal px-2.5 py-0.5 rounded-full bg-cyan-50/80 dark:bg-cyan-950/60 text-cyan-700 dark:text-cyan-400 border border-cyan-200/80 dark:border-cyan-800/40">SecOps</span>
      </h1>
      <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
        Audit perangkat login multi-device, pemantauan IP Address, User-Agent, waktu kadaluarsa, dan pencegahan penumpukan file sesi.
      </p>
    </div>

    <!-- Actions & Stats -->
    <div class="flex flex-wrap items-center gap-2.5">
      <!-- Auto Cleanup Button -->
      <button
        type="button"
        onclick={handleCleanupSessions}
        disabled={actionLoading['cleanup']}
        class="text-xs px-3.5 py-2 rounded-lg bg-white/80 hover:bg-white dark:bg-slate-800/80 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold border border-white/50 dark:border-white/10 transition disabled:opacity-50 flex items-center gap-1.5 shadow-xs cursor-pointer active:scale-95"
        title="Bersihkan token dan sesi yang sudah kadaluarsa atau tidak valid"
      >
        <svg class="w-3.5 h-3.5 {actionLoading['cleanup'] ? 'animate-spin' : ''}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
        <span>{actionLoading['cleanup'] ? 'Membersihkan...' : 'Auto-Remove Sesi Kadaluarsa'}</span>
      </button>

      <!-- Emergency Revoke All Other Devices -->
      <button
        type="button"
        onclick={handleRevokeAllSessions}
        disabled={actionLoading['all']}
        class="text-xs px-3.5 py-2 rounded-lg bg-rose-50/80 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/70 dark:hover:bg-rose-900 dark:text-rose-300 font-semibold border border-rose-200/80 dark:border-rose-800/60 shadow-xs transition disabled:opacity-50 flex items-center gap-1.5 cursor-pointer active:scale-95"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span>{actionLoading['all'] ? 'Memproses...' : 'Cabut Semua Sesi Lain'}</span>
      </button>

      <!-- Refresh Button -->
      <button
        type="button"
        onclick={loadSessions}
        disabled={loading}
        class="text-xs px-3 py-2 rounded-lg bg-white/80 hover:bg-white dark:bg-slate-800/80 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold border border-white/50 dark:border-white/10 transition disabled:opacity-50 flex items-center gap-1.5 shadow-xs cursor-pointer active:scale-95"
      >
        <svg class="w-3.5 h-3.5 {loading ? 'animate-spin' : ''}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <span>Refresh</span>
      </button>
    </div>
  </div>

  <!-- Metric Overview Cards (Apple Spatial Glassmorphism & Atmospheric Gradients) -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- Card 1: Total Devices (Cyan Gradient) -->
    <div class="gsap-card p-5 bg-white/80 dark:bg-slate-900/60 backdrop-blur-xl border border-white/50 dark:border-white/10 rounded-2xl shadow-xs dark:shadow-lg relative overflow-hidden group hover:border-cyan-500/40 transition duration-200 before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/50 dark:before:via-white/20 before:to-transparent bg-gradient-to-br from-cyan-500/[0.06] to-transparent active:scale-[0.99]">
      <div class="flex items-center justify-between mb-2">
        <span class="text-slate-500 dark:text-slate-400 text-xs font-medium">Perangkat Aktif Login</span>
        <div class="w-8 h-8 rounded-xl bg-cyan-50 dark:bg-cyan-950/60 border border-cyan-200 dark:border-cyan-800/40 text-cyan-600 dark:text-cyan-400 flex items-center justify-center">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
        </div>
      </div>
      <span class="text-2xl font-extrabold text-slate-900 dark:text-white font-mono tracking-tight">{meta.total_tracked}</span>
      <span class="text-[11px] text-slate-500 dark:text-slate-400 block mt-1">Sesi perangkat fisik terdaftar</span>
    </div>

    <!-- Card 2: Active Users Online (Emerald Gradient) -->
    <div class="gsap-card p-5 bg-white/80 dark:bg-slate-900/60 backdrop-blur-xl border border-white/50 dark:border-white/10 rounded-2xl shadow-xs dark:shadow-lg relative overflow-hidden group hover:border-emerald-500/40 transition duration-200 before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/50 dark:before:via-white/20 before:to-transparent bg-gradient-to-br from-emerald-500/[0.06] to-transparent active:scale-[0.99]">
      <div class="flex items-center justify-between mb-2">
        <span class="text-slate-500 dark:text-slate-400 text-xs font-medium">Pengguna Sedang Online</span>
        <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <span class="text-2xl font-extrabold text-slate-900 dark:text-white font-mono tracking-tight">{meta.active_users}</span>
        <span class="relative flex h-2 w-2">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500 shadow-[0_0_8px_#10b981]"></span>
        </span>
      </div>
      <span class="text-[11px] text-slate-500 dark:text-slate-400 block mt-1">Akun dengan minimal satu sesi aktif</span>
    </div>

    <!-- Card 3: Max Device Cap (Indigo Gradient) -->
    <div class="gsap-card p-5 bg-white/80 dark:bg-slate-900/60 backdrop-blur-xl border border-white/50 dark:border-white/10 rounded-2xl shadow-xs dark:shadow-lg relative overflow-hidden group hover:border-indigo-500/40 transition duration-200 before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/50 dark:before:via-white/20 before:to-transparent bg-gradient-to-br from-indigo-500/[0.06] to-transparent active:scale-[0.99]">
      <div class="flex items-center justify-between mb-2">
        <span class="text-slate-500 dark:text-slate-400 text-xs font-medium">Batas Maksimal Kuota</span>
        <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
        </div>
      </div>
      <span class="text-2xl font-extrabold text-slate-900 dark:text-white font-mono tracking-tight">{meta.max_devices_cap} <span class="text-xs text-indigo-600 dark:text-indigo-400 font-sans font-normal">Per Akun</span></span>
      <span class="text-[11px] text-slate-500 dark:text-slate-400 block mt-1">Plafon konkurensi multi-device aman</span>
    </div>

    <!-- Card 4: Disk Sessions Count (Amber Gradient) -->
    <div class="gsap-card p-5 bg-white/80 dark:bg-slate-900/60 backdrop-blur-xl border border-white/50 dark:border-white/10 rounded-2xl shadow-xs dark:shadow-lg relative overflow-hidden group hover:border-amber-500/40 transition duration-200 before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/50 dark:before:via-white/20 before:to-transparent bg-gradient-to-br from-amber-500/[0.06] to-transparent active:scale-[0.99]">
      <div class="flex items-center justify-between mb-2">
        <span class="text-slate-500 dark:text-slate-400 text-xs font-medium">Berkas Sesi Disk</span>
        <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
          </svg>
        </div>
      </div>
      <span class="text-2xl font-extrabold text-slate-900 dark:text-white font-mono tracking-tight">{meta.disk_sessions_count ?? 0}</span>
      <span class="text-[11px] text-slate-500 dark:text-slate-400 block mt-1">File fisik storage/framework/sessions</span>
    </div>
  </div>

  <!-- Sessions Data Table (Reusable DataTable Component) -->
  <div class="gsap-card">
  <DataTable
    items={sessions}
    pageSize={10}
    searchPlaceholder="Cari berdasarkan nama, email, perangkat, atau IP..."
    searchFields={['user_name', 'user_email', 'device_name', 'ip_address']}
    {loading}
    emptyMessage="Tidak ada sesi perangkat aktif saat ini."
  >
    {#snippet header()}
      <tr>
        <th class="px-5 py-3.5">Pengguna & Akun</th>
        <th class="px-5 py-3.5">Perangkat & Browser</th>
        <th class="px-5 py-3.5">Alamat IP</th>
        <th class="px-5 py-3.5">Aktivitas Terakhir</th>
        <th class="px-5 py-3.5">Masa Berlaku (Expired)</th>
        <th class="px-5 py-3.5 text-right">Tindakan Keamanan</th>
      </tr>
    {/snippet}

    {#snippet row(s)}
      <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition {s.is_current ? 'bg-indigo-50/50 dark:bg-indigo-950/30' : ''}">
        <!-- User Info -->
        <td class="px-5 py-3.5">
          <div class="flex items-center gap-2">
            <span class="font-semibold text-slate-900 dark:text-white text-sm">{s.user_name}</span>
            {#if s.is_current}
              <span class="px-1.5 py-0.5 text-[9px] rounded-full bg-indigo-600 text-white font-mono font-medium">Perangkat Ini</span>
            {/if}
          </div>
          <div class="text-[11px] text-slate-500 dark:text-slate-400 font-mono mt-0.5">{s.user_email}</div>
          <div class="flex flex-wrap gap-1 mt-1">
            {#each (Array.isArray(s.user_role) ? s.user_role : [s.user_role]) as r}
              <Badge type="role" value={r} />
            {/each}
          </div>
        </td>

        <!-- Device & Browser -->
        <td class="px-5 py-3.5">
          <div class="flex items-center gap-2">
            {#if s.device_type === 'mobile'}
              <svg class="w-4 h-4 text-cyan-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
            {:else if s.device_type === 'tablet'}
              <svg class="w-4 h-4 text-cyan-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
            {:else}
              <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            {/if}
            <span class="font-medium text-slate-800 dark:text-slate-200 text-xs">{s.device_name}</span>
          </div>
          <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono block mt-0.5 truncate max-w-xs" title={s.user_agent}>
            {s.platform} • {s.browser}
          </span>
        </td>

        <!-- IP Address -->
        <td class="px-5 py-3.5">
          <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono text-[11px] border border-slate-200 dark:border-slate-700">
            {s.ip_address}
          </span>
        </td>

        <!-- Last Active -->
        <td class="px-5 py-3.5 text-xs text-slate-700 dark:text-slate-300">
          <div>{formatTime(s.last_active_at)}</div>
          <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">Token v{s.token_version ?? 1}</span>
        </td>

        <!-- Expiration -->
        <td class="px-5 py-3.5 text-xs">
          {#if s.is_expired}
            <span class="inline-flex items-center gap-1 text-rose-500 font-medium">
              <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
              Kadaluarsa
            </span>
          {:else}
            <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-medium">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              {formatTime(s.expires_at)}
            </span>
          {/if}
        </td>

        <!-- Actions -->
        <td class="px-5 py-3.5 text-right space-x-1.5 whitespace-nowrap">
          <!-- Revoke Specific Device Session Button -->
          <button
            type="button"
            onclick={() => handleRevokeSingleSession(s)}
            disabled={actionLoading[`dev_${s.id}`]}
            aria-label="Cabut sesi perangkat {s.device_name} untuk {s.user_name}"
            class="text-xs px-2.5 py-1 rounded-lg font-medium bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-800/40 transition cursor-pointer disabled:opacity-40"
            title="Cabut sesi hanya pada perangkat ini"
          >
            {actionLoading[`dev_${s.id}`] ? 'Mencabut...' : 'Cabut Sesi Ini'}
          </button>

          <!-- Revoke User's All Sessions Button -->
          <button
            type="button"
            onclick={() => handleRevokeUserSessions(s)}
            disabled={actionLoading[`user_${s.user_id}`]}
            aria-label="Cabut seluruh sesi login akun {s.user_name}"
            class="text-xs px-2 py-1 rounded-lg font-medium bg-white hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 transition cursor-pointer disabled:opacity-40 shadow-xs"
            title="Cabut seluruh sesi login akun ini di semua perangkat"
          >
            {actionLoading[`user_${s.user_id}`] ? '...' : 'Cabut Akun'}
          </button>
        </td>
      </tr>
    {/snippet}

    {#snippet card(s)}
      <div class="space-y-2.5 {s.is_current ? 'p-2 rounded-lg bg-indigo-50/60 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60' : ''}">
        <!-- Card Header: User & Status Badge -->
        <div class="flex items-start justify-between gap-2">
          <div class="min-w-0">
            <div class="flex items-center gap-1.5">
              <span class="font-bold text-slate-900 dark:text-white text-xs truncate">{s.user_name}</span>
              {#if s.is_current}
                <span class="px-1.5 py-0.5 text-[9px] rounded-full bg-indigo-600 text-white font-mono font-medium shrink-0">Perangkat Ini</span>
              {/if}
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 font-mono truncate mt-0.5">{s.user_email}</div>
          </div>
          <div class="shrink-0">
            {#if s.is_expired}
              <span class="px-2 py-0.5 text-[10px] rounded-md bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50 font-bold">
                Kadaluarsa
              </span>
            {:else}
              <span class="px-2 py-0.5 text-[10px] rounded-md bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50 font-bold">
                Aktif
              </span>
            {/if}
          </div>
        </div>

        <!-- Role Badges -->
        <div class="flex flex-wrap gap-1">
          {#each (Array.isArray(s.user_role) ? s.user_role : [s.user_role]) as r (r)}
            <Badge type="role" value={r} />
          {/each}
        </div>

        <!-- Device & IP Grid -->
        <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-slate-100 dark:border-slate-800/60">
          <div>
            <span class="text-[10px] text-slate-400 dark:text-slate-500 block">Perangkat & Browser:</span>
            <div class="flex items-center gap-1 mt-0.5">
              {#if s.device_type === 'mobile'}
                <svg class="w-3.5 h-3.5 text-cyan-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
              {:else if s.device_type === 'tablet'}
                <svg class="w-3.5 h-3.5 text-cyan-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
              {:else}
                <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
              {/if}
              <span class="font-bold text-slate-800 dark:text-slate-200 text-[11px] truncate">{s.device_name}</span>
            </div>
            <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono block truncate mt-0.5">{s.platform} • {s.browser}</span>
          </div>
          <div>
            <span class="text-[10px] text-slate-400 dark:text-slate-500 block">Alamat IP & Token:</span>
            <span class="font-mono text-slate-800 dark:text-slate-200 text-[11px] font-bold block mt-0.5">{s.ip_address}</span>
            <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono block">Token v{s.token_version ?? 1}</span>
          </div>
        </div>

        <!-- Timestamps -->
        <div class="text-[10.5px] text-slate-500 dark:text-slate-400 flex items-center justify-between pt-1 border-t border-slate-100 dark:border-slate-800/40">
          <span>Aktif: <strong class="text-slate-700 dark:text-slate-300 font-medium">{formatTime(s.last_active_at)}</strong></span>
          <span>Kedaluwarsa: <strong class="text-slate-700 dark:text-slate-300 font-medium">{formatTime(s.expires_at)}</strong></span>
        </div>

        <!-- Mobile Action Buttons (Thumb Zone) -->
        <div class="grid grid-cols-2 gap-1.5 pt-2 border-t border-slate-100 dark:border-slate-800/60">
          <button
            type="button"
            onclick={() => handleRevokeSingleSession(s)}
            disabled={actionLoading[`dev_${s.id}`]}
            aria-label="Cabut sesi perangkat {s.device_name} untuk {s.user_name}"
            class="min-h-[30px] px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-800/40 transition active:scale-95 disabled:opacity-40 cursor-pointer flex items-center justify-center gap-1 shadow-2xs"
            title="Cabut sesi hanya pada perangkat ini"
          >
            <svg class="w-3 h-3 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span>{actionLoading[`dev_${s.id}`] ? 'Mencabut...' : 'Cabut Sesi Ini'}</span>
          </button>

          <button
            type="button"
            onclick={() => handleRevokeUserSessions(s)}
            disabled={actionLoading[`user_${s.user_id}`]}
            aria-label="Cabut seluruh sesi login akun {s.user_name}"
            class="min-h-[30px] px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-white hover:bg-slate-100 dark:bg-slate-800/80 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60 transition active:scale-95 disabled:opacity-40 cursor-pointer flex items-center justify-center gap-1 shadow-2xs"
            title="Cabut seluruh sesi login akun ini di semua perangkat"
          >
            <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span>{actionLoading[`user_${s.user_id}`] ? '...' : 'Cabut Akun'}</span>
          </button>
        </div>
      </div>
    {/snippet}
  </DataTable>
  </div>
</div>
{/if}
