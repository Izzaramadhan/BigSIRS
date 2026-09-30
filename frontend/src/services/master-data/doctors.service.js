import axios from '@/utils/axios'

class DoctorService {
  async getDoctors(params) {
    const response = await axios.get('/master-data/doctors', { params })
    return response.data
  }

  async createDoctor(data) {
    const response = await axios.post('/master-data/doctors', data, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  }

  async getDoctor(id) {
    const response = await axios.get(`/master-data/doctors/${id}`)
    return response.data
  }

  async updateDoctor(id, data) {
    // We use POST with _method=PUT to support file uploads in PHP/Laravel
    data.append('_method', 'PUT')
    const response = await axios.post(`/master-data/doctors/${id}`, data, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  }

  async updateStatus(id, isActive) {
    const response = await axios.patch(`/master-data/doctors/${id}/status`, { is_active: isActive })
    return response.data
  }

  async deleteDoctor(id) {
    const response = await axios.delete(`/master-data/doctors/${id}`)
    return response.data
  }
}

export default new DoctorService()
