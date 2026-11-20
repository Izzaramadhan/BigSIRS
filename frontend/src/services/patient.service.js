import api from '@/utils/axios';

export const patientService = {
  searchPatients(params = {}) {
    return api.get('/patients/search', { params });
  },

  getPatient(id) {
    return api.get(`/patients/${id}`);
  },

  getPatientAdmissions(id, params = {}) {
    return api.get(`/patients/${id}/admissions`, { params });
  },

  getPatientTimeline(id, params = {}) {
    return api.get(`/patients/${id}/timeline`, { params });
  },

  createPatient(payload) {
    return api.post('/patients', payload);
  },

  updatePatient(id, payload) {
    return api.put(`/patients/${id}`, payload);
  },

  deletePatient(id, reason = '') {
    return api.delete(`/patients/${id}`, { data: { reason } });
  }
};

export default patientService;
