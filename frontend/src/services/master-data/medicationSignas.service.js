import axios from '@/utils/axios';

class MedicationSignasService {
  async getMedicationSignas(params) {
    const response = await axios.get('/master-data/medication-signas', { params });
    return response.data;
  }

  async getMedicationSigna(id) {
    const response = await axios.get(`/master-data/medication-signas/${id}`);
    return response.data;
  }

  async createMedicationSigna(data) {
    const response = await axios.post('/master-data/medication-signas', data);
    return response.data;
  }

  async updateMedicationSigna(id, data) {
    const response = await axios.put(`/master-data/medication-signas/${id}`, data);
    return response.data;
  }

  async deleteMedicationSigna(id) {
    const response = await axios.delete(`/master-data/medication-signas/${id}`);
    return response.data;
  }
}

export const medicationSignasService = new MedicationSignasService();
