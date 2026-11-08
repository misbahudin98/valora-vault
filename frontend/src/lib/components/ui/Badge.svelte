<script>
  let { 
    type = 'role', // 'role' | 'status' | 'custom'
    value = '',
    variant = 'default', // 'indigo' | 'emerald' | 'rose' | 'amber' | 'slate' | 'cyan'
    children
  } = $props();

  let colorClasses = $derived.by(() => {
    if (type === 'status') {
      return value === 'active' || value === true || value === 1
        ? 'bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-500/40 shadow-[0_0_12px_rgba(16,185,129,0.15)]'
        : 'bg-rose-50 text-rose-700 border-rose-300 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-500/40 shadow-[0_0_12px_rgba(244,63,94,0.15)]';
    }

    if (type === 'role') {
      const r = String(value).toLowerCase();
      if (r === 'admin') {
        return 'bg-indigo-50 text-indigo-700 border-indigo-300 dark:bg-gradient-to-r dark:from-indigo-950/80 dark:to-purple-950/80 dark:text-indigo-300 dark:border-indigo-500/40 shadow-sm';
      }
      if (r === 'manager') {
        return 'bg-cyan-50 text-cyan-700 border-cyan-300 dark:bg-gradient-to-r dark:from-cyan-950/80 dark:to-teal-950/80 dark:text-cyan-300 dark:border-cyan-500/40 shadow-sm';
      }
      return 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800/80 dark:text-slate-300 dark:border-slate-700';
    }

    switch (variant) {
      case 'emerald': return 'bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-500/40 shadow-[0_0_10px_rgba(16,185,129,0.15)]';
      case 'rose': return 'bg-rose-50 text-rose-700 border-rose-300 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-500/40 shadow-[0_0_10px_rgba(244,63,94,0.15)]';
      case 'amber': return 'bg-amber-50 text-amber-700 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-500/40 shadow-[0_0_10px_rgba(245,158,11,0.15)]';
      case 'cyan': return 'bg-cyan-50 text-cyan-700 border-cyan-300 dark:bg-cyan-950/60 dark:text-cyan-300 dark:border-cyan-500/40 shadow-[0_0_10px_rgba(6,182,212,0.15)]';
      case 'slate': return 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800/80 dark:text-slate-300 dark:border-slate-700';
      default: return 'bg-indigo-50 text-indigo-700 border-indigo-300 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-500/40 shadow-[0_0_10px_rgba(79,70,229,0.15)]';
    }
  });
</script>

<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-[11px] font-semibold tracking-wide rounded-full border backdrop-blur-sm transition-all duration-150 {colorClasses}">
  {#if type === 'status'}
    <span class="relative flex h-1.5 w-1.5">
      {#if value === 'active' || value === true || value === 1}
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500 shadow-[0_0_6px_#10b981]"></span>
      {:else}
        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-rose-500 shadow-[0_0_6px_#f43f5e]"></span>
      {/if}
    </span>
    {#if children}
      {@render children()}
    {:else}
      {value === 'active' || value === true || value === 1 ? 'Aktif' : 'Ditangguhkan'}
    {/if}
  {:else}
    {#if children}
      {@render children()}
    {:else}
      {value}
    {/if}
  {/if}
</span>
