import api from '@/utils/axios'

export const guarantorsService = {
  getGuarantors(params) {
    return api.get('/master-data/guarantors', { params })
      .then(response => response.data)
  },

  getGuarantor(id) {
    return api.get(`/master-data/guarantors/${id}`)
      .then(response => response.data)
  },

  createGuarantor(data) {
    return api.post('/master-data/guarantors', data)
      .then(response => response.data)
  },

  updateGuarantor(id, data) {
    return api.put(`/master-data/guarantors/${id}`, data)
      .then(response => response.data)
  },

  updateStatus(id, isActive) {
    return api.patch(`/master-data/guarantors/${id}/status`, { is_active: isActive })
      .then(response => response.data)
  },

  deleteGuarantor(id) {
    return api.delete(`/master-data/guarantors/${id}`)
      .then(response => response.data)
  }
}
