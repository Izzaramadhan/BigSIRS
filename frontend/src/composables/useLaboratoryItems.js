import { ref, reactive } from 'vue';
import { laboratoryItemsService } from '@/services/master-data/laboratoryItems.service';

export function useLaboratoryItems() {
  const items = ref([]);
  const pagination = reactive({
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

  const fetchLaboratoryItems = async (params = {}) => {
    loading.value = true;
    error.value = null;

    try {
      const queryParams = {
        page: pagination.current_page,
        per_page: pagination.per_page,
        sort_by: sort.column,
        sort_direction: sort.direction,
        ...params
      };

      if (filters.search && filters.search.trim()) {
        queryParams.search = filters.search.trim();
      }

      const response = await laboratoryItemsService.list(queryParams);

      items.value = response.data || [];

      if (response.meta) {
        pagination.current_page = response.meta.current_page || 1;
        pagination.per_page = response.meta.per_page || 10;
        pagination.total = response.meta.total || 0;
        pagination.last_page = response.meta.last_page || 1;
      }

      return { success: true, data: response };
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data item lab.';
      return { success: false, error: err };
    } finally {
      loading.value = false;
    }
  };

  const createLaboratoryItem = async (payload) => {
    submitting.value = true;
    try {
      const response = await laboratoryItemsService.create(payload);
      return { success: true, data: response };
    } catch (err) {
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const updateLaboratoryItem = async (id, payload) => {
    submitting.value = true;
    try {
      const response = await laboratoryItemsService.update(id, payload);
      return { success: true, data: response };
    } catch (err) {
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const deleteLaboratoryItem = async (id) => {
    submitting.value = true;
    try {
      const response = await laboratoryItemsService.remove(id);
      return { success: true, data: response };
    } catch (err) {
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const setPage = (page) => {
    pagination.current_page = page;
    fetchLaboratoryItems();
  };

  const setSort = (column) => {
    if (sort.column === column) {
      sort.direction = sort.direction === 'asc' ? 'desc' : 'asc';
    } else {
      sort.column = column;
      sort.direction = 'asc';
    }
    pagination.current_page = 1;
    fetchLaboratoryItems();
  };

  return {
    items,
    laboratoryItems: items, // backward-compatibility alias for LaboratoryGroupFormModal.vue
    pagination,
    meta: pagination, // backward-compatibility alias
    filters,
    sort,
    loading,
    submitting,
    error,
    fetchLaboratoryItems,
    fetchItems: fetchLaboratoryItems, // backward-compatibility alias
    createLaboratoryItem,
    updateLaboratoryItem,
    deleteLaboratoryItem,
    setPage,
    setSort
  };
}
