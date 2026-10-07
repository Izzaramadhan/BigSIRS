import axios from '@/utils/axios';

class LaboratoryCategoriesService {
  async getLaboratoryCategories(params) {
    const response = await axios.get('/master-data/laboratory-categories', { params });
    return response.data;
  }

  async getLaboratoryCategory(id) {
    const response = await axios.get(`/master-data/laboratory-categories/${id}`);
    return response.data;
  }

  async createLaboratoryCategory(data) {
    const response = await axios.post('/master-data/laboratory-categories', data);
    return response.data;
  }

  async updateLaboratoryCategory(id, data) {
    const response = await axios.put(`/master-data/laboratory-categories/${id}`, data);
    return response.data;
  }

  async updateStatus(id, isActive) {
    const response = await axios.patch(`/master-data/laboratory-categories/${id}/status`, { is_active: isActive });
    return response.data;
  }

  async deleteLaboratoryCategory(id) {
    const response = await axios.delete(`/master-data/laboratory-categories/${id}`);
    return response.data;
  }
}

export const laboratoryCategoriesService = new LaboratoryCategoriesService();
