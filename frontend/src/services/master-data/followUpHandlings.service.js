import axios from '@/utils/axios';

class FollowUpHandlingsService {
  async getFollowUpHandlings(params = {}) {
    const response = await axios.get('/master-data/follow-up-handlings', { params });
    return response.data;
  }

  async getHandlings(params = {}) {
    return this.getFollowUpHandlings(params);
  }

  async getFollowUpHandling(id) {
    const response = await axios.get(`/master-data/follow-up-handlings/${id}`);
    return response.data;
  }

  async getHandling(id) {
    return this.getFollowUpHandling(id);
  }

  async createFollowUpHandling(data) {
    const response = await axios.post('/master-data/follow-up-handlings', data);
    return response.data;
  }

  async createHandling(data) {
    return this.createFollowUpHandling(data);
  }

  async updateFollowUpHandling(id, data) {
    const response = await axios.put(`/master-data/follow-up-handlings/${id}`, data);
    return response.data;
  }

  async updateHandling(id, data) {
    return this.updateFollowUpHandling(id, data);
  }

  async deleteFollowUpHandling(id) {
    const response = await axios.delete(`/master-data/follow-up-handlings/${id}`);
    return response.data;
  }

  async deleteHandling(id) {
    return this.deleteFollowUpHandling(id);
  }
}

export const followUpHandlingsService = new FollowUpHandlingsService();
export default followUpHandlingsService;
