import axios from '@/utils/axios'

class LookupService {
  async getEmployees(params = {}) {
    const response = await axios.get('/lookups/employees', { params })
    return response.data
  }
}

export default new LookupService()
