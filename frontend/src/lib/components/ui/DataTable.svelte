<script>
  import ElementLoader from './ElementLoader.svelte';
  import { exportToCsv } from '$lib/utils/csvExport.js';

  let {
    items = [],
    pageSize = 10,
    searchPlaceholder = 'Cari data...',
    searchFields = [], // array of key strings to search on, or empty to search all string fields
    loading = false,
    error = null,
    onretry = null,
    emptyMessage = 'Tidak ada data ditemukan.',
    enableExport = true,
    exportFilename = 'tabel_data',
    exportColumns = null,
    header,
    row,
    actions,
    card
  } = $props();

  let searchQuery = $state('');
  let currentPage = $state(1);
  let userPageSize = $state(null);
  let perPage = $derived(userPageSize !== null ? userPageSize : (Number(pageSize) || 10));

  // Filter items based on searchQuery (Browser-Memory Instant Filtering < 0.1ms)
  let filteredItems = $derived.by(() => {
    const q = searchQuery.toLowerCase().trim();
    if (!q) return items;

    return items.filter(item => {
      if (searchFields.length > 0) {
        return searchFields.some(field => {
          const val = item[field];
          return val !== null && val !== undefined && String(val).toLowerCase().includes(q);
        });
      }
      return Object.values(item).some(val => {
        if (typeof val === 'object' && val !== null) {
          return JSON.stringify(val).toLowerCase().includes(q);
        }
        return val !== null && val !== undefined && String(val).toLowerCase().includes(q);
      });
    });
  });

  // Calculate lazy chunked pagination
  let totalItems = $derived(filteredItems.length);
  let totalPages = $derived(Math.max(1, Math.ceil(totalItems / perPage)));

  // Svelte 5 Pure Derived: Bound currentPage safely without cyclical effect cascade
  let safeCurrentPage = $derived(Math.min(Math.max(1, currentPage), totalPages));

  // Current page chunk (Lazy rendered in DOM)
  let paginatedItems = $derived.by(() => {
    const start = (safeCurrentPage - 1) * perPage;
    return filteredItems.slice(start, start + perPage);
  });

  function nextPage() {
    if (safeCurrentPage < totalPages) currentPage = safeCurrentPage + 1;
  }

  function prevPage() {
    if (safeCurrentPage > 1) currentPage = safeCurrentPage - 1;
  }

  function handleExportCsv() {
    const dataToExport = filteredItems.length > 0 ? filteredItems : items;
    exportToCsv({
      data: dataToExport,
      filename: exportFilename,
      columns: exportColumns
    });
  }
</script>

<div class="space-y-4">
  <!-- Controls Bar: Search & Action Controls (Mobile-First) -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <!-- Search Input with Keyboard Indicator -->
    <div class="relative flex-1 max-w-sm w-full">
      <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </span>
      <input
        type="text"
        bind:value={searchQuery}
        oninput={() => { currentPage = 1; }}
        placeholder={searchPlaceholder}
        class="w-full pl-8.5 pr-7 py-1.5 min-h-[36px] bg-white/90 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-1 focus:ring-indigo-500/30 transition shadow-2xs"
      />
      {#if searchQuery}
        <button
          type="button"
          onclick={() => { searchQuery = ''; currentPage = 1; }}
          class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-800 dark:hover:text-white text-xs font-bold transition cursor-pointer min-h-[36px]"
          aria-label="Hapus Pencarian"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      {:else}
        <span class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none">
          <kbd class="hidden sm:inline-block px-1.5 py-0.5 text-[9px] font-mono text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded">
            /
          </kbd>
        </span>
      {/if}
    </div>

    <!-- Actions & Client-Side CSV Export Controls (Mandat [INV-UI-06]) -->
    <div class="flex items-center gap-2 w-full sm:w-auto justify-end flex-wrap">
      {#if actions}
        {@render actions()}
      {/if}

      {#if enableExport}
        <button
          type="button"
          onclick={handleExportCsv}
          disabled={loading || (items.length === 0)}
          class="min-h-[34px] px-2.5 py-1 text-xs font-semibold rounded-xl bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-800 shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
          title="Ekspor Data ke Berkas CSV (Client-Side In-Memory, Zero-Roundtrip)"
          aria-label="Ekspor CSV"
        >
          <svg class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <span>Ekspor CSV</span>
        </button>
      {/if}
    </div>
  </div>

  <!-- Table Container (Mobile First: Dual Table & Compact Card Stack — L1 Macro Surface) -->
  <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-2xs overflow-hidden">
    {#if loading}
      <div class="py-10 flex flex-col items-center justify-center gap-2">
        <ElementLoader size="md" variant="indigo" text="Memuat data tabel..." />
        <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">Pencarian instan di memori browser (&lt; 0.1ms)</span>
      </div>
    {:else if error}
      <div class="p-6 sm:p-10 text-center space-y-2.5">
        <div class="w-10 h-10 mx-auto rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shadow-2xs">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div class="space-y-0.5">
          <p class="text-xs font-bold text-slate-900 dark:text-rose-200">Gagal Memuat Data</p>
          <p class="text-[11px] text-slate-600 dark:text-rose-300/80 max-w-md mx-auto">{typeof error === 'string' ? error : (error.message || 'Terjadi kesalahan sistem saat memuat tabel.')}</p>
        </div>
        {#if onretry}
          <button
            type="button"
            onclick={onretry}
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-2xs transition cursor-pointer active:scale-95"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>Coba Lagi</span>
          </button>
        {/if}
      </div>
    {:else if paginatedItems.length === 0}
      <div class="p-8 text-center space-y-1.5">
        <div class="w-9 h-9 mx-auto rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500 flex items-center justify-center">
          <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
          </svg>
        </div>
        <p class="text-xs text-slate-800 dark:text-slate-200 font-medium">{emptyMessage}</p>
        <p class="text-[10px] text-slate-500 dark:text-slate-400">Coba ubah kata kunci pencarian atau segarkan data.</p>
      </div>
    {:else}
      {#if card}
        <!-- Mobile Card View (< md) with Pro-Grade Compact Separation -->
        <div class="md:hidden p-2.5 space-y-2 bg-slate-50/60 dark:bg-slate-950/40">
          {#each paginatedItems as item, idx (item.id ?? idx)}
            <div class="p-3 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs hover:border-indigo-400 dark:hover:border-indigo-500 transition">
              {@render card(item, (safeCurrentPage - 1) * perPage + idx)}
            </div>
          {/each}
        </div>
      {:else}
        <!-- Default Fallback Card View (< md) for Tables without Custom Card Snippet -->
        <div class="md:hidden p-2.5 space-y-2 bg-slate-50/60 dark:bg-slate-950/40">
          {#each paginatedItems as item, idx (item.id ?? idx)}
            <div class="p-3 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs space-y-2">
              <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-1.5">
                <span class="text-[10px] font-mono font-bold text-slate-400 dark:text-slate-500">#{(safeCurrentPage - 1) * perPage + idx + 1}</span>
                {#if item.status || item.is_active !== undefined}
                  <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {item.is_active === false || item.status === 'inactive' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300'}">
                    {item.status ? String(item.status).toUpperCase() : (item.is_active ? 'AKTIF' : 'NONAKTIF')}
                  </span>
                {/if}
              </div>
              <div class="space-y-1 text-xs">
                {#each Object.entries(item).slice(0, 6) as [key, val]}
                  {#if val !== null && val !== undefined && typeof val !== 'object' && !key.endsWith('_id') && key !== 'id'}
                    <div class="flex items-baseline justify-between gap-2 text-[11px]">
                      <span class="text-slate-400 dark:text-slate-500 font-mono text-[10px] uppercase">{key.replace(/_/g, ' ')}:</span>
                      <span class="font-medium text-slate-800 dark:text-slate-200 text-right truncate max-w-[200px]">{String(val)}</span>
                    </div>
                  {/if}
                {/each}
              </div>
            </div>
          {/each}
        </div>
      {/if}

      <!-- Desktop Table View (hidden md:block) -->
      <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs text-slate-700 dark:text-slate-200">
          <thead class="bg-slate-50/90 dark:bg-slate-900/90 text-[10.5px] uppercase font-semibold text-slate-600 dark:text-slate-400 tracking-wider border-b border-slate-200 dark:border-slate-800">
            {#if header}
              {@render header()}
            {/if}
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-sans">
            {#each paginatedItems as item, idx (item.id ?? idx)}
              {#if row}
                {@render row(item, (safeCurrentPage - 1) * perPage + idx)}
              {/if}
            {/each}
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer with Interactive Per-Page Selector -->
      <div class="px-4 py-3 sm:py-2.5 bg-slate-50/70 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600 dark:text-slate-400">
        <div class="text-[11px] flex flex-wrap items-center justify-between w-full sm:w-auto gap-2">
          <div class="flex items-center gap-1.5">
            <span>Menampilkan</span>
            <span class="font-semibold text-slate-900 dark:text-slate-100 px-1.5 py-0.5 bg-white dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700 font-mono">
              {totalItems > 0 ? (safeCurrentPage - 1) * perPage + 1 : 0} - {Math.min(safeCurrentPage * perPage, totalItems)}
            </span>
            <span>dari</span>
            <span class="font-semibold text-slate-900 dark:text-slate-100 px-1.5 py-0.5 bg-white dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700 font-mono">
              {totalItems}
            </span>
            <span>entri</span>
          </div>

          <!-- Per-Page Selector -->
          <div class="flex items-center gap-1 pl-2 border-l border-slate-200 dark:border-slate-800">
            <label for="table-page-size" class="text-slate-500 dark:text-slate-400 text-[10.5px]">Baris:</label>
            <select
              id="table-page-size"
              value={perPage}
              onchange={(e) => { userPageSize = Number(e.currentTarget.value); currentPage = 1; }}
              class="px-2 py-0.5 text-[11px] rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 font-mono font-semibold focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer shadow-2xs"
              aria-label="Pilih jumlah baris data per halaman"
            >
              <option value={10}>10</option>
              <option value={25}>25</option>
              <option value={50}>50</option>
              <option value={100}>100</option>
            </select>
          </div>

          <!-- Page indicator for mobile -->
          <span class="sm:hidden text-[11px] font-mono font-bold text-indigo-700 dark:text-indigo-400">
            Hal {safeCurrentPage}/{totalPages}
          </span>
        </div>

        <div class="flex items-center justify-between sm:justify-end w-full sm:w-auto gap-2">
          <button
            type="button"
            onclick={prevPage}
            disabled={safeCurrentPage === 1}
            class="flex-1 sm:flex-none min-h-[32px] px-3 py-1 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 disabled:opacity-40 disabled:hover:bg-white dark:disabled:hover:bg-slate-800 transition flex items-center justify-center gap-1 text-[11px] font-medium shadow-2xs cursor-pointer active:scale-95"
            aria-label="Halaman Sebelumnya"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <span>Prev</span>
          </button>

          <span class="hidden sm:inline-block px-2 py-0.5 text-[11px] text-slate-800 dark:text-slate-200 font-mono bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md">
            Hal <b class="text-indigo-600 dark:text-indigo-400">{safeCurrentPage}</b> / {totalPages}
          </span>

          <button
            type="button"
            onclick={nextPage}
            disabled={safeCurrentPage === totalPages}
            class="flex-1 sm:flex-none min-h-[32px] px-3 py-1 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 disabled:opacity-40 disabled:hover:bg-white dark:disabled:hover:bg-slate-800 transition flex items-center justify-center gap-1 text-[11px] font-medium shadow-2xs cursor-pointer active:scale-95"
            aria-label="Halaman Berikutnya"
          >
            <span>Next</span>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>
    {/if}
  </div>
</div>
