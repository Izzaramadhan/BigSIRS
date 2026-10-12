import { ref, reactive } from 'vue';
import { positionsService } from '../services/master-data/positions.service';

export function usePositions() {
  const items = ref([]);
  const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  });
  const filters = reactive({
    search: ''
  });

  const loading = ref(false);
  const submitting = ref(false);
  const error = ref(null);

  const fetchPositions = async () => {
    loading.value = true;
    error.value = null;
    
    try {
      const params = {
        page: pagination.current_page,
        per_page: pagination.per_page
      };

      if (filters.search) params.search = filters.search;

      const response = await positionsService.getPositions(params);
      
      items.value = response.data;
      if (response.meta) {
        pagination.current_page = response.meta.current_page;
        pagination.last_page = response.meta.last_page;
        pagination.per_page = response.meta.per_page;
        pagination.total = response.meta.total;
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data jabatan.';
      console.error('Error fetching positions:', err);
    } finally {
      loading.value = false;
    }
  };

  const createPosition = async (payload) => {
    submitting.value = true;
    try {
      await positionsService.createPosition(payload);
      return { success: true };
    } catch (err) {
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const updatePosition = async (id, payload) => {
    submitting.value = true;
    try {
      await positionsService.updatePosition(id, payload);
      return { success: true };
    } catch (err) {
      return { success: false, error: err };
    } finally {
      submitting.value = false;
    }
  };

  const deletePosition = async (id) => {
    submitting.value = true;
    try {
      await positionsService.deletePosition(id);
      return { success: true };
    } catch (err) {
      return {
        success: false,
        error: err
      };
    } finally {
      submitting.value = false;
    }
  };

  const setPage = (page) => {
    pagination.current_page = page;
    fetchPositions();
  };

  return {
    items,
    pagination,
    filters,
    loading,
    submitting,
    error,
    fetchPositions,
    createPosition,
    updatePosition,
    deletePosition,
    setPage
  };
}
