import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useCrudResource } from '../useCrudResource'

const page = (data, meta = {}) => ({
  data,
  meta: {
    current_page: 1,
    per_page: 10,
    total: data.length,
    last_page: 1,
    from: 1,
    to: data.length,
    ...meta,
  },
})

const validationError = {
  response: {
    status: 422,
    data: { message: 'Data tidak valid.', errors: { name: ['Wajib diisi.'] } },
  },
}

describe('useCrudResource', () => {
  let service

  beforeEach(() => {
    service = {
      list: vi.fn().mockResolvedValue(page([{ id: 1, name: 'Bed 1' }])),
      create: vi.fn().mockResolvedValue({ data: { id: 2 } }),
      update: vi.fn().mockResolvedValue({ data: { id: 1 } }),
      remove: vi.fn().mockResolvedValue({}),
    }
  })

  it('memuat daftar dan memetakan meta paginasi Laravel', async () => {
    service.list.mockResolvedValue(page([{ id: 1 }], { current_page: 2, last_page: 3, total: 21 }))
    const crud = useCrudResource(service)

    await crud.fetchItems()

    expect(crud.items.value).toEqual([{ id: 1 }])
    expect(crud.meta).toMatchObject({ page: 2, lastPage: 3, total: 21 })
    expect(crud.loading.value).toBe(false)
  })

  it('tidak mengirim filter kosong ke API', async () => {
    const crud = useCrudResource(service, { filters: { room_id: null } })

    await crud.fetchItems()

    expect(service.list).toHaveBeenCalledWith({
      page: 1,
      per_page: 10,
      sort: 'created_at',
      direction: 'desc',
    })
  })

  it('setFilters kembali ke halaman 1', async () => {
    const crud = useCrudResource(service)
    crud.meta.page = 3

    await crud.setFilters({ search: 'melati' })

    expect(service.list).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 1, search: 'melati' }),
    )
  })

  it('mengisi loadError saat daftar gagal dimuat', async () => {
    service.list.mockRejectedValue({ response: { status: 500, data: {} } })
    const crud = useCrudResource(service, { label: 'bed' })

    await crud.fetchItems()

    expect(crud.loadError.value).toBe('Data bed gagal dimuat.')
  })

  // Regresi: versi lama menulis error simpan ke state halaman, sehingga
  // tabel diganti error state ketika validasi form gagal.
  it('gagal simpan TIDAK mengubah loadError dan mengembalikan error per field', async () => {
    service.create.mockRejectedValue(validationError)
    const crud = useCrudResource(service)

    const result = await crud.create({ name: '' })

    expect(result.ok).toBe(false)
    expect(result.error.fieldErrors).toEqual({ name: 'Wajib diisi.' })
    expect(crud.loadError.value).toBe('')
    expect(crud.saving.value).toBe(false)
  })

  it('memuat ulang daftar setelah mutasi berhasil', async () => {
    const crud = useCrudResource(service)

    const result = await crud.update(1, { name: 'Bed 1A' })

    expect(result.ok).toBe(true)
    expect(service.update).toHaveBeenCalledWith(1, { name: 'Bed 1A' })
    expect(service.list).toHaveBeenCalledTimes(1)
  })

  it('mundur satu halaman saat menghapus item terakhir di halaman', async () => {
    service.list.mockResolvedValueOnce(page([{ id: 9 }], { current_page: 2, last_page: 2 }))
    const crud = useCrudResource(service)
    await crud.fetchItems()

    await crud.remove(9)

    expect(service.list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 1 }))
  })

  it('mengabaikan respons lama yang datang terlambat (race condition pencarian)', async () => {
    let resolveSlow
    service.list
      .mockReturnValueOnce(new Promise((resolve) => (resolveSlow = resolve)))
      .mockResolvedValueOnce(page([{ id: 2, name: 'hasil baru' }]))
    const crud = useCrudResource(service)

    const slow = crud.setFilters({ search: 'a' })
    await crud.setFilters({ search: 'ab' })
    resolveSlow(page([{ id: 1, name: 'hasil lama' }]))
    await slow

    expect(crud.items.value).toEqual([{ id: 2, name: 'hasil baru' }])
  })
})
