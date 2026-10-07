import { ref } from 'vue';
import letterTypeService from '../services/letterType.service';

export function useLetterTypes() {
  const letterTypes = ref([]);
  const loading = ref(false);
  const error = ref(null);
  const meta = ref(null);

  const fetchLetterTypes = async (params = {}) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await letterTypeService.getLetterTypes(params);
      letterTypes.value = response.data;
      meta.value = response.meta;
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat data surat-surat';
    } finally {
      loading.value = false;
    }
  };

  const createLetterType = async (data) => {
    loading.value = true;
    error.value = null;
    try {
      await letterTypeService.createLetterType(data);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menambahkan surat';
      if (err.response?.data?.errors) {
        throw err.response.data.errors;
      }
      return false;
    } finally {
      loading.value = false;
    }
  };

  const updateLetterType = async (id, data) => {
    loading.value = true;
    error.value = null;
    try {
      await letterTypeService.updateLetterType(id, data);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memperbarui surat';
      if (err.response?.data?.errors) {
        throw err.response.data.errors;
      }
      return false;
    } finally {
      loading.value = false;
    }
  };

  const deleteLetterType = async (id) => {
    loading.value = true;
    error.value = null;
    try {
      await letterTypeService.deleteLetterType(id);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal menghapus surat';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const updateStatus = async (id, isActive) => {
    try {
      await letterTypeService.updateStatus(id, isActive);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memperbarui status surat';
      return false;
    }
  };

  return {
    letterTypes,
    loading,
    error,
    meta,
    fetchLetterTypes,
    createLetterType,
    updateLetterType,
    deleteLetterType,
    updateStatus,
  };
}
