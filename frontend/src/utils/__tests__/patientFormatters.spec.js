import { describe, it, expect } from 'vitest';
import { 
  calculateAge, 
  formatDateIndo, 
  formatDateWithAge, 
  maskNik, 
  formatGender 
} from '../patientFormatters';

describe('patientFormatters utility', () => {
  describe('calculateAge', () => {
    it('calculates age correctly for a given birth date', () => {
      // 1986-05-12 compared to today
      const age = calculateAge('1986-05-12');
      expect(age).toBeGreaterThanOrEqual(37);
      expect(typeof age).toBe('number');
    });

    it('returns 0 for empty or invalid date', () => {
      expect(calculateAge('')).toBe(0);
      expect(calculateAge(null)).toBe(0);
      expect(calculateAge('invalid-date')).toBe(0);
    });
  });

  describe('formatDateIndo', () => {
    it('formats date into Indonesian string', () => {
      const formatted = formatDateIndo('1986-05-12');
      expect(formatted).toBe('12 Mei 1986');
    });

    it('returns dash for empty string', () => {
      expect(formatDateIndo('')).toBe('—');
      expect(formatDateIndo(null)).toBe('—');
    });
  });

  describe('formatDateWithAge', () => {
    it('formats date and appends age in parentheses', () => {
      const result = formatDateWithAge('1986-05-12');
      expect(result).toMatch(/^12 Mei 1986 \(\d+ th\)$/);
    });

    it('returns dash for empty input', () => {
      expect(formatDateWithAge('')).toBe('—');
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
