<script>
  import Modal from '$lib/components/ui/Modal.svelte';
  import { api } from '$lib/api/client.js';

  let {
    show = false,
    menu = null,
    roleName = '',
    zIndex = 'z-[70]',
    onclose = () => {},
    onconfirm = () => {},
    submitting = false
  } = $props();

  const teamMembers = [
    'Muhammad Iqbal Murtadho',
    'Misbahudin'
  ];

  let usersList = $state([]);
  let completionDate = $state(new Date().toISOString().split('T')[0]);
  let completionPic = $state('Muhammad Iqbal Murtadho');

  $effect(() => {
    if (show) {
      api.request('/api/v1/admin/users?limit=100').then(res => {
        if (res && res.data) {
          usersList = res.data;
        }
      }).catch(() => {});

      if (menu) {
        completionDate = menu.completed_date || new Date().toISOString().split('T')[0];
        completionPic = menu.pic || 'Muhammad Iqbal Murtadho';
      }
    }
  });

  function handleSubmit(e) {
    e.preventDefault();
    if (!menu) return;
    const picUser = usersList.find(u => u.name === completionPic);
    onconfirm({
      menuId: menu.id,
      completedDate: completionDate,
      pic: completionPic,
      pic_user_id: picUser?.id || null
    });
  }
</script>

<Modal
  {show}
  title="Pilih Tanggal Pengerjaan"
  subtitle="Tentukan kapan menu ini selesai dikerjakan"
  maxWidth="max-w-md"
  {zIndex}
  {onclose}
>
  {#snippet header()}
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold shrink-0">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </div>
      <div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Pilih Tanggal Pengerjaan</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400">Tentukan kapan menu ini selesai dikerjakan</p>
      </div>
    </div>
  {/snippet}

  <form id="menu-completion-form" onsubmit={handleSubmit} class="space-y-4">
    <!-- Preview Menu & Role -->
    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 space-y-1">
      <span class="text-[10px] font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400">
        Role: {roleName || 'Umum'}
      </span>
      <h4 class="text-sm font-bold text-slate-900 dark:text-white">
        {menu?.menu_name || 'Nama Menu Modul'}
      </h4>
      {#if menu?.menu_desc}
        <p class="text-xs text-slate-500 dark:text-slate-400">
          {menu.menu_desc}
        </p>
      {/if}
    </div>

    <!-- Input Tanggal Pengerjaan -->
    <div>
      <label for="completion-date-input" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
        Tanggal Selesai Pengerjaan <span class="text-rose-500">*</span>
      </label>
      <input
        id="completion-date-input"
        type="date"
        bind:value={completionDate}
        required
        class="w-full px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
      />
    </div>

    <!-- Pilih Teknisi / PIC Pengerja -->
    <div>
      <label for="completion-pic-select" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
        Teknisi / Lead PIC Pengerja <span class="text-rose-500">*</span>
      </label>
      <select
        id="completion-pic-select"
        bind:value={completionPic}
        class="w-full px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
      >
        {#if usersList.length === 0}
          {#each teamMembers as member}
            <option value={member}>{member}</option>
          {/each}
        {:else}
          {#each usersList as user}
            <option value={user.name}>{user.name} {user.jabatan ? `(${user.jabatan})` : ''}</option>
          {/each}
        {/if}
      </select>
    </div>
  </form>

  {#snippet footer()}
    <div class="flex items-center justify-end gap-3">
      <button
        type="button"
        onclick={onclose}
        disabled={submitting}
        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
      >
        Batal
      </button>
      <button
        type="submit"
        form="menu-completion-form"
        disabled={submitting}
        class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 transition shadow-md shadow-emerald-600/30 flex items-center gap-2 cursor-pointer disabled:opacity-50"
      >
        {#if submitting}
          <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>Menyimpan...</span>
        {:else}
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span>Simpan & Tandai Selesai</span>
        {/if}
      </button>
    </div>
  {/snippet}
</Modal>
