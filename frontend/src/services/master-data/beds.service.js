import axios from '@/utils/axios'

class BedsService {
  async getBeds(params) {
    const response = await axios.get('/master-data/beds', { params })
    return response.data
  }

  async getBed(id) {
    const response = await axios.get(`/master-data/beds/${id}`)
    return response.data
  }

  async createBed(data) {
    const response = await axios.post('/master-data/beds', data)
    return response.data
  }

  async updateBed(id, data) {
    const response = await axios.put(`/master-data/beds/${id}`, data)
    return response.data
  }

  async deleteBed(id) {
    const response = await axios.delete(`/master-data/beds/${id}`)
    return response.data
  }
}

export const bedsService = new BedsService()
