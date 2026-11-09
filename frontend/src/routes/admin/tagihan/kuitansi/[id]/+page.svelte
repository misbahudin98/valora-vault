<script>
  import { onMount } from 'svelte';
  import { page } from '$app/state';
  import { api } from '$lib/api/client.js';
  import { terbilang } from '$lib/utils/terbilang.js';
  import { exportReceiptToWord } from '$lib/utils/documentExport.js';
  import { toastState } from '$lib/state/toast.svelte.js';
  import ElementLoader from '$lib/components/ui/ElementLoader.svelte';

  let paymentId = $derived(page.params.id);

  let loading = $state(true);
  let error = $state(null);
  let receiptData = $state(null);

  let comp = $derived(receiptData?.agency || receiptData?.company || {
    name: 'PT Valora Digital Solusindo',
    agency_name: 'Valora Digital Solusindo',
    legal_name: 'PT Valora Digital Solusindo',
    tagline: 'Software Engineering & Enterprise Web Solutions',
    address: 'Gedung Cyber One, Lantai 5, Jl. H.R. Rasuna Said Kav. 8, Jakarta Selatan 12950',
    operational_address: 'Gedung Cyber One, Lantai 5, Jl. H.R. Rasuna Said Kav. 8, Jakarta Selatan 12950',
    email: 'billing@valora.id',
    phone: '+62 812-9988-7766',
    hotline_wa: '+62 812-9988-7766',
    logo_url: '',
    signatory_name: 'Muhammad Iqbal Murtadho',
    signatory_title: 'Managing Director & Lead Architect'
  });

  onMount(() => {
    if (paymentId) {
      loadReceipt();
    }
  });

  async function loadReceipt() {
    loading = true;
    error = null;
    try {
      const res = await api.request(`/api/v1/admin/billing/payments/${paymentId}/receipt`);
      if (res) {
        receiptData = res;
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === 'true') {
          setTimeout(() => {
            window.print();
          }, 400);
        }
      }
    } catch (err) {
      error = err?.message || 'Gagal memuat dokumen kuitansi resmi.';
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
      return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    } catch {
      return dateStr;
    }
  }

  let exportingWord = $state(false);

  function handlePrint() {
    window.print();
  }

  async function handleExportWord() {
    if (!receiptData || exportingWord) return;
    exportingWord = true;
    try {
      const ok = await exportReceiptToWord(receiptData, comp);
      if (ok) {
        toastState.success('Kuitansi pembayaran berhasil diekspor ke format Microsoft Word (.doc).', 'Ekspor Berhasil');
      }
    } catch (err) {
      toastState.error('Gagal mengekspor kuitansi ke format Word.', 'Ekspor Gagal');
    } finally {
      exportingWord = false;
    }
  }

  function sendWhatsAppReceipt() {
    const r = receiptData?.receipt;
    const p = receiptData?.project;
    const c = receiptData?.client;
    if (!r || !c) return;

    let phone = c.phone || '';
    phone = phone.replace(/[^0-9]/g, '');
    if (phone.startsWith('0')) {
      phone = '62' + phone.substring(1);
    }

    const compName = (comp.legal_name || comp.name || 'PT VALORA DIGITAL SOLUSINDO').toUpperCase();

    const text = encodeURIComponent(
      `*KUITANSI PEMBAYARAN RESMI — ${compName}*\n` +
      `No. Kuitansi: ${r.receipt_number}\n` +
      `Tanggal: ${formatDate(r.payment_date)}\n\n` +
      `Telah Terima Dari: ${c.name}\n` +
      `Jumlah Pembayaran: ${formatIDR(r.amount)}\n` +
      `Terbilang: ${terbilang(r.amount)}\n\n` +
      `Untuk Pembayaran: ${r.reference_no || 'Pembayaran Termin Proyek ' + (p?.app_name || '')}\n` +
      `Metode: ${r.payment_method}\n` +
      `Sisa Tagihan Proyek: ${formatIDR(p?.remaining_amount || 0)}\n\n` +
      `Dokumen kuitansi ini adalah bukti sah penerimaan pembayaran yang diterbitkan resmi oleh ${comp.legal_name || comp.name}.\n` +
      `Terima kasih atas kerja sama dan kepercayaan Anda.`
    );

    window.open(`https://wa.me/${phone}?text=${text}`, '_blank');
  }
</script>

<svelte:head>
  <title>Kuitansi {receiptData?.receipt?.receipt_number || 'Resmi'} — {comp.legal_name || comp.name}</title>
</svelte:head>

<!-- Dedicated Print Styling & WYSIWYG Fidelity -->
<style>
  @media print {
    :global(*) {
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
      box-sizing: border-box !important;
    }
    :global(html),
    :global(body) {
      background: #ffffff !important;
      color: #0f172a !important;
      margin: 0 !important;
      padding: 0 !important;
      width: 100% !important;
      font-size: 11.5px !important;
    }
    :global(#app-sidebar),
    :global(#app-topbar),
    :global(.app-topbar),
    :global(#mobile-thumb-dock),
    :global(.mobile-thumb-dock),
    :global([role="navigation"]),
    .print-hide {
      display: none !important;
    }
    :global(.lg\:pl-60) {
      padding-left: 0 !important;
      margin-left: 0 !important;
    }
    :global(main) {
      padding: 0 !important;
      margin: 0 !important;
      background: transparent !important;
      max-width: 100% !important;
    }
    :global(.min-h-screen) {
      min-height: auto !important;
      background: transparent !important;
      padding: 0 !important;
    }
    .print-receipt-page {
      max-width: 100% !important;
      width: 100% !important;
      margin: 0 auto !important;
      padding: 8mm 10mm !important;
      border: 2px solid #0f172a !important;
      border-radius: 8px !important;
      box-shadow: none !important;
      background: #ffffff !important;
      color: #0f172a !important;
    }
    .print-receipt-page header {
      display: flex !important;
      visibility: visible !important;
    }
    .print-receipt-page footer {
      display: block !important;
      visibility: visible !important;
    }
    .print-receipt-page .print\:grid-cols-2 {
      display: grid !important;
      grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
    .print-receipt-page .print\:flex-row {
      display: flex !important;
      flex-direction: row !important;
    }
    .print-receipt-page .print\:items-start {
      align-items: flex-start !important;
    }
    .print-receipt-page .print\:items-baseline {
      align-items: baseline !important;
    }
    .print-receipt-page .print\:text-right {
      text-align: right !important;
    }
    @page {
      size: A4 portrait;
      margin: 10mm 12mm 12mm 12mm;
    }
    .avoid-break {
      break-inside: avoid !important;
      page-break-inside: avoid !important;
    }
  }
</style>

<div class="min-h-screen bg-slate-100/80 dark:bg-[#070b12] py-4 sm:py-8 px-2 sm:px-4 print:bg-white print:p-0">
  <div class="max-w-3xl mx-auto space-y-4">

    <!-- Top Action Bar (Disembunyikan saat cetak) -->
    <div class="print-hide flex flex-wrap items-center justify-between gap-3 p-3.5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-2xs">
      <div class="flex items-center gap-2">
        <a
          href="/admin/tagihan"
          class="min-h-[36px] px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold transition inline-flex items-center gap-1.5 cursor-pointer active:scale-95"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Kembali ke Tagihan</span>
        </a>
        <span class="text-xs text-slate-400">|</span>
        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
          Kuitansi Pembayaran Sah
        </span>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        {#if receiptData?.project?.id}
          <a
            href={`/admin/tagihan/invoice/${receiptData.project.id}`}
            class="min-h-[36px] px-3 py-1.5 rounded-xl border border-indigo-200 dark:border-indigo-800 bg-indigo-50/70 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 text-xs font-bold transition inline-flex items-center gap-1.5 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 active:scale-95"
            title="Lihat Faktur Invoice Lengkap"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Invoice Proyek ↗</span>
          </a>
        {/if}

        {#if receiptData?.client?.phone}
          <button
            type="button"
            onclick={sendWhatsAppReceipt}
            class="min-h-[36px] px-3 py-1.5 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 text-xs font-bold transition inline-flex items-center gap-1.5 cursor-pointer active:scale-95"
            title="Kirim bukti kuitansi via WhatsApp"
          >
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.969.586 1.838.868 2.796.868 3.179 0 5.766-2.586 5.767-5.766.001-3.18-2.585-5.767-5.767-5.767zm7.597 5.766c-.001 4.19-3.411 7.599-7.598 7.599-1.282 0-2.523-.327-3.623-.949l-4.408 1.156 1.176-4.298c-.687-1.159-1.05-2.488-1.049-3.856.002-4.19 3.412-7.599 7.6-7.599 4.188 0 7.6 3.409 7.602 7.598z" />
            </svg>
            <span>Kirim WA</span>
          </button>
        {/if}

        <!-- Tombol Export Word (.doc) -->
        <button
          type="button"
          onclick={handleExportWord}
          disabled={exportingWord}
          class="min-h-[36px] px-3.5 py-1.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 hover:bg-blue-100 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 text-xs font-bold transition inline-flex items-center gap-1.5 cursor-pointer active:scale-95 shadow-2xs disabled:opacity-60 disabled:cursor-not-allowed"
          title="Unduh kuitansi dalam format Microsoft Word (.doc)"
        >
          {#if exportingWord}
            <div class="w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
            <span>Mengekspor...</span>
          {:else}
            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Ekspor Word (.doc)</span>
          {/if}
        </button>

        <!-- Tombol Cetak / PDF -->
        <button
          type="button"
          onclick={handlePrint}
          class="min-h-[36px] px-4 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-xs inline-flex items-center gap-1.5 cursor-pointer active:scale-95"
          title="Buka dialog cetak atau simpan sebagai file PDF A4 resmi"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          <span>Cetak / Simpan PDF</span>
        </button>
      </div>
    </div>

    <!-- Tampilan Loading & Error -->
    {#if loading}
      <div class="p-16 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 text-center space-y-3">
        <ElementLoader size="lg" variant="indigo" text="Menyiapkan Dokumen Kuitansi Resmi..." />
        <p class="text-xs text-slate-400 font-mono">Memuat otentikasi data transaksi & tanda terima.</p>
      </div>
    {:else if error}
      <div class="p-12 bg-white dark:bg-slate-900 rounded-2xl border border-rose-200 text-center space-y-3">
        <div class="w-12 h-12 mx-auto rounded-full bg-rose-50 text-rose-600 flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <p class="text-sm font-bold text-slate-900 dark:text-white">Gagal Memuat Kuitansi</p>
        <p class="text-xs text-rose-500 max-w-md mx-auto">{error}</p>
        <button
          type="button"
          onclick={loadReceipt}
          class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold cursor-pointer"
        >
          Coba Muat Ulang
        </button>
      </div>
    {:else if receiptData}
      {@const r = receiptData.receipt}
      {@const p = receiptData.project}
      {@const c = receiptData.client}

      <!-- DOKUMEN KUITANSI RESMI (A4/A5 CANVAS) -->
      <article class="print-receipt-page bg-white dark:bg-slate-900 border-2 border-slate-800 dark:border-slate-700 rounded-2xl p-6 sm:p-10 shadow-sm text-slate-800 dark:text-slate-100 font-sans space-y-6 relative overflow-hidden">
        
        <!-- Watermark Latar Belakang Kuitansi -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-[0.03] select-none">
          <span class="text-7xl sm:text-9xl font-black uppercase tracking-widest font-sans text-slate-900">
            {(comp.agency_name || comp.name || 'VALORA').toUpperCase()}
          </span>
        </div>

        <!-- 1. KOP SURAT RESMI PERUSAHAAN -->
        <header class="flex flex-col sm:flex-row print:flex-row sm:items-start print:items-start justify-between gap-4 pb-4 border-b-2 border-slate-800 dark:border-slate-700 print:border-slate-900 relative z-10">
          <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-emerald-600 flex items-center justify-center text-white shadow-xs shrink-0 font-bold text-sm">
              {#if comp.logo_url}
                <img src={comp.logo_url} alt="Logo" class="w-8 h-8 object-contain" />
              {:else}
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              {/if}
            </div>
            <div>
              <h2 class="text-base font-black tracking-tight text-slate-950 dark:text-white uppercase">
                {comp.legal_name || comp.name}
              </h2>
              <p class="text-[9.5px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">
                {comp.tagline || 'Software Engineering & Enterprise Web Solutions'}
              </p>
              <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-tight">
                {comp.operational_address || comp.address} • Telp/WA: {comp.hotline_wa || comp.phone}
              </p>
            </div>
          </div>

          <div class="sm:text-right print:text-right space-y-0.5 shrink-0">
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight font-serif">
              KUITANSI
            </h1>
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
              Official Payment Receipt
            </p>
            <div class="font-mono text-xs pt-1">
              <span class="text-slate-400">No:</span>
              <span class="font-bold text-slate-900 dark:text-white px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700 ml-1">
                {r.receipt_number}
              </span>
            </div>
          </div>
        </header>

        <!-- 2. KONTEN UTAMA KUITANSI FORMAL -->
        <section class="space-y-4 relative z-10 text-xs sm:text-sm">
          
          <!-- Baris: Telah Terima Dari -->
          <div class="flex flex-col sm:flex-row print:flex-row sm:items-baseline print:items-baseline gap-2 pb-2.5 border-b border-dashed border-slate-200 dark:border-slate-700 print:border-slate-300">
            <span class="w-48 font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider text-[11px] shrink-0">
              Telah Terima Dari:
            </span>
            <div class="flex-1 font-bold text-slate-900 dark:text-white text-sm sm:text-base">
              {c?.name || 'Klien Valora'}
              {#if c?.contact_person}
                <span class="text-xs font-normal text-slate-500 dark:text-slate-400 block sm:inline sm:ml-2">
                  (a.n. {c.contact_person})
                </span>
              {/if}
            </div>
          </div>

          <!-- Baris: Uang Sejumlah (Terbilang) -->
          <div class="flex flex-col sm:flex-row print:flex-row sm:items-baseline print:items-baseline gap-2 pb-2.5 border-b border-dashed border-slate-200 dark:border-slate-700 print:border-slate-300">
            <span class="w-48 font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider text-[11px] shrink-0">
              Uang Sejumlah:
            </span>
            <div class="flex-1 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-bold text-slate-900 dark:text-white italic">
              "{terbilang(r.amount)}"
            </div>
          </div>

          <!-- Baris: Untuk Pembayaran -->
          <div class="flex flex-col sm:flex-row print:flex-row sm:items-baseline print:items-baseline gap-2 pb-2.5 border-b border-dashed border-slate-200 dark:border-slate-700 print:border-slate-300">
            <span class="w-48 font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider text-[11px] shrink-0">
              Untuk Pembayaran:
            </span>
            <div class="flex-1 space-y-1">
              <p class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">
                {r.reference_no || `Pembayaran Proyek ${p?.app_name || ''}`}
              </p>
              {#if p}
                <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2 flex-wrap">
                  <span>Aplikasi: <b class="text-slate-800 dark:text-slate-200">{p.app_name}</b></span>
                  <span>•</span>
                  <span>No. Invoice: <b class="font-mono text-indigo-600 dark:text-indigo-400">{p.invoice_number}</b></span>
                </div>
              {/if}
            </div>
          </div>

          <!-- Baris: Metode Pembayaran -->
          <div class="flex flex-col sm:flex-row print:flex-row sm:items-baseline print:items-baseline gap-2 pb-2.5 border-b border-dashed border-slate-200 dark:border-slate-700 print:border-slate-300">
            <span class="w-48 font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider text-[11px] shrink-0">
              Metode Pembayaran:
            </span>
            <div class="flex-1 font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 font-mono text-xs font-bold">
                {r.payment_method}
              </span>
              <span class="text-slate-400 font-normal text-xs">Diterima & diverifikasi efektif di rekening koran</span>
            </div>
          </div>

        </section>

        <!-- 3. KOTAK NOMINAL BESAR & REKAPITULASI AKUNTANSI -->
        <section class="grid grid-cols-1 sm:grid-cols-2 print:grid-cols-2 gap-4 items-center pt-2 relative z-10 avoid-break">
          
          <!-- Kotak Nominal Resmi (Classic Receipt Badge) -->
          <div class="p-4 rounded-xl border-2 border-slate-900 dark:border-white bg-slate-50 dark:bg-slate-800/80 shadow-2xs">
            <span class="text-[9.5px] uppercase font-bold tracking-wider text-slate-400 block mb-0.5">
              Jumlah Uang (Nominal):
            </span>
            <div class="text-xl sm:text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400 tracking-tight">
              {formatIDR(r.amount)}
            </div>
            <span class="text-[10px] text-slate-400 italic block mt-0.5">
              *Tanda bukti penerimaan pembayaran sah
            </span>
          </div>

          <!-- Rekapitulasi Sisa Tagihan -->
          {#if p}
            <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 space-y-1 font-mono text-xs">
              <div class="flex justify-between text-slate-500">
                <span class="font-sans">Total Nilai Proyek:</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{formatIDR(p.grand_total)}</span>
              </div>
              <div class="flex justify-between text-emerald-600 font-bold">
                <span class="font-sans">Pembayaran Masuk Ini:</span>
                <span>(-) {formatIDR(r.amount)}</span>
              </div>
              <div class="flex justify-between border-t border-slate-200 dark:border-slate-800 pt-1 text-xs font-extrabold {p.remaining_amount <= 0 ? 'text-emerald-600' : 'text-amber-600 dark:text-amber-400'}">
                <span class="font-sans">Sisa Kewajiban Tagihan:</span>
                <span>{formatIDR(p.remaining_amount)}</span>
              </div>
            </div>
          {/if}
        </section>

        <!-- 4. TEMPAT, TANGGAL & TANDA TANGAN PENERIMA -->
        <footer class="pt-6 border-t-2 border-slate-800 dark:border-slate-700 relative z-10 avoid-break">
          <div class="flex flex-col sm:flex-row print:flex-row items-center justify-between gap-6">
            
            <!-- Klausul Hukum -->
            <div class="text-[10px] text-slate-400 dark:text-slate-500 max-w-sm space-y-1">
              <p class="font-semibold text-slate-600 dark:text-slate-400">Catatan & Ketentuan Kuitansi:</p>
              <p>1. Kuitansi ini sah dan mengikat setelah dana efektif masuk ke rekening {comp.legal_name || comp.name}.</p>
              <p>2. Rincian deliverable dan kapabilitas modul tercantum lengkap pada Faktur Tagihan ({p?.invoice_number || 'Invoice Proyek'}).</p>
              <p>3. Bukti pembayaran ini diterbitkan secara elektronik dan sah tanpa memerlukan meterai tempel fisik sesuai UU ITE.</p>
            </div>

            <!-- Area Tanda Tangan & Cap Stempel Resmi -->
            <div class="text-center sm:text-right print:text-right space-y-12 relative w-64">
              <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Jakarta, {formatDate(r.payment_date)}
              </p>

              <!-- Stempel Digital Lunas -->
              <div class="absolute left-1/2 sm:left-auto sm:right-6 print:left-auto print:right-6 top-3 -translate-x-1/2 sm:translate-x-0 print:translate-x-0 pointer-events-none opacity-85">
                <div class="w-24 h-24 rounded-full border-2 border-dashed border-emerald-600 dark:border-emerald-400 flex flex-col items-center justify-center text-center p-1 rotate-[-10deg] bg-emerald-50/40 dark:bg-emerald-950/20">
                  <span class="text-[7.5px] font-black text-emerald-700 dark:text-emerald-300 uppercase tracking-widest truncate max-w-[70px]">{(comp.agency_name || comp.name || 'VALORA').toUpperCase()}</span>
                  <span class="text-[9px] font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-wider">LUNAS</span>
                  <span class="text-[6.5px] font-bold text-slate-500 font-mono">FINANCE DEPT</span>
                </div>
              </div>

              <div class="space-y-0.5 relative z-10 pt-2">
                <p class="text-xs font-bold text-slate-900 dark:text-white underline">
                  {comp.signatory_name}
                </p>
                <p class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold">
                  {comp.signatory_title}
                </p>
                <p class="text-[9px] text-slate-400 font-mono">
                  {comp.legal_name || comp.name}
                </p>
              </div>
            </div>

          </div>
        </footer>

      </article>
    {/if}

  </div>
</div>
