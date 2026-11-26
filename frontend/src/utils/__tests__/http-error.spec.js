import { describe, expect, it } from 'vitest'
import { normalizeApiError } from '../http-error'

describe('normalizeApiError', () => {
  it('mengambil pesan & error per field dari respons validasi Laravel (422)', () => {
    const error = {
      response: {
        status: 422,
        data: {
          message: 'Data tidak valid.',
          errors: { name: ['Nama wajib diisi.', 'Nama terlalu pendek.'], room_id: 'Kamar wajib.' },
        },
      },
    }

    expect(normalizeApiError(error)).toEqual({
      status: 422,
      message: 'Data tidak valid.',
      fieldErrors: { name: 'Nama wajib diisi.', room_id: 'Kamar wajib.' },
      isValidation: true,
    })
  })

  it('memakai pesan fallback saat server tidak mengirim pesan', () => {
    const result = normalizeApiError({ response: { status: 500, data: {} } }, 'Gagal menyimpan.')

    expect(result.message).toBe('Gagal menyimpan.')
    expect(result.isValidation).toBe(false)
    expect(result.fieldErrors).toEqual({})
  })

  it('menangani error jaringan tanpa response', () => {
    const result = normalizeApiError(new Error('Network Error'))

    expect(result.status).toBeNull()
    expect(result.message).toBe('Terjadi kesalahan. Silakan coba lagi.')
  })
})
