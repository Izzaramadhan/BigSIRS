import { describe, it, expect } from 'vitest';
import {
  INDO_MONTHS_LONG,
  INDO_MONTHS_SHORT,
  parseDateSafe,
  formatDateDMY,
  formatDateSlash,
  formatDateShort,
  formatDateLong,
  formatDateTimeIndo,
  formatDateInput
} from '../dateFormatters';
import { formatBillingDate } from '../billingFormatters';

describe('dateFormatters foundation utility', () => {
  describe('parseDateSafe', () => {
    it('handles null, undefined, empty string, and whitespace', () => {
      expect(parseDateSafe(null)).toBeNull();
      expect(parseDateSafe(undefined)).toBeNull();
      expect(parseDateSafe('')).toBeNull();
      expect(parseDateSafe('   ')).toBeNull();
    });

    it('parses valid YYYY-MM-DD in local calendar time without UTC date shift', () => {
      const d = parseDateSafe('2026-01-01');
      expect(d).toBeInstanceOf(Date);
      expect(d.getFullYear()).toBe(2026);
      expect(d.getMonth()).toBe(0);
      expect(d.getDate()).toBe(1);
    });

    it('rejects invalid calendar dates instead of rolling over month/day', () => {
      expect(parseDateSafe('invalid-date')).toBeNull();
      expect(parseDateSafe('2026-02-30')).toBeNull();
      expect(parseDateSafe('2025-02-29')).toBeNull();
      expect(parseDateSafe('2026-13-01')).toBeNull();
      expect(parseDateSafe('2026-00-10')).toBeNull();
      expect(parseDateSafe('2026-11-20 25:00:00')).toBeNull();
      expect(parseDateSafe('2026-11-20 12:75:00')).toBeNull();
    });

    it('parses leap year dates and naive datetimes accurately', () => {
      const leap = parseDateSafe('2024-02-29');
      expect(leap.getFullYear()).toBe(2024);
      expect(leap.getMonth()).toBe(1);
      expect(leap.getDate()).toBe(29);

      const dt = parseDateSafe('2026-12-31 23:45:10');
      expect(dt.getFullYear()).toBe(2026);
      expect(dt.getMonth()).toBe(11);
      expect(dt.getDate()).toBe(31);
      expect(dt.getHours()).toBe(23);
      expect(dt.getMinutes()).toBe(45);
      expect(dt.getSeconds()).toBe(10);
    });

    it('preserves timezone instant semantics on ISO timestamps containing Z or offset', () => {
      const raw = '2026-11-20T02:00:00Z';
      const parsed = parseDateSafe(raw);
      const expectedInstant = new Date(raw);

      expect(parsed.getTime()).toBe(expectedInstant.getTime());
      expect(parsed.getFullYear()).toBe(expectedInstant.getFullYear());
      expect(parsed.getMonth()).toBe(expectedInstant.getMonth());
      expect(parsed.getDate()).toBe(expectedInstant.getDate());
      expect(parsed.getHours()).toBe(expectedInstant.getHours());
    });
  });

  describe('Indonesian locale constants and output formatters', () => {
    it('exposes consistent Indonesian month names', () => {
      expect(INDO_MONTHS_LONG).toHaveLength(12);
      expect(INDO_MONTHS_SHORT).toHaveLength(12);
      expect(INDO_MONTHS_LONG[7]).toBe('Agustus');
      expect(INDO_MONTHS_SHORT[7]).toBe('Agu');
    });

    it('formats YYYY-MM-DD consistently across timezones for historical, leap, and year-boundary dates', () => {
      expect(formatDateDMY('1945-08-17')).toBe('17-08-1945');
      expect(formatDateSlash('2024-02-29')).toBe('29/02/2024');
      expect(formatBillingDate('2026-01-01')).toBe('01/01/2026');
      expect(formatDateShort('2025-12-31')).toBe('31 Des 2025');
      expect(formatDateLong('2026-08-05')).toBe('5 Agustus 2026');
      expect(formatDateLong('2026-08-05', { padDay: true })).toBe('05 Agustus 2026');
      expect(formatDateInput('2026-05-09 10:00:00')).toBe('2026-05-09');
    });

    it('formats naive datetimes and timezone-aware timestamps accurately', () => {
      expect(formatDateTimeIndo('2026-11-20 14:30:00')).toBe('20 November 2026 • 14:30 WIB');
      expect(formatDateTimeIndo('2026-11-20')).toBe('20 November 2026 • 00:00 WIB');

      const isoZ = '2026-11-20T02:00:00Z';
      const d = new Date(isoZ);
      const expected = `${d.getDate()} ${INDO_MONTHS_LONG[d.getMonth()]} ${d.getFullYear()} • ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')} WIB`;
      expect(formatDateTimeIndo(isoZ)).toBe(expected);
    });

    it('returns configured fallbacks for empty values and safe fallback for invalid strings', () => {
      expect(formatDateDMY(null)).toBe('-');
      expect(formatDateDMY('')).toBe('-');
      expect(formatDateSlash(undefined)).toBe('-');
      expect(formatBillingDate('')).toBe('-');
      expect(formatBillingDate('invalid')).toBe('invalid');
      expect(formatDateShort(null)).toBe('—');
      expect(formatDateLong('')).toBe('—');
      expect(formatDateTimeIndo(undefined)).toBe('—');
      expect(formatDateInput('2026-02-30')).toBe('');
      expect(formatDateLong('2026-02-30')).toBe('2026-02-30');
    });
  });
});
