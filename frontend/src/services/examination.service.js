import api from '@/utils/axios';

export const examinationService = {
  getExaminations(params = {}) {
    return api.get('/examinations', { params });
  },

  getExamination(id) {
    return api.get(`/examinations/${id}`);
  }
};

export default examinationService;
