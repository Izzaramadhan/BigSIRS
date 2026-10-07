import { ref } from 'vue';
import { laboratoryCategoriesService } from '@/services/master-data/laboratoryCategories.service';

export function useLaboratoryCategories() {
  const laboratoryCategories = ref([]);
  const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  });
  
  const loading = ref(false);
  const error = ref(null);

  const fetchLaboratoryCategories = async (params = {}) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await laboratoryCategoriesService.getLaboratoryCategories(params);
      laboratoryCategories.value = response.data;
      if (response.meta) {
        meta.value = response.meta;
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data kategori lab';
    } finally {
      loading.value = false;
    }
  };

  const createLaboratoryCategory = async (data) => {
    loading.value = true;
    error.value = null;
    try {
      await laboratoryCategoriesService.createLaboratoryCategory(data);
      return true;
    } catch (err) {
      error.value = err.response?.data?.errors || err.response?.data?.message || 'Gagal menambahkan kategori lab';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const updateLaboratoryCategory = async (id, data) => {
    loading.value = true;
    error.value = null;
    try {
      await laboratoryCategoriesService.updateLaboratoryCategory(id, data);
      return true;
    } catch (err) {
      error.value = err.response?.data?.errors || err.response?.data?.message || 'Gagal memperbarui kategori lab';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const updateStatus = async (id, isActive) => {
    loading.value = true;
    error.value = null;
    try {
      await laboratoryCategoriesService.updateStatus(id, isActive);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memperbarui status kategori lab';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const deleteLaboratoryCategory = async (id) => {
    loading.value = true;
    error.value = null;
    try {
      await laboratoryCategoriesService.deleteLaboratoryCategory(id);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menghapus kategori lab';
      return false;
    } finally {
      loading.value = false;
    }
  };

  return {
    laboratoryCategories,
    meta,
    loading,
    error,
    fetchLaboratoryCategories,
    createLaboratoryCategory,
    updateLaboratoryCategory,
    updateStatus,
    deleteLaboratoryCategory
  };
}
