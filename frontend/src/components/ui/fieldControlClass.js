/**
 * Kelas bersama untuk semua kontrol form (input, textarea, search).
 * Satu tempat untuk mengubah tinggi, radius, warna fokus, dan state error.
 *
 * @param {object} [options]
 * @param {boolean} [options.invalid]   tampilkan state error
 * @param {boolean} [options.withIcon]  sisakan ruang ikon di kiri (mis. kotak pencarian)
 */
export function fieldControlClass({ invalid = false, withIcon = false } = {}) {
  return [
    'w-full rounded-lg border bg-surface py-2.5 font-sans text-sm text-fg transition-colors',
    'placeholder:text-fg-muted focus:ring-3 focus:outline-none',
    'disabled:cursor-not-allowed disabled:bg-canvas disabled:opacity-70',
    // Padding kiri dipilih di sini, bukan ditimpa dari luar (px-3.5 + pl-10 = bentrok).
    withIcon ? 'pr-3.5 pl-10' : 'px-3.5',
    invalid
      ? 'border-danger focus:ring-danger/15'
      : 'border-line-strong focus:border-primary focus:ring-primary/15',
  ]
}
