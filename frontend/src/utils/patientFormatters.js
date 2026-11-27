/**
 * Patient data formatting helpers for SIMRS BigSIRS
 */

import {
  parseDateSafe,
  formatDateLong,
  formatDateTimeIndo
} from './dateFormatters';

/**
 * Calculates current age from birthdate string (YYYY-MM-DD or timestamp)
 * @param {string|Date} birthDateStr
 * @returns {number}
 */
export function calculateAge(birthDateStr) {
  const birth = parseDateSafe(birthDateStr);
  if (!birth) return 0;

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
 * @param {string|Date} dateStr
 * @returns {string}
 */
export function formatDateIndo(dateStr) {
  return formatDateLong(dateStr, { emptyText: '—', padDay: false });
}

export { formatDateTimeIndo };

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

/**
 * Returns single letter gender code: 'L' or 'P'
 * @param {string} gender
 * @returns {string}
 */
export function formatGenderCode(gender) {
  if (!gender) return '-';
  const upper = String(gender).trim().toUpperCase();
  if (upper.startsWith('L')) return 'L';
  if (upper.startsWith('P') || upper.startsWith('W')) return 'P';
  return upper.slice(0, 1);
}

/**
 * Calculates detailed age in years, months, and days: "27 Th 2 Bl 8 hr"
 * @param {string} birthDateStr
 * @param {Date} [referenceDate]
 * @returns {string}
 */
export function calculateDetailedAge(birthDateStr, referenceDate = new Date()) {
  if (!birthDateStr) return '—';
  const birth = parseDateSafe(birthDateStr);
  if (!birth) return '—';

  const ref = parseDateSafe(referenceDate);
  if (!ref) return '—';

  let years = ref.getFullYear() - birth.getFullYear();
  let months = ref.getMonth() - birth.getMonth();
  let days = ref.getDate() - birth.getDate();

  if (days < 0) {
    months -= 1;
    const prevMonthDays = new Date(ref.getFullYear(), ref.getMonth(), 0).getDate();
    days += prevMonthDays;
  }

  if (months < 0) {
    years -= 1;
    months += 12;
  }

  return `${Math.max(0, years)} Th ${Math.max(0, months)} Bl ${Math.max(0, days)} hr`;
}
