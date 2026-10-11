import { ref, reactive, computed } from 'vue';
import { medicinesService } from '@/services/master-data/medicines.service';

export function useMedicines() {
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
    category_id: '',
    classification_id: ''
  });

  const sort = reactive({
    column: 'name',
    direction: 'asc'
  });

  const loading = ref(false);
  const submitting = ref(false);
  const error = ref(null);

  const fetchMedicines = async (customParams = {}) => {
    loading.value = true;
    error.value = null;

    try {
      const params = {
        page: pagination.current_page,
        per_page: pagination.per_page,
        sortBy: sort.column,
        sortDesc: sort.direction === 'desc',
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

      if (filters.classification_id) {
        params.classification_id = filters.classification_id;
      }

      const response = await medicinesService.getMedicines(params);

      const dataList = response.data || [];
      items.value = Array.isArray(dataList) ? dataList : (dataList.data || []);

      if (response.meta) {
        pagination.current_page = response.meta.current_page || 1;
        pagination.per_page = response.meta.per_page || 10;
        pagination.total = response.meta.total || 0;
        pagination.last_page = response.meta.last_page || 1;
        pagination.from = response.meta.from ?? ((pagination.current_page - 1) * pagination.per_page + 1);
        pagination.to = response.meta.to ?? Math.min(pagination.current_page * pagination.per_page, pagination.total);

        meta.value = { ...pagination };
      } else {
        pagination.total = items.value.length;
        pagination.from = items.value.length > 0 ? 1 : 0;
        pagination.to = items.value.length;
        pagination.last_page = 1;
        meta.value = { ...pagination };
      }

      return { success: true, data: response };
    } catch (err) {
      const msg = err.response?.data?.message || 'Gagal memuat data obat.';
      error.value = msg;
      return { success: false, error: err };
    } finally {
      loading.value = false;
    }
  };

  const createMedicine = async (payload) => {
    submitting.value = true;
    error.value = null;

    try {
      const response = await medicinesService.createMedicine(payload);
      return { success: true, data: response };
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal menambahkan obat.';
      error.value = errorMessage;
      return {
        success: false,
        error: err,
        errors: err.response?.data?.errors
      };
    } finally {
      submitting.value = false;
    }
  };

  const updateMedicine = async (id, payload) => {
    submitting.value = true;
    error.value = null;

    try {
      const response = await medicinesService.updateMedicine(id, payload);
      return { success: true, data: response };
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal memperbarui obat.';
      error.value = errorMessage;
      return {
        success: false,
        error: err,
        errors: err.response?.data?.errors
      };
    } finally {
      submitting.value = false;
    }
  };

  const deleteMedicine = async (id) => {
    submitting.value = true;
    error.value = null;

    try {
      await medicinesService.deleteMedicine(id);
      return { success: true };
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal menghapus obat.';
      error.value = errorMessage;
      return {
        success: false,
        error: err
      };
    } finally {
      submitting.value = false;
    }
  };

  const setPage = (page) => {
    if (page >= 1 && page <= pagination.last_page) {
      pagination.current_page = page;
      return fetchMedicines();
    }
  };

  const setSort = (column) => {
    if (sort.column === column) {
      sort.direction = sort.direction === 'asc' ? 'desc' : 'asc';
    } else {
      sort.column = column;
      sort.direction = 'asc';
    }
    pagination.current_page = 1;
    return fetchMedicines();
  };

  return {
    items,
    medicines: items,
    pagination,
    meta,
    filters,
    sort,
    loading,
    submitting,
    error,
    fetchMedicines,
    fetchItems: fetchMedicines,
    createMedicine,
    createItem: createMedicine,
    updateMedicine,
    updateItem: updateMedicine,
    deleteMedicine,
    deleteItem: deleteMedicine,
    setPage,
    setSort,
    isEmpty: computed(() => !loading.value && items.value.length === 0)
  };
}
