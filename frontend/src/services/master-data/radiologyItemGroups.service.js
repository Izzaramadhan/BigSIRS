import axios from '@/utils/axios'

const API_PATH = '/master-data/radiology-item-groups'

export const radiologyItemGroupsService = {
  async getGroups(params) {
    const { data } = await axios.get(API_PATH, { params })
    return data
  },

  async getGroup(id) {
    const { data } = await axios.get(`${API_PATH}/${id}`)
    return data
  },

  async createGroup(payload) {
    const { data } = await axios.post(API_PATH, payload)
    return data
  },

  async updateGroup(id, payload) {
    const { data } = await axios.put(`${API_PATH}/${id}`, payload)
    return data
  },

  async updateStatus(id, isActive) {
    const { data } = await axios.patch(`${API_PATH}/${id}/status`, { is_active: isActive })
    return data
  },

  async deleteGroup(id) {
    const { data } = await axios.delete(`${API_PATH}/${id}`)
    return data
  },
}
