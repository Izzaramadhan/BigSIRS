import { describe, it, expect, vi, afterEach } from 'vitest';
import { 
  calculateAge, 
  formatDateIndo, 
  formatDateWithAge, 
  maskNik, 
  formatGender 
} from '../patientFormatters';

describe('patientFormatters utility', () => {
  afterEach(() => {
    vi.useRealTimers();
  });

  describe('calculateAge', () => {
    it('calculates age deterministically across birthday today, birthday tomorrow, and valid YYYY-MM-DD', () => {
      vi.useFakeTimers();
      vi.setSystemTime(new Date(2026, 4, 12, 0, 1, 0));

      // Birthday today -> turns 40
      expect(calculateAge('1986-05-12')).toBe(40);
      // Birthday tomorrow -> still 39
      expect(calculateAge('1986-05-13')).toBe(39);
      // Birthday yesterday -> already 40
      expect(calculateAge('1986-05-11')).toBe(40);
    });

    it('returns 0 for empty, null, future, or invalid calendar dates', () => {
      vi.useFakeTimers();
      vi.setSystemTime(new Date(2026, 4, 12, 12, 0, 0));

      expect(calculateAge('')).toBe(0);
      expect(calculateAge(null)).toBe(0);
      expect(calculateAge(undefined)).toBe(0);
      expect(calculateAge('invalid-date')).toBe(0);
      expect(calculateAge('2026-02-30')).toBe(0);
      expect(calculateAge('2025-02-29')).toBe(0);
      expect(calculateAge('2030-01-01')).toBe(0);
    });

    it('handles leap day (29 February) accurately on non-leap and leap years', () => {
      vi.useFakeTimers();

      vi.setSystemTime(new Date(2026, 1, 28, 10, 0, 0));
      expect(calculateAge('2000-02-29')).toBe(25);

      vi.setSystemTime(new Date(2026, 2, 1, 10, 0, 0));
      expect(calculateAge('2000-02-29')).toBe(26);

      vi.setSystemTime(new Date(2024, 1, 29, 10, 0, 0));
      expect(calculateAge('2000-02-29')).toBe(24);
    });

    it('supports SQL and ISO timestamps cleanly', () => {
      vi.useFakeTimers();
      vi.setSystemTime(new Date(2026, 4, 12, 12, 0, 0));

      expect(calculateAge('1990-05-12 08:30:00')).toBe(36);
      expect(calculateAge('1990-05-12T04:00:00Z')).toBe(36);
    });
  });

  describe('formatDateIndo', () => {
    it('formats date into Indonesian string safely across historical, leap, and year boundaries', () => {
      expect(formatDateIndo('1986-05-12')).toBe('12 Mei 1986');
      expect(formatDateIndo('1945-08-17')).toBe('17 Agustus 1945');
      expect(formatDateIndo('2024-02-29')).toBe('29 Februari 2024');
      expect(formatDateIndo('2025-12-31')).toBe('31 Desember 2025');
      expect(formatDateIndo('2026-01-01')).toBe('1 Januari 2026');
    });

    it('returns dash for empty input and preserves unparseable invalid string', () => {
      expect(formatDateIndo('')).toBe('—');
      expect(formatDateIndo(null)).toBe('—');
      expect(formatDateIndo(undefined)).toBe('—');
      expect(formatDateIndo('2026-02-30')).toBe('2026-02-30');
    });
  });

  describe('formatDateWithAge', () => {
    it('formats date and appends deterministic age in parentheses', () => {
      vi.useFakeTimers();
      vi.setSystemTime(new Date(2026, 4, 12, 12, 0, 0));

      const result = formatDateWithAge('1986-05-12');
      expect(result).toBe('12 Mei 1986 (40 th)');
    });

    it('returns dash for empty input', () => {
      expect(formatDateWithAge('')).toBe('—');
      expect(formatDateWithAge(null)).toBe('—');
    });
  });

  describe('maskNik', () => {
    it('masks 16-digit NIK correctly (first 4 digits, 8 asterisks, last 4 digits)', () => {
      const masked = maskNik('3471011205860001');
      expect(masked).toBe('3471********0001');
    });

    it('handles short or empty values gracefully', () => {
      expect(maskNik('')).toBe('—');
      expect(maskNik(null)).toBe('—');
      expect(maskNik('1234')).toBe('1234');
    });
  });

  describe('formatGender', () => {
    it('formats L to Laki-laki and P to Perempuan', () => {
      expect(formatGender('L')).toBe('Laki-laki');
      expect(formatGender('P')).toBe('Perempuan');
      expect(formatGender('laki-laki')).toBe('Laki-laki');
      expect(formatGender('perempuan')).toBe('Perempuan');
    });

    it('returns fallback for unknown gender', () => {
      expect(formatGender('')).toBe('—');
      expect(formatGender(null)).toBe('—');
    });
  });
});
