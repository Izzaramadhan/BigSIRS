import { beforeEach, describe, expect, it, vi } from 'vitest'
import http from '@/utils/axios'
import letterTypeService, { adaptLetterTypeListParams } from '../letterType.service'

vi.mock('@/utils/axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}))

describe('letterType.service', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('maps sort/direction to sort_by/sort_dir in adaptLetterTypeListParams and list()', async () => {
    expect(
      adaptLetterTypeListParams({
        page: 2,
        per_page: 10,
        search: 'Sehat',
        sort: 'name',
        direction: 'desc',
      }),
    ).toEqual({
      page: 2,
      per_page: 10,
      search: 'Sehat',
      sort_by: 'name',
      sort_dir: 'desc',
    })

    http.get.mockResolvedValueOnce({ data: { data: [], meta: {} } })
    await letterTypeService.list({ page: 1, per_page: 10, sort: 'name', direction: 'asc' })

    expect(http.get).toHaveBeenCalledWith('/master-data/letter-types', {
      params: {
        page: 1,
        per_page: 10,
        sort_by: 'name',
        sort_dir: 'asc',
      },
    })
  })

  it('preserves legacy method aliases and updateStatus endpoint contract', async () => {
    http.get.mockResolvedValue({ data: { data: [] } })
    http.post.mockResolvedValueOnce({ data: { data: { id: 1 } } })
    http.put.mockResolvedValueOnce({ data: { data: { id: 1 } } })
    http.patch.mockResolvedValueOnce({ data: { data: { id: 1, is_active: false } } })
    http.delete.mockResolvedValueOnce({ data: { message: 'Deleted' } })

    await letterTypeService.getLetterTypes({ sort: 'name', direction: 'asc' })
    expect(http.get).toHaveBeenNthCalledWith(1, '/master-data/letter-types', {
      params: { sort_by: 'name', sort_dir: 'asc' },
    })

    await letterTypeService.getLetterType(5)
    expect(http.get).toHaveBeenNthCalledWith(2, '/master-data/letter-types/5')

    await letterTypeService.createLetterType({ name: 'Surat Baru' })
    expect(http.post).toHaveBeenCalledWith('/master-data/letter-types', { name: 'Surat Baru' })

    await letterTypeService.updateLetterType(5, { name: 'Surat Revisi' })
    expect(http.put).toHaveBeenCalledWith('/master-data/letter-types/5', { name: 'Surat Revisi' })

    await letterTypeService.updateStatus(5, false)
    expect(http.patch).toHaveBeenCalledWith('/master-data/letter-types/5/status', {
      is_active: false,
    })

    await letterTypeService.deleteLetterType(5)
    expect(http.delete).toHaveBeenCalledWith('/master-data/letter-types/5')
  })
})
