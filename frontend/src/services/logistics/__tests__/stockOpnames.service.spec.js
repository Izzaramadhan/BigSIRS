import { describe, it, expect, vi, beforeEach } from 'vitest';
import stockOpnamesService from '../stockOpnames.service';
import axios from '@/utils/axios';

vi.mock('@/utils/axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn()
  }
}));

describe('StockOpnamesService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('calls getStockOpnames with correct endpoint and parameters', async () => {
    const mockData = { data: [{ id: 1, medicine: { name: 'Alprazolam' }, stock: 500 }] };
    axios.get.mockResolvedValueOnce({ data: mockData });

    const params = { page: 1, per_page: 10, warehouse_id: 1, hide_zero_stock: 1 };
    const result = await stockOpnamesService.getStockOpnames(params);

    expect(axios.get).toHaveBeenCalledWith('/logistics/stock-opnames', { params });
    expect(result).toEqual(mockData);
  });

  it('calls getStockOpname by ID', async () => {
    const mockDetail = { id: 5, medicine: { name: 'Meloxicam' }, stock: 55 };
    axios.get.mockResolvedValueOnce({ data: mockDetail });

    const result = await stockOpnamesService.getStockOpname(5);

    expect(axios.get).toHaveBeenCalledWith('/logistics/stock-opnames/5');
    expect(result).toEqual(mockDetail);
  });

  it('calls updateStockOpname with payload using PATCH', async () => {
    const payload = { expiration_date: '2028-12-31', selling_price: 1200, stock: 450 };
    axios.patch.mockResolvedValueOnce({ data: { id: 5, ...payload } });

    const result = await stockOpnamesService.updateStockOpname(5, payload);

    expect(axios.patch).toHaveBeenCalledWith('/logistics/stock-opnames/5', payload);
    expect(result.stock).toBe(450);
    expect(result.selling_price).toBe(1200);
  });

  it('calls getWarehousesLookup', async () => {
    const mockWarehouses = [{ id: 1, name: 'Gudang Utama' }];
    axios.get.mockResolvedValueOnce({ data: mockWarehouses });

    const result = await stockOpnamesService.getWarehousesLookup();

    expect(axios.get).toHaveBeenCalledWith('/lookups/warehouses', { params: {} });
    expect(result).toEqual(mockWarehouses);
  });

  it('calls exportCsv endpoint', async () => {
    const mockBlob = new Blob(['csv content'], { type: 'text/csv' });
    axios.get.mockResolvedValueOnce({ data: mockBlob });

    const params = { warehouse_id: 1 };
    const result = await stockOpnamesService.exportCsv(params);

    expect(axios.get).toHaveBeenCalledWith('/logistics/stock-opnames/export', {
      params,
      responseType: 'blob'
    });
    expect(result).toEqual(mockBlob);
  });
});
