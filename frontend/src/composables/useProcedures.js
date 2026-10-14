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
            const cleanParams = {
                page: pagination.current_page,
                per_page: pagination.per_page
            };
            Object.keys(params).forEach(key => {
                if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
                    cleanParams[key] = params[key];
                }
            });
            const response = await procedureService.getProcedures(cleanParams);
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

    const exporting = ref(false);

    const exportProcedures = async (params = {}) => {
        exporting.value = true;
        try {
            const cleanParams = {};
            Object.keys(params).forEach(key => {
                if (key !== 'page' && key !== 'per_page') {
                    if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
                        cleanParams[key] = params[key];
                    }
                }
            });

            const response = await procedureService.exportProcedures(cleanParams);

            // If backend returned a JSON response (e.g. empty results or message)
            if (response.data?.type?.includes('application/json') || response.headers?.['content-type']?.includes('application/json')) {
                let json = {};
                try {
                    const text = typeof response.data.text === 'function' ? await response.data.text() : JSON.stringify(response.data);
                    json = JSON.parse(text);
                } catch {
                    // ignore
                }
                return {
                    success: false,
                    empty: true,
                    message: json.message || 'Tidak ada data tindakan yang dapat diunduh.'
                };
            }

            // Extract filename from Content-Disposition if present
            let filename = `data-tindakan-${new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19)}.xlsx`;
            const disposition = response.headers?.['content-disposition'];
            if (disposition) {
                const match = disposition.match(/filename=["']?([^"';]+)["']?/i);
                if (match && match[1]) {
                    filename = match[1].trim();
                }
            }

            // Trigger browser download safely
            const blob = new Blob([response.data], {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            });
            const downloadUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(downloadUrl);

            return {
                success: true,
                message: 'Data tindakan berhasil diunduh.'
            };
        } catch (err) {
            let errorMessage = 'Data tindakan gagal diunduh. Silakan coba lagi.';
            let isEmpty = false;

            if (err.response?.data instanceof Blob) {
                try {
                    const text = await err.response.data.text();
                    const json = JSON.parse(text);
                    if (json.message) {
                        errorMessage = json.message;
                        if (json.message.includes('Tidak ada data')) {
                            isEmpty = true;
                        }
                    }
                } catch {
                    // Not JSON blob
                }
            } else if (err.response?.data?.message) {
                errorMessage = err.response.data.message;
                if (errorMessage.includes('Tidak ada data')) {
                    isEmpty = true;
                }
            }

            return {
                success: false,
                empty: isEmpty,
                message: errorMessage
            };
        } finally {
            exporting.value = false;
        }
    };

    return {
        procedures,
        pagination,
        loading,
        exporting,
        error,
        fetchProcedures,
        getProcedure,
        createProcedure,
        updateProcedure,
        updateProcedureVisibility,
        deleteProcedure,
        exportProcedures
    };
}
