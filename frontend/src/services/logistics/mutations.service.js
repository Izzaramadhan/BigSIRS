import axios from '@/utils/axios';

class MutationService {
  /**
   * Get paginated mutations with filters
   */
  async getMutations(params = {}) {
    const response = await axios.get('/logistics/mutations', { params });
    return response.data;
  }

  /**
   * Get mutations statistical summary metrics
   */
  async getSummary(params = {}) {
    const response = await axios.get('/logistics/mutations/summary', { params });
    return response.data;
  }

  /**
   * Get single mutation detail by id
   */
  async getMutation(id) {
    const response = await axios.get(`/logistics/mutations/${id}`);
    return response.data;
  }

  /**
   * Create a new stock mutation
   */
  async createMutation(payload) {
    const response = await axios.post('/logistics/mutations', payload);
    return response.data;
  }

  /**
   * Fetch available stocks for a specific warehouse
   */
  async getWarehouseStocks(params = {}) {
    const response = await axios.get('/logistics/warehouse-stocks', { params });
    return response.data;
  }

  /**
   * Fetch active warehouses for lookup/select
   */
  async getWarehousesLookup(params = {}) {
    const response = await axios.get('/lookups/warehouses', { params });
    return response.data;
  }

  /**
   * Export filtered mutations as CSV download blob
   */
  async exportCsv(params = {}) {
    const response = await axios.get('/logistics/mutations/export', {
      params,
      responseType: 'blob'
    });
    return response.data;
  }
}

export const mutationsService = new MutationService();
export default mutationsService;

