import api from '@/utils/axios';

export const admissionService = {
  getAdmissions(params = {}) {
    return api.get('/admissions', { params });
  },

  getAdmission(id) {
    return api.get(`/admissions/${id}`);
  },

  createAdmission(payload) {
    return api.post('/admissions', payload);
  },

  updateAdmission(id, payload) {
    return api.put(`/admissions/${id}`, payload);
  }
};

export default admissionService;
