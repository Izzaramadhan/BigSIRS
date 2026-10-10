import { ref, reactive } from 'vue'
import procedureService from '@/services/procedure';

export function useProcedures() {
    const procedures = ref([]);
    
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

    const fetchProcedures = async (params = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await procedureService.getProcedures({
                page: pagination.current_page,
                per_page: pagination.per_page,
                ...params
            });
            procedures.value = response.data.data;
            if (response.data.meta) {
              pagination.current_page = response.data.meta.current_page;
              pagination.last_page = response.data.meta.last_page;
              pagination.per_page = response.data.meta.per_page;
              pagination.total = response.data.meta.total;
              pagination.from = response.data.meta.from;
              pagination.to = response.data.meta.to;
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal mengambil data tindakan';
            console.error('Error fetching procedures:', err);
        } finally {
            loading.value = false;
        }
    };

    const getProcedure = async (id) => {
        error.value = null;
        try {
            const response = await procedureService.getProcedure(id);
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal mengambil rincian tindakan';
            throw err;
        }
    };

    const createProcedure = async (data) => {
        error.value = null;
        loading.value = true;
        try {
            const response = await procedureService.createProcedure(data);
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal menyimpan tindakan';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const updateProcedure = async (id, data) => {
        error.value = null;
        loading.value = true;
        try {
            const response = await procedureService.updateProcedure(id, data);
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal memperbarui tindakan';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const updateProcedureVisibility = async (id, isVisible) => {
        error.value = null;
        try {
            const response = await procedureService.updateProcedureVisibility(id, isVisible);
            const index = procedures.value.findIndex(item => item.id === id);
            if (index !== -1) {
                procedures.value[index].is_visible = isVisible;
            }
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal memperbarui status visibilitas tindakan';
            throw err;
        }
    };

    const deleteProcedure = async (id) => {
        error.value = null;
        loading.value = true;
        try {
            await procedureService.deleteProcedure(id);
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal menghapus tindakan';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    return {
        procedures,
        pagination,
        loading,
        error,
        fetchProcedures,
        getProcedure,
        createProcedure,
        updateProcedure,
        updateProcedureVisibility,
        deleteProcedure
    };
}
