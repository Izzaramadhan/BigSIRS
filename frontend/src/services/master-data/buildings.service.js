import axios from '@/utils/axios'

class BuildingsService {
  async getBuildings(params) {
    const response = await axios.get('/master-data/buildings', { params })
    return response.data
  }

  async getBuilding(id) {
    const response = await axios.get(`/master-data/buildings/${id}`)
    return response.data
  }

  async createBuilding(data) {
    const response = await axios.post('/master-data/buildings', data)
    return response.data
  }

  async updateBuilding(id, data) {
    const response = await axios.put(`/master-data/buildings/${id}`, data)
    return response.data
  }

  async deleteBuilding(id) {
    const response = await axios.delete(`/master-data/buildings/${id}`)
    return response.data
  }
}

export const buildingsService = new BuildingsService()
