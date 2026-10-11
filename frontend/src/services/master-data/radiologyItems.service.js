import axios from '@/utils/axios'

const API_PATH = '/master-data/radiology-items'

export const radiologyItemsService = {
  async getItems(params = {}) {
    const { data } = await axios.get(API_PATH, { params })
    return data
  },

  async getItem(id) {
    const { data } = await axios.get(`${API_PATH}/${id}`)
    return data
  },

  async createItem(payload) {
    const { data } = await axios.post(API_PATH, payload)
    return data
  },

  async updateItem(id, payload) {
    const { data } = await axios.put(`${API_PATH}/${id}`, payload)
    return data
  },

  async updateStatus(id, isActive) {
    const { data } = await axios.patch(`${API_PATH}/${id}/status`, { is_active: isActive })
    return data
  },

  async deleteItem(id) {
    const { data } = await axios.delete(`${API_PATH}/${id}`)
    return data
  },

  // Aliases for unified service pattern
  getAll(params = {}) {
    return this.getItems(params)
  },
  getById(id) {
    return this.getItem(id)
  },
  create(payload) {
    return this.createItem(payload)
  },
  update(id, payload) {
    return this.updateItem(id, payload)
  },
  delete(id) {
    return this.deleteItem(id)
  }
}
