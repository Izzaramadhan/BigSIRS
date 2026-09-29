import axios from '@/utils/axios'

class Icd10Service {
  async getIcd10Codes(params) {
    const response = await axios.get('/master-data/icd10', { params })
    return response.data
  }

  async createIcd10Code(data) {
    const response = await axios.post('/master-data/icd10', data)
    return response.data
  }

  async updateIcd10Code(id, data) {
    const response = await axios.put(`/master-data/icd10/${id}`, data)
    return response.data
  }

  async updateStatus(id, isActive) {
    const response = await axios.patch(`/master-data/icd10/${id}/status`, { is_active: isActive })
    return response.data
  }

  async deleteIcd10Code(id) {
    const response = await axios.delete(`/master-data/icd10/${id}`)
    return response.data
  }
}

export default new Icd10Service()
