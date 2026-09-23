import { ref, reactive } from 'vue';
import { polyclinicService } from '../services/polyclinic';

export function usePolyclinics() {
  const items = ref([]);
  const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
  });
  const filters = reactive({
    search: '',
    is_active: null,
    service_type: null,
  });
  const sort = reactive({
    column: 'name',
    direction: 'asc'
  });

  const loading = ref(false);
  const submitting = ref(false);
  const error = ref(null);

  const serviceTypes = ref([]);

  const fetchServiceTypes = async () => {
    try {
      const response = await polyclinicService.getServiceTypes();
      serviceTypes.value = response.data.data;
    } catch {
      serviceTypes.value = [];
    }
  };

  const fetchPolyclinics = async () => {
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
      if (filters.service_type) params.service_type = filters.service_type;

      const response = await polyclinicService.getPolyclinics(params);

      items.value = response.data.data;

      if (response.data.meta) {
        pagination.current_page = response.data.meta.current_page;
        pagination.last_page = response.data.meta.last_page;
        pagination.per_page = response.data.meta.per_page;
        pagination.total = response.data.meta.total;
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Data Poliklinik gagal dimuat.';
    } finally {
      loading.value = false;
    }
  };

  const createPolyclinic = async (payload) => {
    submitting.value = true;
    try {
      await polyclinicService.createPolyclinic(payload);
      return { success: true };
    } catch (err) {
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const updatePolyclinic = async (id, payload) => {
    submitting.value = true;
    try {
      await polyclinicService.updatePolyclinic(id, payload);
      return { success: true };
    } catch (err) {
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const toggleStatus = async (id, isActive) => {
    try {
      await polyclinicService.updatePolyclinicStatus(id, isActive);
      return { success: true };
    } catch (err) {
      return { success: false, error: err };
    }
  };

  const archivePolyclinic = async (id) => {
    try {
      await polyclinicService.archivePolyclinic(id);
      return { success: true };
    } catch (err) {
      return { success: false, error: err };
    }
  };

  const setPage = (page) => {
    if (page >= 1 && page <= pagination.last_page) {
      pagination.current_page = page;
      fetchPolyclinics();
    }
  };

  const setSort = (column) => {
    if (sort.column === column) {
      sort.direction = sort.direction === 'asc' ? 'desc' : 'asc';
    } else {
      sort.column = column;
      sort.direction = 'asc';
    }
    fetchPolyclinics();
  };

  return {
    items,
    pagination,
    filters,
    sort,
    loading,
    submitting,
    error,
    serviceTypes,
    fetchPolyclinics,
    fetchServiceTypes,
    createPolyclinic,
    updatePolyclinic,
    toggleStatus,
    archivePolyclinic,
    setPage,
    setSort
  };
}
