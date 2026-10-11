import { ref, reactive } from 'vue';
import { laboratoryCategoriesService } from '@/services/master-data/laboratoryCategories.service';

export function useLaboratoryCategories() {
  const items = ref([]);
  const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0
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

  const fetchLaboratoryCategories = async (customParams = {}) => {
    loading.value = true;
    error.value = null;

    try {
      const params = {
        page: pagination.current_page,
        per_page: pagination.per_page,
        sortBy: sort.column,
        sortDesc: sort.direction === 'desc',
        ...customParams
      };

      if (filters.search) {
        params.search = filters.search;
      }

      if (filters.is_active !== null && filters.is_active !== '') {
        params.is_active = filters.is_active;
      }

      const response = await laboratoryCategoriesService.getLaboratoryCategories(params);
      items.value = response.data || [];

      if (response.meta) {
        pagination.current_page = response.meta.current_page;
        pagination.last_page = response.meta.last_page;
        pagination.per_page = response.meta.per_page;
        pagination.total = response.meta.total;
        pagination.from = response.meta.from ?? ((response.meta.current_page - 1) * response.meta.per_page + 1);
        pagination.to = response.meta.to ?? Math.min(response.meta.current_page * response.meta.per_page, response.meta.total);
      }

      return response;
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Gagal memuat data kategori lab.';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const createLaboratoryCategory = async (payload) => {
    submitting.value = true;
    error.value = null;
    try {
      const response = await laboratoryCategoriesService.createLaboratoryCategory(payload);
      return { success: true, data: response };
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Gagal menambahkan kategori lab.';
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const updateLaboratoryCategory = async (id, payload) => {
    submitting.value = true;
    error.value = null;
    try {
      const response = await laboratoryCategoriesService.updateLaboratoryCategory(id, payload);
      return { success: true, data: response };
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Gagal memperbarui kategori lab.';
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const updateStatus = async (id, isActive) => {
    submitting.value = true;
    error.value = null;
    try {
      const response = await laboratoryCategoriesService.updateStatus(id, isActive);
      return { success: true, data: response };
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Gagal memperbarui status kategori lab.';
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const deleteLaboratoryCategory = async (id) => {
    submitting.value = true;
    error.value = null;
    try {
      await laboratoryCategoriesService.deleteLaboratoryCategory(id);
      return { success: true };
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Gagal menghapus kategori lab.';
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const setPage = (page) => {
    if (page >= 1 && page <= pagination.last_page) {
      pagination.current_page = page;
      fetchLaboratoryCategories();
    }
  };

  const setSort = (column) => {
    if (sort.column === column) {
      sort.direction = sort.direction === 'asc' ? 'desc' : 'asc';
    } else {
      sort.column = column;
      sort.direction = 'asc';
    }
    fetchLaboratoryCategories();
  };

  return {
    items,
    laboratoryCategories: items, // Alias for backwards compatibility
    pagination,
    meta: pagination, // Alias for backwards compatibility
    filters,
    sort,
    loading,
    submitting,
    error,
    fetchLaboratoryCategories,
    createLaboratoryCategory,
    updateLaboratoryCategory,
    updateStatus,
    deleteLaboratoryCategory,
    setPage,
    setSort
  };
}
