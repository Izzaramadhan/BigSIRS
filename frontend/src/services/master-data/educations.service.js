import api from '@/utils/axios'

const BASE_URL = '/master-data/educations'

export const educationsService = {
  async getEducations(params = {}) {
    const response = await api.get(BASE_URL, { params })
    return response.data
  },

  async getEducation(id) {
    const response = await api.get(`${BASE_URL}/${id}`)
    return response.data
  },

  async createEducation(data) {
    const response = await api.post(BASE_URL, data)
    return response.data
  },

  async updateEducation(id, data) {
    const response = await api.put(`${BASE_URL}/${id}`, data)
    return response.data
  },

  async deleteEducation(id) {
    const response = await api.delete(`${BASE_URL}/${id}`)
    return response.data
  },

  async updateStatus(id, isActive) {
    const response = await api.patch(`${BASE_URL}/${id}/status`, { is_active: isActive })
    return response.data
  }
}
