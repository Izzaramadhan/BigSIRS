import { ref, reactive } from 'vue';
import doctorService from '../services/master-data/doctors.service';

export function useDoctors() {
  const items = ref([]);
  const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
  });
  const filters = reactive({
    search: '',
    specialization_id: null,
  });
  const sort = reactive({
    column: 'created_at',
    direction: 'desc'
  });

  const loading = ref(false);
  const submitting = ref(false);
  const error = ref(null);

  const fetchDoctors = async () => {
    loading.value = true;
    error.value = null;

    try {
      const params = {
        page: pagination.current_page,
        per_page: pagination.per_page,
        sort_by: sort.column,
        sort_desc: sort.direction === 'desc' ? 1 : 0
      };

      if (filters.search) params.search = filters.search;
      if (filters.specialization_id) params.specialization_id = filters.specialization_id;

      const response = await doctorService.getDoctors(params);

      items.value = response.data;

      if (response.meta) {
        pagination.current_page = response.meta.current_page;
        pagination.last_page = response.meta.last_page;
        pagination.per_page = response.meta.per_page;
        pagination.total = response.meta.total;
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Data Dokter gagal dimuat.';
    } finally {
      loading.value = false;
    }
  };

  const createDoctor = async (payload) => {
    submitting.value = true;
    try {
      await doctorService.createDoctor(payload);
      return { success: true };
    } catch (err) {
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const updateDoctor = async (id, payload) => {
    submitting.value = true;
    try {
      await doctorService.updateDoctor(id, payload);
      return { success: true };
    } catch (err) {
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };



  const deleteDoctor = async (id) => {
    try {
      await doctorService.deleteDoctor(id);
      return { success: true };
    } catch (err) {
      return { success: false, error: err };
    }
  };

  const setPage = (page) => {
    if (page >= 1 && page <= pagination.last_page) {
      pagination.current_page = page;
      fetchDoctors();
    }
  };

  const setSort = (column) => {
    if (sort.column === column) {
      sort.direction = sort.direction === 'asc' ? 'desc' : 'asc';
    } else {
      sort.column = column;
      sort.direction = 'asc';
    }
    fetchDoctors();
  };

  return {
    items,
    pagination,
    filters,
    sort,
    loading,
    submitting,
    error,
    fetchDoctors,
    createDoctor,
    updateDoctor,
    deleteDoctor,
    setPage,
    setSort
  };
}
