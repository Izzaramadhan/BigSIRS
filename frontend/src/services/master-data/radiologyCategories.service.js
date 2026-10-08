import axios from '@/utils/axios'

const basePath = '/master-data/radiology-categories'

export const radiologyCategoriesService = {
  getAll: (params = {}) => {
    return axios.get(basePath, { params })
  },
  
  getById: (id) => {
    return axios.get(`${basePath}/${id}`)
  },

  create: (data) => {
    return axios.post(basePath, data)
  },

  update: (id, data) => {
    return axios.put(`${basePath}/${id}`, data)
  },

  updateStatus: (id, is_active) => {
    return axios.patch(`${basePath}/${id}/status`, { is_active })
  },

  delete: (id) => {
    return axios.delete(`${basePath}/${id}`)
  }
}
