<script>
  let {
    id = 'select-' + Math.random().toString(36).substring(2, 7),
    label = '',
    value = $bindable(''),
    options = [], // [{ value: any, label: string, sublabel?: string, badge?: string }]
    placeholder = 'Pilih salah satu...',
    searchPlaceholder = 'Ketik untuk mencari...',
    required = false,
    disabled = false,
    helpText = '',
    onchange = () => {}
  } = $props();

  let isOpen = $state(false);
  let searchTerm = $state('');
  let containerRef = $state(null);

  // Derived filtered options based on search query
  let filteredOptions = $derived.by(() => {
    if (!searchTerm.trim()) return options;
    const term = searchTerm.toLowerCase();
    return options.filter(opt => {
      const matchLabel = String(opt.label || '').toLowerCase().includes(term);
      const matchSub = String(opt.sublabel || '').toLowerCase().includes(term);
      const matchVal = String(opt.value || '').toLowerCase().includes(term);
      return matchLabel || matchSub || matchVal;
    });
  });

  // Current selected option object
  let selectedOption = $derived.by(() => {
    return options.find(opt => String(opt.value) === String(value)) || null;
  });

  function toggleOpen() {
    if (disabled) return;
    isOpen = !isOpen;
    if (isOpen) {
      searchTerm = '';
    }
  }

  function selectOption(opt) {
    value = opt.value;
    isOpen = false;
    searchTerm = '';
    onchange(opt.value, opt);
  }

  function handleKeydown(e) {
    if (e.key === 'Escape') {
      isOpen = false;
    }
  }

  function handleWindowClick(e) {
    if (containerRef && !containerRef.contains(e.target)) {
      isOpen = false;
    }
  }
</script>

<svelte:window onclick={handleWindowClick} onkeydown={handleKeydown} />

<div class="relative w-full" bind:this={containerRef}>
  {#if label}
    <label for={id} class="flex items-center justify-between text-xs sm:text-sm font-bold text-stone-900 dark:text-white mb-1">
      <span>{label}</span>
      {#if required}
        <span class="text-[10.5px] font-bold text-rose-600 bg-rose-50 dark:bg-rose-950/60 px-2 py-0.5 rounded border border-rose-200 dark:border-rose-800">* Wajib</span>
      {:else}
        <span class="text-[10px] text-stone-500 font-normal">(Opsional)</span>
      {/if}
    </label>
  {/if}

  <!-- Main Select Trigger Button -->
  <button
    {id}
    type="button"
    onclick={toggleOpen}
    {disabled}
    aria-haspopup="listbox"
    aria-expanded={isOpen}
    class="w-full text-left min-h-[44px] sm:min-h-[40px] px-3.5 py-2 rounded-xl border transition-all flex items-center justify-between gap-2 shadow-2xs cursor-pointer {disabled ? 'opacity-60 bg-stone-100 cursor-not-allowed border-stone-200' : isOpen ? 'border-indigo-600 ring-2 ring-indigo-500/20 bg-white dark:bg-[#0f172a]' : 'border-stone-300 hover:border-indigo-500 dark:border-stone-700 dark:hover:border-indigo-500 bg-white dark:bg-[#090d16]'}"
  >
    <div class="flex items-center gap-1.5 sm:gap-2 min-w-0 flex-1 flex-wrap">
      {#if selectedOption}
        <span class="font-bold text-xs sm:text-sm text-stone-900 dark:text-white break-words whitespace-normal leading-tight">
          {selectedOption.label}
        </span>
        {#if selectedOption.sublabel}
          <span class="text-[11px] text-stone-500 dark:text-indigo-300/80 break-words whitespace-normal">
            ({selectedOption.sublabel})
          </span>
        {/if}
        {#if selectedOption.badge}
          <span class="px-1.5 py-0.5 rounded text-[9.5px] font-mono font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 shrink-0">
            {selectedOption.badge}
          </span>
        {/if}
      {:else}
        <span class="text-xs sm:text-sm text-stone-400 dark:text-stone-500 break-words whitespace-normal">
          {placeholder}
        </span>
      {/if}
    </div>

    <!-- Chevron Icon -->
    <svg
      class="w-4 h-4 text-stone-500 dark:text-indigo-400 shrink-0 transition-transform duration-200 {isOpen ? 'rotate-180 text-indigo-600' : ''}"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
    >
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
    </svg>
  </button>

  {#if helpText}
    <p class="text-[11px] text-stone-500 dark:text-indigo-400/80 mt-1">{helpText}</p>
  {/if}

  <!-- Dropdown Popover Menu -->
  {#if isOpen}
    <div
      role="listbox"
      aria-label={label || 'Pilihan'}
      class="absolute left-0 right-0 top-full mt-1.5 z-[70] bg-white dark:bg-[#0f172a] border border-indigo-600 dark:border-indigo-500 rounded-xl shadow-2xl overflow-hidden flex flex-col max-h-72 animate-in fade-in zoom-in-95 duration-150 w-full"
    >
      <!-- Search Input Header -->
      <div class="p-2 border-b border-stone-200 dark:border-slate-800 bg-stone-50 dark:bg-[#090d16]">
        <div class="relative">
          <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-stone-400 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="text"
            bind:value={searchTerm}
            placeholder={searchPlaceholder}
            class="w-full pl-9 pr-7 py-2 text-xs sm:text-sm rounded-lg border border-stone-300 dark:border-slate-800 bg-white dark:bg-[#0f172a] text-stone-900 dark:text-white placeholder-stone-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
          />
          {#if searchTerm}
            <button
              type="button"
              onclick={() => searchTerm = ''}
              class="absolute right-2 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-700 dark:hover:text-white text-xs p-1 cursor-pointer"
              aria-label="Bersihkan pencarian"
            >
              ✕
            </button>
          {/if}
        </div>
      </div>

      <!-- Options List -->
      <div class="flex-1 overflow-y-auto p-1 space-y-0.5 divide-y divide-stone-100 dark:divide-slate-800">
        {#if filteredOptions.length === 0}
          <div class="p-4 text-center text-xs text-stone-500 dark:text-slate-400">
            Tidak ditemukan pilihan yang cocok dengan "<b>{searchTerm}</b>"
          </div>
        {:else}
          {#each filteredOptions as opt}
            {@const isSelected = String(opt.value) === String(value)}
            <button
              type="button"
              onclick={() => selectOption(opt)}
              class="w-full text-left px-3 py-2.5 rounded-lg text-xs sm:text-sm transition flex items-center justify-between gap-2 cursor-pointer {isSelected ? 'bg-indigo-600 text-white font-black shadow-xs ring-1 ring-indigo-400' : 'text-stone-800 dark:text-stone-100 hover:bg-indigo-50 dark:hover:bg-slate-800'}"
            >
              <div class="flex items-center gap-1.5 sm:gap-2 min-w-0 flex-1 flex-wrap">
                <span class="font-semibold break-words whitespace-normal leading-tight {isSelected ? 'text-white' : ''}">
                  {opt.label}
                </span>
                {#if opt.sublabel}
                  <span class="text-[11px] break-words whitespace-normal opacity-85 {isSelected ? 'text-indigo-100' : 'text-stone-500 dark:text-indigo-300/80'}">
                    ({opt.sublabel})
                  </span>
                {/if}
              </div>

              {#if opt.badge}
                <span class="text-[9.5px] px-1.5 py-0.5 rounded font-mono font-bold shrink-0 {isSelected ? 'bg-indigo-900 text-cyan-300' : 'bg-stone-100 text-stone-700 dark:bg-slate-800 dark:text-indigo-300'}">
                  {opt.badge}
                </span>
              {/if}

              {#if isSelected}
                <svg class="w-4 h-4 text-cyan-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
              {/if}
            </button>
          {/each}
        {/if}
      </div>
    </div>
  {/if}
</div>
