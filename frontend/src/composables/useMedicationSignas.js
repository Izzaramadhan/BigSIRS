import { ref, reactive, computed } from 'vue';
import { medicationSignasService } from '@/services/master-data/medicationSignas.service';

export function useMedicationSignas() {
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
    last_page: 1,
    from: null,
    to: null
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

  const fetchMedicationSignas = async (customParams = {}) => {
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

      const response = await medicationSignasService.getMedicationSignas(params);

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
      const msg = err.response?.data?.message || 'Gagal memuat data signa obat.';
      error.value = msg;
      return { success: false, error: err };
    } finally {
      loading.value = false;
    }
  };

  const createMedicationSigna = async (payload) => {
    submitting.value = true;
    error.value = null;

    try {
      const response = await medicationSignasService.createMedicationSigna(payload);
      return { success: true, data: response };
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal menambahkan signa obat.';
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

  const updateMedicationSigna = async (id, payload) => {
    submitting.value = true;
    error.value = null;

    try {
      const response = await medicationSignasService.updateMedicationSigna(id, payload);
      return { success: true, data: response };
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal memperbarui signa obat.';
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

  const deleteMedicationSigna = async (id) => {
    submitting.value = true;
    error.value = null;

    try {
      await medicationSignasService.deleteMedicationSigna(id);
      return { success: true };
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal menghapus signa obat.';
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
      return fetchMedicationSignas();
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
    return fetchMedicationSignas();
  };

  return {
    items,
    medicationSignas: items,
    pagination,
    meta,
    filters,
    sort,
    loading,
    submitting,
    error,
    fetchMedicationSignas,
    createMedicationSigna,
    updateMedicationSigna,
    deleteMedicationSigna,
    setPage,
    setSort,
    isEmpty: computed(() => !loading.value && items.value.length === 0)
  };
}
