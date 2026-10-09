import axios from '@/utils/axios';

class MedicineUnitsService {
  async getMedicineUnits(params) {
    const response = await axios.get('/master-data/medicine-units', { params });
    return response.data;
  }

  async getMedicineUnit(id) {
    const response = await axios.get(`/master-data/medicine-units/${id}`);
    return response.data;
  }

  async createMedicineUnit(data) {
    const response = await axios.post('/master-data/medicine-units', data);
    return response.data;
  }

  async updateMedicineUnit(id, data) {
    const response = await axios.put(`/master-data/medicine-units/${id}`, data);
    return response.data;
  }

  async deleteMedicineUnit(id) {
    const response = await axios.delete(`/master-data/medicine-units/${id}`);
    return response.data;
  }
}

export const medicineUnitsService = new MedicineUnitsService();
