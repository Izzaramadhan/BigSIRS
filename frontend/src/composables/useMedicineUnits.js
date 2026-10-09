import { ref } from 'vue';
import { medicineUnitsService } from '@/services/master-data/medicineUnits.service';

export function useMedicineUnits() {
  const medicineUnits = ref([]);
  const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  });
  
  const loading = ref(false);
  const error = ref(null);

  const fetchMedicineUnits = async (params = {}) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await medicineUnitsService.getMedicineUnits(params);
      medicineUnits.value = response.data;
      if (response.meta) {
        meta.value = response.meta;
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data satuan obat';
    } finally {
      loading.value = false;
    }
  };

  const createMedicineUnit = async (data) => {
    loading.value = true;
    error.value = null;
    try {
      await medicineUnitsService.createMedicineUnit(data);
      return true;
    } catch (err) {
      error.value = err.response?.data?.errors || err.response?.data?.message || 'Gagal menambahkan satuan obat';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const updateMedicineUnit = async (id, data) => {
    loading.value = true;
    error.value = null;
    try {
      await medicineUnitsService.updateMedicineUnit(id, data);
      return true;
    } catch (err) {
      error.value = err.response?.data?.errors || err.response?.data?.message || 'Gagal memperbarui satuan obat';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const deleteMedicineUnit = async (id) => {
    loading.value = true;
    error.value = null;
    try {
      await medicineUnitsService.deleteMedicineUnit(id);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menghapus satuan obat';
      return false;
    } finally {
      loading.value = false;
    }
  };

  return {
    medicineUnits,
    meta,
    loading,
    error,
    fetchMedicineUnits,
    createMedicineUnit,
    updateMedicineUnit,
    deleteMedicineUnit
  };
}
