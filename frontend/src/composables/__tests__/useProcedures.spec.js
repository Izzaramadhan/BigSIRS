import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useProcedures } from '../useProcedures';
import procedureService from '@/services/procedure';

vi.mock('@/services/procedure', () => ({
  default: {
    getProcedures: vi.fn(),
    getProcedure: vi.fn(),
    createProcedure: vi.fn(),
    updateProcedure: vi.fn(),
    updateProcedureVisibility: vi.fn(),
    deleteProcedure: vi.fn(),
    exportProcedures: vi.fn()
  }
}));

describe('useProcedures composable - exportProcedures', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('exports procedures, triggers browser download, and revokes object URL', async () => {
    const mockCreateObjectURL = vi.fn().mockReturnValue('blob:http://localhost/test-blob');
    const mockRevokeObjectURL = vi.fn();
    window.URL.createObjectURL = mockCreateObjectURL;
    window.URL.revokeObjectURL = mockRevokeObjectURL;

    const appendChildSpy = vi.spyOn(document.body, 'appendChild');
    const removeChildSpy = vi.spyOn(document.body, 'removeChild');

    procedureService.exportProcedures.mockResolvedValue({
      data: new Blob(['fake excel content'], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }),
      headers: {
        'content-disposition': 'attachment; filename="data-tindakan-2026-10-14-114500.xlsx"'
      }
    });

    const { exporting, exportProcedures } = useProcedures();

    expect(exporting.value).toBe(false);

    const promise = exportProcedures({
      category_id: 1,
      tariff_type_id: 2,
      search: 'Gigi',
      sort_by: 'name',
      sort_dir: 'asc',
      page: 3,
      per_page: 10
    });

    expect(exporting.value).toBe(true);

    const result = await promise;

    expect(exporting.value).toBe(false);
    expect(result.success).toBe(true);
    expect(result.message).toBe('Data tindakan berhasil diunduh.');

    expect(procedureService.exportProcedures).toHaveBeenCalledWith({
      category_id: 1,
      tariff_type_id: 2,
      search: 'Gigi',
      sort_by: 'name',
      sort_dir: 'asc'
    });

    expect(mockCreateObjectURL).toHaveBeenCalledTimes(1);
    expect(appendChildSpy).toHaveBeenCalled();
    expect(removeChildSpy).toHaveBeenCalled();
    expect(mockRevokeObjectURL).toHaveBeenCalledWith('blob:http://localhost/test-blob');
  });

  it('handles empty results from backend JSON blob response', async () => {
    procedureService.exportProcedures.mockResolvedValue({
      data: new Blob([JSON.stringify({ message: 'Tidak ada data tindakan yang dapat diunduh.' })], {
        type: 'application/json'
      }),
      headers: {
        'content-type': 'application/json'
      }
    });

    const { exporting, exportProcedures } = useProcedures();
    const result = await exportProcedures({ search: 'NonExistent' });

    expect(exporting.value).toBe(false);
    expect(result.success).toBe(false);
    expect(result.empty).toBe(true);
    expect(result.message).toBe('Tidak ada data tindakan yang dapat diunduh.');
  });

  it('handles API error and returns failure message', async () => {
    procedureService.exportProcedures.mockRejectedValue(new Error('Network failure'));

    const { exporting, exportProcedures } = useProcedures();
    const result = await exportProcedures();

    expect(exporting.value).toBe(false);
    expect(result.success).toBe(false);
    expect(result.message).toBe('Data tindakan gagal diunduh. Silakan coba lagi.');
  });
});
