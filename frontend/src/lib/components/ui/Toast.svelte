<script>
  /**
   * Reusable Svelte 5 Toast Component (Pure JS Runes)
   * Can be used in two modes:
   * 1. Global Floating Bus: Place `<Toast />` in +layout.svelte, then trigger with `toastState.success(...)`, etc.
   * 2. Inline/Standalone Form Status: Place `<Toast message="Terjadi error" type="error" inline={true} />` inside any form/component.
   */
  import { toastState } from '$lib/state/toast.svelte.js';

  let {
    message = '',
    type = 'info',
    title = '',
    duration = 0,
    show = true,
    inline = false,
    onclose = null
  } = $props();

  function handleDismiss(id) {
    if (id) {
      toastState.remove(id);
    }
    if (onclose) {
      onclose();
    }
  }

  // Semantic styles for high contrast & accessible visibility
  const variantStyles = {
    success: {
      card: 'bg-emerald-50/95 dark:bg-[#06291c]/95 border-emerald-500 text-emerald-950 dark:text-emerald-50 shadow-emerald-950/15',
      iconBg: 'bg-emerald-600 text-white shadow-sm',
      title: 'text-emerald-900 dark:text-emerald-200',
      text: 'text-stone-700 dark:text-emerald-100/90',
      icon: '✓'
    },
    error: {
      card: 'bg-rose-50/95 dark:bg-rose-950/95 border-rose-500 text-rose-950 dark:text-rose-50 shadow-rose-950/15',
      iconBg: 'bg-rose-600 text-white shadow-sm',
      title: 'text-rose-900 dark:text-rose-200',
      text: 'text-stone-700 dark:text-rose-100/90',
      icon: '✕'
    },
    warning: {
      card: 'bg-amber-50/95 dark:bg-amber-950/95 border-amber-500 text-amber-950 dark:text-amber-50 shadow-amber-950/15',
      iconBg: 'bg-amber-600 text-white shadow-sm',
      title: 'text-amber-900 dark:text-amber-200',
      text: 'text-stone-700 dark:text-amber-100/90',
      icon: '⚠'
    },
    info: {
      card: 'bg-sky-50/95 dark:bg-sky-950/95 border-sky-500 text-sky-950 dark:text-sky-50 shadow-sky-950/15',
      iconBg: 'bg-sky-600 text-white shadow-sm',
      title: 'text-sky-900 dark:text-sky-200',
      text: 'text-stone-700 dark:text-sky-100/90',
      icon: 'ℹ'
    }
  };
</script>

{#if inline}
  <!-- INLINE FORM STATUS MODE -->
  {#if show && message}
    {@const style = variantStyles[type] || variantStyles.info}
    <div
      class="p-3.5 sm:p-4 rounded-xl border flex items-start gap-3 transition-all duration-200 {style.card}"
      role="alert"
    >
      <div class="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-xs shrink-0 {style.iconBg}">
        {style.icon}
      </div>
      <div class="flex-1 min-w-0">
        {#if title}
          <div class="font-bold text-xs sm:text-sm leading-tight {style.title}">{title}</div>
        {/if}
        <div class="text-xs leading-relaxed font-medium {style.text} {title ? 'mt-1' : ''}">
          {message}
        </div>
      </div>
      {#if onclose}
        <button
          type="button"
          onclick={() => handleDismiss(null)}
          class="p-1 rounded-lg text-stone-500 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white transition cursor-pointer"
          aria-label="Tutup notifikasi status"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      {/if}
    </div>
  {/if}
{:else if message && show}
  <!-- STANDALONE SINGLE TOAST -->
  {@const style = variantStyles[type] || variantStyles.info}
  <div
    class="fixed top-4 left-1/2 -translate-x-1/2 z-[9999] max-w-sm sm:max-w-md w-[calc(100%-2rem)] rounded-xl border p-3 sm:p-3.5 flex items-start gap-2.5 backdrop-blur-md shadow-xl transition-all duration-200 {style.card}"
    role="alert"
  >
    <div class="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-xs shrink-0 {style.iconBg}">
      {style.icon}
    </div>
    <div class="flex-1 min-w-0 pr-1">
      {#if title}
        <div class="font-bold text-xs tracking-tight leading-tight {style.title}">{title}</div>
      {/if}
      <div class="text-[11px] mt-0.5 leading-relaxed break-words font-medium {style.text}">
        {message}
      </div>
    </div>
    {#if onclose}
      <button
        type="button"
        onclick={() => handleDismiss(null)}
        class="min-h-[44px] min-w-[44px] -mr-1.5 -mt-1.5 p-2 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-white transition cursor-pointer flex items-center justify-center shrink-0"
        aria-label="Tutup notifikasi"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    {/if}
  </div>
{:else}
  <!-- GLOBAL FLOATING TOAST STACK -->
  {#if toastState.toasts.length > 0}
    <div
      class="fixed top-4 left-1/2 -translate-x-1/2 z-[9999] flex flex-col items-center gap-2 max-w-sm sm:max-w-md w-[calc(100%-2rem)] pointer-events-none"
      aria-live="polite"
    >
      {#each toastState.toasts as toast (toast.id)}
        {@const style = variantStyles[toast.type] || variantStyles.info}
        <div
          class="pointer-events-auto w-full rounded-xl border p-3 sm:p-3.5 flex items-start gap-2.5 backdrop-blur-md shadow-xl transition-all duration-300 {style.card}"
          role="alert"
        >
          <!-- Semantic Badge Icon -->
          <div class="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-xs shrink-0 {style.iconBg}">
            {style.icon}
          </div>

          <!-- Body Content -->
          <div class="flex-1 min-w-0 pr-1">
            <div class="font-bold text-xs tracking-tight leading-tight {style.title}">
              {toast.title}
            </div>
            <div class="text-[11px] mt-0.5 leading-relaxed opacity-95 break-words font-medium {style.text}">
              {toast.message}
            </div>
          </div>

          <!-- Dismiss Action Button -->
          <button
            type="button"
            onclick={() => handleDismiss(toast.id)}
            class="min-h-[44px] min-w-[44px] -mr-1.5 -mt-1.5 p-2 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-white hover:bg-stone-200/50 dark:hover:bg-stone-800/50 transition cursor-pointer flex items-center justify-center shrink-0"
            aria-label="Tutup notifikasi"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      {/each}
    </div>
  {/if}
{/if}
