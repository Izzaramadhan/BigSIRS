import axios from '@/utils/axios';

class EmployeeService {
  async getEmployees(params) {
    const response = await axios.get('/master-data/employees', { params });
    return response.data;
  }

  async getEmployee(id) {
    const response = await axios.get(`/master-data/employees/${id}`);
    return response.data;
  }

  buildFormData(data, method = null) {
    if (data instanceof FormData) {
      if (method && !data.has('_method')) {
        data.append('_method', method);
      }
      return data;
    }

    const formData = new FormData();
    if (method) {
      formData.append('_method', method);
    }

    for (const [key, value] of Object.entries(data)) {
      if (key === 'signature') {
        if (value instanceof File || value instanceof Blob) {
          formData.append('signature', value);
        }
      } else if (key === 'remove_signature') {
        if (value) {
          formData.append('remove_signature', '1');
        }
      } else if (value === null) {
        formData.append(key, '');
      } else if (value !== undefined) {
        if (typeof value === 'boolean') {
          formData.append(key, value ? '1' : '0');
        } else {
          formData.append(key, value);
        }
      }
    }

    return formData;
  }

  async createEmployee(data) {
    const formData = this.buildFormData(data);
    const response = await axios.post('/master-data/employees', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    return response.data;
  }

  async updateEmployee(id, data) {
    const formData = this.buildFormData(data, 'PUT');
    const response = await axios.post(`/master-data/employees/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    return response.data;
  }

  async deleteEmployee(id) {
    const response = await axios.delete(`/master-data/employees/${id}`);
    return response.data;
  }

  async updateStatus(id, isActive) {
    const response = await axios.patch(`/master-data/employees/${id}/status`, { is_active: isActive });
    return response.data;
  }

  async getPositions(params) {
    const response = await axios.get('/lookups/positions', { params });
    return response.data;
  }
}

export default new EmployeeService();
