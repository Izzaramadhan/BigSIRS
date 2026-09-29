import axios from '@/utils/axios'

class Icd9CmService {
  async getList(params) {
    const response = await axios.get('/master-data/icd9-cms', { params })
    return response.data
  }

  async getDetail(id) {
    const response = await axios.get(`/master-data/icd9-cms/${id}`)
    return response.data
  }

  async create(data) {
    const response = await axios.post('/master-data/icd9-cms', data)
    return response.data
  }

  async update(id, data) {
    const response = await axios.put(`/master-data/icd9-cms/${id}`, data)
    return response.data
  }

  async updateStatus(id, isActive) {
    const response = await axios.patch(`/master-data/icd9-cms/${id}/status`, { is_active: isActive })
    return response.data
  }

  async delete(id) {
    const response = await axios.delete(`/master-data/icd9-cms/${id}`)
    return response.data
  }
  
  async lookup(params) {
    const response = await axios.get('/lookups/icd9-cms', { params })
    return response.data
  }
}

export default new Icd9CmService()
