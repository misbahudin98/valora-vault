<script>
  import Modal from '$lib/components/ui/Modal.svelte';

  let {
    show = false,
    project = null,
    zIndex = 'z-50',
    onclose = () => {},
    onopendetail = () => {},
    ontogglemenu = () => {}
  } = $props();

  let searchQuery = $state('');

  // Hitung progres real-time
  let progressStats = $derived.by(() => {
    if (!project) return { percent: 0, total: 0, done: 0 };
    let total = 0;
    let done = 0;
    (project.roles || []).forEach(role => {
      (role.menus || []).forEach(menu => {
        total++;
        if (menu.is_done) done++;
      });
    });
    const percent = total > 0 ? Math.round((done / total) * 100) : 0;
    return { percent, total, done };
  });

  // Filter menu berdasarkan pencarian
  let filteredRoles = $derived.by(() => {
    if (!project?.roles) return [];
    const query = searchQuery.toLowerCase().trim();
    if (!query) return project.roles;

    return project.roles.map(role => {
      const filteredMenus = (role.menus || []).filter(menu => {
        return (
          menu.menu_name?.toLowerCase().includes(query) ||
          menu.menu_desc?.toLowerCase().includes(query) ||
          role.role_name?.toLowerCase().includes(query) ||
          menu.pic?.toLowerCase().includes(query)
        );
      });
      return {
        ...role,
        menus: filteredMenus
      };
    }).filter(role => role.menus.length > 0);
  });

  const capabilityLabels = [
    { key: 'has_create', label: 'C', title: 'Create' },
    { key: 'has_read', label: 'R', title: 'Read' },
    { key: 'has_update', label: 'U', title: 'Update' },
    { key: 'has_delete', label: 'D', title: 'Delete' },
    { key: 'has_grafik', label: 'Chart', title: 'Grafik Analitik' },
    { key: 'has_box_dashboard', label: 'Box', title: 'Box Dashboard' },
    { key: 'has_import', label: 'Imp', title: 'Import (Excel, CSV)' },
    { key: 'has_export', label: 'Exp', title: 'Export (Excel, CSV, PDF)' },
    { key: 'has_filter', label: 'Flt', title: 'Filter Data' },
    { key: 'has_print', label: 'Prt', title: 'Print' },
  ];
</script>

<Modal
  {show}
  title={project?.app_name || 'Rincian Checklist Progres'}
  subtitle={project?.client?.name || 'Checklist modul dan peran aplikasi'}
  maxWidth="max-w-2xl"
  {zIndex}
  {onclose}
>
  {#snippet header()}
    <div class="flex items-center justify-between w-full pr-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
          </svg>
        </div>
        <div>
          <h3 class="text-base font-bold text-slate-900 dark:text-white">
            {project?.app_name || 'Monitoring Progres'}
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            {project?.client?.name || 'Klien Mitra'} — Checklist Pengerjaan Modul
          </p>
        </div>
      </div>
      <button
        type="button"
        onclick={() => {
          onclose();
          onopendetail(project);
        }}
        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900 text-indigo-700 dark:text-indigo-300 text-xs font-bold border border-indigo-200 dark:border-indigo-800 transition cursor-pointer"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
        </svg>
        <span>Detail Proyek</span>
      </button>
    </div>
  {/snippet}

  <div class="space-y-4">
    <!-- Progress Indicator Bar & Metrics -->
    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 space-y-2.5">
      <!-- Team PIC & Workers Info -->
      <div class="flex items-center justify-between pb-2 border-b border-slate-200/80 dark:border-slate-800 text-xs">
        <div class="flex items-center gap-2 flex-wrap">
          <span class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1">
            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span>Lead PIC:</span>
            <span class="text-indigo-600 dark:text-indigo-400 font-extrabold">{project?.lead_pic_user?.name || project?.lead_pic || 'Tim Valora'}</span>
          </span>
          {#if project?.workers && project.workers.length > 0}
            <span class="text-slate-300 dark:text-slate-600">•</span>
            <span class="text-[11px] text-slate-500 dark:text-slate-400">Tim Pekerja:</span>
            <div class="flex items-center gap-1 flex-wrap">
              {#each project.workers as worker}
                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-200/80 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                  {worker.name}
                </span>
              {/each}
            </div>
          {/if}
        </div>
      </div>

      <div class="flex items-center justify-between text-xs">
        <span class="font-bold text-slate-700 dark:text-slate-300">
          Progres Keseluruhan Menuju 100%
        </span>
        <div class="flex items-center gap-2">
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
            {progressStats.done} / {progressStats.total} Modul Selesai
          </span>
          <span class="text-xs font-mono font-bold text-slate-900 dark:text-white">
            {progressStats.percent}%
          </span>
        </div>
      </div>
      <div class="w-full bg-slate-200 dark:bg-slate-800 h-2.5 rounded-full overflow-hidden">
        <div
          class="h-full bg-gradient-to-r from-indigo-500 via-indigo-600 to-emerald-500 rounded-full transition-all duration-300"
          style="width: {progressStats.percent}%;"
        ></div>
      </div>
    </div>

    <!-- Search in checklist -->
    <div class="relative">
      <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
      <input
        type="text"
        bind:value={searchQuery}
        placeholder="Filter modul atau peran di proyek ini..."
        class="w-full pl-9 pr-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      />
    </div>

    <!-- Grouped Checklist Container -->
    <div class="max-h-[60vh] overflow-y-auto space-y-4 pr-1">
      {#if filteredRoles.length === 0}
        <div class="p-8 text-center bg-slate-50 dark:bg-slate-950/40 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800">
          <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
            Tidak ada modul yang cocok dengan pencarian.
          </p>
        </div>
      {:else}
        {#each filteredRoles as role}
          <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 space-y-2">
            <div class="flex items-center justify-between pb-1.5 border-b border-slate-200 dark:border-slate-800">
              <span class="text-xs font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>Role: {role.role_name}</span>
              </span>
              <span class="text-[10px] text-slate-400 font-mono">
                {(role.menus || []).filter(m => m.is_done).length} / {(role.menus || []).length} Selesai
              </span>
            </div>

            <div class="space-y-2">
              {#each (role.menus || []) as menu}
                <div
                  class="p-2.5 rounded-xl border transition flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 {menu.is_done ? 'bg-emerald-50/50 dark:bg-emerald-950/20 border-emerald-500/30' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800'}"
                >
                  <div class="flex items-start gap-2.5 min-w-0">
                    <button
                      type="button"
                      onclick={() => ontogglemenu(menu, role.role_name)}
                      class="mt-0.5 w-5 h-5 rounded-md flex items-center justify-center transition shrink-0 cursor-pointer {menu.is_done ? 'bg-emerald-500 text-white shadow-xs' : 'border border-slate-300 dark:border-slate-600 hover:border-emerald-500'}"
                      aria-label="Tandai status selesai"
                    >
                      {#if menu.is_done}
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                      {/if}
                    </button>
                    <div class="min-w-0">
                      <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-xs font-bold text-slate-900 dark:text-white truncate">
                          {menu.menu_name}
                        </span>
                        {#if menu.is_done}
                          <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                            Selesai: {menu.completed_date || 'Hari ini'}
                          </span>
                        {:else}
                          <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-800">
                            Pending
                          </span>
                        {/if}
                      </div>
                      {#if menu.menu_desc}
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">
                          {menu.menu_desc}
                        </p>
                      {/if}

                      <!-- 10 Feature Capabilities Pill Indicators -->
                      <div class="flex items-center gap-1 mt-1.5 flex-wrap">
                        {#each capabilityLabels as cap}
                          {#if menu[cap.key]}
                            <span
                              title={cap.title}
                              class="px-1 py-0.2 rounded text-[9px] font-mono font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 cursor-help"
                            >
                              {cap.label}
                            </span>
                          {/if}
                        {/each}
                      </div>
                    </div>
                  </div>

                  <div class="flex items-center justify-between sm:justify-end gap-2 shrink-0 pt-1.5 sm:pt-0 border-t sm:border-t-0 border-slate-100 dark:border-slate-800">
                    <span class="inline-flex items-center gap-1 text-[11px] text-slate-500 dark:text-slate-400">
                      <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                      <span>{menu.pic_user?.name || menu.pic || project?.lead_pic_user?.name || project?.lead_pic || 'Tim Valora'}</span>
                    </span>

                    <button
                      type="button"
                      onclick={() => ontogglemenu(menu, role.role_name)}
                      class="px-2 py-1 rounded-lg text-[10px] font-bold transition cursor-pointer {menu.is_done ? 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-300' : 'bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 hover:bg-emerald-100'}"
                    >
                      {menu.is_done ? 'Batal Selesai' : 'Tandai Selesai'}
                    </button>
                  </div>
                </div>
              {/each}
            </div>
          </div>
        {/each}
      {/if}
    </div>
  </div>

  {#snippet footer()}
    <div class="flex items-center justify-between w-full">
      <button
        type="button"
        onclick={() => {
          onclose();
          onopendetail(project);
        }}
        class="sm:hidden px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 text-xs font-bold border border-indigo-200 dark:border-indigo-800"
      >
        Detail Proyek
      </button>
      <div class="flex items-center gap-2 ml-auto">
        <button
          type="button"
          onclick={onclose}
          class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
        >
          Tutup Checklist
        </button>
      </div>
    </div>
  {/snippet}
</Modal>
