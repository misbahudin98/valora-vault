<script>
  import { onMount } from 'svelte';
  import { page } from '$app/state';
  import DataTable from '$lib/components/ui/DataTable.svelte';
  import Badge from '$lib/components/ui/Badge.svelte';
  import InvoiceModal from '$lib/components/billing/InvoiceModal.svelte';
  import UpdateMasterRateModal from '$lib/components/billing/UpdateMasterRateModal.svelte';
  import { api } from '$lib/api/client.js';
  import { toastState } from '$lib/state/toast.svelte.js';
  import { exportToCsv } from '$lib/utils/csvExport.js';

  // Active Main Tab: 'client' | 'rates'
  let activeMainTab = $state('client');

  // Filter status pembayaran untuk sub-tab 1
  let activePaymentFilter = $state('all'); // 'all' | 'lunas' | 'termin' | 'belum_bayar'

  // Data State Sub-Tab 1: Billing & Payments
  let loadingBilling = $state(false);
  let billingError = $state(null);
  let projects = $state([]);
  let billingMeta = $state({
    total_projects: 0,
    total_grand: 0,
    total_paid: 0,
    total_remaining: 0,
    fully_paid_count: 0,
    partially_paid_count: 0,
    unpaid_count: 0
  });

  // Data State Sub-Tab 2: Master Rates & History
  let loadingRates = $state(false);
  let ratesError = $state(null);
  let ratesList = $state([]);
  let rateHistories = $state([]);
  let historySearch = $state('');

  // Modals state
  let showInvoiceModal = $state(false);
  let selectedInvoiceProjectId = $state(null);

  let showRateModal = $state(false);
  let selectedRateKey = $state(null);

  // Synchronize URL query parameter ?tab=rates
  $effect(() => {
    const tabParam = page?.url?.searchParams?.get('tab');
    if (tabParam === 'rates') {
      activeMainTab = 'rates';
    }
  });

  // Initial load
  $effect(() => {
    loadBillingData();
    loadRatesData();
  });

  async function loadBillingData() {
    loadingBilling = true;
    billingError = null;
    try {
      const res = await api.request('/api/v1/admin/billing/projects');
      if (res) {
        projects = res.data || [];
        billingMeta = res.meta || billingMeta;
      }
    } catch (err) {
      billingError = err?.message || 'Gagal memuat ringkasan tagihan klien.';
    } finally {
      loadingBilling = false;
    }
  }

  async function loadRatesData() {
    loadingRates = true;
    ratesError = null;
    try {
      const res = await api.request('/api/v1/admin/billing-rates');
      if (res) {
        ratesList = res.data || [];
        rateHistories = res.histories || [];
      }
    } catch (err) {
      ratesError = err?.message || 'Gagal memuat master tarif standar dan riwayat.';
    } finally {
      loadingRates = false;
    }
  }

  // Filtered projects by payment status tab
  let filteredProjects = $derived.by(() => {
    if (activePaymentFilter === 'lunas') {
      return projects.filter(p => p.payment_status === 'Lunas');
    }
    if (activePaymentFilter === 'termin') {
      return projects.filter(p => p.payment_status === 'DP / Termin');
    }
    if (activePaymentFilter === 'belum_bayar') {
      return projects.filter(p => p.payment_status === 'Belum Bayar');
    }
    return projects;
  });

  let systemCoreRates = $derived(
    ratesList.filter(r => r.is_active)
  );

  let filteredHistories = $derived.by(() => {
    if (!historySearch.trim()) return rateHistories;
    const q = historySearch.toLowerCase();
    return rateHistories.filter(h =>
      (h.item_name && h.item_name.toLowerCase().includes(q)) ||
      (h.rate_key && h.rate_key.toLowerCase().includes(q)) ||
      (h.reason && h.reason.toLowerCase().includes(q)) ||
      (h.updated_by && h.updated_by.toLowerCase().includes(q))
    );
  });

  function formatIDR(val) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0
    }).format(val || 0);
  }

  function formatDate(dateStr) {
    if (!dateStr) return '-';
    try {
      const d = new Date(dateStr);
      return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    } catch {
      return dateStr;
    }
  }

  function openInvoiceModal(project) {
    selectedInvoiceProjectId = project.id;
    showInvoiceModal = true;
  }

  function openRateUpdateModal(rateKey = null) {
    selectedRateKey = rateKey;
    showRateModal = true;
  }

  function exportHistoriesCsv() {
    const cols = [
      { key: 'created_at', label: 'TANGGAL UPDATE', transform: (h) => formatDate(h.created_at) },
      { key: 'item_name', label: 'ITEM FITUR / MODUL', transform: (h) => h.item_name || h.rate_key },
      { key: 'old_price', label: 'TARIF SEBELUMNYA', transform: (h) => formatIDR(h.old_price) },
      { key: 'new_price', label: 'TARIF BARU', transform: (h) => formatIDR(h.new_price) },
      { key: 'reason', label: 'ALASAN / KETERANGAN' },
      { key: 'updated_by', label: 'DIPERBARUI OLEH' }
    ];
    exportToCsv(filteredHistories, 'valora_riwayat_tarif_standar', cols);
  }

  const csvColumnsBilling = [
    { key: 'app_name', label: 'NAMA APLIKASI' },
    { key: 'client', label: 'NAMA CLIENT', transform: (p) => p.client?.name || '-' },
    { key: 'client', label: 'INDUSTRI', transform: (p) => p.client?.industry || 'Umum' },
    { key: 'lead_pic', label: 'LEAD PIC' },
    { key: 'grand_total', label: 'TOTAL TAGIHAN', transform: (p) => formatIDR(p.grand_total) },
    { key: 'paid_amount', label: 'SUDAH DIBAYAR', transform: (p) => formatIDR(p.paid_amount) },
    { key: 'remaining_amount', label: 'SISA PIUTANG', transform: (p) => formatIDR(p.remaining_amount) },
    { key: 'payment_status', label: 'STATUS BAYAR' }
  ];
</script>

<svelte:head>
  <title>Tagihan Client & Master Tarif — Valora OS</title>
</svelte:head>

<div class="min-h-screen bg-slate-100/70 dark:bg-[#070b12] text-slate-800 dark:text-slate-200">
  <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-5 sm:py-7 space-y-6 pb-24 md:pb-12">
    
    <!-- Top Action Bar & Nav Main Tabs Switcher (L1 Macro Surface) -->
    <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
          <span>Keuangan</span>
          <span>/</span>
          <span class="text-emerald-600 dark:text-emerald-400 font-bold">
            {activeMainTab === 'client' ? 'Invoicing & Tagihan Client' : 'Master Tarif Standar & Riwayat'}
          </span>
        </div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8h4" />
            </svg>
          </div>
          <span>Tagihan Client & Master Tarif</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
          Monitoring status pembayaran klien, rincian biaya project, konfigurasi tarif acuan resmi, dan riwayat audit trail.
        </p>
      </div>

      <!-- Segmented Main Tab Switcher (RULE-14: Responsive Multi-line Wrap) -->
      <div class="flex flex-wrap items-center p-1 bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 rounded-2xl shrink-0 gap-1">
        <button
          type="button"
          onclick={() => (activeMainTab = 'client')}
          class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer {activeMainTab === 'client' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'}"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
          </svg>
          <span>Tagihan & Pembayaran Client</span>
        </button>

        <button
          type="button"
          onclick={() => (activeMainTab = 'rates')}
          class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer {activeMainTab === 'rates' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'}"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
          </svg>
          <span>Master Tarif & Riwayat</span>
        </button>
      </div>
    </div>

    <!-- ============================================== -->
    <!-- SUB-TAB 1: TAGIHAN & PEMBAYARAN CLIENT         -->
    <!-- ============================================== -->
    {#if activeMainTab === 'client'}
      <!-- 3 Ringkasan Keuangan Box (KPI Cards - L1 Macro Surface) -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        <!-- Card 1: Total Tagihan -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-2xs space-y-2">
          <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
            <span class="uppercase tracking-wider font-bold text-[10px]">Total Tagihan Seluruh Project</span>
            <div class="w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
          <p class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white font-mono">
            {formatIDR(billingMeta.total_grand)}
          </p>
          <p class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold">
            Akumulasi seluruh fitur, menu, database, & maintenance
          </p>
        </div>

        <!-- Card 2: Sudah Dibayar -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-2xs space-y-2">
          <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
            <span class="uppercase tracking-wider font-bold text-[10px]">Dana Yang Sudah Dibayarkan</span>
            <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <p class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 font-mono">
            {formatIDR(billingMeta.total_paid)}
          </p>
          <p class="text-[11px] text-slate-500 dark:text-slate-400">
            Pembayaran DP & pelunasan kas masuk ({billingMeta.fully_paid_count} Lunas, {billingMeta.partially_paid_count} Termin)
          </p>
        </div>

        <!-- Card 3: Sisa Piutang -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-2xs space-y-2">
          <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
            <span class="uppercase tracking-wider font-bold text-[10px]">Sisa Tagihan / Belum Lunas</span>
            <div class="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
          </div>
          <p class="text-2xl sm:text-3xl font-black text-amber-600 dark:text-amber-400 font-mono">
            {formatIDR(billingMeta.total_remaining)}
          </p>
          <p class="text-[11px] text-slate-500 dark:text-slate-400">
            Piutang yang masih tertunda ({billingMeta.unpaid_count} belum bayar sama sekali)
          </p>
        </div>
      </div>

      <!-- Filter Segmented Tabs (RULE-14: Responsive Multi-line Wrap) -->
      <div class="flex flex-wrap items-center gap-1.5 p-1 bg-slate-200/60 dark:bg-slate-900/60 rounded-xl border border-slate-200 dark:border-slate-800">
        <button
          type="button"
          onclick={() => (activePaymentFilter = 'all')}
          class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer {activePaymentFilter === 'all' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'}"
        >
          Semua Proyek ({projects.length})
        </button>
        <button
          type="button"
          onclick={() => (activePaymentFilter = 'lunas')}
          class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer {activePaymentFilter === 'lunas' ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'}"
        >
          Lunas ({billingMeta.fully_paid_count})
        </button>
        <button
          type="button"
          onclick={() => (activePaymentFilter = 'termin')}
          class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer {activePaymentFilter === 'termin' ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'}"
        >
          DP / Termin ({billingMeta.partially_paid_count})
        </button>
        <button
          type="button"
          onclick={() => (activePaymentFilter = 'belum_bayar')}
          class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer {activePaymentFilter === 'belum_bayar' ? 'bg-white dark:bg-slate-800 text-rose-600 dark:text-rose-400 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'}"
        >
          Belum Bayar ({billingMeta.unpaid_count})
        </button>
      </div>

      <!-- Tabel Data Monitoring Tagihan & Pembayaran -->
      <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-2xs">
        <DataTable
          items={filteredProjects}
          searchPlaceholder="Cari aplikasi, klien, industri, lead PIC..."
          searchKeys={['app_name', 'project_name', 'lead_pic', 'client.name', 'client.industry']}
          defaultPageSize={10}
          csvFilename="valora_tagihan_klien"
          csvColumns={csvColumnsBilling}
        >
          {#snippet header()}
            <th class="py-3 px-3 text-center w-12">No</th>
            <th class="py-3 px-3">Nama Project & Klien</th>
            <th class="py-3 px-3">Lead PIC</th>
            <th class="py-3 px-3 text-right">Total Tagihan</th>
            <th class="py-3 px-3 text-right">Sudah Dibayar</th>
            <th class="py-3 px-3 text-right">Sisa Tagihan</th>
            <th class="py-3 px-3 text-center">Status</th>
            <th class="py-3 px-3 text-right">Aksi</th>
          {/snippet}

          {#snippet row(project, index)}
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
              <td class="py-3 px-3 text-center font-bold text-slate-400 font-mono text-xs">{index + 1}</td>
              <td class="py-3 px-3">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-black text-xs shrink-0">
                    {(project.app_name || 'V').substring(0, 2).toUpperCase()}
                  </div>
                  <div>
                    <span class="font-bold text-slate-900 dark:text-white block text-xs">{project.app_name}</span>
                    <div class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                      <span>{project.client?.name || 'Klien Umum'}</span>
                      <span>•</span>
                      <span class="text-[10px] font-semibold text-slate-400">{project.client?.industry || 'Teknologi'}</span>
                    </div>
                  </div>
                </div>
              </td>
              <td class="py-3 px-3 text-xs text-slate-600 dark:text-slate-400 font-mono">
                {project.lead_pic || '-'}
              </td>
              <td class="py-3 px-3 text-right font-mono font-bold text-slate-900 dark:text-white text-xs">
                {formatIDR(project.grand_total)}
              </td>
              <td class="py-3 px-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400 text-xs">
                {formatIDR(project.paid_amount)}
              </td>
              <td class="py-3 px-3 text-right font-mono font-bold {project.remaining_amount <= 0 ? 'text-slate-400' : 'text-rose-500'} text-xs">
                {formatIDR(project.remaining_amount)}
              </td>
              <td class="py-3 px-3 text-center">
                {#if project.payment_status === 'Lunas'}
                  <Badge variant="success" size="sm">Lunas</Badge>
                {:else if project.payment_status === 'DP / Termin'}
                  <Badge variant="info" size="sm">DP / Termin</Badge>
                {:else}
                  <Badge variant="error" size="sm">Belum Bayar</Badge>
                {/if}
              </td>
              <td class="py-3 px-3 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <a
                    href={`/admin/tagihan/invoice/${project.id}`}
                    class="min-h-[28px] px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 font-bold text-[11px] transition inline-flex items-center gap-1"
                    title="Buka Lembar Invoice Resmi Cetak"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    <span>Faktur</span>
                  </a>

                  <button
                    type="button"
                    onclick={() => openInvoiceModal(project)}
                    class="min-h-[28px] px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] shadow-xs transition flex items-center gap-1 cursor-pointer"
                    title="Lihat Detail Invoice & Pembayaran"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span>Detail Tagihan</span>
                  </button>
                </div>
              </td>
            </tr>
          {/snippet}

          {#snippet card(project)}
            <div class="space-y-3">
              <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-black text-xs shrink-0">
                    {(project.app_name || 'V').substring(0, 2).toUpperCase()}
                  </div>
                  <div>
                    <h4 class="text-xs font-bold text-slate-900 dark:text-white">{project.app_name}</h4>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{project.client?.name || 'Klien Umum'}</p>
                  </div>
                </div>
                <div>
                  {#if project.payment_status === 'Lunas'}
                    <Badge variant="success" size="sm">Lunas</Badge>
                  {:else if project.payment_status === 'DP / Termin'}
                    <Badge variant="info" size="sm">DP / Termin</Badge>
                  {:else}
                    <Badge variant="error" size="sm">Belum Bayar</Badge>
                  {/if}
                </div>
              </div>

              <div class="grid grid-cols-2 gap-2 text-xs font-mono p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800">
                <div>
                  <span class="text-[10px] text-slate-400 font-sans block">Total Tagihan:</span>
                  <span class="font-bold text-slate-900 dark:text-white">{formatIDR(project.grand_total)}</span>
                </div>
                <div>
                  <span class="text-[10px] text-slate-400 font-sans block">Sudah Dibayar:</span>
                  <span class="font-bold text-emerald-600 dark:text-emerald-400">{formatIDR(project.paid_amount)}</span>
                </div>
                <div class="col-span-2 pt-1 border-t border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between">
                  <span class="text-[10px] text-slate-400 font-sans">Sisa Kewajiban:</span>
                  <span class="font-bold {project.remaining_amount <= 0 ? 'text-slate-400' : 'text-rose-500'}">{formatIDR(project.remaining_amount)}</span>
                </div>
              </div>

              <div class="flex items-center justify-end gap-2 pt-1">
                <a
                  href={`/admin/tagihan/invoice/${project.id}`}
                  class="min-h-[28px] px-3 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 font-bold text-[11px] inline-flex items-center gap-1"
                >
                  Faktur ↗
                </a>
                <button
                  type="button"
                  onclick={() => openInvoiceModal(project)}
                  class="min-h-[28px] px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] shadow-xs transition cursor-pointer"
                >
                  Detail Tagihan
                </button>
              </div>
            </div>
          {/snippet}
        </DataTable>
      </div>

    <!-- ============================================== -->
    <!-- SUB-TAB 2: MASTER TARIF STANDAR & RIWAYAT      -->
    <!-- ============================================== -->
    {:else if activeMainTab === 'rates'}
      <div class="space-y-6">
        
        <!-- Header Master Settingan Tarif & Tombol Aksi (L1 Macro Surface) -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
              </svg>
            </div>
            <div>
              <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Master Settingan Tarif Standar & Riwayat</h2>
              <p class="text-xs text-slate-500 dark:text-slate-400">Pusat konfigurasi acuan harga resmi Menu Aplikasi / Modul Kerja dan Tarif Pondasi Sistem Inti.</p>
            </div>
          </div>

          <button
            type="button"
            onclick={() => openRateUpdateModal()}
            class="min-h-[36px] px-4 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer shrink-0"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
            <span>Sesuaikan Tarif Standar</span>
          </button>
        </div>

        <!-- Info Ketentuan Tarif Box -->
        <div class="p-4 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/30 border border-indigo-200/80 dark:border-indigo-800/80 flex items-start gap-3">
          <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="text-xs text-indigo-950 dark:text-indigo-200 space-y-1">
            <p>
              <strong class="font-bold">Tarif Acuan Standar Valora OS:</strong> Tarif di bawah merupakan harga acuan standar resmi untuk komponen pondasi sistem, otentikasi, modul aplikasi, dan skema basis data. Penyesuaian tarif acuan standar ini hanya berlaku sebagai nilai awal default saat menambah menu/modul baru.
            </p>
            <p class="text-[11px] text-indigo-800 dark:text-indigo-300">
              <strong class="font-bold">Jaminan Immutabilitas Data (Snapshot):</strong> Perubahan tarif acuan standar di halaman ini <span class="underline font-bold">tidak akan mengubah</span> valuasi proyek eksisting maupun transaksi/kuitansi yang sudah tersimpan sebelumnya.
            </p>
          </div>
        </div>

        <!-- ======================================================= -->
        <!-- TABEL 1: TARIF STANDAR SISTEM & KOMPONEN INTI           -->
        <!-- ======================================================= -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-2xs space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Tarif Standar Fitur & Komponen Sistem</span>
              </h3>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">
                Tarif acuan resmi landing page, otentikasi login/register mandiri, menu aplikasi/modul kerja, skema database, dan maintenance.
              </p>
            </div>
            <span class="px-3 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
              {systemCoreRates.length} Komponen Aktif
            </span>
          </div>

          {#if loadingRates}
            <div class="p-8 text-center text-xs text-slate-400">
              <div class="w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
              <span>Memuat data tarif standar...</span>
            </div>
          {:else if systemCoreRates.length === 0}
            <div class="p-8 text-center text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-950/40 rounded-xl border border-dashed border-slate-200 dark:border-slate-800">
              Belum ada komponen tarif terdaftar.
            </div>
          {:else}
            <!-- Desktop Table View -->
            <div class="hidden sm:block overflow-x-auto">
              <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-slate-50/90 dark:bg-slate-900/90 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                  <tr>
                    <th class="py-3 px-3 text-center w-10">No</th>
                    <th class="py-3 px-3 w-[36%]">Item Fitur / Pondasi Sistem</th>
                    <th class="py-3 px-3 w-[18%]">Satuan Biaya</th>
                    <th class="py-3 px-3 font-bold text-indigo-600 dark:text-indigo-400 w-[22%]">Tarif Standar Saat Ini</th>
                    <th class="py-3 px-3 w-[12%]">Status</th>
                    <th class="py-3 px-3 text-right w-[12%]">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-sans">
                  {#each systemCoreRates as item, idx}
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                      <td class="py-3 px-3 text-center font-bold text-slate-400 font-mono">{idx + 1}</td>
                      <td class="py-3 px-3">
                        <span class="font-bold text-slate-900 dark:text-white block text-xs">{item.name}</span>
                        <span class="text-[10.5px] text-slate-400 dark:text-slate-500 font-mono">{item.description || item.rate_key}</span>
                      </td>
                      <td class="py-3 px-3 text-slate-500 dark:text-slate-400 font-medium">
                        {item.unit}
                      </td>
                      <td class="py-3 px-3 font-extrabold text-indigo-600 dark:text-indigo-400 font-mono text-sm">
                        {formatIDR(item.price)}
                      </td>
                      <td class="py-3 px-3">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                          Aktif
                        </span>
                      </td>
                      <td class="py-3 px-3 text-right">
                        <button
                          type="button"
                          onclick={() => openRateUpdateModal(item.rate_key)}
                          class="min-h-[26px] px-3 py-1 rounded-xl bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 font-bold text-[11px] transition cursor-pointer"
                        >
                          Ubah Tarif
                        </button>
                      </td>
                    </tr>
                  {/each}
                </tbody>
              </table>
            </div>

            <!-- Mobile Card Stack View (RULE-12) -->
            <div class="sm:hidden space-y-2.5">
              {#each systemCoreRates as item, idx}
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 space-y-2">
                  <div class="flex items-start justify-between gap-2">
                    <div>
                      <span class="text-xs font-bold text-slate-900 dark:text-white block">{item.name}</span>
                      <span class="text-[10px] text-slate-400 font-mono">{item.unit}</span>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                      Aktif
                    </span>
                  </div>
                  <div class="flex items-center justify-between pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 font-mono">
                      {formatIDR(item.price)}
                    </span>
                    <button
                      type="button"
                      onclick={() => openRateUpdateModal(item.rate_key)}
                      class="min-h-[26px] px-2.5 py-0.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[11px] transition"
                    >
                      Ubah Tarif
                    </button>
                  </div>
                </div>
              {/each}
            </div>
          {/if}
        </div>

        <!-- ======================================================= -->
        <!-- TABEL 2: RIWAYAT PERUBAHAN TARIF STANDAR (AUDIT TRAIL)  -->
        <!-- ======================================================= -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-2xs space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Tabel Riwayat Perubahan Tarif Standar (Audit Trail)</span>
              </h3>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">Rekam jejak perubahan tarif terdahulu beserta alasan tertulis dan penanggung jawab.</p>
            </div>
            
            <div class="flex items-center gap-2">
              <input
                type="text"
                bind:value={historySearch}
                placeholder="Cari riwayat perubahan..."
                class="min-h-[32px] px-3 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
              <button
                type="button"
                onclick={exportHistoriesCsv}
                disabled={rateHistories.length === 0}
                class="min-h-[32px] px-3 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-40"
                title="Ekspor CSV Riwayat Perubahan Tarif"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>Unduh CSV</span>
              </button>
            </div>
          </div>

          {#if loadingRates}
            <div class="p-8 text-center text-xs text-slate-400">
              <div class="w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
              <span>Memuat riwayat perubahan tarif...</span>
            </div>
          {:else if filteredHistories.length === 0}
            <div class="p-8 text-center text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-950/40 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 space-y-1">
              <p class="font-bold">Belum ada riwayat perubahan tarif.</p>
              <p class="text-[11px] text-slate-400">Setiap penyesuaian tarif acuan akan tercatat secara otomatis di sini.</p>
            </div>
          {:else}
            <!-- Desktop Table View -->
            <div class="hidden sm:block overflow-x-auto">
              <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-slate-50/90 dark:bg-slate-900/90 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                  <tr>
                    <th class="py-3 px-3 text-center w-10">No</th>
                    <th class="py-3 px-3 w-[16%]">Tanggal Update</th>
                    <th class="py-3 px-3 w-[22%]">Item Fitur / Modul</th>
                    <th class="py-3 px-3 w-[20%]">Penyesuaian Tarif</th>
                    <th class="py-3 px-3 w-[26%]">Keterangan / Alasan</th>
                    <th class="py-3 px-3 w-[16%]">Diperbarui Oleh</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-sans">
                  {#each filteredHistories as hist, idx}
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                      <td class="py-3 px-3 text-center font-bold text-slate-400 font-mono">{idx + 1}</td>
                      <td class="py-3 px-3 font-mono text-slate-600 dark:text-slate-400 text-xs">{formatDate(hist.created_at)}</td>
                      <td class="py-3 px-3 font-bold text-slate-900 dark:text-white text-xs">{hist.item_name || hist.rate_key}</td>
                      <td class="py-3 px-3 font-mono">
                        <div class="flex items-center gap-1.5">
                          <span class="text-slate-400 line-through text-[11px]">{formatIDR(hist.old_price)}</span>
                          <span class="text-slate-400 text-[10px]">➔</span>
                          <span class="font-extrabold text-emerald-600 dark:text-emerald-400 text-xs">{formatIDR(hist.new_price)}</span>
                        </div>
                      </td>
                      <td class="py-3 px-3 text-slate-600 dark:text-slate-400 text-xs italic">{hist.reason || '-'}</td>
                      <td class="py-3 px-3 text-slate-700 dark:text-slate-300 font-semibold text-xs">{hist.updated_by || 'Admin Valora'}</td>
                    </tr>
                  {/each}
                </tbody>
              </table>
            </div>

            <!-- Mobile Card Stack View (RULE-12) -->
            <div class="sm:hidden space-y-2.5">
              {#each filteredHistories as hist, idx}
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 space-y-1.5 text-xs">
                  <div class="flex items-center justify-between text-[11px] text-slate-400 font-mono">
                    <span>{formatDate(hist.created_at)}</span>
                    <span class="font-sans font-semibold text-slate-600 dark:text-slate-300">{hist.updated_by || 'Admin Valora'}</span>
                  </div>
                  <div class="font-bold text-slate-900 dark:text-white">
                    {hist.item_name || hist.rate_key}
                  </div>
                  <div class="flex items-center gap-1.5 font-mono text-xs pt-0.5">
                    <span class="text-slate-400 line-through text-[11px]">{formatIDR(hist.old_price)}</span>
                    <span class="text-slate-400 text-[10px]">➔</span>
                    <span class="font-extrabold text-emerald-600 dark:text-emerald-400">{formatIDR(hist.new_price)}</span>
                  </div>
                  {#if hist.reason}
                    <p class="text-[11px] text-slate-500 italic pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                      "{hist.reason}"
                    </p>
                  {/if}
                </div>
              {/each}
            </div>
          {/if}
        </div>

      </div>
    {/if}

  </div>
</div>

<!-- Modal 1: Detail Invoice & Pembayaran Proyek -->
<InvoiceModal
  show={showInvoiceModal}
  projectId={selectedInvoiceProjectId}
  onclose={() => (showInvoiceModal = false)}
  onpaymentrecorded={() => {
    loadBillingData();
  }}
/>

<!-- Modal 2: Perbarui Tarif Standar -->
<UpdateMasterRateModal
  show={showRateModal}
  rates={ratesList}
  initialRateKey={selectedRateKey}
  onclose={() => (showRateModal = false)}
  onsave={() => {
    loadRatesData();
    loadBillingData();
  }}
/>