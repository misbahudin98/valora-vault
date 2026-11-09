import { terbilang } from './terbilang.js';

/**
 * Precomputed 256x256 high-resolution Valora Brand Emblem Base64 PNG.
 * Gradient squircle (Indigo to Emerald) with crisp white lightning bolt glyph.
 * Physically embedded inside the Word document so MS Word renders it offline without external blocks.
 */
export const VALORA_DEFAULT_LOGO_BASE64 = 'data:image/png;base64,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';

/**
 * Resolves an agency logo to a self-contained base64 data URI.
 * Works seamlessly across custom data URLs, web URLs, and the default Valora brand emblem.
 */
export async function resolveLogoBase64(logoUrl) {
  if (logoUrl && typeof logoUrl === 'string' && logoUrl.startsWith('data:image/')) {
    return logoUrl;
  }
  if (logoUrl && typeof logoUrl === 'string' && (logoUrl.startsWith('http') || logoUrl.startsWith('/') || logoUrl.startsWith('./'))) {
    try {
      const img = new Image();
      img.crossOrigin = 'anonymous';
      const loaded = await new Promise((resolve) => {
        img.onload = () => resolve(true);
        img.onerror = () => resolve(false);
        img.src = logoUrl;
        setTimeout(() => resolve(false), 2000);
      });
      if (loaded && img.naturalWidth && img.naturalHeight) {
        const canvas = document.createElement('canvas');
        canvas.width = img.naturalWidth;
        canvas.height = img.naturalHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0);
        return canvas.toDataURL('image/png');
      }
    } catch {
      // Fallback below
    }
  }
  return VALORA_DEFAULT_LOGO_BASE64;
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
    d.setDate(d.getDate() + 14);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
  } catch {
    return '-';
  }
}

/**
 * Client-Side Microsoft Word (.doc) Exporter for Invoice
 * Generates an Office-compliant Word HTML document 100% identical to the on-screen preview.
 * Embeds the agency brand logo directly as a native image picture.
 */
export async function exportInvoiceToWord(invoiceData, agency) {
  if (!invoiceData || !invoiceData.project) return false;

  const p = invoiceData.project;
  const items = invoiceData.items || [];
  const payments = invoiceData.payments || [];
  const c = p.client || {};

  const agencyName = (agency.legal_name || agency.agency_name || 'PT VALORA DIGITAL SOLUSINDO').toUpperCase();
  const invoiceNo = p.invoice_number || 'INV-RESMI';
  const issueDate = formatDate(p.created_at);
  const dueDate = getDueDate(p.created_at);

  const logoBase64 = await resolveLogoBase64(agency?.logo_url);

  let statusBadgeStyle = 'background-color: #ecfdf5; color: #047857; border: 2pt solid #059669;';
  let statusText = '✓ LUNAS / PAID';
  if (p.payment_status === 'DP / Termin') {
    statusBadgeStyle = 'background-color: #eef2ff; color: #3730a3; border: 2pt solid #4f46e5;';
    statusText = '⏱ DP / TERMIN DITERIMA';
  } else if (p.payment_status !== 'Lunas') {
    statusBadgeStyle = 'background-color: #fff1f2; color: #be123c; border: 2pt solid #e11d48;';
    statusText = '⚠ MENUNGGU PEMBAYARAN';
  }

  // Build items rows
  const itemRowsHtml = items.map((item, idx) => {
    const caps = (item.capabilities || []).map(cap => 
      `<span style="display:inline-block; margin-right:3pt; margin-top:2pt; margin-bottom:1pt; padding:1.5pt 4.5pt; font-size:7.5pt; font-family:Consolas,monospace; font-weight:600; background-color:#f1f5f9; border:1px solid #cbd5e1; border-radius:3pt; color:#334155;">${cap}</span>`
    ).join(' ');

    const note = item.note ? `<div style="font-size:7.5pt; color:#4338ca; margin-top:2pt; font-weight:600;">Catatan Khusus: ${item.note}</div>` : '';
    const pic = item.pic ? `<div style="font-size:7pt; color:#64748b; font-family:Consolas,monospace; margin-top:1pt;">Teknisi: ${item.pic}</div>` : '';

    return `
      <tr>
        <td style="text-align:center; font-family:Consolas,monospace; font-size:8.5pt; color:#64748b; vertical-align:top; padding:5pt 6pt; border:1px solid #e2e8f0;">${idx + 1}</td>
        <td style="vertical-align:top; padding:5pt 6pt; border:1px solid #e2e8f0;">
          <div style="font-weight:bold; color:#0f172a; font-size:9.5pt;">${item.description || '-'}</div>
          ${caps ? `<div style="margin-top:2pt;">${caps}</div>` : ''}
          ${note}
          ${pic}
        </td>
        <td style="font-size:8.5pt; color:#475569; vertical-align:top; padding:5pt 6pt; border:1px solid #e2e8f0;">${item.category || '-'}</td>
        <td style="text-align:right; font-family:Consolas,monospace; font-size:9pt; color:#334155; vertical-align:top; padding:5pt 6pt; border:1px solid #e2e8f0;">${formatIDR(item.setting_price ?? item.unit_price)}</td>
        <td style="text-align:right; font-family:Consolas,monospace; font-weight:bold; font-size:9pt; color:#0f172a; vertical-align:top; padding:5pt 6pt; border:1px solid #e2e8f0;">${formatIDR(item.adjusted_price ?? item.subtotal)}</td>
      </tr>
    `;
  }).join('');

  // Build payments rows
  let paymentsHtml = '';
  if (payments.length > 0) {
    const paymentRows = payments.map((pay, idx) => `
      <tr>
        <td style="text-align:center; font-family:Consolas,monospace; font-size:8pt; padding:4pt 6pt; border:1px solid #cbd5e1;">${idx + 1}</td>
        <td style="font-family:Consolas,monospace; font-size:8.5pt; padding:4pt 6pt; border:1px solid #cbd5e1; color:#334155;">${formatDate(pay.payment_date)}</td>
        <td style="font-size:8.5pt; padding:4pt 6pt; border:1px solid #cbd5e1; color:#334155;">${pay.payment_method || '-'}</td>
        <td style="font-size:8.5pt; color:#475569; padding:4pt 6pt; border:1px solid #cbd5e1;">${pay.reference_no || '-'}</td>
        <td style="text-align:right; font-family:Consolas,monospace; font-weight:bold; font-size:8.5pt; color:#047857; padding:4pt 6pt; border:1px solid #cbd5e1;">${formatIDR(pay.amount)}</td>
      </tr>
    `).join('');

    paymentsHtml = `
      <div style="margin-top:14pt;">
        <div style="font-size:8pt; font-weight:bold; text-transform:uppercase; color:#0f172a; margin-bottom:4pt; letter-spacing:0.5pt;">
          Catatan Rekapitulasi Pembayaran Masuk (Audit Trail)
        </div>
        <table style="width:100%; border-collapse:collapse; margin-bottom:8pt;">
          <thead>
            <tr style="background-color:#f1f5f9;">
              <th style="padding:4pt 6pt; border:1px solid #cbd5e1; text-align:center; width:25pt; font-size:8pt; color:#475569; text-transform:uppercase;">No</th>
              <th style="padding:4pt 6pt; border:1px solid #cbd5e1; text-align:left; width:85pt; font-size:8pt; color:#475569; text-transform:uppercase;">Tanggal Bayar</th>
              <th style="padding:4pt 6pt; border:1px solid #cbd5e1; text-align:left; width:95pt; font-size:8pt; color:#475569; text-transform:uppercase;">Metode</th>
              <th style="padding:4pt 6pt; border:1px solid #cbd5e1; text-align:left; font-size:8pt; color:#475569; text-transform:uppercase;">Nomor Referensi / Berita</th>
              <th style="padding:4pt 6pt; border:1px solid #cbd5e1; text-align:right; width:90pt; font-size:8pt; color:#475569; text-transform:uppercase;">Nominal Masuk</th>
            </tr>
          </thead>
          <tbody>
            ${paymentRows}
          </tbody>
        </table>
      </div>
    `;
  }

  // Bank transfer info
  let bankInfoHtml = `
    <div style="font-family:Consolas,monospace; font-size:8.5pt; margin-bottom:2pt;"><span style="color:#475569;">${agency.bank_name || 'Bank BCA'}:</span> <b style="color:#0f172a;">${agency.bank_account_no || '-'}</b></div>
  `;
  if (agency.secondary_bank_name && agency.secondary_bank_account_no) {
    bankInfoHtml += `
      <div style="font-family:Consolas,monospace; font-size:8.5pt; margin-bottom:2pt;"><span style="color:#475569;">${agency.secondary_bank_name}:</span> <b style="color:#0f172a;">${agency.secondary_bank_account_no}</b></div>
    `;
  }
  bankInfoHtml += `
    <div style="margin-top:3pt; border-top:1px dashed #c7d2fe; padding-top:2pt; font-family:Consolas,monospace; font-size:8.5pt;"><span style="color:#475569;">Atas Nama:</span> <b style="color:#0f172a;">${agency.bank_account_name || agencyName}</b></div>
  `;

  const wordHtml = `
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
  <meta charset="utf-8">
  <title>Invoice ${invoiceNo} — ${agencyName}</title>
  <!--[if gte mso 9]>
  <xml>
    <w:WordDocument>
      <w:View>Print</w:View>
      <w:Zoom>100</w:Zoom>
      <w:DoNotOptimizeForBrowser/>
    </w:WordDocument>
  </xml>
  <![endif]-->
  <style>
    @page Section1 {
      size: 595.3pt 841.9pt; /* A4 */
      margin: 28.3pt 35.4pt 35.4pt 35.4pt;
      mso-header-margin: 28.3pt;
      mso-footer-margin: 28.3pt;
      mso-paper-source: 0;
    }
    div.Section1 { 
      page: Section1; 
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; 
      font-size: 9.5pt; 
      color: #0f172a; 
      line-height: 1.35; 
      background-color: #f1f5f9;
      padding: 0;
    }
    table { border-collapse: collapse; width: 100%; margin-bottom: 8pt; }
    th { background-color: #f1f5f9; color: #0f172a; font-weight: bold; font-size: 8.5pt; text-transform: uppercase; padding: 5pt 7pt; border: 1px solid #cbd5e1; }
    td { padding: 5pt 7pt; border: 1px solid #e2e8f0; font-size: 9pt; vertical-align: top; }
  </style>
</head>
<body>
  <div class="Section1">
    
    <!-- OUTER CONTAINER BORDER INVOICE FORMAL (WYSIWYG CANVAS PREVIEW) -->
    <div style="border:1px solid #cbd5e1; border-radius:8pt; padding:18pt 22pt; background-color:#ffffff; margin:0 auto; max-width:100%;">

    <!-- 1. KOP SURAT RESMI PERUSAHAAN (LETTERHEAD) -->
    <table style="width:100%; border:none; margin-bottom:8pt;">
      <tr>
        <td style="border:none; width:62%; padding:0; vertical-align:top;">
          <table style="border:none; width:100%; margin-bottom:4pt;">
            <tr>
              <td style="border:none; width:44pt; vertical-align:middle; padding:0 8pt 0 0;">
                <img src="${logoBase64}" width="44" height="44" style="width:44pt; height:44pt; object-fit:contain; border-radius:8pt; vertical-align:middle; display:block;" alt="Logo Valora" />
              </td>
              <td style="border:none; vertical-align:middle; padding:0;">
                <div style="font-size:13.5pt; font-weight:900; color:#0f172a; text-transform:uppercase; letter-spacing:0.5pt; font-family:'Segoe UI',sans-serif;">
                  ${agencyName}
                </div>
                <div style="font-size:8.5pt; font-weight:bold; color:#4338ca; text-transform:uppercase; letter-spacing:0.8pt; font-family:'Segoe UI',sans-serif;">
                  ${agency.tagline || 'Software Engineering & Enterprise Web Solutions'}
                </div>
              </td>
            </tr>
          </table>
          <div style="font-size:8.5pt; color:#475569; margin-top:2pt; line-height:1.35;">
            ${agency.operational_address || agency.address || '-'}<br>
            Email: <b style="color:#0f172a;">${agency.email || '-'}</b> • Telepon/WA: <b style="color:#0f172a;">${agency.hotline_wa || agency.phone || '-'}</b><br>
            ${agency.tax_id ? `NPWP: <b style="color:#0f172a;">${agency.tax_id}</b> • ` : ''}Website: <b style="color:#0f172a;">${agency.website_url || 'www.valora.id'}</b>
          </div>
        </td>
        <td style="border:none; width:38%; padding:0; text-align:right; vertical-align:top;">
          <div style="font-size:16pt; font-weight:900; color:#0f172a; text-transform:uppercase; font-family:'Segoe UI',sans-serif; letter-spacing:-0.5pt;">
            FAKTUR INVOICE
          </div>
          <div style="font-size:8pt; color:#64748b; font-weight:bold; text-transform:uppercase; margin-bottom:5pt; letter-spacing:0.5pt;">
            Official Commercial Invoice
          </div>
          <div style="font-size:8.5pt; font-family:Consolas,monospace; line-height:1.45;">
            <div><span style="color:#64748b;">No. Invoice:</span> <b style="color:#0f172a;">${invoiceNo}</b></div>
            <div><span style="color:#64748b;">Tanggal Terbit:</span> <b style="color:#0f172a;">${issueDate}</b></div>
            <div><span style="color:#64748b;">Jatuh Tempo:</span> <b style="color:#e11d48;">${dueDate}</b></div>
          </div>
          <div style="margin-top:6pt;">
            <span style="display:inline-block; padding:3pt 10pt; font-size:8pt; font-weight:bold; text-transform:uppercase; border-radius:4pt; ${statusBadgeStyle}">
              ${statusText}
            </span>
          </div>
        </td>
      </tr>
    </table>

    <div style="border-bottom:2pt solid #0f172a; margin-bottom:10pt;"></div>

    <!-- 2. DETAIL KLIEN & RUANG LINGKUP SISTEM (2 CARDS) -->
    <table style="width:100%; border:none; margin-bottom:10pt;">
      <tr>
        <td style="border:1px solid #cbd5e1; background-color:#f8fafc; border-radius:6pt; width:49%; padding:8pt; vertical-align:top;">
          <div style="font-size:8pt; font-weight:bold; color:#64748b; text-transform:uppercase; margin-bottom:3pt; letter-spacing:0.5pt;">
            Ditujukan Kepada (Billed To):
          </div>
          <div style="font-size:11pt; font-weight:bold; color:#0f172a;">
            ${c.name || p.project_name || 'Klien Valora'}
          </div>
          <div style="font-size:8.5pt; color:#334155; margin-top:2pt;">
            PIC: <b>${c.contact_person || 'Pimpinan / Penanggung Jawab'}</b>
          </div>
          <div style="font-size:8.5pt; color:#334155;">
            Telepon/WA: <b>${c.phone || '-'}</b>
          </div>
          ${c.email ? `<div style="font-size:8.5pt; color:#334155;">Email: <b>${c.email}</b></div>` : ''}
          ${c.address ? `<div style="font-size:8pt; color:#64748b; margin-top:2pt;">Alamat: ${c.address}</div>` : ''}
        </td>
        <td style="border:none; width:2%;"></td>
        <td style="border:1px solid #cbd5e1; background-color:#f8fafc; border-radius:6pt; width:49%; padding:8pt; vertical-align:top;">
          <div style="font-size:8pt; font-weight:bold; color:#64748b; text-transform:uppercase; margin-bottom:3pt; letter-spacing:0.5pt;">
            Rincian Penugasan Rekayasa Proyek:
          </div>
          <div style="margin-bottom:3pt;">
            <span style="display:inline-block; padding:1.5pt 5pt; font-size:7.5pt; font-weight:bold; border-radius:3pt; ${p.project_type === 'maintenance' ? 'background-color:#fef3c7; color:#b45309; border:1px solid #fde68a;' : 'background-color:#eef2ff; color:#4338ca; border:1px solid #c7d2fe;'}">
              ${p.project_type === 'maintenance' ? 'Maintenance Sistem' : 'Pengembangan Aplikasi Baru'}
            </span>
            <b style="font-size:10pt; color:#0f172a; margin-left:4pt;">${p.app_name || '-'}</b>
          </div>
          <div style="font-size:8.5pt; color:#334155;">
            Sektor Industri: <b>${c.industry || 'Teknologi & Operasional Bisnis'}</b>
          </div>
          <div style="font-size:8.5pt; color:#334155;">
            Lead Architect / PIC: <b>${p.lead_pic || 'Tim Rekayasa Valora'}</b>
          </div>
          <div style="font-size:7.5pt; color:#64748b; font-style:italic; margin-top:4pt; line-height:1.3;">
            Catatan: Seluruh modul rekayasa diverifikasi bebas dari bug, lulus uji otomatis PHPUnit & SvelteKit SSG, dan berstandar keamanan OWASP ASVS.
          </div>
        </td>
      </tr>
    </table>

    <!-- 3. TABEL RINCIAN BIAYA & KOMPONEN PEKERJAAN -->
    <table style="width:100%; border:none; margin-bottom:3pt;">
      <tr>
        <td style="border:none; padding:0; vertical-align:middle;">
          <div style="font-size:8.5pt; font-weight:bold; text-transform:uppercase; color:#0f172a; letter-spacing:0.5pt;">
            Daftar Komponen & Modul Layanan Rekayasa
          </div>
        </td>
        <td style="border:none; padding:0; text-align:right; vertical-align:middle;">
          <div style="font-size:7.5pt; font-family:Consolas,monospace; color:#64748b;">
            Total ${items.length} Item Komponen Terpasang
          </div>
        </td>
      </tr>
    </table>

    <table style="width:100%; border-collapse:collapse; margin-bottom:10pt;">
      <thead>
        <tr>
          <th style="width:25pt; text-align:center;">No</th>
          <th>Rincian Layanan / Fitur Modul</th>
          <th style="width:105pt; text-align:left;">Klasifikasi Kategori</th>
          <th style="width:85pt; text-align:right;">Tarif Setting</th>
          <th style="width:95pt; text-align:right;">Tarif Penyesuaian Client</th>
        </tr>
      </thead>
      <tbody>
        ${itemRowsHtml}
      </tbody>
    </table>

    <!-- 4. RINGKASAN FINANSIAL, TERBILANG & REKENING PEMBAYARAN -->
    <table style="width:100%; border:none; margin-bottom:12pt;">
      <tr>
        <!-- Left: Terbilang & Rekening Bank -->
        <td style="border:none; width:51%; padding:0; vertical-align:top;">
          <div style="border:1px solid #cbd5e1; background-color:#f8fafc; border-radius:6pt; padding:7pt; margin-bottom:6pt;">
            <div style="font-size:7.5pt; font-weight:bold; color:#64748b; text-transform:uppercase; margin-bottom:2pt; letter-spacing:0.5pt;">
              Terbilang (Total Nilai Valuasi):
            </div>
            <div style="font-size:8.5pt; font-weight:bold; color:#0f172a; font-style:italic;">
              "${terbilang(p.grand_total)}"
            </div>
          </div>

          <div style="border:1px solid #c7d2fe; background-color:#eef2ff; border-radius:6pt; padding:7pt;">
            <div style="font-weight:bold; color:#3730a3; text-transform:uppercase; font-size:7.5pt; margin-bottom:4pt; letter-spacing:0.5pt;">
              Rekening Resmi Pembayaran ${agencyName}:
            </div>
            ${bankInfoHtml}
            <div style="font-size:7.5pt; color:#475569; margin-top:4pt;">
              *Mohon mencantumkan nomor invoice <b style="color:#4338ca; font-family:Consolas,monospace;">${invoiceNo}</b> pada keterangan transfer.
            </div>
          </div>
        </td>

        <td style="border:none; width:2%;"></td>

        <!-- Right: Financial Breakdown -->
        <td style="border:none; width:47%; padding:0; vertical-align:top;">
          <table style="width:100%; border:1px solid #cbd5e1; background-color:#f8fafc; border-radius:6pt; font-family:Consolas,monospace; font-size:8.5pt;">
            <tr>
              <td style="border:none; padding:4pt 6pt; color:#475569; font-family:'Segoe UI',sans-serif;">Total Tarif Awal (Tarif Setting)</td>
              <td style="border:none; padding:4pt 6pt; text-align:right; font-weight:bold; color:#334155;">${formatIDR(p.total_setting_price || p.grand_total)}</td>
            </tr>
            <tr style="border-top:1px solid #e2e8f0;">
              <td style="border:none; padding:4pt 6pt; color:#0f172a; font-family:'Segoe UI',sans-serif; font-weight:bold;">Total Tarif Penyesuaian Client</td>
              <td style="border:none; padding:4pt 6pt; text-align:right; font-weight:bold; color:#4338ca;">${formatIDR(p.total_adjusted_price || p.grand_total)}</td>
            </tr>
            ${p.adjustment_diff !== undefined && p.adjustment_diff !== 0 ? `
              <tr style="border-top:1px solid #e2e8f0;">
                <td style="border:none; padding:4pt 6pt; color:${p.adjustment_diff < 0 ? '#059669' : '#d97706'}; font-family:'Segoe UI',sans-serif;">
                  ${p.adjustment_diff < 0 ? 'Potongan / Diskon Negosiasi' : 'Penyesuaian Biaya Tambahan'}
                </td>
                <td style="border:none; padding:4pt 6pt; text-align:right; font-weight:bold; color:${p.adjustment_diff < 0 ? '#059669' : '#d97706'};">
                  ${p.adjustment_diff < 0 ? '(-)' : '(+)'} ${formatIDR(Math.abs(p.adjustment_diff))}
                </td>
              </tr>
            ` : ''}
            <tr style="border-top:1.5pt solid #cbd5e1; border-bottom:1.5pt solid #cbd5e1; background-color:#f1f5f9;">
              <td style="border:none; padding:5pt 6pt; font-size:9pt; font-weight:900; color:#0f172a; font-family:'Segoe UI',sans-serif; text-transform:uppercase;">TOTAL TAGIHAN KLIEN</td>
              <td style="border:none; padding:5pt 6pt; font-size:10pt; font-weight:900; color:#0f172a; text-align:right;">${formatIDR(p.grand_total)}</td>
            </tr>
            <tr>
              <td style="border:none; padding:4pt 6pt; color:#059669; font-family:'Segoe UI',sans-serif; font-weight:bold;">Total Pembayaran Masuk (Paid)</td>
              <td style="border:none; padding:4pt 6pt; text-align:right; font-weight:bold; color:#059669;">(-) ${formatIDR(p.paid_amount)}</td>
            </tr>
            <tr style="border-top:1.5pt solid #cbd5e1;">
              <td style="border:none; padding:5pt 6pt; font-size:9.5pt; font-weight:900; color:${p.remaining_amount <= 0 ? '#059669' : '#b91c1c'}; font-family:'Segoe UI',sans-serif; text-transform:uppercase;">SISA KEWAJIBAN TAGIHAN</td>
              <td style="border:none; padding:5pt 6pt; font-size:10.5pt; font-weight:900; color:${p.remaining_amount <= 0 ? '#059669' : '#b91c1c'}; text-align:right;">${formatIDR(p.remaining_amount)}</td>
            </tr>
          </table>
        </td>
      </tr>
    </table>

    ${paymentsHtml}

    <!-- 5. KLAUSUL GARANSI & OTORISASI TANDA TANGAN -->
    <div style="border-top:1pt solid #cbd5e1; padding-top:8pt; margin-top:10pt;">
      <div style="font-size:7.5pt; color:#64748b; margin-bottom:10pt; line-height:1.35;">
        <b style="color:#475569; text-transform:uppercase;">Syarat & Ketentuan Layanan:</b><br>
        1. ${agency.terms_notes || `Pembayaran dinyatakan sah setelah dana efektif masuk ke rekening koran resmi a.n. ${agencyName}.`}<br>
        2. Masa garansi pemeliharaan teknis & perbaikan bug aktif selama 30 hari kalender sejak tanggal serah terima aplikasi.<br>
        3. Hak cipta dan seluruh berkas kode sumber (*source code*) diserahkan penuh kepada klien setelah penyelesaian pelunasan 100%.
      </div>

      <table style="width:100%; border:none;">
        <tr>
          <!-- Pihak Klien -->
          <td style="border:none; width:50%; text-align:center; padding:0; vertical-align:top;">
            <div style="font-size:8.5pt; font-weight:bold; color:#475569; text-transform:uppercase; letter-spacing:0.5pt;">
              Diterima & Disetujui Oleh:
            </div>
            <div style="height:48pt;"></div>
            <div style="font-size:9pt; font-weight:bold; text-decoration:underline; color:#0f172a;">
              ${c.contact_person || c.name || 'Pihak Klien'}
            </div>
            <div style="font-size:7.5pt; color:#64748b;">
              ${c.name || 'Penanggung Jawab Proyek'}
            </div>
          </td>

          <!-- Pihak Agency -->
          <td style="border:none; width:50%; text-align:center; padding:0; vertical-align:top;">
            <div style="font-size:8.5pt; font-weight:bold; color:#475569; text-transform:uppercase; letter-spacing:0.5pt;">
              Hormat Kami, ${agencyName}:
            </div>
            
            <!-- Stempel Digital Agency -->
            <table style="width:68pt; height:68pt; border:1.5pt dashed #4338ca; background-color:#f5f3ff; border-radius:34pt; margin:4pt auto; text-align:center;">
              <tr>
                <td style="border:none; text-align:center; vertical-align:middle; padding:2pt;">
                  <div style="font-size:6.5pt; font-weight:900; color:#3730a3; text-transform:uppercase;">${(agency.agency_name || 'VALORA').toUpperCase()}</div>
                  <div style="font-size:6pt; font-weight:bold; color:#059669; font-family:Consolas,monospace;">OFFICIAL SEAL</div>
                  <div style="font-size:4.5pt; color:#4338ca; font-family:Consolas,monospace;">FINANCE & CONTRACT</div>
                </td>
              </tr>
            </table>

            <div style="font-size:9pt; font-weight:bold; text-decoration:underline; color:#0f172a;">
              ${agency.signatory_name || 'Muhammad Iqbal Murtadho'}
            </div>
            <div style="font-size:8pt; font-weight:bold; color:#4338ca;">
              ${agency.signatory_title || 'Managing Director & Lead Architect'}
            </div>
          </td>
        </tr>
      </table>
    </div>

    </div> <!-- Close Outer Container Border -->

  </div>
</body>
</html>
  `;

  const blob = new Blob(['\ufeff', wordHtml], {
    type: 'application/msword;charset=utf-8'
  });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  const safeClient = (c.name || 'Client').replace(/[^a-zA-Z0-9_-]/g, '_');
  a.download = `Invoice_${invoiceNo}_${safeClient}.doc`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
  return true;
}

/**
 * Client-Side Microsoft Word (.doc) Exporter for Kuitansi (Payment Receipt)
 * Generates an Office-compliant Word HTML document 100% identical to the on-screen preview.
 * Embeds the brand logo image and formal seal stamp directly into the file.
 */
export async function exportReceiptToWord(receiptData, comp) {
  if (!receiptData || !receiptData.receipt) return false;

  const r = receiptData.receipt;
  const p = receiptData.project || {};
  const c = receiptData.client || {};

  const compName = (comp.legal_name || comp.name || 'PT VALORA DIGITAL SOLUSINDO').toUpperCase();
  const receiptNo = r.receipt_number || 'KWT-RESMI';
  const payDate = formatDate(r.payment_date);

  const logoBase64 = await resolveLogoBase64(comp?.logo_url);

  const wordHtml = `
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
  <meta charset="utf-8">
  <title>Kuitansi ${receiptNo} — ${compName}</title>
  <!--[if gte mso 9]>
  <xml>
    <w:WordDocument>
      <w:View>Print</w:View>
      <w:Zoom>100</w:Zoom>
      <w:DoNotOptimizeForBrowser/>
    </w:WordDocument>
  </xml>
  <![endif]-->
  <style>
    @page Section1 {
      size: 595.3pt 841.9pt; /* A4 */
      margin: 35.4pt 42.5pt 42.5pt 42.5pt;
      mso-header-margin: 35.4pt;
      mso-footer-margin: 35.4pt;
      mso-paper-source: 0;
    }
    div.Section1 { 
      page: Section1; 
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; 
      font-size: 10pt; 
      color: #0f172a; 
      line-height: 1.4; 
      background-color: #f1f5f9;
      padding: 0;
    }
    table { border-collapse: collapse; width: 100%; margin-bottom: 8pt; }
    td { padding: 6pt 8pt; vertical-align: top; }
  </style>
</head>
<body>
  <div class="Section1">
    
    <!-- OUTER CONTAINER BORDER KUITANSI FORMAL (WYSIWYG CANVAS PREVIEW) -->
    <div style="border:2pt solid #0f172a; border-radius:8pt; padding:18pt 22pt; background-color:#ffffff; margin:0 auto; max-width:100%;">
      
      <!-- Watermark Latar Belakang Kuitansi Resmi -->
      <div style="text-align:center; color:#f1f5f9; font-size:40pt; font-weight:900; letter-spacing:8pt; text-transform:uppercase; margin-top:-6pt; margin-bottom:-26pt; font-family:'Segoe UI',sans-serif; user-select:none;">
        ${(comp.agency_name || comp.name || 'VALORA').toUpperCase()}
      </div>

      <!-- 1. KOP SURAT PERUSAHAAN & METADATA KUITANSI -->
      <table style="width:100%; border:none; margin-bottom:8pt; position:relative;">
        <tr>
          <td style="width:65%; padding:0; vertical-align:top;">
            <table style="border:none; width:100%; margin-bottom:4pt;">
              <tr>
                <td style="border:none; width:44pt; vertical-align:middle; padding:0 8pt 0 0;">
                  <img src="${logoBase64}" width="44" height="44" style="width:44pt; height:44pt; object-fit:contain; border-radius:8pt; vertical-align:middle; display:block;" alt="Logo Valora" />
                </td>
                <td style="border:none; vertical-align:middle; padding:0;">
                  <div style="font-size:12.5pt; font-weight:900; color:#0f172a; text-transform:uppercase; letter-spacing:0.5pt; font-family:'Segoe UI',sans-serif;">
                    ${compName}
                  </div>
                  <div style="font-size:8.5pt; font-weight:bold; color:#4338ca; text-transform:uppercase; letter-spacing:0.8pt; font-family:'Segoe UI',sans-serif;">
                    ${comp.tagline || 'Software Engineering & Enterprise Web Solutions'}
                  </div>
                </td>
              </tr>
            </table>
            <div style="font-size:8pt; color:#475569; margin-top:2pt; line-height:1.35;">
              ${comp.operational_address || comp.address || '-'}<br>
              Hotline/WA: <b style="color:#0f172a;">${comp.hotline_wa || comp.phone || '-'}</b> • Email: <b style="color:#0f172a;">${comp.email || '-'}</b>
            </div>
          </td>
          <td style="width:35%; padding:0; text-align:right; vertical-align:top;">
            <div style="font-size:20pt; font-weight:900; color:#0f172a; font-family:Georgia,serif; text-transform:uppercase; letter-spacing:0.5pt;">
              KUITANSI
            </div>
            <div style="font-size:8pt; font-weight:bold; color:#64748b; text-transform:uppercase; letter-spacing:0.5pt;">
              Official Payment Receipt
            </div>
            <div style="font-size:9pt; font-family:Consolas,monospace; font-weight:bold; color:#0f172a; margin-top:4pt;">
              <span style="color:#64748b;">No:</span> <span style="background-color:#f1f5f9; padding:2pt 6pt; border:1px solid #cbd5e1; border-radius:3pt;">${receiptNo}</span>
            </div>
          </td>
        </tr>
      </table>

      <div style="border-bottom:2pt solid #0f172a; margin-bottom:12pt;"></div>

      <!-- 2. FORMAL RECEIPT BODY (4 ROWS WITH DASHED DIVIDERS) -->
      <table style="width:100%; border:none; margin-bottom:12pt;">
        <tr style="border-bottom:1px dashed #cbd5e1;">
          <td style="width:125pt; font-weight:bold; color:#475569; font-size:8.5pt; text-transform:uppercase; padding:6pt 0; vertical-align:baseline; letter-spacing:0.5pt;">
            Telah Terima Dari:
          </td>
          <td style="font-weight:bold; font-size:11pt; color:#0f172a; padding:6pt 0; vertical-align:baseline;">
            ${c.name || 'Klien Valora'} ${c.contact_person ? `<span style="font-weight:normal; font-size:8.5pt; color:#64748b; margin-left:4pt;">(a.n. ${c.contact_person})</span>` : ''}
          </td>
        </tr>
        <tr style="border-bottom:1px dashed #cbd5e1;">
          <td style="width:125pt; font-weight:bold; color:#475569; font-size:8.5pt; text-transform:uppercase; padding:6pt 0; vertical-align:baseline; letter-spacing:0.5pt;">
            Uang Sejumlah:
          </td>
          <td style="padding:6pt 0; vertical-align:baseline;">
            <div style="background-color:#f8fafc; border:1px solid #cbd5e1; border-radius:4pt; padding:5pt 8pt; font-style:italic; font-weight:bold; color:#0f172a; font-size:9pt;">
              "${terbilang(r.amount)}"
            </div>
          </td>
        </tr>
        <tr style="border-bottom:1px dashed #cbd5e1;">
          <td style="width:125pt; font-weight:bold; color:#475569; font-size:8.5pt; text-transform:uppercase; padding:6pt 0; vertical-align:baseline; letter-spacing:0.5pt;">
            Untuk Pembayaran:
          </td>
          <td style="padding:6pt 0; vertical-align:baseline;">
            <div style="font-weight:bold; font-size:9.5pt; color:#0f172a;">
              ${r.reference_no || `Pembayaran Proyek ${p.app_name || ''}`}
            </div>
            ${p.app_name ? `<div style="font-size:8pt; color:#475569; margin-top:2pt;">Aplikasi: <b>${p.app_name}</b> • No. Invoice: <b style="color:#4338ca; font-family:Consolas,monospace;">${p.invoice_number || '-'}</b></div>` : ''}
          </td>
        </tr>
        <tr style="border-bottom:1px dashed #cbd5e1;">
          <td style="width:125pt; font-weight:bold; color:#475569; font-size:8.5pt; text-transform:uppercase; padding:6pt 0; vertical-align:baseline; letter-spacing:0.5pt;">
            Metode Pembayaran:
          </td>
          <td style="padding:6pt 0; vertical-align:baseline;">
            <span style="font-family:Consolas,monospace; font-weight:bold; background-color:#eef2ff; color:#3730a3; padding:2pt 6pt; border:1px solid #c7d2fe; border-radius:3pt; font-size:8.5pt;">
              ${r.payment_method || 'Transfer Bank'}
            </span>
            <span style="font-size:8pt; color:#64748b; margin-left:6pt;">(Diterima & diverifikasi efektif di rekening koran)</span>
          </td>
        </tr>
      </table>

      <!-- 3. KOTAK NOMINAL BESAR & REKAP SISA TAGIHAN -->
      <table style="width:100%; border:none; margin-bottom:14pt;">
        <tr>
          <!-- Kotak Nominal Classic Receipt Badge -->
          <td style="width:50%; padding:0; vertical-align:middle;">
            <div style="border:2pt solid #0f172a; background-color:#f8fafc; border-radius:6pt; padding:8pt 10pt;">
              <div style="font-size:7.5pt; font-weight:bold; color:#64748b; text-transform:uppercase; margin-bottom:2pt; letter-spacing:0.5pt;">
                Jumlah Uang (Nominal):
              </div>
              <div style="font-size:18pt; font-weight:900; font-family:Consolas,monospace; color:#047857;">
                ${formatIDR(r.amount)}
              </div>
              <div style="font-size:7.5pt; color:#64748b; font-style:italic; margin-top:2pt;">
                *Tanda bukti penerimaan pembayaran sah
              </div>
            </div>
          </td>

          <td style="width:4%;"></td>

          <!-- Rekapitulasi Sisa Tagihan -->
          <td style="width:46%; padding:0; vertical-align:middle;">
            ${p.id ? `
              <table style="width:100%; border:1px solid #cbd5e1; background-color:#ffffff; border-radius:6pt; font-family:Consolas,monospace; font-size:8.5pt;">
                <tr>
                  <td style="border:none; padding:3pt 6pt; color:#64748b; font-family:'Segoe UI',sans-serif;">Total Nilai Proyek:</td>
                  <td style="border:none; padding:3pt 6pt; text-align:right; font-weight:bold; color:#0f172a;">${formatIDR(p.grand_total)}</td>
                </tr>
                <tr>
                  <td style="border:none; padding:3pt 6pt; color:#047857; font-family:'Segoe UI',sans-serif; font-weight:bold;">Pembayaran Ini:</td>
                  <td style="border:none; padding:3pt 6pt; text-align:right; font-weight:bold; color:#047857;">(-) ${formatIDR(r.amount)}</td>
                </tr>
                <tr style="border-top:1pt solid #cbd5e1;">
                  <td style="border:none; padding:4pt 6pt; font-weight:bold; color:${p.remaining_amount <= 0 ? '#047857' : '#d97706'}; font-family:'Segoe UI',sans-serif;">Sisa Kewajiban Tagihan:</td>
                  <td style="border:none; padding:4pt 6pt; text-align:right; font-weight:bold; color:${p.remaining_amount <= 0 ? '#047857' : '#d97706'};">${formatIDR(p.remaining_amount)}</td>
                </tr>
              </table>
            ` : ''}
          </td>
        </tr>
      </table>

      <!-- 4. TEMPAT, TANGGAL & TANDA TANGAN PENERIMA -->
      <div style="border-top:1.5pt solid #0f172a; padding-top:8pt; margin-top:10pt;">
        <table style="width:100%; border:none;">
          <tr>
            <td style="border:none; width:55%; padding:0; vertical-align:top; font-size:7.5pt; color:#64748b; line-height:1.35;">
              <b style="color:#475569;">Catatan & Ketentuan Kuitansi:</b><br>
              1. Kuitansi ini sah dan mengikat setelah dana efektif masuk ke rekening ${compName}.<br>
              2. Rincian deliverable tercantum lengkap pada Faktur Tagihan (${p.invoice_number || 'Invoice Proyek'}).<br>
              3. Diterbitkan secara elektronik dan sah tanpa memerlukan meterai tempel fisik sesuai UU ITE.
            </td>
            <td style="border:none; width:45%; padding:0; text-align:right; vertical-align:top;">
              <div style="font-size:8.5pt; color:#334155;">
                Jakarta, <b>${payDate}</b>
              </div>
              
              <!-- Stempel Digital Lunas -->
              <table style="width:64pt; height:64pt; border:1.5pt dashed #059669; background-color:#ecfdf5; border-radius:32pt; margin:4pt 0 4pt auto; text-align:center;">
                <tr>
                  <td style="border:none; text-align:center; vertical-align:middle; padding:2pt;">
                    <div style="font-size:6.5pt; font-weight:900; color:#065f46; text-transform:uppercase;">${(comp.agency_name || comp.name || 'VALORA').toUpperCase()}</div>
                    <div style="font-size:7.5pt; font-weight:900; color:#047857; font-family:Consolas,monospace; letter-spacing:1pt;">LUNAS</div>
                    <div style="font-size:5pt; font-weight:bold; color:#475569; font-family:Consolas,monospace;">FINANCE DEPT</div>
                  </td>
                </tr>
              </table>

              <div style="font-size:9pt; font-weight:bold; text-decoration:underline; color:#0f172a;">
                ${comp.signatory_name || 'Muhammad Iqbal Murtadho'}
              </div>
              <div style="font-size:8pt; font-weight:bold; color:#4338ca;">
                ${comp.signatory_title || 'Managing Director & Lead Architect'}
              </div>
              <div style="font-size:7.5pt; color:#64748b; font-family:Consolas,monospace;">
                ${compName}
              </div>
            </td>
          </tr>
        </table>
      </div>

    </div>

  </div>
</body>
</html>
  `;

  const blob = new Blob(['\ufeff', wordHtml], {
    type: 'application/msword;charset=utf-8'
  });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  const safeClient = (c.name || 'Client').replace(/[^a-zA-Z0-9_-]/g, '_');
  a.download = `Kuitansi_${receiptNo}_${safeClient}.doc`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
  return true;
}
