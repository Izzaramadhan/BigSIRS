import { describe, it, expect, vi, beforeEach } from 'vitest';
import goodsIssuesService from '../goodsIssues.service';
import axios from '@/utils/axios';

vi.mock('@/utils/axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn()
  }
}));

describe('GoodsIssuesService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('calls getGoodsIssues with correct endpoint and parameters', async () => {
    const mockData = { data: [{ id: 1, issueNumber: 'OUT-202610-0001' }] };
    axios.get.mockResolvedValueOnce({ data: mockData });

    const params = { page: 1, per_page: 10, search: 'OUT' };
    const result = await goodsIssuesService.getGoodsIssues(params);

    expect(axios.get).toHaveBeenCalledWith('/logistics/goods-issues', { params });
    expect(result).toEqual(mockData);
  });

  it('calls getSummary endpoint', async () => {
    const mockSummary = { data: { todayIssues: '5', totalItemOut: '150' } };
    axios.get.mockResolvedValueOnce({ data: mockSummary });

    const result = await goodsIssuesService.getSummary();

    expect(axios.get).toHaveBeenCalledWith('/logistics/goods-issues/summary', { params: {} });
    expect(result).toEqual(mockSummary);
  });

  it('calls getFormOptions endpoint', async () => {
    const mockOptions = { data: { warehouses: ['Gudang Utama'], officers: ['Apt. Budi'] } };
    axios.get.mockResolvedValueOnce({ data: mockOptions });

    const result = await goodsIssuesService.getFormOptions();

    expect(axios.get).toHaveBeenCalledWith('/logistics/goods-issues/form-options');
    expect(result).toEqual(mockOptions);
  });

  it('calls getGoodsIssue by ID', async () => {
    const mockIssue = { data: { id: 10, issueNumber: 'OUT-202610-0010' } };
    axios.get.mockResolvedValueOnce({ data: mockIssue });

    const result = await goodsIssuesService.getGoodsIssue(10);

    expect(axios.get).toHaveBeenCalledWith('/logistics/goods-issues/10');
    expect(result).toEqual(mockIssue);
  });

  it('calls createGoodsIssue with correct payload', async () => {
    const payload = { warehouse_id: 1, purpose: 'Kebutuhan Internal', items: [] };
    axios.post.mockResolvedValueOnce({ data: { id: 15, ...payload } });

    const result = await goodsIssuesService.createGoodsIssue(payload);

    expect(axios.post).toHaveBeenCalledWith('/logistics/goods-issues', payload);
    expect(result.id).toBe(15);
  });

  it('calls updateGoodsIssue with ID and payload', async () => {
    const payload = { purpose: 'Barang Rusak' };
    axios.put.mockResolvedValueOnce({ data: { id: 12, ...payload } });

    const result = await goodsIssuesService.updateGoodsIssue(12, payload);

    expect(axios.put).toHaveBeenCalledWith('/logistics/goods-issues/12', payload);
    expect(result.id).toBe(12);
  });

  it('calls finalizeGoodsIssue with ID', async () => {
    axios.post.mockResolvedValueOnce({ data: { id: 8, status: 'Selesai' } });

    const result = await goodsIssuesService.finalizeGoodsIssue(8);

    expect(axios.post).toHaveBeenCalledWith('/logistics/goods-issues/8/finalize');
    expect(result.status).toBe('Selesai');
  });

  it('calls cancelGoodsIssue with ID and reason', async () => {
    axios.post.mockResolvedValueOnce({ data: { id: 9, status: 'Dibatalkan' } });

    const result = await goodsIssuesService.cancelGoodsIssue(9, 'Salah entri data');

    expect(axios.post).toHaveBeenCalledWith('/logistics/goods-issues/9/cancel', { reason: 'Salah entri data' });
    expect(result.status).toBe('Dibatalkan');
  });

  it('calls deleteGoodsIssue with ID', async () => {
    axios.delete.mockResolvedValueOnce({ data: { success: true } });

    const result = await goodsIssuesService.deleteGoodsIssue(7);

    expect(axios.delete).toHaveBeenCalledWith('/logistics/goods-issues/7');
    expect(result.success).toBe(true);
  });
});

