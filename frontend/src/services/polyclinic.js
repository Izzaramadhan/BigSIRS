import api from '@/utils/axios';

export const polyclinicService = {
  getPolyclinics(params) {
    return api.get('/master-data/polyclinics', { params });
  },

  getPolyclinic(id) {
    return api.get(`/master-data/polyclinics/${id}`);
  },

  getServiceTypes() {
    return api.get('/master-data/polyclinics/service-types');
  },

  createPolyclinic(payload) {
    return api.post('/master-data/polyclinics', payload);
  },

  updatePolyclinic(id, payload) {
    return api.put(`/master-data/polyclinics/${id}`, payload);
  },

  updatePolyclinicStatus(id, isActive) {
    return api.patch(`/master-data/polyclinics/${id}/status`, { is_active: isActive });
  },

  archivePolyclinic(id) {
    return api.delete(`/master-data/polyclinics/${id}`);
  }
};
