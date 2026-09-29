import { ref } from 'vue'
import reportGroupService from '@/services/reportGroup';

export function useReportGroups() {
    const reportGroups = ref([]);
    const loading = ref(false);
    const error = ref(null);

    const fetchReportGroups = async (params = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await reportGroupService.getReportGroups(params);
            reportGroups.value = response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal mengambil data Kelompok Laporan';
            console.error('Error fetching Report Groups:', err);
        } finally {
            loading.value = false;
        }
    };

    return {
        reportGroups,
        loading,
        error,
        fetchReportGroups
    };
}
