import { ref } from 'vue'
import tariffTypeService from '@/services/tariffType';

export function useTariffTypes() {
    const tariffTypes = ref([]);
    const totalItems = ref(0);
    const currentPage = ref(1);
    const perPage = ref(10);
    const loading = ref(false);
    const error = ref(null);

    const fetchTariffTypes = async (params = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await tariffTypeService.getTariffTypes({
                page: currentPage.value,
                per_page: perPage.value,
                ...params
            });
            tariffTypes.value = response.data.data;
            totalItems.value = response.data.meta.total;
            currentPage.value = response.data.meta.current_page;
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal mengambil data jenis tarif';
            console.error('Error fetching tariff types:', err);
        } finally {
            loading.value = false;
        }
    };

    const createTariffType = async (data) => {
        error.value = null;
        loading.value = true;
        try {
            const response = await tariffTypeService.createTariffType(data);
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal menyimpan jenis tarif';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const updateTariffType = async (id, data) => {
        error.value = null;
        loading.value = true;
        try {
            const response = await tariffTypeService.updateTariffType(id, data);
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal memperbarui jenis tarif';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const updateTariffTypeStatus = async (id, isActive) => {
        error.value = null;
        try {
            const response = await tariffTypeService.updateTariffTypeStatus(id, isActive);
            const index = tariffTypes.value.findIndex(item => item.id === id);
            if (index !== -1) {
                tariffTypes.value[index].is_active = isActive;
            }
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal memperbarui status jenis tarif';
            throw err;
        }
    };

    const deleteTariffType = async (id) => {
        error.value = null;
        loading.value = true;
        try {
            await tariffTypeService.deleteTariffType(id);
        } catch (err) {
            if (err.response?.status === 409) {
                error.value = 'Jenis tarif tidak dapat dihapus karena masih digunakan.';
            } else {
                error.value = err.response?.data?.message || err.message || 'Gagal menghapus jenis tarif';
            }
            throw err;
        } finally {
            loading.value = false;
        }
    };

    return {
        tariffTypes,
        totalItems,
        currentPage,
        perPage,
        loading,
        error,
        fetchTariffTypes,
        createTariffType,
        updateTariffType,
        updateTariffTypeStatus,
        deleteTariffType
    };
}
