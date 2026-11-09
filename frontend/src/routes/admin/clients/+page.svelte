<script>
  import { onMount, onDestroy } from 'svelte';
  import { page } from '$app/state';
  import gsap from 'gsap';
  import { api } from '$lib/api/client.js';
  import { toastState } from '$lib/state/toast.svelte.js';
  import DataTable from '$lib/components/ui/DataTable.svelte';
  import Badge from '$lib/components/ui/Badge.svelte';
  import ClientModal from '$lib/components/clients/ClientModal.svelte';
  import ClientDetailSettingModal from '$lib/components/clients/ClientDetailSettingModal.svelte';

  let projects = $state([]);
  let meta = $state({
    total_projects: 0,
    total_clients: 0,
    active_projects: 0,
    development_projects: 0,
    maintenance_projects: 0,
    total_valuation: 0,
    total_paid: 0,
  });

  let loading = $state(true);
  let error = $state(null);
  let activeFilter = $state('all'); // 'all' | 'development' | 'maintenance'

  // Modal Intake State
  let showModal = $state(false);
  let modalMode = $state('create'); // 'create' | 'edit'
  let selectedProject = $state(null);
  let submitting = $state(false);

  // Modal Detail & Setting State
  let showDetailModal = $state(false);
  let selectedDetailProjectId = $state(null);

  // Delete Confirm State
  let showDeleteConfirm = $state(false);
  let projectToDelete = $state(null);
  let deleting = $state(false);

  let pageContainer = $state(null);
  let gsapCtx;

  $effect(() => {
    if (pageContainer && typeof window !== 'undefined' && !loading) {
      const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      if (prefersReducedMotion) return;

      if (gsapCtx) gsapCtx.revert();
      gsapCtx = gsap.context(() => {
        gsap.from('.gsap-header, .gsap-kpi, .gsap-table', {
          y: 16,
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

  onMount(() => {
    loadProjects();
    const detailId = page.url.searchParams.get('detail');
    if (detailId) {
      selectedDetailProjectId = Number(detailId);
      showDetailModal = true;
    }
  });

  async function loadProjects() {
    loading = true;
    error = null;
    try {
      let url = '/api/v1/admin/clients';
      if (activeFilter !== 'all') {
        url += `?project_type=${activeFilter}`;
      }
      const res = await api.request(url);
      if (res && res.data) {
        projects = res.data;
        meta = res.meta || meta;
      }
    } catch (e) {
      error = e?.message || 'Gagal memuat direktori klien & proyek.';
      toastState.error(error, 'Koneksi Gagal');
    } finally {
      loading = false;
    }
  }

  function handleFilterChange(filterKey) {
    activeFilter = filterKey;
    loadProjects();
  }

  // Derive unique list of existing clients for the dropdown modal
  let uniqueClients = $derived.by(() => {
    const map = new Map();
    for (const p of projects) {
      if (p.client && !map.has(p.client.id)) {
        map.set(p.client.id, p.client);
      }
    }
    return Array.from(map.values());
  });

  function openCreateModal() {
    modalMode = 'create';
    selectedProject = null;
    showModal = true;
  }

  function openDetailModal(project) {
    selectedDetailProjectId = project.id;
    showDetailModal = true;
  }

  async function handleProjectReady(projectId) {
    showModal = false;
    await loadProjects();
    if (projectId) {
      selectedDetailProjectId = projectId;
      showDetailModal = true;
    }
  }

  async function handleSaveProject(payload) {
    submitting = true;
    try {
      const res = await api.request('/api/v1/admin/clients', {
        method: 'POST',
        body: JSON.stringify(payload)
      });
      toastState.success('Proyek klien baru berhasil ditambahkan.', 'Pendaftaran Sukses');
      showModal = false;
      await loadProjects();
      if (res?.data?.id) {
        selectedDetailProjectId = res.data.id;
        showDetailModal = true;
      }
    } catch (e) {
      toastState.error(e?.message || 'Terjadi kesalahan saat menyimpan data.', 'Gagal Menyimpan');
    } finally {
      submitting = false;
    }
  }

  function confirmDeleteProject(project) {
    if (Number(project.paid_amount || 0) > 0) {
      toastState.error('Proyek yang sudah memiliki riwayat pembayaran tidak dapat dihapus.', 'Akses Dibatasi');
      return;
    }
    projectToDelete = project;
    showDeleteConfirm = true;
  }

  async function executeDeleteProject() {
    if (!projectToDelete) return;
    deleting = true;
    try {
      await api.request(`/api/v1/admin/clients/${projectToDelete.id}`, {
        method: 'DELETE'
      });
      toastState.success(`Proyek ${projectToDelete.app_name} berhasil dihapus.`, 'Terhapus');
      showDeleteConfirm = false;
      projectToDelete = null;
      await loadProjects();
    } catch (e) {
      toastState.error(e?.message || 'Gagal menghapus proyek klien.', 'Gagal');
    } finally {
      deleting = false;
    }
  }

  function calculateProjectProgress(project) {
    let done = 0;
    let total = 0;
    if (project?.roles && Array.isArray(project.roles)) {
      project.roles.forEach(r => {
        if (r.menus && Array.isArray(r.menus)) {
          total += r.menus.length;
          done += r.menus.filter(m => m.is_done).length;
        }
      });
    }
    const percent = total > 0 ? Math.round((done / total) * 100) : 0;
    return { done, total, percent };
  }

  function formatDate(dateStr) {
    if (!dateStr) return 'Jan 2026';
    try {
      const d = new Date(dateStr);
      return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch {
      return dateStr;
    }
  }

  function formatIDR(val) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0
    }).format(val || 0);
  }

  const clientCsvColumns = [
    { key: 'app_name', label: 'NAMA APLIKASI' },
    { key: 'client', label: 'NAMA CLIENT', transform: (p) => p.client?.name || p.name || '-' },
    { key: 'client', label: 'INDUSTRI', transform: (p) => p.client?.industry || 'Umum' },
    { key: 'project_type', label: 'TIPE PROYEK', transform: (p) => p.project_type?.toUpperCase() || '-' },
    { key: 'lead_pic', label: 'LEAD PIC' },
    { key: 'created_at', label: 'TGL INPUT', transform: (p) => formatDate(p.created_at) },
    { key: 'roles', label: 'JUMLAH ROLE', transform: (p) => `${p.roles?.length || 0} Role` },
    { key: 'github_url', label: 'REPO GITHUB', transform: (p) => p.github_url || '-' },
    { key: 'status', label: 'STATUS' }
  ];
</script>

<svelte:head>
  <title>Manajemen Client & Proyek — Valora OS</title>
</svelte:head>

<div bind:this={pageContainer} class="min-h-screen bg-slate-100/70 dark:bg-[#070b12] text-slate-800 dark:text-slate-200 transition-colors duration-200">
  <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-5 pb-24 md:pb-12 space-y-5">
    
    <!-- Top Action Bar & Header (L1 Macro Surface) -->
    <div class="gsap-header bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/80">
            Valora Agency OS
          </span>
          <span class="text-xs text-slate-400 dark:text-slate-500">•</span>
          <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">PT Valora Digital Solusindo</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
          <span>Direktori Client & Manajemen Aplikasi</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-2xl">
          Pusat kendali kontrak, rincian komponen arsitektur, dynamic roles login, dan valuasi keuangan proyek digital.
        </p>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-wrap items-center gap-2 shrink-0">
        <button
          type="button"
          onclick={loadProjects}
          disabled={loading}
          class="min-h-[36px] px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold transition border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
        >
          <svg class="w-3.5 h-3.5 {loading ? 'animate-spin' : ''}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>Segarkan</span>
        </button>

        <a
          href="/api/v1/admin/clients/export"
          download
          class="min-h-[36px] px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold transition border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 cursor-pointer"
          title="Ekspor CSV Data Proyek Terpadu (UTF-8 BOM)"
        >
          <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <span>Ekspor CSV</span>
        </a>

        <button
          type="button"
          onclick={openCreateModal}
          class="min-h-[36px] px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-md shadow-indigo-600/30 flex items-center gap-1.5 cursor-pointer active:scale-95"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>+ Tambah Client / Proyek</span>
        </button>
      </div>
    </div>

    <!-- KPI Metric Cards (L1 Macro Surface) -->
    <div class="gsap-kpi grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
      <!-- KPI 1: Total Proyek -->
      <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-3.5 sm:p-4 shadow-2xs">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Portofolio</span>
          <span class="p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
            </svg>
          </span>
        </div>
        <p class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1.5 font-mono">{meta.total_projects} <span class="text-xs font-sans text-slate-500 font-normal">Proyek</span></p>
        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1">
          <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{meta.active_projects} Aktif</span> • {meta.total_clients} Klien Mitra
        </p>
      </div>

      <!-- KPI 2: Tipe Kontrak -->
      <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-3.5 sm:p-4 shadow-2xs">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Distribusi Tipe</span>
          <span class="p-1.5 rounded-lg bg-cyan-50 dark:bg-cyan-950/70 text-cyan-600 dark:text-cyan-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </span>
        </div>
        <p class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1.5 font-mono">{meta.development_projects} <span class="text-xs font-sans text-slate-500 font-normal">Dev</span></p>
        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1">
          <span class="text-amber-600 dark:text-amber-400 font-semibold">{meta.maintenance_projects} Maintenance SLA</span>
        </p>
      </div>

      <!-- KPI 3: Total Valuasi -->
      <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-3.5 sm:p-4 shadow-2xs">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Nilai Kontrak</span>
          <span class="p-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </span>
        </div>
        <p class="text-base sm:text-lg font-black text-slate-900 dark:text-white mt-1.5 font-mono truncate">{formatIDR(meta.total_valuation)}</p>
        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">Akumulasi Valuasi Paket & Komponen</p>
      </div>

      <!-- KPI 4: Kas Masuk -->
      <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-3.5 sm:p-4 shadow-2xs">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pembayaran Diterima</span>
          <span class="p-1.5 rounded-lg bg-purple-50 dark:bg-purple-950/70 text-purple-600 dark:text-purple-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </span>
        </div>
        <p class="text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 mt-1.5 font-mono truncate">{formatIDR(meta.total_paid)}</p>
        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">
          Sisa: <span class="font-mono font-semibold text-rose-500">{formatIDR(Math.max(0, meta.total_valuation - meta.total_paid))}</span>
        </p>
      </div>
    </div>

    <!-- Filter Segmented Control (RULE-14: Responsive Wrap, Visible Tabs) -->
    <div class="flex flex-wrap items-center gap-1.5">
      <button
        type="button"
        onclick={() => handleFilterChange('all')}
        class="px-3 py-1.5 rounded-xl text-xs font-semibold transition border cursor-pointer {activeFilter === 'all' ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:text-slate-900 dark:hover:text-white'}"
      >
        <span>Semua Proyek ({meta.total_projects})</span>
      </button>

      <button
        type="button"
        onclick={() => handleFilterChange('development')}
        class="px-3 py-1.5 rounded-xl text-xs font-semibold transition border cursor-pointer {activeFilter === 'development' ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:text-slate-900 dark:hover:text-white'}"
      >
        <span>Pengembangan Baru ({meta.development_projects})</span>
      </button>

      <button
        type="button"
        onclick={() => handleFilterChange('maintenance')}
        class="px-3 py-1.5 rounded-xl text-xs font-semibold transition border cursor-pointer {activeFilter === 'maintenance' ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:text-slate-900 dark:hover:text-white'}"
      >
        <span>Pemeliharaan / SLA ({meta.maintenance_projects})</span>
      </button>
    </div>

    <!-- Data Table & Card View (L1 Macro Surface + Dual View) -->
    <div class="gsap-table">
      <DataTable
        items={projects}
        pageSize={10}
        searchPlaceholder="Cari klien, nama aplikasi, industri, atau PIC..."
        searchFields={['app_name', 'project_name', 'lead_pic', 'status']}
        {loading}
        {error}
        onretry={loadProjects}
        emptyMessage="Belum ada data proyek klien pada filter ini."
        exportFilename="valora_direktori_klien"
        exportColumns={clientCsvColumns}
      >
        {#snippet header()}
          <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 uppercase text-[10.5px] tracking-wider">
            <th class="py-3 px-3 text-center w-10 font-bold">No</th>
            <th class="py-3 px-3 font-bold w-[22%]">Klien & Kontak</th>
            <th class="py-3 px-3 font-bold w-[26%]">Aplikasi & Industri</th>
            <th class="py-3 px-3 font-bold w-[22%]">Progress & Fitur</th>
            <th class="py-3 px-3 text-center font-bold w-[12%]">Struktur & Status</th>
            <th class="py-3 px-3 text-right font-bold w-[18%]">Aksi Project</th>
          </tr>
        {/snippet}

        {#snippet row(project, idx)}
          {@const progress = calculateProjectProgress(project)}
          <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors border-b border-slate-100 dark:border-slate-800/60">
            <!-- No -->
            <td class="py-3 px-3 text-center font-bold text-slate-400 text-xs font-mono">{idx + 1}</td>

            <!-- Klien & Kontak -->
            <td class="py-3 px-3">
              <span class="font-bold text-slate-900 dark:text-white block text-xs">{project.client?.name || project.name}</span>
              <span class="text-[11px] text-slate-500 dark:text-slate-400 font-mono block">{project.client?.phone || project.client?.contact_person || project.client?.email || '-'}</span>
              <span class="text-[10px] text-slate-400 font-mono mt-0.5 block">Tgl: {formatDate(project.created_at)}</span>
            </td>

            <!-- Aplikasi & Industri -->
            <td class="py-3 px-3">
              <div class="flex items-center gap-1.5 mb-1 flex-wrap">
                {#if project.project_type === 'maintenance'}
                  <span class="px-2 py-0.5 rounded text-[9.5px] font-extrabold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                    Maintenance
                  </span>
                {:else}
                  <span class="px-2 py-0.5 rounded text-[9.5px] font-extrabold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                    Development
                  </span>
                {/if}
                <span class="px-1.5 py-0.5 rounded text-[9.5px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                  {project.client?.industry || 'Umum'}
                </span>
                {#if project.github_url}
                  <a
                    href={project.github_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    class="p-0.5 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                    title="Lihat Repositori GitHub"
                  >
                    <svg class="w-3.5 h-3.5 inline-block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"></path>
                      <path d="M9 18c-4.51 2-5-2-7-2"></path>
                    </svg>
                  </a>
                {/if}
              </div>
              <span class="font-bold text-slate-900 dark:text-white text-xs block">{project.app_name}</span>
              <div class="mt-0.5">
                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold">Lead PIC: {project.lead_pic_user?.name || project.lead_pic || 'Tim Valora'}</span>
                {#if project.workers && project.workers.length > 0}
                  <div class="flex flex-wrap gap-1 mt-0.5">
                    {#each project.workers as w}
                      <span class="text-[9px] font-mono px-1 py-0.2 rounded bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/50" title="{w.name} ({w.pivot?.role_in_project || 'Pekerja'})">
                        {w.name.split(' ')[0]}
                      </span>
                    {/each}
                  </div>
                {/if}
              </div>
            </td>

            <!-- Progress & Fitur -->
            <td class="py-3 px-3">
              <div class="flex items-center gap-2 mb-1">
                <div class="flex-1 bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                  <div class="h-full bg-gradient-to-r from-indigo-500 to-emerald-500 rounded-full" style="width: {progress.percent}%;"></div>
                </div>
                <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 font-mono">{progress.percent}%</span>
              </div>
              <div class="flex items-center justify-between text-[10px]">
                <span class="text-slate-400 dark:text-slate-500 font-mono">{progress.done}/{progress.total} Modul Selesai</span>
                <div class="flex items-center gap-1 font-mono">
                  <span class="px-1 py-0.2 rounded text-[9px] font-bold {project.use_landing ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40' : 'text-slate-300'}">Web</span>
                  <span class="px-1 py-0.2 rounded text-[9px] font-bold {project.use_login ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40' : 'text-slate-300'}">Auth</span>
                  <span class="px-1 py-0.2 rounded text-[9px] font-bold {project.use_app_core ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40' : 'text-slate-300'}">App</span>
                </div>
              </div>
            </td>

            <!-- Struktur & Status -->
            <td class="py-3 px-3 text-center">
              <span class="font-bold text-slate-800 dark:text-slate-200 font-mono text-xs block">{project.roles?.length || 0} Role</span>
              <div class="mt-1">
                {#if project.status === 'Aktif'}
                  <span class="px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">Aktif</span>
                {:else if project.status === 'Pending'}
                  <span class="px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">Pending</span>
                {:else}
                  <span class="px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/20">{project.status}</span>
                {/if}
              </div>
            </td>

            <!-- Aksi Project -->
            <td class="py-3 px-3 text-right">
              <div class="flex items-center justify-end gap-1.5">
                <button
                  type="button"
                  onclick={() => openDetailModal(project)}
                  class="min-h-[30px] px-2.5 py-1 rounded-lg text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition shadow-xs flex items-center gap-1.5 cursor-pointer active:scale-95"
                  title="Detail & Konfigurasi Fitur Proyek"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <span>Detail & Setting</span>
                </button>
                {#if Number(project.paid_amount || 0) > 0}
                  <button
                    type="button"
                    disabled
                    class="min-h-[30px] px-2 py-1 rounded-lg text-xs font-semibold text-slate-300 dark:text-slate-600 cursor-not-allowed opacity-60"
                    title="Proyek yang sudah memiliki riwayat pembayaran tidak dapat dihapus"
                  >
                    Hapus
                  </button>
                {:else}
                  <button
                    type="button"
                    onclick={() => confirmDeleteProject(project)}
                    class="min-h-[30px] px-2 py-1 rounded-lg text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition cursor-pointer"
                    title="Hapus Proyek"
                  >
                    Hapus
                  </button>
                {/if}
              </div>
            </td>
          </tr>
        {/snippet}

        <!-- Card View untuk Mobile (RULE-12) -->
        {#snippet card(project)}
          {@const progress = calculateProjectProgress(project)}
          <div class="space-y-3">
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-cyan-500 flex items-center justify-center text-white font-bold text-xs shrink-0 shadow-xs">
                  {(project.client?.name || 'V').substring(0, 2).toUpperCase()}
                </div>
                <div>
                  <h4 class="text-xs font-bold text-slate-900 dark:text-white">{project.app_name}</h4>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400">{project.client?.name || 'Klien Umum'}</p>
                </div>
              </div>
              <div>
                {#if project.status === 'Aktif'}
                  <Badge variant="success" size="sm">Aktif</Badge>
                {:else}
                  <Badge variant="warning" size="sm">{project.status}</Badge>
                {/if}
              </div>
            </div>

            <!-- Progress & Modul -->
            <div class="space-y-1.5 p-2 bg-slate-50 dark:bg-slate-950/60 rounded-lg text-[11px]">
              <div class="flex items-center justify-between">
                <span class="text-slate-500 text-[10px]">Progress Pengerjaan</span>
                <span class="font-bold text-indigo-600 dark:text-indigo-400 font-mono text-[10px]">{progress.percent}% ({progress.done}/{progress.total} Modul)</span>
              </div>
              <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-indigo-500 to-emerald-500 rounded-full" style="width: {progress.percent}%;"></div>
              </div>
              <div class="flex items-center justify-between text-[10px] text-slate-400 pt-1">
                <span class="font-semibold text-slate-600 dark:text-slate-300">PIC: {project.lead_pic_user?.name || project.lead_pic || 'Tim Valora'}</span>
                <span>{project.roles?.length || 0} Role</span>
              </div>
              {#if project.workers && project.workers.length > 0}
                <div class="flex flex-wrap gap-1 pt-0.5">
                  {#each project.workers as w}
                    <span class="text-[9px] font-mono px-1 rounded bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/50" title="{w.name}">
                      {w.name.split(' ')[0]}
                    </span>
                  {/each}
                </div>
              {/if}
            </div>

            <div class="flex items-center justify-between pt-1 border-t border-slate-100 dark:border-slate-800">
              <div class="flex items-center gap-1">
                <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold {project.use_landing ? 'bg-emerald-500/10 text-emerald-600' : 'text-slate-400'}">Web</span>
                <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold {project.use_login ? 'bg-emerald-500/10 text-emerald-600' : 'text-slate-400'}">Auth</span>
                <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold {project.use_app_core ? 'bg-emerald-500/10 text-emerald-600' : 'text-slate-400'}">App</span>
              </div>
              <div class="flex items-center gap-1.5">
                <button
                  type="button"
                  onclick={() => openDetailModal(project)}
                  class="min-h-[28px] px-2.5 py-1 rounded-lg text-[11px] font-bold text-white bg-indigo-600 hover:bg-indigo-500 shadow-xs flex items-center gap-1 cursor-pointer"
                >
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <span>Detail & Setting</span>
                </button>
                {#if Number(project.paid_amount || 0) > 0}
                  <button
                    type="button"
                    disabled
                    class="min-h-[28px] px-2 py-1 rounded text-[11px] font-semibold text-slate-300 dark:text-slate-600 cursor-not-allowed opacity-60"
                    title="Proyek yang sudah memiliki riwayat pembayaran tidak dapat dihapus"
                  >
                    Hapus
                  </button>
                {:else}
                  <button
                    type="button"
                    onclick={() => confirmDeleteProject(project)}
                    class="min-h-[28px] px-2 py-1 rounded text-[11px] font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 cursor-pointer"
                  >
                    Hapus
                  </button>
                {/if}
              </div>
            </div>
          </div>
        {/snippet}
      </DataTable>
    </div>
  </div>
</div>

<!-- Modal Tambah / Edit Klien & Proyek -->
<ClientModal
  show={showModal}
  mode={modalMode}
  project={selectedProject}
  existingClients={uniqueClients}
  existingProjects={projects}
  onclose={() => (showModal = false)}
  onsave={handleSaveProject}
  onprojectready={handleProjectReady}
  {submitting}
/>

<!-- Modal Detail & Setting Proyek (Full Scope & Granular Editing) -->
<ClientDetailSettingModal
  show={showDetailModal}
  projectId={selectedDetailProjectId}
  onclose={() => (showDetailModal = false)}
  onchange={loadProjects}
/>

<!-- Dialog Konfirmasi Hapus Proyek -->
{#if showDeleteConfirm && projectToDelete}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-200">
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-5 space-y-4 shadow-2xl">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-950/70 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </div>
        <div>
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">Konfirmasi Penghapusan Proyek</h3>
          <p class="text-xs text-slate-500 mt-0.5">Tindakan ini tidak dapat dibatalkan.</p>
        </div>
      </div>

      <p class="text-xs text-slate-600 dark:text-slate-300">
        Apakah Anda yakin ingin menghapus proyek <b class="text-slate-900 dark:text-white">"{projectToDelete.app_name}"</b> milik <b class="text-slate-900 dark:text-white">{projectToDelete.client?.name}</b>?
      </p>

      <div class="flex items-center justify-end gap-2.5 pt-2">
        <button
          type="button"
          onclick={() => { showDeleteConfirm = false; projectToDelete = null; }}
          disabled={deleting}
          class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={executeDeleteProject}
          disabled={deleting}
          class="px-4 py-2 rounded-xl text-xs font-semibold bg-rose-600 hover:bg-rose-500 text-white shadow-md shadow-rose-600/30 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
        >
          {#if deleting}
            <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>Menghapus...</span>
          {:else}
            <span>Ya, Hapus Proyek</span>
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}
