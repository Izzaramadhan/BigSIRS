import axios from '@/utils/axios';

class StockOpnameService {
  /**
   * Get paginated direct warehouse stocks with filters
   * @param {Object} params - Query params (warehouse_id, hide_zero_stock, search, page, per_page, sort, direction)
   */
  async getStockOpnames(params = {}) {
    const response = await axios.get('/logistics/stock-opnames', { params });
    return response.data;
  }

  /**
   * Get single warehouse stock detail by ID
   * @param {number|string} id - Stock ID
   */
  async getStockOpname(id) {
    const response = await axios.get(`/logistics/stock-opnames/${id}`);
    return response.data;
  }

  /**
   * Update warehouse stock (expiration_date, selling_price, stock)
   * @param {number|string} id - Stock ID
   * @param {Object} payload - Update data { expiration_date, selling_price, stock }
   */
  async updateStockOpname(id, payload) {
    const response = await axios.patch(`/logistics/stock-opnames/${id}`, payload);
    return response.data;
  }

  /**
   * Fetch active warehouses for dropdown filters
   */
  async getWarehousesLookup(params = {}) {
    const response = await axios.get('/lookups/warehouses', { params });
    return response.data;
  }

  /**
   * Export stock opnames as CSV blob
   */
  async exportCsv(params = {}) {
    const response = await axios.get('/logistics/stock-opnames/export', {
      params,
      responseType: 'blob'
    });
    return response.data;
  }
}

export const stockOpnamesService = new StockOpnameService();
export default stockOpnamesService;
