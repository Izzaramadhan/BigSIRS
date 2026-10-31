import axios from '@/utils/axios';

class PosSalesService {
  /**
   * Get paginated POS OTC sales with filters
   */
  async getPosSales(params = {}) {
    const response = await axios.get('/logistics/pos-sales', { params });
    return response.data;
  }

  /**
   * Get POS sales summary metrics
   */
  async getSummary(params = {}) {
    const response = await axios.get('/logistics/pos-sales/summary', { params });
    return response.data;
  }

  /**
   * Get form reference options (locations, payment methods, OTC medicines)
   */
  async getFormOptions() {
    const response = await axios.get('/logistics/pos-sales/form-options');
    return response.data;
  }

  /**
   * Get single POS sale detail
   */
  async getPosSale(id) {
    const response = await axios.get(`/logistics/pos-sales/${id}`);
    return response.data;
  }

  /**
   * Create new POS sale (draft or completed)
   */
  async createPosSale(payload) {
    const response = await axios.post('/logistics/pos-sales', payload);
    return response.data;
  }

  /**
   * Update draft POS sale
   */
  async updatePosSale(id, payload) {
    const response = await axios.put(`/logistics/pos-sales/${id}`, payload);
    return response.data;
  }

  /**
   * Cancel (void) POS sale with reason
   */
  async cancelPosSale(id, reason) {
    const response = await axios.post(`/logistics/pos-sales/${id}/cancel`, { reason });
    return response.data;
  }

  /**
   * Delete draft POS sale
   */
  async deletePosSale(id) {
    const response = await axios.delete(`/logistics/pos-sales/${id}`);
    return response.data;
  }

  /**
   * Search master pembeli (customers / employees)
   */
  async getCustomers(params = {}) {
    const response = await axios.get('/logistics/pos-sales/customers', { params });
    return response.data;
  }

  /**
   * Get available stocks for a specific warehouse
   */
  async getAvailableStocks(warehouseId, params = {}, config = {}) {
    const response = await axios.get(`/logistics/warehouses/${warehouseId}/available-stocks`, {
      params,
      ...config
    });
    return response.data;
  }

  /**
   * Get warehouse stocks fallback
   */
  async getWarehouseStocks(params = {}, config = {}) {
    const response = await axios.get('/logistics/warehouse-stocks', {
      params,
      ...config
    });
    return response.data;
  }

  /**
   * Add single item to sale detail
   */
  async addItem(posSaleId, payload) {
    const response = await axios.post(`/logistics/pos-sales/${posSaleId}/items`, payload);
    return response.data;
  }

  /**
   * Update single item in sale detail
   */
  async updateItem(posSaleId, itemId, payload) {
    const response = await axios.put(`/logistics/pos-sales/${posSaleId}/items/${itemId}`, payload);
    return response.data;
  }

  /**
   * Delete single item from sale detail
   */
  async deleteItem(posSaleId, itemId) {
    const response = await axios.delete(`/logistics/pos-sales/${posSaleId}/items/${itemId}`);
    return response.data;
  }

  /**
   * Export filtered POS sales as CSV blob
   */
  async exportCsv(params = {}) {
    const response = await axios.get('/logistics/pos-sales/export', {
      params,
      responseType: 'blob'
    });
    return response.data;
  }
}

export const posSalesService = new PosSalesService();
export default posSalesService;

