<script>
  import Modal from '$lib/components/ui/Modal.svelte';
  import ElementLoader from '$lib/components/ui/ElementLoader.svelte';
  import { toastState } from '$lib/state/toast.svelte.js';
  import { api } from '$lib/api/client.js';
  import { terbilang } from '$lib/utils/terbilang.js';
  import { exportInvoiceToWord } from '$lib/utils/documentExport.js';

  let {
    show = false,
    projectId = null,
    onclose = () => {},
    onpaymentrecorded = () => {}
  } = $props();

  let loading = $state(false);
  let error = $state(null);
  let invoiceData = $state(null);

  // Form Catat Pembayaran
  let payAmount = $state(0);
  let payDate = $state(new Date().toISOString().split('T')[0]);
  let payMethod = $state('Transfer BCA');
  let payRef = $state('');
  let submittingPayment = $state(false);
  let deletingPaymentId = $state(null);

  // Perhitungan batas masuk akal
  let remainingBalance = $derived(Number(invoiceData?.project?.remaining_amount) || 0);
  let grandTotalBalance = $derived(Number(invoiceData?.project?.grand_total) || 0);
  let isAmountExceeded = $derived(payAmount > remainingBalance);
  let isAmountInvalid = $derived(payAmount <= 0 || isAmountExceeded);

  let agency = $derived(invoiceData?.agency || {
    agency_name: 'Valora Digital Solusindo',
    legal_name: 'PT Valora Digital Solusindo',
    bank_name: 'Bank BCA',
    bank_account_no: '827-091-8888',
    bank_account_name: 'PT Valora Digital Solusindo',
    secondary_bank_name: 'Bank Mandiri',
    secondary_bank_account_no: '132-00-998877-6',
    secondary_bank_account_name: 'PT Valora Digital Solusindo',
    hotline_wa: '+62 812-9988-7766'
  });

  $effect(() => {
    if (show && projectId) {
      loadInvoiceDetails();
    }
  });

  async function loadInvoiceDetails() {
    loading = true;
    error = null;
    try {
      const res = await api.request(`/api/v1/admin/billing/projects/${projectId}/invoice`);
      if (res) {
        invoiceData = res;
        // Default payment amount to remaining amount
        payAmount = Number(res.project?.remaining_amount) || 0;
      }
    } catch (err) {
      error = err?.message || 'Gagal memuat rincian invoice dan tagihan.';
    } finally {
      loading = false;
    }
  }

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
      return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch {
      return dateStr;
    }
  }

  function setQuickAmount(type) {
    if (type === 'full') {
      payAmount = remainingBalance;
    } else if (type === 'half') {
      payAmount = Math.max(1000, Math.round(remainingBalance * 0.5));
    } else if (type === 'dp30') {
      payAmount = Math.max(1000, Math.round(grandTotalBalance * 0.3));
    }
  }

  async function handleRecordPayment(e) {
    e?.preventDefault();
    if (payAmount <= 0) {
      toastState.error('Nominal pembayaran harus lebih besar dari Rp 0.', 'Validasi Gagal');
      return;
    }
    if (payAmount > remainingBalance) {
      toastState.error(`Nominal pembayaran (${formatIDR(payAmount)}) tidak boleh melebihi sisa tagihan (${formatIDR(remainingBalance)}).`, 'Validasi Gagal');
      return;
    }

    submittingPayment = true;
    try {
      const res = await api.request(`/api/v1/admin/billing/projects/${projectId}/payments`, {
        method: 'POST',
        body: {
          amount: Number(payAmount),
          payment_date: payDate,
          payment_method: payMethod,
          reference_no: payRef.trim() || null
        }
      });

      toastState.success(res?.message || 'Pembayaran berhasil dicatat.', 'Pembayaran Masuk');
      payRef = '';
      await loadInvoiceDetails();
      onpaymentrecorded();
    } catch (err) {
      toastState.error(err?.message || 'Gagal mencatat transaksi pembayaran.', 'Gagal');
    } finally {
      submittingPayment = false;
    }
  }

  async function handleDeletePayment(payment) {
    if (!confirm(`Hapus catatan transaksi pembayaran Rp ${formatIDR(payment.amount)} via ${payment.payment_method}?\n\nSisa tagihan proyek akan kembali disesuaikan secara otomatis.`)) {
      return;
    }

    deletingPaymentId = payment.id;
    try {
      const res = await api.request(`/api/v1/admin/billing/payments/${payment.id}`, {
        method: 'DELETE'
      });
      toastState.success(res?.message || 'Catatan pembayaran berhasil dihapus.', 'Pembayaran Dibatalkan');
      await loadInvoiceDetails();
      onpaymentrecorded();
    } catch (err) {
      toastState.error(err?.message || 'Gagal menghapus catatan pembayaran.', 'Gagal');
    } finally {
      deletingPaymentId = null;
    }
  }

  function sendWhatsAppInvoice() {
    const p = invoiceData?.project;
    if (!p) return;

    let phone = p.client?.phone || '';
    phone = phone.replace(/[^0-9]/g, '');
    if (phone.startsWith('0')) {
      phone = '62' + phone.substring(1);
    }

    let bankText = `- ${agency.bank_name}: ${agency.bank_account_no} a.n. ${agency.bank_account_name}`;
    if (agency.secondary_bank_name && agency.secondary_bank_account_no) {
      bankText += `\n- ${agency.secondary_bank_name}: ${agency.secondary_bank_account_no} a.n. ${agency.secondary_bank_account_name}`;
    }
    const agencyTitle = (agency.legal_name || agency.agency_name || 'VALORA AGENCY').toUpperCase();

    const text = encodeURIComponent(
      `*INVOICE TAGIHAN RESMI — ${agencyTitle}*\n` +
      `No. Invoice: ${p.invoice_number}\n` +
      `Client: ${p.client?.name || '-'}\n` +
      `Aplikasi: ${p.app_name} (${p.project_type === 'maintenance' ? 'Maintenance' : 'Development'})\n\n` +
      `Total Tagihan: ${formatIDR(p.grand_total)}\n` +
      `Sudah Dibayar: ${formatIDR(p.paid_amount)}\n` +
      `Sisa Piutang: ${formatIDR(p.remaining_amount)}\n` +
      `Status: ${p.payment_status.toUpperCase()}\n\n` +
      `Rekening Pembayaran Resmi:\n` +
      `${bankText}\n\n` +
      `Konfirmasi via Hotline WA: ${agency.hotline_wa || '-'}\n` +
      `Terima kasih atas kerja sama dan kepercayaan Anda.`
    );

    window.open(`https://wa.me/${phone}?text=${text}`, '_blank');
  }

  function printInvoice() {
    if (projectId) {
      window.open(`/admin/tagihan/invoice/${projectId}?print=true`, '_blank');
    } else {
      window.print();
    }
  }

  let exportingWord = $state(false);

  async function handleExportWord() {
    if (!invoiceData || exportingWord) return;
    exportingWord = true;
    try {
      const ok = await exportInvoiceToWord(invoiceData, agency);
      if (ok) {
        toastState.success('Faktur Invoice berhasil diekspor ke format Microsoft Word (.doc).', 'Ekspor Berhasil');
      }
    } catch (err) {
      toastState.error('Gagal mengekspor invoice ke format Word.', 'Ekspor Gagal');
    } finally {
      exportingWord = false;
    }
  }
</script>

<Modal
  {show}
  title="Detail Tagihan & Invoice Resmi"
  subtitle={invoiceData?.project?.invoice_number ? `Nomor Invoice: ${invoiceData.project.invoice_number}` : 'Memuat data invoice...'}
  maxWidth="max-w-5xl"
  size="2xl"
  {onclose}
>
  {#if loading}
    <div class="py-12 flex flex-col items-center justify-center gap-2">
      <ElementLoader size="md" variant="indigo" text="Memuat berkas invoice dan rincian transaksi..." />
    </div>
  {:else if error}
    <div class="p-8 text-center space-y-2">
      <p class="text-xs font-bold text-rose-600 dark:text-rose-400">Gagal Memuat Invoice</p>
      <p class="text-[11px] text-slate-500">{error}</p>
      <button
        type="button"
        onclick={loadInvoiceDetails}
        class="mt-2 px-3 py-1.5 rounded-xl bg-indigo-600 text-white text-xs font-semibold cursor-pointer"
      >
        Coba Lagi
      </button>
    </div>
  {:else if invoiceData}
    <div class="space-y-5 text-xs">
      
      <!-- Card Info Klien & Status Pembayaran -->
      <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 mb-0.5">
            <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider {invoiceData.project?.project_type === 'maintenance' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20'}">
              {invoiceData.project?.project_type === 'maintenance' ? 'Pemeliharaan SLA' : 'Pengembangan Baru'}
            </span>
            <span class="text-xs font-bold text-slate-900 dark:text-white">{invoiceData.project?.client?.name || '-'}</span>
          </div>
          <p class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">{invoiceData.project?.app_name}</p>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 font-mono">
            PIC: {invoiceData.project?.lead_pic || 'Tim Valora'} • Kontak: {invoiceData.project?.client?.phone || invoiceData.project?.client?.email || '-'}
          </p>
        </div>

        <div class="text-left sm:text-right shrink-0">
          <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold block mb-1">Status Pembayaran:</span>
          {#if invoiceData.project?.payment_status === 'Lunas'}
            <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
              LUNAS
            </span>
          {:else if invoiceData.project?.payment_status === 'DP / Termin'}
            <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
              DP / TERMIN
            </span>
          {:else}
            <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
              BELUM BAYAR
            </span>
          {/if}
        </div>
      </div>

      <!-- Tabel Rincian Item Tagihan (Fill View Tanpa Scroll Horizontal) -->
      <div class="border border-slate-200/80 dark:border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-4 py-2.5 bg-slate-100/70 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 font-bold text-slate-700 dark:text-slate-300 text-xs flex items-center justify-between">
          <span>Rincian Komponen Arsitektur & Biaya Project</span>
          <span class="font-mono text-[10px] text-slate-400">{invoiceData.items?.length || 0} Item Tagihan</span>
        </div>
        <div class="max-h-60 overflow-y-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead class="bg-slate-50 dark:bg-slate-900/90 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
              <tr>
                <th class="py-2.5 px-3 w-[20%]">Kategori</th>
                <th class="py-2.5 px-3 w-[42%]">Deskripsi Item / Modul</th>
                <th class="py-2.5 px-3 text-right w-[19%]">Tarif Setting</th>
                <th class="py-2.5 px-3 text-right w-[19%]">Tarif Penyesuaian Client</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-sans">
              {#each (invoiceData.items || []) as item}
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                  <td class="py-2 px-3">
                    <span class="px-1.5 py-0.5 rounded text-[9.5px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 inline-block">
                      {item.category}
                    </span>
                  </td>
                  <td class="py-2 px-3">
                    <span class="font-semibold text-slate-900 dark:text-white block">{item.description}</span>
                    {#if item.capabilities && item.capabilities.length > 0}
                      <div class="flex flex-wrap gap-1 pt-1">
                        {#each item.capabilities as cap}
                          <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-mono">
                            {cap}
                          </span>
                        {/each}
                      </div>
                    {/if}
                    {#if item.note}
                      <span class="text-[10px] text-amber-600 dark:text-amber-400 font-mono italic block pt-0.5">Catatan: {item.note}</span>
                    {/if}
                  </td>
                  <td class="py-2 px-3 text-right font-mono text-slate-600 dark:text-slate-300">
                    {formatIDR(item.setting_price ?? item.unit_price)}
                  </td>
                  <td class="py-2 px-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                    {formatIDR(item.adjusted_price ?? item.subtotal)}
                  </td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>
      </div>

      <!-- Ringkasan Perhitungan Pembayaran -->
      <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-800 space-y-2">
        <div class="flex items-center justify-between text-slate-600 dark:text-slate-300 text-xs">
          <span>Total Tarif Awal (Tarif Setting):</span>
          <span class="font-bold text-slate-700 dark:text-slate-200 font-mono">
            {formatIDR(invoiceData.project?.total_setting_price || invoiceData.project?.grand_total)}
          </span>
        </div>
        <div class="flex items-center justify-between text-slate-700 dark:text-slate-200 text-xs">
          <span class="font-semibold">Total Tarif Penyesuaian Client:</span>
          <span class="font-bold text-indigo-600 dark:text-indigo-400 font-mono text-sm">
            {formatIDR(invoiceData.project?.total_adjusted_price || invoiceData.project?.grand_total)}
          </span>
        </div>
        <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400 font-semibold text-xs">
          <span>Jumlah Yang Sudah Dibayarkan:</span>
          <span class="font-bold font-mono">
            (-) {formatIDR(invoiceData.project?.paid_amount)}
          </span>
        </div>
        <div class="pt-2 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between text-sm font-extrabold">
          <span class="text-amber-600 dark:text-amber-400">Sisa Tagihan (Belum Dibayar):</span>
          <span class="text-amber-600 dark:text-amber-400 font-mono text-base">
            {formatIDR(invoiceData.project?.remaining_amount)}
          </span>
        </div>
      </div>

      <!-- Riwayat Transaksi & Pembayaran Masuk (Bisa Dihapus) -->
      <div class="border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 bg-white dark:bg-slate-900/90 space-y-2.5">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Riwayat Transaksi & Pembayaran Masuk</span>
          </span>
          <span class="text-[10px] font-semibold text-slate-400 font-mono">
            {invoiceData.payments?.length || 0} Pembayaran Tercatat
          </span>
        </div>

        {#if !invoiceData.payments || invoiceData.payments.length === 0}
          <p class="text-center py-4 text-slate-400 text-xs italic">
            Belum ada transaksi pembayaran yang dicatat untuk proyek ini.
          </p>
        {:else}
          <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
            <table class="w-full text-left border-collapse text-xs">
              <thead class="bg-slate-50 dark:bg-slate-800/70 text-[10px] text-slate-500 uppercase font-bold border-b border-slate-200 dark:border-slate-800">
                <tr>
                  <th class="py-2.5 px-3 text-center w-10">No</th>
                  <th class="py-2.5 px-3 w-[14%]">Tgl Bayar</th>
                  <th class="py-2.5 px-3 font-bold text-emerald-600 dark:text-emerald-400 w-[17%]">Nominal (Rp)</th>
                  <th class="py-2.5 px-3 w-[16%]">Metode Pembayaran</th>
                  <th class="py-2.5 px-3 w-[22%]">Keterangan / No. Ref</th>
                  <th class="py-2.5 px-3 w-[13%]">Dicatat Oleh</th>
                  <th class="py-2.5 px-3 text-right w-[18%]">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-sans">
                {#each invoiceData.payments as pay, idx}
                  <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition">
                    <td class="py-2.5 px-3 text-center font-bold text-slate-400 font-mono text-[11px]">{idx + 1}</td>
                    <td class="py-2.5 px-3 text-slate-600 dark:text-slate-300 font-mono">{formatDate(pay.payment_date)}</td>
                    <td class="py-2.5 px-3 font-extrabold text-emerald-600 dark:text-emerald-400 font-mono">{formatIDR(pay.amount)}</td>
                    <td class="py-2.5 px-3 text-slate-700 dark:text-slate-300">
                      <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 inline-block">
                        {pay.payment_method}
                      </span>
                    </td>
                    <td class="py-2.5 px-3 text-slate-500 dark:text-slate-400">{pay.reference_no || '-'}</td>
                    <td class="py-2.5 px-3 text-slate-400 text-[10px]">{pay.recorded_by || 'Admin'}</td>
                    <td class="py-2.5 px-3 text-right">
                      <div class="flex items-center justify-end gap-1.5 flex-wrap">
                        <a
                          href={`/admin/tagihan/kuitansi/${pay.id}`}
                          target="_blank"
                          rel="noopener noreferrer"
                          class="px-2 py-1 rounded-lg text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 text-[11px] font-bold transition border border-emerald-200/80 dark:border-emerald-800/60 inline-flex items-center gap-1 cursor-pointer"
                          title="Lihat & Cetak Kuitansi Resmi Pembayaran Ini"
                        >
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                          </svg>
                          <span>Kuitansi ↗</span>
                        </a>

                        <button
                          type="button"
                          onclick={() => handleDeletePayment(pay)}
                          disabled={deletingPaymentId === pay.id}
                          aria-label="Hapus catatan pembayaran Rp {pay.amount}"
                          class="px-2 py-1 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-[11px] font-bold transition border border-rose-200/80 dark:border-rose-900/40 cursor-pointer disabled:opacity-40 inline-flex items-center gap-1"
                          title="Hapus catatan pembayaran ini dan pulihkan sisa tagihan"
                        >
                          {#if deletingPaymentId === pay.id}
                            <span class="text-[10px]">...</span>
                          {:else}
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Hapus</span>
                          {/if}
                        </button>
                      </div>
                    </td>
                  </tr>
                {/each}
              </tbody>
            </table>
          </div>
        {/if}
      </div>

      <!-- Panel Form Catat Pembayaran Baru (Presisi & Batas Masuk Akal) -->
      {#if invoiceData.project?.remaining_amount > 0}
        <form onsubmit={handleRecordPayment} class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/80 border border-slate-200/90 dark:border-slate-800 space-y-3.5 shadow-2xs">
          <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800 pb-2.5">
            <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
              <div class="w-6 h-6 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
              </div>
              <span>Catat / Tambah Pembayaran Klien Baru</span>
            </span>

            <div class="flex items-center gap-1.5 text-xs">
              <span class="text-[10px] uppercase font-bold text-slate-400">Sisa Tagihan:</span>
              <span class="font-mono font-black text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 px-2 py-0.5 rounded-lg border border-rose-200/80 dark:border-rose-900/40">
                {formatIDR(remainingBalance)}
              </span>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-12 gap-3.5">
            <!-- Input Nominal Pembayaran -->
            <div class="md:col-span-5 space-y-1.5">
              <label for="pay-amount-input" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Nominal Pembayaran (Rp) *
              </label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-bold text-slate-400">Rp</span>
                <input
                  type="number"
                  id="pay-amount-input"
                  bind:value={payAmount}
                  min="1000"
                  max={remainingBalance}
                  step="1000"
                  required
                  class="w-full pl-9 pr-3.5 py-2 rounded-xl bg-white dark:bg-slate-900 border {isAmountExceeded ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-200 dark:border-slate-700'} text-xs font-mono font-bold text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-2xs"
                  placeholder="Contoh: 5000000"
                />
              </div>

              <!-- Real-Time Terbilang & Validasi Batas -->
              <div class="min-h-[18px]">
                {#if isAmountExceeded}
                  <p class="text-[10.5px] text-rose-600 dark:text-rose-400 font-semibold flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>Nominal melebihi sisa tagihan! Maksimal {formatIDR(remainingBalance)}.</span>
                  </p>
                {:else if payAmount > 0}
                  <p class="text-[10px] text-indigo-600 dark:text-indigo-400 italic leading-snug">
                    Terbilang: "{terbilang(payAmount)}"
                  </p>
                {/if}
              </div>

              <!-- Tombol Shortcut Batas Masuk Akal -->
              <div class="flex items-center gap-1.5 flex-wrap pt-0.5">
                <button
                  type="button"
                  onclick={() => setQuickAmount('full')}
                  class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 transition cursor-pointer"
                >
                  Pelunasan Sisa (100%)
                </button>
                <button
                  type="button"
                  onclick={() => setQuickAmount('half')}
                  class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 transition cursor-pointer"
                >
                  50% Sisa
                </button>
                {#if Number(invoiceData.project?.paid_amount) === 0}
                  <button
                    type="button"
                    onclick={() => setQuickAmount('dp30')}
                    class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/40 dark:hover:bg-amber-900/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 transition cursor-pointer"
                  >
                    DP 30% Total
                  </button>
                {/if}
              </div>
            </div>

            <!-- Tanggal Bayar -->
            <div class="md:col-span-3 space-y-1.5">
              <label for="pay-date-input" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Tanggal Bayar *
              </label>
              <input
                type="date"
                id="pay-date-input"
                bind:value={payDate}
                required
                class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-2xs font-mono"
              />
            </div>

            <!-- Metode Pembayaran -->
            <div class="md:col-span-4 space-y-1.5">
              <label for="pay-method-select" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Metode Bayar *
              </label>
              <select
                id="pay-method-select"
                bind:value={payMethod}
                class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer shadow-2xs"
              >
                <option value={`Transfer ${agency.bank_name}`}>Transfer {agency.bank_name} ({agency.bank_account_no})</option>
                {#if agency.secondary_bank_name && agency.secondary_bank_account_no}
                  <option value={`Transfer ${agency.secondary_bank_name}`}>Transfer {agency.secondary_bank_name} ({agency.secondary_bank_account_no})</option>
                {/if}
                <option value="Transfer Bank Lain">Transfer Bank Lain</option>
                <option value="Tunai / Cash">Tunai / Cash Kantor</option>
                <option value="QRIS / E-Wallet">QRIS / E-Wallet</option>
              </select>
            </div>

            <!-- Catatan / No. Referensi & Tombol Simpan -->
            <div class="md:col-span-12 flex flex-col sm:flex-row items-center gap-3 pt-1">
              <div class="flex-1 w-full">
                <input
                  type="text"
                  bind:value={payRef}
                  placeholder="Catatan / No. Referensi Transfer (Contoh: Bukti Transfer TRX-BCA-9812 / Pelunasan Termin 1)"
                  class="w-full px-3.5 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-2xs"
                />
              </div>

              <button
                type="submit"
                disabled={submittingPayment || isAmountInvalid}
                class="w-full sm:w-auto px-6 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed shrink-0 active:scale-95"
              >
                {#if submittingPayment}
                  <span>Menyimpan...</span>
                {:else}
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                  </svg>
                  <span>Simpan Pembayaran Masuk</span>
                {/if}
              </button>
            </div>
          </div>
        </form>
      {:else}
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 text-center text-xs font-bold text-emerald-700 dark:text-emerald-300 flex items-center justify-center gap-2">
          <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>Tagihan proyek ini sudah 100% LUNAS. Seluruh pembayaran telah diverifikasi tercatat.</span>
        </div>
      {/if}

    </div>
  {/if}

  {#snippet footer()}
    <div class="flex flex-wrap items-center justify-between gap-3 w-full">
      <button
        type="button"
        onclick={onclose}
        class="px-4 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition cursor-pointer"
      >
        Tutup
      </button>

      <div class="flex items-center gap-2">
        <a
          href={`/admin/tagihan/invoice/${projectId}`}
          target="_blank"
          rel="noopener noreferrer"
          class="px-3.5 py-1.5 rounded-xl border border-indigo-200 dark:border-indigo-800 bg-indigo-50/70 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 text-xs font-bold transition inline-flex items-center gap-1.5 hover:bg-indigo-100 dark:hover:bg-indigo-900/60"
          title="Buka Halaman Invoice Khusus Resmi (Format Cetak A4)"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
          </svg>
          <span>Halaman Invoice Resmi ↗</span>
        </a>

        <button
          type="button"
          onclick={sendWhatsAppInvoice}
          class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-xs flex items-center gap-1.5 cursor-pointer"
          title="Kirim rincian invoice ke nomor WhatsApp klien"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          <span>Kirim WA</span>
        </button>

        <button
          type="button"
          onclick={handleExportWord}
          disabled={exportingWord}
          class="px-3.5 py-1.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 hover:bg-blue-100 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 text-xs font-bold transition shadow-2xs flex items-center gap-1.5 cursor-pointer active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed"
          title="Unduh faktur invoice dalam format Microsoft Word (.doc)"
        >
          {#if exportingWord}
            <div class="w-3.5 h-3.5 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
            <span>Mengekspor...</span>
          {:else}
            <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Ekspor Word</span>
          {/if}
        </button>

        <button
          type="button"
          onclick={printInvoice}
          class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 text-white text-xs font-bold transition shadow-xs flex items-center gap-1.5 cursor-pointer"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          <span>Cetak Resmi (A4)</span>
        </button>
      </div>
    </div>
  {/snippet}
</Modal>
