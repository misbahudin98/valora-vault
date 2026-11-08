<script>
  let {
    show = true,
    title = '',
    subtitle = '',
    maxWidth = 'max-w-lg',
    onclose = () => {},
    children,
    footer,
    headerTabs
  } = $props();

  function handleKeydown(e) {
    if (e.key === 'Escape' && show) {
      onclose();
    }
  }
</script>

<svelte:window onkeydown={handleKeydown} />

{#if show}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-2.5 sm:p-4 md:p-6 bg-black/60 dark:bg-black/85 backdrop-blur-md animate-in fade-in duration-200">
    <!-- Click outside backdrop -->
    <button
      type="button"
      aria-label="Tutup Modal"
      class="fixed inset-0 bg-transparent cursor-default border-none outline-none"
      onclick={onclose}
    ></button>

    <!-- Modal Box (Apple Spatial Glassmorphism & Specular Chamfer) -->
    <div 
      class="relative z-10 w-full {maxWidth} max-h-[calc(100dvh-1.5rem)] sm:max-h-[calc(100dvh-3rem)] flex flex-col bg-white/95 dark:bg-[#090d16]/95 backdrop-blur-2xl border border-white/50 dark:border-white/10 rounded-xl sm:rounded-2xl shadow-2xl shadow-slate-950/20 dark:shadow-black/70 overflow-hidden before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/50 dark:before:via-white/20 before:to-transparent before:z-30 animate-in zoom-in-95 duration-200"
      role="dialog"
      aria-modal="true"
    >
      <!-- Top Subtle Atmospheric Highlight Line -->
      <div class="h-0.5 w-full bg-gradient-to-r from-indigo-500/40 via-cyan-400/50 to-indigo-600/40 flex-shrink-0"></div>

      <!-- Header (Sticky / Pinned Container) -->
      <div class="border-b border-stone-200/80 dark:border-white/10 flex-shrink-0 bg-slate-50/80 dark:bg-slate-900/60 backdrop-blur-md">
        <div class="px-4 py-3 sm:px-5 sm:py-3.5 flex items-center justify-between gap-3">
          <div class="min-w-0">
            <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white tracking-tight truncate">{title}</h3>
            {#if subtitle}
              <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5">{subtitle}</p>
            {/if}
          </div>
          <button
            type="button"
            onclick={onclose}
            class="min-h-[44px] min-w-[44px] p-2 rounded-lg text-slate-400 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer flex-shrink-0 flex items-center justify-center"
            aria-label="Tutup Dialog"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        {#if headerTabs}
          <div class="px-3 pb-2 sm:px-5 sm:pb-2.5 border-t border-stone-200/80 dark:border-white/10 pt-2 bg-slate-100/70 dark:bg-slate-950/50">
            {@render headerTabs()}
          </div>
        {/if}
      </div>

      <!-- Body -->
      <div class="p-4 sm:p-5 overflow-y-auto flex-1 overscroll-contain text-slate-700 dark:text-slate-300">
        {@render children?.()}
      </div>

      <!-- Footer (if provided) -->
      {#if footer}
        <div class="px-4 py-3 sm:px-5 sm:py-3.5 bg-slate-50/80 dark:bg-slate-950/60 backdrop-blur-md border-t border-stone-200/80 dark:border-white/10 flex items-center justify-end gap-2.5 flex-shrink-0">
          {@render footer()}
        </div>
      {/if}
    </div>
  </div>
{/if}
