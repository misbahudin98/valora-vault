<script>
  import { onMount } from 'svelte';
  import { page } from '$app/state';
  import { api } from '$lib/api/client.js';
  import { terbilang } from '$lib/utils/terbilang.js';
  import { exportInvoiceToWord } from '$lib/utils/documentExport.js';
  import { toastState } from '$lib/state/toast.svelte.js';
  import ElementLoader from '$lib/components/ui/ElementLoader.svelte';

  let projectId = $derived(page.params.id);

  let loading = $state(true);
  let error = $state(null);
  let invoiceData = $state(null);

  let agency = $derived(invoiceData?.agency || {
    agency_name: 'Valora Digital Solusindo',
    legal_name: 'PT Valora Digital Solusindo',
    tagline: 'Software Engineering & Enterprise Web Solutions',
    email: 'billing@valora.id',
    hotline_wa: '+62 812-9988-7766',
    operational_address: 'Gedung Cyber One, Lantai 5, Jl. H.R. Rasuna Said Kav. 8, Jakarta Selatan 12950',
    github_url: 'https://github.com/valora-vault',
    website_url: 'www.valora.id',
    logo_url: '',
    tax_id: '01.456.789.0-012.000',
    bank_name: 'Bank Central Asia (BCA)',
    bank_account_no: '827-091-8888',
    bank_account_name: 'PT Valora Digital Solusindo',
    secondary_bank_name: 'Bank Mandiri',
    secondary_bank_account_no: '132-00-998877-6',
    secondary_bank_account_name: 'PT Valora Digital Solusindo',
    signatory_name: 'Muhammad Iqbal Murtadho',
    signatory_title: 'Managing Director & Lead Architect',
    terms_notes: 'Pembayaran tagihan ditransfer penuh ke rekening resmi agensi di atas.',
  });

  onMount(() => {
    if (projectId) {
      loadInvoice();
    }
  });

  async function loadInvoice() {
    loading = true;
    error = null;
    try {
      const res = await api.request(`/api/v1/admin/billing/projects/${projectId}/invoice`);
      if (res) {
        invoiceData = res;
        // Check if print query parameter is present (e.g., ?print=true)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === 'true') {
          setTimeout(() => {
            window.print();
          }, 400);
        }
      }
    } catch (err) {
      error = err?.message || 'Gagal memuat dokumen invoice resmi.';
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

  function getDueDate(dateStr) {
    if (!dateStr) return '-';
    try {
      const d = new Date(dateStr);
      d.setDate(d.getDate() + 14); // 14 hari kalender jatuh tempo
      return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    } catch {
      return '-';
    }
  }

  let exportingWord = $state(false);

  function handlePrint() {
    window.print();
  }

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
      `*FAKTUR INVOICE RESMI — ${agencyTitle}*\n` +
      `No. Invoice: ${p.invoice_number}\n` +
      `Tanggal: ${formatDate(p.created_at)}\n` +
      `Klien: ${p.client?.name || '-'}\n` +
      `Aplikasi: ${p.app_name} (${p.project_type === 'maintenance' ? 'Paket Pemeliharaan' : 'Pengembangan Baru'})\n\n` +
      `Total Nilai Valuasi: ${formatIDR(p.grand_total)}\n` +
      `Pembayaran Masuk: ${formatIDR(p.paid_amount)}\n` +
      `Sisa Piutang: ${formatIDR(p.remaining_amount)}\n` +
      `Status Tagihan: ${p.payment_status.toUpperCase()}\n\n` +
      `Rekening Pembayaran:\n` +
      `${bankText}\n\n` +
      `Konfirmasi / Hotline WA: ${agency.hotline_wa}\n` +
      `Terima kasih atas kerja sama dan kepercayaan Anda.`
    );

    window.open(`https://wa.me/${phone}?text=${text}`, '_blank');
  }
</script>

<svelte:head>
  <title>Invoice {invoiceData?.project?.invoice_number || 'Resmi'} — {agency.legal_name || agency.agency_name}</title>
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
    .print-invoice-page {
      max-width: 100% !important;
      width: 100% !important;
      margin: 0 auto !important;
      padding: 6mm 8mm !important;
      border: 1px solid #cbd5e1 !important;
      border-radius: 8px !important;
      box-shadow: none !important;
      background: #ffffff !important;
      color: #0f172a !important;
    }
    .print-invoice-page header {
      display: flex !important;
      visibility: visible !important;
    }
    .print-invoice-page footer {
      display: block !important;
      visibility: visible !important;
    }
    .print-invoice-page .print\:grid-cols-2 {
      display: grid !important;
      grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
    .print-invoice-page .print\:flex-row {
      display: flex !important;
      flex-direction: row !important;
    }
    .print-invoice-page .print\:items-start {
      align-items: flex-start !important;
    }
    .print-invoice-page .print\:text-right {
      text-align: right !important;
    }
    .print-invoice-page .print\:justify-end {
      justify-content: flex-end !important;
    }
    @page {
      size: A4 portrait;
      margin: 8mm 10mm 10mm 10mm;
    }
    .avoid-break {
      break-inside: avoid !important;
      page-break-inside: avoid !important;
    }
  }
</style>

<div class="min-h-screen bg-slate-100/80 dark:bg-[#070b12] py-4 sm:py-8 px-2 sm:px-4 print:bg-white print:p-0">
  <div class="max-w-4xl mx-auto space-y-4">
    <!-- Top Action Bar (Hanya tampil di layar peramban, disembunyikan saat cetak) -->
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
          Faktur Tagihan Resmi Valora
        </span>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        {#if invoiceData?.project?.client?.phone}
          <button
            type="button"
            onclick={sendWhatsAppInvoice}
            class="min-h-[36px] px-3 py-1.5 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 text-xs font-bold transition inline-flex items-center gap-1.5 cursor-pointer active:scale-95"
            title="Kirim detail invoice via WhatsApp"
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
          title="Unduh faktur invoice dalam format Microsoft Word (.doc)"
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

        <!-- Tombol Cetak / Simpan PDF -->
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
        <ElementLoader size="lg" variant="indigo" text="Menyiapkan Dokumen Faktur Invoice Resmi..." />
        <p class="text-xs text-slate-400 font-mono">Memuat rincian breakdown komponen biaya & audit pembayaran.</p>
      </div>
    {:else if error}
      <div class="p-12 bg-white dark:bg-slate-900 rounded-2xl border border-rose-200 text-center space-y-3">
        <div class="w-12 h-12 mx-auto rounded-full bg-rose-50 text-rose-600 flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <p class="text-sm font-bold text-slate-900 dark:text-white">Gagal Menyiapkan Invoice</p>
        <p class="text-xs text-rose-500 max-w-md mx-auto">{error}</p>
        <button
          type="button"
          onclick={loadInvoice}
          class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold cursor-pointer"
        >
          Coba Muat Ulang
        </button>
      </div>
    {:else if invoiceData}
      {@const project = invoiceData.project}
      {@const items = invoiceData.items || []}
      {@const payments = invoiceData.payments || []}

      <!-- DOKUMEN FAKTUR INVOICE RESMI PT VALORA DIGITAL SOLUSINDO (A4 CANVAS) -->
      <article class="print-invoice-page bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-6 sm:p-12 shadow-sm text-slate-800 dark:text-slate-100 font-sans space-y-8">
        
        <!-- ============================================== -->
        <!-- 1. KOP SURAT RESMI PERUSAHAAN (LETTERHEAD)      -->
        <!-- ============================================== -->
        <header class="flex flex-col sm:flex-row print:flex-row sm:items-start print:items-start justify-between gap-6 pb-6 border-b-2 border-slate-800 dark:border-slate-700 print:border-slate-900">
          <!-- Logo & Profil Perusahaan -->
          <div class="space-y-1.5">
            <div class="flex items-center gap-2.5">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-emerald-600 flex items-center justify-center text-white shadow-xs shrink-0 font-bold text-sm">
                {#if agency.logo_url}
                  <img src={agency.logo_url} alt="Logo" class="w-8 h-8 object-contain" />
                {:else}
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                {/if}
              </div>
              <div>
                <span class="text-lg font-black tracking-tight text-slate-950 dark:text-white uppercase font-sans">
                  {agency.legal_name || agency.agency_name}
                </span>
                <p class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">
                  {agency.tagline || 'Software Engineering & Enterprise Web Solutions'}
                </p>
              </div>
            </div>

            <div class="text-[11px] text-slate-500 dark:text-slate-400 space-y-0.5 pt-1">
              <p>{agency.operational_address}</p>
              <p>Email: <span class="font-mono text-slate-700 dark:text-slate-300 font-semibold">{agency.email}</span> • Telepon/WA: <span class="font-mono text-slate-700 dark:text-slate-300 font-semibold">{agency.hotline_wa}</span></p>
              <p class="text-[10px] font-mono text-slate-400">
                {#if agency.tax_id}NPWP: {agency.tax_id} • {/if}
                Website: {agency.website_url || 'www.valora.id'}
              </p>
            </div>
          </div>

          <!-- Metadata Faktur Tagihan & Stempel Resmi -->
          <div class="sm:text-right print:text-right space-y-2 shrink-0">
            <div class="inline-block">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight font-sans">
                FAKTUR INVOICE
              </h1>
              <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                Official Commercial Invoice
              </p>
            </div>

            <div class="text-xs space-y-1 font-mono">
              <div class="flex sm:justify-end print:justify-end gap-2">
                <span class="text-slate-400">No. Invoice:</span>
                <span class="font-bold text-slate-900 dark:text-white">{project.invoice_number}</span>
              </div>
              <div class="flex sm:justify-end print:justify-end gap-2">
                <span class="text-slate-400">Tanggal Terbit:</span>
                <span class="font-semibold text-slate-700 dark:text-slate-300">{formatDate(project.created_at)}</span>
              </div>
              <div class="flex sm:justify-end print:justify-end gap-2">
                <span class="text-slate-400">Jatuh Tempo:</span>
                <span class="font-semibold text-rose-600 dark:text-rose-400">{getDueDate(project.created_at)}</span>
              </div>
            </div>

            <!-- Stempel Status Resmi Cap Digital -->
            <div class="pt-1 flex sm:justify-end print:justify-end">
              {#if project.payment_status === 'Lunas'}
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg border-2 border-emerald-600 dark:border-emerald-500 bg-emerald-50/80 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 text-xs font-black uppercase tracking-wider shadow-2xs rotate-[-2deg]">
                  <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                  </svg>
                  <span>LUNAS / PAID</span>
                </div>
              {:else if project.payment_status === 'DP / Termin'}
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg border-2 border-indigo-600 dark:border-indigo-500 bg-indigo-50/80 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 text-xs font-black uppercase tracking-wider shadow-2xs rotate-[-2deg]">
                  <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>DP / TERMIN DITERIMA</span>
                </div>
              {:else}
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg border-2 border-rose-600 dark:border-rose-500 bg-rose-50/80 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 text-xs font-black uppercase tracking-wider shadow-2xs rotate-[-2deg]">
                  <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>MENUNGGU PEMBAYARAN</span>
                </div>
              {/if}
            </div>
          </div>
        </header>

        <!-- ============================================== -->
        <!-- 2. DETAIL KLIEN & RUANG LINGKUP SISTEM         -->
        <!-- ============================================== -->
        <section class="grid grid-cols-1 sm:grid-cols-2 print:grid-cols-2 gap-4 avoid-break">
          <!-- Billed To (Pihak Klien) -->
          <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">
              Ditujukan Kepada (Billed To):
            </span>
            <p class="text-sm font-black text-slate-900 dark:text-white">
              {project.client?.name || project.project_name}
            </p>
            <p class="text-xs text-slate-700 dark:text-slate-300 font-medium">
              PIC: <span class="font-bold">{project.client?.contact_person || 'Pimpinan / Penanggung Jawab'}</span>
            </p>
            <p class="text-xs font-mono text-slate-600 dark:text-slate-400">
              Telepon/WA: {project.client?.phone || '-'}
            </p>
            {#if project.client?.email}
              <p class="text-xs font-mono text-slate-600 dark:text-slate-400">
                Email: {project.client?.email}
              </p>
            {/if}
            {#if project.client?.address}
              <p class="text-[11px] text-slate-500 dark:text-slate-400 pt-0.5">
                Alamat: {project.client?.address}
              </p>
            {/if}
          </div>

          <!-- Project & Scope Meta -->
          <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">
              Rincian Penugasan Rekayasa Proyek:
            </span>
            <div class="flex items-center gap-1.5 pt-0.5">
              {#if project.project_type === 'maintenance'}
                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                  Maintenance Sistem
                </span>
              {:else}
                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                  Pengembangan Aplikasi Baru
                </span>
              {/if}
              <span class="text-xs font-bold text-slate-900 dark:text-white">{project.app_name}</span>
            </div>
            <p class="text-xs text-slate-600 dark:text-slate-400">
              Sektor Industri: <span class="font-semibold text-slate-800 dark:text-slate-200">{project.client?.industry || 'Teknologi & Operasional Bisnis'}</span>
            </p>
            <p class="text-xs text-slate-600 dark:text-slate-400">
              Lead Architect / PIC: <span class="font-semibold text-slate-800 dark:text-slate-200">{project.lead_pic || 'Tim Rekayasa Valora'}</span>
            </p>
            <p class="text-[11px] text-slate-500 italic pt-1">
              Catatan: Seluruh modul rekayasa diverifikasi bebas dari bug, lulus uji otomatis PHPUnit & SvelteKit SSG, dan berstandar keamanan OWASP ASVS.
            </p>
          </div>
        </section>

        <!-- ============================================== -->
        <!-- 3. TABEL RINCIAN BIAYA & KOMPONEN PEKERJAAN    -->
        <!-- ============================================== -->
        <section class="space-y-2 avoid-break">
          <div class="flex items-center justify-between">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">
              Daftar Komponen & Modul Layanan Rekayasa
            </h2>
            <span class="text-[10px] font-mono text-slate-400">
              Total {items.length} Item Komponen Terpasang
            </span>
          </div>

          <div class="w-full border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
            <table class="w-full text-left border-collapse text-xs">
              <thead class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800">
                <tr>
                  <th class="py-2.5 px-3 text-center w-10">No</th>
                  <th class="py-2.5 px-3">Rincian Layanan / Fitur Modul</th>
                  <th class="py-2.5 px-3 w-40">Klasifikasi Kategori</th>
                  <th class="py-2.5 px-3 text-right w-32">Tarif Setting</th>
                  <th class="py-2.5 px-3 text-right w-36">Tarif Penyesuaian Client</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70 font-sans">
                {#each items as item, idx}
                  <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                    <td class="py-2 px-3 text-center text-slate-400 font-mono text-[11px]">{idx + 1}</td>
                    <td class="py-2 px-3">
                      <span class="font-bold text-slate-800 dark:text-slate-200 block text-xs">{item.description}</span>
                      {#if item.capabilities && item.capabilities.length > 0}
                        <div class="flex flex-wrap gap-1 pt-1">
                          {#each item.capabilities as cap}
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9.5px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/90 dark:border-slate-700 font-mono print:border-slate-300 print:text-slate-800 print:bg-slate-50">
                              {cap}
                            </span>
                          {/each}
                        </div>
                      {/if}
                      {#if item.note}
                        <span class="text-[10px] text-indigo-600 dark:text-indigo-400 block font-medium pt-0.5">Catatan Khusus: {item.note}</span>
                      {/if}
                      {#if item.pic}
                        <span class="text-[9px] text-slate-400 block font-mono pt-0.5">Teknisi: {item.pic}</span>
                      {/if}
                    </td>
                    <td class="py-2 px-3 text-slate-600 dark:text-slate-400 text-[11px]">
                      {item.category}
                    </td>
                    <td class="py-2 px-3 text-right font-mono text-slate-700 dark:text-slate-300 text-xs">
                      {formatIDR(item.setting_price ?? item.unit_price)}
                    </td>
                    <td class="py-2 px-3 text-right font-mono font-bold text-slate-900 dark:text-white text-xs">
                      {formatIDR(item.adjusted_price ?? item.subtotal)}
                    </td>
                  </tr>
                {/each}
              </tbody>
            </table>
          </div>
        </section>

        <!-- ============================================== -->
        <!-- 4. RINGKASAN FINANSIAL, TERBILANG & PIUTANG    -->
        <!-- ============================================== -->
        <section class="grid grid-cols-1 sm:grid-cols-2 print:grid-cols-2 gap-6 pt-2 avoid-break">
          <!-- Terbilang & Ketentuan Pembayaran -->
          <div class="space-y-3">
            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-800 space-y-1">
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">
                Terbilang (Total Nilai Valuasi):
              </span>
              <p class="text-xs font-bold text-slate-900 dark:text-white italic">
                "{terbilang(project.grand_total)}"
              </p>
            </div>

            <!-- Rekening Bank Resmi Agency -->
            <div class="p-3.5 rounded-xl border border-indigo-200 dark:border-indigo-900/60 bg-indigo-50/40 dark:bg-indigo-950/20 space-y-1.5 text-xs">
              <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-300 block">
                Rekening Resmi Pembayaran {agency.legal_name || agency.agency_name}:
              </span>
              <div class="space-y-1 font-mono text-[11px]">
                <div class="flex items-center justify-between">
                  <span class="text-slate-600 dark:text-slate-400">{agency.bank_name}:</span>
                  <span class="font-bold text-slate-900 dark:text-white">{agency.bank_account_no}</span>
                </div>
                {#if agency.secondary_bank_name && agency.secondary_bank_account_no}
                  <div class="flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-400">{agency.secondary_bank_name}:</span>
                    <span class="font-bold text-slate-900 dark:text-white">{agency.secondary_bank_account_no}</span>
                  </div>
                {/if}
                <div class="flex items-center justify-between border-t border-indigo-200 dark:border-indigo-900 pt-1">
                  <span class="text-slate-600 dark:text-slate-400">Atas Nama:</span>
                  <span class="font-bold text-slate-900 dark:text-white">{agency.bank_account_name}</span>
                </div>
              </div>
              <p class="text-[10px] text-slate-500 pt-0.5">
                *Mohon mencantumkan nomor invoice <b class="font-mono text-indigo-600 dark:text-indigo-400">{project.invoice_number}</b> pada keterangan transfer.
              </p>
            </div>
          </div>

          <!-- Total Valuasi, Realisasi Bayar & Sisa Piutang -->
          <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 space-y-2.5 font-mono text-xs">
            <div class="flex items-center justify-between text-slate-600 dark:text-slate-300 pb-1.5 border-b border-slate-200 dark:border-slate-700">
              <span class="font-sans font-semibold">Total Tarif Awal (Tarif Setting)</span>
              <span class="font-bold">{formatIDR(project.total_setting_price || project.grand_total)}</span>
            </div>
            <div class="flex items-center justify-between text-slate-800 dark:text-slate-200 pb-1.5 border-b border-slate-200 dark:border-slate-700">
              <span class="font-sans font-semibold">Total Tarif Penyesuaian Client</span>
              <span class="font-bold text-indigo-600 dark:text-indigo-400">{formatIDR(project.total_adjusted_price || project.grand_total)}</span>
            </div>
            {#if project.adjustment_diff !== undefined && project.adjustment_diff !== 0}
              <div class="flex items-center justify-between text-[11px] {project.adjustment_diff < 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'} pb-1.5 border-b border-slate-200 dark:border-slate-700">
                <span class="font-sans">
                  {project.adjustment_diff < 0 ? 'Potongan / Diskon Negosiasi' : 'Penyesuaian Biaya Tambahan'}
                </span>
                <span>{project.adjustment_diff < 0 ? '(-)' : '(+)'} {formatIDR(Math.abs(project.adjustment_diff))}</span>
              </div>
            {/if}
            <div class="flex items-center justify-between text-slate-900 dark:text-white text-sm pb-1.5 border-b border-slate-200 dark:border-slate-700">
              <span class="font-sans font-black">TOTAL TAGIHAN KLIEN</span>
              <span class="font-black">{formatIDR(project.grand_total)}</span>
            </div>
            <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400 font-bold pb-1.5 border-b border-slate-200 dark:border-slate-700">
              <span class="font-sans">Total Pembayaran Masuk (Paid)</span>
              <span>(-) {formatIDR(project.paid_amount)}</span>
            </div>
            <div class="flex items-center justify-between pt-1 text-sm sm:text-base {project.remaining_amount <= 0 ? 'text-emerald-600' : 'text-rose-600 dark:text-rose-400'} font-black">
              <span class="font-sans tracking-tight">SISA KEWAJIBAN TAGIHAN</span>
              <span>{formatIDR(project.remaining_amount)}</span>
            </div>
          </div>
        </section>

        <!-- ============================================== -->
        <!-- 5. RIWAYAT PEMBAYARAN MASUK (TRANSAKSI CICILAN) -->
        <!-- ============================================== -->
        {#if payments.length > 0}
          <section class="space-y-2 avoid-break">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
              Catatan Rekapitulasi Pembayaran Masuk (Audit Trail)
            </h3>
            <div class="w-full border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
              <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-[10px] uppercase font-bold tracking-wider">
                  <tr>
                    <th class="py-2 px-3 text-center w-10">No</th>
                    <th class="py-2 px-3 w-32">Tanggal Bayar</th>
                    <th class="py-2 px-3 w-36">Metode</th>
                    <th class="py-2 px-3">Nomor Referensi / Berita</th>
                    <th class="py-2 px-3 text-right w-32">Nominal Masuk</th>
                    <th class="py-2 px-3 text-right w-24 print-hide">Kuitansi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70 font-mono text-[11px]">
                  {#each payments as pay, idx}
                    <tr>
                      <td class="py-1.5 px-3 text-center text-slate-400">{idx + 1}</td>
                      <td class="py-1.5 px-3 text-slate-700 dark:text-slate-300">{formatDate(pay.payment_date)}</td>
                      <td class="py-1.5 px-3 text-slate-700 dark:text-slate-300">{pay.payment_method}</td>
                      <td class="py-1.5 px-3 text-slate-500 font-sans">{pay.reference_no || '-'}</td>
                      <td class="py-1.5 px-3 text-right font-bold text-emerald-600 dark:text-emerald-400">{formatIDR(pay.amount)}</td>
                      <td class="py-1.5 px-3 text-right print-hide">
                        <a
                          href={`/admin/tagihan/kuitansi/${pay.id}`}
                          target="_blank"
                          rel="noopener noreferrer"
                          class="px-2 py-0.5 rounded text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 font-sans inline-block"
                        >
                          Kuitansi ↗
                        </a>
                      </td>
                    </tr>
                  {/each}
                </tbody>
              </table>
            </div>
          </section>
        {/if}

        <!-- ============================================== -->
        <!-- 6. KLAUSUL GARANSI & OTORISASI TANDA TANGAN    -->
        <!-- ============================================== -->
        <footer class="pt-6 border-t border-slate-200 dark:border-slate-800 space-y-6 avoid-break">
          <!-- Syarat dan Ketentuan -->
          <div class="text-[10px] text-slate-400 dark:text-slate-500 space-y-1">
            <p class="font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Syarat & Ketentuan Layanan:</p>
            <ol class="list-decimal pl-4 space-y-0.5">
              <li>{agency.terms_notes || `Pembayaran dinyatakan sah setelah dana efektif masuk ke rekening koran resmi a.n. ${agency.legal_name || agency.agency_name}.`}</li>
              <li>Masa garansi pemeliharaan teknis & perbaikan bug aktif selama 30 hari kalender sejak tanggal serah terima aplikasi.</li>
              <li>Hak cipta dan seluruh berkas kode sumber (*source code*) diserahkan penuh kepada klien setelah penyelesaian pelunasan 100%.</li>
            </ol>
          </div>

          <!-- Tanda Tangan & Cap Stempel Resmi -->
          <div class="grid grid-cols-2 print:grid-cols-2 gap-8 pt-4">
            <!-- Pihak Klien -->
            <div class="text-center space-y-14">
              <span class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider block">
                Diterima & Disetujui Oleh:
              </span>
              <div class="space-y-0.5">
                <p class="text-xs font-bold text-slate-900 dark:text-white underline">
                  {project.client?.contact_person || project.client?.name || 'Pihak Klien'}
                </p>
                <p class="text-[10px] text-slate-400">
                  {project.client?.name || 'Penanggung Jawab Proyek'}
                </p>
              </div>
            </div>

            <!-- Pihak Agency -->
            <div class="text-center space-y-14 relative">
              <span class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider block">
                Hormat Kami, {agency.legal_name || agency.agency_name}:
              </span>
              
              <!-- Stempel Digital Agency -->
              <div class="absolute left-1/2 top-4 -translate-x-1/2 pointer-events-none opacity-80">
                <div class="w-24 h-24 rounded-full border-2 border-dashed border-indigo-600/70 dark:border-indigo-400/70 flex flex-col items-center justify-center text-center p-1 rotate-[-12deg]">
                  <span class="text-[7.5px] font-black text-indigo-700 dark:text-indigo-300 uppercase tracking-widest truncate max-w-[70px]">{(agency.agency_name || 'VALORA').toUpperCase()}</span>
                  <span class="text-[7px] font-bold text-emerald-600 dark:text-emerald-400 font-mono">OFFICIAL SEAL</span>
                  <span class="text-[6px] text-indigo-500 font-mono">FINANCE & CONTRACT</span>
                </div>
              </div>

              <div class="space-y-0.5 relative z-10">
                <p class="text-xs font-bold text-slate-900 dark:text-white underline">
                  {agency.signatory_name}
                </p>
                <p class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold">
                  {agency.signatory_title}
                </p>
              </div>
            </div>
          </div>
        </footer>

      </article>
    {/if}
  </div>
</div>
