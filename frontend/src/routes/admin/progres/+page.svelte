<script>
  import { onMount } from 'svelte';
  import { api } from '$lib/api/client.js';
  import { toastState } from '$lib/state/toast.svelte.js';
  import DataTable from '$lib/components/ui/DataTable.svelte';
  import ProgressChecklistModal from '$lib/components/clients/ProgressChecklistModal.svelte';
  import MenuCompletionModal from '$lib/components/clients/MenuCompletionModal.svelte';
  import ClientDetailSettingModal from '$lib/components/clients/ClientDetailSettingModal.svelte';

  let rawProjects = $state([]);
  let loading = $state(true);
  let error = $state(null);

  // Active filter tab: 'all' | 'development' | 'maintenance' | 'completed' | 'in_progress'
  let activeTab = $state('all');

  // Modals state
  let showChecklistModal = $state(false);
  let selectedChecklistProject = $state(null);

  let showCompletionModal = $state(false);
  let selectedMenuForCompletion = $state(null);
  let completionRoleName = $state('');
  let submittingCompletion = $state(false);

  let showDetailModal = $state(false);
  let selectedDetailProjectId = $state(null);

  // Load projects from API
  async function loadProgressData() {
    loading = true;
    error = null;
    try {
      const res = await api.request('/api/v1/admin/clients?limit=1000');
      rawProjects = Array.isArray(res?.data) ? res.data : [];
    } catch (err) {
      error = err?.message || 'Gagal memuat data monitoring progres proyek.';
      toastState.error(error, 'Gagal');
    } finally {
      loading = false;
    }
  }

  onMount(() => {
    loadProgressData();
  });

  // Calculate project progress percentage & done stats helper
  function computeProgress(project) {
    let total = 0;
    let done = 0;
    (project.roles || []).forEach(r => {
      (r.menus || []).forEach(m => {
        total++;
        if (m.is_done) done++;
      });
    });
    const percent = total > 0 ? Math.round((done / total) * 100) : 0;
    return { percent, total, done };
  }

  // Enhanced project list with progress data
  let projectsWithProgress = $derived.by(() => {
    return rawProjects.map(p => {
      const prog = computeProgress(p);
      return {
        ...p,
        _progress: prog,
        _clientName: p.client?.name || p.name || 'Umum',
        _industry: p.client?.industry || p.industry || 'Umum',
      };
    });
  });

  // Aggregated Top KPI Metrics
  let metrics = $derived.by(() => {
    const totalProjects = projectsWithProgress.length;
    let sumPercent = 0;
    let completedProjects = 0;
    let totalModulesDone = 0;
    let totalModules = 0;

    projectsWithProgress.forEach(p => {
      sumPercent += p._progress.percent;
      if (p._progress.percent === 100 && p._progress.total > 0) {
        completedProjects++;
      }
      totalModulesDone += p._progress.done;
      totalModules += p._progress.total;
    });

    const avgProgress = totalProjects > 0 ? Math.round(sumPercent / totalProjects) : 0;

    return {
      totalProjects,
      avgProgress,
      completedProjects,
      totalModulesDone,
      totalModules
    };
  });

  // Filtered projects by tab
  let tabFilteredProjects = $derived.by(() => {
    if (activeTab === 'development') {
      return projectsWithProgress.filter(p => p.project_type === 'development');
    }
    if (activeTab === 'maintenance') {
      return projectsWithProgress.filter(p => p.project_type === 'maintenance');
    }
    if (activeTab === 'completed') {
      return projectsWithProgress.filter(p => p._progress.percent === 100 && p._progress.total > 0);
    }
    if (activeTab === 'in_progress') {
      return projectsWithProgress.filter(p => p._progress.percent < 100 || p._progress.total === 0);
    }
    return projectsWithProgress;
  });

  // Handler membuka modal checklist progres
  function openChecklistModal(project) {
    selectedChecklistProject = project;
    showChecklistModal = true;
  }

  // Handler toggle menu di dalam checklist modal
  async function handleToggleMenu(menu, roleName) {
    if (menu.is_done) {
      // Langsung batalkan tanda selesai
      try {
        await api.request(`/api/v1/admin/menus/${menu.id}/toggle-done`, {
          method: 'POST',
          body: { is_done: false }
        });
        toastState.success(`Status menu "${menu.menu_name}" ditandai belum selesai.`, 'Status Diperbarui');
        await loadProgressData();
        // Update selected checklist project snapshot
        if (selectedChecklistProject) {
          const updated = rawProjects.find(p => p.id === selectedChecklistProject.id);
          if (updated) selectedChecklistProject = updated;
        }
      } catch (err) {
        toastState.error(err?.message || 'Gagal mengubah status menu.', 'Gagal');
      }
    } else {
      // Buka modal pilih tanggal penyelesaian
      selectedMenuForCompletion = menu;
      completionRoleName = roleName;
      showCompletionModal = true;
    }
  }

  // Konfirmasi penyelesaian menu bertanggal & ber-PIC
  async function handleConfirmMenuCompletion(data) {
    submittingCompletion = true;
    try {
      await api.request(`/api/v1/admin/menus/${data.menuId}/toggle-done`, {
        method: 'POST',
        body: {
          is_done: true,
          completed_date: data.completedDate,
          pic: data.pic,
          pic_user_id: data.pic_user_id || null
        }
      });
      toastState.success(`Menu "${selectedMenuForCompletion?.menu_name}" berhasil ditandai selesai!`, 'Pengerjaan Selesai');
      showCompletionModal = false;
      await loadProgressData();
      if (selectedChecklistProject) {
        const updated = rawProjects.find(p => p.id === selectedChecklistProject.id);
        if (updated) selectedChecklistProject = updated;
      }
    } catch (err) {
      toastState.error(err?.message || 'Gagal menandai pengerjaan menu selesai.', 'Gagal');
    } finally {
      submittingCompletion = false;
    }
  }

  // Buka detail & setting proyek
  function openProjectDetail(project) {
    selectedDetailProjectId = project.id;
    showDetailModal = true;
  }

  // Ekspor CSV format columns
  const csvColumns = [
    { key: 'app_name', label: 'NAMA APLIKASI' },
    { key: '_clientName', label: 'NAMA CLIENT' },
    { key: '_industry', label: 'INDUSTRI' },
    { key: 'project_type', label: 'TIPE PROYEK' },
    { key: 'lead_pic', label: 'LEAD PIC' },
    {
      key: '_progress',
      label: 'PROGRES PERSEN',
      transform: (p) => `${p?.percent || 0}%`
    },
    {
      key: '_progress',
      label: 'MODUL SELESAI',
      transform: (p) => `${p?.done || 0}/${p?.total || 0}`
    },
    { key: 'status', label: 'STATUS' }
  ];
</script>

<svelte:head>
  <title>Monitoring Progres Project & Checklist Menu — Valora OS</title>
</svelte:head>

<div class="min-h-screen bg-slate-100/70 dark:bg-[#070b12] text-slate-800 dark:text-slate-200">
  <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-5 sm:py-7 space-y-6">
    
    <!-- Top Header & Breadcrumb -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
          <span>Operasional</span>
          <span>/</span>
          <span class="text-indigo-600 dark:text-indigo-400 font-bold">Progres Project</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
          </div>
          <span>Monitoring Progres Project & Checklist Menu</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
          Pantau persentase kemajuan pengerjaan, garis progress 100%, serta checklist setiap menu modul aplikasi.
        </p>
      </div>

      <!-- Quick Action Buttons -->
      <div class="flex items-center gap-2.5">
        <button
          type="button"
          onclick={loadProgressData}
          class="min-h-[36px] px-3.5 py-1.5 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-2xs flex items-center gap-1.5 cursor-pointer"
        >
          <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>Segarkan Data</span>
        </button>

        <a
          href="/admin/clients"
          class="min-h-[36px] px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-md shadow-indigo-600/30 flex items-center gap-1.5 cursor-pointer"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
          </svg>
          <span>Kelola Proyek & Klien</span>
        </a>
      </div>
    </div>

    <!-- Top KPI Stat Cards Progres (L1 Macro Surface) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
      <!-- Card 1: Total Project -->
      <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-2xs">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Project</span>
          <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
          </div>
        </div>
        <p class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">
          {metrics.totalProjects} <span class="text-xs font-sans text-slate-500 font-normal">Project</span>
        </p>
        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">Semua portofolio aktif & selesai</p>
      </div>

      <!-- Card 2: Rata-Rata Progres -->
      <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-2xs">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Rata-Rata Progres</span>
          <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
        </div>
        <p class="text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1 font-mono">
          {metrics.avgProgress}%
        </p>
        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 font-semibold">Tingkat penyelesaian keseluruhan</p>
      </div>

      <!-- Card 3: Project 100% Selesai -->
      <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-2xs">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Proyek 100% Selesai</span>
          <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <p class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
          {metrics.completedProjects} <span class="text-xs font-sans text-slate-500 font-normal">Project</span>
        </p>
        <p class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-1 font-semibold">Checklist tuntas siap deploy</p>
      </div>

      <!-- Card 4: Total Modul Selesai -->
      <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-2xs">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Modul Selesai</span>
          <div class="w-8 h-8 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
        </div>
        <p class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">
          {metrics.totalModulesDone} <span class="text-xs font-sans text-slate-400 font-normal">/ {metrics.totalModules} Modul</span>
        </p>
        <p class="text-[10px] text-purple-600 dark:text-purple-400 mt-1 font-semibold">Menu ter-checklist & bertanggal</p>
      </div>
    </div>

    <!-- Filter Segmented Tabs (Rule-14: Responsive Wrap Anti-Hidden) -->
    <div class="flex flex-wrap items-center gap-1.5 p-1 bg-slate-200/60 dark:bg-slate-900/60 rounded-xl border border-slate-200 dark:border-slate-800">
      <button
        type="button"
        onclick={() => (activeTab = 'all')}
        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer {activeTab === 'all' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'}"
      >
        Semua Proyek ({projectsWithProgress.length})
      </button>
      <button
        type="button"
        onclick={() => (activeTab = 'development')}
        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer {activeTab === 'development' ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'}"
      >
        Development ({projectsWithProgress.filter(p => p.project_type === 'development').length})
      </button>
      <button
        type="button"
        onclick={() => (activeTab = 'maintenance')}
        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer {activeTab === 'maintenance' ? 'bg-white dark:bg-slate-800 text-amber-600 dark:text-amber-400 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'}"
      >
        Maintenance ({projectsWithProgress.filter(p => p.project_type === 'maintenance').length})
      </button>
      <button
        type="button"
        onclick={() => (activeTab = 'completed')}
        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer {activeTab === 'completed' ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'}"
      >
        100% Selesai ({metrics.completedProjects})
      </button>
      <button
        type="button"
        onclick={() => (activeTab = 'in_progress')}
        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer {activeTab === 'in_progress' ? 'bg-white dark:bg-slate-800 text-purple-600 dark:text-purple-400 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'}"
      >
        Dalam Pengerjaan ({projectsWithProgress.length - metrics.completedProjects})
      </button>
    </div>

    <!-- Main Table Section (L1 Macro Surface) -->
    <div class="gsap-table">
      <DataTable
        items={tabFilteredProjects}
        pageSize={10}
        searchPlaceholder="Cari project, client, atau bidang aplikasi..."
        searchFields={['app_name', 'project_name', '_clientName', '_industry', 'lead_pic']}
        {loading}
        {error}
        onretry={loadProgressData}
        emptyMessage="Tidak ada data project yang sesuai dengan filter."
        enableExport={true}
        exportFilename="valora_progres_project"
        exportColumns={csvColumns}
      >
        <!-- Desktop Table Header (Fill View: 6 Proporsional Kolom Anti-Scroll) -->
        {#snippet header()}
          <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider">
            <th class="py-3 px-3 text-center w-12 font-bold">No</th>
            <th class="py-3 px-3 font-bold w-[28%]">Project & Klien</th>
            <th class="py-3 px-3 font-bold w-[18%]">Lead PIC</th>
            <th class="py-3 px-3 font-bold w-[26%]">Progress Pengerjaan Menuju 100%</th>
            <th class="py-3 px-3 font-bold w-[12%]">Status</th>
            <th class="py-3 px-3 text-right font-bold w-[16%]">Aksi Proyek</th>
          </tr>
        {/snippet}

        <!-- Desktop Table Row (Fill View) -->
        {#snippet row(project, idx)}
          <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors border-b border-slate-100 dark:border-slate-800/60">
            <td class="py-3 px-3 text-center font-bold text-slate-400 text-xs font-mono">{idx + 1}</td>
            <td class="py-3 px-3">
              <div class="flex items-center gap-1.5 mb-1 flex-wrap">
                {#if project.project_type === 'maintenance'}
                  <span class="px-1.5 py-0.5 rounded text-[9.5px] font-extrabold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                    Maintenance
                  </span>
                {:else}
                  <span class="px-1.5 py-0.5 rounded text-[9.5px] font-extrabold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                    Development
                  </span>
                {/if}
                <span class="font-bold text-slate-900 dark:text-white text-xs">{project.app_name}</span>
              </div>
              <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5 flex-wrap">
                <span class="font-semibold text-slate-700 dark:text-slate-300">{project.client?.name || project.name}</span>
                <span>•</span>
                <span class="px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 text-[10px]">{project.client?.industry || project.industry || 'Umum'}</span>
              </div>
            </td>
            <td class="py-3 px-3 text-slate-600 dark:text-slate-300 font-medium text-xs">
              <div class="inline-flex items-center gap-1.5">
                <div class="w-6 h-6 rounded-full bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                </div>
                <div>
                  <span class="font-bold text-slate-800 dark:text-slate-200 block text-xs">{project.lead_pic_user?.name || project.lead_pic || 'Tim Valora'}</span>
                  {#if project.workers && project.workers.length > 0}
                    <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-medium block">
                      +{project.workers.length} Pekerja
                    </span>
                  {:else}
                    <span class="text-[10px] text-slate-400 block">Penanggung Jawab</span>
                  {/if}
                </div>
              </div>
            </td>
            <td class="py-3 px-3">
              <div class="space-y-1.5">
                <div class="flex items-center justify-between gap-2">
                  <span class="text-[10.5px] font-semibold text-slate-600 dark:text-slate-300">
                    {project._progress.done} / {project._progress.total} Modul Selesai
                  </span>
                  <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 font-mono">
                    {project._progress.percent}%
                  </span>
                </div>
                <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                  <div
                    class="h-full bg-gradient-to-r from-indigo-500 to-emerald-500 rounded-full transition-all duration-300"
                    style="width: {project._progress.percent}%;"
                  ></div>
                </div>
              </div>
            </td>
            <td class="py-3 px-3">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border inline-block {project.status === 'Aktif' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' : project.status === 'Selesai' ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20' : 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20'}">
                {project.status || 'Aktif'}
              </span>
              <span class="text-[10px] text-slate-400 block mt-0.5">
                {project._progress.percent === 100 ? 'Siap Deploy' : 'Dalam Pengerjaan'}
              </span>
            </td>
            <td class="py-3 px-3 text-right">
              <div class="flex items-center justify-end gap-1.5 flex-wrap">
                <button
                  type="button"
                  onclick={() => openChecklistModal(project)}
                  class="px-2.5 py-1 rounded-xl bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 font-bold text-xs transition cursor-pointer"
                >
                  Lihat Checklist
                </button>
                <button
                  type="button"
                  onclick={() => openProjectDetail(project)}
                  class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition cursor-pointer"
                >
                  Detail
                </button>
              </div>
            </td>
          </tr>
        {/snippet}

        <!-- Mobile Card View Snippet (RULE-12 & RULE-08) -->
        {#snippet card(project, idx)}
          <div class="space-y-2.5">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-1.5">
                {#if project.project_type === 'maintenance'}
                  <span class="px-2 py-0.5 rounded text-[9px] font-extrabold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                    Maintenance
                  </span>
                {:else}
                  <span class="px-2 py-0.5 rounded text-[9px] font-extrabold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                    Development
                  </span>
                {/if}
                <span class="text-xs font-bold text-slate-900 dark:text-white truncate max-w-[180px]">
                  {project.client?.name || project.name}
                </span>
              </div>
              <span class="text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400">
                {project._progress.percent}%
              </span>
            </div>

            <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">
              {project.app_name}
            </p>

            <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
              <div
                class="h-full bg-gradient-to-r from-indigo-500 to-emerald-500 rounded-full"
                style="width: {project._progress.percent}%;"
              ></div>
            </div>

            <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 pt-1 border-t border-slate-100 dark:border-slate-800">
              <span>{project._progress.done} / {project._progress.total} Modul</span>
              <span class="font-medium text-slate-700 dark:text-slate-300">
                {project.lead_pic_user?.name || project.lead_pic || 'Tim Valora'}
                {#if project.workers && project.workers.length > 0}
                  <span class="text-indigo-600 dark:text-indigo-400 font-bold">(+{project.workers.length})</span>
                {/if}
              </span>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-1">
              <button
                type="button"
                onclick={() => openChecklistModal(project)}
                class="min-h-[30px] px-2.5 py-1 rounded-lg bg-indigo-600 text-white text-[11px] font-bold shadow-xs hover:bg-indigo-500 transition text-center cursor-pointer"
              >
                Lihat Checklist
              </button>
              <button
                type="button"
                onclick={() => openProjectDetail(project)}
                class="min-h-[30px] px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-[11px] font-bold transition text-center cursor-pointer"
              >
                Detail Proyek
              </button>
            </div>
          </div>
        {/snippet}
      </DataTable>
    </div>
  </div>
</div>

<!-- Modal 1: Interactive Progress Checklist Modal -->
<ProgressChecklistModal
  show={showChecklistModal}
  project={selectedChecklistProject}
  onclose={() => (showChecklistModal = false)}
  onopendetail={(p) => openProjectDetail(p)}
  ontogglemenu={(menu, roleName) => handleToggleMenu(menu, roleName)}
/>

<!-- Modal 2: Confirmation Date & Technician for Done Menu -->
<MenuCompletionModal
  show={showCompletionModal}
  menu={selectedMenuForCompletion}
  roleName={completionRoleName}
  onclose={() => (showCompletionModal = false)}
  onconfirm={handleConfirmMenuCompletion}
  submitting={submittingCompletion}
/>

<!-- Modal 3: Full Scope Project Detail & Setting Modal -->
<ClientDetailSettingModal
  show={showDetailModal}
  projectId={selectedDetailProjectId}
  onclose={() => (showDetailModal = false)}
  onchange={loadProgressData}
/>
