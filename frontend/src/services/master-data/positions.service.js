import axios from '@/utils/axios';

class PositionsService {
  async getPositions(params) {
    const response = await axios.get('/master-data/positions', { params });
    return response.data;
  }

  async getPosition(id) {
    const response = await axios.get(`/master-data/positions/${id}`);
    return response.data;
  }

  async createPosition(data) {
    const response = await axios.post('/master-data/positions', data);
    return response.data;
  }

  async updatePosition(id, data) {
    const response = await axios.put(`/master-data/positions/${id}`, data);
    return response.data;
  }


  async deletePosition(id) {
    const response = await axios.delete(`/master-data/positions/${id}`);
    return response.data;
  }
}

export const positionsService = new PositionsService();
