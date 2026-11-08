<script>
  /**
   * ElementLoader.svelte - Ultra-lightweight, Fast Element-Level Loader (Svelte 5 Runes)
   * Pure CSS + SVG. Designed for inline elements, tables, buttons, and cards.
   * Zero external dependencies. Zero layout shift.
   */
  let {
    size = 'sm', // 'xs' | 'sm' | 'md' | 'lg'
    variant = 'primary', // 'primary' | 'cyan' | 'indigo' | 'emerald'
    text = '',
    type = 'spinner', // 'spinner' | 'pulse' | 'dots'
    className = ''
  } = $props();

  const sizeMap = {
    xs: { ring: 'w-3.5 h-3.5 border', dot: 'w-1 h-1', font: 'text-[10px]', gap: 'gap-1.5' },
    sm: { ring: 'w-4 h-4 border-2', dot: 'w-1.5 h-1.5', font: 'text-xs', gap: 'gap-2' },
    md: { ring: 'w-6 h-6 border-2', dot: 'w-2 h-2', font: 'text-xs', gap: 'gap-2.5' },
    lg: { ring: 'w-8 h-8 border-3', dot: 'w-2.5 h-2.5', font: 'text-sm', gap: 'gap-3' },
  };

  const currentSize = $derived(sizeMap[size] || sizeMap.sm);

  const variantStyles = {
    primary: {
      border: 'border-slate-700/60 border-t-cyan-400 border-r-indigo-500',
      dot: 'bg-gradient-to-r from-cyan-400 to-indigo-500',
      text: 'text-cyan-400',
      glow: 'shadow-[0_0_10px_rgba(6,182,212,0.4)]'
    },
    cyan: {
      border: 'border-slate-700/60 border-t-cyan-300 border-r-cyan-500',
      dot: 'bg-cyan-400',
      text: 'text-cyan-300',
      glow: 'shadow-[0_0_10px_rgba(6,182,212,0.3)]'
    },
    indigo: {
      border: 'border-slate-700/60 border-t-indigo-400 border-r-indigo-600',
      dot: 'bg-indigo-400',
      text: 'text-indigo-300',
      glow: 'shadow-[0_0_10px_rgba(99,102,241,0.3)]'
    },
    emerald: {
      border: 'border-slate-700/60 border-t-emerald-400 border-r-teal-500',
      dot: 'bg-emerald-400',
      text: 'text-emerald-300',
      glow: 'shadow-[0_0_10px_rgba(16,185,129,0.3)]'
    }
  };

  const currentVariant = $derived(variantStyles[variant] || variantStyles.primary);
</script>

<div class="inline-flex items-center justify-center {currentSize.gap} {className}" role="status" aria-live="polite">
  {#if type === 'spinner'}
    <!-- Fast Dual-Tone Micro Spinner -->
    <div
      class="{currentSize.ring} {currentVariant.border} rounded-full animate-spin flex-shrink-0"
      style="animation-duration: 0.6s;"
    ></div>
  {:else if type === 'pulse'}
    <!-- Micro Pulse Bar -->
    <div class="flex items-center gap-1">
      <span class="{currentSize.dot} rounded-full {currentVariant.dot} animate-pulse"></span>
      <span class="{currentSize.dot} rounded-full {currentVariant.dot} animate-pulse [animation-delay:150ms]"></span>
      <span class="{currentSize.dot} rounded-full {currentVariant.dot} animate-pulse [animation-delay:300ms]"></span>
    </div>
  {:else if type === 'dots'}
    <!-- Bouncing Dots -->
    <div class="flex items-center gap-1">
      <span class="{currentSize.dot} rounded-full {currentVariant.dot} animate-bounce"></span>
      <span class="{currentSize.dot} rounded-full {currentVariant.dot} animate-bounce [animation-delay:150ms]"></span>
      <span class="{currentSize.dot} rounded-full {currentVariant.dot} animate-bounce [animation-delay:300ms]"></span>
    </div>
  {/if}

  {#if text}
    <span class="{currentSize.font} {currentVariant.text} font-medium select-none tracking-tight">
      {text}
    </span>
  {/if}
</div>
