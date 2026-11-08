/**
 * Utility Ekspor CSV Client-Side (Front-End In-Memory Export)
 * Mematuhi Standar Mandat [INV-UI-06] / [RULE-17] Zero-Roundtrip CSV.
 * 
 * Standar Teknis:
 * 1. UTF-8 Byte Order Mark (BOM: \uFEFF) untuk kompatibilitas sempurna MS Excel (Windows/Mac) & Google Sheets.
 * 2. Mitigasi CSV Formula Injection (CWE-1236) pada sel yang diawali =, +, -, @.
 * 3. Escaping tanda kutip ganda ("") dan wrapping string secara defensif.
 * 4. Zero network round-trip (< 5ms via in-memory Blob) tanpa beban server backend.
 */

export function exportToCsv({
  data = [],
  filename = 'data_export',
  columns = null, // array of { key: string, label: string, transform?: (val, row) => string }
  appendDate = true
}) {
  if (!Array.isArray(data) || data.length === 0) {
    console.warn('exportToCsv: Data kosong atau bukan array.');
    return false;
  }

  // 1. Tentukan definisi kolom (Header)
  let resolvedColumns = [];
  if (Array.isArray(columns) && columns.length > 0) {
    resolvedColumns = columns;
  } else {
    // Ambil seluruh keys dari record pertama
    const sample = data[0];
    resolvedColumns = Object.keys(sample).map(key => ({
      key,
      label: key.replace(/_/g, ' ').toUpperCase()
    }));
  }

  // Helper untuk membersihkan dan mengamankan nilai sel
  function sanitizeCell(val) {
    if (val === null || val === undefined) return '""';

    // Objek bersarang: Ambil identifier atau stringify ringkas
    if (typeof val === 'object') {
      if (val instanceof Date) {
        val = val.toISOString().split('T')[0];
      } else if (val.nama || val.nama_lengkap || val.name) {
        val = val.nama || val.nama_lengkap || val.name;
      } else {
        val = JSON.stringify(val);
      }
    }

    let str = String(val).trim();

    // Mitigasi Formula Injection (CWE-1236): Awali dengan tanda petik tunggal jika diawali =, +, -, @
    if (/^[=+\-@]/.test(str)) {
      str = "'" + str;
    }

    // Escape kutip ganda internal
    str = str.replace(/"/g, '""');

    return `"${str}"`;
  }

  // 2. Baris Header
  const headerRow = resolvedColumns.map(col => sanitizeCell(col.label || col.key)).join(',');

  // 3. Baris Data
  const rows = data.map(item => {
    return resolvedColumns.map(col => {
      let rawVal;
      if (typeof col.transform === 'function') {
        rawVal = col.transform(item[col.key], item);
      } else {
        rawVal = item[col.key];
      }
      return sanitizeCell(rawVal);
    }).join(',');
  });

  // 4. Rakit Konten CSV dengan UTF-8 BOM
  const csvContent = '\uFEFF' + [headerRow, ...rows].join('\r\n');

  // 5. Buat Blob dan picu unduhan peramban
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');

  const now = new Date();
  const dateStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
  const finalFilename = appendDate ? `${filename}_${dateStr}.csv` : `${filename}.csv`;

  link.setAttribute('href', url);
  link.setAttribute('download', finalFilename);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);

  return true;
}
