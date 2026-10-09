import axios from '@/utils/axios';

class MedicineRoutesService {
  async getMedicineRoutes(params) {
    const response = await axios.get('/master-data/medicine-routes', { params });
    return response.data;
  }

  async getMedicineRoute(id) {
    const response = await axios.get(`/master-data/medicine-routes/${id}`);
    return response.data;
  }

  async createMedicineRoute(data) {
    const response = await axios.post('/master-data/medicine-routes', data);
    return response.data;
  }

  async updateMedicineRoute(id, data) {
    const response = await axios.put(`/master-data/medicine-routes/${id}`, data);
    return response.data;
  }

  async deleteMedicineRoute(id) {
    const response = await axios.delete(`/master-data/medicine-routes/${id}`);
    return response.data;
  }
}

export const medicineRoutesService = new MedicineRoutesService();
