import { ref } from 'vue';
import { SupplierService } from '@/services/master-data/suppliers.service';

export function useSuppliers() {
  const suppliers = ref([]);
  const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  });
  
  const loading = ref(false);
  const error = ref(null);

  const fetchSuppliers = async (params = {}) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await SupplierService.getSuppliers(params);
      suppliers.value = response.data;
      if (response.meta) {
        meta.value = response.meta;
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data supplier';
    } finally {
      loading.value = false;
    }
  };

  const createSupplier = async (data) => {
    loading.value = true;
    error.value = null;
    try {
      await SupplierService.createSupplier(data);
      return true;
    } catch (err) {
      error.value = err.response?.data?.errors || err.response?.data?.message || 'Gagal menambahkan supplier';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const updateSupplier = async (id, data) => {
    loading.value = true;
    error.value = null;
    try {
      await SupplierService.updateSupplier(id, data);
      return true;
    } catch (err) {
      error.value = err.response?.data?.errors || err.response?.data?.message || 'Gagal memperbarui supplier';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const deleteSupplier = async (id) => {
    loading.value = true;
    error.value = null;
    try {
      await SupplierService.deleteSupplier(id);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menghapus supplier';
      return false;
    } finally {
      loading.value = false;
    }
  };

  return {
    suppliers,
    meta,
    loading,
    error,
    fetchSuppliers,
    createSupplier,
    updateSupplier,
    deleteSupplier
  };
}
