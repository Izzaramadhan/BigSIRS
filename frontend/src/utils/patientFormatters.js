/**
 * Patient data formatting helpers for SIMRS BigSIRS
 */

const INDO_MONTHS = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

/**
 * Calculates current age from birthdate string (YYYY-MM-DD)
 * @param {string} birthDateStr 
 * @returns {number}
 */
export function calculateAge(birthDateStr) {
  if (!birthDateStr) return 0;
  const birth = new Date(birthDateStr);
  if (isNaN(birth.getTime())) return 0;

  const today = new Date();
  let age = today.getFullYear() - birth.getFullYear();
  const m = today.getMonth() - birth.getMonth();

  if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
    age--;
  }

  return Math.max(0, age);
}

/**
 * Formats ISO date to Indonesian standard string: "12 Mei 1986"
 * @param {string} dateStr 
 * @returns {string}
 */
export function formatDateIndo(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return dateStr;
  
  const day = d.getDate();
  const month = INDO_MONTHS[d.getMonth()];
  const year = d.getFullYear();
  return `${day} ${month} ${year}`;
}

/**
 * Formats birthdate and age: "12 Mei 1986 (40 th)"
 * @param {string} birthDateStr 
 * @returns {string}
 */
export function formatDateWithAge(birthDateStr) {
  if (!birthDateStr) return '—';
  const formattedDate = formatDateIndo(birthDateStr);
  const age = calculateAge(birthDateStr);
  return `${formattedDate} (${age} th)`;
}

/**
 * Masks 16-digit NIK to format: "3471********0001"
 * @param {string} nik 
 * @returns {string}
 */
export function maskNik(nik) {
  if (!nik) return '—';
  const clean = String(nik).trim();
  if (clean.length === 16) {
    return `${clean.slice(0, 4)}********${clean.slice(12)}`;
  }
  if (clean.length > 8) {
    return `${clean.slice(0, 4)}${'*'.repeat(clean.length - 8)}${clean.slice(-4)}`;
  }
  return clean;
}

/**
 * Formats gender code to human-readable Indonesian text
 * @param {string} gender 'L' | 'P' | 'Laki-laki' | 'Perempuan'
 * @returns {string}
 */
export function formatGender(gender) {
  if (!gender) return '—';
  const upper = String(gender).toUpperCase();
  if (upper === 'L' || upper === 'LAKI-LAKI') return 'Laki-laki';
  if (upper === 'P' || upper === 'PEREMPUAN') return 'Perempuan';
  return gender;
}
