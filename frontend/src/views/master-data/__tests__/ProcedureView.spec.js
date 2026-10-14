import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import ProcedureView from '../ProcedureView.vue';
import procedureService from '@/services/procedure';
import { procedureCategoryService } from '@/services/procedureCategory';
import tariffTypeService from '@/services/tariffType';

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

vi.mock('@/services/procedureCategory', () => ({
  procedureCategoryService: {
    getProcedureCategories: vi.fn()
  }
}));

vi.mock('@/services/tariffType', () => ({
  default: {
    getTariffTypes: vi.fn()
  }
}));

describe('ProcedureView.vue', () => {
  const mockProceduresResponse = {
    data: {
      data: [
        {
          id: 1,
          code: 'P001',
          name: 'Pemeriksaan Umum',
          is_visible: true,
          procedure_category_id: 1,
          category: { id: 1, name: 'Poli Umum' },
          tariffs: [
            {
              id: 1,
              tariff_type_id: 10,
              total_amount: 50000,
              tariff_type: { id: 10, name: 'Umum' }
            }
          ]
        }
      ],
      meta: {
        current_page: 1,
        last_page: 2,
        per_page: 10,
        total: 15,
        from: 1,
        to: 10
      }
    }
  };

  const mockCategoriesResponse = {
    data: {
      data: [
        { id: 1, name: 'Poli Umum' },
        { id: 2, name: 'Poli Gigi' }
      ]
    }
  };

  const mockTariffTypesResponse = {
    data: {
      data: [
        { id: 10, name: 'Tarif Umum' },
        { id: 20, name: 'Tarif BPJS' }
      ]
    }
  };

  beforeEach(() => {
    vi.clearAllMocks();
    procedureService.getProcedures.mockResolvedValue(mockProceduresResponse);
    procedureCategoryService.getProcedureCategories.mockResolvedValue(mockCategoriesResponse);
    tariffTypeService.getTariffTypes.mockResolvedValue(mockTariffTypesResponse);
  });

  it('fetches categories, tariff types, and procedures on mount', async () => {
    const wrapper = mount(ProcedureView, {
      global: {
        stubs: {
          ProcedureFormModal: true,
          MasterDataDeleteDialog: true,
          Teleport: true
        }
      }
    });

    await flushPromises();

    expect(procedureCategoryService.getProcedureCategories).toHaveBeenCalledTimes(1);
    expect(tariffTypeService.getTariffTypes).toHaveBeenCalledTimes(1);
    expect(procedureService.getProcedures).toHaveBeenCalledTimes(1);

    expect(wrapper.findComponent({ name: 'ProcedureTable' }).exists()).toBe(true);
    expect(wrapper.findComponent({ name: 'ProcedureFilters' }).exists()).toBe(true);
  });

  it('filters procedures when category_id changes and resets page to 1', async () => {
    const wrapper = mount(ProcedureView, {
      global: {
        stubs: {
          ProcedureFormModal: true,
          MasterDataDeleteDialog: true,
          Teleport: true
        }
      }
    });

    await flushPromises();
    expect(procedureService.getProcedures).toHaveBeenCalledTimes(1);

    const filtersComp = wrapper.findComponent({ name: 'ProcedureFilters' });
    await filtersComp.vm.$emit('filter', { key: 'category_id', value: 2 });
    await flushPromises();

    expect(procedureService.getProcedures).toHaveBeenCalledTimes(2);
    expect(procedureService.getProcedures).toHaveBeenLastCalledWith(
      expect.objectContaining({
        page: 1,
        category_id: 2
      })
    );
  });

  it('filters procedures when tariff_type_id changes and resets page to 1', async () => {
    const wrapper = mount(ProcedureView, {
      global: {
        stubs: {
          ProcedureFormModal: true,
          MasterDataDeleteDialog: true,
          Teleport: true
        }
      }
    });

    await flushPromises();

    const filtersComp = wrapper.findComponent({ name: 'ProcedureFilters' });
    await filtersComp.vm.$emit('filter', { key: 'tariff_type_id', value: 20 });
    await flushPromises();

    expect(procedureService.getProcedures).toHaveBeenLastCalledWith(
      expect.objectContaining({
        page: 1,
        tariff_type_id: 20
      })
    );
  });

  it('supports combining category_id and tariff_type_id with AND logic', async () => {
    const wrapper = mount(ProcedureView, {
      global: {
        stubs: {
          ProcedureFormModal: true,
          MasterDataDeleteDialog: true,
          Teleport: true
        }
      }
    });

    await flushPromises();

    const filtersComp = wrapper.findComponent({ name: 'ProcedureFilters' });
    await filtersComp.vm.$emit('filter', { key: 'category_id', value: 1 });
    await flushPromises();

    await filtersComp.vm.$emit('filter', { key: 'tariff_type_id', value: 10 });
    await flushPromises();

    expect(procedureService.getProcedures).toHaveBeenLastCalledWith(
      expect.objectContaining({
        page: 1,
        category_id: 1,
        tariff_type_id: 10
      })
    );
  });

  it('omits category_id and tariff_type_id when "Semua" is selected', async () => {
    const wrapper = mount(ProcedureView, {
      global: {
        stubs: {
          ProcedureFormModal: true,
          MasterDataDeleteDialog: true,
          Teleport: true
        }
      }
    });

    await flushPromises();

    const filtersComp = wrapper.findComponent({ name: 'ProcedureFilters' });
    // Set filter
    await filtersComp.vm.$emit('filter', { key: 'category_id', value: 1 });
    await flushPromises();
    expect(procedureService.getProcedures).toHaveBeenLastCalledWith(
      expect.objectContaining({ category_id: 1 })
    );

    // Reset to Semua
    await filtersComp.vm.$emit('filter', { key: 'category_id', value: '' });
    await flushPromises();

    const lastCall = procedureService.getProcedures.mock.calls[procedureService.getProcedures.mock.calls.length - 1][0];
    expect(lastCall).not.toHaveProperty('category_id');
  });

  it('preserves active filters during pagination change and sorting', async () => {
    const wrapper = mount(ProcedureView, {
      global: {
        stubs: {
          ProcedureFormModal: true,
          MasterDataDeleteDialog: true,
          Teleport: true
        }
      }
    });

    await flushPromises();

    const filtersComp = wrapper.findComponent({ name: 'ProcedureFilters' });
    await filtersComp.vm.$emit('filter', { key: 'category_id', value: 2 });
    await flushPromises();

    // Trigger pagination change
    const paginationComp = wrapper.findComponent({ name: 'MasterDataPagination' });
    await paginationComp.vm.$emit('page-change', 2);
    await flushPromises();

    expect(procedureService.getProcedures).toHaveBeenLastCalledWith(
      expect.objectContaining({
        page: 2,
        category_id: 2
      })
    );

    // Trigger sort
    const tableComp = wrapper.findComponent({ name: 'ProcedureTable' });
    await tableComp.vm.$emit('sort', { column: 'name', direction: 'asc' });
    await flushPromises();

    expect(procedureService.getProcedures).toHaveBeenLastCalledWith(
      expect.objectContaining({
        sort_by: 'name',
        sort_dir: 'asc',
        category_id: 2
      })
    );
  });

  it('re-fetches categories, tariff types, and procedures when refresh is triggered', async () => {
    const wrapper = mount(ProcedureView, {
      global: {
        stubs: {
          ProcedureFormModal: true,
          MasterDataDeleteDialog: true,
          Teleport: true
        }
      }
    });

    await flushPromises();
    expect(procedureCategoryService.getProcedureCategories).toHaveBeenCalledTimes(1);
    expect(tariffTypeService.getTariffTypes).toHaveBeenCalledTimes(1);
    expect(procedureService.getProcedures).toHaveBeenCalledTimes(1);

    const filtersComp = wrapper.findComponent({ name: 'ProcedureFilters' });
    await filtersComp.vm.$emit('refresh');
    await flushPromises();

    expect(procedureCategoryService.getProcedureCategories).toHaveBeenCalledTimes(2);
    expect(tariffTypeService.getTariffTypes).toHaveBeenCalledTimes(2);
    expect(procedureService.getProcedures).toHaveBeenCalledTimes(2);
  });

  it('renders correct empty state when filtered vs when completely empty', async () => {
    // Empty result
    procedureService.getProcedures.mockResolvedValue({
      data: {
        data: [],
        meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 }
      }
    });

    const wrapper = mount(ProcedureView, {
      global: {
        stubs: {
          ProcedureFormModal: true,
          MasterDataDeleteDialog: true,
          Teleport: true
        }
      }
    });

    await flushPromises();

    // Completely empty (no filter)
    const emptyState = wrapper.findComponent({ name: 'MasterDataEmptyState' });
    expect(emptyState.exists()).toBe(true);
    expect(emptyState.props('isSearch')).toBe(false);
    expect(emptyState.props('title')).toBe('Belum ada data tindakan');

    // Now set a category filter
    const filtersComp = wrapper.findComponent({ name: 'ProcedureFilters' });
    await filtersComp.vm.$emit('filter', { key: 'category_id', value: 1 });
    await flushPromises();

    const updatedEmptyState = wrapper.findComponent({ name: 'MasterDataEmptyState' });
    expect(updatedEmptyState.props('isSearch')).toBe(true);
    expect(updatedEmptyState.props('searchMessage')).toBe('Tidak ada tindakan yang sesuai dengan filter.');
  });

  describe('Download Excel export', () => {
    it('triggers exportProcedures with current active filters and sorting', async () => {
      // Mock window.URL.createObjectURL and revokeObjectURL
      const mockCreateObjectURL = vi.fn().mockReturnValue('blob:http://localhost/mock-uuid');
      const mockRevokeObjectURL = vi.fn();
      window.URL.createObjectURL = mockCreateObjectURL;
      window.URL.revokeObjectURL = mockRevokeObjectURL;

      procedureService.exportProcedures.mockResolvedValue({
        data: new Blob(['mock binary xlsx content'], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }),
        headers: {
          'content-disposition': 'attachment; filename="data-tindakan-2026-10-14-114500.xlsx"'
        }
      });

      const wrapper = mount(ProcedureView, {
        global: {
          stubs: {
            ProcedureFormModal: true,
            MasterDataDeleteDialog: true,
            Teleport: true
          }
        }
      });

      await flushPromises();

      const filtersComp = wrapper.findComponent({ name: 'ProcedureFilters' });
      // Set filters
      await filtersComp.vm.$emit('filter', { key: 'category_id', value: 2 });
      await filtersComp.vm.$emit('filter', { key: 'tariff_type_id', value: 10 });
      await flushPromises();

      // Trigger export
      await filtersComp.vm.$emit('export');
      await flushPromises();

      expect(procedureService.exportProcedures).toHaveBeenCalledTimes(1);
      expect(procedureService.exportProcedures).toHaveBeenCalledWith(
        expect.objectContaining({
          category_id: 2,
          tariff_type_id: 10,
          sort_by: 'created_at',
          sort_dir: 'desc'
        })
      );
      // Ensure page and per_page are NOT passed
      const exportArgs = procedureService.exportProcedures.mock.calls[0][0];
      expect(exportArgs).not.toHaveProperty('page');
      expect(exportArgs).not.toHaveProperty('per_page');

      // Toast shows success
      const toast = wrapper.findComponent({ name: 'AppToast' });
      expect(toast.exists()).toBe(true);
      expect(toast.props('message')).toBe('Data tindakan berhasil diunduh.');
      expect(toast.props('type')).toBe('success');
    });

    it('shows info toast when export result has no data', async () => {
      procedureService.exportProcedures.mockResolvedValue({
        data: new Blob([JSON.stringify({ message: 'Tidak ada data tindakan yang dapat diunduh.' })], {
          type: 'application/json'
        }),
        headers: {
          'content-type': 'application/json'
        }
      });

      const wrapper = mount(ProcedureView, {
        global: {
          stubs: {
            ProcedureFormModal: true,
            MasterDataDeleteDialog: true,
            Teleport: true
          }
        }
      });

      await flushPromises();

      const filtersComp = wrapper.findComponent({ name: 'ProcedureFilters' });
      await filtersComp.vm.$emit('export');
      await flushPromises();

      const toast = wrapper.findComponent({ name: 'AppToast' });
      expect(toast.exists()).toBe(true);
      expect(toast.props('message')).toBe('Tidak ada data tindakan yang dapat diunduh.');
      expect(toast.props('type')).toBe('info');
    });

    it('shows error toast when export API call fails', async () => {
      procedureService.exportProcedures.mockRejectedValue(new Error('Network error'));

      const wrapper = mount(ProcedureView, {
        global: {
          stubs: {
            ProcedureFormModal: true,
            MasterDataDeleteDialog: true,
            Teleport: true
          }
        }
      });

      await flushPromises();

      const filtersComp = wrapper.findComponent({ name: 'ProcedureFilters' });
      await filtersComp.vm.$emit('export');
      await flushPromises();

      const toast = wrapper.findComponent({ name: 'AppToast' });
      expect(toast.exists()).toBe(true);
      expect(toast.props('message')).toBe('Data tindakan gagal diunduh. Silakan coba lagi.');
      expect(toast.props('type')).toBe('error');
    });
  });
});
