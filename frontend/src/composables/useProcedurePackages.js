import { ref, reactive } from 'vue'
import ProcedurePackageService from '@/services/master-data/procedure-packages.service';

export function useProcedurePackages() {
    const packages = ref([]);
    
    const pagination = reactive({
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
      from: 0,
      to: 0
    });

    const loading = ref(false);
    const error = ref(null);

    const fetchPackages = async (params = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await ProcedurePackageService.getPackages({
                page: pagination.current_page,
                per_page: pagination.per_page,
                ...params
            });
            packages.value = response.data.data;
            if (response.data.meta) {
              pagination.current_page = response.data.meta.current_page;
              pagination.last_page = response.data.meta.last_page;
              pagination.per_page = response.data.meta.per_page;
              pagination.total = response.data.meta.total;
              pagination.from = response.data.meta.from;
              pagination.to = response.data.meta.to;
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal mengambil data paket tindakan';
            console.error('Error fetching packages:', err);
        } finally {
            loading.value = false;
        }
    };

    const getPackage = async (id) => {
        error.value = null;
        try {
            const response = await ProcedurePackageService.getProcedurePackage(id);
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal mengambil rincian paket tindakan';
            throw err;
        }
    };

    const createPackage = async (data) => {
        error.value = null;
        loading.value = true;
        try {
            const response = await ProcedurePackageService.createProcedurePackage(data);
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal menyimpan paket tindakan';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const updatePackage = async (id, data) => {
        error.value = null;
        loading.value = true;
        try {
            const response = await ProcedurePackageService.updateProcedurePackage(id, data);
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal memperbarui paket tindakan';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const deletePackage = async (id) => {
        error.value = null;
        loading.value = true;
        try {
            await ProcedurePackageService.deleteProcedurePackage(id);
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal menghapus paket tindakan';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    return {
        packages,
        pagination,
        loading,
        error,
        fetchPackages,
        getPackage,
        createPackage,
        updatePackage,
        deletePackage
    };
}
