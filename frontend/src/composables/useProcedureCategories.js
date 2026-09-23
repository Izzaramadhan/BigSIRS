import { ref, reactive } from 'vue';
import { procedureCategoryService } from '../services/procedureCategory';

export function useProcedureCategories() {
  const items = ref([]);
  const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
  });
  const filters = reactive({
    search: '',
    is_active: null
  });
  const sort = reactive({
    column: 'name',
    direction: 'asc'
  });

  const loading = ref(false);
  const submitting = ref(false);
  const error = ref(null);

  const fetchProcedureCategories = async () => {
    loading.value = true;
    error.value = null;

    try {
      const params = {
        page: pagination.current_page,
        per_page: pagination.per_page,
        sort: sort.column,
        direction: sort.direction
      };

      if (filters.search) params.search = filters.search;
      if (filters.is_active !== null) params.is_active = filters.is_active;

      const response = await procedureCategoryService.getProcedureCategories(params);

      items.value = response.data.data;

      if (response.data.meta) {
        pagination.current_page = response.data.meta.current_page;
        pagination.last_page = response.data.meta.last_page;
        pagination.per_page = response.data.meta.per_page;
        pagination.total = response.data.meta.total;
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Data Kategori Tindakan gagal dimuat.';
    } finally {
      loading.value = false;
    }
  };

  const createProcedureCategory = async (payload) => {
    submitting.value = true;
    try {
      await procedureCategoryService.createProcedureCategory(payload);
      return { success: true };
    } catch (err) {
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const updateProcedureCategory = async (id, payload) => {
    submitting.value = true;
    try {
      await procedureCategoryService.updateProcedureCategory(id, payload);
      return { success: true };
    } catch (err) {
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const toggleStatus = async (id, isActive) => {
    try {
      await procedureCategoryService.updateProcedureCategoryStatus(id, isActive);
      return { success: true };
    } catch (err) {
      return { success: false, error: err };
    }
  };

  const archiveProcedureCategory = async (id) => {
    try {
      await procedureCategoryService.archiveProcedureCategory(id);
      return { success: true };
    } catch (err) {
      return { success: false, error: err };
    }
  };

  const setPage = (page) => {
    if (page >= 1 && page <= pagination.last_page) {
      pagination.current_page = page;
      fetchProcedureCategories();
    }
  };

  const setSort = (column) => {
    if (sort.column === column) {
      sort.direction = sort.direction === 'asc' ? 'desc' : 'asc';
    } else {
      sort.column = column;
      sort.direction = 'asc';
    }
    fetchProcedureCategories();
  };

  return {
    items,
    pagination,
    filters,
    sort,
    loading,
    submitting,
    error,
    fetchProcedureCategories,
    createProcedureCategory,
    updateProcedureCategory,
    toggleStatus,
    archiveProcedureCategory,
    setPage,
    setSort
  };
}
