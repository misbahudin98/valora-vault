<script>
  import Modal from '$lib/components/ui/Modal.svelte';
  import { toastState } from '$lib/state/toast.svelte.js';
  import { api } from '$lib/api/client.js';

  let {
    show = false,
    rates = [],
    initialRateKey = null,
    onclose = () => {},
    onsave = () => {}
  } = $props();

  let selectedKey = $state('menu_price');
  let newPrice = $state(750000);
  let editName = $state('');
  let editDescription = $state('');
  let reason = $state('');
  let submitting = $state(false);

  // Active rate item based on selection
  let currentRate = $derived(rates.find(r => r.rate_key === selectedKey) || rates[0] || null);

  // Synchronize when modal opens
  $effect(() => {
    if (show) {
      if (initialRateKey && rates.some(r => r.rate_key === initialRateKey)) {
        selectedKey = initialRateKey;
      } else if (rates.length > 0 && !rates.some(r => r.rate_key === selectedKey)) {
        selectedKey = rates[0].rate_key;
      }
      reason = '';
    }
  });

  // Update default newPrice when currentRate changes
  $effect(() => {
    if (currentRate) {
      newPrice = Number(currentRate.price) || 0;
      editName = currentRate.name || '';
      editDescription = currentRate.description || '';
    }
  });

  function formatIDR(val) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0
    }).format(val || 0);
  }

  async function handleSubmit(e) {
    e?.preventDefault();
    if (!reason.trim()) {
      toastState.error('Keterangan alasan perubahan tarif wajib disertakan sesuai standar transparansi.', 'Validasi Gagal');
      return;
    }

    if (newPrice < 0) {
      toastState.error('Nominal tarif tidak boleh bernilai negatif.', 'Validasi Gagal');
      return;
    }

    submitting = true;
    try {
      const res = await api.request(`/api/v1/admin/billing-rates/${selectedKey}`, {
        method: 'PUT',
        body: JSON.stringify({
          price: Number(newPrice),
          reason: reason.trim(),
          description: editDescription.trim() || undefined
        })
      });

      toastState.success(res?.message || `Tarif '${currentRate?.name}' berhasil diperbarui.`, 'Tarif Diperbarui');
      onsave(res?.data);
      onclose();
    } catch (err) {
      toastState.error(err?.message || 'Gagal memperbarui tarif acuan standar.', 'Gagal');
    } finally {
      submitting = false;
    }
  }
</script>

<Modal
  {show}
  title="Perbarui Tarif Acuan Standar"
  subtitle="Penetapan tarif resmi Menu Aplikasi / Modul Kerja & Sistem Inti"
  maxWidth="max-w-xl"
  zIndex="z-[60]"
  {onclose}
>
  <form id="update-master-rate-form" onsubmit={handleSubmit} class="space-y-4">
    <!-- Item Selector dengan 2 Kategori -->
    <div>
      <label for="master-rate-key-select" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
        Pilih Rincian Kapabilitas / Item Tarif *
      </label>
      <select
        id="master-rate-key-select"
        bind:value={selectedKey}
        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer shadow-2xs"
      >
        <optgroup label="Menu Aplikasi / Modul Kerja (12 Kapabilitas & Acuan)">
          {#each rates.filter(r => r.category === 'menu_work' || r.rate_key?.startsWith('cap_') || r.rate_key === 'menu_price') as r}
            <option value={r.rate_key}>{r.name} ({formatIDR(r.price)})</option>
          {/each}
        </optgroup>
        <optgroup label="Tarif Pondasi Sistem Inti">
          {#each rates.filter(r => r.category === 'system_core' && !r.rate_key?.startsWith('cap_') && r.rate_key !== 'menu_price') as r}
            <option value={r.rate_key}>{r.name} ({formatIDR(r.price)})</option>
          {/each}
        </optgroup>
      </select>
      {#if currentRate?.description}
        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1.5 italic">
          {currentRate.description}
        </p>
      {/if}
    </div>

    <!-- Rate Comparison Grid -->
    <div class="grid grid-cols-2 gap-3 p-3 bg-slate-50 dark:bg-slate-950/60 rounded-xl border border-slate-200/80 dark:border-slate-800 text-xs">
      <div>
        <span class="text-[10px] uppercase font-bold text-slate-400 block mb-0.5">Tarif Lama (Saat Ini):</span>
        <span class="font-mono font-bold text-slate-700 dark:text-slate-300 line-through">
          {formatIDR(currentRate?.price)}
        </span>
      </div>
      <div>
        <span class="text-[10px] uppercase font-bold text-indigo-600 dark:text-indigo-400 block mb-0.5">Tarif Baru Disetel (IDR):</span>
        <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">
          {formatIDR(newPrice)}
        </span>
      </div>
    </div>

    <!-- Input Tarif Baru -->
    <div>
      <label for="master-new-price-input" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
        Nominal Tarif Baru (Rp) *
      </label>
      <div class="relative">
        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-bold text-slate-400">
          Rp
        </span>
        <input
          type="number"
          id="master-new-price-input"
          bind:value={newPrice}
          step="5000"
          min="0"
          required
          class="w-full pl-9 pr-3.5 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white font-mono font-semibold focus:outline-none focus:ring-1 focus:ring-indigo-500 shadow-2xs"
          placeholder="75000"
        />
      </div>
      <p class="text-[10.5px] text-slate-400 mt-1">
        Satuan: <b class="text-slate-700 dark:text-slate-300">{currentRate?.unit || 'Per Fitur di Menu'}</b>
      </p>
    </div>

    <!-- Deskripsi Fungsional Singkat -->
    <div>
      <label for="master-rate-desc-input" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
        Keterangan / Deskripsi Fungsional Item
      </label>
      <input
        type="text"
        id="master-rate-desc-input"
        bind:value={editDescription}
        class="w-full px-3.5 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 shadow-2xs"
        placeholder="Deskripsi singkat cakupan teknis item ini..."
      />
    </div>

    <!-- Alasan Perubahan Tarif -->
    <div>
      <label for="master-rate-reason-input" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
        Alasan / Keterangan Penyesuaian Tarif *
      </label>
      <textarea
        id="master-rate-reason-input"
        bind:value={reason}
        rows="2"
        required
        class="w-full px-3.5 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 shadow-2xs"
        placeholder="Contoh: Penyesuaian kompleksitas chart, integrasi API gateway, dan standar SLA"
      ></textarea>
      <p class="text-[10px] text-slate-400 mt-0.5">
        Tercatat permanen pada tabel riwayat audit trail perubahan tarif.
      </p>
    </div>
  </form>

  {#snippet footer()}
    <div class="flex items-center justify-end gap-2 w-full">
      <button
        type="button"
        onclick={onclose}
        disabled={submitting}
        class="min-h-[34px] px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition cursor-pointer"
      >
        Batal
      </button>

      <button
        type="submit"
        form="update-master-rate-form"
        disabled={submitting || !reason.trim()}
        class="min-h-[34px] px-4 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-xs flex items-center gap-1.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {#if submitting}
          <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>Menyimpan...</span>
        {:else}
          <span>Simpan & Terapkan Tarif</span>
        {/if}
      </button>
    </div>
  {/snippet}
</Modal>
