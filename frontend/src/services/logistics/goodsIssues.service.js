import axios from '@/utils/axios';

class GoodsIssueService {
  /**
   * Get paginated goods issues with filters
   */
  async getGoodsIssues(params = {}) {
    const response = await axios.get('/logistics/goods-issues', { params });
    return response.data;
  }

  /**
   * Get goods issues summary metrics
   */
  async getSummary(params = {}) {
    const response = await axios.get('/logistics/goods-issues/summary', { params });
    return response.data;
  }

  /**
   * Get form reference options (warehouses, officers, purposes)
   */
  async getFormOptions() {
    const response = await axios.get('/logistics/goods-issues/form-options');
    return response.data;
  }

  /**
   * Fetch available stocks for a specific warehouse
   */
  async getWarehouseStocks(params = {}, config = {}) {
    const response = await axios.get('/logistics/warehouse-stocks', { params, ...config });
    return response.data;
  }

  /**
   * Fetch available stocks for a specific warehouse with dedicated endpoint
   */
  async getAvailableStocks(warehouseId, params = {}, config = {}) {
    const response = await axios.get(`/logistics/warehouses/${warehouseId}/available-stocks`, {
      params,
      ...config
    });
    return response.data;
  }

  /**
   * Get single goods issue detail
   */
  async getGoodsIssue(id) {
    const response = await axios.get(`/logistics/goods-issues/${id}`);
    return response.data;
  }

  /**
   * Create new goods issue (draft or finalized)
   */
  async createGoodsIssue(payload) {
    const response = await axios.post('/logistics/goods-issues', payload);
    return response.data;
  }

  /**
   * Update draft goods issue
   */
  async updateGoodsIssue(id, payload) {
    const response = await axios.put(`/logistics/goods-issues/${id}`, payload);
    return response.data;
  }

  /**
   * Finalize draft goods issue
   */
  async finalizeGoodsIssue(id) {
    const response = await axios.post(`/logistics/goods-issues/${id}/finalize`);
    return response.data;
  }

  /**
   * Cancel goods issue with reason
   */
  async cancelGoodsIssue(id, reason) {
    const response = await axios.post(`/logistics/goods-issues/${id}/cancel`, { reason });
    return response.data;
  }

  /**
   * Delete draft goods issue
   */
  async deleteGoodsIssue(id) {
    const response = await axios.delete(`/logistics/goods-issues/${id}`);
    return response.data;
  }

  /**
   * Add item to goods issue
   */
  async addItem(goodsIssueId, payload) {
    const response = await axios.post(`/logistics/goods-issues/${goodsIssueId}/items`, payload);
    return response.data;
  }

  /**
   * Update item on goods issue
   */
  async updateItem(goodsIssueId, itemId, payload) {
    const response = await axios.put(`/logistics/goods-issues/${goodsIssueId}/items/${itemId}`, payload);
    return response.data;
  }

  /**
   * Delete item from goods issue
   */
  async deleteItem(goodsIssueId, itemId) {
    const response = await axios.delete(`/logistics/goods-issues/${goodsIssueId}/items/${itemId}`);
    return response.data;
  }

  /**
   * Export filtered goods issues as CSV blob
   */
  async exportCsv(params = {}) {
    const response = await axios.get('/logistics/goods-issues/export', {
      params,
      responseType: 'blob'
    });
    return response.data;
  }
}

export const goodsIssuesService = new GoodsIssueService();
export default goodsIssuesService;

