import { ref } from 'vue'
import tariffComponentService from '@/services/tariffComponent';

export function useTariffComponents() {
    const tariffComponents = ref([]);
    const totalItems = ref(0);
    const currentPage = ref(1);
    const perPage = ref(10);
    const loading = ref(false);
    const error = ref(null);

    const fetchTariffComponents = async (params = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await tariffComponentService.getTariffComponents({
                page: currentPage.value,
                per_page: perPage.value,
                ...params
            });
            tariffComponents.value = response.data.data;
            totalItems.value = response.data.meta.total;
            currentPage.value = response.data.meta.current_page;
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal mengambil data komponen tarif';
            console.error('Error fetching tariff components:', err);
        } finally {
            loading.value = false;
        }
    };

    const createTariffComponent = async (data) => {
        error.value = null;
        loading.value = true;
        try {
            const response = await tariffComponentService.createTariffComponent(data);
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal menyimpan komponen tarif';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const updateTariffComponent = async (id, data) => {
        error.value = null;
        loading.value = true;
        try {
            const response = await tariffComponentService.updateTariffComponent(id, data);
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal memperbarui komponen tarif';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const updateTariffComponentStatus = async (id, isActive) => {
        error.value = null;
        try {
            const response = await tariffComponentService.updateTariffComponentStatus(id, isActive);
            
            // Update local state directly
            const index = tariffComponents.value.findIndex(item => item.id === id);
            if (index !== -1) {
                tariffComponents.value[index].is_active = isActive;
            }
            
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal memperbarui status komponen tarif';
            throw err;
        }
    };

    const deleteTariffComponent = async (id) => {
        error.value = null;
        loading.value = true;
        try {
            await tariffComponentService.deleteTariffComponent(id);
        } catch (err) {
            if (err.response?.status === 409) {
                error.value = 'Komponen tarif tidak dapat dihapus karena masih digunakan.';
            } else {
                error.value = err.response?.data?.message || err.message || 'Gagal menghapus komponen tarif';
            }
            throw err;
        } finally {
            loading.value = false;
        }
    };

    return {
        tariffComponents,
        totalItems,
        currentPage,
        perPage,
        loading,
        error,
        fetchTariffComponents,
        createTariffComponent,
        updateTariffComponent,
        updateTariffComponentStatus,
        deleteTariffComponent
    };
}
