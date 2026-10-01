import axios from '@/utils/axios'

function extractCollection(response) {
  const payload = response?.data?.data ?? response?.data ?? []
  return Array.isArray(payload) ? payload : payload.data ?? []
}

class LookupService {
  async getEmployees(params = {}) {
    const response = await axios.get('/lookups/employees', { params })
    return response.data // Keep this as is if they expect paginator for employees
  }
  
  async getSpecializations(params = {}) {
    const response = await axios.get('/lookups/specializations', { params })
    return extractCollection(response)
  }
  
  async getProvinces(params = {}) {
    const response = await axios.get('/lookups/provinces', { params })
    return extractCollection(response)
  }

  async getCities(params = {}) {
    const response = await axios.get('/lookups/cities', { params })
    return extractCollection(response)
  }

  async getDistricts(params = {}) {
    const response = await axios.get('/lookups/districts', { params })
    return extractCollection(response)
  }

  async getVillages(params = {}) {
    const response = await axios.get('/lookups/villages', { params })
    return extractCollection(response)
  }

  async getEducations(params = {}) {
    const response = await axios.get('/lookups/educations', { params })
    return extractCollection(response)
  }

  async getOccupations(params = {}) {
    const response = await axios.get('/lookups/occupations', { params })
    return extractCollection(response)
  }
}

export default new LookupService()
