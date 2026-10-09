import axios from '@/utils/axios';

class SupplierService {
  async getSuppliers(params) {
    const response = await axios.get('/master-data/suppliers', { params });
    return response.data;
  }

  async getSupplier(id) {
    const response = await axios.get(`/master-data/suppliers/${id}`);
    return response.data;
  }

  async createSupplier(data) {
    const response = await axios.post('/master-data/suppliers', data);
    return response.data;
  }

  async updateSupplier(id, data) {
    const response = await axios.put(`/master-data/suppliers/${id}`, data);
    return response.data;
  }

  async deleteSupplier(id) {
    const response = await axios.delete(`/master-data/suppliers/${id}`);
    return response.data;
  }
}

export const SupplierServiceInstance = new SupplierService();
export { SupplierServiceInstance as SupplierService };
