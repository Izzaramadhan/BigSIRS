import axios from '@/utils/axios';

class MedicinesService {
  async getMedicines(params) {
    const response = await axios.get('/master-data/medicines', { params });
    return response.data;
  }

  async getMedicine(id) {
    const response = await axios.get(`/master-data/medicines/${id}`);
    return response.data;
  }

  async createMedicine(data) {
    const response = await axios.post('/master-data/medicines', data);
    return response.data;
  }

  async updateMedicine(id, data) {
    const response = await axios.put(`/master-data/medicines/${id}`, data);
    return response.data;
  }

  async deleteMedicine(id) {
    const response = await axios.delete(`/master-data/medicines/${id}`);
    return response.data;
  }
}

export const medicinesService = new MedicinesService();
