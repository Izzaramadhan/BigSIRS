import axios from '@/utils/axios'

class FloorsService {
  async getFloors(params) {
    const response = await axios.get('/master-data/floors', { params })
    return response.data
  }

  async getFloor(id) {
    const response = await axios.get(`/master-data/floors/${id}`)
    return response.data
  }

  async createFloor(data) {
    const response = await axios.post('/master-data/floors', data)
    return response.data
  }

  async updateFloor(id, data) {
    const response = await axios.put(`/master-data/floors/${id}`, data)
    return response.data
  }

  async deleteFloor(id) {
    const response = await axios.delete(`/master-data/floors/${id}`)
    return response.data
  }
}

export const floorsService = new FloorsService()
