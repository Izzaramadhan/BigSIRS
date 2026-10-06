import axios from '@/utils/axios'

const API_URL = '/master-data/service-types'

class ServiceTypeService {
  async getAll(params) {
    const response = await axios.get(API_URL, { params })
    return response.data
  }

  async getById(id) {
    const response = await axios.get(`${API_URL}/${id}`)
    return response.data
  }

  async create(data) {
    const response = await axios.post(API_URL, data)
    return response.data
  }

  async update(id, data) {
    const response = await axios.put(`${API_URL}/${id}`, data)
    return response.data
  }

  async updateStatus(id, isActive) {
    const response = await axios.patch(`${API_URL}/${id}/status`, {
      is_active: isActive
    })
    return response.data
  }

  async delete(id) {
    const response = await axios.delete(`${API_URL}/${id}`)
    return response.data
  }
}

export default new ServiceTypeService()
