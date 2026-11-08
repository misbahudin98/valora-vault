<script>
  import { goto } from '$app/navigation';
  import { onMount } from 'svelte';
  import ElementLoader from '$lib/components/ui/ElementLoader.svelte';

  onMount(() => {
    goto('/dashboard', { replaceState: true });
  });
</script>

<svelte:head>
  <title>Mengalihkan ke Dashboard Terpadu...</title>
  <meta http-equiv="refresh" content="0;url=/dashboard" />
  <meta name="robots" content="noindex, nofollow" />
</svelte:head>

<div class="min-h-[50vh] flex flex-col items-center justify-center p-6 text-center">
  <div class="p-6 bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm backdrop-blur-xl flex flex-col items-center max-w-sm w-full space-y-3">
    <ElementLoader size="md" variant="indigo" />
    <div class="text-sm font-semibold text-slate-800 dark:text-slate-200">
      Mengalihkan ke Dashboard Terpadu...
    </div>
    <p class="text-xs text-slate-500 dark:text-slate-400">
      Rute admin panel telah dikonsolidasikan ke portal dashboard adaptif.
    </p>
  </div>
</div>
