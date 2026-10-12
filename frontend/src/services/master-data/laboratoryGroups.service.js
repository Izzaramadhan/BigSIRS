import axios from '@/utils/axios';

export const laboratoryGroupsService = {
  async list(params = {}) {
    const response = await axios.get('/master-data/laboratory-groups', { params });
    return response.data;
  },

  async detail(id) {
    const response = await axios.get(`/master-data/laboratory-groups/${id}`);
    return response.data;
  },

  async create(payload) {
    const response = await axios.post('/master-data/laboratory-groups', payload);
    return response.data;
  },

  async update(id, payload) {
    const response = await axios.put(`/master-data/laboratory-groups/${id}`, payload);
    return response.data;
  },

  async remove(id) {
    const response = await axios.delete(`/master-data/laboratory-groups/${id}`);
    return response.data;
  }
};
