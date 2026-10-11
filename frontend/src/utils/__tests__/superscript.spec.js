import { describe, it, expect } from 'vitest';
import {
  convertToSuperscript,
  hasSuperscriptNotation,
  containsSuperscript,
  SUPERSCRIPT_MAP
} from '../superscript';

describe('superscript utility', () => {
  describe('convertToSuperscript', () => {
    it('converts single digit exponents: 10^2 -> 10²', () => {
      expect(convertToSuperscript('10^2')).toBe('10²');
      expect(convertToSuperscript('10^3')).toBe('10³');
      expect(convertToSuperscript('10^6')).toBe('10⁶');
    });

    it('converts multi-digit exponents: 10^12 -> 10¹²', () => {
      expect(convertToSuperscript('10^12')).toBe('10¹²');
      expect(convertToSuperscript('10^12/L')).toBe('10¹²/L');
    });

    it('converts negative exponents: 10^-3 -> 10⁻³', () => {
      expect(convertToSuperscript('10^-3')).toBe('10⁻³');
    });

    it('converts positive exponents: 10^+2 -> 10⁺²', () => {
      expect(convertToSuperscript('10^+2')).toBe('10⁺²');
    });

    it('converts unit notations: mm^3 -> mm³ and /mm^3 -> /mm³', () => {
      expect(convertToSuperscript('mm^3')).toBe('mm³');
      expect(convertToSuperscript('/mm^3')).toBe('/mm³');
      expect(convertToSuperscript('ribu/mm^3')).toBe('ribu/mm³');
    });

    it('converts complex unit strings without altering subsequent text: x10^3/uL -> x10³/uL', () => {
      expect(convertToSuperscript('x10^3/uL')).toBe('x10³/uL');
      expect(convertToSuperscript('5 x 10^6')).toBe('5 x 10⁶');
      expect(convertToSuperscript('10^6/uL')).toBe('10⁶/uL');
    });

    it('converts parenthesized exponents: 10^(-3) -> 10⁽⁻³⁾', () => {
      expect(convertToSuperscript('10^(-3)')).toBe('10⁽⁻³⁾');
    });

    it('converts multiple power expressions in the same string', () => {
      expect(convertToSuperscript('10^3 - 10^6 /uL')).toBe('10³ - 10⁶ /uL');
    });

    it('keeps normal numbers without carets untouched: 100 mg -> 100 mg, 10.5 -> 10.5', () => {
      expect(convertToSuperscript('100 mg')).toBe('100 mg');
      expect(convertToSuperscript('10.5')).toBe('10.5');
      expect(convertToSuperscript('mg/dL')).toBe('mg/dL');
      expect(convertToSuperscript('L: 13-17, P: 12-15')).toBe('L: 13-17, P: 12-15');
    });

    it('is idempotent: running conversion twice produces identical result', () => {
      const input = 'x10^3/uL';
      const firstRun = convertToSuperscript(input);
      const secondRun = convertToSuperscript(firstRun);
      expect(firstRun).toBe('x10³/uL');
      expect(secondRun).toBe('x10³/uL');
      expect(secondRun).toBe(firstRun);
    });

    it('does not alter already converted superscript characters', () => {
      expect(convertToSuperscript('10³')).toBe('10³');
      expect(convertToSuperscript('mm³')).toBe('mm³');
      expect(convertToSuperscript('10⁻³')).toBe('10⁻³');
    });

    it('handles empty string and non-string inputs safely', () => {
      expect(convertToSuperscript('')).toBe('');
      expect(convertToSuperscript(null)).toBe('');
      expect(convertToSuperscript(undefined)).toBe('');
      expect(convertToSuperscript(123)).toBe('123');
    });

    it('supports all mapped characters in SUPERSCRIPT_MAP', () => {
      const allChars = '0123456789+-()';
      const expected = '⁰¹²³⁴⁵⁶⁷⁸⁹⁺⁻⁽⁾';
      expect(convertToSuperscript(`^${allChars}`)).toBe(expected);
    });
  });

  describe('hasSuperscriptNotation', () => {
    it('returns true when text contains ^ followed by valid exponent chars', () => {
      expect(hasSuperscriptNotation('10^2')).toBe(true);
      expect(hasSuperscriptNotation('x10^3/uL')).toBe(true);
      expect(hasSuperscriptNotation('mm^3')).toBe(true);
      expect(hasSuperscriptNotation('10^-3')).toBe(true);
    });

    it('returns false when text contains no caret or is empty or invalid', () => {
      expect(hasSuperscriptNotation('100 mg')).toBe(false);
      expect(hasSuperscriptNotation('10²')).toBe(false);
      expect(hasSuperscriptNotation('^')).toBe(false);
      expect(hasSuperscriptNotation('^abc')).toBe(false);
      expect(hasSuperscriptNotation('')).toBe(false);
      expect(hasSuperscriptNotation(null)).toBe(false);
      expect(hasSuperscriptNotation(undefined)).toBe(false);
    });
  });

  describe('containsSuperscript', () => {
    it('returns true when text contains any superscript characters', () => {
      expect(containsSuperscript('10²')).toBe(true);
      expect(containsSuperscript('x10³/uL')).toBe(true);
      expect(containsSuperscript('mm³')).toBe(true);
      expect(containsSuperscript('10⁻³')).toBe(true);
    });

    it('returns false when text contains no superscript characters', () => {
      expect(containsSuperscript('10^2')).toBe(false);
      expect(containsSuperscript('100 mg')).toBe(false);
      expect(containsSuperscript('')).toBe(false);
      expect(containsSuperscript(null)).toBe(false);
    });
  });
});
