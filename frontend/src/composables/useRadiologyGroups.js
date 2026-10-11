import { ref, reactive, computed } from 'vue';
import { radiologyGroupsService } from '@/services/master-data/radiologyGroups.service';

export function useRadiologyGroups() {
  const items = ref([]);
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
    search: '',
    category_id: null,
    type_id: null
  });

  const sort = reactive({
    column: 'name',
    direction: 'asc'
  });

  const loading = ref(false);
  const submitting = ref(false);
  const error = ref(null);

  const fetchItems = async (customParams = {}) => {
    loading.value = true;
    error.value = null;
    try {
      const params = {
        page: pagination.current_page,
        per_page: pagination.per_page,
        sort: sort.column,
        direction: sort.direction,
        ...customParams
      };

      if (filters.search && filters.search.trim()) {
        params.search = filters.search.trim();
      }

      if (filters.category_id) {
        params.category_id = filters.category_id;
      }

      if (filters.type_id) {
        params.type_id = filters.type_id;
      }

      const response = await radiologyGroupsService.getAll(params);
      const resData = response.data;
      const rawItems = Array.isArray(resData) ? resData : (resData.data || []);
      items.value = rawItems;

      const metaSource = resData.meta || response.meta;
      if (metaSource) {
        pagination.current_page = metaSource.current_page || 1;
        pagination.per_page = metaSource.per_page || 10;
        pagination.total = metaSource.total || 0;
        pagination.last_page = metaSource.last_page || 1;
        pagination.from = metaSource.from ?? ((pagination.current_page - 1) * pagination.per_page + 1);
        pagination.to = metaSource.to ?? Math.min(pagination.current_page * pagination.per_page, pagination.total);

        meta.value = { ...pagination };
      }

      return { success: true, data: resData };
    } catch (err) {
      const msg = err.response?.data?.message || 'Gagal memuat data grup radiologi.';
      error.value = msg;
      return { success: false, error: err };
    } finally {
      loading.value = false;
    }
  };

  const createItem = async (data) => {
    submitting.value = true;
    error.value = null;
    try {
      const response = await radiologyGroupsService.create(data);
      return { success: true, data: response.data?.data || response.data };
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal menyimpan grup radiologi.';
      error.value = errorMessage;
      return { success: false, error: errorMessage, errors: err.response?.data?.errors };
    } finally {
      submitting.value = false;
    }
  };

  const updateItem = async (id, data) => {
    submitting.value = true;
    error.value = null;
    try {
      const response = await radiologyGroupsService.update(id, data);
      return { success: true, data: response.data?.data || response.data };
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal memperbarui grup radiologi.';
      error.value = errorMessage;
      return { success: false, error: errorMessage, errors: err.response?.data?.errors };
    } finally {
      submitting.value = false;
    }
  };

  const deleteItem = async (id) => {
    submitting.value = true;
    error.value = null;
    try {
      await radiologyGroupsService.delete(id);
      return { success: true };
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal menghapus grup radiologi.';
      error.value = errorMessage;
      return { success: false, error: errorMessage };
    } finally {
      submitting.value = false;
    }
  };

  const setPage = (page) => {
    pagination.current_page = page;
    return fetchItems();
  };

  const setSort = (column) => {
    if (sort.column === column) {
      sort.direction = sort.direction === 'asc' ? 'desc' : 'asc';
    } else {
      sort.column = column;
      sort.direction = 'asc';
    }
    pagination.current_page = 1;
    return fetchItems();
  };

  return {
    items,
    pagination,
    meta,
    filters,
    sort,
    loading,
    submitting,
    error,
    fetchItems,
    createItem,
    updateItem,
    deleteItem,
    setPage,
    setSort,
    isEmpty: computed(() => !loading.value && items.value.length === 0)
  };
}
