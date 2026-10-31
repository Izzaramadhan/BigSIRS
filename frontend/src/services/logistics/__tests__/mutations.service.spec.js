import { describe, it, expect, vi, beforeEach } from 'vitest';
import mutationsService from '../mutations.service';
import axios from '@/utils/axios';

vi.mock('@/utils/axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn()
  }
}));

describe('MutationsService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('calls getMutations with correct endpoint and parameters', async () => {
    const mockData = { data: [{ id: 1, mutationNumber: 'MUT-001' }] };
    axios.get.mockResolvedValueOnce({ data: mockData });

    const params = { page: 1, per_page: 10, search: 'MUT' };
    const result = await mutationsService.getMutations(params);

    expect(axios.get).toHaveBeenCalledWith('/logistics/mutations', { params });
    expect(result).toEqual(mockData);
  });

  it('calls getSummary endpoint', async () => {
    const mockSummary = { totalTransactions: '10 Transaksi', totalCount: 10 };
    axios.get.mockResolvedValueOnce({ data: mockSummary });

    const result = await mutationsService.getSummary();

    expect(axios.get).toHaveBeenCalledWith('/logistics/mutations/summary', { params: {} });
    expect(result).toEqual(mockSummary);
  });

  it('calls getMutation by ID', async () => {
    const mockMutation = { id: 5, mutationNumber: 'MUT-005' };
    axios.get.mockResolvedValueOnce({ data: mockMutation });

    const result = await mutationsService.getMutation(5);

    expect(axios.get).toHaveBeenCalledWith('/logistics/mutations/5');
    expect(result).toEqual(mockMutation);
  });

  it('calls createMutation with correct payload', async () => {
    const payload = { sourceWarehouseId: 1, targetWarehouseId: 2, items: [] };
    axios.post.mockResolvedValueOnce({ data: { id: 10, ...payload } });

    const result = await mutationsService.createMutation(payload);

    expect(axios.post).toHaveBeenCalledWith('/logistics/mutations', payload);
    expect(result.id).toBe(10);
  });

  it('calls getWarehouseStocks with correct parameters', async () => {
    const mockStocks = { data: [{ medicineId: 1, availableStock: 100 }] };
    axios.get.mockResolvedValueOnce({ data: mockStocks });

    const params = { warehouse_id: 1, all: 1 };
    const result = await mutationsService.getWarehouseStocks(params);

    expect(axios.get).toHaveBeenCalledWith('/logistics/warehouse-stocks', { params });
    expect(result).toEqual(mockStocks);
  });

  it('calls getWarehousesLookup', async () => {
    const mockWarehouses = { data: [{ id: 1, name: 'Gudang Utama' }] };
    axios.get.mockResolvedValueOnce({ data: mockWarehouses });

    const result = await mutationsService.getWarehousesLookup();

    expect(axios.get).toHaveBeenCalledWith('/lookups/warehouses', { params: {} });
    expect(result).toEqual(mockWarehouses);
  });

  it('calls exportCsv with blob responseType', async () => {
    const mockBlob = new Blob(['csv content'], { type: 'text/csv' });
    axios.get.mockResolvedValueOnce({ data: mockBlob });

    const params = { search: 'Test' };
    const result = await mutationsService.exportCsv(params);

    expect(axios.get).toHaveBeenCalledWith('/logistics/mutations/export', {
      params,
      responseType: 'blob'
    });
    expect(result).toEqual(mockBlob);
  });
});

