import { ref, reactive, computed } from 'vue';
import { radiologyTypesService } from '@/services/master-data/radiologyTypes.service';

export function useRadiologyTypes() {
  const types = ref([]);
  const pagination = reactive({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
    from: 0,
    to: 0
  });
  const meta = ref({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1
  });

  const filters = reactive({
    search: ''
  });

  const sort = reactive({
    column: 'name',
    direction: 'asc'
  });

  const loading = ref(false);
  const submitting = ref(false);
  const error = ref(null);

  const fetchTypes = async (customParams = {}) => {
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

      if (filters.search && filters.search.trim()) {
        params.search = filters.search.trim();
      }

      const response = await radiologyTypesService.getAll(params);
      const resData = response.data;
      types.value = resData.data || [];

      if (resData.meta) {
        pagination.current_page = resData.meta.current_page || 1;
        pagination.per_page = resData.meta.per_page || 10;
        pagination.total = resData.meta.total || 0;
        pagination.last_page = resData.meta.last_page || 1;
        pagination.from = resData.meta.from ?? ((pagination.current_page - 1) * pagination.per_page + 1);
        pagination.to = resData.meta.to ?? Math.min(pagination.current_page * pagination.per_page, pagination.total);

        meta.value = { ...pagination };
      }

      return { success: true, data: resData };
    } catch (err) {
      const msg = err.response?.data?.message || 'Gagal memuat data tipe radiologi.';
      error.value = msg;
      return { success: false, error: err };
    } finally {
      loading.value = false;
    }
  };

  const createType = async (data) => {
    submitting.value = true;
    error.value = null;
    try {
      const response = await radiologyTypesService.create(data);
      return { success: true, data: response.data };
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal menyimpan tipe radiologi.';
      error.value = errorMessage;
      return { success: false, error: errorMessage, errors: err.response?.data?.errors };
    } finally {
      submitting.value = false;
    }
  };

  const updateType = async (id, data) => {
    submitting.value = true;
    error.value = null;
    try {
      const response = await radiologyTypesService.update(id, data);
      return { success: true, data: response.data };
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal memperbarui tipe radiologi.';
      error.value = errorMessage;
      return { success: false, error: errorMessage, errors: err.response?.data?.errors };
    } finally {
      submitting.value = false;
    }
  };

  const deleteType = async (id) => {
    submitting.value = true;
    error.value = null;
    try {
      await radiologyTypesService.delete(id);
      return { success: true };
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal menghapus tipe radiologi.';
      error.value = errorMessage;
      return { success: false, error: errorMessage };
    } finally {
      submitting.value = false;
    }
  };

  const setPage = (page) => {
    pagination.current_page = page;
    return fetchTypes();
  };

  const setSort = (column) => {
    if (sort.column === column) {
      sort.direction = sort.direction === 'asc' ? 'desc' : 'asc';
    } else {
      sort.column = column;
      sort.direction = 'asc';
    }
    pagination.current_page = 1;
    return fetchTypes();
  };

  return {
    items: types,
    types,
    pagination,
    meta,
    filters,
    sort,
    loading,
    submitting,
    error,
    fetchTypes,
    createType,
    updateType,
    deleteType,
    setPage,
    setSort,
    isEmpty: computed(() => !loading.value && types.value.length === 0)
  };
}
