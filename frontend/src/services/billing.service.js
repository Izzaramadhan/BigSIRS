import api from '@/utils/axios';

export const billingService = {
  getBillings(params = {}) {
    return api.get('/billings', { params });
  },

  getBilling(visitId) {
    return api.get(`/billings/${visitId}`);
  }
};

export default billingService;
