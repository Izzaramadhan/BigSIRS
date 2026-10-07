import axios from '@/utils/axios'

export const laboratoryItemsService = {
  async list(params = {}) {
    const response = await axios.get('/master-data/laboratory-items', { params })
    return response.data
  },

  async detail(id) {
    const response = await axios.get(`/master-data/laboratory-items/${id}`)
    return response.data
  },

  async create(payload) {
    const response = await axios.post('/master-data/laboratory-items', payload)
    return response.data
  },

  async update(id, payload) {
    const response = await axios.put(`/master-data/laboratory-items/${id}`, payload)
    return response.data
  },

  async remove(id) {
    const response = await axios.delete(`/master-data/laboratory-items/${id}`)
    return response.data
  },
}
