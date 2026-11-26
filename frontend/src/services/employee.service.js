import axios from '@/utils/axios'

function normalizePaginatorResponse(payload) {
  if (!payload || typeof payload !== 'object') {
    return { data: [], meta: { current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 } }
  }

  if (payload.meta) {
    return {
      data: Array.isArray(payload.data) ? payload.data : [],
      meta: payload.meta,
    }
  }

  // Endpoint `/master-data/employees` mengembalikan paginator Laravel mentah tanpa wrapper `meta`.
  return {
    data: Array.isArray(payload.data) ? payload.data : [],
    meta: {
      current_page: payload.current_page ?? 1,
      last_page: payload.last_page ?? 1,
      per_page: payload.per_page ?? 15,
      total: payload.total ?? 0,
      from: payload.from ?? 0,
      to: payload.to ?? 0,
    },
  }
}

function toEmployeeQueryParams(params = {}) {
  const { sort, direction, ...rest } = params
  const query = { ...rest }

  if (sort !== undefined && query.sort_by === undefined) {
    query.sort_by = sort
  }
  if (direction !== undefined && query.sort_desc === undefined) {
    query.sort_desc = direction === 'desc' ? 1 : 0
  }

  return query
}

function extractOptionsCollection(payload) {
  const inner = payload?.data ?? payload ?? []
  if (Array.isArray(inner)) return inner
  return Array.isArray(inner?.data) ? inner.data : []
}

class EmployeeService {
  async list(params = {}) {
    const response = await axios.get('/master-data/employees', {
      params: toEmployeeQueryParams(params),
    })
    return normalizePaginatorResponse(response.data)
  }

  async get(id) {
    const response = await axios.get(`/master-data/employees/${id}`)
    return response.data?.data ?? response.data
  }

  buildFormData(data, method = null) {
    const formData = new FormData()
    if (method) {
      formData.append('_method', method)
    }

    for (const [key, value] of Object.entries(data)) {
      if (key === 'signature') {
        if (value instanceof File || value instanceof Blob) {
          formData.append('signature', value)
        }
        continue
      }

      if (key === 'signature_preview' || key === 'signature_url') {
        continue
      }

      if (key === 'remove_signature') {
        if (value) formData.append('remove_signature', '1')
        continue
      }

      if (value === null || value === undefined) {
        formData.append(key, '')
      } else if (typeof value === 'boolean') {
        formData.append(key, value ? '1' : '0')
      } else {
        formData.append(key, value)
      }
    }

    return formData
  }

  async create(data) {
    const formData = this.buildFormData(data)
    const response = await axios.post('/master-data/employees', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data?.data ?? response.data
  }

  async update(id, data) {
    const formData = this.buildFormData(data, 'PUT')
    const response = await axios.post(`/master-data/employees/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data?.data ?? response.data
  }

  async remove(id) {
    const response = await axios.delete(`/master-data/employees/${id}`)
    return response.data
  }

  // Alias kompatibilitas untuk pemanggil lama.
  getEmployees(params = {}) {
    return this.list(params)
  }

  getEmployee(id) {
    return this.get(id)
  }

  createEmployee(data) {
    return this.create(data)
  }

  updateEmployee(id, data) {
    return this.update(id, data)
  }

  deleteEmployee(id) {
    return this.remove(id)
  }

  async updateStatus(id, isActive) {
    const response = await axios.patch(`/master-data/employees/${id}/status`, {
      is_active: isActive,
    })
    return response.data
  }

  async getPositions(params = {}) {
    const response = await axios.get('/lookups/positions', { params })
    return extractOptionsCollection(response.data)
  }
}

export const employeeService = new EmployeeService()

export default employeeService
