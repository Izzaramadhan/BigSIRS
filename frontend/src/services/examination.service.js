import api from '@/utils/axios';

export const examinationService = {
  getExaminations(params = {}) {
    return api.get('/examinations', { params });
  },

  getExamination(id) {
    return api.get(`/examinations/${id}`);
  },

  updateStatus(id, status) {
    return api.put(`/examinations/${id}/status`, { status });
  },

  saveVitals(id, payload) {
    return api.put(`/examinations/${id}/vitals`, payload);
  },

  saveClinical(id, payload) {
    return api.put(`/examinations/${id}/clinical`, payload);
  },

  getAllergies(id) {
    return api.get(`/examinations/${id}/allergies`);
  },

  saveAllergies(id, payload) {
    return api.put(`/examinations/${id}/allergies`, payload);
  },

  addDiagnosis(id, payload) {
    return api.post(`/examinations/${id}/diagnoses`, payload);
  },

  deleteDiagnosis(id, diagnosisId) {
    return api.delete(`/examinations/${id}/diagnoses/${diagnosisId}`);
  },

  addProcedure(id, payload) {
    return api.post(`/examinations/${id}/procedures`, payload);
  },

  deleteProcedure(id, procedureId) {
    return api.delete(`/examinations/${id}/procedures/${procedureId}`);
  },

  savePrescription(id, payload) {
    return api.post(`/examinations/${id}/prescriptions`, payload);
  },

  saveLabOrder(id, payload) {
    return api.post(`/examinations/${id}/labs`, payload);
  },

  saveRadiologyOrder(id, payload) {
    return api.post(`/examinations/${id}/radiologies`, payload);
  }
};

export default examinationService;
