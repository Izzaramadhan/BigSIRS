import axios from '@/utils/axios';

class LetterTypeService {
  async getLetterTypes(params) {
    const response = await axios.get('/master-data/letter-types', { params });
    return response.data;
  }

  async getLetterType(id) {
    const response = await axios.get(`/master-data/letter-types/${id}`);
    return response.data;
  }

  async createLetterType(data) {
    const response = await axios.post('/master-data/letter-types', data);
    return response.data;
  }

  async updateLetterType(id, data) {
    const response = await axios.put(`/master-data/letter-types/${id}`, data);
    return response.data;
  }

  async deleteLetterType(id) {
    const response = await axios.delete(`/master-data/letter-types/${id}`);
    return response.data;
  }

  async updateStatus(id, isActive) {
    const response = await axios.patch(`/master-data/letter-types/${id}/status`, { is_active: isActive });
    return response.data;
  }
}

export default new LetterTypeService();
