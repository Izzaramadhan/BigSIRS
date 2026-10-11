import axios from '@/utils/axios';

class MedicinePackagesService {
  async getMedicinePackages(params) {
    const response = await axios.get('/master-data/medicine-packages', { params });
    return response.data;
  }

  async getMedicinePackage(id) {
    const response = await axios.get(`/master-data/medicine-packages/${id}`);
    return response.data;
  }

  async createMedicinePackage(data) {
    const response = await axios.post('/master-data/medicine-packages', data);
    return response.data;
  }

  async updateMedicinePackage(id, data) {
    const response = await axios.put(`/master-data/medicine-packages/${id}`, data);
    return response.data;
  }

  async deleteMedicinePackage(id) {
    const response = await axios.delete(`/master-data/medicine-packages/${id}`);
    return response.data;
  }
}

export const medicinePackagesService = new MedicinePackagesService();
