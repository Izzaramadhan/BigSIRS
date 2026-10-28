import { describe, it, expect, vi, beforeEach } from 'vitest';
import invoicesService from '../invoices.service';
import axios from '@/utils/axios';

vi.mock('@/utils/axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn()
  }
}));

describe('InvoicesService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('calls getInvoices with correct endpoint and parameters', async () => {
    const mockData = { data: [{ id: 1, invoiceNumber: 'FKT-001' }] };
    axios.get.mockResolvedValueOnce({ data: mockData });

    const params = { page: 1, per_page: 10, search: 'KF' };
    const result = await invoicesService.getInvoices(params);

    expect(axios.get).toHaveBeenCalledWith('/logistics/invoices', { params });
    expect(result).toEqual(mockData);
  });

  it('calls getSummary endpoint', async () => {
    const mockSummary = { totalCount: 10, totalAmount: 5000000 };
    axios.get.mockResolvedValueOnce({ data: mockSummary });

    const result = await invoicesService.getSummary();

    expect(axios.get).toHaveBeenCalledWith('/logistics/invoices/summary', { params: {} });
    expect(result).toEqual(mockSummary);
  });

  it('calls getInvoice by ID', async () => {
    const mockInvoice = { id: 5, invoiceNumber: 'FKT-005' };
    axios.get.mockResolvedValueOnce({ data: mockInvoice });

    const result = await invoicesService.getInvoice(5);

    expect(axios.get).toHaveBeenCalledWith('/logistics/invoices/5');
    expect(result).toEqual(mockInvoice);
  });

  it('calls createInvoice with correct payload', async () => {
    const payload = { invoiceNumber: 'FKT-NEW', totalAmount: 1000000 };
    axios.post.mockResolvedValueOnce({ data: { id: 99, ...payload } });

    const result = await invoicesService.createInvoice(payload);

    expect(axios.post).toHaveBeenCalledWith('/logistics/invoices', payload);
    expect(result.id).toBe(99);
  });

  it('calls updateInvoice with correct endpoint and payload', async () => {
    const payload = { invoiceNumber: 'FKT-EDITED', status: 'Lunas' };
    axios.put.mockResolvedValueOnce({ data: { id: 12, ...payload } });

    const result = await invoicesService.updateInvoice(12, payload);

    expect(axios.put).toHaveBeenCalledWith('/logistics/invoices/12', payload);
    expect(result.status).toBe('Lunas');
  });

  it('calls deleteInvoice with correct ID', async () => {
    axios.delete.mockResolvedValueOnce({ data: { success: true } });

    const result = await invoicesService.deleteInvoice(12);

    expect(axios.delete).toHaveBeenCalledWith('/logistics/invoices/12');
    expect(result.success).toBe(true);
  });

  it('calls getSuppliersLookup with all=1 parameter', async () => {
    const mockSuppliers = [{ id: 1, name: 'PT Supplier' }];
    axios.get.mockResolvedValueOnce({ data: mockSuppliers });

    const result = await invoicesService.getSuppliersLookup();

    expect(axios.get).toHaveBeenCalledWith('/lookups/suppliers', {
      params: { all: 1 }
    });
    expect(result).toEqual(mockSuppliers);
  });

  it('calls getMedicinesLookup with all=1 parameter', async () => {
    const mockMedicines = [{ id: 1, name: 'Paracetamol' }];
    axios.get.mockResolvedValueOnce({ data: mockMedicines });

    const result = await invoicesService.getMedicinesLookup();

    expect(axios.get).toHaveBeenCalledWith('/lookups/medicines', {
      params: { all: 1 }
    });
    expect(result).toEqual(mockMedicines);
  });

  it('calls getWarehousesLookup endpoint', async () => {
    const mockWarehouses = [{ id: 1, name: 'Gudang Utama' }];
    axios.get.mockResolvedValueOnce({ data: mockWarehouses });

    const result = await invoicesService.getWarehousesLookup();

    expect(axios.get).toHaveBeenCalledWith('/lookups/warehouses', {
      params: {}
    });
    expect(result).toEqual(mockWarehouses);
  });

  it('calls exportCsv with responseType blob', async () => {
    const mockBlob = new Blob(['test,csv'], { type: 'text/csv' });
    axios.get.mockResolvedValueOnce({ data: mockBlob });

    const result = await invoicesService.exportCsv();

    expect(axios.get).toHaveBeenCalledWith('/logistics/invoices/export', {
      responseType: 'blob'
    });
    expect(result).toEqual(mockBlob);
  });
});
