import { ref } from 'vue';
import { medicineCategoriesService } from '@/services/master-data/medicineCategories.service';

export function useMedicineCategories() {
  const medicineCategories = ref([]);
  const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  });
  
  const loading = ref(false);
  const error = ref(null);

  const fetchMedicineCategories = async (params = {}) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await medicineCategoriesService.getMedicineCategories(params);
      medicineCategories.value = response.data;
      if (response.meta) {
        meta.value = response.meta;
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data kategori obat';
    } finally {
      loading.value = false;
    }
  };

  const createMedicineCategory = async (data) => {
    loading.value = true;
    error.value = null;
    try {
      await medicineCategoriesService.createMedicineCategory(data);
      return true;
    } catch (err) {
      error.value = err.response?.data?.errors || err.response?.data?.message || 'Gagal menambahkan kategori obat';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const updateMedicineCategory = async (id, data) => {
    loading.value = true;
    error.value = null;
    try {
      await medicineCategoriesService.updateMedicineCategory(id, data);
      return true;
    } catch (err) {
      error.value = err.response?.data?.errors || err.response?.data?.message || 'Gagal memperbarui kategori obat';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const deleteMedicineCategory = async (id) => {
    loading.value = true;
    error.value = null;
    try {
      await medicineCategoriesService.deleteMedicineCategory(id);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menghapus kategori obat';
      return false;
    } finally {
      loading.value = false;
    }
  };

  return {
    medicineCategories,
    meta,
    loading,
    error,
    fetchMedicineCategories,
    createMedicineCategory,
    updateMedicineCategory,
    deleteMedicineCategory
  };
}
