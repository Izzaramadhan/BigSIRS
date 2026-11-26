import { beforeEach, describe, expect, it, vi } from 'vitest'
import http from '@/utils/axios'
import serviceTypeService from '@/services/serviceType.service'

vi.mock('@/utils/axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}))

describe('serviceType.service', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('maps direction param to backend order param on list and getAll', async () => {
    http.get.mockResolvedValue({ data: { data: [], meta: {} } })

    await serviceTypeService.list({ page: 1, per_page: 10, sort: 'name', direction: 'desc' })
    expect(http.get).toHaveBeenCalledWith('/master-data/service-types', {
      params: { page: 1, per_page: 10, sort: 'name', order: 'desc' },
    })

    await serviceTypeService.getAll({ sort: 'id', direction: 'asc' })
    expect(http.get).toHaveBeenLastCalledWith('/master-data/service-types', {
      params: { sort: 'id', order: 'asc' },
    })
  })

  it('preserves CRUD and status update endpoints', async () => {
    http.post.mockResolvedValue({ data: { data: { id: 1 } } })
    http.put.mockResolvedValue({ data: { data: { id: 1 } } })
    http.patch.mockResolvedValue({ data: { data: { id: 1, is_active: false } } })
    http.delete.mockResolvedValue({ data: null })

    await serviceTypeService.create({ name: 'IGD' })
    await serviceTypeService.update(1, { name: 'IGD 24 Jam' })
    await serviceTypeService.updateStatus(1, false)
    await serviceTypeService.delete(1)

    expect(http.post).toHaveBeenCalledWith('/master-data/service-types', { name: 'IGD' })
    expect(http.put).toHaveBeenCalledWith('/master-data/service-types/1', { name: 'IGD 24 Jam' })
    expect(http.patch).toHaveBeenCalledWith('/master-data/service-types/1/status', {
      is_active: false,
    })
    expect(http.delete).toHaveBeenCalledWith('/master-data/service-types/1')
  })
})
