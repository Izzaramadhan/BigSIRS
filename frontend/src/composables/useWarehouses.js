import { ref } from 'vue';
import { warehouseService } from '@/services/master-data/warehouses.service';

export function useWarehouses() {
  const warehouses = ref([]);
  const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  });
  
  const loading = ref(false);
  const error = ref(null);

  const fetchWarehouses = async (params = {}) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await warehouseService.getWarehouses(params);
      warehouses.value = response.data;
      if (response.meta) {
        meta.value = response.meta;
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data gudang';
    } finally {
      loading.value = false;
    }
  };

  const createWarehouse = async (data) => {
    loading.value = true;
    error.value = null;
    try {
      await warehouseService.createWarehouse(data);
      return true;
    } catch (err) {
      error.value = err.response?.data?.errors || err.response?.data?.message || 'Gagal menambahkan gudang';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const updateWarehouse = async (id, data) => {
    loading.value = true;
    error.value = null;
    try {
      await warehouseService.updateWarehouse(id, data);
      return true;
    } catch (err) {
      error.value = err.response?.data?.errors || err.response?.data?.message || 'Gagal memperbarui gudang';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const deleteWarehouse = async (id) => {
    loading.value = true;
    error.value = null;
    try {
      await warehouseService.deleteWarehouse(id);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menghapus gudang';
      return false;
    } finally {
      loading.value = false;
    }
  };

  return {
    warehouses,
    meta,
    loading,
    error,
    fetchWarehouses,
    createWarehouse,
    updateWarehouse,
    deleteWarehouse
  };
}
