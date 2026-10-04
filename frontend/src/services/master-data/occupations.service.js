import axios from '@/utils/axios'

export const occupationsService = {
  getOccupations(params = {}) {
    return axios.get('/master-data/occupations', { params }).then(res => res.data)
  },

  getOccupation(id) {
    return axios.get(`/master-data/occupations/${id}`).then(res => res.data)
  },

  createOccupation(data) {
    return axios.post('/master-data/occupations', data).then(res => res.data)
  },

  updateOccupation(id, data) {
    return axios.put(`/master-data/occupations/${id}`, data).then(res => res.data)
  },

  deleteOccupation(id) {
    return axios.delete(`/master-data/occupations/${id}`).then(res => res.data)
  },

  updateStatus(id, is_active) {
    return axios.patch(`/master-data/occupations/${id}/status`, { is_active }).then(res => res.data)
  }
}
