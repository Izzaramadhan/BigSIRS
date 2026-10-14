import axios from '@/utils/axios';

class GenericMedicinesService {
  async getGenericMedicines(params) {
    const response = await axios.get('/master-data/generic-medicines', { params });
    return response.data;
  }

  async getGenericMedicine(id) {
    const response = await axios.get(`/master-data/generic-medicines/${id}`);
    return response.data;
  }

  async createGenericMedicine(data) {
    const response = await axios.post('/master-data/generic-medicines', data);
    return response.data;
  }
}

export const genericMedicinesService = new GenericMedicinesService();
export default genericMedicinesService;
