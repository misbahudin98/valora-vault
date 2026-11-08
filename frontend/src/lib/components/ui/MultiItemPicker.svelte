<script>
  /**
   * Enterprise Multi-Item Picker Component (Pure Svelte 5 Runes)
   * High-density, senior-accessible UI for selecting multiple records (members, roles, items, permissions).
   * 
   * Props:
   * - label: string (optional field label)
   * - selectedValues: array of selected IDs (bindable)
   * - items: array of { id: any, label: string, sublabel?: string, badge?: string, disabled?: boolean, disabledReason?: string }
   * - placeholder: string (search placeholder)
   * - maxHeight: string (Tailwind max-h class, default 'max-h-60')
   * - maxItems: number (optional maximum capacity)
   * - disabled: boolean
   * - onchange: function(selectedValues)
   */
  let {
    label = '',
    selectedValues = $bindable([]),
    items = [],
    placeholder = 'Cari item...',
    maxHeight = 'max-h-60',
    maxItems = null,
    disabled = false,
    onchange = () => {}
  } = $props();

  let searchTerm = $state('');

  // Derived filtered items based on search input
  let filteredItems = $derived.by(() => {
    if (!searchTerm.trim()) return items;
    const term = searchTerm.toLowerCase();
    return items.filter(item => {
      const matchLabel = String(item.label || '').toLowerCase().includes(term);
      const matchSub = String(item.sublabel || '').toLowerCase().includes(term);
      const matchBadge = String(item.badge || '').toLowerCase().includes(term);
      return matchLabel || matchSub || matchBadge;
    });
  });

  // Set lookup for O(1) membership check
  let selectedSet = $derived.by(() => new Set(selectedValues.map(v => String(v))));

  function toggleItem(item) {
    if (disabled || item.disabled) return;

    const valStr = String(item.id);
    let next;
    if (selectedSet.has(valStr)) {
      next = selectedValues.filter(v => String(v) !== valStr);
    } else {
      if (maxItems && selectedValues.length >= maxItems) {
        return;
      }
      next = [...selectedValues, item.id];
    }

    selectedValues = next;
    onchange(next);
  }

  function selectAllFiltered() {
    if (disabled) return;
    const availableFiltered = filteredItems.filter(item => !item.disabled);
    const newIds = new Set(selectedValues.map(v => String(v)));
    
    for (const item of availableFiltered) {
      if (maxItems && newIds.size >= maxItems) break;
      newIds.add(String(item.id));
    }

    const next = items.filter(item => newIds.has(String(item.id))).map(item => item.id);
    selectedValues = next;
    onchange(next);
  }

  function clearAll() {
    if (disabled) return;
    selectedValues = [];
    onchange([]);
  }
</script>

<div class="w-full space-y-2">
  {#if label}
    <div class="flex items-center justify-between text-xs sm:text-sm font-bold text-stone-900 dark:text-white">
      <span>{label}</span>
      <span class="text-[11px] font-mono font-medium px-2 py-0.5 rounded-md bg-stone-100 dark:bg-slate-800 text-stone-700 dark:text-slate-300 border border-stone-200 dark:border-slate-700">
        {selectedValues.length} {maxItems ? `/ ${maxItems}` : ''} dipilih
      </span>
    </div>
  {/if}

  <!-- Main Picker Container -->
  <div class="border border-stone-300 dark:border-slate-700 rounded-xl bg-white dark:bg-[#090d16] overflow-hidden shadow-xs">
    <!-- Header: Search Filter & Batch Actions -->
    <div class="p-2 sm:p-2.5 bg-stone-50 dark:bg-slate-900 border-b border-stone-200 dark:border-slate-800 flex flex-col sm:flex-row gap-2 items-stretch sm:items-center justify-between">
      <div class="relative flex-1">
        <svg class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-stone-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          type="text"
          bind:value={searchTerm}
          {placeholder}
          {disabled}
          class="w-full pl-8 pr-6 py-1.5 text-xs rounded-lg border border-stone-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-stone-900 dark:text-white placeholder-stone-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
        />
        {#if searchTerm}
          <button
            type="button"
            onclick={() => searchTerm = ''}
            class="absolute right-2 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-700 dark:hover:text-white text-xs cursor-pointer p-0.5"
            aria-label="Hapus filter"
          >
            ✕
          </button>
        {/if}
      </div>

      <div class="flex items-center gap-1.5 shrink-0 self-end sm:self-auto">
        <button
          type="button"
          onclick={selectAllFiltered}
          disabled={disabled || filteredItems.filter(i => !i.disabled).length === 0}
          class="px-2.5 py-1 text-[11px] font-bold rounded-md bg-stone-200 hover:bg-stone-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-stone-800 dark:text-stone-200 transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
        >
          Pilih Semua
        </button>
        <button
          type="button"
          onclick={clearAll}
          disabled={disabled || selectedValues.length === 0}
          class="px-2.5 py-1 text-[11px] font-bold rounded-md bg-stone-200 hover:bg-stone-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-stone-800 dark:text-stone-200 transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
        >
          Hapus Pilihan
        </button>
      </div>
    </div>

    <!-- Items Scrollable List -->
    <div class="overflow-y-auto {maxHeight} divide-y divide-stone-100 dark:divide-slate-800/60 p-1">
      {#if filteredItems.length === 0}
        <div class="py-8 text-center text-xs text-stone-500 dark:text-slate-400">
          Tidak ada item yang sesuai pencarian.
        </div>
      {:else}
        {#each filteredItems as item (item.id)}
          {@const isSelected = selectedSet.has(String(item.id))}
          {@const isDisabled = disabled || (item.disabled && !isSelected)}
          <label
            class="flex items-center gap-2.5 px-3 py-2 rounded-lg transition-colors cursor-pointer select-none {isDisabled ? 'opacity-50 bg-stone-50/50 dark:bg-slate-900/30 cursor-not-allowed' : isSelected ? 'bg-indigo-50/80 dark:bg-indigo-950/40' : 'hover:bg-stone-50 dark:hover:bg-slate-900/50'}"
          >
            <input
              type="checkbox"
              checked={isSelected}
              disabled={isDisabled}
              onchange={() => toggleItem(item)}
              class="w-4 h-4 rounded border-stone-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 shrink-0 cursor-pointer disabled:cursor-not-allowed"
            />
            <div class="flex-1 min-w-0 flex items-center justify-between gap-2">
              <div class="min-w-0">
                <span class="text-xs sm:text-[13px] font-bold text-stone-900 dark:text-white truncate block">
                  {item.label}
                </span>
                {#if item.sublabel}
                  <span class="text-[11px] text-stone-500 dark:text-slate-400 truncate block">
                    {item.sublabel}
                  </span>
                {/if}
              </div>

              <div class="flex items-center gap-1.5 shrink-0">
                {#if item.badge}
                  <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-stone-100 text-stone-700 dark:bg-slate-800 dark:text-slate-300 border border-stone-200 dark:border-slate-700">
                    {item.badge}
                  </span>
                {/if}
                {#if item.disabled && item.disabledReason}
                  <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                    {item.disabledReason}
                  </span>
                {/if}
              </div>
            </div>
          </label>
        {/each}
      {/if}
    </div>
  </div>
</div>
