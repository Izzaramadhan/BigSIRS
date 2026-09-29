import axios from '@/utils/axios'

class ProcedureUserMappingService {
  async getMappings(params = {}) {
    const response = await axios.get('/master-data/procedure-user-mappings', { params })
    return response.data
  }

  async getMapping(id) {
    const response = await axios.get(`/master-data/procedure-user-mappings/${id}`)
    return response.data.data
  }

  async createMapping(data) {
    const response = await axios.post('/master-data/procedure-user-mappings', data)
    return response.data
  }

  async updateMapping(id, data) {
    const response = await axios.put(`/master-data/procedure-user-mappings/${id}`, data)
    return response.data
  }

  async deleteMapping(id) {
    const response = await axios.delete(`/master-data/procedure-user-mappings/${id}`)
    return response.data
  }
}

export default new ProcedureUserMappingService()
