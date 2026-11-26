import axios from '@/utils/axios'

function normalizePaginatorResponse(payload) {
  if (!payload || typeof payload !== 'object') {
    return {
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 },
    }
  }

  if (payload.meta) {
    return {
      data: Array.isArray(payload.data) ? payload.data : [],
      meta: payload.meta,
    }
  }

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

function toDoctorQueryParams(params = {}) {
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

class DoctorService {
  async list(params = {}) {
    const response = await axios.get('/master-data/doctors', {
      params: toDoctorQueryParams(params),
    })
    return normalizePaginatorResponse(response.data)
  }

  async get(id) {
    const response = await axios.get(`/master-data/doctors/${id}`)
    return response.data?.data ?? response.data
  }

  buildFormData(data, method = null) {
    const formData = new FormData()
    if (method) {
      formData.append('_method', method)
    }

    if (data.employee_id !== null && data.employee_id !== undefined && data.employee_id !== '') {
      formData.append('employee_id', data.employee_id)
    }
    if (data.signature instanceof File || data.signature instanceof Blob) {
      formData.append('signature', data.signature)
    }
    if (data.remove_signature) {
      formData.append('remove_signature', '1')
    }

    if (data.person && typeof data.person === 'object') {
      for (const [key, value] of Object.entries(data.person)) {
        if (value === undefined) continue
        // Kirim string kosong eksplisit saat null agar middleware ConvertEmptyStringsToNull mengosongkan nilai DB.
        formData.append(`person[${key}]`, value === null ? '' : value)
      }
    }

    if (data.professional && typeof data.professional === 'object') {
      for (const [key, value] of Object.entries(data.professional)) {
        if (value === undefined) continue
        if (value === null) {
          formData.append(`professional[${key}]`, '')
        } else {
          formData.append(
            `professional[${key}]`,
            value === true ? '1' : value === false ? '0' : value,
          )
        }
      }
    }

    return formData
  }

  async create(data) {
    const formData = this.buildFormData(data)
    const response = await axios.post('/master-data/doctors', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data?.data ?? response.data
  }

  async update(id, data) {
    const formData = this.buildFormData(data, 'PUT')
    const response = await axios.post(`/master-data/doctors/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data?.data ?? response.data
  }

  async remove(id) {
    const response = await axios.delete(`/master-data/doctors/${id}`)
    return response.data
  }

  // Alias kompatibilitas untuk caller atau test lama.
  getDoctors(params = {}) {
    return this.list(params)
  }

  getDoctor(id) {
    return this.get(id)
  }

  createDoctor(data) {
    return this.create(data)
  }

  updateDoctor(id, data) {
    return this.update(id, data)
  }

  deleteDoctor(id) {
    return this.remove(id)
  }

  async updateStatus(id, isActive) {
    const response = await axios.patch(`/master-data/doctors/${id}/status`, {
      is_active: isActive,
    })
    return response.data
  }
}

export const doctorService = new DoctorService()

export default doctorService
