// Formatters dan utilities untuk Modul Billing Rawat Jalan SIMRS BigSIRS

/**
 * Format number to Indonesian Rupiah (e.g. 625000 -> "Rp 625.000")
 * @param {number|string} amount
 * @returns {string}
 */
export function formatRupiah(amount) {
  if (amount === null || amount === undefined || isNaN(amount)) {
    return 'Rp 0';
  }
  const num = Math.round(Number(amount));
  return 'Rp ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

/**
 * Format ISO Date string to localized Indonesian Date (e.g. "12/09/2026")
 * @param {string} dateStr
 * @returns {string}
 */
export function formatBillingDate(dateStr) {
  if (!dateStr) return '-';
  try {
    const d = new Date(dateStr);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
  } catch {
    return dateStr;
  }
}

/**
 * Get CSS class for billing status badge
 * @param {string} status
 * @returns {string}
 */
export function getBillingStatusBadgeClass(status) {
  switch (status) {
    case 'Siap Dibayar':
      return 'badge-status-ready';
    case 'Lunas':
      return 'badge-status-paid';
    case 'Menunggu Verifikasi':
      return 'badge-status-verify';
    case 'Dibayar Sebagian':
      return 'badge-status-partial';
    case 'Belum Lengkap':
      return 'badge-status-incomplete';
    case 'Dibatalkan':
      return 'badge-status-cancelled';
    default:
      return 'badge-status-default';
  }
}

/**
 * Get CSS class for guarantor badge
 * @param {string} guarantorType
 * @returns {string}
 */
export function getGuarantorBadgeClass(guarantorType) {
  if (!guarantorType) return 'guarantor-general';
  if (guarantorType.includes('BPJS PBI')) return 'guarantor-bpjs-pbi';
  if (guarantorType.includes('BPJS')) return 'guarantor-bpjs';
  if (guarantorType.includes('Inhealth')) return 'guarantor-inhealth';
  if (guarantorType.includes('Asuransi')) return 'guarantor-insurance';
  return 'guarantor-general';
}
