/**
 * Indonesian DateTime & Formatting Helpers for Hyperion Vault
 * High clarity, explicit weekdays, full Indonesian month names, and clear time indicators.
 */

const NAMA_HARI = [
  'Ahad',
  'Senin',
  'Selasa',
  'Rabu',
  'Kamis',
  "Jum'at",
  'Sabtu'
];

const NAMA_BULAN = [
  'Januari',
  'Februari',
  'Maret',
  'April',
  'Mei',
  'Juni',
  'Juli',
  'Agustus',
  'September',
  'Oktober',
  'November',
  'Desember'
];

/**
 * Format tanggal ke format Indonesia yang mudah dibaca.
 * Contoh: "Rabu, 30 September 2026" atau "30 September 2026"
 */
export function formatIndoDate(dateInput, includeDay = true) {
  if (!dateInput) return '-';
  const d = new Date(dateInput);
  if (isNaN(d.getTime())) return String(dateInput);

  const hari = NAMA_HARI[d.getDay()];
  const tanggal = d.getDate();
  const bulan = NAMA_BULAN[d.getMonth()];
  const tahun = d.getFullYear();

  if (includeDay) {
    return `${hari}, ${tanggal} ${bulan} ${tahun}`;
  }
  return `${tanggal} ${bulan} ${tahun}`;
}

/**
 * Format tanggal dan waktu ke format Indonesia lengkap dengan indikator WIB.
 * Contoh: "Rabu, 30 September 2026, 14:30 WIB"
 */
export function formatIndoDateTime(dateInput, includeDay = true) {
  if (!dateInput) return '-';
  const d = new Date(dateInput);
  if (isNaN(d.getTime())) return String(dateInput);

  const dateStr = formatIndoDate(d, includeDay);
  const hours = String(d.getHours()).padStart(2, '0');
  const minutes = String(d.getMinutes()).padStart(2, '0');

  return `${dateStr}, ${hours}:${minutes} WIB`;
}

/**
 * Format waktu saja (Jam:Menit WIB)
 * Contoh: "14:30 WIB"
 */
export function formatIndoTime(dateInput) {
  if (!dateInput) return '-';
  const d = new Date(dateInput);
  if (isNaN(d.getTime())) return String(dateInput);

  const hours = String(d.getHours()).padStart(2, '0');
  const minutes = String(d.getMinutes()).padStart(2, '0');

  return `${hours}:${minutes} WIB`;
}

/**
 * Format number into Indonesian Rupiah currency format.
 */
export function formatRupiah(amount) {
  if (typeof amount !== 'number') {
    amount = Number(amount) || 0;
  }
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(amount);
}
