const DEFAULT_MESSAGE = 'Terjadi kesalahan. Silakan coba lagi.'

/**
 * Ubah error axios menjadi bentuk yang seragam untuk UI.
 * Menggantikan blok `if (status === 422) ... else ...` yang tersalin di 35 view.
 *
 * @param {unknown} error
 * @param {string} [fallbackMessage]
 * @returns {{ status: number|null, message: string, fieldErrors: Record<string, string>, isValidation: boolean }}
 */
export function normalizeApiError(error, fallbackMessage = DEFAULT_MESSAGE) {
  const response = error?.response
  const rawFieldErrors = response?.data?.errors ?? {}

  // Laravel mengirim { field: ['pesan 1', 'pesan 2'] } — UI cukup pesan pertama.
  const fieldErrors = Object.fromEntries(
    Object.entries(rawFieldErrors).map(([field, messages]) => [
      field,
      Array.isArray(messages) ? messages[0] : String(messages),
    ]),
  )

  return {
    status: response?.status ?? null,
    message: response?.data?.message || fallbackMessage,
    fieldErrors,
    isValidation: response?.status === 422,
  }
}
