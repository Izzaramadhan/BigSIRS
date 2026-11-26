import api from '@/utils/axios'

const basePath = '/master-data/doctor-schedules'

function normalizeListResponse(payload) {
  if (!payload || typeof payload !== 'object') {
    return {
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 },
    }
  }

  return {
    data: Array.isArray(payload.data) ? payload.data : [],
    meta: payload.meta ?? {
      current_page: payload.current_page ?? 1,
      last_page: payload.last_page ?? 1,
      per_page: payload.per_page ?? 10,
      total: payload.total ?? (Array.isArray(payload.data) ? payload.data.length : 0),
      from: payload.from ?? 0,
      to: payload.to ?? 0,
    },
  }
}

function toScheduleQueryParams(params = {}) {
  const { sort, direction, ...rest } = params
  const query = { ...rest }

  if (sort !== undefined && query.sort_by === undefined) {
    query.sort_by = sort
  }
  if (direction !== undefined && query.sort_dir === undefined) {
    query.sort_dir = direction
  }

  return query
}

class DoctorScheduleService {
  async list(params = {}) {
    const response = await api.get(basePath, { params: toScheduleQueryParams(params) })
    return normalizeListResponse(response.data)
  }

  async getRecap() {
    const response = await api.get(`${basePath}/recap`)
    return response.data
  }

  async get(id) {
    const response = await api.get(`${basePath}/${id}`)
    return response.data?.data ?? response.data
  }

  async create(data) {
    const response = await api.post(basePath, data)
    return response.data?.data ?? response.data
  }

  async update(id, data) {
    const response = await api.put(`${basePath}/${id}`, data)
    return response.data?.data ?? response.data
  }

  async remove(id) {
    const response = await api.delete(`${basePath}/${id}`)
    return response.data
  }

  // Alias kompatibilitas untuk caller atau test lama.
  getDoctorSchedules(params = {}) {
    return api.get(basePath, { params: toScheduleQueryParams(params) }).then((res) => res.data)
  }

  getDoctorSchedule(id) {
    return api.get(`${basePath}/${id}`).then((res) => res.data)
  }

  createDoctorSchedule(data) {
    return api.post(basePath, data).then((res) => res.data)
  }

  updateDoctorSchedule(id, data) {
    return api.put(`${basePath}/${id}`, data).then((res) => res.data)
  }

  deleteDoctorSchedule(id) {
    return api.delete(`${basePath}/${id}`).then((res) => res.data)
  }
}

export const doctorScheduleService = new DoctorScheduleService()

export default doctorScheduleService
