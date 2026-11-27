/**
 * Reusable Indonesian date/time formatting and timezone-safe parsing utilities
 * for SIMRS BigSIRS.
 */

export const INDO_MONTHS_LONG = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

export const INDO_MONTHS_SHORT = [
  'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
  'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
];

/**
 * Safely parses dates:
 * - Date-only ('YYYY-MM-DD') and naive datetime ('YYYY-MM-DD HH:mm[:ss]' / 'YYYY-MM-DDTHH:mm[:ss]')
 *   are parsed strictly in local calendar time after validating real calendar bounds.
 * - ISO timestamps carrying an explicit timezone ('Z' or '±HH:MM') are delegated to native Date()
 *   so instant timezone semantics are preserved instead of stripped.
 *
 * @param {string|Date|null|undefined} input
 * @returns {Date|null}
 */
export function parseDateSafe(input) {
  if (input === null || input === undefined) return null;
  if (input instanceof Date) {
    return isNaN(input.getTime()) ? null : new Date(input.getTime());
  }

  const raw = String(input).trim();
  if (!raw) return null;

  // Date-only or naive datetime WITHOUT timezone offset (Z or ±HH:MM)
  const naiveMatch = raw.match(
    /^(\d{4})-(\d{2})-(\d{2})(?:[T\s](\d{2}):(\d{2})(?::(\d{2})(?:\.\d+)?)?)?$/
  );
  if (naiveMatch) {
    const year = Number(naiveMatch[1]);
    const month = Number(naiveMatch[2]);
    const day = Number(naiveMatch[3]);
    const hours = naiveMatch[4] !== undefined ? Number(naiveMatch[4]) : 0;
    const minutes = naiveMatch[5] !== undefined ? Number(naiveMatch[5]) : 0;
    const seconds = naiveMatch[6] !== undefined ? Number(naiveMatch[6]) : 0;

    if (
      month < 1 || month > 12 ||
      day < 1 || day > 31 ||
      hours < 0 || hours > 23 ||
      minutes < 0 || minutes > 59 ||
      seconds < 0 || seconds > 59
    ) {
      return null;
    }

    const parsed = new Date(year, month - 1, day, hours, minutes, seconds);
    if (
      parsed.getFullYear() !== year ||
      parsed.getMonth() !== month - 1 ||
      parsed.getDate() !== day
    ) {
      return null;
    }
    return parsed;
  }

  // ISO timestamp with timezone offset (or other standard format)
  const parsed = new Date(raw);
  return isNaN(parsed.getTime()) ? null : parsed;
}

function isEmptyInput(value) {
  return value === null || value === undefined || String(value).trim() === '';
}

/**
 * Format date as "DD-MM-YYYY" (e.g. "12-05-1986").
 * Preserves non-empty invalid input string fallback cleanly.
 */
export function formatDateDMY(value, emptyText = '-') {
  if (isEmptyInput(value)) return emptyText;
  const d = parseDateSafe(value);
  if (!d) return String(value);

  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  return `${day}-${month}-${year}`;
}

/**
 * Format date as "DD/MM/YYYY" (e.g. "12/09/2026").
 */
export function formatDateSlash(value, emptyText = '-') {
  if (isEmptyInput(value)) return emptyText;
  const d = parseDateSafe(value);
  if (!d) return String(value);

  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  return `${day}/${month}/${year}`;
}

/**
 * Format date as "DD MMM YYYY" in Indonesian (e.g. "05 Agu 2026").
 */
export function formatDateShort(value, emptyText = '—') {
  if (isEmptyInput(value)) return emptyText;
  const d = parseDateSafe(value);
  if (!d) return String(value);

  const day = String(d.getDate()).padStart(2, '0');
  const month = INDO_MONTHS_SHORT[d.getMonth()];
  const year = d.getFullYear();
  return `${day} ${month} ${year}`;
}

/**
 * Format date as Indonesian long month name (e.g. "5 Mei 1986" or "05 Mei 1986" when padDay is true).
 */
export function formatDateLong(value, options = {}) {
  const { emptyText = '—', padDay = false } = options;
  if (isEmptyInput(value)) return emptyText;
  const d = parseDateSafe(value);
  if (!d) return String(value);

  const rawDay = d.getDate();
  const day = padDay ? String(rawDay).padStart(2, '0') : String(rawDay);
  const month = INDO_MONTHS_LONG[d.getMonth()];
  const year = d.getFullYear();
  return `${day} ${month} ${year}`;
}

/**
 * Format date-time as "12 Mei 1986 • 14:30 WIB".
 */
export function formatDateTimeIndo(value, emptyText = '—') {
  if (isEmptyInput(value)) return emptyText;
  const d = parseDateSafe(value);
  if (!d) return String(value);

  const day = d.getDate();
  const month = INDO_MONTHS_LONG[d.getMonth()];
  const year = d.getFullYear();
  const hours = String(d.getHours()).padStart(2, '0');
  const minutes = String(d.getMinutes()).padStart(2, '0');
  return `${day} ${month} ${year} • ${hours}:${minutes} WIB`;
}

/**
 * Normalize date to "YYYY-MM-DD" for HTML date inputs.
 */
export function formatDateInput(value, emptyText = '') {
  if (isEmptyInput(value)) return emptyText;
  const d = parseDateSafe(value);
  if (!d) return emptyText;

  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}
