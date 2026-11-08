<script>
  import { onMount, onDestroy } from 'svelte';
  import { goto } from '$app/navigation';
  import gsap from 'gsap';
  import { api } from '$lib/api/client.js';
  import { authState } from '$lib/state/auth.svelte.js';
  import { toastState } from '$lib/state/toast.svelte.js';
  import DataTable from '$lib/components/ui/DataTable.svelte';
  import Modal from '$lib/components/ui/Modal.svelte';
  import Badge from '$lib/components/ui/Badge.svelte';

  let users = $state([]);
  let meta = $state({ total: 0, active_count: 0 });
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
        gsap.from('.gsap-header, .gsap-table', {
          y: 14,
          opacity: 0,
          duration: 0.4,
          stagger: 0.06,
          ease: 'power2.out',
          clearProps: 'transform,opacity',
        });
      }, pageContainer);
    }
  });

  onDestroy(() => {
    if (gsapCtx) gsapCtx.revert();
  });

  // Modal State for Add & Edit
  let showModal = $state(false);
  let modalMode = $state('create'); // 'create' | 'edit'
  let formId = $state(null);
  let formName = $state('');
  let formEmail = $state('');
  let formPassword = $state('');
  let showFormPassword = $state(false);
  let formRoles = $state(['user']);
  let formIsActive = $state(true);
  let formErrors = $state({});
  let formSubmitting = $state(false);

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
    await loadUsers();
  });

  async function loadUsers() {
    loading = true;
    try {
      const res = await api.request('/api/v1/admin/users');
      users = res.data || [];
      meta = res.meta || { total: users.length, active_count: 0 };
    } catch (err) {
      toastState.error(err.message || 'Gagal memuat daftar pengguna.');
      if (err.status === 403) {
        goto('/dashboard');
      }
    } finally {
      loading = false;
    }
  }

  function openCreateModal() {
    modalMode = 'create';
    formId = null;
    formName = '';
    formEmail = '';
    formPassword = '';
    showFormPassword = false;
    formRoles = ['user'];
    formIsActive = true;
    formErrors = {};
    showModal = true;
  }

  function openEditModal(user) {
    modalMode = 'edit';
    formId = user.id;
    formName = user.name;
    formEmail = user.email;
    formPassword = '';
    showFormPassword = false;
    formRoles = Array.isArray(user.role) ? [...user.role] : [user.role || 'user'];
    formIsActive = Boolean(user.is_active);
    formErrors = {};
    showModal = true;
  }

  function toggleRoleSelection(role) {
    if (formRoles.includes(role)) {
      if (formRoles.length > 1) {
        formRoles = formRoles.filter(r => r !== role);
      }
    } else {
      formRoles = [...formRoles, role];
    }
  }

  async function handleSaveUser(e) {
    e.preventDefault();
    formErrors = {};
    formSubmitting = true;

    try {
      if (modalMode === 'create') {
        const payload = {
          name: formName,
          email: formEmail,
          password: formPassword,
          role: formRoles,
          is_active: formIsActive,
        };
        const res = await api.request('/api/v1/admin/users', {
          method: 'POST',
          body: JSON.stringify(payload)
        });
        toastState.success(res.message || 'Pengguna berhasil ditambahkan.');
        const newUser = res.user || res.data;
        if (newUser) {
          users = [newUser, ...users];
          meta = {
            ...meta,
            total: meta.total + 1,
            active_count: newUser.is_active ? meta.active_count + 1 : meta.active_count
          };
        }
      } else {
        const payload = {
          name: formName,
          email: formEmail,
          role: formRoles,
          is_active: formIsActive,
        };
        if (formPassword.trim()) {
          payload.password = formPassword;
        }
        const res = await api.request(`/api/v1/admin/users/${formId}`, {
          method: 'PUT',
          body: JSON.stringify(payload)
        });
        toastState.success(res.message || 'Data pengguna berhasil diperbarui.');
        const updatedUser = res.user || res.data;
        if (updatedUser) {
          const idx = users.findIndex(u => u.id === updatedUser.id);
          if (idx !== -1) {
            users[idx] = updatedUser;
          }
        }
      }

      showModal = false;
    } catch (err) {
      if (err.status === 422 && err.errors) {
        formErrors = err.errors;
      } else {
        toastState.error(err.message || 'Gagal menyimpan data pengguna.');
      }
    } finally {
      formSubmitting = false;
    }
  }

  async function handleDeleteUser(user) {
    if (user.id === authState.user?.id) {
      toastState.warning('Anda tidak dapat menghapus akun sendiri.');
      return;
    }

    if (!confirm(`Hapus pengguna ${user.name} (${user.email}) secara permanen? Data yang dihapus tidak dapat dikembalikan.`)) {
      return;
    }

    actionLoading[`del_${user.id}`] = true;

    try {
      const res = await api.request(`/api/v1/admin/users/${user.id}`, {
        method: 'DELETE'
      });
      toastState.success(res.message || 'Pengguna berhasil dihapus.');
      // Optimasi Render In-Memory (Zero Network Re-Fetch)
      users = users.filter(u => u.id !== user.id);
      meta = {
        ...meta,
        total: Math.max(0, meta.total - 1),
        active_count: user.is_active ? Math.max(0, meta.active_count - 1) : meta.active_count
      };
    } catch (err) {
      toastState.error(err.message || 'Gagal menghapus pengguna.');
    } finally {
      actionLoading[`del_${user.id}`] = false;
    }
  }

  async function handleToggleStatus(user) {
    if (user.id === authState.user?.id) {
      toastState.warning('Anda tidak dapat menonaktifkan akun sendiri.');
      return;
    }

    actionLoading[user.id] = true;

    try {
      const res = await api.request(`/api/v1/admin/users/${user.id}/toggle-status`, {
        method: 'POST'
      });
      toastState.success(res.message);
      // Optimasi Render In-Memory (Zero Network Re-Fetch)
      const updatedUser = res.user || res.data;
      if (updatedUser) {
        const idx = users.findIndex(u => u.id === updatedUser.id);
        if (idx !== -1) {
          const wasActive = users[idx].is_active;
          users[idx] = updatedUser;
          if (wasActive && !updatedUser.is_active) {
            meta = { ...meta, active_count: Math.max(0, meta.active_count - 1) };
          } else if (!wasActive && updatedUser.is_active) {
            meta = { ...meta, active_count: meta.active_count + 1 };
          }
        }
      }
    } catch (err) {
      toastState.error(err.message || 'Gagal mengubah status pengguna.');
    } finally {
      actionLoading[user.id] = false;
    }
  }

  async function handleRevokeSessions(user) {
    if (!confirm(`Cabut seluruh sesi login untuk ${user.name}? Pengguna akan dipaksa logout di semua perangkat.`)) {
      return;
    }

    actionLoading[`revoke_${user.id}`] = true;

    try {
      const res = await api.request(`/api/v1/admin/users/${user.id}/revoke-sessions`, {
        method: 'POST'
      });
      toastState.success(res.message);
      // Optimasi Render In-Memory: Naikkan token_version seketika
      const updatedUser = res.user || res.data;
      if (updatedUser) {
        const idx = users.findIndex(u => u.id === updatedUser.id);
        if (idx !== -1) {
          users[idx] = updatedUser;
        }
      }
    } catch (err) {
      toastState.error(err.message || 'Gagal mencabut sesi perangkat.');
    } finally {
      actionLoading[`revoke_${user.id}`] = false;
    }
  }
</script>

<svelte:head>
  <title>Kelola Pengguna — Hyperion Vault</title>
  <meta name="robots" content="noindex, nofollow" />
</svelte:head>

{#if authState.isAuthenticated && authState.hasRole('admin')}
<div bind:this={pageContainer} class="max-w-6xl mx-auto p-4 sm:p-6 space-y-6">
  <!-- Page Header (Apple Spatial Glassmorphism & Specular Chamfer) -->
  <div class="gsap-header p-4 sm:p-5 rounded-2xl bg-white/75 dark:bg-slate-900/60 backdrop-blur-xl border border-white/50 dark:border-white/10 shadow-sm relative overflow-hidden before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/50 dark:before:via-white/20 before:to-transparent bg-gradient-to-br from-indigo-500/[0.04] via-transparent to-cyan-500/[0.03] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
        <span>Manajemen Pengguna</span>
        <span class="text-xs font-mono font-normal px-2.5 py-0.5 rounded-full bg-indigo-50/80 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 border border-indigo-200/80 dark:border-indigo-800/40">RBAC</span>
      </h1>
      <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
        Kelola akun pengguna, konfigurasi multiple role, status akun, dan keamanan kredensial.
      </p>
    </div>

    <!-- Actions & Stats -->
    <div class="flex flex-wrap items-center gap-2.5">
      <div class="flex items-center gap-2">
        <span class="text-xs px-3 py-1.5 rounded-xl bg-white/80 dark:bg-slate-950/80 backdrop-blur-md text-slate-700 dark:text-slate-300 font-mono border border-white/50 dark:border-white/10 flex items-center gap-1.5 shadow-xs">
          <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
          <span>Total: <b class="text-slate-900 dark:text-white">{meta.total}</b></span>
        </span>
        <span class="text-xs px-3 py-1.5 rounded-xl bg-white/80 dark:bg-slate-950/80 backdrop-blur-md text-slate-700 dark:text-slate-300 font-mono border border-white/50 dark:border-white/10 flex items-center gap-1.5 shadow-xs">
          <span class="relative flex h-1.5 w-1.5">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500 shadow-[0_0_6px_#10b981]"></span>
          </span>
          <span>Aktif: <b class="text-emerald-600 dark:text-emerald-400">{meta.active_count}</b></span>
        </span>
      </div>

      <button
        type="button"
        onclick={loadUsers}
        disabled={loading}
        class="text-xs px-3 py-2 rounded-lg bg-white/80 hover:bg-white dark:bg-slate-800/80 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold border border-white/50 dark:border-white/10 transition disabled:opacity-50 flex items-center gap-1.5 shadow-xs cursor-pointer active:scale-95"
      >
        <svg class="w-3.5 h-3.5 {loading ? 'animate-spin' : ''}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <span>Refresh</span>
      </button>

      <a
        href="/api/v1/admin/users/export"
        download
        class="text-xs px-3 py-2 rounded-lg bg-white/80 hover:bg-white dark:bg-slate-800/80 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold border border-white/50 dark:border-white/10 transition flex items-center gap-1.5 shadow-xs cursor-pointer active:scale-95"
        title="Ekspor seluruh pengguna ke CSV (Streamed $O(1)$ memory)"
      >
        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <span>Ekspor CSV</span>
      </a>

      <button
        type="button"
        id="add-user-btn"
        onclick={openCreateModal}
        class="text-xs px-3.5 py-2 rounded-lg bg-gradient-to-r from-indigo-600 via-indigo-600 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 text-white font-semibold shadow-md shadow-indigo-600/30 transition flex items-center gap-1.5 cursor-pointer active:scale-95 border border-indigo-400/40"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Tambah Pengguna</span>
      </button>
    </div>
  </div>

  <!-- Users Data Table (Using Reusable DataTable Component with Lazy Load) -->
  <div class="gsap-table">
  <DataTable
    items={users}
    pageSize={10}
    searchPlaceholder="Cari berdasarkan nama, email, atau role..."
    searchFields={['name', 'email', 'role']}
    {loading}
    emptyMessage="Belum ada data pengguna terdaftar."
  >
    {#snippet header()}
      <tr>
        <th class="px-5 py-3.5">No</th>
        <th class="px-5 py-3.5">Nama & Email</th>
        <th class="px-5 py-3.5">Role Otorisasi</th>
        <th class="px-5 py-3.5">Status Akun</th>
        <th class="px-5 py-3.5">Versi Token</th>
        <th class="px-5 py-3.5 text-right">Aksi Manajemen & Keamanan</th>
      </tr>
    {/snippet}

    {#snippet row(u, idx)}
      <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition">
        <td class="px-5 py-3.5 font-mono text-slate-400 dark:text-slate-500 font-semibold">{idx + 1}</td>
        <td class="px-5 py-3.5">
          <div class="font-semibold text-slate-900 dark:text-white text-sm">{u.name}</div>
          <div class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">{u.email}</div>
        </td>
        <td class="px-5 py-3.5">
          <div class="flex flex-wrap gap-1">
            {#each (Array.isArray(u.role) ? u.role : [u.role]) as r}
              <Badge type="role" value={r} />
            {/each}
          </div>
        </td>
        <td class="px-5 py-3.5">
          <Badge type="status" value={u.is_active} />
        </td>
        <td class="px-5 py-3.5 font-mono text-[11px] text-slate-500 dark:text-slate-400">
          v{u.token_version ?? 1}
        </td>
        <td class="px-5 py-3.5 text-right space-x-1.5 whitespace-nowrap">
          <!-- Edit User Button -->
          <button
            type="button"
            onclick={() => openEditModal(u)}
            aria-label="Edit data {u.name}"
            class="text-xs px-2.5 py-1 rounded-lg font-medium bg-white hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition cursor-pointer shadow-xs"
          >
            Edit
          </button>

          <!-- Toggle Status Button -->
          <button
            type="button"
            onclick={() => handleToggleStatus(u)}
            disabled={actionLoading[u.id] || u.id === authState.user?.id}
            aria-label="{u.is_active ? 'Tangguhkan' : 'Aktifkan'} akun {u.name}"
            class="text-xs px-2.5 py-1 rounded-lg font-medium transition cursor-pointer disabled:opacity-40 {u.is_active ? 'bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300 dark:hover:bg-amber-900/60 border border-amber-200 dark:border-amber-800/40' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800/40'}"
          >
            {actionLoading[u.id] ? 'Proses...' : (u.is_active ? 'Tangguhkan' : 'Aktifkan')}
          </button>

          <!-- Instant Session Revoke Button -->
          <button
            type="button"
            onclick={() => handleRevokeSessions(u)}
            disabled={actionLoading[`revoke_${u.id}`]}
            aria-label="Cabut sesi perangkat {u.name}"
            class="text-xs px-2.5 py-1 rounded-lg font-medium bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-800/40 transition cursor-pointer disabled:opacity-40"
            title="Cabut sesi perangkat pengguna secara instan"
          >
            {actionLoading[`revoke_${u.id}`] ? 'Mencabut...' : 'Cabut Sesi'}
          </button>

          <!-- Delete User Button -->
          {#if u.id !== authState.user?.id}
            <button
              type="button"
              onclick={() => handleDeleteUser(u)}
              disabled={actionLoading[`del_${u.id}`]}
              aria-label="Hapus akun {u.name}"
              class="text-xs px-2.5 py-1 rounded-lg font-medium bg-rose-100 text-rose-800 hover:bg-rose-200 dark:bg-rose-950/60 dark:text-rose-400 dark:hover:bg-rose-900/80 border border-rose-300 dark:border-rose-800/60 transition cursor-pointer disabled:opacity-40"
            >
              {actionLoading[`del_${u.id}`] ? 'Hapus...' : 'Hapus'}
            </button>
          {/if}
        </td>
      </tr>
    {/snippet}

    {#snippet card(u, idx)}
      <div class="space-y-2.5">
        <!-- User Info & Status Header -->
        <div class="flex items-start justify-between gap-2">
          <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-slate-800 to-indigo-900 dark:from-slate-900 dark:to-indigo-950 border border-indigo-400/30 flex items-center justify-center font-bold text-indigo-300 text-xs shadow-inner shrink-0 font-mono">
              {u.name.slice(0, 2).toUpperCase()}
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-1.5">
                <span class="font-bold text-slate-900 dark:text-white text-xs truncate">{u.name}</span>
                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono shrink-0">#{idx + 1}</span>
              </div>
              <div class="text-[11px] text-slate-500 dark:text-slate-400 font-mono truncate">{u.email}</div>
            </div>
          </div>
          <div class="shrink-0">
            <Badge type="status" value={u.is_active} />
          </div>
        </div>

        <!-- Role Badges & Token Version -->
        <div class="flex flex-wrap items-center gap-1 pt-1 border-t border-slate-100 dark:border-slate-800/60">
          {#each (Array.isArray(u.role) ? u.role : [u.role]) as r (r)}
            <Badge type="role" value={r} />
          {/each}
          <span class="px-1.5 py-0.5 text-[10px] rounded font-mono bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
            Token v{u.token_version ?? 1}
          </span>
        </div>

        <!-- Mobile Actions Grid (Thumb Zone) -->
        <div class="grid grid-cols-2 gap-1.5 pt-2 border-t border-slate-100 dark:border-slate-800/60">
          <button
            type="button"
            onclick={() => openEditModal(u)}
            aria-label="Edit data {u.name}"
            class="min-h-[30px] px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-white hover:bg-slate-100 dark:bg-slate-800/80 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/60 flex items-center justify-center gap-1 transition active:scale-95 cursor-pointer shadow-2xs"
          >
            <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            <span>Edit</span>
          </button>

          <button
            type="button"
            onclick={() => handleToggleStatus(u)}
            disabled={actionLoading[u.id] || u.id === authState.user?.id}
            aria-label="{u.is_active ? 'Tangguhkan' : 'Aktifkan'} akun {u.name}"
            class="min-h-[30px] px-2.5 py-1 rounded-lg text-[11px] font-semibold transition active:scale-95 disabled:opacity-40 cursor-pointer flex items-center justify-center gap-1 shadow-2xs {u.is_active ? 'bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300 dark:hover:bg-amber-900/60 border border-amber-200 dark:border-amber-800/40' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800/40'}"
          >
            <span>{actionLoading[u.id] ? 'Proses...' : (u.is_active ? 'Tangguhkan' : 'Aktifkan')}</span>
          </button>

          <button
            type="button"
            onclick={() => handleRevokeSessions(u)}
            disabled={actionLoading[`revoke_${u.id}`]}
            aria-label="Cabut sesi perangkat {u.name}"
            class="min-h-[30px] px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-800/40 transition active:scale-95 disabled:opacity-40 cursor-pointer flex items-center justify-center gap-1 shadow-2xs"
            title="Cabut sesi perangkat pengguna secara instan"
          >
            <svg class="w-3 h-3 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
            <span>{actionLoading[`revoke_${u.id}`] ? 'Mencabut...' : 'Cabut Sesi'}</span>
          </button>

          {#if u.id !== authState.user?.id}
            <button
              type="button"
              onclick={() => handleDeleteUser(u)}
              disabled={actionLoading[`del_${u.id}`]}
              aria-label="Hapus akun {u.name}"
              class="min-h-[30px] px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-rose-100 text-rose-800 hover:bg-rose-200 dark:bg-rose-950/60 dark:text-rose-400 dark:hover:bg-rose-900/80 border border-rose-300 dark:border-rose-800/60 transition active:scale-95 disabled:opacity-40 cursor-pointer flex items-center justify-center gap-1 shadow-2xs"
            >
              <svg class="w-3 h-3 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              <span>{actionLoading[`del_${u.id}`] ? 'Hapus...' : 'Hapus'}</span>
            </button>
          {:else}
            <div class="flex items-center justify-center text-[10px] text-slate-400 font-mono italic">
              Akun Anda Sendiri
            </div>
          {/if}
        </div>
      </div>
    {/snippet}
  </DataTable>
  </div>
</div>

<!-- Modal Form: Add / Edit User (Using Reusable Modal Component) -->
<Modal
  show={showModal}
  title={modalMode === 'create' ? 'Tambah Pengguna Baru' : 'Edit Data Pengguna'}
  subtitle="Konfigurasi data pengguna dan hak akses sistem"
  onclose={() => showModal = false}
>
  <form onsubmit={handleSaveUser} id="user-form" class="space-y-4">
    <!-- Contextual Guidance Callout (Baby Boomer & Senior UX) -->
    <div class="p-3 rounded-xl bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60 flex items-start gap-2.5 text-xs text-indigo-950 dark:text-indigo-200">
      <span class="text-base select-none">💡</span>
      <div class="leading-relaxed font-medium">
        <strong>Panduan:</strong> Lengkapi data pengguna di bawah ini. Pastikan alamat email aktif dan pilih peran (role) yang tepat untuk menentukan hak akses fitur.
      </div>
    </div>

    <!-- Name Input -->
    <div>
      <div class="flex items-center justify-between mb-1.5">
        <label for="form-name" class="text-xs font-bold text-slate-800 dark:text-slate-200">Nama Lengkap</label>
        <span class="text-[10px] font-bold text-rose-600 bg-rose-50 dark:bg-rose-950/60 px-1.5 py-0.5 rounded border border-rose-200 dark:border-rose-900">* Wajib Diisi</span>
      </div>
      <input
        id="form-name"
        type="text"
        bind:value={formName}
        required
        placeholder="John Doe"
        class="w-full px-3.5 py-2.5 min-h-[44px] bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-base sm:text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition"
      />
      {#if formErrors.name}<span class="text-[11px] text-rose-500 dark:text-rose-400 mt-1 block font-medium">{formErrors.name[0]}</span>{/if}
    </div>

    <!-- Email Input -->
    <div>
      <div class="flex items-center justify-between mb-1.5">
        <label for="form-email" class="text-xs font-bold text-slate-800 dark:text-slate-200">Alamat Email</label>
        <span class="text-[10px] font-bold text-rose-600 bg-rose-50 dark:bg-rose-950/60 px-1.5 py-0.5 rounded border border-rose-200 dark:border-rose-900">* Wajib Diisi</span>
      </div>
      <input
        id="form-email"
        type="email"
        bind:value={formEmail}
        required
        placeholder="john@hyperion-vault.test"
        class="w-full px-3.5 py-2.5 min-h-[44px] bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-base sm:text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition"
      />
      {#if formErrors.email}<span class="text-[11px] text-rose-500 dark:text-rose-400 mt-1 block font-medium">{formErrors.email[0]}</span>{/if}
    </div>

    <!-- Password Input -->
    <div>
      <div class="flex items-center justify-between mb-1.5">
        <label for="form-password" class="text-xs font-bold text-slate-800 dark:text-slate-200">
          Kata Sandi {modalMode === 'edit' ? '(Boleh Kosong jika tidak diubah)' : '(Minimal 8 Karakter)'}
        </label>
        {#if modalMode === 'create'}
          <span class="text-[10px] font-bold text-rose-600 bg-rose-50 dark:bg-rose-950/60 px-1.5 py-0.5 rounded border border-rose-200 dark:border-rose-900">* Wajib Diisi</span>
        {:else}
          <span class="text-[10px] font-medium text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700">(Boleh Kosong)</span>
        {/if}
      </div>
      <div class="relative">
        <input
          id="form-password"
          type={showFormPassword ? 'text' : 'password'}
          bind:value={formPassword}
          required={modalMode === 'create'}
          placeholder="••••••••"
          class="w-full pl-3.5 pr-12 py-2.5 min-h-[44px] bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-base sm:text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition"
        />
        <button
          type="button"
          id="toggle-user-password-visibility"
          onclick={() => (showFormPassword = !showFormPassword)}
          aria-label={showFormPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
          class="min-h-[44px] min-w-[44px] absolute inset-y-0 right-0 pr-3 flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition focus:outline-none cursor-pointer"
        >
          {#if showFormPassword}
            <!-- Eye Off Icon -->
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
            </svg>
          {:else}
            <!-- Eye Icon -->
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          {/if}
        </button>
      </div>
      {#if formErrors.password}<span class="text-[11px] text-rose-500 dark:text-rose-400 mt-1 block font-medium">{formErrors.password[0]}</span>{/if}
    </div>

    <!-- Multiple Roles Selection -->
    <div>
      <span class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1.5">Kewenangan / Roles (Pilihan Multi Peran)</span>
      <div class="flex items-center gap-3">
        {#each ['admin', 'manager', 'user'] as r}
          <label class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer bg-slate-100 dark:bg-slate-950 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-indigo-400 dark:hover:border-indigo-600 transition min-h-[38px]">
            <input
              type="checkbox"
              checked={formRoles.includes(r)}
              onchange={() => toggleRoleSelection(r)}
              class="w-4 h-4 rounded bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-0 cursor-pointer"
            />
            <span class="capitalize font-semibold">{r}</span>
          </label>
        {/each}
      </div>
      {#if formErrors.role}<span class="text-[11px] text-rose-500 dark:text-rose-400 mt-1 block font-medium">{formErrors.role[0]}</span>{/if}
    </div>

    <!-- Active Status -->
    <div class="pt-1">
      <label class="flex items-center gap-2.5 text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/50 border border-slate-200 dark:border-slate-800">
        <input
          type="checkbox"
          bind:checked={formIsActive}
          class="w-4 h-4 rounded bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-0 cursor-pointer"
        />
        <span>Akun Aktif (Dapat Login ke Sistem)</span>
      </label>
    </div>
  </form>

  {#snippet footer()}
    <button
      type="button"
      onclick={() => showModal = false}
      class="min-h-[44px] px-4 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-xl transition cursor-pointer flex items-center gap-1.5"
    >
      <span>✕ Batal</span>
    </button>
    <button
      type="submit"
      form="user-form"
      disabled={formSubmitting}
      class="min-h-[44px] px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 active:scale-[0.98] border border-indigo-400/40 rounded-xl shadow-md shadow-indigo-600/30 transition disabled:opacity-50 cursor-pointer flex items-center gap-1.5"
    >
      {#if formSubmitting}
        <div class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
        <span>Menyimpan...</span>
      {:else}
        <span>{modalMode === 'create' ? '➕ Tambah Pengguna' : '💾 Simpan Perubahan'}</span>
      {/if}
    </button>
  {/snippet}
</Modal>
{/if}
