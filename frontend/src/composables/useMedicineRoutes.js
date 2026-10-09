import { ref } from 'vue';
import { medicineRoutesService } from '@/services/master-data/medicineRoutes.service';

export function useMedicineRoutes() {
  const medicineRoutes = ref([]);
  const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  });
  
  const loading = ref(false);
  const error = ref(null);

  const fetchMedicineRoutes = async (params = {}) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await medicineRoutesService.getMedicineRoutes(params);
      medicineRoutes.value = response.data;
      if (response.meta) {
        meta.value = response.meta;
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data kategori obat';
    } finally {
      loading.value = false;
    }
  };

  const createMedicineRoute = async (data) => {
    loading.value = true;
    error.value = null;
    try {
      await medicineRoutesService.createMedicineRoute(data);
      return true;
    } catch (err) {
      error.value = err.response?.data?.errors || err.response?.data?.message || 'Gagal menambahkan kategori obat';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const updateMedicineRoute = async (id, data) => {
    loading.value = true;
    error.value = null;
    try {
      await medicineRoutesService.updateMedicineRoute(id, data);
      return true;
    } catch (err) {
      error.value = err.response?.data?.errors || err.response?.data?.message || 'Gagal memperbarui kategori obat';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const deleteMedicineRoute = async (id) => {
    loading.value = true;
    error.value = null;
    try {
      await medicineRoutesService.deleteMedicineRoute(id);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menghapus kategori obat';
      return false;
    } finally {
      loading.value = false;
    }
  };

  return {
    medicineRoutes,
    meta,
    loading,
    error,
    fetchMedicineRoutes,
    createMedicineRoute,
    updateMedicineRoute,
    deleteMedicineRoute
  };
}
