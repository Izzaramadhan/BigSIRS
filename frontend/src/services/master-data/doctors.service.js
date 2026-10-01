import axios from '@/utils/axios'

class DoctorService {
  async getDoctors(params) {
    const response = await axios.get('/master-data/doctors', { params })
    return response.data
  }

  buildFormData(data, method = null) {
    const formData = new FormData();
    if (method) {
      formData.append('_method', method);
    }
    
    if (data.employee_id) formData.append('employee_id', data.employee_id);
    if (data.signature) formData.append('signature', data.signature);
    if (data.remove_signature) formData.append('remove_signature', data.remove_signature ? '1' : '0');

    if (data.person) {
      for (const [key, value] of Object.entries(data.person)) {
        if (value !== null && value !== undefined) {
          formData.append(`person[${key}]`, value);
        }
      }
    }

    if (data.professional) {
      for (const [key, value] of Object.entries(data.professional)) {
        if (value !== null && value !== undefined) {
          formData.append(`professional[${key}]`, value === true ? '1' : (value === false ? '0' : value));
        }
      }
    }
    
    return formData;
  }

  async createDoctor(data) {
    const formData = this.buildFormData(data);
    const response = await axios.post('/master-data/doctors', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    return response.data;
  }

  async getDoctor(id) {
    const response = await axios.get(`/master-data/doctors/${id}`);
    return response.data;
  }

  async updateDoctor(id, data) {
    const formData = this.buildFormData(data, 'PUT');
    const response = await axios.post(`/master-data/doctors/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    return response.data;
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
