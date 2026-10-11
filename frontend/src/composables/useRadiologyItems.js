import { ref, reactive, computed } from 'vue';
import { radiologyItemsService } from '@/services/master-data/radiologyItems.service';

export function useRadiologyItems() {
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
    search: ''
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
        sortBy: sort.column,
        sortDesc: sort.direction === 'desc',
        ...customParams
      };

      if (filters.search && filters.search.trim()) {
        params.search = filters.search.trim();
      }

      const response = await radiologyItemsService.getItems(params);
      const rawData = response?.data !== undefined ? response.data : response;
      const itemsList = Array.isArray(rawData)
        ? rawData
        : (Array.isArray(rawData?.data) ? rawData.data : []);
      items.value = itemsList;

      const metaSource = response?.meta || rawData?.meta;
      if (metaSource) {
        pagination.current_page = metaSource.current_page || 1;
        pagination.per_page = metaSource.per_page || 10;
        pagination.total = metaSource.total || 0;
        pagination.last_page = metaSource.last_page || 1;
        pagination.from = metaSource.from ?? ((pagination.current_page - 1) * pagination.per_page + 1);
        pagination.to = metaSource.to ?? Math.min(pagination.current_page * pagination.per_page, pagination.total);

        meta.value = { ...pagination };
      }

      return { success: true, data: response };
    } catch (err) {
      const msg = err.response?.data?.message || 'Gagal memuat data item radiologi.';
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
      const response = await radiologyItemsService.createItem(data);
      return { success: true, data: response.data || response };
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal menyimpan item radiologi.';
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
      const response = await radiologyItemsService.updateItem(id, data);
      return { success: true, data: response.data || response };
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal memperbarui item radiologi.';
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
      await radiologyItemsService.deleteItem(id);
      return { success: true };
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal menghapus item radiologi.';
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

  const handlePageChange = (page) => {
    pagination.current_page = page;
    fetchItems();
  };

  const handleLimitChange = (limit) => {
    pagination.current_page = 1;
    pagination.per_page = limit;
    fetchItems();
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
    handlePageChange,
    handleLimitChange,
    isEmpty: computed(() => !loading.value && items.value.length === 0)
  };
}
