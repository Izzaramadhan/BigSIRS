import { ref, reactive, computed } from 'vue';
import stockOpnamesService from '@/services/logistics/stockOpnames.service';

export function useStockOpnames() {
  const stocks = ref([]);
  const warehouses = ref([]);

  const loading = ref(false);
  const submitting = ref(false);
  const error = ref(null);

  const defaultFilters = () => ({
    warehouse_id: '',
    hide_zero_stock: true,
    search: '',
    page: 1,
    per_page: 10
  });

  const filters = reactive(defaultFilters());
  const appliedFilters = reactive(defaultFilters());

  const paginationInfo = ref({
    current_page: 1,
    per_page: 10,
    last_page: 1,
    total: 0,
    from: 0,
    to: 0
  });

  const hasActiveFilters = computed(() => {
    return Boolean(
      (appliedFilters.warehouse_id && appliedFilters.warehouse_id !== '' && appliedFilters.warehouse_id !== 'all') ||
      appliedFilters.hide_zero_stock === false ||
      (appliedFilters.search && appliedFilters.search.trim() !== '')
    );
  });

  // Alias for backward compatibility if components reference `opnames` or `paginatedOpnames`
  const opnames = computed(() => stocks.value);
  const paginatedOpnames = computed(() => stocks.value);

  /**
   * Fetch active warehouses for dropdown filters
   */
  const fetchWarehouses = async () => {
    try {
      const res = await stockOpnamesService.getWarehousesLookup();
      if (Array.isArray(res)) {
        warehouses.value = res;
      } else if (res && res.data) {
        warehouses.value = res.data;
      }
    } catch {
      // Keep existing warehouses if request fails
    }
  };

  /**
   * Fetch direct stock opname records from backend API
   */
  const fetchStockOpnames = async () => {
    loading.value = true;
    error.value = null;

    try {
      const params = {
        page: filters.page,
        per_page: filters.per_page,
        hide_zero_stock: filters.hide_zero_stock ? 1 : 0
      };

      if (filters.warehouse_id && filters.warehouse_id !== 'all') {
        params.warehouse_id = filters.warehouse_id;
      }

      if (filters.search && filters.search.trim() !== '') {
        params.search = filters.search.trim();
      }

      const res = await stockOpnamesService.getStockOpnames(params);

      if (res && res.data) {
        stocks.value = res.data;

        if (res.meta) {
          paginationInfo.value = {
            current_page: res.meta.current_page || 1,
            per_page: res.meta.per_page || filters.per_page,
            last_page: res.meta.last_page || 1,
            total: res.meta.total || 0,
            from: res.meta.from || 0,
            to: res.meta.to || 0
          };
        } else {
          paginationInfo.value = {
            current_page: 1,
            per_page: stocks.value.length,
            last_page: 1,
            total: stocks.value.length,
            from: stocks.value.length > 0 ? 1 : 0,
            to: stocks.value.length
          };
        }
      } else if (Array.isArray(res)) {
        stocks.value = res;
        paginationInfo.value = {
          current_page: 1,
          per_page: res.length,
          last_page: 1,
          total: res.length,
          from: res.length > 0 ? 1 : 0,
          to: res.length
        };
      } else {
        stocks.value = [];
      }

      // Sync applied filters
      Object.assign(appliedFilters, {
        warehouse_id: filters.warehouse_id,
        hide_zero_stock: filters.hide_zero_stock,
        search: filters.search,
        page: filters.page,
        per_page: filters.per_page
      });
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Gagal memuat saldo stok.';
      stocks.value = [];
    } finally {
      loading.value = false;
    }
  };

  /**
   * Apply filters
   */
  const applyFilters = async (newFilters = {}) => {
    Object.assign(filters, newFilters, { page: 1 });
    await fetchStockOpnames();
  };

  /**
   * Reset filters to defaults
   */
  const resetFilters = async () => {
    Object.assign(filters, defaultFilters());
    await fetchStockOpnames();
  };

  /**
   * Set active page
   */
  const setPage = async (page) => {
    if (page < 1 || (paginationInfo.value.last_page && page > paginationInfo.value.last_page)) {
      return;
    }
    filters.page = page;
    await fetchStockOpnames();
  };

  /**
   * Set per-page amount
   */
  const setPerPage = async (perPage) => {
    filters.per_page = Number(perPage);
    filters.page = 1;
    await fetchStockOpnames();
  };

  /**
   * Update stock via Edit modal
   */
  const updateStock = async (id, payload) => {
    submitting.value = true;
    error.value = null;

    try {
      const res = await stockOpnamesService.updateStockOpname(id, payload);
      await fetchStockOpnames();
      return {
        success: true,
        data: res.data || res,
        message: res.message || 'Stok opname berhasil diperbarui.'
      };
    } catch (err) {
      const apiErrors = err.response?.data?.errors;
      const message = err.response?.data?.message || err.message || 'Gagal memperbarui stok sediaan.';
      return {
        success: false,
        message,
        errors: apiErrors || {}
      };
    } finally {
      submitting.value = false;
    }
  };

  /**
   * Export stock opnames to CSV
   */
  const exportStocksCsv = async () => {
    try {
      const params = {
        hide_zero_stock: filters.hide_zero_stock ? 1 : 0
      };
      if (filters.warehouse_id && filters.warehouse_id !== 'all') {
        params.warehouse_id = filters.warehouse_id;
      }
      if (filters.search && filters.search.trim() !== '') {
        params.search = filters.search.trim();
      }

      const blob = await stockOpnamesService.exportCsv(params);
      const url = window.URL.createObjectURL(new Blob([blob], { type: 'text/csv' }));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `data-stok-opname-${new Date().toISOString().slice(0, 10)}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      return { success: true };
    } catch (err) {
      return {
        success: false,
        message: err.message || 'Gagal mengekspor data stok.'
      };
    }
  };

  return {
    stocks,
    opnames,
    paginatedOpnames,
    warehouses,
    loading,
    submitting,
    error,
    filters,
    appliedFilters,
    hasActiveFilters,
    paginationInfo,
    fetchWarehouses,
    fetchStockOpnames,
    applyFilters,
    resetFilters,
    setPage,
    setPerPage,
    updateStock,
    exportStocksCsv
  };
}

export default useStockOpnames;
