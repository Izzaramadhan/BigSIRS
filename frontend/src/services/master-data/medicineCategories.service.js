import axios from '@/utils/axios';

class MedicineCategoriesService {
  async getMedicineCategories(params) {
    const response = await axios.get('/master-data/medicine-categories', { params });
    return response.data;
  }

  async getMedicineCategory(id) {
    const response = await axios.get(`/master-data/medicine-categories/${id}`);
    return response.data;
  }

  async createMedicineCategory(data) {
    const response = await axios.post('/master-data/medicine-categories', data);
    return response.data;
  }

  async updateMedicineCategory(id, data) {
    const response = await axios.put(`/master-data/medicine-categories/${id}`, data);
    return response.data;
  }

  async deleteMedicineCategory(id) {
    const response = await axios.delete(`/master-data/medicine-categories/${id}`);
    return response.data;
  }
}

export const medicineCategoriesService = new MedicineCategoriesService();
