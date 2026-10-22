import axios from '@/utils/axios'

class WardsService {
  async getWards(params) {
    const response = await axios.get('/master-data/wards', { params })
    return response.data
  }

  async getWard(id) {
    const response = await axios.get(`/master-data/wards/${id}`)
    return response.data
  }

  async createWard(data) {
    const response = await axios.post('/master-data/wards', data)
    return response.data
  }

  async updateWard(id, data) {
    const response = await axios.put(`/master-data/wards/${id}`, data)
    return response.data
  }

  async deleteWard(id) {
    const response = await axios.delete(`/master-data/wards/${id}`)
    return response.data
  }
}

export const wardsService = new WardsService()
