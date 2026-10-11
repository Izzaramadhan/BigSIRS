/**
 * Map of standard characters to their Unicode superscript equivalents.
 */
export const SUPERSCRIPT_MAP = Object.freeze({
  '0': '⁰',
  '1': '¹',
  '2': '²',
  '3': '³',
  '4': '⁴',
  '5': '⁵',
  '6': '⁶',
  '7': '⁷',
  '8': '⁸',
  '9': '⁹',
  '+': '⁺',
  '-': '⁻',
  '(': '⁽',
  ')': '⁾'
});

/**
 * Checks whether a text contains power notation using the caret symbol '^'
 * followed by characters supported in superscript conversion.
 *
 * @param {unknown} text
 * @returns {boolean}
 */
export function hasSuperscriptNotation(text) {
  if (typeof text !== 'string') {
    return false;
  }

  return /\^[0-9+\-()]+/.test(text);
}

/**
 * Checks whether a text contains any Unicode superscript character.
 *
 * @param {unknown} text
 * @returns {boolean}
 */
export function containsSuperscript(text) {
  if (typeof text !== 'string') {
    return false;
  }

  return /[⁰¹²³⁴⁵⁶⁷⁸⁹⁺⁻⁽⁾]/.test(text);
}

/**
 * Converts caret power notations (e.g., 10^2, 10^-3, mm^3, x10^3/uL)
 * into Unicode superscript characters (10², 10⁻³, mm³, x10³/uL).
 *
 * Rules:
 * - Only characters directly following '^' are converted.
 * - Regular numbers not preceded by '^' (such as 100 mg, 10.5, /uL) remain untouched.
 * - Calling multiple times is idempotent (already converted superscripts are not changed).
 * - Safe against null, undefined, empty strings, and non-string inputs.
 *
 * @param {unknown} text
 * @returns {string}
 */
export function convertToSuperscript(text) {
  if (typeof text !== 'string') {
    return text === null || text === undefined ? '' : String(text);
  }

  if (!text) {
    return '';
  }

  return text.replace(/\^([0-9+\-()]+)/g, (_, chars) => {
    return chars
      .split('')
      .map((char) => SUPERSCRIPT_MAP[char] || char)
      .join('');
  });
}
