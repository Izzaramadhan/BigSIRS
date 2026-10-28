import axios from '@/utils/axios';

class InvoiceService {
  /**
   * Get paginated invoices with filters
   */
  async getInvoices(params = {}) {
    const response = await axios.get('/logistics/invoices', { params });
    return response.data;
  }

  /**
   * Get invoices summary metrics
   */
  async getSummary(params = {}) {
    const response = await axios.get('/logistics/invoices/summary', { params });
    return response.data;
  }

  /**
   * Get single invoice detail
   */
  async getInvoice(id) {
    const response = await axios.get(`/logistics/invoices/${id}`);
    return response.data;
  }

  /**
   * Create a new invoice
   */
  async createInvoice(data) {
    const response = await axios.post('/logistics/invoices', data);
    return response.data;
  }

  /**
   * Update an existing invoice
   */
  async updateInvoice(id, data) {
    const response = await axios.put(`/logistics/invoices/${id}`, data);
    return response.data;
  }

  /**
   * Delete an invoice
   */
  async deleteInvoice(id) {
    const response = await axios.delete(`/logistics/invoices/${id}`);
    return response.data;
  }

  /**
   * Fetch active suppliers for lookup/select
   */
  async getSuppliersLookup(params = {}) {
    const response = await axios.get('/lookups/suppliers', {
      params: { all: 1, ...params }
    });
    return response.data;
  }

  /**
   * Fetch active medicines for lookup/select
   */
  async getMedicinesLookup(params = {}) {
    const response = await axios.get('/lookups/medicines', {
      params: { all: 1, ...params }
    });
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
   * Export invoices as CSV download
   */
  async exportCsv() {
    const response = await axios.get('/logistics/invoices/export', {
      responseType: 'blob'
    });
    return response.data;
  }
}

export const invoicesService = new InvoiceService();
export default invoicesService;
