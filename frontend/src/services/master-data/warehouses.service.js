import axios from '@/utils/axios';

class WarehouseService {
  async getWarehouses(params) {
    const response = await axios.get('/master-data/warehouses', { params });
    return response.data;
  }

  async getWarehouse(id) {
    const response = await axios.get(`/master-data/warehouses/${id}`);
    return response.data;
  }

  async createWarehouse(data) {
    const response = await axios.post('/master-data/warehouses', data);
    return response.data;
  }

  async updateWarehouse(id, data) {
    const response = await axios.put(`/master-data/warehouses/${id}`, data);
    return response.data;
  }

  async deleteWarehouse(id) {
    const response = await axios.delete(`/master-data/warehouses/${id}`);
    return response.data;
  }
}

export const warehouseService = new WarehouseService();
