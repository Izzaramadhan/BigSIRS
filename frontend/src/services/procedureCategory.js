import api from '@/utils/axios';

export const procedureCategoryService = {
  getProcedureCategories(params) {
    return api.get('/master-data/procedure-categories', { params });
  },

  getProcedureCategory(id) {
    return api.get(`/master-data/procedure-categories/${id}`);
  },

  createProcedureCategory(payload) {
    return api.post('/master-data/procedure-categories', payload);
  },

  updateProcedureCategory(id, payload) {
    return api.put(`/master-data/procedure-categories/${id}`, payload);
  },

  updateProcedureCategoryStatus(id, isActive) {
    return api.patch(`/master-data/procedure-categories/${id}/status`, { is_active: isActive });
  },

  archiveProcedureCategory(id) {
    return api.delete(`/master-data/procedure-categories/${id}`);
  }
};
