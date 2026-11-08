<script>
  /**
   * Reusable, Lightweight PageLoader Component (Svelte 5 Runes)
   * Pure CSS + SVG, zero external dependencies.
   */
  let {
    fullscreen = false,
    size = 'md', // 'sm' | 'md' | 'lg' | 'xl'
    text = '',
    variant = 'primary', // 'primary' | 'cyan' | 'indigo'
    backdrop = true
  } = $props();

  const sizeMap = {
    sm: { container: 'w-6 h-6', icon: 'w-3 h-3', stroke: 'border-2', textSize: 'text-xs' },
    md: { container: 'w-10 h-10', icon: 'w-5 h-5', stroke: 'border-2', textSize: 'text-sm' },
    lg: { container: 'w-14 h-14', icon: 'w-7 h-7', stroke: 'border-3', textSize: 'text-base' },
    xl: { container: 'w-20 h-20', icon: 'w-10 h-10', stroke: 'border-4', textSize: 'text-lg' }
  };

  const currentSize = $derived(sizeMap[size] || sizeMap.md);

  const gradientMap = {
    primary: 'from-cyan-400 via-indigo-500 to-emerald-400',
    cyan: 'from-cyan-300 via-teal-400 to-sky-500',
    indigo: 'from-indigo-400 via-violet-500 to-purple-400'
  };

  const currentGradient = $derived(gradientMap[variant] || gradientMap.primary);
</script>

{#if fullscreen}
  <div
    class="fixed inset-0 z-50 flex flex-col items-center justify-center p-4 transition-all duration-300 {backdrop ? 'bg-slate-950/80 backdrop-blur-md' : 'bg-transparent'}"
    role="status"
    aria-live="polite"
  >
    <div class="relative flex items-center justify-center">
      <!-- Outer glowing pulse -->
      <div class="absolute {currentSize.container} rounded-full bg-gradient-to-tr {currentGradient} opacity-30 blur-xl animate-pulse"></div>

      <!-- Outer spinning track -->
      <div class="{currentSize.container} rounded-full border border-slate-700/60 border-t-cyan-400 border-r-indigo-500 animate-spin"></div>

      <!-- Center Logo / Lightning Bolt -->
      <div class="absolute flex items-center justify-center text-cyan-400 animate-pulse">
        <svg class="{currentSize.icon}" viewBox="0 0 24 24" fill="currentColor">
          <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
        </svg>
      </div>
    </div>

    {#if text}
      <p class="mt-4 {currentSize.textSize} font-medium text-slate-300 tracking-wide text-center animate-pulse">
        {text}
      </p>
    {/if}
  </div>
{:else}
  <div class="inline-flex flex-col items-center justify-center p-2" role="status" aria-live="polite">
    <div class="relative flex items-center justify-center">
      <!-- Outer spinning track -->
      <div class="{currentSize.container} rounded-full border border-slate-700/60 border-t-cyan-400 border-r-indigo-500 animate-spin"></div>

      <!-- Center Icon -->
      <div class="absolute flex items-center justify-center text-cyan-400">
        <svg class="{currentSize.icon}" viewBox="0 0 24 24" fill="currentColor">
          <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
        </svg>
      </div>
    </div>

    {#if text}
      <span class="mt-2 {currentSize.textSize} font-medium text-slate-400 text-center">
        {text}
      </span>
    {/if}
  </div>
{/if}

<style>
  @keyframes spin-slow {
    to { transform: rotate(360deg); }
  }
</style>
