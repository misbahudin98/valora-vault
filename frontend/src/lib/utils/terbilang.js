/**
 * Utility konversi angka nominal keuangan menjadi kalimat teks bahasa Indonesia (Terbilang Resmi).
 * 100% Pure JavaScript tanpa dependensi eksternal.
 * 
 * @param {number|string} n
 * @returns {string} Contoh: "Tujuh Juta Lima Ratus Ribu Rupiah"
 */
export function terbilang(n) {
  if (n === null || n === undefined || isNaN(n)) return '';
  const num = Math.floor(Math.abs(Number(n)));
  if (num === 0) return 'Nol Rupiah';

  const satuan = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

  function spell(x) {
    if (x < 12) return satuan[x];
    if (x < 20) return spell(x - 10) + ' Belas';
    if (x < 100) return spell(Math.floor(x / 10)) + ' Puluh' + (x % 10 !== 0 ? ' ' + spell(x % 10) : '');
    if (x < 200) return 'Seratus' + (x % 100 !== 0 ? ' ' + spell(x % 100) : '');
    if (x < 1000) return spell(Math.floor(x / 100)) + ' Ratus' + (x % 100 !== 0 ? ' ' + spell(x % 100) : '');
    if (x < 2000) return 'Seribu' + (x % 1000 !== 0 ? ' ' + spell(x % 1000) : '');
    if (x < 1000000) return spell(Math.floor(x / 1000)) + ' Ribu' + (x % 1000 !== 0 ? ' ' + spell(x % 1000) : '');
    if (x < 1000000000) return spell(Math.floor(x / 1000000)) + ' Juta' + (x % 1000000 !== 0 ? ' ' + spell(x % 1000000) : '');
    if (x < 1000000000000) return spell(Math.floor(x / 1000000000)) + ' Miliar' + (x % 1000000000 !== 0 ? ' ' + spell(x % 1000000000) : '');
    return spell(Math.floor(x / 1000000000000)) + ' Triliun' + (x % 1000000000000 !== 0 ? ' ' + spell(x % 1000000000000) : '');
  }

  const result = spell(num).trim() + ' Rupiah';
  return result.replace(/\s+/g, ' ');
}
