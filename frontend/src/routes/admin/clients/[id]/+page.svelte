<script>
  import { onMount } from 'svelte';
  import { page } from '$app/state';
  import { goto } from '$app/navigation';

  let projectId = $derived(page.params.id);

  onMount(() => {
    if (projectId) {
      goto(`/admin/clients?detail=${projectId}`, { replaceState: true });
    } else {
      goto('/admin/clients', { replaceState: true });
    }
  });
</script>

<svelte:head>
  <title>Detail & Setting Proyek — Valora OS</title>
</svelte:head>

<div class="min-h-screen flex items-center justify-center bg-slate-100/70 dark:bg-[#070b12] text-xs text-slate-500">
  <div class="flex items-center gap-2.5 p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs">
    <div class="w-4 h-4 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
    <span class="font-semibold text-slate-700 dark:text-slate-300">Membuka Modal Detail & Setting Proyek...</span>
  </div>
</div>
