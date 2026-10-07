import { ref } from 'vue';
import { positionsService } from '../services/master-data/positions.service';

export function usePositions() {
  const positions = ref([]);
  const loading = ref(false);
  const error = ref(null);
  const meta = ref({
    current_page: 1,
    last_page: 1,
    total: 0
  });

  const fetchPositions = async (params = {}) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await positionsService.getPositions(params);
      positions.value = response.data;
      if (response.meta) {
        meta.value = response.meta;
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data jabatan';
      console.error('Error fetching positions:', err);
    } finally {
      loading.value = false;
    }
  };

  const deletePosition = async (id) => {
    try {
      await positionsService.deletePosition(id);
      return { success: true };
    } catch (err) {
      return {
        success: false,
        error: err.response?.data?.message || 'Gagal menghapus data jabatan'
      };
    }
  };


  return {
    positions,
    loading,
    error,
    meta,
    fetchPositions,
    deletePosition
  };
}
