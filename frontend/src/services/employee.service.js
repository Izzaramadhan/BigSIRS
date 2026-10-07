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

  async createEmployee(data) {
    const response = await axios.post('/master-data/employees', data);
    return response.data;
  }

  async updateEmployee(id, data) {
    const response = await axios.put(`/master-data/employees/${id}`, data);
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
