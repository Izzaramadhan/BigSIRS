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
  async getDoctors(params = {}) {
    const response = await axios.get('/lookups/doctors', { params })
    return response.data
  }

  async getSpecializations(params = {}) {
    const response = await axios.get('/lookups/specializations', { params })
    return extractCollection(response)
  }
  
  async getProvinces(params = {}) {
    const response = await axios.get('/lookups/provinces', { params })
    return extractCollection(response)
  }

  async getRegencies(params = {}) {
    const response = await axios.get('/lookups/regencies', { params })
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

  async getWarehouses(params = {}) {
    const response = await axios.get('/lookups/warehouses', { params })
    return extractCollection(response)
  }

  async getMedicineUnits(params = {}) {
    const response = await axios.get('/lookups/medicine-units', { params })
    return extractCollection(response)
  }

  async getMedicineCategories(params = {}) {
    const response = await axios.get('/lookups/medicine-categories', { params })
    return extractCollection(response)
  }

  async getMedicineClassifications(params = {}) {
    const response = await axios.get('/lookups/medicine-classifications', { params })
    return extractCollection(response)
  }

  async getMedicineRoutes(params = {}) {
    const response = await axios.get('/lookups/medicine-routes', { params })
    return extractCollection(response)
  }

  async getGenericMedicines(params = {}) {
    const response = await axios.get('/lookups/generic-medicines', { params })
    return extractCollection(response)
  }

  async createGenericMedicine(data) {
    const response = await axios.post('/master-data/generic-medicines', data)
    return response.data?.data ?? response.data
  }
}

export default new LookupService()
